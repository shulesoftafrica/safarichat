@php
// ─── BILLING GATE: load subscription data ────────────────────────────────────
$user = Auth::user();
$businessId = $user && $user->business ? $user->business->id : ($user ? $user->id : null);

// Load billing account (business-level, then user-level as fallback)
$billingAccount = null;
if ($user) {
    $billingAccount = $user->business
        ? $user->business->billingAccount
        : $user->billingAccount;
}

// ── Primary source: BillingAccount ──────────────────────────────────────────
$subscriptionStatus = $billingAccount ? ($billingAccount->subscription_status ?? 'inactive') : 'inactive';
$currentPlan        = $billingAccount ? ($billingAccount->subscription_plan  ?? 'trial')    : 'trial';
$expiresAt          = $billingAccount ? $billingAccount->subscription_expires_at             : null;
$aiCredits          = $billingAccount ? ($billingAccount->ai_credits ?? 0)                   : 0;

// ── Fallback: use User model fields when BillingAccount is absent/incomplete ──
if (!$expiresAt && $user && $user->trial_ends_at) {
    // Use trial_ends_at from the users table as the expiry source
    $expiresAt = $user->trial_ends_at;
}
if (!$billingAccount && $user && $user->subscription_status) {
    // Sync status from users table if no billing account exists
    $subscriptionStatus = $user->subscription_status ?? 'inactive';
}

// ── Expiry flags ──────────────────────────────────────────────────────────────
$isStillValid = $expiresAt && now()->lessThanOrEqualTo($expiresAt);

// Expired trial: plan=trial + expiresAt in the past (includes "active" status that was never updated)
$isTrialExpired = $currentPlan === 'trial' && $expiresAt && now()->greaterThan($expiresAt);

// Explicitly expired/cancelled paid plan
$isSubscriptionExpired = in_array($subscriptionStatus, ['expired', 'cancelled'])
    && (!$expiresAt || now()->greaterThan($expiresAt));

// Inactive or no billing account at all with no valid date
$isInactive = ($subscriptionStatus === 'inactive' || !$billingAccount) && !$isStillValid;

// Edge-case: BillingAccount exists as "active" but subscription_expires_at is in the past
// (system didn't mark it expired yet) — treat it like expired
$isExpiredActiveSubscription = $billingAccount
    && $subscriptionStatus === 'active'
    && $currentPlan !== 'trial'    // paid plan that silently lapsed
    && $expiresAt
    && now()->greaterThan($expiresAt);
if ($isExpiredActiveSubscription) {
    $isSubscriptionExpired = true;
}

// ── Modal copy ────────────────────────────────────────────────────────────────
$modalTitle   = 'Upgrade Required';
$modalIcon    = 'fa-crown';
$modalIconColor = '#667eea';
$defaultMessage = 'This feature is not included in your current subscription plan.';

if ($isTrialExpired) {
    $modalTitle     = 'Trial Period Ended';
    $modalIcon      = 'fa-clock';
    $modalIconColor = '#ff9800';
    $defaultMessage = 'Your free trial has ended. Please upgrade to continue using SafariChat features.';
}
if ($isSubscriptionExpired) {
    $modalTitle     = 'Subscription Expired';
    $modalIcon      = 'fa-exclamation-triangle';
    $modalIconColor = '#f44336';
    $defaultMessage = 'Your subscription has expired. Please renew to continue using SafariChat features.';
}
if ($isInactive) {
    $modalTitle     = 'Subscription Required';
    $modalIcon      = 'fa-lock';
    $modalIconColor = '#dc3545';
    $defaultMessage = 'You need an active subscription to access this feature.';
}
@endphp

@php
// Hard-block: subscription/trial is definitively expired or absent
$isHardBlock = $isTrialExpired || $isSubscriptionExpired || $isInactive;

// Pages where we must NOT overlay (user needs to reach billing to pay)
$isOnBillingPage = request()->routeIs('billing.*')
    || request()->is('billing/*')
    || request()->is('*/billing/*')
    || request()->is('payment/*')
    || str_contains(request()->url(), '/billing/')
    || str_contains(request()->url(), '/payment');
@endphp

@auth
@if($isHardBlock && !$isOnBillingPage)
{{-- ══════════════════════════════════════════════════════════════════════════
     SERVER-SIDE HARD-BLOCK OVERLAY
     Rendered synchronously — no JavaScript required to show this.
     Covers the entire viewport with a fixed overlay so users CANNOT interact
     with UI beneath it, regardless of JS loading state.
     ══════════════════════════════════════════════════════════════════════════ --}}
<div id="hardBlockOverlay" style="
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    z-index: 99999;
    background: rgba(10, 14, 26, 0.88);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
">
    <div style="
        background: #ffffff;
        border-radius: 16px;
        max-width: 540px;
        width: 95%;
        box-shadow: 0 24px 64px rgba(0,0,0,0.55);
        overflow: hidden;
        animation: slideInOverlay 0.35s ease-out;
    ">
        {{-- Header --}}
        <div style="
            background: linear-gradient(135deg, {{ $modalIconColor }} 0%, {{ $modalIconColor }}cc 100%);
            padding: 28px 24px;
            text-align: center;
            color: white;
        ">
            <i class="fas {{ $modalIcon }}" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.95;"></i>
            <h4 style="color: white; margin: 0; font-weight: 700; font-size: 1.4rem;">{{ $modalTitle }}</h4>
        </div>

        {{-- Body --}}
        <div style="padding: 28px 28px 20px; text-align: center;">
            <p style="color: #555; margin-bottom: 8px; font-size: 15px; line-height: 1.6;">
                {{ $defaultMessage }}
            </p>
            @if($expiresAt)
            <p style="color: #999; font-size: 13px; margin-bottom: 24px;">
                <i class="fas fa-calendar-times" style="color: #f44336;"></i>
                @if($isTrialExpired) Trial ended @elseif($isSubscriptionExpired) Expired @else Expired @endif
                on <strong>{{ $expiresAt->format('M d, Y') }}</strong>
            </p>
            @else
            <div style="margin-bottom: 24px;"></div>
            @endif

            {{-- Primary CTA: go to billing/payment page --}}
            <a href="{{ route('billing.payment') }}"
               style="
                   display: block;
                   background: linear-gradient(135deg, #667eea, #764ba2);
                   color: white;
                   padding: 14px 20px;
                   border-radius: 8px;
                   text-decoration: none;
                   font-weight: 600;
                   font-size: 16px;
                   margin-bottom: 12px;
                   transition: opacity 0.2s;
               "
               onmouseover="this.style.opacity='0.9'"
               onmouseout="this.style.opacity='1'"
            >
                <i class="fas fa-crown" style="margin-right: 8px;"></i> View Plans &amp; Subscribe
            </a>

            {{-- Secondary CTA: wallet top-up --}}
            <a href="{{ route('billing.wallet') }}"
               style="
                   display: block;
                   border: 2px solid #28a745;
                   color: #28a745;
                   padding: 12px 20px;
                   border-radius: 8px;
                   text-decoration: none;
                   font-weight: 600;
                   font-size: 15px;
                   transition: all 0.2s;
               "
               onmouseover="this.style.background='#28a745'; this.style.color='#fff';"
               onmouseout="this.style.background='transparent'; this.style.color='#28a745';"
            >
                <i class="fas fa-wallet" style="margin-right: 8px;"></i> Go to Wallet &amp; Top Up
            </a>
        </div>

        {{-- Footer --}}
        <div style="
            background: #f8f9fa;
            padding: 14px 24px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        ">
            <small style="color: #6c757d;">
                <i class="fas fa-lock" style="margin-right: 4px;"></i>
                SafariChat is locked until an active subscription is confirmed.
            </small>
        </div>
    </div>
</div>

<style>
@keyframes slideInOverlay {
    from { opacity: 0; transform: translateY(-30px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)   scale(1);    }
}
/* Dark mode: keep overlay card readable */
.dark-mode #hardBlockOverlay > div {
    background: #1e2535 !important;
}
.dark-mode #hardBlockOverlay p,
.dark-mode #hardBlockOverlay small {
    color: #cbd5e0 !important;
}
.dark-mode #hardBlockOverlay h4 {
    color: #ffffff !important;
}
</style>
@endif
@endauth

<!-- Pricing Controls Modal - Reusable across all pages -->
<div class="modal fade" id="pricingControlsModal" tabindex="-1" aria-labelledby="pricingControlsModalLabel" aria-hidden="true" data-bs-backdrop="{{ $isHardBlock ? 'static' : 'true' }}" data-bs-keyboard="{{ $isHardBlock ? 'false' : 'true' }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-brand); color: white;">
                <h5 class="modal-title" id="pricingControlsModalLabel">
                    <i class="fas {{ $modalIcon }}"></i> {{ $modalTitle }}
                </h5>
                <button type="button" class="close text-white {{ $isHardBlock ? 'd-none' : '' }}" data-dismiss="modal" aria-label="Close" id="modalCloseBtn">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="pricingControlsModalBody">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas {{ $modalIcon }} fa-3x" style="color: {{ $modalIconColor }};"></i>
                    </div>
                    <h5 id="modalContextTitle">{{ $modalTitle }}</h5>
                    <p class="text-muted" id="featureMessage">{{ $defaultMessage }}</p>
                </div>

                <!-- Current Plan Display -->
                <div class="card mb-4" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-user-tag"></i> Current Plan</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $currentPlan === 'trial' ? 'secondary' : ($currentPlan === 'starter' ? 'info' : ($currentPlan === 'pro' ? 'primary' : 'warning')) }}" id="currentPlanName">
                                    {{ ucfirst($currentPlan) }}{{ $currentPlan === 'trial' ? ' (Free)' : '' }} Plan
                                </span>
                                <small class="text-muted d-block" id="currentPlanDetails">
                                    Status: <strong class="text-{{ $subscriptionStatus === 'active' ? 'success' : 'danger' }}">{{ ucfirst($subscriptionStatus) }}</strong>
                                    @if($aiCredits > 0)
                                        • {{ number_format($aiCredits) }} AI Credits
                                    @endif
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">{{ $expiresAt && now()->lessThan($expiresAt) ? 'Expires' : 'Expired' }}</small>
                                <div id="currentPlanExpiry" class="fw-bold">
                                    @if($expiresAt)
                                        {{ $expiresAt->format('M d, Y') }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Plans -->
                <div class="row" id="availablePlans">
                    <!-- Plans will be loaded dynamically -->
                    <div class="col-12 text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading available plans...</p>
                    </div>
                </div>

                <!-- Credits Option -->
                <div class="card mt-4" style="border-left: 4px solid #28a745;">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-wallet"></i> Top Up Your Wallet</h6>
                        <p class="text-muted mb-3">Add credits to your wallet via UCN, Stripe, or Flutterwave</p>
                        <div class="text-center">
                            <a href="{{ route('billing.wallet') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-wallet"></i> Go to Wallet & Top Up
                            </a>
                            <p class="text-muted small mt-2">Manage your wallet balance and payment methods</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary {{ $isHardBlock ? 'd-none' : '' }}" data-dismiss="modal" id="modalCancelBtn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="proceedWithCurrentPlan()" style="display: none;" id="proceedButton">
                        <i class="fas fa-arrow-right"></i> Continue with Current Plan
                    </button>
                    @if($isHardBlock)
                    <div class="alert alert-warning mb-0 w-100 text-center">
                        <i class="fas fa-lock"></i> <strong>Subscription Required:</strong> You must select a plan to continue using SafariChat.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Controls JavaScript -->
<script>
    // Prevent duplicate script execution
    if (typeof window.pricingControlsInitialized === 'undefined') {
        window.pricingControlsInitialized = true;

        class PricingControls {
            constructor() {
                this.modal = null;
                this.currentSubscription = {
                    plan: '{{ $currentPlan }}',
                    status: '{{ $subscriptionStatus }}',
                    expires_at: '{{ $expiresAt ? $expiresAt->toISOString() : "" }}',
                    ai_credits: {{ $aiCredits }}
                };
                this.availablePlans = [];
                this.currentFeature = null;
            }

        async init() {
            // Don't initialize the modal yet - wait until showModal is called
            // This prevents errors when modal is initialized before DOM is fully ready
            
            // Just load available plans
            await this.loadAvailablePlans();
        }

        async loadCurrentSubscription() {
            // Already loaded from PHP, but can refresh if needed
            try {
                const response = await fetch('{{ url("/api/billing/status") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    this.currentSubscription = data.subscription;
                }
            } catch (error) {
                console.error('Error loading subscription:', error);
            }
        }

        async loadAvailablePlans() {
            console.log('🔵 [BILLING] Loading plans from API...');
            try {
                const response = await fetch('{{ url("/api/billing/plans") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                console.log('🔵 [BILLING] API Response:', data);
                
                if (data.success && data.data && data.data.plans) {
                    // Convert plans object to array format with proper feature descriptions
                    const plansObj = data.data.plans;
                    this.availablePlans = Object.keys(plansObj).map(code => ({
                        code: code,
                        name: plansObj[code].name,
                        price: plansObj[code].price,
                        currency: plansObj[code].currency || 'TZS',
                        features: this.convertFeaturesToDescription(plansObj[code].features)
                    }));
                    console.log('✅ [BILLING] Using API plans:', this.availablePlans);
                    this.renderAvailablePlans();
                } else {
                    console.warn('⚠️ [BILLING] Invalid API response, using fallback plans', data);
                    this.renderFallbackPlans();
                }
            } catch (error) {
                console.error('❌ [BILLING] Error loading plans:', error);
                this.renderFallbackPlans();
            }
        }
        
        convertFeaturesToDescription(features) {
            const descriptions = [];
            
            if (features.max_contacts) {
                descriptions.push(`${features.max_contacts} contacts`);
            }
            if (features.max_products) {
                descriptions.push(`${features.max_products} products`);
            }
            if (features.whatsapp_channels) {
                descriptions.push(`${features.whatsapp_channels} WhatsApp ${features.whatsapp_channels === 1 ? 'channel' : 'channels'}`);
            }
            if (features.ai_credits) {
                descriptions.push(`${features.ai_credits.toLocaleString()} AI credits`);
            }
            if (features.customer_followups) {
                descriptions.push('Customer followups');
            }
            if (features.customer_categorization) {
                descriptions.push('Customer categorization');
            }
            if (features.booking_calendars) {
                descriptions.push('Booking calendars');
            }
            if (features.sales_reports) {
                descriptions.push('Sales reports');
            }
            if (features.unlimited_messages) {
                descriptions.push('Unlimited messages');
            }
            
            return descriptions;
        }

        updateCurrentPlanDisplay() {
            // Plan display is now handled server-side in PHP
            // This method can be called to refresh dynamically if needed
            const planName = this.currentSubscription?.plan || 'trial';
            const planTitle = this.capitalize(planName) + (planName === 'trial' ? ' (Free)' : ' Plan');
            
            const currentPlanElement = document.getElementById('currentPlanName');
            if (currentPlanElement) {
                currentPlanElement.textContent = planTitle;
                currentPlanElement.className = `badge bg-${this.getPlanColor(planName)}`;
            }
            
            const status = this.currentSubscription?.status || 'inactive';
            const credits = this.currentSubscription?.ai_credits || 0;
            const detailsElement = document.getElementById('currentPlanDetails');
            if (detailsElement) {
                const statusColor = status === 'active' ? 'success' : 'danger';
                detailsElement.innerHTML = `Status: <strong class="text-${statusColor}">${this.capitalize(status)}</strong>` +
                    (credits > 0 ? ` • ${credits.toLocaleString()} AI Credits` : '');
            }
            
            const expiryDate = this.currentSubscription?.expires_at 
                ? new Date(this.currentSubscription.expires_at).toLocaleDateString()
                : 'N/A';
            const expiryElement = document.getElementById('currentPlanExpiry');
            if (expiryElement) {
                expiryElement.textContent = expiryDate;
            }
        }

        renderAvailablePlans() {
            console.log('🟢 [BILLING] renderAvailablePlans() called');
            console.log('🟢 [BILLING] this.availablePlans:', this.availablePlans);
            
            const container = document.getElementById('availablePlans');
            const currentPlan = this.currentSubscription?.plan || 'trial';
            const subscriptionStatus = this.currentSubscription?.status || 'inactive';
            const isExpired = ['expired', 'cancelled', 'inactive'].includes(subscriptionStatus);
            
            console.log('🟢 [BILLING] Current plan:', currentPlan, 'Status:', subscriptionStatus, 'Expired:', isExpired);
            
            const planOrder = ['starter', 'pro', 'premium'];
            const upgradePlans = planOrder.filter(plan => this.shouldShowPlan(plan, currentPlan));
            
            let html = '';
            
            // If subscription is expired and user has a paid plan, show renewal option first (horizontally)
            if (isExpired && currentPlan !== 'trial') {
                const currentPlanData = this.availablePlans && this.availablePlans.find(p => p.code === currentPlan);
                console.log('🟢 [BILLING] Renewal card - Plan data for', currentPlan, ':', currentPlanData);
                html += this.renderPlanCard(currentPlan, currentPlanData, true); // true = isRenewal
            }
            
            // Show upgrade options if available (cards flow horizontally)
            if (upgradePlans.length > 0) {
                console.log('🟢 [BILLING] Rendering upgrade plans:', upgradePlans);
                html += upgradePlans.map(planCode => {
                    const planData = this.availablePlans && this.availablePlans.find(p => p.code === planCode);
                    console.log('🟢 [BILLING] Upgrade card - Plan data for', planCode, ':', planData);
                    return this.renderPlanCard(planCode, planData, false);
                }).join('');
            } else if (!isExpired || currentPlan === 'trial') {
                // Only show "highest plan" message if subscription is active
                html = `
                    <div class="col-12 text-center">
                        <p class="text-muted">You're already on the highest plan!</p>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }

        renderFallbackPlans() {
            console.log('🟡 [BILLING] Using FALLBACK plans (API failed or invalid)');
            const fallbackPlans = {
                starter: { price: 69000, features: ['50 contacts', '5 products', '1 WhatsApp channel', '69,000 AI credits'] },
                pro: { price: 149000, features: ['150 contacts', '50 products', '3 WhatsApp channels', '149,000 AI credits', 'Customer followups', 'Sales reports'] },
                premium: { price: 299000, features: ['400 contacts', '200 products', '7 WhatsApp channels', '299,000 AI credits', 'All features', 'Booking calendars'] }
            };

            // Convert fallbackPlans object to array format for this.availablePlans
            this.availablePlans = Object.keys(fallbackPlans).map(code => ({
                code: code,
                price: fallbackPlans[code].price,
                features: fallbackPlans[code].features
            }));
            console.log('🟡 [BILLING] Fallback plans set:', this.availablePlans);
            
            const container = document.getElementById('availablePlans');
            const currentPlan = this.currentSubscription?.plan || 'trial';
            const subscriptionStatus = this.currentSubscription?.status || 'inactive';
            const isExpired = ['expired', 'cancelled', 'inactive'].includes(subscriptionStatus);
            
            const planOrder = ['starter', 'pro', 'premium'];
            const upgradePlans = planOrder.filter(plan => this.shouldShowPlan(plan, currentPlan));
            
            let html = '';
            
            // If subscription is expired and user has a paid plan, show renewal option first (horizontally)
            if (isExpired && currentPlan !== 'trial' && fallbackPlans[currentPlan]) {
                html += this.renderPlanCard(currentPlan, fallbackPlans[currentPlan], true);
            }
            
            // Show upgrade options if available (cards flow horizontally)
            if (upgradePlans.length > 0) {
                html += upgradePlans.map(planCode => {
                    const plan = fallbackPlans[planCode];
                    return this.renderPlanCard(planCode, plan, false);
                }).join('');
            }
            
            container.innerHTML = html;
        }

        renderPlanCard(planCode, planData = null, isRenewal = false) {
            // Hardcoded fallback plans if data is not available
            const fallbackPlanData = {
                starter: { price: 69000, features: ['50 contacts', '5 products', '1 WhatsApp channel', '69,000 AI credits'] },
                pro: { price: 149000, features: ['150 contacts', '50 products', '3 WhatsApp channels', '149,000 AI credits', 'Customer followups', 'Sales reports'] },
                premium: { price: 299000, features: ['400 contacts', '200 products', '7 WhatsApp channels', '299,000 AI credits', 'All features', 'Booking calendars'] }
            };
            
            const plan = planData || (this.availablePlans && this.availablePlans.find(p => p.code === planCode)) || fallbackPlanData[planCode] || {};
            const price = plan?.price || 0;
            const features = plan?.features || [];
            
            const dataSource = planData ? 'parameter' : (this.availablePlans && this.availablePlans.find(p => p.code === planCode)) ? 'availablePlans' : fallbackPlanData[planCode] ? 'inline-fallback' : 'empty';
            console.log(`🔷 [BILLING] renderPlanCard(${planCode}, isRenewal=${isRenewal}) - Data source: ${dataSource}`, { price, features });
            const isRecommended = !isRenewal && planCode === 'pro';
            const currentPlan = this.currentSubscription?.plan || 'trial';
            const isCurrent = planCode === currentPlan;
            
            // Determine card styling
            let cardClass = '';
            let cardStyle = '';
            let headerHtml = '';
            let buttonClass = 'btn-outline-primary';
            let buttonText = '<i class="fas fa-arrow-up"></i> Upgrade Now';
            let buttonAction = `upgradeToPlan('${planCode}', ${price})`;
            
            if (isRenewal) {
                cardClass = 'border-success';
                cardStyle = 'box-shadow: 0 4px 8px rgba(40,167,69,0.3); border-width: 2px;';
                headerHtml = '<div class="card-header text-center" style="background-color: #28a745 !important; color: #ffffff !important; font-weight: 600;"><small><i class="fas fa-sync-alt"></i> CURRENT PLAN - RENEW TO CONTINUE</small></div>';
                buttonClass = 'btn-success';
                buttonText = '<i class="fas fa-sync-alt"></i> Renew Plan - Pay Now';
                buttonAction = `renewCurrentPlan('${planCode}', ${price})`;
            } else if (isRecommended) {
                cardClass = 'border-primary';
                cardStyle = 'box-shadow: 0 4px 8px rgba(0,123,255,0.25);';
                headerHtml = '<div class="card-header text-center" style="background-color: #007bff !important; color: #ffffff !important; font-weight: 600;"><small><i class="fas fa-star"></i> RECOMMENDED</small></div>';
                buttonClass = 'btn-primary';
            }
            
            return `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 ${cardClass}" style="${cardStyle}">
                        ${headerHtml}
                        <div class="card-body text-center">
                            <h6 class="card-title">${this.capitalize(planCode)} Plan</h6>
                            <div class="mb-3">
                                <span class="h4">TZS ${this.formatPrice(price)}</span>
                                <small class="text-muted">/month</small>
                            </div>
                            <ul class="list-unstyled text-start">
                                ${features.slice(0, 4).map(feature => `<li><i class="fas fa-check text-success mr-2"></i><span style="color: #2d3748;">${feature}</span></li>`).join('')}
                                ${features.length > 4 ? `<li><small class="text-muted">+ ${features.length - 4} more features</small></li>` : ''}
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn ${buttonClass} w-100" onclick="${buttonAction}">
                                ${buttonText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        shouldShowPlan(planCode, currentPlan) {
            const hierarchy = { trial: 0, starter: 1, pro: 2, premium: 3 };
            return hierarchy[planCode] > hierarchy[currentPlan];
        }

        getPlanColor(plan) {
            const colors = { trial: 'secondary', starter: 'info', pro: 'primary', premium: 'warning' };
            return colors[plan] || 'secondary';
        }

        formatPrice(price) {
            return new Intl.NumberFormat().format(price);
        }

        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        showModal(feature = null, message = null, isHardBlock = false) {
            this.currentFeature = feature;
            
            // Safety check - ensure Bootstrap is loaded
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap not loaded');
                return;
            }
            
            // Determine if this is a hard block (expired subscription) or soft block (feature/credits)
            const modalElement = document.getElementById('pricingControlsModal');
            
            // Safety check - ensure modal element exists and is in the DOM
            if (!modalElement || !document.body.contains(modalElement)) {
                console.error('Modal element not found or not attached to DOM');
                return;
            }
            
            const closeBtn = document.getElementById('modalCloseBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            // Configure modal options based on block type
            let modalOptions = {};
            
            if (isHardBlock) {
                // Hard block: lock the modal (cannot close)
                modalOptions = {
                    backdrop: 'static',
                    keyboard: false
                };
                modalElement.setAttribute('data-bs-backdrop', 'static');
                modalElement.setAttribute('data-bs-keyboard', 'false');
                if (closeBtn) closeBtn.classList.add('d-none');
                if (cancelBtn) cancelBtn.classList.add('d-none');
            } else {
                // Soft block: allow closing (feature upgrade or credits)
                modalOptions = {
                    backdrop: true,
                    keyboard: true
                };
                modalElement.setAttribute('data-bs-backdrop', 'true');
                modalElement.setAttribute('data-bs-keyboard', 'true');
                if (closeBtn) closeBtn.classList.remove('d-none');
                if (cancelBtn) cancelBtn.classList.remove('d-none');
            }
            
            // Update feature message
            const messageElement = document.getElementById('featureMessage');
            if (message) {
                messageElement.textContent = message;
            } else if (feature) {
                messageElement.textContent = `The "${feature}" feature is not available in your current plan. Upgrade to unlock this feature.`;
            }
            
            try {
                // Dispose of any existing modal instance
                if (this.modal) {
                    try {
                        this.modal.dispose();
                    } catch (e) {
                        // Ignore disposal errors
                    }
                    this.modal = null;
                }
                
                // Wait a tick for DOM to settle
                setTimeout(() => {
                    try {
                        // Create and show modal
                        this.modal = new bootstrap.Modal(modalElement, modalOptions);
                        this.modal.show();
                    } catch (error) {
                        console.error('Error showing modal:', error);
                    }
                }, 50);
            } catch (error) {
                console.error('Error in showModal:', error);
            }
        }

        async refetchSubscriptionStatus() {
            await this.loadCurrentSubscription();
            await this.loadAvailablePlans();
        }
    }

    // Global instance - only create if not already exists
    if (typeof window.pricingControls === 'undefined') {
        window.pricingControls = null;
    }

    // Initialize when document is ready AND Bootstrap is loaded
    function initializePricingControls() {
        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap not loaded yet, retrying...');
            setTimeout(initializePricingControls, 100);
            return;
        }
        
        // Check if modal element exists
        if (!document.getElementById('pricingControlsModal')) {
            console.warn('Modal element not found yet, retrying...');
            setTimeout(initializePricingControls, 100);
            return;
        }
        
        if (!window.pricingControls) {
            window.pricingControls = new PricingControls();
            window.pricingControls.init().catch(function(error) {
                console.error('Error initializing pricing controls:', error);
            });
        }
    }

    // Initialize when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializePricingControls, 100);
        });
    } else {
        // DOM already loaded
        setTimeout(initializePricingControls, 100);
    }

    // Global functions for modal interactions
    if (typeof window.showUpgradeModal === 'undefined') {
        window.showUpgradeModal = function(feature = null, message = null, isHardBlock = false) {
            if (window.pricingControls) {
                window.pricingControls.showModal(feature, message, isHardBlock);
            }
        };
    }

    if (typeof window.upgradeToPlan === 'undefined') {
        window.upgradeToPlan = async function(planCode, price) {
        try {
            // Show loading state
            const button = event.target;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;

            // Call billing API to initiate upgrade
            const response = await fetch('{{ url("/api/billing/upgrade") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    plan_code: planCode,
                    amount: price,
                    feature: pricingControls.currentFeature
                })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to payment or show success
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    // Refetch subscription status
                    await window.pricingControls.refetchSubscriptionStatus();
                    window.pricingControls.modal.hide();
                    
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Upgrade successful!');
                    } else {
                        alert(data.message || 'Upgrade successful!');
                    }
                }
            } else {
                throw new Error(data.message || 'Upgrade failed');
            }
        } catch (error) {
            console.error('Upgrade error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Failed to process upgrade');
            } else {
                alert(error.message || 'Failed to process upgrade');
            }
        } finally {
            // Restore button
            if (event && event.target) {
                event.target.innerHTML = originalContent;
                event.target.disabled = false;
            }
        }
    };
}

if (typeof window.renewCurrentPlan === 'undefined') {
    window.renewCurrentPlan = async function(planCode, price) {
        try {
            // Show loading state
            const button = event.target;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Renewal...';
            button.disabled = true;

            // Call billing API to renew subscription
            const response = await fetch('{{ url("/api/billing/renew") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    plan_code: planCode,
                    amount: price
                })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to payment or show success
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    // Refetch subscription status
                    await window.pricingControls.refetchSubscriptionStatus();
                    window.pricingControls.modal.hide();
                    
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Plan renewed successfully!');
                    } else {
                        alert(data.message || 'Plan renewed successfully!');
                    }
                    
                    // Reload page to reflect new subscription status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                throw new Error(data.message || 'Renewal failed');
            }
        } catch (error) {
            console.error('Renewal error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Failed to process renewal. Please try again or contact support.');
            } else {
                alert(error.message || 'Failed to process renewal. Please try again or contact support.');
            }
        } finally {
            // Restore button
            if (event && event.target) {
                event.target.innerHTML = originalContent;
                event.target.disabled = false;
            }
        }
    };
}

if (typeof window.purchaseCredits === 'undefined') {
    window.purchaseCredits = async function() {
        const amount = document.getElementById('creditAmount').value;
        
        if (!amount || amount < 1000) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Minimum credit amount is TZS 1,000');
            } else {
                alert('Minimum credit amount is TZS 1,000');
            }
            return;
        }

        try {
            const response = await fetch('{{ url("/api/billing/credits") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({ amount: parseInt(amount) })
            });

            const data = await response.json();

            if (data.success) {
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    window.pricingControls.modal.hide();
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Credits purchased successfully!');
                    } else {
                        alert(data.message || 'Credits purchased successfully!');
                    }
                }
            } else {
                throw new Error(data.message || 'Credit purchase failed');
            }
        } catch (error) {
            console.error('Credit purchase error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Failed to purchase credits');
            } else {
                alert(error.message || 'Failed to purchase credits');
            }
        }
    };
}

if (typeof window.proceedWithCurrentPlan === 'undefined') {
    window.proceedWithCurrentPlan = function() {
        // Allow user to proceed with limited functionality
        window.pricingControls.modal.hide();
        
        if (typeof toastr !== 'undefined') {
            toastr.info('Continuing with current plan limitations');
        }
    };
}

// Helper function to check if user can access feature
if (typeof window.checkFeatureAccess === 'undefined') {
    window.checkFeatureAccess = function(featureName) {
        // This would integrate with your existing billing service
        // Return true if user has access, false otherwise
        // For now, return true to allow development
        return true;
    };
}

// Helper function to show upgrade modal for specific features
if (typeof window.requireFeatureUpgrade === 'undefined') {
    window.requireFeatureUpgrade = function(featureName, customMessage = null) {
        if (!window.checkFeatureAccess(featureName)) {
            window.showUpgradeModal(featureName, customMessage);
            return false;
        }
        return true;
    };
}

    } // End of pricingControlsInitialized check
</script>

@auth
@if($isTrialExpired || $isSubscriptionExpired || $isInactive)
{{-- ── Secondary: also show Bootstrap modal (on top of the hard-block overlay) ─ --}}
<script>
(function () {
    'use strict';

    var HARD_BLOCK_MESSAGE = @json($defaultMessage);

    /**
     * Show the pricing Bootstrap modal directly — no dependency on PricingControls.
     * This is the bullet-proof fallback that fires even if PricingControls fails.
     */
    function showBlockModal() {
        if (typeof bootstrap === 'undefined') { return; }

        var el = document.getElementById('pricingControlsModal');
        if (!el) { return; }

        // Ensure close buttons stay hidden for a hard block
        var closeBtn   = document.getElementById('modalCloseBtn');
        var cancelBtn  = document.getElementById('modalCancelBtn');
        if (closeBtn)  closeBtn.classList.add('d-none');
        if (cancelBtn) cancelBtn.classList.add('d-none');

        // Update message text to the server-side computed message
        var msgEl = document.getElementById('featureMessage');
        if (msgEl) { msgEl.textContent = HARD_BLOCK_MESSAGE; }

        el.setAttribute('data-bs-backdrop', 'static');
        el.setAttribute('data-bs-keyboard', 'false');

        try {
            // Dispose any stale instance first
            var existing = bootstrap.Modal.getInstance(el);
            if (existing) { try { existing.dispose(); } catch (e) {} }

            var modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
            modal.show();

            // Expose on window so PricingControls can still reference it
            if (window.pricingControls) {
                window.pricingControls.modal = modal;
            }
        } catch (e) {
            console.warn('[BillingGate] Modal show failed:', e);
        }
    }

    /**
     * Try via PricingControls first; fall back to direct Bootstrap call.
     * Retries for up to 4 seconds, then falls back regardless.
     */
    function tryShow(attempt) {
        attempt = attempt || 0;

        if (window.pricingControls && typeof window.pricingControls.showModal === 'function') {
            window.pricingControls.showModal(null, HARD_BLOCK_MESSAGE, true);
            return;
        }

        if (attempt < 40) {
            // Retry every 100 ms (4 s total)
            setTimeout(function () { tryShow(attempt + 1); }, 100);
            return;
        }

        // PricingControls never became ready — use direct Bootstrap
        console.warn('[BillingGate] PricingControls not ready after 4 s, using direct modal.');
        showBlockModal();
    }

    function boot() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () { tryShow(0); }, 300);
            });
        } else {
            setTimeout(function () { tryShow(0); }, 300);
        }
    }

    boot();
})();
</script>
@endif
@endauth

<style>
/* Pricing Modal Card Headers - Ensure readability */
#pricingControlsModal .card-header[style*="background-color"] {
    border: none !important;
}

#pricingControlsModal .card-header[style*="background-color"] small {
    font-weight: 600 !important;
    letter-spacing: 0.025em;
}

/* Dark Mode - Upgrade Modal Styling */

/* Modal Container */
.dark-mode .modal-content {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #e2e8f0 !important;
}

/* Modal Header */
.dark-mode .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: 1px solid #4a5568 !important;
    color: #ffffff !important;
}

.dark-mode .modal-header[style*="background"] {
    filter: brightness(1.05) !important;
}

.dark-mode .modal-title {
    color: #ffffff !important;
    font-size: 1.25rem !important;
    font-weight: 600 !important;
}

.dark-mode .modal-title i {
    color: #ffffff !important;
}

.dark-mode .btn-close-white {
    filter: brightness(1.2) !important;
    opacity: 1 !important;
}

.dark-mode .btn-close-white:hover {
    filter: brightness(1.5) !important;
}

/* Modal Body */
.dark-mode .modal-body {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
}

/* Main Description Section */
.dark-mode .modal-body .text-center {
    color: #f7fafc !important;
}

.dark-mode .modal-body .text-center h5 {
    color: #f7fafc !important;
}

.dark-mode .modal-body .text-center p {
    color: #e2e8f0 !important;
}

.dark-mode .modal-body h5 {
    color: #f7fafc !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
}

.dark-mode .modal-body h6 {
    color: #f7fafc !important;
    font-size: 1.125rem !important;
    font-weight: 600 !important;
}

.dark-mode .modal-body p {
    color: #e2e8f0 !important;
    font-size: 1rem !important;
    line-height: 1.6 !important;
}

.dark-mode .modal-body p.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .modal-body small {
    color: #cbd5e0 !important;
    font-size: 0.875rem !important;
}

.dark-mode .modal-body small.text-muted {
    color: #cbd5e0 !important;
}

/* Icons in Modal */
.dark-mode .modal-body i.fa-3x,
.dark-mode .modal-body i.fa-2x {
    opacity: 0.9;
}

/* Current Plan Card */
.dark-mode .modal-body .card {
    background: #1a202c !important;
    border: 2px solid #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-body .card[style*="border-left: 4px solid #17a2b8"] {
    border-left: 4px solid #0bc5ea !important;
}

.dark-mode .modal-body .card[style*="border-left: 4px solid #28a745"] {
    border-left: 4px solid #48bb78 !important;
}

.dark-mode .modal-body .card-body {
    background: #1a202c !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-body .card-title {
    color: #f7fafc !important;
    font-size: 1.125rem !important;
    font-weight: 600 !important;
}

.dark-mode .modal-body .card h6.card-title {
    color: #f7fafc !important;
    font-weight: 600 !important;
}

.dark-mode .modal-body .card .card-body h6 {
    color: #f7fafc !important;
}

.dark-mode .modal-body .card-title i {
    color: #90cdf4 !important;
}

.dark-mode .modal-body .card-body p {
    color: #e2e8f0 !important;
}

.dark-mode .modal-body .card-body p.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .modal-body .card-body small {
    color: #cbd5e0 !important;
}

.dark-mode .modal-body .card-body strong {
    color: #ffffff !important;
    font-weight: 600 !important;
}

/* Plan Badges */
.dark-mode .badge {
    font-weight: 500 !important;
    font-size: 0.875rem !important;
    padding: 0.5rem 0.75rem !important;
}

.dark-mode .badge.bg-secondary {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;
    color: #ffffff !important;
}

.dark-mode .badge.bg-info {
    background: linear-gradient(135deg, #0bc5ea 0%, #00a3c4 100%) !important;
    color: #ffffff !important;
}

.dark-mode .badge.bg-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    color: #ffffff !important;
}

.dark-mode .badge.bg-warning {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%) !important;
    color: #ffffff !important;
}

/* Text Colors */
.dark-mode .text-success {
    color: #68d391 !important;
}

.dark-mode .text-danger {
    color: #fc8181 !important;
}

.dark-mode .text-info {
    color: #76e4f7 !important;
}

.dark-mode .text-warning {
    color: #f6ad55 !important;
}

/* Pricing Cards */
.dark-mode .modal-body .card.border-primary {
    border: 2px solid #4299e1 !important;
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3) !important;
}

.dark-mode .modal-body .card-header {
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode .modal-body .card-header.bg-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    color: #ffffff !important;
}

.dark-mode .modal-body .card-header.bg-primary small {
    color: #ffffff !important;
}

.dark-mode .modal-body .card-header.bg-primary i {
    color: #ffd700 !important;
}

/* Success/Renewal Card Headers */
.dark-mode .modal-body .card-header.bg-success,
.dark-mode .modal-body .card-header[style*="background-color: #28a745"] {
    background-color: #38a169 !important;
    color: #ffffff !important;
}

.dark-mode .modal-body .card-header.bg-success small,
.dark-mode .modal-body .card-header[style*="background-color: #28a745"] small {
    color: #ffffff !important;
}

.dark-mode .modal-body .card-header.bg-success i,
.dark-mode .modal-body .card-header[style*="background-color: #28a745"] i {
    color: #ffffff !important;
}

.dark-mode .modal-body .card-footer {
    background: #1a202c !important;
    border-top: 1px solid #4a5568 !important;
}

/* Pricing Display */
.dark-mode .modal-body .h4 {
    color: #f7fafc !important;
    font-weight: 700 !important;
    font-size: 1.75rem !important;
}

.dark-mode .modal-body .card .h4 {
    color: #f7fafc !important;
}

.dark-mode .modal-body .card .h6 {
    color: #f7fafc !important;
}

.dark-mode .modal-body .card span.h4 {
    color: #ffffff !important;
}

/* Feature Lists */
.dark-mode .modal-body ul {
    padding-left: 0 !important;
}

.dark-mode .modal-body ul li {
    color: #e2e8f0 !important;
    margin-bottom: 0.5rem !important;
    font-size: 0.95rem !important;
}

.dark-mode .modal-body ul li i.fa-check {
    color: #68d391 !important;
}

.dark-mode .modal-body ul li small {
    color: #cbd5e0 !important;
}

/* Buttons in Modal */
.dark-mode .btn {
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.dark-mode .btn-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    border: none !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(66, 153, 225, 0.3) !important;
}

.dark-mode .btn-primary:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.5) !important;
}

.dark-mode .btn-outline-primary {
    color: #63b3ed !important;
    border: 2px solid #4299e1 !important;
    background: transparent !important;
}

.dark-mode .btn-outline-primary:hover {
    background: #4299e1 !important;
    color: #ffffff !important;
    transform: translateY(-2px) !important;
}

.dark-mode .btn-success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%) !important;
    border: none !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(72, 187, 120, 0.3) !important;
}

.dark-mode .btn-success:hover {
    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(72, 187, 120, 0.5) !important;
}

.dark-mode .btn-lg {
    padding: 0.75rem 1.5rem !important;
    font-size: 1.125rem !important;
}

.dark-mode .btn-outline-secondary {
    color: #cbd5e0 !important;
    border: 2px solid #718096 !important;
    background: transparent !important;
}

.dark-mode .btn-outline-secondary:hover {
    background: #718096 !important;
    color: #ffffff !important;
}

/* Button Icons */
.dark-mode .btn i {
    color: inherit !important;
}

/* Alert in Modal */
.dark-mode .modal-body .alert {
    border-width: 1px !important;
    font-size: 0.95rem !important;
}

.dark-mode .modal-body .alert-warning {
    background: rgba(237, 137, 54, 0.2) !important;
    border-color: #ed8936 !important;
    color: #fbd38d !important;
}

.dark-mode .modal-body .alert-warning strong {
    color: #fbd38d !important;
}

.dark-mode .modal-body .alert-warning i {
    color: #f6ad55 !important;
}

/* Spinner */
.dark-mode .spinner-border {
    color: #4299e1 !important;
}

.dark-mode .visually-hidden {
    color: #e2e8f0 !important;
}

/* Loading Text */
.dark-mode .modal-body .col-12.text-center p {
    color: #cbd5e0 !important;
}

/* Enhanced Readability - Increase Font Sizes */
.dark-mode #pricingControlsModal .modal-body {
    font-size: 1rem !important;
    line-height: 1.6 !important;
}

.dark-mode #pricingControlsModal .card-body {
    padding: 1.5rem !important;
}

.dark-mode #pricingControlsModal h5 {
    margin-bottom: 1rem !important;
}

.dark-mode #pricingControlsModal h6 {
    margin-bottom: 0.75rem !important;
}

/* Specific to Pricing Cards */
.dark-mode #pricingControlsModal .col-md-4 .card {
    transition: all 0.3s ease !important;
}

.dark-mode #pricingControlsModal .col-md-4 .card:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4) !important;
}

/* Modal Dialog */
.dark-mode .modal-dialog {
    max-width: 900px !important;
}

/* Ensure All Text is Visible */
.dark-mode #pricingControlsModalLabel {
    color: #ffffff !important;
}

.dark-mode #modalContextTitle {
    color: #f7fafc !important;
    font-weight: 600 !important;
}

.dark-mode #featureMessage {
    color: #e2e8f0 !important;
    font-size: 1rem !important;
}

.dark-mode #currentPlanName {
    display: inline-block !important;
}

.dark-mode #currentPlanDetails {
    color: #e2e8f0 !important;
}

.dark-mode #currentPlanDetails strong {
    color: #f7fafc !important;
}

.dark-mode #currentPlanExpiry {
    color: #f7fafc !important;
    font-weight: 600 !important;
}

/* Text Muted Overrides for Better Readability */
.dark-mode #pricingControlsModal .text-muted {
    color: #cbd5e0 !important;
}

.dark-mode #pricingControlsModal .card .text-muted {
    color: #cbd5e0 !important;
}

.dark-mode #pricingControlsModal small.text-muted {
    color: #cbd5e0 !important;
}

/* Small Text Visibility */
.dark-mode #pricingControlsModal small {
    color: #cbd5e0 !important;
}

.dark-mode #pricingControlsModal .card-body small {
    color: #cbd5e0 !important;
}

/* Alert Text Improvements */
.dark-mode #pricingControlsModal .alert {
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .alert strong {
    color: #ffffff !important;
}

.dark-mode #pricingControlsModal .alert-warning {
    background: rgba(237, 137, 54, 0.15) !important;
    border: 1px solid #ed8936 !important;
    color: #fbd38d !important;
}

.dark-mode #pricingControlsModal .alert-warning strong {
    color: #fbd38d !important;
}

.dark-mode #pricingControlsModal .alert-warning i {
    color: #f6ad55 !important;
}

/* Wallet Card Styling */
.dark-mode #pricingControlsModal .card[style*="border-left: 4px solid #28a745"] .card-body p {
    color: #e2e8f0 !important;
}

.dark-mode #pricingControlsModal .card[style*="border-left: 4px solid #28a745"] .card-body p.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode #pricingControlsModal .card[style*="border-left: 4px solid #28a745"] .card-body p.small {
    color: #cbd5e0 !important;
}

/* Dividers */
.dark-mode .modal-body hr {
    border-color: #4a5568 !important;
    opacity: 1 !important;
}

/* Focus States */
.dark-mode .btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(66, 153, 225, 0.5) !important;
}

.dark-mode .btn-success:focus {
    box-shadow: 0 0 0 0.25rem rgba(72, 187, 120, 0.5) !important;
}
</style>

@if($subscriptionStatus === 'trial' && $expiresAt && now()->lessThan($expiresAt))
<!-- Trial Status Info -->
<div class="alert alert-info alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-clock"></i> 
    <strong>Trial Active:</strong> Your trial expires on {{ $expiresAt->format('M d, Y') }}. Subscribe to continue using all features after trial ends.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<style>
/* Dark Mode - Trial Alert */
.dark-mode .alert-info {
    background: rgba(66, 153, 225, 0.15) !important;
    border: 1px solid #4299e1 !important;
    color: #90cdf4 !important;
}

.dark-mode .alert-info strong {
    color: #90cdf4 !important;
    font-weight: 600 !important;
}

.dark-mode .alert-info i {
    color: #63b3ed !important;
}

/* Fix btn-close to show × instead of - */
.alert .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    width: 1em;
    height: 1em;
    opacity: 0.5;
    border: none;
    padding: 0;
}

.alert .btn-close:hover {
    opacity: 0.75;
}

.dark-mode .alert .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    opacity: 0.8;
    filter: none !important;
}

.dark-mode .alert .btn-close:hover {
    opacity: 1 !important;
}

/* Light Mode - Ensure text is readable when OS is in dark mode */
/* Override OS dark mode preferences when app is in light mode */
#pricingControlsModal:not(.dark-mode) .modal-content,
#pricingControlsModal .modal-content {
    background-color: #ffffff !important;
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) .modal-body,
#pricingControlsModal .modal-body {
    background-color: #ffffff !important;
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) .card,
#pricingControlsModal .card {
    background-color: #ffffff !important;
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) .card-body,
#pricingControlsModal .card-body {
    background-color: #ffffff !important;
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) .card-title,
#pricingControlsModal .card-title {
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) h6,
#pricingControlsModal h6 {
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) .h4,
#pricingControlsModal .h4 {
    color: #212529 !important;
}

#pricingControlsModal:not(.dark-mode) ul li,
#pricingControlsModal ul li {
    color: #2d3748 !important;
}

#pricingControlsModal:not(.dark-mode) .text-muted,
#pricingControlsModal .text-muted {
    color: #6c757d !important;
}

/* Dark mode overrides for when app is actually in dark mode */
.dark-mode #pricingControlsModal .modal-content {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode #pricingControlsModal .modal-body {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode #pricingControlsModal .card {
    background-color: #1a202c !important;
    color: #e2e8f0 !important;
}

.dark-mode #pricingControlsModal ul li span {
    color: #e2e8f0 !important;
}
</style>
@endif