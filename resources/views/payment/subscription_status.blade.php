@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="mdi mdi-credit-card"></i> Subscription Status
                    </h4>
                </div>
                <div class="card-body">
                    @if($active_subscription)
                        <div class="alert alert-success">
                            <h5><i class="mdi mdi-check-circle"></i> Active Subscription</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Subscription End:</strong> {{ \Carbon\Carbon::parse($active_subscription->subscription_end)->format('d M Y H:i') }}</p>
                                    <p><strong>Days Remaining:</strong> {{ \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($active_subscription->subscription_end)) }} days</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Amount Paid:</strong> TSH {{ number_format($active_subscription->amount) }}</p>
                                    <p><strong>Payment Method:</strong> {{ $active_subscription->method }}</p>
                                </div>
                            </div>
                        </div>
                    @elseif($is_trial)
                        <div class="alert alert-info">
                            <h5><i class="mdi mdi-information"></i> Free Trial Active</h5>
                            <p><strong>Trial Days Remaining:</strong> {{ $trial_days_left }} out of {{ config('app.TRIAL_DAYS', 3) }} days</p>
                            <p><strong>Account Created:</strong> {{ $user->created_at->format('d M Y H:i') }}</p>
                            <hr>
                            <p class="mb-0">Your trial will expire soon. Subscribe now to continue using all features.</p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="mdi mdi-alert-circle"></i> Subscription Required</h5>
                            <p>Your trial period has ended. Please subscribe to continue using the service.</p>
                            <p><strong>Monthly Fee:</strong> TSH 50,000</p>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Subscription Plan</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="text-primary">Monthly Plan</h5>
                                    <h4 class="text-dark">TSH 50,000 <small class="text-muted">/ month</small></h4>
                                    <ul class="list-unstyled">
                                        <li><i class="mdi mdi-check text-success"></i> WhatsApp messaging</li>
                                        <li><i class="mdi mdi-check text-success"></i> Event management</li>
                                        <li><i class="mdi mdi-check text-success"></i> Guest management</li>
                                        <li><i class="mdi mdi-check text-success"></i> Message scheduling</li>
                                        <li><i class="mdi mdi-check text-success"></i> Analytics & reports</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Payment Instructions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-light">
                                        <h6><i class="mdi mdi-phone"></i> LIPA NAMBA Payment</h6>
                                        <p><strong>Send TSH 50,000 to:</strong> 000-111-222</p>
                                        <small class="text-muted">You will receive a reference number after payment</small>
                                    </div>
                                    
                                    @if(!$active_subscription)
                                        <button class="btn btn-primary btn-block" onclick="$('#payment_model').modal('show')">
                                            <i class="mdi mdi-credit-card"></i> Subscribe Now
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($recent_payments) > 0)
                        <div class="mt-4">
                            <h5>Recent Payments</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Reference</th>
                                            <th>Method</th>
                                            <th>Subscription Period</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_payments as $payment)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y') }}</td>
                                                <td>TSH {{ number_format($payment->amount) }}</td>
                                                <td>{{ $payment->transaction_id }}</td>
                                                <td>{{ $payment->method }}</td>
                                                <td>
                                                    @if($payment->subscription_start && $payment->subscription_end)
                                                        {{ \Carbon\Carbon::parse($payment->subscription_start)->format('d M Y') }} - 
                                                        {{ \Carbon\Carbon::parse($payment->subscription_end)->format('d M Y') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal (reuse from checkpayment.blade.php if needed) -->
<div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Monthly Subscription Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="mdi mdi-information"></i> Subscription Details</h6>
                    <p><strong>Monthly Fee:</strong> TSH 50,000 per month</p>
                    <p><strong>Payment Method:</strong> LIPA NAMBA (TANQR)</p>
                </div>

                <form id="paymentForm" action="{{ url('payment/verify') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Step 1: Send Payment</label>
                        <div class="alert alert-warning">
                            <i class="mdi mdi-phone"></i> Send <strong>TSH 50,000</strong> to LIPA NAMBA: <strong>000-111-222</strong>
                            <br><small class="text-muted">Please replace with your actual LIPA NAMBA number</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reference_number" class="font-weight-bold">Step 2: Enter Reference Number</label>
                        <input type="text" 
                               class="form-control" 
                               id="reference_number" 
                               name="reference_number" 
                               placeholder="Enter the transaction reference number from your payment"
                               required>
                        <small class="text-muted">You will receive this reference after sending money via LIPA NAMBA</small>
                    </div>

                    <div class="form-group">
                        <label for="amount_paid" class="font-weight-bold">Amount Paid (TSH)</label>
                        <input type="number" 
                               class="form-control" 
                               id="amount_paid" 
                               name="amount_paid" 
                               placeholder="50000"
                               min="1"
                               required>
                        <small class="text-muted">Enter the exact amount you sent</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="paymentForm" class="btn btn-success">
                    <i class="mdi mdi-check"></i> Verify Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var referenceNumber = $('#reference_number').val();
        var amountPaid = $('#amount_paid').val();
        
        if (!referenceNumber || !amountPaid) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Show loading state
        var submitBtn = $('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="mdi mdi-loading mdi-spin"></i> Verifying...').prop('disabled', true);
        
        // Submit form
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    alert('Payment verified successfully! Your subscription is now active.');
                    location.reload();
                } else {
                    alert('Payment verification failed: ' + response.message);
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
