<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ContactApiController;
use App\Http\Controllers\Api\LeadApiController;
use App\Http\Controllers\Api\LeadProductApiController;
use App\Http\Controllers\Api\ConversationApiController;
use App\Http\Controllers\Api\CrmSyncApiController;
use App\Http\Controllers\Api\CrmImportController;
use App\Http\Controllers\WaSenderController;
use App\Http\Controllers\Auth\WhatsAppRegistrationController;
use App\Http\Controllers\Api\BillingApiController;

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

// Billing API Routes (Revenue Protected)
Route::prefix('billing')->name('api.billing.')->group(function () {
    // Configuration (setup only)
    Route::post('/configure-product', [BillingApiController::class, 'configureProduct'])->name('configure_product');
    
    // Runtime operations
    Route::get('/customers/{customerId}/complete-status', [BillingApiController::class, 'getCompleteStatus'])->name('complete_status');
    Route::post('/sync-credits', [BillingApiController::class, 'syncCredits'])->name('sync_credits');
    Route::post('/verify-credits', [BillingApiController::class, 'verifyCredits'])->name('verify_credits');
    Route::post('/refresh-status', [BillingApiController::class, 'refreshStatus'])->name('refresh_status');
    Route::post('/emergency-refresh', [BillingApiController::class, 'emergencyRefresh'])->name('emergency_refresh');
});

// WhatsApp Registration Routes (Public)
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/check-phone', [WhatsAppRegistrationController::class, 'checkPhone'])->name('check_phone');
    Route::post('/send-otp', [WhatsAppRegistrationController::class, 'sendOtp'])->name('send_otp');
    Route::post('/register', [WhatsAppRegistrationController::class, 'register'])->name('register');
    Route::post('/resend-otp', [WhatsAppRegistrationController::class, 'resendOtp'])->name('resend_otp');
    Route::post('/forgot-password', [WhatsAppRegistrationController::class, 'sendPasswordResetOtp'])->name('forgot_password');
    Route::post('/reset-password', [WhatsAppRegistrationController::class, 'resetPassword'])->name('reset_password');
});

// Registration Stats (Admin only)
Route::middleware('auth:sanctum')->prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/registration-stats', [WhatsAppRegistrationController::class, 'getStats'])->name('registration_stats');
});

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
    
    // Get leads for a specific contact
    Route::get('/{contactId}/leads', [LeadApiController::class, 'getLeadsByContact'])->name('leads.index');
});

// Lead Management API Routes - Phase 1 CRM Integration
Route::middleware('auth:sanctum')->prefix('leads')->name('api.leads.')->group(function () {
    // Core CRUD operations
    Route::post('/', [LeadApiController::class, 'store'])->name('store');
    Route::get('/', [LeadApiController::class, 'index'])->name('index');
    Route::get('/{id}', [LeadApiController::class, 'show'])->name('show');
    
    // Lead status management
    Route::put('/{id}/status', [LeadApiController::class, 'updateStatus'])->name('status.update');
    Route::post('/{id}/assign', [LeadApiController::class, 'assign'])->name('assign');
    
    // Lead timeline and activity
    Route::get('/{id}/timeline', [LeadApiController::class, 'timeline'])->name('timeline');
    
    // Bulk operations
    Route::post('/bulk-create', [LeadApiController::class, 'bulkCreate'])->name('bulk.create');
    Route::put('/bulk-update', [LeadApiController::class, 'bulkUpdate'])->name('bulk.update');
    
    // Sales pipeline
    Route::get('/pipeline', [LeadApiController::class, 'pipeline'])->name('pipeline');
    
    // Churn management
    Route::post('/{id}/churn', [LeadApiController::class, 'markAsChurned'])->name('churn');
    Route::post('/{id}/reactivate', [LeadApiController::class, 'reactivate'])->name('reactivate');
    
    // Product relationship management
    Route::post('/{leadId}/products', [LeadProductApiController::class, 'addProducts'])->name('products.add');
    Route::get('/{leadId}/products', [LeadProductApiController::class, 'getLeadProducts'])->name('products.index');
    Route::put('/{leadId}/products/{productId}/status', [LeadProductApiController::class, 'updateProductStatus'])->name('products.status.update');
    Route::delete('/{leadId}/products/{productId}', [LeadProductApiController::class, 'removeProduct'])->name('products.remove');
    Route::put('/{leadId}/products/{productId}/primary', [LeadProductApiController::class, 'setPrimaryProduct'])->name('products.primary');
});

// Product-Lead Relationship API Routes
Route::middleware('auth:sanctum')->prefix('products')->name('api.products.')->group(function () {
    // Get all leads for a specific product
    Route::get('/{productId}/leads', [LeadProductApiController::class, 'getLeadsByProduct'])->name('leads.index');
});

// Conversation Management API Routes - Phase 2 CRM Integration
Route::middleware('auth:sanctum')->prefix('conversations')->name('api.conversations.')->group(function () {
    // Core conversation operations
    Route::post('/', [ConversationApiController::class, 'store'])->name('store');
    Route::get('/{leadId}', [ConversationApiController::class, 'getConversationHistory'])->name('history');
    Route::get('/single/{id}', [ConversationApiController::class, 'show'])->name('show');
    Route::put('/{id}', [ConversationApiController::class, 'update'])->name('update');
    
    // Analytics and search
    Route::get('/analytics/summary', [ConversationApiController::class, 'analytics'])->name('analytics');
    Route::get('/search', [ConversationApiController::class, 'search'])->name('search');
    
    // Export functionality
    Route::get('/{leadId}/export', [ConversationApiController::class, 'export'])->name('export');
});

// External CRM Sync API Routes - Phase 2 CRM Integration
Route::middleware('auth:sanctum')->prefix('crm')->name('api.crm.')->group(function () {
    // Bidirectional sync endpoints
    Route::post('/sync/contacts', [CrmSyncApiController::class, 'syncContacts'])->name('sync.contacts');
    Route::post('/sync/leads', [CrmSyncApiController::class, 'syncLeads'])->name('sync.leads');
    Route::get('/sync/status', [CrmSyncApiController::class, 'getSyncStatus'])->name('sync.status');
    
    // Webhook handlers for external CRM updates
    Route::post('/webhooks/updates', [CrmSyncApiController::class, 'handleWebhookUpdates'])->name('webhooks.updates');
});

// Conversation Management API Routes - Phase 2 CRM Integration
Route::middleware('auth:sanctum')->prefix('conversations')->name('api.conversations.')->group(function () {
    // Conversation CRUD
    Route::post('/', [ConversationApiController::class, 'store'])->name('store');
    Route::get('/{leadId}', [ConversationApiController::class, 'index'])->name('index');
    Route::get('/detail/{id}', [ConversationApiController::class, 'show'])->name('show');
    Route::put('/{id}', [ConversationApiController::class, 'update'])->name('update');
    
    // Analytics and search
    Route::get('/analytics', [ConversationApiController::class, 'analytics'])->name('analytics');
    Route::get('/search', [ConversationApiController::class, 'search'])->name('search');
});

// External CRM Sync API Routes - Phase 2 CRM Integration
Route::middleware('auth:sanctum')->prefix('crm')->name('api.crm.')->group(function () {
    // Bidirectional sync endpoints
    Route::post('/sync/contacts', [CrmSyncApiController::class, 'syncContacts'])->name('sync.contacts');
    Route::post('/sync/leads', [CrmSyncApiController::class, 'syncLeads'])->name('sync.leads');
    Route::get('/sync/status', [CrmSyncApiController::class, 'getSyncStatus'])->name('sync.status');
    
    // Webhook for receiving updates from external CRM
    Route::post('/webhooks/updates', [CrmSyncApiController::class, 'receiveUpdates'])->name('webhooks.updates');
});



// Product Management API Routes  
Route::post('products', [ProductController::class, 'store']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('products/{id}/edit', [ProductController::class, 'edit']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);
Route::post('products/bulk-action', [ProductController::class, 'bulkAction']);

// RAG Document Management API Routes - Remove auth middleware temporarily for testing
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
Route::get('/wasender/test-connection', [WaSenderController::class, 'testConnection']);
Route::get('/wasender/user-instances', [WaSenderController::class, 'getUserInstances']);
Route::post('/wasender/send-test-message', [WaSenderController::class, 'sendTestMessage']);
Route::post('/wasender/test-qr-generation', [WaSenderController::class, 'testQRGeneration']);

// Queue testing routes
Route::post('/wasender/test-queue-message', 'WaSenderController@testQueueMessage');
Route::post('/wasender/test-incoming-message', 'WaSenderController@testIncomingMessage');
Route::get('/wasender/queue-stats', 'WaSenderController@getQueueStats');
Route::post('/wasender/clear-failed-jobs', 'WaSenderController@clearFailedJobs');
Route::post('/wasender/retry-failed-jobs', 'WaSenderController@retryFailedJobs');

// WaSender Incoming Message Processing (Updated for UUID-based routing)
Route::post('/wasender/webhook/{instanceId}', 'WaSenderController@handleWebhook')->middleware(['throttle:webhooks']); // Legacy route
Route::post('/webhook/whatsapp/{instanceUuid}', 'WaSenderController@handleWebhookByUuid')->middleware(['throttle:webhooks']); // New UUID-based route

// WhatsApp Instance Management API Routes
Route::middleware('auth:sanctum')->prefix('whatsapp/instances')->name('api.whatsapp.instances.')->group(function () {
    Route::get('/', 'WhatsappInstanceController@index');
    Route::post('/', 'WhatsappInstanceController@store');
    Route::get('/active', 'WhatsappInstanceController@getActiveInstance');
    Route::post('/select', 'WhatsappInstanceController@selectActiveInstance');
    Route::get('/{id}', 'WhatsappInstanceController@show');
    Route::put('/{id}', 'WhatsappInstanceController@updateInstance');
    Route::delete('/{id}', 'WhatsappInstanceController@destroy');
    Route::get('/{id}/stats', 'WhatsappInstanceController@getInstanceStats');
    Route::get('/purposes', 'WhatsappInstanceController@getPurposes');
});

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

// Payment routes removed - using new billing system
Route::post('/registerBusiness', 'Setup@registerBusiness');
Route::post('/save-whatsapp-instance', 'Setup@saveWhatsappInstance');
Route::post('/update-instance-status', 'Setup@updateInstanceStatus');
Route::get('/user-whatsapp-instances', 'Setup@getUserWhatsappInstances');
Route::delete('/delete-whatsapp-instance', 'Setup@deleteWhatsappInstance');


// Background payment processing removed - using new billing system

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

// Unified Notification API (Sanctum authenticated) - Phase 4 Implementation
Route::middleware(['auth:sanctum', 'notification.api'])->prefix('notifications')->group(function () {
    // Main notification endpoints following unified API spec
    Route::post('/send', [App\Http\Controllers\Api\NotificationController::class, 'send'])
        ->name('notifications.send');
    Route::post('/bulk/send', [App\Http\Controllers\Api\NotificationController::class, 'bulkSend'])
        ->name('notifications.bulk');
    Route::get('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'show'])
        ->name('notifications.show');
    Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index'])
        ->name('notifications.index');
    
    // Optional: Additional endpoints for enhanced functionality
    Route::get('/{id}/status', [App\Http\Controllers\Api\NotificationController::class, 'status'])
        ->name('notifications.status');
    Route::patch('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'update'])
        ->name('notifications.update');
    Route::delete('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
    Route::get('/stats/summary', [App\Http\Controllers\Api\NotificationController::class, 'summary'])
        ->name('notifications.summary');
});

// WaSender Session Management - Phase 4 Implementation
Route::middleware(['auth:sanctum', 'notification.api'])->prefix('wasender/sessions')->group(function () {
    Route::post('/create', [App\Http\Controllers\WaSenderController::class, 'createSession'])
        ->name('wasender.sessions.create');
    Route::get('/', [App\Http\Controllers\WaSenderController::class, 'getSessions'])
        ->name('wasender.sessions.index');
    Route::get('/{id}', [App\Http\Controllers\WaSenderController::class, 'getSession'])
        ->name('wasender.sessions.show');
    Route::post('/{id}/connect', [App\Http\Controllers\WaSenderController::class, 'connectSession'])
        ->name('wasender.sessions.connect');
    Route::get('/{id}/status', [App\Http\Controllers\WaSenderController::class, 'getSessionStatus'])
        ->name('wasender.sessions.status');
    Route::get('/{id}/qrcode', [App\Http\Controllers\WaSenderController::class, 'getQRCode'])
        ->name('wasender.sessions.qr');
    Route::put('/{id}', [App\Http\Controllers\WaSenderController::class, 'updateSession'])
        ->name('wasender.sessions.update');
    Route::delete('/{id}', [App\Http\Controllers\WaSenderController::class, 'deleteSession'])
        ->name('wasender.sessions.destroy');
});

// CRM Import API Routes - Simple import functionality for contacts and context
Route::middleware('auth:sanctum')->prefix('crm/import')->name('api.crm.import.')->group(function () {
    // Import contacts from external CRM
    Route::post('/contacts', [CrmImportController::class, 'importContacts'])->name('contacts');
    
    // Import conversation context from external CRM
    Route::post('/context', [CrmImportController::class, 'importContext'])->name('context');
    
    // Get imported contact with context (for verification)
    Route::get('/contacts/{crm_id}/context', [CrmImportController::class, 'getContactContext'])->name('contact.context');
});

// Subscription API Routes - REMOVED (using new billing API architecture)