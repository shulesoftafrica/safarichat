<?php ?>
<!-- Add Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-dyZt6QpDqT1Q6Xo8XzYx+lNEwGDbVS9/6IKxhpcJn/qdNqxabWWMwBLT/gRghOAqxCLv7saEh1P1LzpTnt324g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!-- leftbar-tab-menu -->
<div class="leftbar-tab-menu">
    <div class="main-icon-menu">
        <a href="<?= url('/') ?>" class="logo logo-metrica d-block text-center">
            <span>
                <img src="<?= asset(ROOT.'assets/images/safarichat.png') ?>?v=3" alt="logo-small" class="logo-sm">
            </span>
        </a>
        <nav class="nav">
            <?php
            if (!preg_match('/upgrade/', url()->current()) && Auth::user()->usersEvents()->count() > 0) {
                ?>

                <a href="#MetricaCRM" class="nav-link active" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="Message" data-trigger="hover">
                    <i data-feather="grid" class="align-self-center menu-icon icon-dual"></i>

                </a>
                <h6>{{__('message')}}</h6> 

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
        </nav><!--end nav-->
        <div class="pro-metrica-end">
            <!-- <a href="" class="help" data-toggle="tooltip-custom" data-placement="right" title="" data-original-title="{{__('support')}}" >
                <i data-feather="message-circle" class="align-self-center menu-icon icon-md icon-dual mb-4"></i> 

            </a>
            <span>{{__('support')}}</span>
            <a href="" class="profile"  data-toggle="modal" data-animation="fade"  data-toggle="modal" data-target="#support">
                <img src="<?= asset(ROOT.'assets/images/users/user-1.jpg') ?>" alt="profile-user" class="rounded-circle thumb-sm"> 

            </a> -->

            <!-- Chatra {literal} -->
<script>
    (function(d, w, c) {
        w.ChatraID = 'rvY8vcZLkE5Yu4Yzh';
        var s = d.createElement('script');
        w[c] = w[c] || function() {
            (w[c].q = w[c].q || []).push(arguments);
        };
        s.async = true;
        s.src = 'https://call.chatra.io/chatra.js';
        if (d.head) d.head.appendChild(s);
    })(document, window, 'Chatra');
</script>
<!-- /Chatra {/literal} -->
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
            <?php
            if (!preg_match('/upgrade/', url()->current()) && Auth::user()->usersEvents()->count() > 0) {
                ?>
                <div id="MetricaCRM" class="main-icon-menu-pane <?= !in_array(request()->segment(1), ['message', 'business']) ? 'active' : '' ?> ">
                    <div class="title-box">
                        <h6 class="menu-title">{{__('category')}}</h6>       
                    </div>

                    <ul class="nav in mm-show">
                        <li class="nav-item active">
                            <a class="nav-link" href="<?= url('/') ?>">
                                <i class="fab fa-whatsapp align-middle mr-2" title="Summary"></i>{{__('summary')}}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('guest') ?>">
                                <i class="fas fa-address-book"></i>{{__('Customers')}}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('message') ?>">
                                <i class="fas fa-paper-plane align-middle mr-2" title="Send Message"></i>{{__('Send Message')}}
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/whatsappGroup') ?>">
                                <i class="fas fa-object-group align-middle mr-2" title="Groups"></i>{{__('Groups')}}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/sent') ?>">
                                <i class="fab fa-whatsapp-square align-middle mr-2" title="Channels"></i>{{__('Channels')}}
                            </a>
                        </li> -->
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/schedule') ?>">
                                <i class="fas fa-calendar-alt align-middle mr-2" title="Schedule"></i>{{__('schedule')}}
                            </a>
                        </li> -->
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="<?= url('service/index') ?>">
                                <i class="fas fa-robot align-middle mr-2" title="AI Sales Officer"></i>{{__('AI Sales Officer')}}
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('message/report') ?>">
                                <i class="fas fa-calendar-alt align-middle mr-2" title="Reports"></i>{{__('reports')}}
                            </a>
                        </li>
                    
                       
                    </ul>
                </div><!-- end CRM -->                

         
            <?php } ?>

            <?php if (!empty(Auth::user()->business)) { ?>
                <div id="Business" class="main-icon-menu-pane  <?= in_array(request()->segment(2), ['business']) || Auth::user()->usersEvents()->count() == 0 ? 'active' : '' ?>">
                    <div class="title-box">
                        <h6 class="menu-title">Business </h6>        
                    </div>
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/summary') ?>">{{__('summary')}}</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/request') ?>">{{__('requests')}} </a></li>
                        <!--<li class="nav-item"><a class="nav-link" href="<?= url('business/product/1') ?>">{{__('products')}}</a></li>-->
                        <li class="nav-item"><a class="nav-link" href="<?= url('business/product/2') ?>">{{__('services')}}</a></li>
                        <!--<li class="nav-item"><a class="nav-link" href="<?= url('business/promote') ?>">{{__('matangazo')}}</a></li>-->
                    </ul>
                </div><!-- end Pages -->
            <?php } ?>
         

            <div id="services" class="main-icon-menu-pane ">
                <div class="title-box">
                    <h6 class="menu-title">{{__('services')}}</h6>     
                </div>
                <ul class="nav">
                    <li class="nav-item"><a class="nav-link btn btn-outline-success waves-effect waves-light" href="<?= url('service/search') ?>"><i class="mdi mdi-file-search-outline"></i> {{__('search')}} </a></li>
                    <?php
                    // $services = \App\Models\Service::all();
                    // foreach ($services as $service) {
                    ?>
                        <!--<li class="nav-item"><a class="nav-link" href="<?php // url('service/show/'.$service->id)     ?>"><?php //echo $service->name     ?></a></li>-->
                    <?php
                    //}
                    ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-success waves-effect waves-light" href="<?= url('service/selected') ?>">{{__('selected_services')}}</a></li>
                </ul>
            </div><!-- end Authentication-->
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
                                 {{ app()->getLocale() == 'sw' ? 'Swahili' : 'English' }}
                                 <img src="{{ asset(ROOT.'assets/images/flags/' . (app()->getLocale() == 'sw' ? 'tanzania_flag.jpg' : 'us_flag.jpg')) }}" class="ml-2" height="16" alt=""/>
                                 <i class="mdi mdi-chevron-down"></i> 
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                 <a class="dropdown-item" href="{{ url('lang/en') }}">
                                      <span>English</span>
                                      <img src="{{ asset(ROOT.'assets/images/flags/us_flag.jpg') }}" alt="" class="ml-2 float-right" height="14"/>
                                 </a>
                                 <a class="dropdown-item" href="{{ url('lang/sw') }}">
                                      <span>Swahili</span>
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
                    <a class="dropdown-item" href="<?= url('home/settings') ?>"><i class="dripicons-gear text-muted mr-2"></i> {{__('settings')}}</a>
                    <?php }?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item bg-light" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                               document.getElementById('logout-form').submit();">
                        <i class="dripicons-exit text-muted mr-2"></i> {{ __('Logout') }}
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
                    <li class="mr-2">
                        <button id="theme-toggle" class="btn btn-outline-secondary nav-link" style="border: none; background: none;" title="Toggle Dark/Light Mode">
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
            <?php if ((int) Auth::user()->verified == 0 && Auth::user()->email_verified_at == null) { ?>
                <a href="#" class="alert icon-custom-alert alert-outline-pink b-round fade show" role="alert" data-toggle="modal" data-target="#verifyModal">                                            
                    <i class="mdi mdi-alert-outline alert-icon"></i>
                    <div class="alert-text">
                        <strong>Welcome!</strong> Kindly check your Whatsapp to Verify your account.
                    </div>

                    <div class="alert-close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true"><i class="mdi mdi-close text-danger"></i></span>
                        </button>
                    </div>
                </a>
            <?php
            }
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
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Email & Phone Verification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>From Your Email or Your WhatsApp Inbox, kindly enter the code you have received</p>
                    <div class="form-group">
                        <label for="code_verification">Enter Verification Code</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" name="code" class="form-control" placeholder="Code">

                            <span class="input-group-append">
                                <button type="button" class="btn  btn-sm btn-primary" id="verify_account">Submit</button>
                            </span>

                        </div> 
                        <p><span id="feedback_message"></span></p>
                    </div>
                    <h2>Or</h2>
                    <p>Click here : 
                        <!--To <a href="#" data-id="email" class="btn btn-outline-primary resend_code">Resend Email</a> ,--> 
                        To <a href="#" data-id="whatsapp" class="btn btn-outline-success resend_code">Resend Whatsapp Message</a></p>
                    <br/>
                    <hr/>
                    <p>NB; Account not verified will be deleted automatically after 24 hours</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        verify_account = function () {
            $('#verify_account').mousedown(function () {
                var val = $('#example-input2-group2').val();
                if ($.trim(val) == '') {
                    $('#feedback_message').html('This field is required').addClass('alert alert-danger');
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
                                $('#feedback_message').html('Wrong code supplied').addClass('alert alert-danger');
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
                            $('#feedback_message').html('Wrong code supplied').addClass('alert alert-danger');
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
<div class="modal fade" id="support" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep1" action="{{url('home/support/null')}}" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Support Requests</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tell us a brief about your issue</p>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Main Topic</label>
                        <select class="form-control" name="topic">
                            <option value="question">I have a question</option>
                            <option value="suggestion">I have a suggestion of what needs to be done</option>
                            <option value="complain">I have a complain</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="detail" class=" col-form-label text-right">Descriptions</label>
                        <textarea name="details" id="edit_descriptions" required="" class="form-control" placeholder="Write Clear Descriptions (use English or Swahili)"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
<?= csrf_field() ?>
                    <input type="hidden" value="<?= time() ?>" name="transaction_id"/>
                    <span id="add_inputs"></span>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>