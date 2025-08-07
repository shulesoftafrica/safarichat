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

// Service tab content route
Route::get('/service/tab-content', function() {
    $tab = request('tab', 'products');
    
    // Security: only allow specific tab names
    $allowedTabs = ['products', 'job-description'];
    if (!in_array($tab, $allowedTabs)) {
        $tab = 'products';
    }
    
    $viewPath = "service.{$tab}";
    
    if (view()->exists($viewPath)) {
        return view($viewPath);
    } else {
        return response()->json(['error' => 'Tab content not found'], 404);
    }
})->name('service.tab-content');

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
});

// WhatsApp Status Monitoring
Route::get('/whatsapp/status', function() {
    return view('whatsapp.status');
})->middleware('auth')->name('whatsapp.status');

// WhatsApp Incoming Messages Management
Route::get('/whatsapp/incoming-messages', [App\Http\Controllers\Guest::class, 'incomingMessages'])
    ->middleware('auth')->name('whatsapp.incoming-messages');