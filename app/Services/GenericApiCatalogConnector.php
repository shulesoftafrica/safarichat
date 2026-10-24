<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CatalogApiConnection;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Connects SafariChat to any stock-management software that exposes a product
 * read API (base URL + auth header). The external API should return JSON: a top
 * array, or {data|products: [...]}, where each product carries name/price and
 * (ideally) a sku/code, stock, category and image. Keys are matched flexibly so
 * most APIs work without mapping. Products sync into the catalog, upserted
 * idempotently keyed by (business, external_source='api', external_ref).
 */
class GenericApiCatalogConnector
{
    private int $connectTimeout = 8;
    private int $timeout = 20;

    /**
     * Validate a config by fetching one page.
     *
     * @return array{ok:bool, message:string, items:array}
     */
    public function test(array $cfg): array
    {
        $url = $this->catalogUrl($cfg);
        if (! $url) {
            return ['ok' => false, 'message' => 'Enter a valid Base URL.', 'items' => []];
        }

        try {
            $res = Http::withHeaders($this->headers($cfg))
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->get($url, ['limit' => 5, 'page' => 1]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach that API. Check the Base URL and that it is online.', 'items' => []];
        }

        if ($res->status() === 401 || $res->status() === 403) {
            return ['ok' => false, 'message' => 'The API rejected the credentials (check the auth header and token).', 'items' => []];
        }
        if (! $res->successful()) {
            return ['ok' => false, 'message' => 'The API returned HTTP ' . $res->status() . '.', 'items' => []];
        }

        $items = $this->normalize($this->extractItems($res->json()));
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Connected, but no products were found in the response. Check the catalog path and response shape.', 'items' => []];
        }

        return ['ok' => true, 'message' => 'Connected — found products.', 'items' => array_slice($items, 0, 5)];
    }

    /**
     * Pull the catalog and upsert into products.
     *
     * @return array{ok:bool, message:string, created:int, updated:int}
     */
    public function sync(CatalogApiConnection $conn): array
    {
        $business = Business::find($conn->business_id);
        $ownerId  = $business->user_id ?? null;

        $cfg = $conn->toArray();
        $cfg['auth_value'] = $conn->auth_value; // hidden attr, add back explicitly
        $url = $this->catalogUrl($cfg);

        $created = 0; $updated = 0; $page = 1; $maxPages = 2000;

        try {
            for (; $page <= $maxPages; $page++) {
                $res = Http::withHeaders($this->headers($cfg))
                    ->connectTimeout($this->connectTimeout)
                    ->timeout($this->timeout)
                    ->get($url, [$conn->page_param ?: 'page' => $page, 'limit' => 200]);

                if (! $res->successful()) {
                    $conn->update(['status' => CatalogApiConnection::STATUS_ERROR, 'last_error' => 'Sync failed on page ' . $page . ' (HTTP ' . $res->status() . ')']);
                    return ['ok' => false, 'message' => 'Sync stopped on page ' . $page . ' (HTTP ' . $res->status() . ').', 'created' => $created, 'updated' => $updated];
                }

                $items = $this->normalize($this->extractItems($res->json()));
                if (empty($items)) { break; }

                foreach ($items as $it) {
                    [$didCreate] = $this->upsert($conn->business_id, $ownerId, $it);
                    $didCreate ? $created++ : $updated++;
                }

                if (count($items) < 200) { break; } // last page
            }

            $conn->update([
                'status'         => CatalogApiConnection::STATUS_CONNECTED,
                'last_synced_at' => now(),
                'product_count'  => Product::where('business_id', $conn->business_id)->where('external_source', 'api')->count(),
                'last_error'     => null,
            ]);

            return ['ok' => true, 'message' => "Synced {$created} new, {$updated} updated.", 'created' => $created, 'updated' => $updated];
        } catch (\Throwable $e) {
            Log::error('Generic API sync failed', ['business_id' => $conn->business_id, 'error' => $e->getMessage()]);
            $conn->update(['status' => CatalogApiConnection::STATUS_ERROR, 'last_error' => $e->getMessage()]);
            return ['ok' => ($created + $updated) > 0, 'message' => 'Sync failed: ' . $e->getMessage(), 'created' => $created, 'updated' => $updated];
        }
    }

    // --- helpers ---------------------------------------------------------

    /** @return array{0:bool} [created] */
    private function upsert(int $businessId, ?int $ownerUserId, array $it): array
    {
        $ref = (string) $it['ref'];
        $existing = Product::where('business_id', $businessId)
            ->where('external_source', 'api')
            ->where('external_ref', $ref)
            ->first();

        $retail = (float) ($it['price'] ?? 0);
        $cost   = (float) ($it['cost'] ?? $retail);
        if ($cost > $retail) { $cost = $retail; }

        $fields = [
            'name'            => mb_substr(trim($it['name']) ?: ('Product ' . $ref), 0, 255),
            'category'        => mb_substr((string) ($it['category'] ?: 'General'), 0, 100),
            'description'     => mb_substr((string) ($it['description'] ?: $it['name']), 0, 2000),
            'retail_price'    => $retail,
            'wholesale_price' => $cost,
            'quantity'        => (int) round((float) ($it['quantity'] ?? 0)),
            'image_path'      => $it['image'] ?: null,
            'product_type'    => 'tangible',
            'status'          => 'active',
            'external_synced_at' => now(),
        ];

        if ($existing) {
            $existing->update($fields);
            return [false];
        }
        if (! $ownerUserId) { return [false]; }

        Product::create(array_merge($fields, [
            'user_id'         => $ownerUserId,
            'business_id'     => $businessId,
            'external_source' => 'api',
            'external_ref'    => $ref,
            'sku'             => mb_substr(($it['sku'] ?: ('API-' . $ref)), 0, 100),
        ]));
        return [true];
    }

    /** Map loose incoming keys to canonical fields. */
    private function normalize(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) { continue; }
            $get = function (array $keys, $default = null) use ($row) {
                foreach ($keys as $k) {
                    foreach ($row as $rk => $rv) {
                        if (strcasecmp((string) $rk, $k) === 0 && $rv !== null && $rv !== '') { return $rv; }
                    }
                }
                return $default;
            };

            $ref  = $get(['code', 'sku', 'id', 'product_id', 'ref'], null);
            $name = $get(['name', 'title', 'product_name'], null);
            if ($ref === null && $name === null) { continue; }
            if ($ref === null) { $ref = md5((string) $name); }

            $out[(string) $ref] = [
                'ref'         => (string) $ref,
                'name'        => (string) ($name ?? ('Product ' . $ref)),
                'sku'         => (string) $get(['sku', 'code'], ''),
                'price'       => $this->toNumber($get(['price', 'retail_price', 'selling_price'], 0)),
                'cost'        => $this->toNumber($get(['cost', 'wholesale_price', 'cost_price'], 0)),
                'quantity'    => $this->toNumber($get(['quantity', 'stock', 'qty', 'available'], 0)),
                'category'    => (string) $get(['category', 'brand'], 'General'),
                'description' => (string) $get(['description', 'details'], ''),
                'image'       => (string) $get(['image', 'image_url', 'photo', 'image_path'], ''),
            ];
        }
        return array_values($out);
    }

    private function extractItems($json): array
    {
        if (is_array($json)) {
            foreach (['data', 'products', 'items', 'results'] as $key) {
                if (isset($json[$key]) && is_array($json[$key])) { return $json[$key]; }
            }
            if (array_is_list($json)) { return $json; }
        }
        return [];
    }

    private function headers(array $cfg): array
    {
        $type  = $cfg['auth_type'] ?? 'bearer';
        $value = $cfg['auth_value'] ?? '';
        $base  = ['Accept' => 'application/json'];

        if ($type === 'bearer' && $value !== '') {
            $base['Authorization'] = 'Bearer ' . $value;
        } elseif ($type === 'header' && $value !== '') {
            $base[($cfg['auth_header'] ?: 'X-API-Key')] = $value;
        }
        return $base;
    }

    private function catalogUrl(array $cfg): ?string
    {
        $base = trim((string) ($cfg['base_url'] ?? ''));
        if ($base === '') { return null; }
        if (! preg_match('#^https?://#i', $base)) { $base = 'https://' . $base; }
        if (! filter_var($base, FILTER_VALIDATE_URL)) { return null; }
        return rtrim($base, '/') . '/' . ltrim((string) ($cfg['catalog_path'] ?? '/products'), '/');
    }

    private function toNumber($v): float
    {
        if (is_numeric($v)) { return (float) $v; }
        $clean = preg_replace('/[^0-9.]/', '', (string) $v);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
