@extends('layouts.app')
@section('content')
<div class="container-fluid">



    <!-- Top Bar Start -->
    <div class="topbar">           
        <!-- Navbar -->
        <nav class="navbar-custom" style="margin-left: 0 !important">    
            <ul class="list-unstyled topbar-nav float-right mb-0"> 




                <li class="dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="<?= url('/') ?>" role="button"
                       aria-haspopup="false" aria-expanded="false">
                        <img src="<?= asset('assets/images/logo-sm.png') ?>" alt="profile-user" class="rounded-circle" /> 
                        <span class="ml-1 nav-user-name hidden-sm">DikoDiko  </span>
                    </a>

                </li>

            </ul><!--end topbar-nav-->


        </nav>
        <!-- end navbar-->
    </div>
    <!-- Top Bar End -->

    <div>

        <!-- Page Content-->
        <div>
            <?php if (isset($noevent) && $noevent == 1) { ?>
                <div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title mt-0" id="exampleModalLabel">Live Event Settings</h5>
                                <!--                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>-->
                            </div>
                            <form method="POST" action="<?= url('api/verifyCode') ?>">
                                <div class="modal-body">
                                    <p>Enter Your Verification Code to see this Event Live</p>
                                    <div class="form-group">
                                        <div class="form-group row">
                                            <label for="horizontalInput1" class="col-sm-2 col-form-label">Code</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="horizontalInput1" name="code" placeholder="Enter Code Sent to your phone">
                                            </div>
                                        </div>

                                    </div>

                                    <hr/>
<!--                                    <p>NB; If you have not received the code, kindly enter your phone number below</p>
                                    <div class="input-group">
                                        <input type="text" id="get_new_code" name="phone" class="form-control" placeholder="Enter Your Phone Number">
                                        <span class="input-group-append">
                                            <button type="button" id="resend_code" class="btn  btn-gradient-primary">Submit</button>
                                        </span>
                                        <span id="smsstatus"></span>
                                    </div>-->
                                </div>
                                <div class="modal-footer">
                                    <input type="submit" class="btn btn-success" value="Proceed to Live Page"></input>
                                </div>
                                <?= csrf_field() ?>
                            </form>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    $(window).on('load', function () {
                        $('#payment_model').modal({backdrop: 'static', keyboard: false, show: true});
                    });
                    resend_code = function () {
                        $('#resend_code').mousedown(function () {
                            var val = $('#get_new_code').val();
                            $.ajax({
                                type: 'POST',
                                url: "<?= url('api/resendCode') ?>",
                                data: "val=" + val,
                                dataType: "html",
                                success: function (data) {
                                    $('#smsstatus').html(data);
                                }
                            });
                        })
                    }
                    $(document).ready(resend_code);
                </script>
            <?php } else {
              
                ?>
                <div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="float-right">
                                    <ol class="breadcrumb">

                                        <li class="breadcrumb-item active">Hi, <?= session('name') ?></li>
                                    </ol>
                                </div>
                               
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    <!-- end page title end breadcrumb -->

                    <div class="row">
                        <div class="col-lg-8">
                            <ol class="breadcrumb">

                                        <li class="breadcrumb-item active"> <h4 class="page-title"><?= $event->name ?></h4></li>
                                    </ol>
                            <div class="card">
                                
                                <div class="card-body">                                    
                                    <div class="row">
                                        <link rel="stylesheet" href="https://academy.shulesoft.com/assets/global/plyr/plyr.css">

                                        <div class="plyr__video-embed" id="player" style="width: 100%">
                                            <iframe height="800" src="<?= $event->url ?>?origin=https://plyr.io&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1" allowfullscreen allowtransparency allow="autoplay"></iframe>
                                        </div>

                                        <script src="https://academy.shulesoft.com/assets/global/plyr/plyr.js"></script>
                                        <script>const player = new Plyr('#player');</script>


                                    </div><!--end row-->                                   
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col--> 
                        <div class="col-lg-4">
                            <ol class="breadcrumb">

                                <li class="breadcrumb-item active"> <h4 class="page-title">Hi, <?= ucwords(strtolower($guest->guest_name)) ?></h4></li>
                                    </ol>
                            <div class="card">
                                <div class="card-body">  
                                    <h4 class="header-title mt-0 mb-3">About the Event</h4>                                   

                                    <div class="row"> 
                                        <div class="col-lg-12">
 <div class="">
                                                <span class="text-dark">About</span>
                                                <small class="float-right text-muted ml-3 font-13"><?= $event->name ?></small>
                                                <div class="progress mt-2" style="height:3px;">

                                                </div>
                                            </div>
                                            <div class="">
                                                <span class="text-dark">Location</span>
                                                <small class="float-right text-muted ml-3 font-13"><?= $event->location ?></small>
                                                <div class="progress mt-2" style="height:3px;">

                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <span class="text-dark">Event Type</span>
                                                <small class="float-right text-muted ml-3 font-13"><?= $event->eventsType->name ?></small>
                                                <div class="progress mt-2" style="height:3px;">

                                                </div>
                                            </div>   
                                            <div class="mt-3">
                                                <span class="text-dark">Total Guests</span>
                                                <small class="float-right text-muted ml-3 font-13"><?= $event->eventsGuests()->count() ?></small>
                                                <div class="progress mt-2" style="height:3px;">

                                                </div>
                                            </div> 

                                        </div><!--end col-->
                                        <div class="col-lg-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h4 class="mt-0 header-title">Follow DikoDiko</h4>
                                                    <div class="img-group">
                                                        <a class="user-avatar mr-2" target="_blank" href="https://instagram.com/dikodikoafrica">
                                                            <img src="<?=asset('/images/instagram.jpg')?>" alt="instagram" height="30" width="40" class="">
                                                            <span class="avatar-badge online"></span>
                                                        </a>
                                                        <a class="user-avatar mr-2" href="https://www.youtube.com/channel/UCIh4plEXusxgTot6fQCXMbw" target="_blank">
                                                            <img src="<?=asset('/images/youtube.png')?>" alt="instagram" height="30" width="" class="">
                                                            <span class="avatar-badge online"></span>
                                                        </a>
                                                        
                                                    </div><!--end img-group-->
                                                </div><!--end card-body-->
                                            </div><!--end card-->
                                        </div>
                                    </div><!--end row-->                                     
                                </div><!--end card-body--> 
                            </div>
                        </div> <!--end col--> 
                    </div> <!--end row--> 
                    <div class="row justify-content-center">
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col-8">
                                            <p class="text-dark font-weight-semibold font-14">Total Members</p>
                                            <h3 class="my-3"><?= $event->eventsGuests()->count() ?></h3>
                                            <p class="mb-0 text-truncate">
                                                <span class="text-success">
                                                    <i class="mdi mdi-trending-up"></i><?= $event->eventsGuests()->whereIn('id', \App\Models\Payment::get(['events_guests_id']))->count() ?></span> New Attend Today
                                            </p>
                                        </div>
                                        <div class="col-4 align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="users" class="align-self-center icon-dual-pink icon-lg"></i>  
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col--> 
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">                                                
                                        <div class="col-8">
                                            <p class="text-dark font-weight-semibold font-14">Event Start Time </p>
                                            <h3 class="my-3">18:00h</h3>
                                            <p class="mb-0 text-truncate"><span class="text-success"><i class="mdi mdi-trending-up"></i>100%</span> Ontime</p>
                                        </div>
                                        <div class="col-4 align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="clock" class="align-self-center icon-dual-secondary icon-lg"></i>  
                                            </div>
                                        </div> 
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col--> 
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">                                                
                                        <div class="col-8">
                                            <p class="text-dark font-weight-semibold font-14">Event End Time </p>
                                            <h3 class="my-3">23:59h</h3>
                                            <p class="mb-0 text-truncate"><span class="text-danger"><i class="mdi mdi-trending-down"></i>100%</span> On time</p>
                                        </div>
                                        <div class="col-4 align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="pie-chart" class="align-self-center icon-dual-purple icon-lg"></i>  
                                            </div>
                                        </div> 
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col--> 
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col-8">
                                            <p class="text-dark font-weight-semibold font-14"> Send Your Gift</p>
                                            <h3 class="my-3" style="font-size:18px"><?= $event->usersEvents()->first()->user->phone ?></h3>
                                            <p class="mb-0 text-truncate"><span class="text-success"><i class="mdi mdi-trending-up"></i></span> All gifts allowed </p>
                                        </div>
                                        <div class="col-4 align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="briefcase" class="align-self-center icon-dual-warning icon-lg"></i>  
                                            </div>
                                        </div> 
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col-->                               
                    </div>
                    <!--end row-->


                </div><!-- container -->



            </div>
        <?php   if($guest->payments()->count()==0) {?>
        <div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title mt-0" id="exampleModalLabel">Event Payment</h5>
                                <!--                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>-->
                            </div>
                            <form method="get" action="#">
                                <div class="modal-body">
                                    <p> Sorry, You need to contribute a certain amount first to this event for you to attend it</p>
                                    <p>Do you wish to contribute something ?</p>
                                    <p>Send it here <h3><?= $event->usersEvents()->first()->user->phone ?></h3></p>
                                <br/>
                                <p>Once you send payment, kindly call this number to confirm for your account to be enabled and view this event live</p>
<!--                                    <div class="form-group">
                                        <div class="form-group row">
                                            <label for="horizontalInput1" class="col-sm-2 col-form-label"> Amount </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="horizontalInput1" name="amount" placeholder="Enter amount you want to contribute">
                                            </div>
                                        </div>

                                    </div>-->

                                    <hr/>
<!--                                    <p>NB; If you have not received the code, kindly enter your phone number below</p>
                                    <div class="input-group">
                                        <input type="text" id="get_new_code" name="phone" class="form-control" placeholder="Enter Your Phone Number">
                                        <span class="input-group-append">
                                            <button type="button" id="resend_code" class="btn  btn-gradient-primary">Submit</button>
                                        </span>
                                        <span id="smsstatus"></span>
                                    </div>-->
                                </div>
                                <div class="modal-footer">
                                    <input type="submit" class="btn btn-success" value="Proceed"></input>
                                </div>
                                <?= csrf_field() ?>
                            </form>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    $(window).on('load', function () {
                        $('#payment_model').modal({backdrop: 'static', keyboard: false, show: true});
                    });
                    resend_code = function () {
                        $('#resend_code').mousedown(function () {
                            var val = $('#get_new_code').val();
                            $.ajax({
                                type: 'POST',
                                url: "<?= url('api/resendCode') ?>",
                                data: "val=" + val,
                                dataType: "html",
                                success: function (data) {
                                    $('#smsstatus').html(data);
                                }
                            });
                        })
                    }
                    $(document).ready(resend_code);
                </script>
        <?php }} ?> 
        <!-- end page content -->
    </div>
    <!-- end page-wrapper -->



</div>



<script type="text/javascript">
    function editGuest(a) {
        $('#edit_amount').val($('#guest_pledge' + a).text());
        $('#edit_payment_date').val($('#guest_date' + a).text());
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        // $('#edit_service').val($('#guest_name'+a).text())
        $('#edit_descriptions').val($('#guest_note' + a).text());
        $('#ProfileStep5').attr('action', '<?= url('expense/edit/null') ?>');
        var option = $('<option></option>').attr("value", a).text($('#guest_name' + a).text());
        $("#edit_service").empty().append(option);
        $('#add_inputs').html('<input type="hidden" value="' + a + '" name="id"/>');
    }
    load_contact = function () {
        $.getJSON('https://www.google.com/m8/feeds/contacts/default/full/?access_token=' +
                authResult.access_token + "&alt=json&callback=?", function (result) {
                    console.log(JSON.stringify(result));
                });
    }
    //  $(document).ready(load_contact);
</script>
@endsection