<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// WAAPI Incoming Message Processing
Route::post('/waapi/webhook/{instanceId}', 'Api@handleWebhookEvent');
Route::post('/waapi/process-messages/{instanceId}', 'Api@processIncomingMessages');
Route::get('/waapi/incoming-messages/{instanceId}', 'Api@getIncomingMessages');
Route::post('/waapi/mark-processed/{messageId}', 'Api@markMessageAsProcessed');
Route::any('/background', [Payment::class, 'processPayment']);