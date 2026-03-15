@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-credit-card"></i> {{ __("billing.page_titles.payment") }}</h4>
                </div>
                <div class="card-body">
                    <!-- Plan Summary -->
                    <div class="alert alert-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1">{{ ucfirst($plan_code) }} {{ __("billing.plan.label") }}</h5>
                                <p class="mb-0">{{ $feature ? __("billing.plan.requested_feature") . " " . ucfirst($feature) : __("billing.plan.full_upgrade") }}</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <h3 class="text-primary mb-0">{{ __("billing.amount.currency") }} {{ number_format($amount) }}</h3>
                                <small class="text-muted">{{ __("billing.amount.per_month") }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <h5 class="mb-3"><i class="fas fa-wallet"></i> {{ __("billing.payment_methods.choose") }}</h5>
                    
                    <div class="row">
                        <!-- UCN Payment -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 payment-method" data-method="ucn">
                                <div class="card-body text-center">
                                    <div class="payment-icon mb-3">
                                        <i class="fas fa-university fa-3x text-success"></i>
                                    </div>
                                    <h6 class="card-title">{{ __("billing.payment_methods.ucn.name") }}</h6>
                                    <p class="card-text text-muted small">{{ __("billing.payment_methods.ucn.description") }}</p>
                                    
                                    @if($ucn)
                                        <!-- Display UCN Number -->
                                        <div class="ucn-display my-3 p-3 bg-light rounded">
                                            <small class="text-muted d-block mb-1">Control Number (UCN)</small>
                                            <h4 class="text-success mb-0 fw-bold">{{ $ucn }}</h4>
                                        </div>
                                    @endif
                                    
                                    <button class="btn btn-outline-success w-100 payment-btn" data-method="ucn" data-bs-toggle="modal" data-bs-target="#ucnInstructionsModal">
                                        <i class="fas fa-info-circle"></i> {{ __("Show how to pay") }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Stripe Payment -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 payment-method" data-method="stripe">
                                <div class="card-body text-center">
                                    <div class="payment-icon mb-3">
                                        <i class="fab fa-stripe-s fa-3x text-primary"></i>
                                    </div>
                                    <h6 class="card-title">{{ __("billing.payment_methods.stripe.name") }}</h6>
                                    <p class="card-text text-muted small">{{ __("billing.payment_methods.stripe.description") }}</p>
                                    @if($stripe_link)
                                        <a href="{{ $stripe_link }}" class="btn btn-outline-primary w-100" target="_blank">
                                            <i class="fas fa-credit-card"></i> {{ __("billing.payment_methods.stripe.button") }}
                                        </a>
                                    @else
                                        <button class="btn btn-outline-secondary w-100" disabled>
                                            <i class="fas fa-credit-card"></i> {{ __("Not Available") }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Flutterwave Payment -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 payment-method" data-method="flutterwave">
                                <div class="card-body text-center">
                                    <div class="payment-icon mb-3">
                                        <i class="fas fa-mobile-alt fa-3x text-warning"></i>
                                    </div>
                                    <h6 class="card-title">{{ __("billing.payment_methods.flutterwave.name") }}</h6>
                                    <p class="card-text text-muted small">{{ __("billing.payment_methods.flutterwave.description") }}</p>
                                    @if($flutterwave_link)
                                        <a href="{{ $flutterwave_link }}" class="btn btn-outline-warning w-100" target="_blank">
                                            <i class="fas fa-mobile-alt"></i> {{ __("billing.payment_methods.flutterwave.button") }}
                                        </a>
                                    @else
                                        <button class="btn btn-outline-secondary w-100" disabled>
                                            <i class="fas fa-mobile-alt"></i> {{ __("Not Available") }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Features Preview -->
                    <div class="mt-4">
                        <h6><i class="fas fa-check-circle text-success"></i> {{ __("billing.features.title") }}</h6>
                        <div id="planFeatures" class="row">
                            <!-- Features will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-4 text-center">
                        <a href="{{ route('ai-agents.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __("billing.actions.back_to_agents") }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- UCN Payment Instructions Modal -->
<div class="modal fade" id="ucnInstructionsModal" tabindex="-1" aria-labelledby="ucnInstructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="ucnInstructionsModalLabel">
                    <i class="fas fa-university"></i> How to Pay Using Control Number (UCN)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tanzania Notice -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> This payment method is available <strong>strictly for Tanzania</strong> only.
                </div>
                
                @if($ucn)
                    <!-- UCN Display -->
                    <div class="text-center mb-4 p-4 bg-light rounded">
                        <p class="text-muted mb-2">Your Control Number</p>
                        <h2 class="text-success fw-bold mb-2">{{ $ucn }}</h2>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyUCN('{{ $ucn }}')">
                            <i class="fas fa-copy"></i> Copy Number
                        </button>
                    </div>
                @endif
                
                <div class="payment-instructions">
                    <h6 class="mb-3"><i class="fas fa-mobile-alt text-primary"></i> Mobile Banking</h6>
                    <div class="instruction-card p-3 mb-4 bg-light rounded">
                        <ol class="mb-0">
                            <li>Dial <strong>*150*01*{{ $ucn ?? 'YOUR_CONTROL_NUMBER' }}#</strong> from your registered mobile number</li>
                            <li>Follow the prompts on your phone</li>
                            <li>Enter your PIN to confirm payment</li>
                            <li>You will receive a confirmation SMS</li>
                        </ol>
                    </div>
                    
                    <h6 class="mb-3"><i class="fas fa-laptop text-primary"></i> Internet Banking</h6>
                    <div class="instruction-card p-3 mb-4 bg-light rounded">
                        <ol class="mb-0">
                            <li>Login to your bank's internet banking portal</li>
                            <li>Select "Pay Bills" or "Bill Payment"</li>
                            <li>Enter the control number: <strong>{{ $ucn ?? 'YOUR_CONTROL_NUMBER' }}</strong></li>
                            <li>Enter amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong></li>
                            <li>Confirm and complete the payment</li>
                        </ol>
                    </div>
                    
                    <h6 class="mb-3"><i class="fas fa-building text-primary"></i> Agent Banking / Bank Branch</h6>
                    <div class="instruction-card p-3 mb-4 bg-light rounded">
                        <ol class="mb-0">
                            <li>Visit any bank agent or bank branch</li>
                            <li>Provide the control number: <strong>{{ $ucn ?? 'YOUR_CONTROL_NUMBER' }}</strong></li>
                            <li>Pay the amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong></li>
                            <li>Keep your receipt for reference</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> <strong>Important:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Payment confirmation may take up to 15 minutes</li>
                            <li>Your subscription will be activated automatically once payment is confirmed</li>
                            <li>Keep your payment receipt/SMS for reference</li>
                            <li>Contact support if payment is not reflected within 24 hours</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.payment-method:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.payment-method.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.payment-icon {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ucn-display {
    border: 2px dashed #28a745;
}

.instruction-card {
    border-left: 4px solid #007bff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => m.classList.remove('selected'));
            // Add selected class to clicked method
            this.classList.add('selected');
        });
    });

    // Load plan features
    loadPlanFeatures('{{ $plan_code }}');
});

function copyUCN(ucn) {
    navigator.clipboard.writeText(ucn).then(function() {
        alert('Control number copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

async function loadPlanFeatures(planCode) {
    try {
        const response = await fetch('{{ url("/api/billing/plans") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await response.json();
        
        if (data.success && data.data && data.data.plans && data.data.plans[planCode]) {
            const features = data.data.plans[planCode].features;
            const featuresHtml = [
                `<div class="col-md-6"><i class="fas fa-users text-primary"></i> ${features.max_contacts} contacts</div>`,
                `<div class="col-md-6"><i class="fas fa-box text-primary"></i> ${features.max_products} products</div>`,
                `<div class="col-md-6"><i class="fab fa-whatsapp text-success"></i> ${features.whatsapp_channels} WhatsApp channels</div>`,
                `<div class="col-md-6"><i class="fas fa-robot text-info"></i> ${features.ai_credits.toLocaleString()} AI credits</div>`
            ];
            
            if (features.customer_followups) {
                featuresHtml.push('<div class="col-md-6"><i class="fas fa-follow text-warning"></i> Customer followups</div>');
            }
            if (features.sales_reports) {
                featuresHtml.push('<div class="col-md-6"><i class="fas fa-chart-line text-success"></i> Sales reports</div>');
            }
            if (features.booking_calendars) {
                featuresHtml.push('<div class="col-md-6"><i class="fas fa-calendar text-purple"></i> Booking calendars</div>');
            }
            
            document.getElementById('planFeatures').innerHTML = featuresHtml.join('');
        }
    } catch (error) {
        console.error('Failed to load plan features:', error);
    }
}
</script>
@endsection