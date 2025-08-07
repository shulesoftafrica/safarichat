@extends('layouts.app')

@section('content')
<!--Form Wizard-->
<link rel="stylesheet" href="{{ asset('plugins/jquery-steps/jquery.steps.css')}}">
<!-- Top Bar Start -->
<div class="topbar">           
    <!-- Navbar -->
    <nav class="navbar-custom" style="margin-left:0 !important; cursor: pointer;" onclick="window.location.href='<?= url('/') ?>'">    
        <img src="{{ asset('assets/images/dikodiko.png')}}" alt="" height="420" width="420" class="thumb-sm" style="
             margin-left: 10px;
             margin-top: 5px;
             ">
        <span style="
              font-size: 19px;
              font-weight: bolder;
              font-style: italic;
              "><i>DikoDiko</i></span>
    </nav>
    <!-- end navbar-->
</div>
<!-- Top Bar End -->
<div class="page-content-tab">

    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?= request('p') == null ? 'active' : '' ?> " data-toggle="tab" href="#home" role="tab" aria-selected="true">Personal Account</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link  <?= request('p') != null ? 'active' : '' ?> " data-toggle="tab" href="#profile" role="tab" aria-selected="false">Business Account</a>
                            </li>                                                

                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane p-3  <?= request('p') == null ? 'active' : '' ?>" id="home" role="tabpanel">
                                <p class="mb-0 text-muted">
                                    If you have a personal event, register your account here...
                                </p>
                                <div class="col-md-8">
                                    <div class="card">

                                        <div class="card-body">
                                            <form method="POST" action="{{ route('register') }}">
                                                @csrf

                                                <div class="form-group row">
                                                    <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('Name') }}</label>

                                                    <div class="col-md-6">
                                                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                                        @error('name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                                                    <div class="col-md-6">
                                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                                        @error('email')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Phone Number') }}</label>

                                                    <div class="col-md-6">
                                                        <input id="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required autocomplete="phone">

                                                        @error('phone')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                                                    <div class="col-md-6">
                                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                                        @error('password')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>

                                                    <div class="col-md-6">
                                                        <input id="bpassword-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-0">
                                                    <div class="col-md-6 offset-md-4">
                                                        <input type="hidden" name="user_type_id" value="1"/>
                                                        <button type="submit" class="btn btn-primary">
                                                            {{ __('Register') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane p-3 <?= request('p') != null ? 'active' : '' ?>" id="profile" role="tabpanel">
                                <p class="mb-0 text-muted ">
                                    <b>Register a business account for users to find your page easily</b>
                                </p>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h4 class="mt-0 header-title">Please Follow 3 Basic Steps</h4>
                                                <p class="text-muted mb-3">You can move forward and back easily to ajust information</p>

                                                <form id="form-vertical" class="form-horizontal form-wizard-wrapper"  action="{{ route('register') }}" method="post">                                        
                                                    <h3>Business Details</h3>
                                                    <fieldset>   
                                                        <div class="col-lg-12">
                                                            <div class="form-group row">
                                                                <label for="example-text-input" class="col-sm-2 col-form-label text-right"> Name</label>
                                                                <div class="col-sm-10">
                                                                    <input class="form-control  @error('business_name') is-invalid @enderror" required="" type="text" name="business_name" value="<?= old('business_name') ?>" id="example-text-input">
                                                                    @error('business_name')
                                                                    <span class="invalid-feedback" role="alert">
                                                                        <strong>{{ $message }}</strong>
                                                                    </span>
                                                                    @enderror
                                                                    <span class="hint">Your Valid Business Name or Common name that people identify you</span>
                                                                </div>
                                                            </div>

                                                            <div class="form-group row">
                                                                <label for="example-email-input" class="col-sm-2 col-form-label text-right">Phone</label>
                                                                <div class="col-sm-10">
                                                                    <input class="form-control   @error('business_phone') is-invalid @enderror" type="tel" name="business_phone" value="<?= old('business_phone') ?>" id="example-email-input">
                                                                    @error('business_phone')
                                                                    <span class="invalid-feedback" role="alert">
                                                                        <strong>{{ $message }}</strong>
                                                                    </span>
                                                                    @enderror
                                                                </div>
                                                            </div> 
                                                            <div class="form-group row">
                                                                <label for="example-tel-input" class="col-sm-2 col-form-label text-right"></label>
                                                                <div class="col-sm-10">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="username">Region</label>
                                                                                <select class="form-control" id="region_id">
                                                                                    <option>Select</option>
                                                                                    <?php
                                                                                    $regions = \App\Models\Region::all();
                                                                                    foreach ($regions as $region) {
                                                                                        ?>
                                                                                        <option value="<?= $region->id ?>"><?= $region->name ?></option>
                                                                                    <?php }
                                                                                    ?>

                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="useremail">District</label>
                                                                                <select class="form-control" id="district_id">
                                                                                    <option>Select</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="subject">Ward</label>
                                                                                <select class="form-control" id="ward_id" name="ward_id">
                                                                                    <option>Select</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
<!--                                                                    <input class="form-control   @error('address') is-invalid @enderror" type="text" name="address" value="<?= old('address') ?>" id="address">
                                                                    @error('address')
                                                                    <span class="invalid-feedback" role="alert">
                                                                        <strong>{{ $message }}</strong>
                                                                    </span>
                                                                    @enderror-->
                                                                </div>
                                                            </div>
                                                            <!--                                                            <div class="form-group row">
                                                                                                                            <label class="col-sm-2 col-form-label text-right">Is Registered?</label>
                                                                                                                            <div class="col-sm-10">
                                                                                                                                <select class="form-control" id="registration_status">
                                                                                                                                    <option>Select</option>
                                                                                                                                    <option value="1">YES</option>
                                                                                                                                    <option value="0">NO</option>
                                                                                                                                </select>
                                                                                                                            </div>
                                                                                                                        </div>
                                                            
                                                                                                                        <div class="form-group row" id="file_upload" style="display:none">
                                                                                                                            <label for="example-text-input-lg" class="col-sm-2 col-form-label text-right">TAX Number</label>
                                                                                                                            <div class="col-sm-10">
                                                                                                                                <input class="form-control   @error('legal_document') is-invalid @enderror" type="text" name="legal_document" value="<?= old('legal_document') ?>" id="example-tel-input">
                                                            
                                                                                                                                @error('legal_document')
                                                                                                                                <span class="invalid-feedback" role="alert">
                                                                                                                                    <strong>{{ $message }}</strong>
                                                                                                                                </span>
                                                                                                                                @enderror
                                                                                                                            </div>
                                                                                                                        </div> -->
                                                            <div class="form-group row">
                                                                <label class="col-sm-2 col-form-label text-right">Major Service</label>
                                                                <div class="col-sm-10">
                                                                    <select class="form-control" id="services" name="service_id">
                                                                        <option>Select</option>
                                                                        <?php
                                                                        $services = \App\Models\Service::all();
                                                                        foreach ($services as $service) {
                                                                            ?>
                                                                            <option value="<?= $service->id ?>"><?= $service->name ?></option>
                                                                        <?php }
                                                                        ?>

                                                                    </select>
                                                                    <span>You will be able to add more services in your profile page</span>
                                                                </div>
                                                            </div>
                                                        </div>                                                                              
                                                    </fieldset><!--end fieldset--> 

                                                    <h3>Key Contact Personel</h3>
                                                    <fieldset>
                                                        @csrf

                                                        <div class="form-group row">
                                                            <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('Name') }}</label>

                                                            <div class="col-md-6">
                                                                <input id="bname" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                                                @error('name')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                                @enderror
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                                                            <div class="col-md-6">
                                                                <input id="business_email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                                                @error('email')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Phone Number') }}</label>

                                                            <div class="col-md-6">
                                                                <input id="business_phone" type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required autocomplete="phone">

                                                                @error('phone')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                                @enderror
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                                                            <div class="col-md-6">
                                                                <input id="password2" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                                                @error('password')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                                @enderror
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>

                                                            <div class="col-md-6">
                                                                <input id="bpassword-confirm2" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                                            </div>
                                                        </div>

                                                    </fieldset><!--end fieldset--> 
                                                    <h3>Confirm Details</h3>
                                                    <fieldset>

                                                        <div class="card-body"> 
                                                            <h4 class="header-title mt-0 mb-3">By Clicking Finish, it will imply You have Accepted </h4>
                                                            <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 722.4px;"><div class="slimscroll activity-scroll" style="overflow: hidden; width: auto; height: 722.4px;">
                                                                    <div class="activity">
                                                                        <div class="activity-info">
                                                                            <div class="icon-info-activity">
                                                                                <i class="las la-check-circle bg-soft-primary"></i>
                                                                            </div>

                                                                            <div class="activity-info-text">
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <h6 class="m-0 w-75">Our Terms of Service</h6>
                                                                                </div>
                                                                                <a href="<?= url('terms') ?>" class="text-muted mt-3">
                                                                                    Terms of Service Guide us how we operate our business.

                                                                                </a>
                                                                            </div>

                                                                        </div> 

                                                                        <div class="activity-info">
                                                                            <div class="icon-info-activity">
                                                                                <i class="las la-user-clock bg-soft-primary"></i>
                                                                            </div>
                                                                            <div class="activity-info-text">
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <h6 class="m-0  w-75">Our Terms of Use</h6>
                                                                                </div>
                                                                                <a href="<?= url('terms/use') ?>" class="text-muted mt-3">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration.
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                        <div class="activity-info">
                                                                            <div class="icon-info-activity">
                                                                                <i class="las la-clipboard-check bg-soft-primary"></i>
                                                                            </div>
                                                                            <div class="activity-info-text">
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <h6 class="m-0  w-75">Our Privacy Policy</h6>
                                                                                </div> 
                                                                                <a href="<?= url('privacy') ?>" class="text-muted mt-3">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration.
                                                                                </a>
                                                                            </div>
                                                                        </div>   


                                                                    </div><!--end activity-->
                                                                </div>
                                                                <div class="slimScrollBar" style="background: rgba(162, 177, 208, 0.13); width: 7px; position: absolute; top: 0px; opacity: 1; display: none; border-radius: 7px; z-index: 99; right: 1px; height: 620px;"></div><div class="slimScrollRail" style="width: 7px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; background: rgb(51, 51, 51); opacity: 0.2; z-index: 90; right: 1px;"></div></div><!--end activity-scroll-->
                                                        </div>
                                                    </fieldset><!--end fieldset-->   
                                                    <input type="hidden" name="user_type_id" value="2"/>
                                                </form><!--end form-->
                                            </div><!--end card-body-->
                                        </div><!--end card-->
                                    </div><!--end col-->
                                </div><!--end row-->
                            </div>                                                

                        </div>        
                    </div><!--end card-body-->
                </div><!--end card-->
            </div><!--end col-->


        </div>


    </div>
</div>
@section('footer')
<script src="{{ asset('plugins/jquery-steps/jquery.steps.min.js')}}"></script>
<script src="{{ asset('assets/pages/jquery.form-wizard.init.js')}}"></script>
<script src="{{ asset('assets/js/jquery.core.js')}}"></script>
<script type="text/javascript">

submit_form = function () {
    $("a[href='#finish']").mousedown(function () {
        $('.form-wizard-wrapper').submit();
    });
}
checks = function () {
    $('#registration_status').change(function () {
        var val = $(this).val();
        if (val == '1') {
            $('#file_upload').show();
            $('#customFileLang').attr('required', 'required');
        } else {
            $('#file_upload').hide();
        }
    });

    $('#region_id').change(function () {
        var types = $(this).val();
        if (types === '0') {
            $('#load_types').val(0);
        } else {
            $.ajax({
                type: 'POST',
                url: "<?= url('api/getDistrict') ?>",
                data: "region_id=" + types,
                dataType: "html",
                success: function (data) {
                    $('#district_id').html(data);
                }
            });
        }
    });
    $('#district_id').change(function () {
        var types = $(this).val();
        if (types === '0') {
            $('#load_types').val(0);
        } else {
            $.ajax({
                type: 'POST',
                url: "<?= url('api/getWard') ?>",
                data: "district_id=" + types,
                dataType: "html",
                success: function (data) {
                    $('#ward_id').html(data);
                }
            });
        }
    });
}
$(document).ready(submit_form);
$(document).ready(checks);
</script>
@endsection
@endsection
