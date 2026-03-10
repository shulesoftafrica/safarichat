<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class TranslationHelper
{
    /**
     * Smart pluralization with fallback
     * Handles cases where translation might be missing
     *
     * @param string $key
     * @param int $count
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function trans_choice_smart($key, $count = 1, array $replace = [], $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        try {
            $translation = trans_choice($key, $count, $replace, $locale);
            
            // If translation is same as key, try fallback locale
            if ($translation === $key && $locale !== config('app.fallback_locale')) {
                $translation = trans_choice($key, $count, $replace, config('app.fallback_locale'));
            }
            
            return $translation;
        } catch (\Exception $e) {
            // Fallback to English if all else fails
            return trans_choice($key, $count, $replace, 'en');
        }
    }

    /**
     * Translation with graceful fallback to English
     * Returns the key if no translation found
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function trans_with_fallback($key, array $replace = [], $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        $translation = __($key, $replace, $locale);
        
        // If translation is same as key, try fallback locale
        if ($translation === $key && $locale !== config('app.fallback_locale')) {
            $translation = __($key, $replace, config('app.fallback_locale'));
        }
        
        return $translation;
    }

    /**
     * Translate form attributes for validation messages
     *
     * @param string $attribute
     * @return string
     */
    public static function trans_attribute($attribute)
    {
        // Try to find in messages.labels first
        $labelKey = "messages.labels.{$attribute}";
        $label = __($labelKey);
        
        if ($label !== $labelKey) {
            return $label;
        }
        
        // Fallback to humanizing the attribute name
        return ucfirst(str_replace('_', ' ', $attribute));
    }

    /**
     * Get all available locales
     *
     * @return array
     */
    public static function getAvailableLocales()
    {
        return ['en', 'sw', 'ar', 'es', 'fr', 'hi', 'pt-br'];
    }

    /**
     * Get locale name in native language
     *
     * @param string|null $locale
     * @return string
     */
    public static function getLocaleName($locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        $names = [
            'en' => 'English',
            'sw' => 'Kiswahili',
            'ar' => 'العربية',
            'es' => 'Español',
            'fr' => 'Français',
            'hi' => 'हिन्दी',
            'pt-br' => 'Português (Brasil)',
        ];
        
        return $names[$locale] ?? $locale;
    }

    /**
     * Check if locale is RTL
     *
     * @param string|null $locale
     * @return bool
     */
    public static function isRtl($locale = null)
    {
        $locale = $locale ?? App::getLocale();
        return in_array($locale, ['ar']);
    }

    /**
     * Get text direction for locale
     *
     * @param string|null $locale
     * @return string 'ltr' or 'rtl'
     */
    public static function getDirection($locale = null)
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Translate and format date
     *
     * @param mixed $date
     * @param string $format
     * @param string|null $locale
     * @return string
     */
    public static function transDate($date, $format = 'F j, Y', $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        if (!$date) {
            return '';
        }
        
        $carbon = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        // Set locale for Carbon
        $carbon->locale($locale);
        
        return $carbon->translatedFormat($format);
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     *
     * @param mixed $date
     * @param string|null $locale
     * @return string
     */
    public static function transRelativeTime($date, $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        if (!$date) {
            return '';
        }
        
        $carbon = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        $carbon->locale($locale);
        
        return $carbon->diffForHumans();
    }

    /**
     * Format number according to locale
     *
     * @param float $number
     * @param int $decimals
     * @param string|null $locale
     * @return string
     */
    public static function transNumber($number, $decimals = 0, $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        // Map Laravel locales to NumberFormatter locales
        $formatterLocales = [
            'en' => 'en_US',
            'sw' => 'sw_TZ',
            'ar' => 'ar_SA',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'hi' => 'hi_IN',
            'pt-br' => 'pt_BR',
        ];
        
        $formatterLocale = $formatterLocales[$locale] ?? 'en_US';
        
        $formatter = new \NumberFormatter($formatterLocale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        
        return $formatter->format($number);
    }

    /**
     * Format currency according to locale
     *
     * @param float $amount
     * @param string $currency
     * @param string|null $locale
     * @return string
     */
    public static function transCurrency($amount, $currency = 'TZS', $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        $formatterLocales = [
            'en' => 'en_US',
            'sw' => 'sw_TZ',
            'ar' => 'ar_SA',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'hi' => 'hi_IN',
            'pt-br' => 'pt_BR',
        ];
        
        $formatterLocale = $formatterLocales[$locale] ?? 'en_US';
        
        $formatter = new \NumberFormatter($formatterLocale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Get current locale code
     *
     * @return string
     */
    public static function getCurrentLocale()
    {
        return App::getLocale();
    }

    /**
     * Set current locale
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale($locale)
    {
        if (in_array($locale, self::getAvailableLocales())) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }
    }
}
