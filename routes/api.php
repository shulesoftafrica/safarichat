<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

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

// Official WhatsApp Business API Routes
Route::middleware('auth:api')->prefix('whatsapp/official')->name('api.whatsapp.official.')->group(function () {
    Route::post('/send/text', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendText'])
        ->name('send.text');
    Route::post('/send/template', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendTemplate'])
        ->name('send.template');
    Route::post('/send/image', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendImage'])
        ->name('send.image');
    Route::post('/send/buttons', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendButtons'])
        ->name('send.buttons');
    Route::post('/send/list', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendList'])
        ->name('send.list');
    Route::post('/send/immediate', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'sendImmediate'])
        ->name('send.immediate');
    Route::get('/stats', [App\Http\Controllers\Api\OfficialWhatsAppApiController::class, 'getStats'])
        ->name('stats');
});

// WhatsApp Webhook (no auth required for Meta to send webhooks)
Route::any('/whatsapp/webhook', [App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook');

// Product Management API Routes
Route::post('products', [ProductController::class, 'store']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('products/{id}/edit', [ProductController::class, 'edit']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);
Route::post('products/bulk-action', [ProductController::class, 'bulkAction']);

Route::post('/whatsapp', 'Api@whatsapp');
Route::any('/message','Api@pushEmailsToSend');
Route::any('/sms/{code}/{imei?}/{model?}', 'Api@pushPhoneSMS');
Route::any('/validate/{null}/{imei?}/{model?}/{param1?}/{id?}/{param3?}/{param4?}','Api@aunthenticateMobile');
Route::any('/updatestatus/{code?}/{sms_id?}/{imei?}{device?}','Api@updatestatus');
Route::any('/smsreport/{code?}/{imei?}/{model?}', 'Api@smsReport');
Route::post('/payment','Api@apiAcceptPayment');
Route::post('/save-whatsapp-instance', 'Api@saveWhatsappInstance');
Route::post('/update-instance-status', 'Api@updateInstanceStatus');
Route::get('/user-whatsapp-instances', 'Api@getUserWhatsappInstances');
Route::delete('/delete-whatsapp-instance', 'Api@deleteWhatsappInstance');

// WAAPI testing and management routes
Route::get('/waapi/test-connection', 'WaapiController@testConnection');
Route::get('/waapi/user-instances', 'WaapiController@getUserInstances');
Route::post('/waapi/send-test-message', 'WaapiController@sendTestMessage');

// Queue testing routes
Route::post('/waapi/test-queue-message', 'WaapiController@testQueueMessage');
Route::post('/waapi/test-incoming-message', 'WaapiController@testIncomingMessage');
Route::get('/waapi/queue-stats', 'WaapiController@getQueueStats');
Route::post('/waapi/clear-failed-jobs', 'WaapiController@clearFailedJobs');
Route::post('/waapi/retry-failed-jobs', 'WaapiController@retryFailedJobs');

// WAAPI Incoming Message Processing
Route::post('/waapi/webhook/{instanceId}', 'Api@handleWebhookEvent');
Route::post('/waapi/process-messages/{instanceId}', 'Api@processIncomingMessages');
Route::get('/waapi/incoming-messages/{instanceId}', 'Api@getIncomingMessages');
Route::post('/waapi/mark-processed/{messageId}', 'Api@markMessageAsProcessed');
Route::any('/background', [App\Http\Controllers\Payment::class, 'processPayment']);