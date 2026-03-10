{{--
    Simple Language Switcher (Select Dropdown)
    
    Usage:
    <x-language-switcher-simple />
--}}

@props(['class' => ''])

<div class="language-switcher-simple {{ $class }}">
    <select class="form-select form-select-sm" onchange="window.location.href='?lang='+this.value">
        @foreach(\App\Helpers\TranslationHelper::getAvailableLocales() as $locale)
            <option value="{{ $locale }}" {{ app()->getLocale() === $locale ? 'selected' : '' }}>
                {{ get_locale_name($locale) }}
            </option>
        @endforeach
    </select>
</div>

<style>
    .language-switcher-simple .form-select {
        min-width: 150px;
        max-width: 200px;
    }
    
    /* Dark mode support */
    .dark-mode .language-switcher-simple .form-select {
        background-color: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .dark-mode .language-switcher-simple .form-select:focus {
        background-color: #2d3748;
        border-color: #25d366;
        color: #e2e8f0;
    }
</style>
