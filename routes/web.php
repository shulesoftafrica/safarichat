<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
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
  
    if (in_array($lang, ['en', 'sw'])) {
        
        session(['locale' => $lang]);
        app()->setLocale($lang);
       
    }
    return redirect()->back()->with('succss', __('Language changed successfully!'));
})->name('lang.switch');
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

// Direct upload test route
Route::get('/test-upload', function() {
    return view('test_direct_upload');
})->name('test.upload');

// Simple upload test route
Route::get('/test-simple', function() {
    return view('test_upload_simple');
})->name('test.simple');
Route::get('/api/pricing/{currency}', [App\Http\Controllers\LandingController::class, 'getPricing'])->name('landing.pricing');
Route::post('/contact-submit', [App\Http\Controllers\LandingController::class, 'contactSubmit'])->name('landing.contact');

// Additional API endpoints
Route::get('/api/currency-rates', [App\Http\Controllers\Api\LandingApiController::class, 'getCurrencyRates']);
Route::get('/api/language/{locale}', [App\Http\Controllers\Api\LandingApiController::class, 'getLanguageContent']);
Route::post('/api/calculate-volume', [App\Http\Controllers\Api\LandingApiController::class, 'calculateMessageVolume']);
Route::get('/api/demo-templates', [App\Http\Controllers\Api\LandingApiController::class, 'getDemoTemplates']);
Route::post('/api/track-interaction', [App\Http\Controllers\Api\LandingApiController::class, 'trackInteraction']);

// Original routes (keeping for existing functionality)
Route::get('/dashboard', [App\Http\Controllers\Home::class, 'index'])->name('dashboard')->middleware('onboarding.complete');
Route::get('/terms', function() { return view('auth.legal.terms_of_service');});
Route::get('/terms/use', function() { return view('auth.legal.terms_of_use');});

// Queue testing route (for development)
Route::get('/test-queue', function() {
    return view('test-queue');
})->name('test.queue');
Route::get('/privacy', function() { return view('corporate.privacy');});
Route::get('/live/{event_id?}','Setup@liveEvent');
Route::post('/resetpassword/resetP','Setup@resetP');
//Auth::routes();
Auth::routes(['verify' => true]);



Route::get('/message/channel', [App\Http\Controllers\Message::class, 'channel'])->name('message.channel');
Route::get('/message', [App\Http\Controllers\Message::class, 'index'])->name('message.index');
Route::post('/message/store', [App\Http\Controllers\Message::class, 'store'])->name('message.store');
Route::post('/messages/buy', [App\Http\Controllers\Message::class, 'buy'])->name('messages.buy');
Route::get('/message/report', [App\Http\Controllers\Message::class, 'report'])->name('message.report');
Route::any('/support', [App\Http\Controllers\Home::class, 'support'])->name('support');

// Service routes
Route::get('/service', [App\Http\Controllers\Service::class, 'index'])->name('service.index')->middleware('auth');
Route::get('/service/jd', [App\Http\Controllers\Service::class, 'jd'])->name('service.jd')->middleware('auth');
Route::get('/service/tab-content', [App\Http\Controllers\Service::class, 'getTabContent'])->name('service.tab-content');

// Products Management Routes
Route::middleware('auth')->group(function () {
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/bulk-action', [App\Http\Controllers\ProductController::class, 'bulkAction'])->name('products.bulk-action');
});

// Contact Management Routes
Route::middleware('auth')->group(function () {
    Route::get('/guest/getContactDetails/{id}', [App\Http\Controllers\Guest::class, 'getContactDetails'])->name('guest.getContactDetails');
    Route::get('/guest/getContactMessages/{id}', [App\Http\Controllers\Guest::class, 'getContactMessages'])->name('guest.getContactMessages');
    Route::post('/guest/sendMessage', [App\Http\Controllers\Guest::class, 'sendMessage'])->name('guest.sendMessage');
    Route::delete('/guest/bulkDelete', [App\Http\Controllers\Guest::class, 'bulkDelete'])->name('guest.bulkDelete');
});


Route::get('/home', [App\Http\Controllers\Home::class, 'index'])->name('home');
Route::get('/dashboard', [App\Http\Controllers\Home::class, 'index']);
Route::get('/support', [App\Http\Controllers\Home::class, 'support'])->name('support');

// Guest management routes
Route::middleware('auth')->group(function () {
    Route::get('/guest', [App\Http\Controllers\Guest::class, 'index'])->name('guest.index');
    Route::post('/guest/store/{id?}', [App\Http\Controllers\Guest::class, 'store'])->name('guest.store');
    Route::post('/guest/edit/{id?}', [App\Http\Controllers\Guest::class, 'update'])->name('guest.update');
    Route::delete('/guest/destroy/{id}', [App\Http\Controllers\Guest::class, 'destroy'])->name('guest.destroy');
    Route::get('/guest/getContactDetails/{id}', [App\Http\Controllers\Guest::class, 'getContactDetails'])->name('guest.getContactDetails');
    Route::get('/guest/getContactMessages/{id}', [App\Http\Controllers\Guest::class, 'getContactMessages'])->name('guest.getContactMessages');
    Route::post('/guest/sendMessage', [App\Http\Controllers\Guest::class, 'sendMessage'])->name('guest.sendMessage');
    Route::delete('/guest/bulkDelete', [App\Http\Controllers\Guest::class, 'bulkDelete'])->name('guest.bulkDelete');
    
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

# WA Sender AJAX endpoints for web interface
Route::middleware('auth')->prefix('wasender')->name('wasender.')->group(function () {
    Route::post('/create-session', [App\Http\Controllers\WaSenderController::class, 'createSession'])
        ->name('create-session');
    Route::get('/session-status/{sessionId}', [App\Http\Controllers\WaSenderController::class, 'checkSessionStatus'])
        ->name('session-status');
    Route::get('/user-instances', [App\Http\Controllers\WaSenderController::class, 'getUserInstances'])
        ->name('user-instances');
    Route::post('/disconnect/{instanceId}', [App\Http\Controllers\WaSenderController::class, 'disconnectInstance'])
        ->name('disconnect');
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
    Route::get('/whatsapp/instances', [App\Http\Controllers\WhatsappInstanceController::class, 'indexView'])->name('whatsapp.instances.index');
});


if (createRoute() != NULL) {
    $route = explode('@', createRoute());
    $file = app_path() . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $route[0] . '.php';

    if (file_exists($file)) {
        Route::any('/{controller?}/{method?}/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}/{param6?}/{param7?}', createRoute());
    }
}


