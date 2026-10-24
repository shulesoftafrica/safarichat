<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\CatalogApiConnection;
use App\Models\LineShopConnection;
use App\Models\Product;
use App\Models\WebCatalogConnection;
use App\Services\GenericApiCatalogConnector;
use App\Services\LineShopConnector;
use App\Services\WebCatalogImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Auto-refresh every connected catalog source (LineShop, generic API, web/feed)
 * so prices and stock stay current without the owner clicking "Sync now". Runs
 * inline (no queue worker needed) — safe for the scheduler, and web shops crawl
 * fully here since there's no web-request timeout.
 */
class CatalogSyncSourcesCommand extends Command
{
    protected $signature = 'catalog:sync-sources
        {--source=all : all|lineshop|api|web}
        {--business= : limit to one business_id}
        {--web-limit=2000 : max products per web source}';

    protected $description = 'Re-sync all connected catalog sources (LineShop, API, web/feed).';

    public function handle(): int
    {
        $source  = $this->option('source');
        $bizId   = $this->option('business') ? (int) $this->option('business') : null;
        $webLimit = (int) $this->option('web-limit');

        $done = 0;

        if ($source === 'all' || $source === 'lineshop') {
            $done += $this->syncLineShop($bizId);
        }
        if ($source === 'all' || $source === 'api') {
            $done += $this->syncApi($bizId);
        }
        if ($source === 'all' || $source === 'web') {
            $done += $this->syncWeb($bizId, $webLimit);
        }

        $this->info("Catalog sync finished: {$done} source(s) refreshed.");
        return Command::SUCCESS;
    }

    private function syncLineShop(?int $bizId): int
    {
        $q = LineShopConnection::where('status', LineShopConnection::STATUS_CONNECTED);
        if ($bizId) { $q->where('business_id', $bizId); }

        $connector = app(LineShopConnector::class);
        $n = 0;
        foreach ($q->get() as $conn) {
            try {
                $r = $connector->syncCatalog($conn);
                $this->line("LineShop #{$conn->business_id}: " . ($r['message'] ?? 'done'));
                $n++;
            } catch (\Throwable $e) {
                Log::warning('catalog:sync-sources LineShop failed', ['business_id' => $conn->business_id, 'error' => $e->getMessage()]);
            }
        }
        return $n;
    }

    private function syncApi(?int $bizId): int
    {
        $q = CatalogApiConnection::where('status', CatalogApiConnection::STATUS_CONNECTED);
        if ($bizId) { $q->where('business_id', $bizId); }

        $connector = app(GenericApiCatalogConnector::class);
        $n = 0;
        foreach ($q->get() as $conn) {
            try {
                $r = $connector->sync($conn);
                $this->line("API #{$conn->business_id}: " . ($r['message'] ?? 'done'));
                $n++;
            } catch (\Throwable $e) {
                Log::warning('catalog:sync-sources API failed', ['business_id' => $conn->business_id, 'error' => $e->getMessage()]);
            }
        }
        return $n;
    }

    private function syncWeb(?int $bizId, int $webLimit): int
    {
        $q = WebCatalogConnection::query();
        if ($bizId) { $q->where('business_id', $bizId); }

        $importer = app(WebCatalogImporter::class);
        $n = 0;
        foreach ($q->get() as $conn) {
            $owner = Business::find($conn->business_id)?->user_id;
            try {
                $r = $importer->import($conn->business_id, $owner, $conn->url, $webLimit);
                $conn->update([
                    'status'         => $r['ok'] ? 'connected' : 'error',
                    'last_synced_at' => now(),
                    'product_count'  => Product::where('business_id', $conn->business_id)->where('external_source', 'web')->count(),
                    'last_error'     => $r['ok'] ? null : ($r['message'] ?? 'sync failed'),
                ]);
                $this->line("Web #{$conn->business_id} ({$conn->url}): " . ($r['message'] ?? 'done'));
                $n++;
            } catch (\Throwable $e) {
                $conn->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                Log::warning('catalog:sync-sources Web failed', ['business_id' => $conn->business_id, 'url' => $conn->url, 'error' => $e->getMessage()]);
            }
        }
        return $n;
    }
}
