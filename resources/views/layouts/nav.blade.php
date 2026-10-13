<?php 
// Check if user has connected WhatsApp and defined products
$hasConnectedWhatsApp = false;
$hasProducts = false;
$showNavigation = false;

if (Auth::check()) {
    $user = Auth::user();
    $hasConnectedWhatsApp = $user->whatsappInstances()
        ->where('status', 'connected')
        ->exists();
    $hasProducts = $user->products()->exists();
    $showNavigation = $hasConnectedWhatsApp && $hasProducts;
}
?>
<!-- Add Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    /* Force FontAwesome to load properly */
    .fa, .fas, .far, .fab, .fal, .fad {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        -moz-osx-font-smoothing: grayscale;
        -webkit-font-smoothing: antialiased;
        display: inline-block;
        font-style: normal;
        font-variant: normal;
        text-rendering: auto;
        line-height: 1;
    }
    
    .fab {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
    }
    
    .fas {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
    }
    
    /* Submenu styling */
    .nav-second-level {
        padding-left: 0;
        list-style: none;
        background-color: rgba(0, 0, 0, 0.05);
        margin-top: 5px;
    }
    .nav-second-level .nav-item {
        padding-left: 20px;
    }
    .nav-second-level .nav-link {
        padding: 8px 15px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .nav-second-level .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: #495057;
    }
    .nav-second-level .nav-link i {
        font-size: 0.85rem;
        width: 20px;
    }
    /* Chevron rotation animation */
    .nav-link[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }
    .nav-link[aria-expanded="false"] .fa-chevron-down {
        transform: rotate(0deg);
        transition: transform 0.3s ease;
    }
    
    /* Ensure navigation icons are always visible */
    .nav-link i {
        display: inline-block !important;
        opacity: 1 !important;
        visibility: visible !important;
        width: auto !important;
        min-width: 16px !important;
        text-align: center !important;
        font-size: 14px !important;
        margin-right: 8px !important;
        vertical-align: middle !important;
    }
    
    .nav-link span {
        vertical-align: middle !important;
    }
    
    .nav-link i.fa, 
    .nav-link i.fas, 
    .nav-link i.far, 
    .nav-link i.fab {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
    }
    
    /* Dark mode icon visibility */
    .dark-mode .nav-link i {
        opacity: 0.9 !important;
    }
    
    .dark-mode .nav-link:hover i {
        opacity: 1 !important;
    }
    
    /* ========== USER DROPDOWN MENU DARK MODE STYLES ========== */
    
    /* User Profile Dropdown Toggle */
    .dark-mode .nav-user {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
        border: 1px solid #4a5568 !important;
        border-radius: 8px !important;
        padding: 6px 12px !important;
        transition: all 0.3s ease !important;
    }
    
    .dark-mode .nav-user:hover {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%) !important;
        border-color: #4299e1 !important;
        box-shadow: 0 2px 8px rgba(66, 153, 225, 0.3) !important;
    }
    
    .dark-mode .nav-user-name {
        color: #f7fafc !important;
        font-weight: 500 !important;
    }
    
    .dark-mode .nav-user img.rounded-circle {
        border: 2px solid #4a5568 !important;
    }
    
    /* User Dropdown Menu */
    .dark-mode .dropdown-menu {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
        border: 1px solid #4a5568 !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5) !important;
        padding: 8px 0 !important;
        margin-top: 8px !important;
    }
    
    /* Dropdown Items */
    .dark-mode .dropdown-item {
        color: #f7fafc !important;
        padding: 10px 20px !important;
        transition: all 0.2s ease !important;
        border-radius: 6px !important;
        margin: 2px 8px !important;
    }
    
    .dark-mode .dropdown-item:hover {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
        color: #ffffff !important;
        transform: translateX(4px) !important;
    }
    
    .dark-mode .dropdown-item i {
        color: #cbd5e0 !important;
        margin-right: 8px !important;
    }
    
    .dark-mode .dropdown-item:hover i {
        color: #ffffff !important;
    }
    
    /* Dropdown Divider */
    .dark-mode .dropdown-divider {
        border-top: 1px solid #4a5568 !important;
        margin: 8px 0 !important;
    }
    
    /* Logout Item (bg-light override) */
    .dark-mode .dropdown-item.bg-light {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%) !important;
        color: #f7fafc !important;
        font-weight: 600 !important;
    }
    
    .dark-mode .dropdown-item.bg-light:hover {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%) !important;
        color: #ffffff !important;
    }
    
    /* Language Dropdown Specific */
    .dropdown-item.active {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    
    .dark-mode .dropdown-menu img {
        opacity: 0.9 !important;
        transition: opacity 0.2s ease !important;
    }
    
    .dark-mode .dropdown-item:hover img {
        opacity: 1 !important;
    }
    
    .dark-mode .dropdown-item.active {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    
    /* Dropdown Menu Right Alignment */
    .dark-mode .dropdown-menu-right {
        right: 0 !important;
left: auto !important;
    }
</style>
<!-- leftbar-tab-menu -->
<div class="leftbar-tab-menu">
    <div class="main-icon-menu">
        <a href="<?= url('/') ?>" class="logo logo-metrica d-block text-center">
            <span>
                <img src="<?= asset(ROOT.'assets/images/safarichat.png') ?>?v=3" alt="logo-small" class="logo-sm">
            </span>
        </a>
        <nav class="nav">
            <?php if ($showNavigation) { ?>
            <?php
            if (!preg_match('/upgrade/', url()->current()) && Auth::user()->usersEvents()->count() > 0) {
                ?>

                <a href="#MetricaCRM" class="nav-link active" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="{{ __('navigation.safari_ai') }}" data-trigger="hover">
                    <i data-feather="grid" class="align-self-center menu-icon icon-dual"></i>

                </a>
                <h6>{{ __('navigation.safari_ai') }}</h6> 

                <!--                <a href="#services" class="nav-link active" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="Services" data-trigger="hover">
                                    <i data-feather="package" class="align-self-center menu-icon icon-dual"></i>
                                </a>
                                <h6>Services</h6>-->


               
                
                <?php if (!empty(Auth::user()->business)) { ?>
<!--                    <a href="#Business" class="nav-link" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="Business" data-trigger="hover">
                        <i data-feather="pie-chart" class="align-self-center menu-icon icon-dual"></i>

                    </a>end MetricaCRM 
                    <h6 >Business</h6> -->
                <?php } ?>


            <?php } ?>
            <?php } // End showNavigation check ?>
        </nav><!--end nav-->
        <div class="pro-metrica-end">
            <!-- <a href="" class="help" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="{{__('support')}}" >
                <i data-feather="message-circle" class="align-self-center menu-icon icon-md icon-dual mb-4"></i> 

            </a>
            <!-- Support link removed - use external support tools -->
            <!-- <a href="" class="profile"  data-toggle="modal" data-animation="fade"  data-toggle="modal" data-target="#support">
                <img src="<?= asset(ROOT.'assets/images/users/user-1.jpg') ?>" alt="profile-user" class="rounded-circle thumb-sm"> 

            </a> -->

 
        </div>
    </div><!--end main-icon-menu-->

    <div class="main-menu-inner">
        <!-- LOGO -->
        <div class="#" class="logo">
            <span>
                <!--<img src="<?= asset(ROOT.'assets/images/logo-dark.png') ?>" alt="logo-large" class="logo-lg logo-dark">-->
                <!--<img src="<?= asset(ROOT.'assets/images/logo.png') ?>" alt="logo-large" class="logo-lg logo-light">-->
            </span>
            </a>                    
        </div>
        <!--end logo-->
        <div class="menu-body slimscroll">  
            <?php if ($showNavigation) { ?>
            <?php
        //    if (!preg_match('/upgrade/', url()->current()) && Auth::user()->usersEvents()->count() > 0) {
                ?>
                <div id="MetricaCRM" class="main-icon-menu-pane <?= !in_array(request()->segment(1), ['message', 'business']) ? 'active' : '' ?> ">
                    <div class="title-box">
                        <h6 class="menu-title">{{ __('navigation.category') }}</h6>       
                    </div>

                    <ul class="nav in mm-show">
                        <li class="nav-item active">
                            <a class="nav-link" href="<?= url('/') ?>">
                                <i class="fab fa-whatsapp" style="color: #25d366;"></i><span>{{ __('navigation.summary') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('guest') ?>">
                                <i class="fas fa-address-book" style="color: #3b82f6;"></i><span>{{ __('navigation.customers') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('campaigns') ?>">
                                <i class="fas fa-chart-line" style="color: #f59e0b;"></i><span>{{ __('navigation.sales_campaigns') }}</span>
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/whatsappGroup') ?>">
                                <i class="fas fa-object-group align-middle mr-2" title="Groups"></i>{{ __('navigation.groups') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/sent') ?>">
                                <i class="fab fa-whatsapp-square align-middle mr-2" title="Channels"></i>{{ __('navigation.channels') }}
                            </a>
                        </li> -->
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/schedule') ?>">
                                <i class="fas fa-calendar-alt align-middle mr-2" title="Schedule"></i>{{ __('navigation.schedule') }}
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('products') ?>">
                                <i class="fas fa-box" style="color: #667eea;"></i><span>{{ __('navigation.products') }}</span>
                            </a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link" href="<?= url('ai-agents') ?>">
                                <i class="fas fa-robot" style="color: #764ba2;"></i><span>{{ __('navigation.sales_agents') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= route('appointments.index') ?>">
                                <i class="fas fa-calendar-check" style="color: #10b981;"></i><span>{{ __('navigation.appointments') }}</span>
                                @if(isset($pendingAppointmentsCount) && $pendingAppointmentsCount > 0)
                                <span class="badge badge-soft-danger ml-1">{{ $pendingAppointmentsCount }}</span>
                                @endif
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('whatsapp/instances') ?>">
                                <i class="fas fa-mobile-alt align-middle mr-2" title="WhatsApp Lines"></i>{{ __('navigation.whatsapp_lines') }}
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/report') ?>">
                                <i class="fas fa-chart-bar align-middle mr-2" title="Reports"></i>{{ __('navigation.reports') }}
                            </a>
                        </li>
                    
                       
                    </ul>
                </div><!-- end CRM -->                

         
            <?php } // End showNavigation check ?>
            
            <?php if (!$showNavigation && Auth::check()) { ?>
                <div class="setup-status-message" style="padding: 2rem; text-align: center; color: #6c757d;">
                    <?php if (!$hasConnectedWhatsApp) { ?>
                        <div class="mb-3">
                            <i class="fab fa-whatsapp" style="font-size: 3rem; color: #25D366; margin-bottom: 1rem;"></i>
                            <h5 style="color: #333; margin-bottom: 0.5rem;">{{ __('navigation.setup.connect_whatsapp_title') }}</h5>
                            <p style="margin-bottom: 1rem; font-size: 0.9rem;">{{ __('navigation.setup.connect_whatsapp_message') }}</p>
                            <a href="{{ route('business.wasender') }}" class="btn btn-success">
                                <i class="fab fa-whatsapp mr-2"></i>{{ __('navigation.setup.connect_whatsapp_button') }}
                            </a>
                        </div>
                    <?php } elseif (!$hasProducts) { ?>
                        <div class="mb-3">
                            <i class="fas fa-box" style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;"></i>
                            <h5 style="color: #333; margin-bottom: 0.5rem;">{{ __('navigation.setup.define_products_title') }}</h5>
                            <p style="margin-bottom: 1rem; font-size: 0.9rem;">{{ __('navigation.setup.define_products_message') }}</p>
                            <a href="{{ route('products.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>{{ __('navigation.setup.add_products_button') }}
                            </a>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (!empty(Auth::user()->business) && $showNavigation) { ?>
                <!-- <div id="Business" class="main-icon-menu-pane  <?= in_array(request()->segment(2), ['business']) || Auth::user()->usersEvents()->count() == 0 ? 'active' : '' ?>">
                    <div class="title-box">
                        <h6 class="menu-title">Business </h6>        
                    </div>
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/summary') ?>">{{__('summary')}}</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/request') ?>">{{__('requests')}} </a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/product/1') ?>">{{__('products')}}</a></li>-->
                        <!-- <li class="nav-item"><a class="nav-link" href="<?= url('business/product/2') ?>">{{__('services')}}</a></li> -->
                        <!--<li class="nav-item"><a class="nav-link" href="<?= url('business/promote') ?>">{{__('matangazo')}}</a></li>-->
                    <!-- </ul> -->
                <!-- </div> --> 
                <!-- end Pages -->
            <?php } ?>
         

            <?php if ($showNavigation) { ?>
            <div id="services" class="main-icon-menu-pane ">
                <div class="title-box">
                    <h6 class="menu-title">{{ __('navigation.services') }}</h6>     
                </div>
                <ul class="nav">
                    <li class="nav-item"><a class="nav-link btn btn-outline-success waves-effect waves-light" href="<?= url('service/search') ?>"><i class="mdi mdi-file-search-outline"></i> {{ __('navigation.search') }} </a></li>
                    <?php
                    // $services = \App\Models\Service::all();
                    // foreach ($services as $service) {
                    ?>
                        <!--<li class="nav-item"><a class="nav-link" href="<?php // url('service/show/'.$service->id)     ?>"><?php //echo $service->name     ?></a></li>-->
                    <?php
                    //}
                    ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-success waves-effect waves-light" href="<?= url('service/selected') ?>">{{ __('navigation.selected_services') }}</a></li>
                </ul>
            </div><!-- end Authentication-->
            <?php } // End showNavigation check for services ?>
        </div><!--end menu-body-->
    </div><!-- end main-menu-inner-->
</div>
<!-- end leftbar-tab-menu-->


<!-- Top Bar Start -->
<div class="topbar">           
    <!-- Navbar -->
    <nav class="navbar-custom">    
        <ul class="list-unstyled topbar-nav float-right mb-0"> 
                       <li class="hidden-sm">
                            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="javascript: void(0);" role="button"
                                aria-haspopup="false" aria-expanded="false">
                                 @php
                                     $locale = app()->getLocale();
                                     $languages = [
                                         'en' => 'English',
                                         'es' => 'Español',
                                         'pt' => 'Português',
                                         'pt-br' => 'Português',
                                         'hi' => 'हिंदी',
                                         'ar' => 'العربية',
                                         'fr' => 'Français',
                                         'sw' => 'Kiswahili'
                                     ];
                                     $flags = [
                                         'en' => 'us_flag.jpg',
                                         'es' => 'spain_flag.jpg',
                                         'pt' => 'italy_flag.jpg',
                                         'pt-br' => 'italy_flag.jpg',
                                         'hi' => 'russia_flag.jpg',
                                         'ar' => 'germany_flag.jpg',
                                         'fr' => 'french_flag.jpg',
                                         'sw' => 'tanzania_flag.jpg'
                                     ];
                                     $currentLang = $languages[$locale] ?? 'English';
                                     $currentFlag = $flags[$locale] ?? 'us_flag.jpg';
                                 @endphp
                                 {{ $currentLang }}
                                 <img src="{{ asset(ROOT.'assets/images/flags/' . $currentFlag) }}" class="ml-2" height="16" alt=""/>
                                 <i class="mdi mdi-chevron-down"></i> 
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                 <a class="dropdown-item {{ $locale == 'en' ? 'active' : '' }}" href="{{ url('lang/en') }}">
                                      <span>English</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/us_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ $locale == 'es' ? 'active' : '' }}" href="{{ url('lang/es') }}">
                                      <span>Español</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/spain_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ in_array($locale, ['pt', 'pt-br']) ? 'active' : '' }}" href="{{ url('lang/pt') }}">
                                      <span>Português</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/italy_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ $locale == 'hi' ? 'active' : '' }}" href="{{ url('lang/hi') }}">
                                      <span>हिंदी</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/russia_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ $locale == 'ar' ? 'active' : '' }}" href="{{ url('lang/ar') }}">
                                      <span>العربية</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/germany_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ $locale == 'fr' ? 'active' : '' }}" href="{{ url('lang/fr') }}">
                                      <span>Français</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/french_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item {{ $locale == 'sw' ? 'active' : '' }}" href="{{ url('lang/sw') }}">
                                      <span>Kiswahili</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/tanzania_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                            </div>
                        </li>

            <!-- <li class="dropdown notification-list">
                             <a class="nav-link dropdown-toggle arrow-none" data-toggle="dropdown" href="#" role="button"
                                   aria-haspopup="false" aria-expanded="false">
                                    <i class="ti-bell noti-icon"></i>
                                    <span class="badge badge-danger badge-pill noti-icon-badge">2</span>
                                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-lg py-0">

                    <h6 class="dropdown-item-text font-15 m-0 py-3 bg-light text-dark d-flex justify-content-between align-items-center">
                        Notifications <span class="badge badge-primary badge-pill">2</span>
                    </h6> 
                    <div class="slimscroll notification-list">
                     
                        <a href="#" class="dropdown-item py-3">
                            <small class="float-right text-muted pl-2">2 min ago</small>
                            <div class="media">
                                <div class="avatar-md bg-soft-primary">
                                    <i class="la la-cart-arrow-down"></i>
                                </div>
                                <div class="media-body align-self-center ml-2 text-truncate">
                                    <h6 class="my-0 font-weight-normal text-dark">Your order is placed</h6>
                                    <small class="text-muted mb-0">Dummy text of the printing and industry.</small>
                                </div>
                            </div>
                        </a>
                     
                    </div>
                   
                    <a href="javascript:void(0);" class="dropdown-item text-center text-primary bg-light">
                        View all <i class="fi-arrow-right"></i>
                    </a>
                </div>
            </li> -->

            <li class="dropdown">
                <a class="nav-link dropdown-toggle nav-user" data-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <img src="<?= asset(ROOT.'assets/images/users/user-1.jpg') ?>" alt="profile-user" class="rounded-circle" /> 
                    <span class="ml-1 nav-user-name hidden-sm"> {{ Auth::user()->name }} <i class="mdi mdi-chevron-down"></i> </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                
                    <!-- <a class="dropdown-item" href="<?= url('home/payments') ?>"><i class="dripicons-user text-muted mr-2"></i> {{__('payments')}}</a> -->
                
                 <!--    <a class="dropdown-item" href="#"><i class="dripicons-wallet text-muted mr-2"></i> My Payments</a>-->
                        <?php
                    if (Auth::user()->usersEvents()->count() > 0) {
                    ?>
                    <a class="dropdown-item" href="<?= url('home/settings') ?>"><i class="dripicons-gear text-muted mr-2"></i> {{ __('navigation.settings') }}</a>
                    <?php }?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item bg-light" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                               document.getElementById('logout-form').submit();">
                        <i class="dripicons-exit text-muted mr-2"></i> {{ __('navigation.logout') }}
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
                    <li class="mr-2">
                        <button id="theme-toggle" class="btn btn-outline-secondary nav-link" style="border: none; background: none;" title="{{ __('navigation.toggle_dark_mode') }}">
                            <i id="theme-toggle-icon" data-feather="moon" class="align-self-center"></i>
                        </button>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const toggleBtn = document.getElementById('theme-toggle');
                                const icon = document.getElementById('theme-toggle-icon');
                                // Check localStorage for theme
                                if (localStorage.getItem('theme') === 'dark') {
                                    document.body.classList.add('dark-mode');
                                    icon.setAttribute('data-feather', 'sun');
                                }
                                feather.replace();

                                toggleBtn.addEventListener('click', function () {
                                    document.body.classList.toggle('dark-mode');
                                    const isDark = document.body.classList.contains('dark-mode');
                                    icon.setAttribute('data-feather', isDark ? 'sun' : 'moon');
                                    feather.replace();
                                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                                });
                            });
                        </script>
                        <style>
                            .dark-mode {
                                background: #18191a !important;
                                color: #f5f6fa !important;
                            }
                            .dark-mode .navbar-custom, .dark-mode .leftbar-tab-menu, .dark-mode .main-menu-inner {
                                background: #23272b !important;
                            }
                            .btn#theme-toggle:focus {
                                outline: none;
                                box-shadow: none;
                            }
                        </style>
                    </li>
        </ul><!--end topbar-nav-->

        <ul class="list-unstyled topbar-nav mb-0">  
            <li>
                <a href="#">
                    <span class="responsive-logo">
                        <img src="<?= asset(ROOT.'assets/images/safarichat.png') ?>?v=3" alt="logo-small" class="logo-sm align-self-center" height="34">
                    </span>
                </a>
            </li>                      
            <li>
                <button class="button-menu-mobile nav-link">
                    <i data-feather="menu" class="align-self-center"></i>
                </button>
            </li>
            <?php
            if (!preg_match('/upgrade/', url()->current())) {
                ?>
               
            <?php } ?>
            <li class="hide-phone app-search">
                <!--                <form role="search" class="">
                                    <input type="text" id="AllCompo" placeholder="Search..." class="form-control">
                                    <a href=""><i class="fas fa-search"></i></a>
                                </form>-->

            </li>
            <?php
            if (empty($package) && !empty(Auth::user()->usersEvents()->first())) {
                ?>
                {{-- <a href="<?= url('home/upgrade') ?>" class="alert icon-custom-alert alert-outline-pink b-round fade show">                                             --}}
                    {{-- <i class="mdi mdi-alert-outline alert-icon"></i>
                    <div class="alert-text">
                        <strong>Welcome!</strong> Your trial period will end on <?= date('d M Y', strtotime(Auth::user()->created_at . ' + ' . config('app.TRIAL_DAYS') . ' Days')) ?>
                    </div> --}}

                    {{-- <div class="alert-close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true"><i class="mdi mdi-close text-danger"></i></span>
                        </button>
                    </div> --}}
                </a>
<?php } ?>
        </ul>
    </nav>

    <!-- end navbar-->

</div>
<!-- Top Bar End -->

<?php if ((int) Auth::user()->verified == 0 && Auth::user()->email_verified_at == null) { ?>
    <div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('navigation.verification.modal_title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ __('navigation.verification.enter_code_message') }}</p>
                    <div class="form-group">
                        <label for="code_verification">{{ __('navigation.verification.enter_code_label') }}</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" name="code" class="form-control" placeholder="{{ __('navigation.verification.code_placeholder') }}">

                            <span class="input-group-append">
                                <button type="button" class="btn  btn-sm btn-primary" id="verify_account">{{ __('navigation.verification.submit_button') }}</button>
                            </span>

                        </div> 
                        <p><span id="feedback_message"></span></p>
                    </div>
                    <h2>{{ __('navigation.verification.or_label') }}</h2>
                    <p>{{ __('navigation.verification.resend_label') }} 
                        <!--To <a href="#" data-id="email" class="btn btn-outline-primary resend_code">Resend Email</a> ,--> 
                        To <a href="#" data-id="whatsapp" class="btn btn-outline-success resend_code">{{ __('navigation.verification.resend_whatsapp') }}</a></p>
                    <br/>
                    <hr/>
                    <p>{{ __('navigation.verification.note') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('navigation.verification.close_button') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        // Localized messages for JavaScript
        const i18n = {
            fieldRequired: "{{ __('navigation.verification.field_required') }}",
            wrongCode: "{{ __('navigation.verification.wrong_code') }}",
            success: "{{ __('navigation.verification.success') }}"
        };
        
        verify_account = function () {
            $('#verify_account').mousedown(function () {
                var val = $('#example-input2-group2').val();
                if ($.trim(val) == '') {
                    $('#feedback_message').html(i18n.fieldRequired).addClass('alert alert-danger');
                } else {
                    $.ajax({
                        type: 'POST',
                        url: "<?= url('home/verify') ?>",
                        data: {"code": val},
                        dataType: "html",
                        success: function (data) {
                            if (data == 'success') {
                                $('#feedback_message').html(data).addClass('alert alert-success');
                                ;
                                window.location.reload();
                            } else {
                                $('#feedback_message').html(i18n.wrongCode).addClass('alert alert-danger');
                                ;
                            }

                        }
                    });
                }
            });
            $('.resend_code').mousedown(function () {
                var tag = $(this).attr('data-id');
                $.ajax({
                    type: 'POST',
                    url: "<?= url('home/resend') ?>",
                    data: {tag: tag},
                    dataType: "html",
                    success: function (data) {
                        if (data == 'success') {
                            $('#feedback_message').html(data).addClass('alert alert-success');
                        } else {
                            $('#feedback_message').html(i18n.wrongCode).addClass('alert alert-danger');
                            ;
                        }

                    }
                });

            });
        }

        $(document).ready(verify_account);
    </script>
<?php }
?>
<!-- Support modal removed - use external support tools -->