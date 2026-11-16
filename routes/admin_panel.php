<?php

use App\Http\Controllers\Api\AdminPanelController;
use App\Http\Controllers\Api\AdminWorkspaceController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel API — loaded by RouteServiceProvider under /api/admin-panel.
| Callers are authenticated by an HMAC signature (VerifyAdminPanelSignature), not a user session.
*/

Route::middleware('admin.panel.signature')->group(function () {
    Route::post('businesses/{businessId}/manage', [AdminPanelController::class, 'manage'])->whereNumber('businessId');
    Route::post('businesses/{businessId}/payment', [AdminPanelController::class, 'payment'])->whereNumber('businessId');
    Route::post('businesses/{businessId}/suspend', [AdminPanelController::class, 'suspend'])->whereNumber('businessId');
    Route::post('businesses/{businessId}/activate', [AdminPanelController::class, 'activate'])->whereNumber('businessId');
    Route::post('settings/onboarding-video', [AdminPanelController::class, 'onboardingVideo']);

    // Client workspace (the admin panel's "Impersonate")
    Route::prefix('businesses/{businessId}/workspace')->whereNumber('businessId')->controller(AdminWorkspaceController::class)->group(function () {
        Route::post('business', 'business');
        Route::post('agent', 'agent');

        Route::post('products', 'saveProduct');
        Route::post('products/import', 'importProducts');
        Route::post('products/web-preview', 'webPreview');
        Route::post('products/web-import', 'webImport');
        Route::post('products/{productId}', 'saveProduct')->whereNumber('productId');
        Route::post('products/{productId}/duplicate', 'duplicateProduct')->whereNumber('productId');
        Route::post('products/{productId}/status', 'productStatus')->whereNumber('productId');
        Route::post('products/{productId}/delete', 'deleteProduct')->whereNumber('productId');
        Route::post('products/{productId}/documents', 'uploadDocuments')->whereNumber('productId');
        Route::post('products/{productId}/documents/{attachmentId}/reprocess', 'reprocessDocument')->whereNumber(['productId', 'attachmentId']);
        Route::post('products/{productId}/documents/{attachmentId}/delete', 'deleteDocument')->whereNumber(['productId', 'attachmentId']);

        Route::post('users', 'addUser');
        Route::post('users/{userId}/delete', 'deleteUser')->whereNumber('userId');

        Route::post('whatsapp/{instanceId}/{action}', 'whatsapp')->whereNumber('instanceId')->whereIn('action', ['qr', 'status']);
    });
});
