@php
    $errors = $errors ?? session()->get('errors', new \Illuminate\Support\ViewErrorBag);
@endphp

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
                    <p class="text-muted small text-center">
                        {{ __('We have sent a One-Time Password (OTP) to your mobile number:') }}<br>
                     
                    </p>
                      <h5 align="center"> <strong class="h5">{{ $phone ?? '+62 812-3456-7890' }}</strong></h5>
                      <br/>
                    <p class="text-muted small text-center">
                        {{ __('Please enter the 6-digit OTP you received via WhatsApp. Make sure your phone is connected to the internet and WhatsApp is running smoothly.') }}
                    </p>

                    @if(isset($message) && strlen($message)>5)
                        <div class="alert alert-danger text-center">
                            {{ $message }}
                        </div>
                    @endif

                    <form method="POST" action="{{ url('setup/otpverify') }}">
                        @csrf

                        <div class="form-group mb-4">
                            <br/>
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

<style>
    /* OTP Verification Page Styles */
    .card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .card-header {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 1.5rem;
        border-radius: 12px 12px 0 0;
    }

    .card-header h5 {
        color: #1e293b;
        font-weight: 600;
        margin: 0;
    }

    .card-body {
        padding: 2rem;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1rem;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: 0.5rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    .btn-success {
        background: #10b981;
        border: none;
        border-radius: 8px;
        padding: 0.875rem;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .text-muted {
        color: #64748b !important;
    }

    /* Dark Mode Styles */
    .dark-mode .card {
        background: #2d3748 !important;
        border-color: #4a5568 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .dark-mode .card-header {
        background: #4a5568 !important;
        border-bottom-color: #4a5568 !important;
    }

    .dark-mode .card-header h5 {
        color: #f7fafc !important;
    }

    .dark-mode .card-body {
        background: #2d3748 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .card-body p,
    .dark-mode .card-body p.mb-3,
    .dark-mode .card-body p.text-center {
        color: #f7fafc !important;
        font-weight: 500 !important;
    }

    .dark-mode .card-body p.text-muted {
        color: #e2e8f0 !important;
        font-weight: 400 !important;
    }

    .dark-mode .card-body strong {
        color: #f7fafc !important;
    }

    .dark-mode .form-label {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }

    .dark-mode .form-control {
        background: #4a5568 !important;
        border-color: #718096 !important;
        color: #f7fafc !important;
    }

    .dark-mode .form-control::placeholder {
        color: #a0aec0 !important;
        opacity: 0.8 !important;
    }

    .dark-mode .form-control:focus {
        background: #4a5568 !important;
        border-color: #10b981 !important;
        box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25) !important;
        color: #f7fafc !important;
    }

    .dark-mode .text-muted {
        color: #cbd5e0 !important;
    }

    .dark-mode .text-danger {
        color: #f87171 !important;
    }

    .dark-mode .btn-success {
        background: #10b981 !important;
        color: white !important;
    }

    .dark-mode .btn-success:hover {
        background: #059669 !important;
        color: white !important;
    }

    .dark-mode .btn-link {
        color: #60a5fa !important;
    }

    .dark-mode .btn-link:hover {
        color: #93c5fd !important;
    }

    .dark-mode .alert-danger {
        background: rgba(239, 68, 68, 0.15) !important;
        border-color: #ef4444 !important;
        color: #fca5a5 !important;
    }

    .dark-mode .invalid-feedback {
        color: #fca5a5 !important;
    }

    /* Improved readability */
    .h5 {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .small {
        font-size: 0.875rem;
    }
</style>

@endsection
