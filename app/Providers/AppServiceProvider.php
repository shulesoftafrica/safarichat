<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;

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
        // Share pending appointments count with navigation
        View::composer('layouts.nav', function ($view) {
            if (Auth::check() && Auth::user()->business) {
                $pendingAppointmentsCount = Appointment::whereHas('lead', function ($query) {
                        $query->where('business_id', Auth::user()->business->id);
                    })
                    ->where('status', 'pending')
                    ->where('scheduled_at', '>=', now())
                    ->count();
                
                $view->with('pendingAppointmentsCount', $pendingAppointmentsCount);
            }
        });
    }

}
