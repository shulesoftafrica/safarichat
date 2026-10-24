<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Imports a business's catalog from a public web store / marketplace page
 * (e.g. a Jiji shop, or any site that publishes schema.org JSON-LD products)
 * into SafariChat's products table. Answers come from the synced catalog — we
 * never live-fetch per customer message.
 *
 * Strategy per page: site-specific parser first (Jiji), then generic JSON-LD,
 * then give up gracefully. Products are upserted idempotently keyed by
 * (business, external_source='web', external_ref).
 */
class WebCatalogImporter
{
    private int $connectTimeout = 8;
    private int $timeout = 20;

    /**
     * Fetch one page and return a normalized preview WITHOUT saving anything.
     *
     * @return array{ok:bool, message:string, source:string, items:array, more:bool}
     */
    public function preview(string $url): array
    {
        $url = $this->normalizeUrl($url);
        if (! $url) {
            return ['ok' => false, 'message' => 'Please enter a valid store URL (starting with http).', 'source' => '', 'items' => [], 'more' => false];
        }

        try {
            $html = $this->fetch($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not open that page. Check the URL is public and correct.', 'source' => '', 'items' => [], 'more' => false];
        }
        if ($html === null) {
            return ['ok' => false, 'message' => 'That page returned an error or was blocked.', 'source' => '', 'items' => [], 'more' => false];
        }

        [$source, $items] = $this->parse($html, $url);

        if (empty($items)) {
            return ['ok' => false, 'message' => 'No products found on that page. Make sure it is a shop/listing page.', 'source' => $source, 'items' => [], 'more' => false];
        }

        return [
            'ok'      => true,
            'message' => 'Found ' . count($items) . ' product(s) on the first page.',
            'source'  => $source,
            'items'   => array_slice($items, 0, 30),
            'more'    => $this->hasNextPage($html, $url, count($items)),
        ];
    }

    /**
     * Crawl the store (paginated, capped) and upsert products into the catalog.
     *
     * @return array{ok:bool, message:string, created:int, updated:int, pages:int}
     */
    public function import(int $businessId, ?int $ownerUserId, string $url, int $maxItems = 200): array
    {
        $url = $this->normalizeUrl($url);
        if (! $url) {
            return ['ok' => false, 'message' => 'Invalid store URL.', 'created' => 0, 'updated' => 0, 'pages' => 0];
        }

        $created = 0; $updated = 0; $page = 1; $seen = [];
        $maxPages = (int) ceil($maxItems / 15) + 2;

        try {
            for (; $page <= $maxPages; $page++) {
                $pageUrl = $this->pageUrl($url, $page);
                $html = $this->fetch($pageUrl);
                if ($html === null) { break; }

                [$source, $items] = $this->parse($html, $url);
                if (empty($items)) { break; }

                $newOnPage = 0;
                foreach ($items as $item) {
                    $ref = $item['ref'] ?? '';
                    if ($ref === '' || isset($seen[$ref])) { continue; }
                    $seen[$ref] = true;
                    $newOnPage++;

                    [$didCreate] = $this->upsert($businessId, $ownerUserId, $item);
                    $didCreate ? $created++ : $updated++;

                    if (($created + $updated) >= $maxItems) { break 2; }
                }

                // A feed is a single file with all products — never paginate it.
                if ($this->isFeedSource($source)) { break; }
                if ($newOnPage === 0) { break; }   // no fresh items -> stop
                usleep(600000); // be polite between page fetches
            }

            return [
                'ok'      => true,
                'message' => "Imported {$created} new product(s)" . ($updated ? ", updated {$updated}" : '') . '.',
                'created' => $created, 'updated' => $updated, 'pages' => $page - 1,
            ];
        } catch (\Throwable $e) {
            Log::error('Web catalog import failed', ['url' => $url, 'error' => $e->getMessage()]);
            return ['ok' => ($created + $updated) > 0, 'message' => 'Import stopped early: ' . $e->getMessage(), 'created' => $created, 'updated' => $updated, 'pages' => max(0, $page - 1)];
        }
    }

    // --- parsing ---------------------------------------------------------

    /** @return array{0:string,1:array} [source, items] */
    private function parse(string $html, string $baseUrl): array
    {
        $trim = ltrim($html);

        // A product FEED (Facebook / Google Merchant) — XML (RSS with g: namespace
        // or Atom) or CSV. This is the legit way to pull a Facebook/Instagram
        // catalog: the seller generates a feed URL in Commerce Manager.
        if (stripos($trim, '<?xml') === 0 || stripos($trim, '<rss') !== false || stripos($trim, '<feed') !== false) {
            $items = $this->parseFeedXml($html);
            if (! empty($items)) { return ['feed_xml', $items]; }
        }
        if ($this->looksLikeCsvFeed($trim)) {
            $items = $this->parseFeedCsv($html);
            if (! empty($items)) { return ['feed_csv', $items]; }
        }

        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        if (stripos($host, 'jiji.') !== false) {
            $items = $this->parseJiji($html, $baseUrl);
            if (! empty($items)) { return ['jiji', $items]; }
        }

        $items = $this->parseJsonLd($html, $baseUrl);
        if (! empty($items)) { return ['jsonld', $items]; }

        return ['unknown', []];
    }

    /** True if source is a product feed (import should not paginate). */
    private function isFeedSource(string $source): bool
    {
        return $source === 'feed_xml' || $source === 'feed_csv';
    }

    /** Parse a Facebook/Google Merchant product feed (RSS 2.0 with g: namespace). */
    private function parseFeedXml(string $xml): array
    {
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) { return []; }

        // RSS: channel/item ; Atom: entry
        $items = [];
        $nodes = $sx->channel->item ?? ($sx->item ?? ($sx->entry ?? null));
        if ($nodes === null) { return []; }

        foreach ($nodes as $node) {
            $g = $node->children('http://base.google.com/ns/1.0'); // g: namespace
            $id    = (string) ($g->id ?? $node->guid ?? $node->id ?? '');
            $title = (string) ($node->title ?? $g->title ?? '');
            if ($id === '' && $title === '') { continue; }
            if ($id === '') { $id = md5($title); }

            $avail = strtolower((string) ($g->availability ?? ''));
            $img   = (string) ($g->image_link ?? '');
            $cat   = (string) ($g->product_type ?? ($g->google_product_category ?? ($g->brand ?? 'General')));

            $items[(string) $id] = [
                'ref'      => (string) $id,
                'name'     => $title !== '' ? $title : ('Product ' . $id),
                'price'    => $this->toNumber((string) ($g->price ?? $g->sale_price ?? '0')),
                'url'      => (string) ($node->link ?? $g->link ?? ''),
                'image'    => $img,
                'category' => $cat ?: 'General',
                'quantity' => (strpos($avail, 'in stock') !== false || strpos($avail, 'in_stock') !== false) ? 1 : 0,
            ];
        }
        return array_values($items);
    }

    /** Parse a CSV/TSV product feed (header row with id/title/price/...). */
    private function parseFeedCsv(string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));
        if (count($lines) < 2) { return []; }
        $delim = (substr_count($lines[0], "\t") > substr_count($lines[0], ',')) ? "\t" : ',';
        $headers = array_map(fn ($h) => strtolower(trim($h, " \"'")), str_getcsv($lines[0], $delim));

        $pick = function (array $row, array $keys, $default = '') use ($headers) {
            foreach ($keys as $k) {
                $i = array_search($k, $headers, true);
                if ($i !== false && isset($row[$i]) && $row[$i] !== '') { return $row[$i]; }
            }
            return $default;
        };

        $out = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') { continue; }
            $row = str_getcsv($lines[$i], $delim);
            $id   = $pick($row, ['id', 'sku', 'g:id']);
            $name = $pick($row, ['title', 'name']);
            if ($id === '' && $name === '') { continue; }
            if ($id === '') { $id = md5($name); }
            $avail = strtolower($pick($row, ['availability', 'g:availability']));
            $out[(string) $id] = [
                'ref'      => (string) $id,
                'name'     => $name ?: ('Product ' . $id),
                'price'    => $this->toNumber($pick($row, ['price', 'sale_price', 'g:price'], '0')),
                'url'      => $pick($row, ['link', 'url']),
                'image'    => $pick($row, ['image_link', 'image', 'image_url']),
                'category' => $pick($row, ['product_type', 'google_product_category', 'category', 'brand'], 'General'),
                'quantity' => (strpos($avail, 'in stock') !== false || strpos($avail, 'in_stock') !== false) ? 1 : 0,
            ];
        }
        return array_values($out);
    }

    private function looksLikeCsvFeed(string $s): bool
    {
        $first = strtolower(substr($s, 0, strpos($s . "\n", "\n")));
        return (strpos($first, 'id') !== false || strpos($first, 'sku') !== false)
            && (strpos($first, 'title') !== false || strpos($first, 'name') !== false)
            && (strpos($first, ',') !== false || strpos($first, "\t") !== false);
    }

    /** Parse Jiji listing cards (server-rendered HTML). */
    private function parseJiji(string $html, string $baseUrl): array
    {
        $origin = $this->origin($baseUrl);
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xp = new \DOMXPath($doc);

        $out = [];
        foreach ($xp->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            if (! preg_match('#-([A-Za-z0-9]{8,})\.html#', $href, $idm)) {
                continue; // not an advert detail link
            }
            $priceNode = $xp->query('.//*[contains(@class,"qa-advert-price")]', $a);
            if (! $priceNode->length) { continue; }

            $priceText  = $this->norm($priceNode->item(0)->textContent);
            $headerNode = $xp->query('.//*[contains(@class,"__data__header")]', $a);
            $headerText = $headerNode->length ? $this->norm($headerNode->item(0)->textContent) : $this->norm($a->textContent);

            // Title = header minus the price text; fall back to the URL slug.
            $title = trim(str_replace($priceText, '', $headerText));
            if ($title === '' || mb_strlen($title) < 2) {
                $title = $this->titleFromSlug($href);
            }

            $imgNode = $xp->query('.//img', $a);
            $img = '';
            if ($imgNode->length) {
                $img = $imgNode->item(0)->getAttribute('src') ?: $imgNode->item(0)->getAttribute('data-src');
            }

            $out[$idm[1]] = [
                'ref'      => $idm[1],
                'name'     => $title,
                'price'    => $this->toNumber($priceText),
                'url'      => $this->absolute($origin, $href),
                'image'    => $img,
                'category' => $this->jijiCategory($href),
            ];
        }

        return array_values($out);
    }

    /** Generic schema.org JSON-LD Product / ItemList parser (Shopify, Woo, …). */
    private function parseJsonLd(string $html, string $baseUrl): array
    {
        $origin = $this->origin($baseUrl);
        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m)) {
            return [];
        }

        $products = [];
        foreach ($m[1] as $block) {
            $json = json_decode(trim($block), true);
            if (! is_array($json)) { continue; }
            $this->collectProducts($json, $products);
        }

        $out = [];
        foreach ($products as $p) {
            $name  = $p['name'] ?? null;
            $offers = $p['offers'] ?? null;
            $price = null;
            if (is_array($offers)) {
                $o = isset($offers[0]) ? $offers[0] : $offers;
                $price = $o['price'] ?? ($o['priceSpecification']['price'] ?? null);
            }
            if (! $name || $price === null) { continue; }

            $urlp = $p['url'] ?? ($p['@id'] ?? '');
            $ref  = $p['sku'] ?? ($p['productID'] ?? ($urlp ?: md5($name)));
            $img  = $p['image'] ?? '';
            if (is_array($img)) { $img = $img[0] ?? ''; }

            $out[(string) $ref] = [
                'ref'      => (string) $ref,
                'name'     => (string) $name,
                'price'    => $this->toNumber((string) $price),
                'url'      => $urlp ? $this->absolute($origin, $urlp) : '',
                'image'    => (string) $img,
                'category' => is_array($p['category'] ?? null) ? ($p['category']['name'] ?? 'General') : ($p['category'] ?? 'General'),
            ];
        }

        return array_values($out);
    }

    private function collectProducts($node, array &$products): void
    {
        if (! is_array($node)) { return; }
        $type = $node['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];
        if (array_intersect(['Product', 'Car', 'Vehicle', 'IndividualProduct'], $types) && isset($node['name'])) {
            $products[] = $node;
        }
        foreach ($node as $v) {
            if (is_array($v)) { $this->collectProducts($v, $products); }
        }
    }

    // --- persistence -----------------------------------------------------

    /** @return array{0:bool} [created] */
    private function upsert(int $businessId, ?int $ownerUserId, array $item): array
    {
        $ref = (string) $item['ref'];
        $existing = Product::where('business_id', $businessId)
            ->where('external_source', 'web')
            ->where('external_ref', $ref)
            ->first();

        $fields = [
            'name'            => mb_substr(trim($item['name']) ?: ('Product ' . $ref), 0, 255),
            'category'        => mb_substr((string) ($item['category'] ?: 'General'), 0, 100),
            'description'     => mb_substr(trim($item['name']) . (empty($item['url']) ? '' : "\n" . $item['url']), 0, 2000),
            'retail_price'    => (float) ($item['price'] ?? 0),
            'wholesale_price' => (float) ($item['price'] ?? 0),
            'quantity'        => (int) round((float) ($item['quantity'] ?? 0)),
            'image_path'      => $item['image'] ?: null,
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
            'external_source' => 'web',
            'external_ref'    => $ref,
            'sku'             => 'WEB-' . strtoupper(substr($ref, 0, 20)),
        ]));
        return [true];
    }

    // --- helpers ---------------------------------------------------------

    private function fetch(string $url): ?string
    {
        $res = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (compatible; SafariChatBot/1.0; +https://safarichat.ai)',
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en;q=0.9',
            ])
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->get($url);

        return $res->successful() ? $res->body() : null;
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') { return null; }
        if (! preg_match('#^https?://#i', $url)) { $url = 'https://' . $url; }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function pageUrl(string $url, int $page): string
    {
        if ($page <= 1) { return $url; }
        return $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . $page;
    }

    private function hasNextPage(string $html, string $url, int $count): bool
    {
        // Heuristic: a full first page of listings implies more pages.
        return $count >= 15 || stripos($html, 'page=2') !== false || stripos($html, 'rel="next"') !== false;
    }

    private function origin(string $url): string
    {
        $p = parse_url($url);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '');
    }

    private function absolute(string $origin, string $href): string
    {
        if (preg_match('#^https?://#i', $href)) { return $href; }
        return $origin . '/' . ltrim($href, '/');
    }

    private function titleFromSlug(string $href): string
    {
        $path = parse_url($href, PHP_URL_PATH) ?: $href;
        $slug = basename($path, '.html');
        $slug = preg_replace('#-[A-Za-z0-9]{8,}$#', '', $slug); // strip trailing id
        return ucwords(trim(str_replace('-', ' ', $slug)));
    }

    private function jijiCategory(string $href): string
    {
        $parts = array_values(array_filter(explode('/', parse_url($href, PHP_URL_PATH) ?: '')));
        // /<region>/<category>/<slug>.html
        return isset($parts[1]) ? ucfirst(str_replace('-', ' ', $parts[1])) : 'General';
    }

    private function toNumber(string $s): float
    {
        $clean = preg_replace('/[^0-9.]/', '', $s);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function norm(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
