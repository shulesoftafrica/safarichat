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
                                        <span class="spinner-border spinner-border-sm"></span>
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
                                
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Enter Amount (TZS)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" class="form-control form-control-lg" id="stripeAmount" 
                                               placeholder="Enter amount" min="1000" step="1000"
                                               oninput="togglePaymentButton('stripe')">
                                    </div>
                                    <small class="text-muted">Minimum: TZS 1,000</small>
                                </div>

                                <button class="btn btn-primary btn-lg w-100" id="stripePayBtn" disabled onclick="processPayment('stripe')">
                                    <i class="fab fa-stripe"></i> Pay with Stripe
                                </button>
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
                                
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Enter Amount (TZS)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" class="form-control form-control-lg" id="flutterwaveAmount" 
                                               placeholder="Enter amount" min="1000" step="1000"
                                               oninput="togglePaymentButton('flutterwave')">
                                    </div>
                                    <small class="text-muted">Minimum: TZS 1,000</small>
                                </div>

                                <button class="btn-primary btn-lg w-100" id="flutterwavePayBtn" disabled onclick="processPayment('flutterwave')">
                                    <i class="fas fa-mobile-alt"></i> Pay with Flutterwave
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="mt-4 text-center">
                        <small class="text-muted d-block mb-2">Quick amounts:</small>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary" onclick="setAmount(5000)">TZS 5,000</button>
                            <button class="btn btn-outline-primary" onclick="setAmount(10000)">TZS 10,000</button>
                            <button class="btn btn-outline-primary" onclick="setAmount(25000)">TZS 25,000</button>
                            <button class="btn btn-outline-primary" onclick="setAmount(50000)">TZS 50,000</button>
                            <button class="btn btn-outline-primary" onclick="setAmount(100000)">TZS 100,000</button>
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
let ucnReference = null;

// Load wallet on page load
document.addEventListener('DOMContentLoaded', function() {
    fetchWalletInfo();
});

// Fetch wallet information
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

            // Update UCN reference
            if (data.data.ucn_reference) {
                ucnReference = data.data.ucn_reference;
                document.getElementById('ucnNumber').textContent = ucnReference;
                document.getElementById('copyUcnBtn').style.display = 'inline-block';
            } else {
                document.getElementById('ucnNumber').innerHTML = '<small>Loading...</small>';
            }
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

// Toggle payment button based on amount input
function togglePaymentButton(method) {
    const input = document.getElementById(method + 'Amount');
    const button = document.getElementById(method + 'PayBtn');
    const amount = parseInt(input.value);

    if (amount && amount >= 1000) {
        button.disabled = false;
        button.classList.remove('btn-secondary');
        button.classList.add('btn-primary');
    } else {
        button.disabled = true;
        button.classList.remove('btn-primary');
        button.classList.add('btn-secondary');
    }
}

// Set quick amount
function setAmount(amount) {
    document.getElementById('stripeAmount').value = amount;
    document.getElementById('flutterwaveAmount').value = amount;
    togglePaymentButton('stripe');
    togglePaymentButton('flutterwave');
}

// Copy UCN number
function copyUCN() {
    if (ucnReference) {
        navigator.clipboard.writeText(ucnReference).then(() => {
            toastr.success('UCN number copied to clipboard!');
        }).catch(() => {
            toastr.error('Failed to copy. Please copy manually.');
        });
    }
}

// Process payment
async function processPayment(method) {
    const button = document.getElementById(method + 'PayBtn');
    const amountInput = document.getElementById(method + 'Amount');
    const amount = parseInt(amountInput.value);

    if (!amount || amount < 1000) {
        toastr.error('Please enter a valid amount (minimum TZS 1,000)');
        return;
    }

    // Show loading
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

    try {
        const response = await fetch('{{ url("/api/billing/wallet/topup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                amount: amount,
                payment_method: method,
                wallet_type: 'ai_credits'
            })
        });

        const data = await response.json();

        if (data.success && data.data.payment_url) {
            // Redirect to payment gateway
            toastr.success('Redirecting to payment...');
            window.location.href = data.data.payment_url;
        } else if (data.success) {
            toastr.success(data.message || 'Top-up initiated successfully');
            fetchWalletInfo(); // Refresh balance
        } else {
            throw new Error(data.message || 'Payment failed');
        }
    } catch (error) {
        console.error('Payment error:', error);
        toastr.error(error.message || 'Failed to process payment');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}
</script>
@endsection
