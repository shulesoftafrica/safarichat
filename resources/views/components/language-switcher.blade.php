{{--
    Language Switcher Component
    
    Usage:
    <x-language-switcher />
    
    Or with custom styling:
    <x-language-switcher class="custom-class" />
--}}

@props(['class' => ''])

<div class="language-switcher {{ $class }}">
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="languageDropdown" 
                data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-globe"></i>
            <span class="ms-2">{{ get_locale_name() }}</span>
        </button>
        
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
            @foreach(\App\Helpers\TranslationHelper::getAvailableLocales() as $locale)
                <li>
                    <a class="dropdown-item {{ app()->getLocale() === $locale ? 'active' : '' }}" 
                       href="?lang={{ $locale }}">
                        @if(app()->getLocale() === $locale)
                            <i class="fas fa-check me-2"></i>
                        @else
                            <span class="me-4"></span>
                        @endif
                        {{ get_locale_name($locale) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<style>
    .language-switcher .dropdown-item.active {
        background-color: #e7f3ff;
        color: #0066cc;
        font-weight: 600;
    }
    
    .language-switcher .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    /* Dark mode support */
    .dark-mode .language-switcher .dropdown-menu {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .dark-mode .language-switcher .dropdown-item {
        color: #e2e8f0;
    }
    
    .dark-mode .language-switcher .dropdown-item:hover {
        background-color: #374151;
    }
    
    .dark-mode .language-switcher .dropdown-item.active {
        background-color: #1e40af;
        color: #ffffff;
    }
</style>
