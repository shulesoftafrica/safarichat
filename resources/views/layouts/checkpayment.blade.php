<?php 
// NEW BILLING LOGIC - No more SubscriptionService calls
$user = Auth::user();
$businessId = $user->business?->id ?? $user->id; // Fallback to user ID if no business
?>

@if(!$user->subscription_status || $user->subscription_status === 'expired')
<!-- NEW BILLING MODAL - Using boot-once architecture -->
<div class="modal fade" id="billingRequiredModal" tabindex="-1" aria-labelledby="billingRequiredModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="billingRequiredModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Subscription Required
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Subscription Required</h6>
                    <p>To continue using SafariChat AI features, please subscribe for TSH 50,000 per month.</p>
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
                                <form id="newBillingVerificationForm">
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
                    <button type="button" class="btn btn-info" onclick="refreshBillingStatus()">
                        <i class="fas fa-refresh"></i> Check Status
                    </button>
                    <small class="text-muted ms-2">Click to refresh your subscription status</small>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted">
                    <small><i class="fas fa-clock"></i> Current Status: {{ ucfirst($user->subscription_status ?? 'No subscription') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// NEW BILLING JAVASCRIPT - Using boot-once architecture
document.addEventListener('DOMContentLoaded', function() {
    // Initialize billing cache
    const customerId = '{{ $businessId }}';
    
    // Auto-show billing modal when page loads if subscription required
    const billingModal = new bootstrap.Modal(document.getElementById('billingRequiredModal'));
    billingModal.show();
    
    // Handle new billing verification form
    document.getElementById('newBillingVerificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        submitBtn.disabled = true;
        
        // Use new billing API endpoint (to be implemented)
        fetch('{{ url("/api/billing/verify-payment") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Refresh billing cache and reload page
                refreshBillingStatus();
                setTimeout(() => location.reload(), 1000);
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
});

// Refresh billing status function
async function refreshBillingStatus() {
    const btn = document.querySelector('[onclick="refreshBillingStatus()"]');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;
    
    try {
        // Call new billing status endpoint
        const response = await fetch('{{ url("/api/billing/customers/") }}{{ $businessId }}/complete-status', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.data.subscription.active) {
            alert('Subscription is now active! Refreshing page...');
            location.reload();
        } else {
            alert('Subscription not yet active. Please verify your payment or contact support.');
        }
    } catch (error) {
        alert('Error checking status. Please try again.');
        console.error('Error:', error);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Initialize SafariChat billing boot process (when billing system is implemented)
// SafariChatApp.boot('{{ $businessId }}');
</script>

@elseif($user->subscription_status === 'trial')
<!-- Trial Status Info -->
<div class="alert alert-info alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-clock"></i> 
    <strong>Trial Active:</strong> Your trial is active. Subscribe to continue using all features after trial ends.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif