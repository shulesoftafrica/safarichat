{{-- filepath: c:\xampp\htdocs\dikodiko\resources\views\payment\fullpaymentpage.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('home') }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('event') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('payment') }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ __('Payment Page') }}</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    @if($epayment_enabled)
                        {{-- Payment Methods View --}}
                        @include('payment.pay')
                        {{-- End Payment Methods View --}}
                    @else
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card border-info shadow-sm">
                                    <div class="card-header bg-info text-white">
                                        {{-- <h5 class="mb-0"><i class="fas fa-info-circle"></i> {{ __('E-Payment is currently unavailable.') }}</h5> --}}
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning mb-4">
                                            {{-- <p class="mb-1">
                                                {{ __('Please make your payment by transferring the required amount to the following bank account:') }}
                                            </p> --}}
                                            {{-- <ul class="mb-0">
                                                <li><strong>{{ __('Bank Name:') }}</strong> NMB Bank</li>
                                                <li><strong>{{ __('Account Name:') }}</strong> ShuleSoft Limited</li>
                                                <li><strong>{{ __('Account Number:') }}</strong> 22510077805</li>
                                            </ul>
                                            <div class="text-center my-2">
                                                <strong>{{ __('OR') }}</strong>
                                            </div> --}}
                                            <ul class="mb-0">
                                                {{-- <li><strong>{{ __('Bank Name:') }}</strong> CRDB BANK PLC</li>
                                                <li><strong>{{ __('Account Name:') }}</strong> ShuleSoft Limited</li> --}}
                                                <h3><strong>{{ __('Control Number:') }}</strong> {{ $booking->reference }}</h3>
                                            </ul>
                                        </div>
                                        <div class="card mb-4 border-primary">
                                            <div class="card-body py-2">
                                                <h6 class="mb-0">
                                                    <strong>{{ __('Amount to Pay:') }}</strong>
                                                    <span class="text-primary">{{ number_format($booking->amount, 2) }} {{ __('TZS') }}</span>
                                                </h6>
                                            </div>
                                        </div>
                                        {{-- <div class="alert alert-info">
                                            <i class="fas fa-envelope-open-text"></i>
                                            {{ __('After payment, you will receive a message. Please enter the transaction ID from the message you received.') }}
                                        </div> --}}

                                        <div class="alert alert-info">
                                            <h5>{{ __('How to Pay') }}</h5>
                                            <p>
                                                {{ __('You can pay using mobile money or banking by following the instructions below:') }}
                                            </p>
                                            {{-- Directly include payment instructions --}}
                                            @include('payment.pay', ['minimal' => true, 'booking' => $booking])
                                            <ul class="mt-3">
                                                <li>
                                                    {{ __('Follow the step-by-step instructions for your preferred payment method above.') }}
                                                </li>
                                                <li>
                                                    {{ __('After completing your payment, return to this page to get your receipt.') }}
                                                </li>
                                            </ul>
                                        </div>
                                        {{-- <form action="{{ url('payment/verifyPayment') }}" method="POST" class="mt-3"> --}}
                                        {{-- <form action="{{ url('payment/verifyPayment') }}" method="POST" class="mt-3">
                                            @csrf --}}
                                            {{-- <div class="form-group">
                                                <label for="transaction_id" class="font-weight-bold">{{ __('Transaction ID') }}</label>
                                                <input type="text" name="transaction_id" id="transaction_id" class="form-control" required placeholder="{{ __('Enter Transaction ID from payment message') }}">
                                            </div> --}}
                                            <a href="{{ url('message/transactions') }}" class="btn btn-success mt-2 w-100">
                                                
                                                <i class="fas fa-paper-plane"></i> {{ __('Get Your Receipt') }}
                                           
                                            </a>
                                           
                                            {{-- <div id="payment-result" class="mt-3"></div>
                                        </form> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <script>
                        $(function() {
                            $('form[action="{{ url('payment/verifyPayment') }}"]').on('submit', function(e) {
                                e.preventDefault();
                                var $form = $(this);
                                var $btn = $form.find('button[type="submit"]');
                                var $result = $('#payment-result');
                                $btn.prop('disabled', true);
                                $result.html('<span class="text-info">{{ __("Processing...") }}</span>');
                                $.ajax({
                                    url: $form.attr('action'),
                                    method: 'POST',
                                    data: $form.serialize(),
                                    headers: {'X-CSRF-TOKEN': $('input[name="_token"]').val()},
                                    success: function(response) {
                                        $result.html('<span class="alert alert-info text-center">' + (response.message || '{{ __("Payment verified successfully.") }}') + '</span>');
                                    },
                                    error: function(xhr) {
                                        let msg = '{{ __("An error occurred. Please try again.") }}';
                                        if (xhr.responseJSON && xhr.responseJSON.message) {
                                            msg = xhr.responseJSON.message;
                                        }
                                        $result.html('<span class="text-danger">' + msg + '</span>');
                                    },
                                    complete: function() {
                                        $btn.prop('disabled', false);
                                    }
                                });
                            });
                        });
                        </script>
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
