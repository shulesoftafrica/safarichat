@extends('layouts.app')
@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h5>{{ __('Verify Your Phone Number') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3 text-center">
                        {{ __('We have sent a One-Time Password (OTP) to your mobile number:') }}<br>
                        <strong class="h5">{{ $phone ?? '+62 812-3456-7890' }}</strong>
                    </p>
                    <p class="text-muted small text-center">
                        {{ __('Please enter the 6-digit OTP you received via WhatsApp. Make sure your phone is connected to the internet and WhatsApp is running smoothly.') }}
                    </p>

                    @if(isset($message) && strlen($message)>5)
                        <div class="alert alert-danger text-center">
                            {{ $message }}
                        </div>
                    @endif

                    <form method="POST" action="{{ url('api/otpverify') }}">
                        @csrf

                        <div class="form-group mb-4">
                            <label for="otp" class="form-label">{{ __('OTP Code') }}</label>
                            <input id="otp" type="text" maxlength="6" class="form-control text-center @error('otp') is-invalid @enderror" name="otp" required autofocus pattern="\d{6}" placeholder="Enter 6-digit OTP">
                            @error('otp')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
<input type="hidden" name="email" value="{{ $phone }}">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            {{ __('Verify OTP') }}
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span id="resend-info" class="text-muted small">
                            {{ __('Didn\'t receive the code?') }}
                        </span>
                        <button id="resend-btn" class="btn btn-link p-0" style="display:none;" onclick="document.getElementById('resend-form').submit();">
                            {{ __('Resend OTP') }}
                        </button>
                        <span id="timer" class="text-danger small"></span>
                        <form id="resend-form" method="POST" action="{{ url('api/otp') }}" style="display:none;">
                            <input type="hidden" name="email" value="{{ $phone }}">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 3 minutes countdown
    let resendBtn = document.getElementById('resend-btn');
    let timerSpan = document.getElementById('timer');
    let resendForm = document.getElementById('resend-form');
    let countdown = 180; // seconds

    function updateTimer() {
        if (countdown > 0) {
            let min = Math.floor(countdown / 60);
            let sec = countdown % 60;
            timerSpan.textContent = ` (Resend available in ${min}:${sec.toString().padStart(2, '0')})`;
            resendBtn.style.display = 'none';
            countdown--;
            setTimeout(updateTimer, 1000);
        } else {
            timerSpan.textContent = '';
            resendBtn.style.display = 'inline';
        }
    }

    updateTimer();
</script>
@endsection
