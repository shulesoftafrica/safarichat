<?php 
$user = Auth::user();
$subscriptionService = app(\App\Services\SubscriptionService::class);
$isTrialActive = $subscriptionService->isTrialActive($user);
$isSubscriptionActive = $subscriptionService->isActive($user);
$trialEndsAt = $subscriptionService->getTrialEndDate($user);
$subscriptionEndsAt = $subscriptionService->getSubscriptionEndDate($user);
?>

@if(!$isTrialActive && !$isSubscriptionActive)
<!-- Payment Required Modal -->
<div class="modal fade" id="paymentRequiredModal" tabindex="-1" aria-labelledby="paymentRequiredModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="paymentRequiredModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Subscription Required
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Your trial period has ended</h6>
                    <p>To continue using SafariChat, please subscribe for TSH 50,000 per month.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-credit-card"></i> Payment Instructions</h6>
                            </div>
                            <div class="card-body">
                                <ol>
                                    <li>Send <strong>TSH 50,000</strong> to LIPA NAMBA</li>
                                    <li>LIPA NAMBA Number: <strong class="text-primary">000-111-222</strong></li>
                                    <li>Copy the reference number you receive</li>
                                    <li>Enter the reference number below</li>
                                    <li>Click "Verify Payment"</li>
                                </ol>
                                <div class="alert alert-warning mt-3">
                                    <small><i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> You must pay exactly TSH 50,000 or more. Payments less than TSH 50,000 will not activate your subscription.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-check-circle"></i> Verify Payment</h6>
                            </div>
                            <div class="card-body">
                                <form id="paymentVerificationForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="referenceNumber" class="form-label">Reference Number</label>
                                        <input type="text" class="form-control" id="referenceNumber" name="reference_number" required>
                                        <div class="form-text">Enter the reference number from LIPA NAMBA</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amountPaid" class="form-label">Amount Paid (TSH)</label>
                                        <input type="number" class="form-control" id="amountPaid" name="amount_paid" min="50000" required>
                                        <div class="form-text">Enter the amount you paid</div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check"></i> Verify Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-info" onclick="checkPaymentStatus()">
                        <i class="fas fa-refresh"></i> Check Payment Status
                    </button>
                    <small class="text-muted ms-2">Click if you've already paid but the modal is still showing</small>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted">
                    <small><i class="fas fa-clock"></i> Trial ended: {{ $trialEndsAt ? $trialEndsAt->format('M d, Y H:i') : 'N/A' }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-show payment modal when page loads
document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentRequiredModal'));
    paymentModal.show();
});

// Handle payment verification form
document.getElementById('paymentVerificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    submitBtn.disabled = true;
    
    fetch('{{ route('payment.verify') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Payment verification failed');
        }
    })
    .catch(error => {
        alert('Error verifying payment. Please try again.');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Check payment status function
async function checkPaymentStatus() {
    const btn = document.querySelector('[onclick="checkPaymentStatus()"]');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route('subscription.check-payment-status') }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.status === 'active') {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error checking payment status. Please try again.');
        console.error('Error:', error);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

@elseif($isTrialActive)
<!-- Trial Status Info (Optional - can be shown in a small banner) -->
<div class="alert alert-info alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-clock"></i> 
    <strong>Trial Active:</strong> Your trial period ends on {{ $trialEndsAt ? $trialEndsAt->format('M d, Y \a\t H:i') : 'N/A' }}. 
    <a href="#" class="alert-link" data-bs-toggle="modal" data-bs-target="#subscriptionInfoModal">Subscribe now</a> to avoid interruption.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Subscription Info Modal -->
<div class="modal fade" id="subscriptionInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscription Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Monthly Subscription:</strong> TSH 50,000</p>
                <p><strong>Payment Method:</strong> LIPA NAMBA to 000-111-222</p>
                <p><strong>Your trial ends:</strong> {{ $trialEndsAt ? $trialEndsAt->format('M d, Y \a\t H:i') : 'N/A' }}</p>
                <p>After your trial ends, you'll need to subscribe to continue using SafariChat.</p>
            </div>
        </div>
    </div>
</div>
@endif
