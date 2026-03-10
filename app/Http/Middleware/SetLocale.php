<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Available languages in the application
     *
     * @var array
     */
    protected $availableLocales = ['en', 'sw', 'ar', 'es', 'fr', 'hi', 'pt-br'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->getLocale($request);

        // Validate locale
        if (!in_array($locale, $this->availableLocales)) {
            $locale = config('app.locale', 'en');
        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for persistence
        Session::put('locale', $locale);

        // Set direction for RTL languages
        $direction = in_array($locale, ['ar']) ? 'rtl' : 'ltr';
        view()->share('direction', $direction);
        view()->share('currentLocale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale based on priority:
     * 1. URL parameter (?lang=xx)
     * 2. URL segment (for URL-based localization)
     * 3. User preference (if authenticated)
     * 4. Session
     * 5. Browser preference
     * 6. Default from config
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getLocale(Request $request)
    {
        // 1. Check URL parameter
        if ($request->has('lang')) {
            $locale = $request->get('lang');
            if (in_array($locale, $this->availableLocales)) {
                return $locale;
            }
        }

        // 2. Check URL segment (e.g., /sw/dashboard)
        $segment = $request->segment(1);
        if (in_array($segment, $this->availableLocales)) {
            return $segment;
        }

        // 3. Check authenticated user preference
        if (auth()->check() && isset(auth()->user()->locale)) {
            $userLocale = auth()->user()->locale;
            if (in_array($userLocale, $this->availableLocales)) {
                return $userLocale;
            }
        }

        // 4. Check session
        if (Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if (in_array($sessionLocale, $this->availableLocales)) {
                return $sessionLocale;
            }
        }

        // 5. Check browser preference
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale && in_array($browserLocale, $this->availableLocales)) {
            return $browserLocale;
        }

        // 6. Fallback to default
        return config('app.locale', 'en');
    }

    /**
     * Get locale from browser's Accept-Language header
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getBrowserLocale(Request $request)
    {
        $languages = $request->getLanguages();
        
        foreach ($languages as $language) {
            // Extract primary language code (e.g., 'en' from 'en-US')
            $locale = substr($language, 0, 2);
            
            // Special handling for pt-br
            if ($locale === 'pt' && strpos($language, 'BR') !== false) {
                return 'pt-br';
            }
            
            if (in_array($locale, $this->availableLocales)) {
                return $locale;
            }
        }

        return null;
    }
}