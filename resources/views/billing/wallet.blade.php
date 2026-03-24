@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-wallet"></i> {{ __("billing.page_titles.wallet") }}</h2>
                <a href="{{ url('/home/settings') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __("billing.actions.back_to_settings") }}
                </a>
            </div>

            <!-- Wallet Balance Card -->
            <div class="card shadow-lg mb-4" style="border-radius: 15px; border: none;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="wallet-icon me-4" style="font-size: 48px; color: #667eea;">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">{{ __("billing.wallet.available_credits") }}</h6>
                                    <h1 class="mb-0" style="font-size: 3rem; font-weight: 700; color: #667eea;" id="walletBalance">
                                        <span class="spinner-border spinner-border-sm" role="status"></span> {{ __("billing.wallet.loading") }}
                                    </h1>
                                    <!-- <small class="text-muted">1 Credit = 4 AI Tokens</small> -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                <i class="fas fa-check-circle"></i> {{ __("billing.wallet.active") }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Plan Section -->
            <div class="card shadow mb-4" style="border-radius: 15px; border:

 none;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><i class="fas fa-box"></i> {{ __("billing.plan.current_plan") }}</h5>
                            <h3 class="mb-0" style="color: #667eea;">{{ ucfirst($subscription_plan) }}</h3>
                            <small class="text-muted">
                                @if($subscription_expires_at)
                                    {{ __("billing.plan.expires") }} {{ \Carbon\Carbon::parse($subscription_expires_at)->format('M d, Y') }}
                                @else
                                    {{ __("billing.plan.trial_mode") }}
                                @endif
                            </small>
                        </div>
                        @if($subscription_plan !== 'premium')
                        <div>
                            <a href="{{ url('/home/settings#availablePlansSection') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-up"></i> {{ __("billing.plan.upgrade_button") }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Up Wallet Section -->
            <div class="card shadow-lg" style="border-radius: 15px; border: none;">
                <div class="card-header" style="background: var(--primary-color); border-radius: 15px 15px 0 0;">
                    <h4 class="text-white mb-0"><i class="fas fa-credit-card"></i> {{ __("billing.wallet.top_up") }}</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">{{ __("billing.wallet.top_up_description") }}</p>

                    <!-- Step 1: Enter Amount -->
                    <div id="amountSelectionSection" class="mb-4">
                        <div class="text-center" style="background: #f8faff; padding: 2rem; border-radius: 12px; border: 2px solid #e0e7ff;">
                            <h5 class="mb-3"><i class="fas fa-coins"></i> How much would you like to add?</h5>
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Enter Amount (TZS)</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" class="form-control" id="topupAmount" 
                                                   placeholder="Enter amount" min="1000" step="1000"
                                                   oninput="toggleGenerateButton()">
                                        </div>
                                        <small class="text-muted">Minimum: TZS 1,000</small>
                                    </div>
                                    <button class="btn btn-primary btn-lg px-5" id="generatePaymentBtn" disabled onclick="generatePaymentOptions()">
                                        <i class="fas fa-credit-card"></i> Generate Payment Options
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick Amount Buttons -->
                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">Quick amounts:</small>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary" onclick="setTopupAmount(5000)">TZS 5,000</button>
                                    <button class="btn btn-outline-primary" onclick="setTopupAmount(10000)">TZS 10,000</button>
                                    <button class="btn btn-outline-primary" onclick="setTopupAmount(25000)">TZS 25,000</button>
                                    <button class="btn btn-outline-primary" onclick="setTopupAmount(50000)">TZS 50,000</button>
                                    <button class="btn btn-outline-primary" onclick="setTopupAmount(100000)">TZS 100,000</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Payment Options (hidden until generated) -->
                    <div id="paymentOptionsSection" style="display: none;">
                    <!-- Payment Options in One Row -->
                    <!-- Step 2: Payment Options (hidden until generated) -->
                    <div id="paymentOptionsSection" style="display: none;">
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle"></i> Payment options generated for <strong id="selectedAmount">TZS 0</strong>. Choose your preferred payment method below.
                        </div>
                    <!-- Payment Options in One Row -->
                    <div class="row g-4">
                        <!-- UCN/ Lipa Namba -->
                        <div class="col-md-4">
                            <div class="payment-option-card h-100" style="border: 2px solid #e0e7ff; border-radius: 12px; padding: 2rem; text-align: center; background: #f8faff;">
                                <div class="payment-icon mb-3" style="font-size: 3rem; color: #10b981;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <h5 style="color: #1f2937; font-weight: 600;">{{ __("billing.payment_methods.ucn.name") }}</h5>
                                <p class="text-muted small mb-3">{{ __("billing.payment_methods.ucn.tanzania_only") }}</p>
                                
                                <div class="ucn-number-display mb-3" style="background: white; padding: 1.5rem; border-radius: 8px; border: 2px dashed #10b981;">
                                    <small class="d-block text-muted mb-2">{{ __("billing.wallet.send_payment_to") }}</small>
                                    <h3 class="mb-0" style="color: #10b981; font-weight: 700; font-family: monospace;" id="ucnNumber">
                                        ---
                                    </h3>
                                    <button class="btn btn-sm btn-outline-success mt-2" onclick="copyUCN()" id="copyUcnBtn" style="display: none;">
                                        <i class="fas fa-copy"></i> {{ __("billing.wallet.copy_number") }}
                                    </button>
                                </div>

                                <div class="alert alert-info" style="font-size: 0.85rem;">
                                    <i class="fas fa-info-circle"></i> {{ __("billing.wallet.top_up_instruction") }}
                                </div>
                            </div>
                        </div>

                        <!-- Stripe Payment -->
                        <div class="col-md-4">
                            <div class="payment-option-card h-100" style="border: 2px solid #e0e7ff; border-radius: 12px; padding: 2rem; text-align: center; background: #f8faff;">
                                <div class="payment-icon mb-3" style="font-size: 3rem; color: #6366f1;">
                                    <i class="fab fa-stripe"></i>
                                </div>
                                <h5 style="color: #1f2937; font-weight: 600;">Card Payment (Stripe)</h5>
                                <p class="text-muted small mb-3">Pay with credit/debit card</p>
                                
                                <div class="mb-3 p-3" style="background: white; border-radius: 8px;">
                                    <small class="text-muted d-block">Amount</small>
                                    <h4 class="mb-0" id="stripeAmountDisplay">TZS 0</h4>
                                </div>

                                <a href="#" class="btn btn-primary btn-lg w-100" id="stripePayBtn" target="_blank" style="pointer-events: none; opacity: 0.5;">
                                    <i class="fab fa-stripe"></i> Pay with Stripe
                                </a>
                            </div>
                        </div>

                        <!-- Flutterwave Payment -->
                        <div class="col-md-4">
                            <div class="payment-option-card h-100" style="border: 2px solid #e0e7ff; border-radius: 12px; padding: 2rem; text-align: center; background: #f8faff;">
                                <div class="payment-icon mb-3" style="font-size: 3rem; color: #f97316;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <h5 style="color: #1f2937; font-weight: 600;">Flutterwave Payment</h5>
                                <p class="text-muted small mb-3">Pay via Flutterwave Channels in your Country</p>
                                
                                <div class="mb-3 p-3" style="background: white; border-radius: 8px;">
                                    <small class="text-muted d-block">Amount</small>
                                    <h4 class="mb-0" id="flutterwaveAmountDisplay">TZS 0</h4>
                                </div>

                                <a href="#" class="btn btn-primary btn-lg w-100" id="flutterwavePayBtn" target="_blank" style="pointer-events: none; opacity: 0.5;">
                                    <i class="fas fa-mobile-alt"></i> Pay with Flutterwave
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Amount Button -->
                    <div class="mt-4 text-center">
                        <button class="btn btn-outline-secondary" onclick="resetPaymentOptions()">
                            <i class="fas fa-redo"></i> Change Amount
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-option-card {
    transition: all 0.3s ease;
}

.payment-option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.bg-gradient {
    position: relative;
    overflow: hidden;
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.05);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(-10px, 10px);
    }
}
</style>

<script>
let walletBalance = 0;
let paymentData = null;

// Load wallet on page load
document.addEventListener('DOMContentLoaded', function() {
    fetchWalletInfo();
});

// Fetch wallet information (balance only, no UCN generation)
async function fetchWalletInfo() {
    try {
        const response = await fetch('{{ url("/api/billing/wallet/info") }}', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Update balance
            walletBalance = data.data.balance || 0;
            document.getElementById('walletBalance').innerHTML = new Intl.NumberFormat().format(walletBalance);
        } else {
            document.getElementById('walletBalance').textContent = '0';
            toastr.error(data.message || 'Failed to load wallet information');
        }
    } catch (error) {
        console.error('Wallet fetch error:', error);
        document.getElementById('walletBalance').textContent = '0';
        toastr.error('Failed to connect to billing service');
    }
}

// Toggle generate payment button
function toggleGenerateButton() {
    const input = document.getElementById('topupAmount');
    const button = document.getElementById('generatePaymentBtn');
    const amount = parseInt(input.value);

    if (amount && amount >= 1000) {
        button.disabled = false;
    } else {
        button.disabled = true;
    }
}

// Set quick amount
function setTopupAmount(amount) {
    document.getElementById('topupAmount').value = amount;
    toggleGenerateButton();
}

// Generate payment options with specified amount
async function generatePaymentOptions() {
    const amountInput = document.getElementById('topupAmount');
    const amount = parseInt(amountInput.value);

    if (!amount || amount < 1000) {
        toastr.error('Please enter a valid amount (minimum TZS 1,000)');
        return;
    }

    // Show loading
    const button = document.getElementById('generatePaymentBtn');
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    try {
        const response = await fetch('{{ url("/api/billing/wallet/get-ucn") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ amount: amount })
        });

        const data = await response.json();

        if (data.success) {
            // Store payment data
            paymentData = data;
            
            // Hide amount selection, show payment options
            document.getElementById('amountSelectionSection').style.display = 'none';
            document.getElementById('paymentOptionsSection').style.display = 'block';
            
            // Display selected amount
            document.getElementById('selectedAmount').textContent = 'TZS ' + new Intl.NumberFormat().format(amount);
            
            // Display UCN
            if (data.ucn) {
                displayUCN(data.ucn);
            }
            
            // Update amount displays
            document.getElementById('stripeAmountDisplay').textContent = 'TZS ' + new Intl.NumberFormat().format(amount);
            document.getElementById('flutterwaveAmountDisplay').textContent = 'TZS ' + new Intl.NumberFormat().format(amount);
            
            // Enable and set payment links
            if (data.stripe_link) {
                const stripeBtn = document.getElementById('stripePayBtn');
                stripeBtn.href = data.stripe_link;
                stripeBtn.style.pointerEvents = 'auto';
                stripeBtn.style.opacity = '1';
            }
            
            if (data.flutterwave_link) {
                const flutterwaveBtn = document.getElementById('flutterwavePayBtn');
                flutterwaveBtn.href = data.flutterwave_link;
                flutterwaveBtn.style.pointerEvents = 'auto';
                flutterwaveBtn.style.opacity = '1';
            }
            
            toastr.success(data.message || 'Payment options generated successfully!');
        } else {
            throw new Error(data.message || 'Failed to generate payment options');
        }
    } catch (error) {
        console.error('Payment generation error:', error);
        toastr.error(error.message || 'Failed to generate payment options');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Reset payment options (go back to amount selection)
function resetPaymentOptions() {
    document.getElementById('paymentOptionsSection').style.display = 'none';
    document.getElementById('amountSelectionSection').style.display = 'block';
    
    // Reset UCN display
    document.getElementById('ucnNumber').textContent = '---';
    document.getElementById('copyUcnBtn').style.display = 'none';
    
    // Reset payment links
    const stripeBtn = document.getElementById('stripePayBtn');
    stripeBtn.href = '#';
    stripeBtn.style.pointerEvents = 'none';
    stripeBtn.style.opacity = '0.5';
    
    const flutterwaveBtn = document.getElementById('flutterwavePayBtn');
    flutterwaveBtn.href = '#';
    flutterwaveBtn.style.pointerEvents = 'none';
    flutterwaveBtn.style.opacity = '0.5';
    
    paymentData = null;
}

// Display UCN and show copy button
function displayUCN(ucn) {
    document.getElementById('ucnNumber').textContent = ucn;
    document.getElementById('copyUcnBtn').style.display = 'inline-block';
}

// Copy UCN number
function copyUCN() {
    if (paymentData && paymentData.ucn) {
        navigator.clipboard.writeText(paymentData.ucn).then(() => {
            toastr.success('UCN number copied to clipboard!');
        }).catch(() => {
            toastr.error('Failed to copy. Please copy manually.');
        });
    }
}
</script>
@endsection
