<?php

use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

Route::get('lang/{lang}', function ($lang) {
  
    if (in_array($lang, ['en', 'es', 'pt', 'hi', 'ar', 'fr', 'sw'])) {
        
        // Map 'pt' to 'pt-br' for Portuguese since folder is named 'pt-br'
        $actualLocale = ($lang === 'pt') ? 'pt-br' : $lang;
        
        session(['locale' => $actualLocale]);
        app()->setLocale($actualLocale);
       
    }
    return redirect()->back()->with('succss', __('Language changed successfully!'));
})->name('lang.switch');

// Design System Test Page (Phase 1 UI/UX Implementation)
Route::get('/design-system-test', function() {
    return view('design-system-test');
})->name('design.system.test');

Route::get('/terms-and-conditions', function() {
    return view('auth.termsandconditions');
});

// Corporate page route
Route::get('/corporate', function() {
    return view('corporate.index');
})->name('corporate');

// Information pages
Route::get('/security', function() {
    return view('corporate.security');
})->name('security');

Route::get('/api', function() {
    return view('corporate.api-docs');
})->name('api.docs');

// Landing Page Routes with Multi-language Support  
Route::get('/', [App\Http\Controllers\Setup::class, 'businessLogin'])->name('business.login');
Route::get('/roi-calculator', function() { return view('landing.roi-calculator'); })->name('landing.roi-calculator');

// Demo and API routes (keeping functional ones)
Route::post('/demo-chat', [App\Http\Controllers\LandingController::class, 'demoChat'])->name('landing.demo-chat');
Route::post('/calculate-roi', [App\Http\Controllers\LandingController::class, 'calculateROI'])->name('landing.calculate-roi');




Route::get('/api/pricing/{currency}', [App\Http\Controllers\LandingController::class, 'getPricing'])->name('landing.pricing');
Route::post('/contact-submit', [App\Http\Controllers\LandingController::class, 'contactSubmit'])->name('landing.contact');

// Additional API endpoints
Route::get('/api/currency-rates', [App\Http\Controllers\Api\LandingApiController::class, 'getCurrencyRates']);
Route::get('/api/language/{locale}', [App\Http\Controllers\Api\LandingApiController::class, 'getLanguageContent']);
Route::post('/api/calculate-volume', [App\Http\Controllers\Api\LandingApiController::class, 'calculateMessageVolume']);
Route::get('/api/demo-templates', [App\Http\Controllers\Api\LandingApiController::class, 'getDemoTemplates']);
Route::post('/api/track-interaction', [App\Http\Controllers\Api\LandingApiController::class, 'trackInteraction']);

// Original routes (keeping for existing functionality)
Route::get('/terms', function() { return view('auth.termsandconditions'); });
Route::get('/terms/use', function() { return view('auth.termsandconditions'); });


Route::get('/privacy', function() { return view('corporate.privacy');});
Route::get('/live/{event_id?}','Setup@liveEvent');
Route::post('/resetpassword/resetP','Setup@resetP');

// Custom authentication routes (OTP-based)
Route::get('/login', 'Setup@businessLogin')->name('login');
Route::post('/setup/otp', 'Setup@otp')->name('otp.send');
Route::post('/setup/otpverify', 'Setup@otpverify')->name('otp.verify');
Route::post('/logout', function() {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');
Route::get('/register', function() {
    return view('auth.register');
})->name('register');

// API endpoint for getting cities by country code
Route::get('/api/cities-by-country', 'Setup@getCitiesByCountry')->name('api.cities.by.country');

// Business registration and setup routes
Route::post('/registerBusiness', 'Setup@registerBusiness')->name('register.business');
Route::post('/save-whatsapp-instance', 'Setup@saveWhatsappInstance')->name('whatsapp.save');
Route::post('/update-instance-status', 'Setup@updateInstanceStatus')->name('whatsapp.update_status');
Route::get('/user-whatsapp-instances', 'Setup@getUserWhatsappInstances')->name('whatsapp.instances');
Route::delete('/delete-whatsapp-instance', 'Setup@deleteWhatsappInstance')->name('whatsapp.delete');



Route::get('/message/channel', [App\Http\Controllers\Message::class, 'channel'])->name('message.channel');
Route::get('/message', [App\Http\Controllers\Message::class, 'index'])->name('message.index');
Route::post('/message/store', [App\Http\Controllers\Message::class, 'store'])->name('message.store');
Route::post('/messages/buy', [App\Http\Controllers\Message::class, 'buy'])->name('messages.buy');
Route::get('/message/report', [App\Http\Controllers\Message::class, 'report'])->name('message.report');

// Sales Campaigns Routes (Phase 4)
Route::get('/campaigns', [App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/create', [App\Http\Controllers\CampaignController::class, 'create'])->name('campaigns.create');
Route::post('/campaigns', [App\Http\Controllers\CampaignController::class, 'store'])->name('campaigns.store');
Route::get('/campaigns/{id}/report', [App\Http\Controllers\CampaignController::class, 'report'])->name('campaigns.report');
Route::post('/campaigns/{id}/pause', [App\Http\Controllers\CampaignController::class, 'pause'])->name('campaigns.pause');
Route::post('/campaigns/{id}/resume', [App\Http\Controllers\CampaignController::class, 'resume'])->name('campaigns.resume');
Route::post('/campaigns/{id}/clone', [App\Http\Controllers\CampaignController::class, 'clone'])->name('campaigns.clone');
Route::delete('/campaigns/{id}', [App\Http\Controllers\CampaignController::class, 'destroy'])->name('campaigns.destroy');

// Support system removed - use external support tools

// Service routes
Route::get('/service', [App\Http\Controllers\Service::class, 'index'])->name('service.index')->middleware('auth');
Route::get('/service/jd', [App\Http\Controllers\Service::class, 'jd'])->name('service.jd')->middleware('auth');
Route::get('/service/tab-content', [App\Http\Controllers\Service::class, 'getTabContent'])->name('service.tab-content');

// Products Management Routes
Route::middleware(['auth', 'whatsapp.setup'])->group(function () {
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/manage', [App\Http\Controllers\ProductController::class, 'manage'])->name('products.manage');
    Route::get('/products/{id}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/bulk-action', [App\Http\Controllers\ProductController::class, 'bulkAction'])->name('products.bulk-action');
});

// Contact Management Routes
Route::middleware(['auth', 'whatsapp.setup'])->group(function () {
    Route::get('/guest/getContactDetails/{id}', [App\Http\Controllers\Guest::class, 'getContactDetails'])->name('guest.getContactDetails');
    Route::get('/guest/getContactMessages/{id}', [App\Http\Controllers\Guest::class, 'getContactMessages'])->name('guest.getContactMessages');
    Route::get('/guest/getConversations/{id}', [App\Http\Controllers\Guest::class, 'getConversations'])->name('guest.getConversations');
    Route::get('/guest/getConversationSummary/{id}', [App\Http\Controllers\Guest::class, 'getConversationSummary'])->name('guest.getConversationSummary');
    Route::post('/guest/sendMessage', [App\Http\Controllers\Guest::class, 'sendMessage'])->name('guest.sendMessage');
    Route::delete('/guest/bulkDelete', [App\Http\Controllers\Guest::class, 'bulkDelete'])->name('guest.bulkDelete');
});


Route::get('/home', [App\Http\Controllers\Home::class, 'index'])->name('home')->middleware(['auth', 'whatsapp.setup']);
Route::get('/dashboard', [App\Http\Controllers\Home::class, 'index'])->name('dashboard')->middleware(['auth', 'whatsapp.setup']);
// Support system removed - use external support tools

// Guest management routes
Route::middleware(['auth', 'whatsapp.setup'])->group(function () {
    Route::get('/guest', [App\Http\Controllers\Guest::class, 'index'])->name('guest.index');
    Route::get('/guest/data', [App\Http\Controllers\Guest::class, 'getData'])->name('guest.getData');
    Route::get('/guest/view/{id}', [App\Http\Controllers\Guest::class, 'show'])->name('guest.view');
    Route::get('/guest/{id}', [App\Http\Controllers\Guest::class, 'show'])->name('guest.show');
    Route::post('/guest/store/{id?}', [App\Http\Controllers\Guest::class, 'store'])->name('guest.store');
    Route::post('/guest/edit/{id?}', [App\Http\Controllers\Guest::class, 'update'])->name('guest.update');
    Route::delete('/guest/destroy/{id}', [App\Http\Controllers\Guest::class, 'destroy'])->name('guest.destroy');
    Route::get('/guest/getContactDetails/{id}', [App\Http\Controllers\Guest::class, 'getContactDetails'])->name('guest.getContactDetails');
    Route::get('/guest/getContactMessages/{id}', [App\Http\Controllers\Guest::class, 'getContactMessages'])->name('guest.getContactMessages');
    Route::post('/guest/sendMessage', [App\Http\Controllers\Guest::class, 'sendMessage'])->name('guest.sendMessage');
    Route::delete('/guest/bulkDelete', [App\Http\Controllers\Guest::class, 'bulkDelete'])->name('guest.bulkDelete');
    
    // WhatsApp Sync routes
    Route::get('/guest/whatsappInstanceStatus', [App\Http\Controllers\Guest::class, 'getWhatsappInstanceStatus'])->name('guest.whatsappInstanceStatus');
    Route::get('/guest/syncWhatsappContacts', [App\Http\Controllers\Guest::class, 'syncWhatsappContacts'])->name('guest.syncWhatsappContacts');
    Route::post('/guest/importWhatsappContacts', [App\Http\Controllers\Guest::class, 'importWhatsappContacts'])->name('guest.importWhatsappContacts');
    Route::post('/guest/uploadGuest', [App\Http\Controllers\Guest::class, 'uploadGuest'])->name('guest.uploadGuest');
    
    // Handoff Management routes
    Route::post('/guest/request-handoff', [App\Http\Controllers\Guest::class, 'requestHandoff'])->name('guest.requestHandoff');
    Route::post('/guest/assign-agent', [App\Http\Controllers\Guest::class, 'assignAgent'])->name('guest.assignAgent');
    Route::post('/guest/complete-handoff', [App\Http\Controllers\Guest::class, 'completeHandoff'])->name('guest.completeHandoff');
    Route::post('/guest/return-to-ai', [App\Http\Controllers\Guest::class, 'returnToAI'])->name('guest.returnToAI');
    Route::post('/guest/update-priority', [App\Http\Controllers\Guest::class, 'updatePriority'])->name('guest.updatePriority');
    Route::post('/guest/add-handoff-notes', [App\Http\Controllers\Guest::class, 'addHandoffNotes'])->name('guest.addHandoffNotes');
    Route::get('/guest/handoff-dashboard', [App\Http\Controllers\Guest::class, 'getHandoffDashboard'])->name('guest.handoffDashboard');
    Route::get('/guest/available-agents', [App\Http\Controllers\Guest::class, 'getAvailableAgents'])->name('guest.availableAgents');
    
    // Payment verification route removed - using new billing system
    
    // AI Sales Agent routes
    Route::prefix('ai-agents')->name('ai-agents.')->group(function () {
        Route::get('/', [App\Http\Controllers\AiSalesAgentController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AiSalesAgentController::class, 'store'])->name('store');
        Route::get('/create', [App\Http\Controllers\AiSalesAgentController::class, 'create'])->name('create');
        Route::get('/{aiSalesAgent}', [App\Http\Controllers\AiSalesAgentController::class, 'show'])->name('show');
        Route::get('/{aiSalesAgent}/edit', [App\Http\Controllers\AiSalesAgentController::class, 'edit'])->name('edit');
        Route::put('/{aiSalesAgent}', [App\Http\Controllers\AiSalesAgentController::class, 'update'])->name('update');
        Route::delete('/{aiSalesAgent}', [App\Http\Controllers\AiSalesAgentController::class, 'destroy'])->name('destroy');
        Route::patch('/{aiSalesAgent}/toggle-status', [App\Http\Controllers\AiSalesAgentController::class, 'toggleStatus'])->name('toggle-status');

    });
    
   // AI Agent Terms route
    Route::get('/ai-agent-terms', function() {
        return view('auth.ai-agent-terms');
    })->name('ai-agent-terms');
    
      // AI Agent Terms route
    Route::get('/privacy-policy', function() {
        return view('auth.privacy-policy');
    })->name('privacy-policy');
    
    // WhatsApp Terms and Compliance Guide
    Route::get('/whatsapp-terms', function() {
        return view('policies.whatsapp-terms');
    })->name('whatsapp-terms');

    // User Types API endpoint
    Route::get('/api/user-types', [App\Http\Controllers\AiSalesAgentController::class, 'getUserTypes'])->name('api.user-types');
});


// WhatsApp Status Monitoring
Route::get('/whatsapp/status', function() {
    return view('whatsapp.status');
})->middleware('auth')->name('whatsapp.status');

// WhatsApp Incoming Messages Management
Route::get('/whatsapp/incoming-messages', [App\Http\Controllers\Guest::class, 'incomingMessages'])
    ->middleware('auth')->name('whatsapp.incoming-messages');

// Unified Notification API Test Interface
Route::get('/unified-notification-test', function() {
    return view('unified-notification-test');
})->middleware('auth')->name('unified.notification.test');

// Event ID Fix Test Route
Route::get('/test-event-fix', function() {
    try {
        // Get the authenticated user
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Please log in to run this test'], 401);
        }
        
        // Try to create a test contact using the fixed service
        $contactData = [
            'phone' => '+254700000' . rand(100, 999),
            'name' => 'Event ID Fix Test',
            'user_id' => $user->id
        ];
        
        // Use the UserResolutionService to create a contact
        $contact = \App\Services\UserResolutionService::resolveOrCreateContact($contactData);
        
        if ($contact && $contact->event_id) {
            return response()->json([
                'status' => 'success',
                'message' => 'Contact created successfully! Event ID issue is fixed.',
                'data' => [
                    'contact_id' => $contact->id,
                    'event_id' => $contact->event_id,
                    'phone' => $contact->guest_phone,
                    'name' => $contact->guest_name,
                    'user_id' => $user->id
                ]
            ]);
        } else {
            return response()->json(['error' => 'Failed to create contact'], 500);
        }
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Exception occurred: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth')->name('test.event.fix');

// WA Sender QR Code Integration
Route::get('/wasender', [App\Http\Controllers\WaSenderController::class, 'index'])
    ->middleware('auth')->name('wasender.index');

Route::get('/auth/business/wasender', [App\Http\Controllers\WaSenderController::class, 'index'])
    ->middleware('auth')->name('business.wasender');

# WA Sender AJAX endpoints for web interface
Route::middleware('auth')->prefix('wasender')->name('wasender.')->group(function () {
    Route::post('/create-session', [App\Http\Controllers\WaSenderController::class, 'createSession'])
        ->name('create-session');
    Route::get('/session-status/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'checkSessionStatus'])
        ->name('session-status');
    Route::get('/verify-connection/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'verifyConnection'])
        ->name('verify-connection');
    Route::delete('/cleanup-session/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'cleanupSession'])
        ->name('cleanup-session');
    Route::get('/user-instances', [App\Http\Controllers\WaSenderController::class, 'getUserInstances'])
        ->name('user-instances');
    Route::post('/disconnect/{instanceId}', [App\Http\Controllers\WaSenderController::class, 'disconnectInstance'])
        ->name('disconnect');

    // Add missing QR code refresh endpoint
    Route::get('/session-qr/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'sessionQr'])
        ->name('session-qr');
});

// Subscription Management Routes - Replaced with new billing system

// Webhook routes (no auth required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/lipa-namba', [App\Http\Controllers\WebhookController::class, 'lipaNamba'])->name('lipa-namba');
    Route::post('/stripe', [App\Http\Controllers\WebhookController::class, 'stripe'])->name('stripe');
});

// Subscription and Payment Management Routes - Replaced with new billing system

// Webhook routes (no auth middleware)
Route::post('/webhooks/lipa-number', [App\Http\Controllers\WebhookController::class, 'handleLipaNamba'])->name('webhooks.lipa-number');
Route::post('/webhooks/stripe', [App\Http\Controllers\WebhookController::class, 'handleStripe'])->name('webhooks.stripe');

// WhatsApp Instance Management (web routes)
Route::middleware('auth')->group(function () {
    Route::get('/whatsapp/instances/{id}/edit', [App\Http\Controllers\WhatsappInstanceController::class, 'edit'])->name('whatsapp.instances.edit');
    Route::post('/whatsapp/instances/{id}/update', [App\Http\Controllers\WhatsappInstanceController::class, 'update'])->name('whatsapp.instances.update');
    Route::get('/whatsapp/instances/{id}/stats', [App\Http\Controllers\WhatsappInstanceController::class, 'getInstanceStats'])->name('whatsapp.instances.stats');
});

// Billing Web Routes
Route::middleware('auth')->group(function () {
    Route::get('/billing/payment', [App\Http\Controllers\BillingController::class, 'showPayment'])->name('billing.payment');
    Route::post('/billing/process-payment', [App\Http\Controllers\BillingController::class, 'processPayment'])->name('billing.process-payment');
    Route::get('/billing/stripe/success', [App\Http\Controllers\BillingController::class, 'stripeSuccess'])->name('billing.stripe.success');
    Route::get('/billing/success', [App\Http\Controllers\BillingController::class, 'paymentSuccess'])->name('billing.success');
    Route::get('/billing/cancel', [App\Http\Controllers\BillingController::class, 'paymentCancel'])->name('billing.cancel');
    Route::get('/billing/ucn-instructions/{reference}', [App\Http\Controllers\BillingController::class, 'showUCNInstructions'])->name('billing.ucn-instructions');
    
    // Wallet Management Page
    Route::get('/billing/wallet', [App\Http\Controllers\BillingController::class, 'showWallet'])->name('billing.wallet');
    
    // Billing API Routes (session-based auth for AJAX calls from web pages)
    Route::get('/api/billing/plans', [App\Http\Controllers\BillingController::class, 'getPlans'])->name('api.billing.plans');
    Route::get('/api/billing/status', [App\Http\Controllers\BillingController::class, 'getStatus'])->name('api.billing.status');
    Route::get('/api/billing/wallet/info', [App\Http\Controllers\BillingController::class, 'getWalletInfo'])->name('api.billing.wallet.info');
    Route::post('/api/billing/wallet/get-ucn', [App\Http\Controllers\Api\BillingApiController::class, 'getWalletUCN'])->name('api.billing.wallet.get-ucn');
    Route::post('/api/billing/upgrade', [App\Http\Controllers\BillingController::class, 'upgrade'])->name('api.billing.upgrade');
    Route::post('/api/billing/renew', [App\Http\Controllers\BillingController::class, 'renew'])->name('api.billing.renew');
    Route::get('/api/billing/history', [App\Http\Controllers\Home::class, 'billingHistory'])->name('api.billing.history');
});

// Booking Calendar Routes
Route::middleware('auth')->group(function () {
    // Booking Calendars Management
    Route::get('/booking-calendars', [App\Http\Controllers\BookingCalendarController::class, 'index'])->name('booking-calendars.index');
    Route::get('/booking-calendars/create', [App\Http\Controllers\BookingCalendarController::class, 'create'])->name('booking-calendars.create');
    Route::post('/booking-calendars', [App\Http\Controllers\BookingCalendarController::class, 'store'])->name('booking-calendars.store');
    Route::get('/booking-calendars/{id}/edit', [App\Http\Controllers\BookingCalendarController::class, 'edit'])->name('booking-calendars.edit');
    Route::put('/booking-calendars/{id}', [App\Http\Controllers\BookingCalendarController::class, 'update'])->name('booking-calendars.update');
    Route::delete('/booking-calendars/{id}', [App\Http\Controllers\BookingCalendarController::class, 'destroy'])->name('booking-calendars.destroy');
    Route::post('/booking-calendars/{id}/toggle', [App\Http\Controllers\BookingCalendarController::class, 'toggle'])->name('booking-calendars.toggle');
    Route::get('/booking-calendars/{id}/preview', [App\Http\Controllers\BookingCalendarController::class, 'preview'])->name('booking-calendars.preview');
    
    // Appointments Management (AI-scheduled bookings)
    Route::get('/appointments', [App\Http\Controllers\AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{id}', [App\Http\Controllers\AppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{id}/confirm', [App\Http\Controllers\AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{id}/cancel', [App\Http\Controllers\AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{id}/complete', [App\Http\Controllers\AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{id}/no-show', [App\Http\Controllers\AppointmentController::class, 'markNoShow'])->name('appointments.no-show');
    Route::post('/appointments/{id}/reschedule', [App\Http\Controllers\AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
});

// Admin Dashboard Routes
Route::get('/admin', [App\Http\Controllers\AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [App\Http\Controllers\AdminController::class, 'login']);
Route::middleware('auth.admin')->group(function() {
    Route::get('/admin/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [App\Http\Controllers\AdminController::class, 'logout']);
    Route::post('/admin/update-pricing', [App\Http\Controllers\AdminController::class, 'updatePricing']);
    Route::post('/admin/clear-cache', [App\Http\Controllers\AdminController::class, 'clearCache']);
    
    // Billing API sync routes
    Route::get('/admin/sync-from-billing-api', [App\Http\Controllers\AdminController::class, 'syncFromBillingAPI']);
    Route::get('/admin/test-billing-api', [App\Http\Controllers\AdminController::class, 'testBillingAPI']);
    Route::post('/admin/sync-all-customers', [App\Http\Controllers\AdminController::class, 'syncAllCustomers']);
    Route::post('/admin/refresh-billing-cache', [App\Http\Controllers\AdminController::class, 'refreshBillingCache']);
});


if (createRoute() != NULL) {
    $route = explode('@', createRoute());
    $file = app_path() . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $route[0] . '.php';

    if (file_exists($file)) {
        // Exclude API routes from this wildcard route and apply whatsapp.setup middleware
        Route::any('/{controller?}/{method?}/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}/{param6?}/{param7?}', createRoute())
            ->where('controller', '^(?!api).*')
            ->middleware(['auth', 'whatsapp.setup']);
    }
}


