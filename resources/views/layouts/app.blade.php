<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
   
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="keywords" content="" />
        <meta name="author" content="" />
        <meta name="robots" content="" />
        <meta name="description" content="Wedding Manager" />
        <meta property="og:title" content="Wedding Manager" />
        <meta property="og:description" content="Wedding Manager" />
        <meta property="og:image" content="" />
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
         <script src="{{ asset(ROOT.'assets/js/jquery.min.js')}}"></script>
        <title>{{ config('app.name', 'DikoDiko') }}</title>

        <!-- Scripts -->
        <!--<script src="{{ asset(ROOT.'js/app.js') }}" defer></script>-->

        <!-- Fonts -->
        <!--        <link rel="dns-prefetch" href="//fonts.gstatic.com">
                <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">-->

        <!-- Styles -->


        <!-- App css -->
        <link href="{{ asset(ROOT.'assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset(ROOT.'assets/css/jquery-ui.min.css')}}" rel="stylesheet">
        <link href="{{ asset(ROOT.'assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset(ROOT.'assets/css/metisMenu.min.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset(ROOT.'assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
       
      @if(isset($darkmode))

          <link href="{{ asset(ROOT.'assets/css/bootstrap-dark.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset(ROOT.'assets/css/app-dark.css" rel="stylesheet')}}" type="text/css" />
    <link rel="stylesheet" href="https://demo.shulesoft.africa/public/assets/select2/css/select2_dark.css?v=44">
    <link rel="stylesheet"
        href="https://demo.shulesoft.africa/public/assets/select2/css/select2-bootstrap_dark.css?=41">
    <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'assets/css/app-dark.css')}}">

    @endif
       
       <style type="text/css">
            .select2{width:100% !important}
        </style>

        <script type="text/javascript">
ajax_setup = function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: true,
        cache: false,
        beforeSend: function (xhr) {
            jQuery('#loader-content').show();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            jQuery('#loader-content').hide();
        },
        complete: function (xhr, status) {
            jQuery('#loader-content').hide();

        }
    });
}
$(document).ready(ajax_setup);
        </script>






        <!--               <link href="{{ asset(ROOT.'css/app.css') }}" rel="stylesheet">
                
                
                         FAVICONS ICON 
                        <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
                        <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
                
                
                
                         MOBILE SPECIFIC 
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                
                        [if lt IE 9]>-->
        <!--                <script src="js/html5shiv.min.js') }}"></script>
                        <script src="js/respond.min.js') }}"></script>-->
        <!--<![endif]-->

        <!--DataTables--> 
        <link href="{{ asset(ROOT.'datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset(ROOT.'datatables/buttons.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />
        <!--Responsive datatable examples--> 
        <link href="{{ asset(ROOT.'datatables/responsive.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" /> 
     
        <!--
                STYLESHEETS 
               <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/plugins.css') }}">
               <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/style.css') }}">
               <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/templete.css') }}">
               <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/business.min.css') }}">
               <link rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/responsive.min.css') }}">
               <link class="skin" rel="stylesheet" type="text/css" href="{{ asset(ROOT.'css/skin/skin-1.css') }}">	
                    <script src="{{ asset(ROOT.'js/jquery.min.js') }}"></script> JQUERY.MIN JS 
               <script src="{{ asset(ROOT.'js/jquery-ui.js') }}"></script> JQUERY.MIN JS -->
    </head>
    <body id="bg">
        <div id="loading-area"></div>
        <div id="app">
            <!--            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
                            <div class="container">
            
                                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                     Left Side Of Navbar 
                                    <ul class="navbar-nav mr-auto">
            
                                    </ul>
            
                                     Right Side Of Navbar 
                                    <ul class="navbar-nav ml-auto">
                                         Authentication Links 
                                        @guest
                                        @if (Route::has('login'))
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                        </li>
                                        @endif
            
                                        @if (Route::has('register'))
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                        </li>
                                        @endif
                                        @else
            
                                        @endguest
                                    </ul>
                                </div>
                            </div>
                        </nav>-->

            @guest
            <div class="page-wraper">
                <div class="bns-frame">
                    @else

                    <?php
                    // if (Auth::user()->usersEvents()->count() > 0) {
                    ?>
                     @include('layouts.checkpayment')

                    @include('layouts.nav')

                    <?php // }  ?>
                    <div class="page-wrapper">

                        <!-- Page Content-->
                        <div class="page-content-tab">
                            @endguest

                            @yield('content')
                        </div>
                    </div>
                </div>


                <!-- JAVASCRIPT FILES ========================================= -->
                <!--   
                        <script src="{{ asset(ROOT.'plugins/wow/wow.js') }}"></script> BOOTSTRAP.MIN JS 
                        <script src="{{ asset(ROOT.'plugins/bootstrap/js/popper.min.js') }}"></script> BOOTSTRAP.MIN JS 
                        <script src="{{ asset(ROOT.'plugins/bootstrap/js/bootstrap.min.js') }}"></script> BOOTSTRAP.MIN JS 
                        <script src="{{ asset(ROOT.'plugins/bootstrap-select/bootstrap-select.min.js') }}"></script> FORM JS 
                        <script src="{{ asset(ROOT.'plugins/bootstrap-touchspin/jquery.bootstrap-touchspin.js') }}"></script> FORM JS 
                        <script src="{{ asset(ROOT.'plugins/magnific-popup/magnific-popup.js') }}"></script> MAGNIFIC POPUP JS 
                        <script src="{{ asset(ROOT.'plugins/counter/waypoints-min.js') }}"></script> WAYPOINTS JS 
                        <script src="{{ asset(ROOT.'plugins/counter/counterup.min.js') }}"></script> COUNTERUP JS 
                        <script src="{{ asset(ROOT.'plugins/imagesloaded/imagesloaded.js') }}"></script> IMAGESLOADED 
                        <script src="{{ asset(ROOT.'plugins/masonry/masonry-3.1.4.js') }}"></script> MASONRY 
                        <script src="{{ asset(ROOT.'plugins/masonry/masonry.filter.js') }}"></script> MASONRY 
                        <script src="{{ asset(ROOT.'plugins/rangeslider/rangeslider.js') }}"></script> RANGESLIDER 
                        <script src="{{ asset(ROOT.'plugins/owl-carousel/owl.carousel.js') }}"></script> OWL SLIDER 
                        <script src="{{ asset(ROOT.'plugins/particles/particles.js') }}"></script>
                        <script src="{{ asset(ROOT.'plugins/particles/particles-app.js') }}"></script>
                        <script src="{{ asset(ROOT.'js/dz.carousel.js') }}"></script> SORTCODE FUCTIONS  
                        <script src="{{ asset(ROOT.'js/custom.js') }}"></script> CUSTOM FUCTIONS  
                        <script src="{{ asset(ROOT.'js/dz.ajax.js') }}"></script> CONTACT JS  
                                <script src="{{ asset(ROOT.'datatables/jquery.dataTables.min.js')}}"></script>
                        <script src="{{ asset(ROOT.'datatables/dataTables.bootstrap4.min.js')}}"></script>-->


                <!-- App js -->
        <!--        <script src="../assets/js/app.js"></script>-->

                <!-- jQuery  -->
                <script src="{{ asset(ROOT.'datatables/jquery.dataTables.min.js')}}"></script>
                <script src="{{ asset(ROOT.'datatables/dataTables.bootstrap4.min.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/jquery-ui.min.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/bootstrap.bundle.min.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/metismenu.min.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/waves.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/feather.min.js')}}"></script>
                <script src="{{ asset(ROOT.'assets/js/jquery.slimscroll.min.js')}}"></script>
                <script src="{{ asset(ROOT.'plugins/apexcharts/apexcharts.min.js')}}"></script> 

                <!-- App js -->
                <script src="{{ asset(ROOT.'assets/js/app.js')}}?v=2"></script>
                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

                <script type="text/javascript" src="<?php echo asset(ROOT.'assets/toastr/toastr.min.js'); ?>"></script>
                <link href="<?php echo asset(ROOT.'assets/toastr/toastr.min.css'); ?>" rel="stylesheet">
                <script>
// Datatable
$('.dataTable').DataTable();
$(document).ready(function () {
    $('.select2').select2();
});

                </script>

                <style>
                    .pnotify_first{
                        margin-top: 10em;
                    }
                </style>
                @if ($message = Session::get('success'))
                <script type="text/javascript">
                    load_toast = function () {
                        toastr["success"]("<?= $message ?>");
                        toastr.options = {
                            "closeButton": true,
                            "debug": false,
                            "newestOnTop": false,
                            "progressBar": false,
                            "positionClass": "toast-top-right",
                            "preventDuplicates": false,
                            "onclick": null,
                            "showDuration": "500",
                            "hideDuration": "500",
                            "timeOut": "5000",
                            "extendedTimeOut": "1000",
                            "showEasing": "swing",
                            "hideEasing": "linear",
                            "showMethod": "fadeIn",
                            "hideMethod": "fadeOut"
                        }
                    }
                    $(document).ready(load_toast);
                </script>
                @endif
                @yield('footer')
                </body>

                <!-- Start of HubSpot Embed Code -->
                <script type="text/javascript" id="hs-script-loader" async defer src="//js.hs-scripts.com/9474308.js"></script>
                <!-- End of HubSpot Embed Code -->
                </html>
