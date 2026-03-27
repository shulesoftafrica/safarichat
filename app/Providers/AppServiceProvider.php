<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use App\Models\Product;
use App\Observers\ProductObserver;

class AppServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // Register model observers
        Product::observe(ProductObserver::class);

        // Share pending appointments count with navigation
        View::composer('layouts.nav', function ($view) {
            if (Auth::check() && Auth::user()->business) {
                // Cache the query for 60 seconds to improve performance
                $cacheKey = 'pending_appointments_' . Auth::user()->business->id;
                
                $pendingAppointmentsCount = Cache::remember($cacheKey, 60, function () {
                    return Appointment::whereHas('lead', function ($query) {
                            $query->where('business_id', Auth::user()->business->id);
                        })
                        ->where('status', 'pending')
                        ->where('scheduled_at', '>=', now())
                        ->count();
                });
                
                $view->with('pendingAppointmentsCount', $pendingAppointmentsCount);
            }
        });
    }

}
