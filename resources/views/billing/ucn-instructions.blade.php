@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-university"></i> UCN (Lipa Namba) Payment Instructions</h4>
                </div>
                <div class="card-body">
                    <!-- Payment Details -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-tag"></i> Plan Upgrade:</h6>
                                <p class="mb-0"><strong>{{ ucfirst($payment_intent->plan_code) }} Plan</strong></p>
                                @if($payment_intent->feature)
                                    <small class="text-muted">Feature: {{ ucfirst($payment_intent->feature) }}</small>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <h6><i class="fas fa-money-bill"></i> Amount:</h6>
                                <p class="mb-0"><strong>TZS {{ number_format($payment_intent->amount) }}</strong></p>
                            </div>
                            <div class="col-md-3">
                                <h6><i class="fas fa-hashtag"></i> Reference:</h6>
                                <p class="mb-0 font-monospace"><strong>{{ $reference }}</strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="row">
                        <!-- Mobile Banking -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-mobile-alt"></i> UCN Mobile Money</h6>
                                </div>
                                <div class="card-body">
                                    <div class="payment-steps">
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">1</span>
                                            <div class="step-content">
                                                <strong>Open Your Mobile App Payment Menu (Eg *150*01#)</strong>
                                                <p class="text-muted mb-0">Launch your mobile money app</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">2</span>
                                            <div class="step-content">
                                                <strong>Go to Make Payments (TAN-QR)</strong>
                                                <p class="text-muted mb-0">Select "Pay Bills" or "Bill Payments" or "Lipa Kwa Simu"</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">3</span>
                                            <div class="step-content">
                                                <strong>Enter ucn (LIPA NAMBA) Details</strong>
                                                <div class="payment-details mt-2">
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>Biller:</strong></div>
                                                        <div class="col-sm-8">SafariChat</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>Reference (Lipa Namba):</strong></div>
                                                        <div class="col-sm-8 font-monospace">{{ $reference }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>Amount:</strong></div>
                                                        <div class="col-sm-8">TZS {{ number_format($payment_intent->amount) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="step">
                                            <span class="step-number bg-success">4</span>
                                            <div class="step-content">
                                                <strong>Complete Payment</strong>
                                                <p class="text-muted mb-0">Confirm and complete the payment</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-university"></i> Bank Transfer</h6>
                                </div>
                                <div class="card-body">
                                    <div class="payment-steps">
                                        <div class="step mb-3">
                                            <span class="step-number bg-primary">1</span>
                                            <div class="step-content">
                                                <strong>Login to Online Banking or Mobile App of your Bank</strong>
                                                <p class="text-muted mb-0">Or visit any Wakala/branch that support Lipa Namba Payments (TAN-QR)</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-primary">2</span>
                                            <div class="step-content">
                                                <strong>Transfer to SafariChat Account</strong>
                                                <div class="payment-details mt-2">
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>Account Name:</strong></div>
                                                        <div class="col-sm-7">{{Auth::user()->name}}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>Account Number (Lipa Namba):</strong></div>
                                                        <div class="col-sm-7 font-monospace">{{ $reference }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>Bank/Channel:</strong></div>
                                                        <div class="col-sm-7">TAN-QR</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>Amount:</strong></div>
                                                        <div class="col-sm-7">TZS {{ number_format($payment_intent->amount) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="step">
                                            <span class="step-number bg-primary">3</span>
                                            <div class="step-content">
                                                <strong>Complete Payment</strong>
                                                <p class="text-muted">Confirm and complete the payment:</p>
                                                <!-- <div class="alert alert-light">
                                                    <code>{{ $reference }}</code>
                                                </div> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li>Payment processing may take 24-48 hours for bank transfers</li>
                            <li>Mobile banking payments are usually processed within 1-2 hours</li>
                            <li>Your subscription will be activated automatically once payment is confirmed</li>
                            <li>Keep your payment reference number: <strong>{{ $reference }}</strong></li>
                            <li>Contact support if payment is not reflected within 48 hours</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <div class="btn-group" role="group">
                            <button class="btn btn-primary" onclick="copyReference()">
                                <i class="fas fa-copy"></i> Copy Reference
                            </button>
                            <a href="{{ route('ai-agents.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to AI Agents
                            </a>
                            <a href="{{ route('billing.payment', ['plan_code' => $payment_intent->plan_code, 'amount' => $payment_intent->amount, 'feature' => $payment_intent->feature]) }}" class="btn btn-outline-primary">
                                <i class="fas fa-credit-card"></i> Try Another Payment Method
                            </a>
                            <a href="{{ route('billing.payment', ['plan_code' => $payment_intent->plan_code, 'amount' => $payment_intent->amount, 'feature' => $payment_intent->feature]) }}" class="btn btn-success">
                                <i class="fas fa-arrow-right"></i> Complete & Confirm Payment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-steps .step {
    display: flex;
    align-items: flex-start;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    color: white;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content {
    flex-grow: 1;
}

.payment-details .row {
    margin-bottom: 5px;
}

.payment-details .row:last-child {
    margin-bottom: 0;
}
</style>

<script>
function copyReference() {
    const reference = '{{ $reference }}';
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(reference).then(() => {
            showToast('Reference number copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopyReference(reference);
        });
    } else {
        fallbackCopyReference(reference);
    }
}

function fallbackCopyReference(reference) {
    const textArea = document.createElement('textarea');
    textArea.value = reference;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('Reference number copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy reference number', 'error');
    }
    
    document.body.removeChild(textArea);
}

function showToast(message, type) {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}
</script>
@endsection