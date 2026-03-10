@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-university"></i> {{ __("billing.page_titles.ucn_instructions") }}</h4>
                </div>
                <div class="card-body">
                    <!-- Payment Details -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-tag"></i> {{ __("billing.plan.upgrade") }}:</h6>
                                <p class="mb-0"><strong>{{ ucfirst($payment_intent->plan_code) }} {{ __("billing.plan.label") }}</strong></p>
                                @if($payment_intent->feature)
                                    <small class="text-muted">{{ __("billing.plan.feature") }} {{ ucfirst($payment_intent->feature) }}</small>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <h6><i class="fas fa-money-bill"></i> {{ __("billing.amount.label") }}</h6>
                                <p class="mb-0"><strong>{{ __("billing.amount.currency") }} {{ number_format($payment_intent->amount) }}</strong></p>
                            </div>
                            <div class="col-md-3">
                                <h6><i class="fas fa-hashtag"></i> {{ __("billing.reference.label") }}</h6>
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
                                    <h6 class="mb-0"><i class="fas fa-mobile-alt"></i> {{ __("billing.payment_methods.ucn.mobile_money") }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="payment-steps">
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">1</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.mobile_steps.step1_title") }}</strong>
                                                <p class="text-muted mb-0">{{ __("billing.ucn_instructions.mobile_steps.step1_description") }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">2</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.mobile_steps.step2_title") }}</strong>
                                                <p class="text-muted mb-0">{{ __("billing.ucn_instructions.mobile_steps.step2_description") }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-success">3</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.mobile_steps.step3_title") }}</strong>
                                                <div class="payment-details mt-2">
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>{{ __("billing.ucn_instructions.payment_details.biller") }}</strong></div>
                                                        <div class="col-sm-8">{{ __("billing.ucn_instructions.payment_details.safarichat") }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>{{ __("billing.reference.lipa_namba") }}</strong></div>
                                                        <div class="col-sm-8 font-monospace">{{ $reference }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4"><strong>{{ __("billing.amount.label") }}</strong></div>
                                                        <div class="col-sm-8">{{ __("billing.amount.currency") }} {{ number_format($payment_intent->amount) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="step">
                                            <span class="step-number bg-success">4</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.mobile_steps.step4_title") }}</strong>
                                                <p class="text-muted mb-0">{{ __("billing.ucn_instructions.mobile_steps.step4_description") }}</p>
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
                                    <h6 class="mb-0"><i class="fas fa-university"></i> {{ __("billing.payment_methods.ucn.bank_transfer") }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="payment-steps">
                                        <div class="step mb-3">
                                            <span class="step-number bg-primary">1</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.bank_steps.step1_title") }}</strong>
                                                <p class="text-muted mb-0">{{ __("billing.ucn_instructions.bank_steps.step1_description") }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="step mb-3">
                                            <span class="step-number bg-primary">2</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.bank_steps.step2_title") }}</strong>
                                                <div class="payment-details mt-2">
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>{{ __("billing.ucn_instructions.payment_details.account_name") }}</strong></div>
                                                        <div class="col-sm-7">{{Auth::user()->name}}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>{{ __("billing.ucn_instructions.payment_details.account_number") }}</strong></div>
                                                        <div class="col-sm-7 font-monospace">{{ $reference }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>{{ __("billing.ucn_instructions.payment_details.bank_channel") }}</strong></div>
                                                        <div class="col-sm-7">{{ __("billing.ucn_instructions.payment_details.tan_qr") }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-5"><strong>{{ __("billing.amount.label") }}</strong></div>
                                                        <div class="col-sm-7">{{ __("billing.amount.currency") }} {{ number_format($payment_intent->amount) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="step">
                                            <span class="step-number bg-primary">3</span>
                                            <div class="step-content">
                                                <strong>{{ __("billing.ucn_instructions.bank_steps.step3_title") }}</strong>
                                                <p class="text-muted">{{ __("billing.ucn_instructions.bank_steps.step3_description") }}</p>
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
                        <h6><i class="fas fa-exclamation-triangle"></i> {{ __("billing.ucn_instructions.important_notes.title") }}</h6>
                        <ul class="mb-0">
                            <li>{{ __("billing.ucn_instructions.important_notes.tanzania_only") }}</li>
                            <li>{{ __("billing.ucn_instructions.important_notes.cash_deposit") }}</li>
                            <li>{{ __("billing.ucn_instructions.important_notes.bank_support") }}</li>
                            <li>{{ __("billing.ucn_instructions.important_notes.auto_activation") }}</li>
                            <li>{{ __("billing.reference.keep") }} <strong>{{ $reference }}</strong></li>
                            <li>{{ __("billing.ucn_instructions.important_notes.support_contact") }}</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <div class="btn-group" role="group">
                            <button class="btn btn-primary" onclick="copyReference()">
                                <i class="fas fa-copy"></i> {{ __("billing.reference.copy") }}
                            </button>
                            <a href="{{ route('ai-agents.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> {{ __("billing.actions.back_to_agents") }}
                            </a>
                            <a href="{{ route('billing.payment', ['plan_code' => $payment_intent->plan_code, 'amount' => $payment_intent->amount, 'feature' => $payment_intent->feature]) }}" class="btn btn-outline-primary">
                                <i class="fas fa-credit-card"></i> {{ __("billing.actions.try_another_method") }}
                            </a>
                            <a href="{{ route('billing.payment', ['plan_code' => $payment_intent->plan_code, 'amount' => $payment_intent->amount, 'feature' => $payment_intent->feature]) }}" class="btn btn-success">
                                <i class="fas fa-arrow-right"></i> {{ __("billing.actions.complete_payment") }}
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