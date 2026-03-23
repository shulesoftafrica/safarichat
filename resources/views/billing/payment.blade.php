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
                                    
                                    <button class="btn btn-outline-success w-100 payment-btn" data-method="ucn" data-bs-toggle="modal" data-bs-target="#ucnInstructionsModal" onclick="openPaymentModal()">
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
                    <i class="fas fa-university"></i> How to Pay with Control Number (Lipa Namba)
                </h5>
                <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="closePaymentModal(); return false;" style="font-size: 1.2rem;"></button>
            </div>
            <div class="modal-body">
                <!-- Tanzania Notice -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> <strong>Tanzania Only:</strong> Use your mobile money or bank account to pay instantly
                </div>
                
                @if($ucn)
                    <!-- UCN Display -->
                    <div class="text-center mb-3 p-3 bg-light rounded border border-success">
                        <p class="text-muted mb-1 small">Your Control Number (Lipa Namba)</p>
                        <h2 class="text-success fw-bold mb-2" id="ucnNumber">{{ $ucn }}</h2>
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-success" onclick="copyUCN('{{ $ucn }}')">
                                <i class="fas fa-copy"></i> Copy UCN
                            </button>
                            <span class="badge bg-light text-dark">Amount: TZS {{ number_format($amount ?? 0) }}</span>
                        </div>
                    </div>
                @endif
                
                <!-- Payment Tabs -->
                <ul class="nav nav-tabs mb-3" id="paymentMethodTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mpesa-tab" data-bs-toggle="tab" data-bs-target="#mpesa" type="button" role="tab">
                            <i class="fas fa-mobile-alt"></i> M-Pesa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tigopesa-tab" data-bs-toggle="tab" data-bs-target="#tigopesa" type="button" role="tab">
                            <i class="fas fa-mobile-alt"></i> Tigo Pesa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="airtel-tab" data-bs-toggle="tab" data-bs-target="#airtel" type="button" role="tab">
                            <i class="fas fa-mobile-alt"></i> Airtel Money
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="halopesa-tab" data-bs-toggle="tab" data-bs-target="#halopesa" type="button" role="tab">
                            <i class="fas fa-mobile-alt"></i> Halopesa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">
                            <i class="fas fa-university"></i> Bank
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="paymentMethodTabContent">
                    <!-- M-Pesa Instructions -->
                    <div class="tab-pane fade show active" id="mpesa" role="tabpanel">
                        <div class="payment-instructions">
                            <h6 class="text-success mb-3"><i class="fas fa-mobile-alt"></i> Pay with M-Pesa (Vodacom)</h6>
                            <div class="steps-container">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <strong>Dial USSD Code</strong>
                                        <p class="mb-0">Dial <span class="ussd-code">*150*00#</span> from your M-Pesa registered number</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <strong>Select Option</strong>
                                        <p class="mb-0">Choose <span class="badge bg-success text-white">4. Lipa kwa Simu</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <strong>Select Payment Type</strong>
                                        <p class="mb-0">Choose <span class="badge bg-primary text-white">4. Enter Business Number</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <strong>Enter Control Number</strong>
                                        <p class="mb-0">Enter: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">5</div>
                                    <div class="step-content">
                                        <strong>Confirm Amount</strong>
                                        <p class="mb-0">Verify amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">6</div>
                                    <div class="step-content">
                                        <strong>Enter PIN</strong>
                                        <p class="mb-0">Enter your M-Pesa PIN to complete payment</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tigo Pesa Instructions -->
                    <div class="tab-pane fade" id="tigopesa" role="tabpanel">
                        <div class="payment-instructions">
                            <h6 class="text-primary mb-3"><i class="fas fa-mobile-alt"></i> Pay with Tigo Pesa</h6>
                            <div class="steps-container">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <strong>Dial USSD Code</strong>
                                        <p class="mb-0">Dial <span class="ussd-code">*150*01#</span> from your Tigo Pesa registered number</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <strong>Select Payment Option</strong>
                                        <p class="mb-0">Choose <span class="badge bg-primary text-white">4. Make Payment</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <strong>Enter Control Number</strong>
                                        <p class="mb-0">Enter: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <strong>Confirm Details</strong>
                                        <p class="mb-0">Verify amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong> and merchant details</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">5</div>
                                    <div class="step-content">
                                        <strong>Enter PIN</strong>
                                        <p class="mb-0">Enter your Tigo Pesa PIN to authorize payment</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Airtel Money Instructions -->
                    <div class="tab-pane fade" id="airtel" role="tabpanel">
                        <div class="payment-instructions">
                            <h6 class="text-danger mb-3"><i class="fas fa-mobile-alt"></i> Pay with Airtel Money</h6>
                            <div class="steps-container">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <strong>Dial USSD Code</strong>
                                        <p class="mb-0">Dial <span class="ussd-code">*150*60#</span> from your Airtel Money registered number</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <strong>Select Lipa</strong>
                                        <p class="mb-0">Choose <span class="badge bg-danger text-white">5. Lipa</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <strong>Enter Control Number</strong>
                                        <p class="mb-0">Enter: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <strong>Verify Payment</strong>
                                        <p class="mb-0">Confirm amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">5</div>
                                    <div class="step-content">
                                        <strong>Enter PIN</strong>
                                        <p class="mb-0">Enter your Airtel Money PIN to complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Halopesa Instructions -->
                    <div class="tab-pane fade" id="halopesa" role="tabpanel">
                        <div class="payment-instructions">
                            <h6 class="text-warning mb-3"><i class="fas fa-mobile-alt"></i> Pay with Halopesa (Halotel)</h6>
                            <div class="steps-container">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <strong>Dial USSD Code</strong>
                                        <p class="mb-0">Dial <span class="ussd-code">*150*88#</span> from your Halopesa registered number</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <strong>Select Payment</strong>
                                        <p class="mb-0">Choose <span class="badge bg-warning text-dark">Lipa</span> option</p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <strong>Enter Control Number</strong>
                                        <p class="mb-0">Enter: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <strong>Confirm Amount</strong>
                                        <p class="mb-0">Verify: <strong>TZS {{ number_format($amount ?? 0) }}</strong></p>
                                    </div>
                                </div>
                                <div class="step-item">
                                    <div class="step-number">5</div>
                                    <div class="step-content">
                                        <strong>Enter PIN</strong>
                                        <p class="mb-0">Enter your Halopesa PIN to authorize</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Instructions -->
                    <div class="tab-pane fade" id="bank" role="tabpanel">
                        <div class="payment-instructions">
                            <h6 class="text-info mb-3"><i class="fas fa-university"></i> Pay via Bank</h6>
                            
                            <div class="mb-4">
                                <strong class="d-block mb-2">Internet Banking:</strong>
                                <div class="steps-container">
                                    <div class="step-item">
                                        <div class="step-number">1</div>
                                        <div class="step-content">
                                            <p class="mb-0">Login to your bank's internet/mobile banking</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">2</div>
                                        <div class="step-content">
                                            <p class="mb-0">Select "Bill Payments" or "Pay Bills"</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">3</div>
                                        <div class="step-content">
                                            <p class="mb-0">Enter Control Number: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">4</div>
                                        <div class="step-content">
                                            <p class="mb-0">Enter Amount: <strong>TZS {{ number_format($amount ?? 0) }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">5</div>
                                        <div class="step-content">
                                            <p class="mb-0">Confirm and complete payment</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <strong class="d-block mb-2">At Bank Branch or Agent:</strong>
                                <div class="steps-container">
                                    <div class="step-item">
                                        <div class="step-number">1</div>
                                        <div class="step-content">
                                            <p class="mb-0">Visit any bank branch or agent</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">2</div>
                                        <div class="step-content">
                                            <p class="mb-0">Provide Control Number: <span class="control-number-display">{{ $ucn ?? 'CONTROL_NUMBER' }}</span></p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">3</div>
                                        <div class="step-content">
                                            <p class="mb-0">Pay: <strong>TZS {{ number_format($amount ?? 0) }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">4</div>
                                        <div class="step-content">
                                            <p class="mb-0">Keep your receipt for reference</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Important Notes -->
                <div class="alert alert-warning mt-3 mb-0">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                        <div>
                            <strong>Important:</strong>
                            <ul class="mb-0 mt-1 small">
                                <li>Payment confirmation takes 5-15 minutes</li>
                                <li>Your subscription activates automatically after payment</li>
                                <li>Save your payment confirmation SMS/receipt</li>
                                <li>Contact support if not reflected within 24 hours</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal(); return false;">
                    <i class="fas fa-times-circle"></i> Close
                </button>
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
    border: 2px dashed #28a745 !important;
}

/* Payment Instructions Styles */
.steps-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.step-item {
    display: flex;
    align-items: start;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #28a745;
}

.step-number {
    min-width: 32px;
    height: 32px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content strong {
    display: block;
    color: #2c3e50;
    margin-bottom: 4px;
    font-size: 14px;
}

.step-content p {
    color: #6c757d;
    font-size: 13px;
    line-height: 1.5;
}

.ussd-code {
    background: #fff;
    padding: 4px 12px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #d63384;
    font-size: 16px;
}

.control-number-display {
    background: #fff3cd;
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid #ffc107;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #856404;
    font-size: 15px;
}

#paymentMethodTabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 14px;
    padding: 10px 15px;
}

#paymentMethodTabs .nav-link:hover {
    color: #495057;
    border-bottom-color: #dee2e6;
}

#paymentMethodTabs .nav-link.active {
    color: #28a745;
    border-bottom-color: #28a745;
    background: transparent;
}

@media (max-width: 768px) {
    #paymentMethodTabs .nav-link {
        font-size: 12px;
        padding: 8px 10px;
    }
    
    .step-item {
        padding: 10px;
    }
    
    .ussd-code, .control-number-display {
        font-size: 13px;
    }
}
</style>
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
    
    // Ensure Bootstrap modal functionality
    const ucnModal = document.getElementById('ucnInstructionsModal');
    if (ucnModal) {
        // Check if Bootstrap is loaded
        if (typeof bootstrap !== 'undefined') {
            console.log('Bootstrap modal initialized');
        } else {
            console.warn('Bootstrap not loaded');
        }
    }
});

function openPaymentModal() {
    const modalElement = document.getElementById('ucnInstructionsModal');
    if (modalElement) {
        // Try Bootstrap 5 first
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
        // Fallback to jQuery Bootstrap (Bootstrap 4)
        else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalElement).modal('show');
        }
        // Manual fallback
        else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'manualBackdrop';
            document.body.appendChild(backdrop);
            
            // Close on backdrop click
            backdrop.addEventListener('click', function() {
                closePaymentModal();
            });
        }
    }
    return false; // Prevent default button action
}

function closePaymentModal() {
    const modalElement = document.getElementById('ucnInstructionsModal');
    if (!modalElement) return;
    
    // Hide the modal
    modalElement.style.display = 'none';
    modalElement.classList.remove('show');
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    
    // Remove all modal backdrops
    const allBackdrops = document.querySelectorAll('.modal-backdrop');
    allBackdrops.forEach(backdrop => backdrop.remove());
    
    // Clear body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    return false; // Prevent default
}

function copyUCN(ucn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(ucn).then(function() {
            // Show success feedback
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-success');
            }, 2000);
        }, function(err) {
            console.error('Could not copy text: ', err);
            alert('Failed to copy. Please copy manually: ' + ucn);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = ucn;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Control number copied!');
        } catch (err) {
            alert('Please copy manually: ' + ucn);
        }
        document.body.removeChild(textArea);
    }
}

async function loadPlanFeatures(planCode) {
    try {
        const response = await fetch('{{ url("/api/billing/plans") }}', {
            headers: { 
                'Accept': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            }
        });
        const data = await response.json();
        
        if (data.success && data.data && data.data.plans && data.data.plans[planCode]) {
            const features = data.data.plans[planCode].features;
            const featuresHtml = [
                `<div class="col-md-6 mb-2"><i class="fas fa-users text-primary"></i> <strong class="text-dark">${features.max_contacts} contacts</strong></div>`,
                `<div class="col-md-6 mb-2"><i class="fas fa-box text-primary"></i> <strong class="text-dark">${features.max_products} products</strong></div>`,
                `<div class="col-md-6 mb-2"><i class="fab fa-whatsapp text-success"></i> <strong class="text-dark">${features.whatsapp_channels} WhatsApp channels</strong></div>`,
                `<div class="col-md-6 mb-2"><i class="fas fa-robot text-info"></i> <strong class="text-dark">${features.ai_credits.toLocaleString()} AI credits</strong></div>`
            ];
            
            if (features.customer_followups) {
                featuresHtml.push('<div class="col-md-6 mb-2"><i class="fas fa-user-clock text-warning"></i> <strong class="text-dark">Customer followups</strong></div>');
            }
            if (features.sales_reports) {
                featuresHtml.push('<div class="col-md-6 mb-2"><i class="fas fa-chart-line text-success"></i> <strong class="text-dark">Sales reports</strong></div>');
            }
            if (features.booking_calendars) {
                featuresHtml.push('<div class="col-md-6 mb-2"><i class="fas fa-calendar text-purple"></i> <strong class="text-dark">Booking calendars</strong></div>');
            }
            
            document.getElementById('planFeatures').innerHTML = featuresHtml.join('');
        }
    } catch (error) {
        console.error('Failed to load plan features:', error);
    }
}

// Add close button handler for manual modal
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab functionality
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get target tab pane
            const targetId = this.getAttribute('data-bs-target');
            
            if (!targetId) return;
            
            // Remove active class from all tabs and panes
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // Show target pane
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
});
</script>
@endsection