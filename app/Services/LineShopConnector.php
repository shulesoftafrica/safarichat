<?php

namespace App\Services;

use App\Models\LineShopConnection;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to a business's LineShop (Stock Manager Advance) shop over its per-shop
 * API (X-Client-ID + X-API-Key). Responsible for validating a connection,
 * syncing the catalog into SafariChat's products table (idempotent upsert), and
 * live stock lookups.
 */
class LineShopConnector
{
    /**
     * Validate a base_url + client_id + api_key by hitting the catalog endpoint.
     *
     * @return array{ok:bool, message:string, shop_name:?string, sample:int}
     */
    public function test(string $baseUrl, int $clientId, string $apiKey): array
    {
        $url = $this->url($baseUrl, config('lineshop.catalog_path'));

        try {
            $res = Http::withHeaders($this->headers($clientId, $apiKey))
                ->timeout(config('lineshop.timeout', 20))
                ->get($url, ['limit' => 1, 'page' => 1]);

            if ($res->status() === 401 || $res->status() === 403) {
                return ['ok' => false, 'message' => 'LineShop rejected the credentials (check the Client ID, API key, and that the API is enabled for this shop).', 'shop_name' => null, 'sample' => 0];
            }
            if ($res->status() === 404) {
                return ['ok' => false, 'message' => 'The LineShop catalog endpoint was not found at that address. Check the store URL.', 'shop_name' => null, 'sample' => 0];
            }
            if (! $res->successful()) {
                return ['ok' => false, 'message' => 'LineShop returned an error (HTTP ' . $res->status() . '). Please try again.', 'shop_name' => null, 'sample' => 0];
            }

            $json = $res->json();
            $items = $this->extractItems($json);

            return [
                'ok'        => true,
                'message'   => 'Connected successfully.',
                'shop_name' => $json['shop_name'] ?? ($json['meta']['shop_name'] ?? null),
                'sample'    => count($items),
            ];
        } catch (\Throwable $e) {
            Log::warning('LineShop test connection failed', ['url' => $url, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Could not reach LineShop at that address. Check the store URL and that the server is online.', 'shop_name' => null, 'sample' => 0];
        }
    }

    /**
     * Pull the whole catalog and upsert it into SafariChat's products table,
     * keyed by (business_id, external_source=lineshop, external_ref=code).
     *
     * @return array{ok:bool, synced:int, pages:int, message:string}
     */
    public function syncCatalog(LineShopConnection $conn): array
    {
        $pageSize = (int) config('lineshop.page_size', 200);
        $maxPages = (int) config('lineshop.max_pages', 2000);
        $catalogUrl = $this->url($conn->base_url, config('lineshop.catalog_path'));

        $business = \App\Models\Business::find($conn->business_id);
        $ownerId  = $business->user_id ?? null;

        $synced = 0;
        $page = 1;

        try {
            for (; $page <= $maxPages; $page++) {
                $res = Http::withHeaders($this->headers($conn->client_id, $conn->api_key))
                    ->timeout(config('lineshop.timeout', 20))
                    ->get($catalogUrl, ['limit' => $pageSize, 'page' => $page]);

                if (! $res->successful()) {
                    $conn->update(['status' => LineShopConnection::STATUS_ERROR, 'last_error' => 'Sync failed on page ' . $page . ' (HTTP ' . $res->status() . ')']);
                    return ['ok' => false, 'synced' => $synced, 'pages' => $page - 1, 'message' => 'Sync stopped on page ' . $page . ' (HTTP ' . $res->status() . ').'];
                }

                $items = $this->extractItems($res->json());
                if (empty($items)) {
                    break; // no more pages
                }

                foreach ($items as $item) {
                    $this->upsertProduct($conn, $ownerId, $item);
                    $synced++;
                }

                if (count($items) < $pageSize) {
                    break; // last page
                }
            }

            $conn->update([
                'status'         => LineShopConnection::STATUS_CONNECTED,
                'last_synced_at' => now(),
                'product_count'  => Product::where('business_id', $conn->business_id)->where('external_source', 'lineshop')->count(),
                'last_error'     => null,
            ]);

            return ['ok' => true, 'synced' => $synced, 'pages' => $page, 'message' => "Synced {$synced} products from LineShop."];
        } catch (\Throwable $e) {
            Log::error('LineShop sync failed', ['business_id' => $conn->business_id, 'error' => $e->getMessage()]);
            $conn->update(['status' => LineShopConnection::STATUS_ERROR, 'last_error' => $e->getMessage()]);
            return ['ok' => false, 'synced' => $synced, 'pages' => max(0, $page - 1), 'message' => 'Sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Live stock for specific SKUs (codes). Returns [code => quantity].
     */
    public function fetchStock(LineShopConnection $conn, array $codes): array
    {
        if (empty($codes)) {
            return [];
        }
        try {
            $res = Http::withHeaders($this->headers($conn->client_id, $conn->api_key))
                ->timeout(config('lineshop.timeout', 20))
                ->get($this->url($conn->base_url, config('lineshop.stock_path')), ['codes' => implode(',', $codes)]);

            if (! $res->successful()) {
                return [];
            }
            $out = [];
            foreach ($this->extractItems($res->json()) as $row) {
                $code = $row['code'] ?? $row['sku'] ?? null;
                if ($code !== null) {
                    $out[(string) $code] = (float) ($row['quantity'] ?? 0);
                }
            }
            return $out;
        } catch (\Throwable $e) {
            Log::warning('LineShop stock fetch failed', ['business_id' => $conn->business_id, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // ---------------------------------------------------------------------

    private function upsertProduct(LineShopConnection $conn, ?int $ownerId, array $item): void
    {
        $code = (string) ($item['code'] ?? $item['sku'] ?? $item['id'] ?? '');
        if ($code === '') {
            return; // can't key it
        }

        $name  = trim((string) ($item['name'] ?? 'Product ' . $code));
        $price = (float) ($item['price'] ?? $item['retail_price'] ?? 0);
        $cost  = (float) ($item['cost'] ?? $item['wholesale_price'] ?? $price);
        // wholesale must not exceed retail in SafariChat's own forms; keep consistent.
        if ($cost > $price) {
            $cost = $price;
        }

        $fields = [
            'name'               => $name,
            'sku'                => $code,
            'category'           => (string) ($item['category'] ?? $item['brand'] ?? 'General'),
            'description'        => (string) ($item['description'] ?? $item['details'] ?? $name),
            'minimal_description'=> (string) ($item['brand'] ?? ''),
            'retail_price'       => $price,
            'wholesale_price'    => $cost,
            'quantity'           => (int) round((float) ($item['quantity'] ?? 0)),
            'image_path'         => $item['image_url'] ?? null,
            'product_type'       => 'tangible',
            'status'             => 'active',
            'external_synced_at' => now(),
        ];

        // Explicit find → update / create (avoids updateOrCreate's savepoint quirk,
        // and lets us guarantee user_id is set on create — it's NOT NULL).
        $product = Product::where('business_id', $conn->business_id)
            ->where('external_source', 'lineshop')
            ->where('external_ref', $code)
            ->first();

        if ($product) {
            $product->update($fields);
            return;
        }

        if (! $ownerId) {
            Log::warning('LineShop sync: cannot create product without an owner user_id', [
                'business_id' => $conn->business_id, 'code' => $code,
            ]);
            return;
        }

        Product::create(array_merge($fields, [
            'user_id'         => $ownerId,
            'business_id'     => $conn->business_id,
            'external_source' => 'lineshop',
            'external_ref'    => $code,
        ]));
    }

    /** Pull the list of items out of whatever envelope LineShop returns. */
    private function extractItems($json): array
    {
        if (is_array($json)) {
            if (isset($json['data']) && is_array($json['data'])) {
                return $json['data'];
            }
            if (isset($json['products']) && is_array($json['products'])) {
                return $json['products'];
            }
            // bare list
            if (array_is_list($json)) {
                return $json;
            }
        }
        return [];
    }

    private function headers(int $clientId, string $apiKey): array
    {
        return [
            'X-Client-ID' => (string) $clientId,
            'X-API-Key'   => $apiKey,
            'Accept'      => 'application/json',
        ];
    }

    private function url(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
