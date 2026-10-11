<?php

/*
|--------------------------------------------------------------------------
| LineShop (Stock Manager Advance) integration
|--------------------------------------------------------------------------
|
| SafariChat connects to a business's LineShop shop to sync its product
| catalog + live stock. Auth is per-shop: X-Client-ID + X-API-Key validated
| against LineShop's sma_settings. Endpoint paths are configurable so they can
| track the LineShop API without code changes.
|
*/

return [
    'catalog_path'         => env('LINESHOP_CATALOG_PATH', '/api/v1/products/catalog'),
    'stock_path'           => env('LINESHOP_STOCK_PATH', '/api/v1/products/stock'),
    'connect_confirm_path' => env('LINESHOP_CONNECT_CONFIRM_PATH', '/api/v1/connect/confirm'),

    'timeout'   => (int) env('LINESHOP_HTTP_TIMEOUT', 20),  // seconds per request
    'page_size' => (int) env('LINESHOP_SYNC_PAGE_SIZE', 200),
    'max_pages' => (int) env('LINESHOP_SYNC_MAX_PAGES', 2000), // safety cap (200 * 2000 = 400k SKUs)
];
