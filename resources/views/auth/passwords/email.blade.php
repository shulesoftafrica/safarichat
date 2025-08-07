@extends('layouts.app')

@section('content')
<div class="topbar">           
    <!-- Navbar -->
    <nav class="navbar-custom" style="margin-left:0 !important; cursor: pointer;" onclick="window.location.href = '<?= url('/') ?>'">    
        <img src="{{ asset(ROOT.'assets/images/dikodiko.png')}}" alt="" height="420" width="420" class="thumb-sm" style="
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
<div class="row">
    <div class="col-md-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @endif
                     @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ url('resetpassword/resetP') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Phone Number') }} *</label>

                            <div class="col-md-6">
                                <input id="phone2" type="tel" class="form-control @error('email') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required  autofocus>
  <span id="valid-msg2" class="hide">✓ Valid</span>
                                                        <span id="error-msg2" class="hide"></span>
                                                        <input type="hidden" value="" name="country_code" id="country_code2"/>
                                                        <input type="hidden" value="" name="country_name" id="country_name2"/>
                                                        <input type="hidden" value="" name="country_abbr" id="country_abbr2"/>
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                               
                            </div>

                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Send New Password') }}
                                </button>
                            </div>
                        </div>
                    </form>
<!--                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <p class="text-center" align="center">
                                  
                                <li class="text-sm py-1">
                                    Or &nbsp; &nbsp; <a target="_blank" href="https://api.whatsapp.com/send?phone=255734952586&text=login" style="color:#01E675"> <i class="fab fa-whatsapp fa-2x"></i> Reset via WhatsApp</a>
                                </li>
                                </p>
                            </div>
                        </div>-->
                </div>
            </div>
        </div>
    </div>

</div>
<link rel="stylesheet" href="https://intl-tel-input.com/node_modules/intl-tel-input/examples/css/prism.css">
<link rel="stylesheet" href="https://intl-tel-input.com/node_modules/intl-tel-input/build/css/intlTelInput.css?1613236686837">
<link rel="stylesheet" href="https://intl-tel-input.com/node_modules/intl-tel-input/examples/css/prism.css">
<link rel="stylesheet" href="https://intl-tel-input.com/node_modules/intl-tel-input/examples/css/isValidNumber.css?1613236686837">

<script src="https://intl-tel-input.com/node_modules/intl-tel-input/examples/js/prism.js"></script>
<script src="https://intl-tel-input.com/node_modules/intl-tel-input/build/js/intlTelInput.js?1613236686837"></script>


<script type="text/javascript">

      validate_phone2 = function () {
        var input = document.querySelector("#phone2"),
                errorMsg = document.querySelector("#error-msg2"),
                validMsg = document.querySelector("#valid-msg2");

// here, the index maps to the error code returned from getValidationError - see readme
        var errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

// initialise plugin
        var iti = window.intlTelInput(input, {
            utilsScript: "https://intl-tel-input.com/node_modules/intl-tel-input/build/js/utils.js?1613236686837",
            preferredCountries: ['tz'],
        });

        var reset = function () {
            input.classList.remove("error");
            errorMsg.innerHTML = "";
            errorMsg.classList.add("hide");
            validMsg.classList.add("hide");
        };
// on blur: validate
        input.addEventListener('blur', function () {
            reset();
            if (input.value.trim()) {
                if (iti.isValidNumber()) {
                    validMsg.classList.remove("hide");
                } else {
                    input.classList.add("error");
                    var errorCode = iti.getValidationError();
                    errorMsg.innerHTML = errorMap[errorCode];
                    errorMsg.classList.remove("hide");
                }
                var countryData = iti.getSelectedCountryData();
                // console.log(countryData);
                $("#country_code2").val(countryData.dialCode);
                $("#country_name2").val(countryData.name);
                $("#country_abbr2").val(countryData.iso2);
            }
        });

// on keyup / change flag: reset
        input.addEventListener('change', reset);
        input.addEventListener('keyup', reset);
    }
    $(document).ready(validate_phone2);
</script>

@endsection
