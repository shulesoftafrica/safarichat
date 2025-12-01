<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ContactApiController;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */
//DB::table('api_requests')->insert(['content'=> json_encode(request()->all()),'url'=>url()->current()]);
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Contact Management API Routes
Route::middleware('auth:sanctum')->prefix('contacts')->name('api.contacts.')->group(function () {
    // Single contact operations
    Route::post('/', [ContactApiController::class, 'store'])->name('store');
    Route::get('/', [ContactApiController::class, 'index'])->name('index');
    
    // Bulk operations
    Route::post('/bulk', [ContactApiController::class, 'bulkStore'])->name('bulk.store');
    
    // Contact status update
    Route::put('/{contact}/status', [ContactApiController::class, 'updateContactStatus'])->name('status.update');
});



// Product Management API Routes
Route::post('products', [ProductController::class, 'store']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('products/{id}/edit', [ProductController::class, 'edit']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);
Route::post('products/bulk-action', [ProductController::class, 'bulkAction']);

// RAG Document Management API Routes
Route::prefix('products/{product}')->group(function () {
    Route::post('/attachments', [App\Http\Controllers\Api\ProductAttachmentController::class, 'store']);
    Route::get('/attachments', [App\Http\Controllers\Api\ProductAttachmentController::class, 'index']);
    Route::get('/attachments/{attachment}', [App\Http\Controllers\Api\ProductAttachmentController::class, 'show']);
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Api\ProductAttachmentController::class, 'destroy']);
    Route::post('/attachments/{attachment}/reprocess', [App\Http\Controllers\Api\ProductAttachmentController::class, 'reprocessRAG']);
});

// RAG Search API Routes
Route::prefix('documents')->group(function () {
    Route::post('/search', [App\Http\Controllers\Api\ProductAttachmentController::class, 'searchDocuments']);
    Route::get('/processing-status', [App\Http\Controllers\Api\ProductAttachmentController::class, 'getProcessingStatus']);
});

// OTP Authentication Routes
Route::post('/otp', 'Setup@otp');
Route::post('/otp/verify', 'Setup@otpverify');

Route::post('/whatsapp', 'Setup@whatsapp');
Route::any('/message','Setup@pushEmailsToSend');
Route::any('/sms/{code}/{imei?}/{model?}', 'Setup@pushPhoneSMS');
Route::any('/validate/{null}/{imei?}/{model?}/{param1?}/{id?}/{param3?}/{param4?}','Setup@aunthenticateMobile');
Route::any('/updatestatus/{code?}/{sms_id?}/{imei?}{device?}','Setup@updatestatus');
Route::any('/smsreport/{code?}/{imei?}/{model?}', 'Setup@smsReport');
// WaSender testing and management routes
Route::get('/wasender/test-connection', 'WaSenderController@testConnection');
Route::get('/wasender/user-instances', 'WaSenderController@getUserInstances');
Route::post('/wasender/send-test-message', 'WaSenderController@sendTestMessage');

// Queue testing routes
Route::post('/wasender/test-queue-message', 'WaSenderController@testQueueMessage');
Route::post('/wasender/test-incoming-message', 'WaSenderController@testIncomingMessage');
Route::get('/wasender/queue-stats', 'WaSenderController@getQueueStats');
Route::post('/wasender/clear-failed-jobs', 'WaSenderController@clearFailedJobs');
Route::post('/wasender/retry-failed-jobs', 'WaSenderController@retryFailedJobs');

// WaSender Incoming Message Processing
Route::post('/wasender/webhook/{instanceId}', 'WaSenderController@handleWebhook')->middleware(['throttle:webhooks']);;

// WaSender API endpoints for sending messages
Route::middleware('auth:sanctum')->prefix('wasender')->group(function () {
    // Text messages
    Route::post('/send/text', [App\Http\Controllers\Api\WaSenderApiController::class, 'sendTextMessage']);
    Route::post('/queue/text', [App\Http\Controllers\Api\WaSenderApiController::class, 'queueTextMessage']);
    
    // Media messages
    Route::post('/send/image', [App\Http\Controllers\Api\WaSenderApiController::class, 'sendImage']);
    Route::post('/send/document', [App\Http\Controllers\Api\WaSenderApiController::class, 'sendDocument']);
    Route::post('/queue/media', [App\Http\Controllers\Api\WaSenderApiController::class, 'queueMediaMessage']);
    
    // Location
    Route::post('/send/location', [App\Http\Controllers\Api\WaSenderApiController::class, 'sendLocation']);
    
    // Instance management
    Route::get('/instances', [App\Http\Controllers\Api\WaSenderApiController::class, 'getUserInstances']);
    Route::get('/instances/{instanceId}/status', [App\Http\Controllers\Api\WaSenderApiController::class, 'checkInstanceStatus']);
});

Route::post('/payment','Setup@apiAcceptPayment');
Route::post('/save-whatsapp-instance', 'Setup@saveWhatsappInstance');
Route::post('/update-instance-status', 'Setup@updateInstanceStatus');
Route::get('/user-whatsapp-instances', 'Setup@getUserWhatsappInstances');
Route::delete('/delete-whatsapp-instance', 'Setup@deleteWhatsappInstance');


Route::any('/background', [App\Http\Controllers\Payment::class, 'processPayment']);

// WA Sender - QR Code Session Management (API with auth)
Route::middleware('auth:api')->prefix('whatsapp')->group(function () {
    Route::post('/create-session', [App\Http\Controllers\WaSenderController::class, 'createSession']);
    Route::get('/session-status/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'checkSessionStatus']);
    Route::get('/user-instances', [App\Http\Controllers\WaSenderController::class, 'getUserInstances']);
    Route::post('/disconnect/{instanceId}', [App\Http\Controllers\WaSenderController::class, 'disconnectInstance']);
});

// WA Sender - Web authenticated routes (for frontend use)
Route::middleware('auth:web')->prefix('whatsapp')->group(function () {
    Route::post('/web/create-session', [App\Http\Controllers\WaSenderController::class, 'createSession']);
    Route::get('/web/session-status/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'checkSessionStatus']);
    Route::get('/web/user-instances', [App\Http\Controllers\WaSenderController::class, 'getUserInstances']);
    Route::post('/web/disconnect/{instanceId}', [App\Http\Controllers\WaSenderController::class, 'disconnectInstance']);
});