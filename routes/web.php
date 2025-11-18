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
Route::get('/', [App\Http\Controllers\Home::class, 'index']);
Route::get('/terms', function() { return view('auth.legal.terms_of_service');});
Route::get('/terms/use', function() { return view('auth.legal.terms_of_use');});

// Queue testing route (for development)
Route::get('/test-queue', function() {
    return view('test-queue');
})->name('test.queue');
Route::get('/privacy', function() { return view('auth.legal.privacy');});
Route::get('/live/{event_id?}','Api@liveEvent');
Route::post('/resetpassword/resetP','Api@resetP');
//Auth::routes();
Auth::routes(['verify' => true]);

Route::resource('bookings', \App\Http\Controllers\Booking::class);

Route::get('/message/channel', [App\Http\Controllers\Message::class, 'channel'])->name('message.channel');
Route::post('/messages/buy', [App\Http\Controllers\Message::class, 'buy'])->name('messages.buy');
Route::get('/message/report', [App\Http\Controllers\Message::class, 'report'])->name('message.report');
Route::any('/support', [App\Http\Controllers\Home::class, 'support'])->name('support');

// Service routes
Route::get('/service', [App\Http\Controllers\Service::class, 'index'])->name('service.index')->middleware('auth');
Route::get('/service/tab-content', [App\Http\Controllers\Service::class, 'getTabContent'])->name('service.tab-content');

// Contact Management Routes
Route::middleware('auth')->group(function () {
    Route::get('/guest/getContactDetails/{id}', [App\Http\Controllers\Guest::class, 'getContactDetails'])->name('guest.getContactDetails');
    Route::get('/guest/getContactMessages/{id}', [App\Http\Controllers\Guest::class, 'getContactMessages'])->name('guest.getContactMessages');
    Route::post('/guest/sendMessage', [App\Http\Controllers\Guest::class, 'sendMessage'])->name('guest.sendMessage');
    Route::delete('/guest/bulkDelete', [App\Http\Controllers\Guest::class, 'bulkDelete'])->name('guest.bulkDelete');
});

if (createRoute() != NULL) {
    $route = explode('@', createRoute());
    $file = app_path() . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $route[0] . '.php';

    if (file_exists($file)) {
        Route::any('/{controller?}/{method?}/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}/{param6?}/{param7?}', createRoute());
    }
}
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
    
    // Payment verification route
    Route::post('/payment/verify', [App\Http\Controllers\Payment::class, 'verify'])->name('payment.verify');
    Route::get('/payment/subscription', [App\Http\Controllers\Payment::class, 'subscriptionStatus'])->name('payment.subscription');
    
    // AI Sales Agent routes
    Route::prefix('ai-agents')->name('ai-agents.')->group(function () {
        Route::get('/', [App\Http\Controllers\AiSalesAgentController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AiSalesAgentController::class, 'store'])->name('store');
        Route::get('/create', [App\Http\Controllers\AiSalesAgentController::class, 'create'])->name('create');
        Route::get('/{aiSalesAgent}', [App\Http\Controllers\AiSalesAgentController::class, 'show'])->name('show');
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

// Official WhatsApp Business API Integration Routes
Route::middleware('auth')->prefix('whatsapp/official')->name('whatsapp.official.')->group(function () {
    Route::get('/integration-options', [App\Http\Controllers\OfficialWhatsAppController::class, 'showIntegrationOptions'])
        ->name('integration-options');
    Route::post('/initialize', [App\Http\Controllers\OfficialWhatsAppController::class, 'initializeOnboarding'])
        ->name('initialize');
    Route::get('/callback', [App\Http\Controllers\OfficialWhatsAppController::class, 'handleEmbeddedSignupCallback'])
        ->name('callback');
    Route::get('/status', [App\Http\Controllers\OfficialWhatsAppController::class, 'getOnboardingStatus'])
        ->name('status');
    Route::post('/disconnect', [App\Http\Controllers\OfficialWhatsAppController::class, 'disconnect'])
        ->name('disconnect');
    Route::post('/test-connection', [App\Http\Controllers\OfficialWhatsAppController::class, 'testConnection'])
        ->name('test-connection');
    Route::get('/embedded-signup', [App\Http\Controllers\OfficialWhatsAppController::class, 'showEmbeddedSignup'])
        ->name('embedded-signup');
    Route::get('/phase2-test', function() {
        return view('whatsapp.phase2-test');
    })->name('phase2-test');
});

// WhatsApp Webhook Routes (Public - no auth required)
Route::prefix('api/whatsapp')->name('api.whatsapp.')->group(function () {
    Route::get('/webhook', [App\Http\Controllers\WhatsAppWebhookController::class, 'verify'])
        ->name('webhook.verify');
    Route::post('/webhook', [App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
        ->name('webhook.handle');
});

// WhatsApp Integration Status Pages
Route::get('/whatsapp/integration-success', function() {
    return view('whatsapp.integration-success');
})->middleware('auth')->name('whatsapp.integration-success');

Route::get('/whatsapp/integration-error', function() {
    return view('whatsapp.integration-error');
})->middleware('auth')->name('whatsapp.integration-error');

// Phase 1 Testing Page (Development)
Route::get('/whatsapp/phase1-test', function() {
    return view('whatsapp.phase1-test');
})->middleware('auth')->name('whatsapp.phase1-test');

// Backward compatibility for existing WhatsApp routes
Route::get('/whatsapp/integration-options', [App\Http\Controllers\OfficialWhatsAppController::class, 'showIntegrationOptions'])
    ->middleware('auth')->name('whatsapp.integration-options');