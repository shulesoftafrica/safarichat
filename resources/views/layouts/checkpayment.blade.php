<?php 
// NEW BILLING LOGIC - No more SubscriptionService calls
$user = Auth::user();
$businessId = $user->business?->id ?? $user->id; // Fallback to user ID if no business
?>

<!-- Pricing Controls Modal - Reusable across all pages -->
<div class="modal fade" id="pricingControlsModal" tabindex="-1" aria-labelledby="pricingControlsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title" id="pricingControlsModalLabel">
                    <i class="fas fa-crown"></i> Upgrade Required
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pricingControlsModalBody">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-lock fa-3x" style="color: #dc3545;"></i>
                    </div>
                    <h5>Feature Not Available</h5>
                    <p class="text-muted" id="featureMessage">This feature is not included in your current subscription plan.</p>
                </div>

                <!-- Current Plan Display -->
                <div class="card mb-4" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-user-tag"></i> Current Plan</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-secondary" id="currentPlanName">Loading...</span>
                                <small class="text-muted d-block" id="currentPlanDetails">Loading plan details...</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Expires</small>
                                <div id="currentPlanExpiry" class="fw-bold">Loading...</div>
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
                        <h6 class="card-title"><i class="fas fa-coins"></i> Purchase Additional Credits</h6>
                        <p class="text-muted mb-3">Need more AI credits without upgrading your plan?</p>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">TZS</span>
                                    <input type="number" class="form-control" id="creditAmount" placeholder="Enter amount" min="1000" step="1000">
                                </div>
                                <small class="text-muted">Minimum: TZS 1,000</small>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success w-100" onclick="purchaseCredits()">
                                    <i class="fas fa-shopping-cart"></i> Buy Credits
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="proceedWithCurrentPlan()" style="display: none;" id="proceedButton">
                        <i class="fas fa-arrow-right"></i> Continue with Current Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Controls JavaScript -->
<script>
    class PricingControls {
        constructor() {
            this.modal = null;
            this.currentSubscription = null;
            this.availablePlans = [];
            this.currentFeature = null;
        }

        async init() {
            this.modal = new bootstrap.Modal(document.getElementById('pricingControlsModal'));
            await this.loadCurrentSubscription();
            await this.loadAvailablePlans();
        }

        async loadCurrentSubscription() {
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
                    this.updateCurrentPlanDisplay();
                }
            } catch (error) {
                console.error('Error loading subscription:', error);
            }
        }

        async loadAvailablePlans() {
            try {
                const response = await fetch('{{ url("/api/billing/plans") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                console.log('API Response:', data); // Debug log
                
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
                    console.log('Processed plans:', this.availablePlans); // Debug log
                    this.renderAvailablePlans();
                } else {
                    console.warn('Invalid API response, using fallback plans', data);
                    this.renderFallbackPlans();
                }
            } catch (error) {
                console.error('Error loading plans:', error);
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
            const planName = this.currentSubscription?.plan || 'trial';
            const planTitle = this.capitalize(planName) + (planName === 'trial' ? ' (Free)' : ' Plan');
            
            document.getElementById('currentPlanName').textContent = planTitle;
            document.getElementById('currentPlanName').className = `badge bg-${this.getPlanColor(planName)}`;
            
            const details = this.getPlanDetails(planName);
            document.getElementById('currentPlanDetails').textContent = details;
            
            const expiry = this.currentSubscription?.expires_at 
                ? new Date(this.currentSubscription.expires_at).toLocaleDateString()
                : 'N/A';
            document.getElementById('currentPlanExpiry').textContent = expiry;
        }

        renderAvailablePlans() {
            const container = document.getElementById('availablePlans');
            const currentPlan = this.currentSubscription?.plan || 'trial';
            
            const planOrder = ['starter', 'pro', 'premium'];
            const filteredPlans = planOrder.filter(plan => this.shouldShowPlan(plan, currentPlan));
            
            if (filteredPlans.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center">
                        <p class="text-muted">You're already on the highest plan!</p>
                        <button type="button" class="btn btn-outline-primary" id="proceedButton" onclick="proceedWithCurrentPlan()">
                            <i class="fas fa-arrow-right"></i> Continue with Current Plan
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filteredPlans.map(planCode => this.renderPlanCard(planCode)).join('');
        }

        renderFallbackPlans() {
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
            
            const container = document.getElementById('availablePlans');
            const currentPlan = this.currentSubscription?.plan || 'trial';
            
            const planOrder = ['starter', 'pro', 'premium'];
            const filteredPlans = planOrder.filter(plan => this.shouldShowPlan(plan, currentPlan));
            
            container.innerHTML = filteredPlans.map(planCode => {
                const plan = fallbackPlans[planCode];
                return this.renderPlanCard(planCode, plan);
            }).join('');
        }

        renderPlanCard(planCode, planData = null) {
            const plan = planData || (this.availablePlans && this.availablePlans.find(p => p.code === planCode)) || {};
            const price = plan?.price || 0;
            const features = plan?.features || [];
            const isRecommended = planCode === 'pro';
            
            return `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 ${isRecommended ? 'border-primary' : ''}" style="${isRecommended ? 'box-shadow: 0 4px 8px rgba(0,123,255,0.25);' : ''}">
                        ${isRecommended ? '<div class="card-header bg-primary text-white text-center"><small><i class="fas fa-star"></i> RECOMMENDED</small></div>' : ''}
                        <div class="card-body text-center">
                            <h6 class="card-title">${this.capitalize(planCode)} Plan</h6>
                            <div class="mb-3">
                                <span class="h4">TZS ${this.formatPrice(price)}</span>
                                <small class="text-muted">/month</small>
                            </div>
                            <ul class="list-unstyled text-start">
                                ${features.slice(0, 4).map(feature => `<li><i class="fas fa-check text-success me-2"></i>${feature}</li>`).join('')}
                                ${features.length > 4 ? `<li><small class="text-muted">+ ${features.length - 4} more features</small></li>` : ''}
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn ${isRecommended ? 'btn-primary' : 'btn-outline-primary'} w-100" onclick="upgradeToPlan('${planCode}', ${price})">
                                <i class="fas fa-arrow-up"></i> Upgrade Now
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

        getPlanDetails(plan) {
            const details = {
                trial: 'Limited features, 3 days remaining',
                starter: 'Basic features, 1 WhatsApp channel',
                pro: 'Advanced features, 3 WhatsApp channels',
                premium: 'All features, 7 WhatsApp channels'
            };
            return details[plan] || 'Unknown plan';
        }

        formatPrice(price) {
            return new Intl.NumberFormat().format(price);
        }

        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        showModal(feature = null, message = null) {
            this.currentFeature = feature;
            
            // Update feature message
            const messageElement = document.getElementById('featureMessage');
            if (message) {
                messageElement.textContent = message;
            } else if (feature) {
                messageElement.textContent = `The "${feature}" feature is not available in your current plan. Upgrade to unlock this feature.`;
            }
            
            this.modal.show();
        }

        async refetchSubscriptionStatus() {
            await this.loadCurrentSubscription();
            await this.loadAvailablePlans();
        }
    }

    // Global instance
    let pricingControls;

    // Initialize when document is ready
    document.addEventListener('DOMContentLoaded', async function() {
        pricingControls = new PricingControls();
        await pricingControls.init();
    });

    // Global functions for modal interactions
    function showUpgradeModal(feature = null, message = null) {
        if (pricingControls) {
            pricingControls.showModal(feature, message);
        }
    }

    async function upgradeToPlan(planCode, price) {
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
                    await pricingControls.refetchSubscriptionStatus();
                    pricingControls.modal.hide();
                    
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
            const button = event.target;
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    }

    async function purchaseCredits() {
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
                    pricingControls.modal.hide();
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
    }

    function proceedWithCurrentPlan() {
        // Allow user to proceed with limited functionality
        pricingControls.modal.hide();
        
        if (typeof toastr !== 'undefined') {
            toastr.info('Continuing with current plan limitations');
        }
    }

    // Helper function to check if user can access feature
    function checkFeatureAccess(featureName) {
        // This would integrate with your existing billing service
        // Return true if user has access, false otherwise
        // For now, return true to allow development
        return true;
    }

    // Helper function to show upgrade modal for specific features
    function requireFeatureUpgrade(featureName, customMessage = null) {
        if (!checkFeatureAccess(featureName)) {
            showUpgradeModal(featureName, customMessage);
            return false;
        }
        return true;
    }
</script>

@if(isset($user) && (!$user->subscription_status || $user->subscription_status === 'expired'))
<!-- Auto-show pricing modal when subscription is required -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-show pricing modal for expired/no subscription
    if (pricingControls && pricingControls.modal) {
        setTimeout(() => {
            pricingControls.showModal(null, 'Your subscription has expired or is not active. Please upgrade to continue using SafariChat features.');
        }, 500);
    }
});
</script>
@elseif(isset($user) && $user->subscription_status === 'trial')
<!-- Trial Status Info -->
<div class="alert alert-info alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-clock"></i> 
    <strong>Trial Active:</strong> Your trial is active. Subscribe to continue using all features after trial ends.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif