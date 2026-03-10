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
                                    <button class="btn btn-outline-success w-100 payment-btn" data-method="ucn">
                                        <i class="fas fa-arrow-right"></i> {{ __("billing.payment_methods.ucn.button") }}
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
                                    <button class="btn btn-outline-primary w-100 payment-btn" data-method="stripe">
                                        <i class="fas fa-credit-card"></i> {{ __("billing.payment_methods.stripe.button") }}
                                    </button>
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
                                    <button class="btn btn-outline-warning w-100 payment-btn" data-method="flutterwave">
                                        <i class="fas fa-mobile-alt"></i> {{ __("billing.payment_methods.flutterwave.button") }}
                                    </button>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentButtons = document.querySelectorAll('.payment-btn');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => m.classList.remove('selected'));
            // Add selected class to clicked method
            this.classList.add('selected');
        });
    });

    // Payment button handlers
    paymentButtons.forEach(button => {
        button.addEventListener('click', function() {
            const method = this.dataset.method;
            processPayment(method);
        });
    });

    // Load plan features
    loadPlanFeatures('{{ $plan_code }}');
});

async function processPayment(method) {
    const button = event.target;
    const originalContent = button.innerHTML;
    
    try {
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        button.disabled = true;

        // Process payment based on method
        const response = await fetch('{{ route("billing.process-payment") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                plan_code: '{{ $plan_code }}',
                amount: {{ $amount }},
                payment_method: method,
                feature: '{{ $feature }}'
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.payment_url) {
                // Redirect to external payment processor
                window.location.href = data.payment_url;
            } else if (data.redirect_url) {
                // Redirect to internal page
                window.location.href = data.redirect_url;
            } else {
                // Payment completed immediately
                window.location.href = '{{ route("ai-agents.index") }}?upgraded=1';
            }
        } else {
            throw new Error(data.message || 'Payment processing failed');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Payment failed: ' + error.message);
        button.innerHTML = originalContent;
        button.disabled = false;
    }
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