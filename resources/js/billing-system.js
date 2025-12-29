/**
 * SafariChat Billing System - Frontend Integration
 * Cache-Local Billing with Revenue Protection
 */

class SafariChatBilling {
    constructor() {
        this.customerId = null;
        this.billingStatus = null;
        this.cacheExpiry = null;
        this.refreshInterval = null;
        
        this.API_BASE = '/api/billing';
        this.CACHE_KEY_PREFIX = 'safarichat_billing_';
        this.CACHE_DURATION = 2 * 60 * 60 * 1000; // 2 hours in milliseconds
        
        this.initializeBilling();
    }
    
    /**
     * Initialize billing system on app startup
     */
    async initializeBilling() {
        try {
            console.log('🚀 SafariChat Billing initializing...');
            
            // Get customer ID from current user
            this.customerId = this.getCurrentCustomerId();
            
            if (!this.customerId) {
                console.warn('No customer ID found, billing system not initialized');
                return;
            }
            
            // Load billing status from cache or API
            await this.loadBillingStatus();
            
            // Configure UI based on permissions
            this.configureUI();
            
            // Set up periodic refresh
            this.setupPeriodicRefresh();
            
            console.log('✅ SafariChat Billing ready - status cached locally');
            
        } catch (error) {
            console.error('Billing system initialization failed:', error);
            this.handleBillingFailure();
        }
    }
    
    /**
     * Load billing status (cache first, then API)
     */
    async loadBillingStatus() {
        // Try cache first
        const cached = this.getCachedStatus();
        if (cached && this.isCacheValid(cached)) {
            this.billingStatus = cached;
            console.log('📦 Using cached billing status');
            return cached;
        }
        
        // Load from API
        console.log('🌐 Loading billing status from API...');
        const response = await fetch(`${this.API_BASE}/customers/${this.customerId}/complete-status`);
        
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error('API returned error');
        }
        
        this.billingStatus = data.data;
        this.cacheStatus(this.billingStatus);
        
        return this.billingStatus;
    }
    
    /**
     * Get cached billing status
     */
    getCachedStatus() {
        const cacheKey = this.CACHE_KEY_PREFIX + this.customerId;
        const cached = localStorage.getItem(cacheKey);
        
        if (!cached) return null;
        
        try {
            return JSON.parse(cached);
        } catch (e) {
            console.warn('Invalid cached billing data, clearing cache');
            localStorage.removeItem(cacheKey);
            return null;
        }
    }
    
    /**
     * Cache billing status
     */
    cacheStatus(status) {
        const cacheKey = this.CACHE_KEY_PREFIX + this.customerId;
        const cacheData = {
            ...status,
            cached_at: new Date().toISOString(),
            expires_at: new Date(Date.now() + this.CACHE_DURATION).toISOString()
        };
        
        localStorage.setItem(cacheKey, JSON.stringify(cacheData));
        this.cacheExpiry = new Date(cacheData.expires_at);
    }
    
    /**
     * Check if cache is valid
     */
    isCacheValid(cached) {
        if (!cached || !cached.expires_at) return false;
        
        const expiryDate = new Date(cached.expires_at);
        return expiryDate > new Date();
    }
    
    /**
     * Configure UI based on billing permissions
     */
    configureUI() {
        if (!this.billingStatus) return;
        
        const { permissions, subscription, limits } = this.billingStatus;
        
        // Contact management
        this.configureContactUI(permissions, limits);
        
        // Product management
        this.configureProductUI(permissions, limits);
        
        // AI features
        this.configureAIUI(permissions, limits);
        
        // Feature-specific UI
        this.configureFeatureUI(permissions);
        
        // Subscription status
        this.showSubscriptionStatus(subscription);
    }
    
    /**
     * Configure contact management UI
     */
    configureContactUI(permissions, limits) {
        const addContactBtn = document.getElementById('add-contact-btn');
        const contactsLimit = document.getElementById('contacts-limit-display');
        
        if (addContactBtn) {
            const contactLimits = limits.contacts || {};
            const canAdd = permissions.add_contact && (contactLimits.current < contactLimits.max);
            
            addContactBtn.disabled = !canAdd;
            
            if (!canAdd) {
                addContactBtn.innerHTML = `🔒 Contact limit reached (${contactLimits.current}/${contactLimits.max})`;
                addContactBtn.onclick = () => this.showUpgradeModal('contacts');
            }
        }
        
        if (contactsLimit) {
            const contactLimits = limits.contacts || {};
            contactsLimit.innerHTML = `${contactLimits.current || 0} / ${contactLimits.max || 0} contacts`;
        }
    }
    
    /**
     * Configure product management UI
     */
    configureProductUI(permissions, limits) {
        const addProductBtn = document.getElementById('add-product-btn');
        const productsLimit = document.getElementById('products-limit-display');
        
        if (addProductBtn) {
            const productLimits = limits.products || {};
            const canAdd = permissions.add_product && (productLimits.current < productLimits.max);
            
            addProductBtn.disabled = !canAdd;
            
            if (!canAdd) {
                addProductBtn.innerHTML = `🔒 Product limit reached (${productLimits.current}/${productLimits.max})`;
                addProductBtn.onclick = () => this.showUpgradeModal('products');
            }
        }
        
        if (productsLimit) {
            const productLimits = limits.products || {};
            productsLimit.innerHTML = `${productLimits.current || 0} / ${productLimits.max || 0} products`;
        }
    }
    
    /**
     * Configure AI features UI
     */
    configureAIUI(permissions, limits) {
        const aiCreditsDisplay = document.getElementById('ai-credits-display');
        const aiFeatures = document.querySelectorAll('.ai-feature');
        
        if (aiCreditsDisplay) {
            const credits = limits.ai_credits?.balance || limits.wallet?.ai_credits || 0;
            aiCreditsDisplay.innerHTML = `${credits.toLocaleString()} AI credits`;
            
            // Show warning if credits are low
            if (credits < 1000) {
                aiCreditsDisplay.classList.add('credits-low');
            }
        }
        
        // Enable/disable AI features
        aiFeatures.forEach(element => {
            if (!permissions.use_ai) {
                element.classList.add('disabled');
                element.onclick = () => this.showUpgradeModal('ai');
            }
        });
    }
    
    /**
     * Configure feature-specific UI elements
     */
    configureFeatureUI(permissions) {
        // Customer followups
        const followupsMenu = document.getElementById('customer-followups-menu');
        if (followupsMenu) {
            followupsMenu.style.display = permissions.customer_followups ? 'block' : 'none';
        }
        
        // Sales reports
        const reportsMenu = document.getElementById('sales-reports-menu');
        if (reportsMenu) {
            reportsMenu.style.display = permissions.sales_reports ? 'block' : 'none';
        }
        
        // Booking calendars
        const bookingMenu = document.getElementById('booking-calendar-menu');
        if (bookingMenu) {
            bookingMenu.style.display = permissions.booking_calendars ? 'block' : 'none';
        }
        
        // Customer categorization
        const categorizationFeatures = document.querySelectorAll('.categorization-feature');
        categorizationFeatures.forEach(element => {
            if (!permissions.customer_categorization) {
                element.style.display = 'none';
            }
        });
    }
    
    /**
     * Show subscription status banner
     */
    showSubscriptionStatus(subscription) {
        const statusBanner = document.getElementById('subscription-status-banner');
        if (!statusBanner) return;
        
        if (subscription.is_trial) {
            const expiryDate = new Date(subscription.expires_at);
            const daysLeft = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
            
            statusBanner.innerHTML = `
                <div class="trial-banner">
                    ⏰ Trial expires in ${daysLeft} days. 
                    <a href="/billing/upgrade" class="upgrade-link">Upgrade now!</a>
                </div>
            `;
            statusBanner.style.display = 'block';
        } else if (!subscription.active) {
            statusBanner.innerHTML = `
                <div class="expired-banner">
                    ⚠️ Subscription expired. 
                    <a href="/billing/renew" class="renew-link">Renew now!</a>
                </div>
            `;
            statusBanner.style.display = 'block';
        } else {
            statusBanner.style.display = 'none';
        }
    }
    
    /**
     * Setup periodic cache refresh
     */
    setupPeriodicRefresh() {
        // Refresh every 30 minutes
        this.refreshInterval = setInterval(() => {
            this.backgroundRefresh();
        }, 30 * 60 * 1000);
    }
    
    /**
     * Background refresh (non-blocking)
     */
    async backgroundRefresh() {
        try {
            console.log('🔄 Background billing refresh...');
            await this.loadBillingStatus();
            this.configureUI();
        } catch (error) {
            console.warn('Background refresh failed:', error);
        }
    }
    
    /**
     * Force refresh billing status
     */
    async forceRefresh() {
        try {
            // Clear cache
            const cacheKey = this.CACHE_KEY_PREFIX + this.customerId;
            localStorage.removeItem(cacheKey);
            
            // Reload from API
            await this.loadBillingStatus();
            this.configureUI();
            
            console.log('✅ Billing status force refreshed');
            return true;
            
        } catch (error) {
            console.error('Force refresh failed:', error);
            return false;
        }
    }
    
    /**
     * Show upgrade modal
     */
    showUpgradeModal(feature) {
        const plan = this.billingStatus?.subscription?.plan || 'trial';
        const nextPlan = this.getRecommendedUpgrade(plan, feature);
        
        const modal = document.createElement('div');
        modal.className = 'billing-modal-overlay';
        modal.innerHTML = `
            <div class="billing-modal">
                <div class="modal-header">
                    <h3>🚀 Upgrade Required</h3>
                    <button class="close-btn" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Your ${plan} plan has reached its ${feature} limit.</p>
                    <p>Upgrade to <strong>${nextPlan}</strong> plan for more features!</p>
                    
                    <div class="plan-comparison">
                        ${this.getPlanComparisonHTML(plan, nextPlan)}
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">
                        Maybe Later
                    </button>
                    <a href="/billing/upgrade?to=${nextPlan}" class="btn-primary">
                        Upgrade to ${nextPlan}
                    </a>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    /**
     * Get recommended upgrade plan
     */
    getRecommendedUpgrade(currentPlan, feature) {
        const upgradeMap = {
            'trial': 'starter',
            'starter': 'pro',
            'pro': 'premium',
            'premium': 'premium' // Already at highest
        };
        
        return upgradeMap[currentPlan] || 'starter';
    }
    
    /**
     * Generate plan comparison HTML
     */
    getPlanComparisonHTML(currentPlan, nextPlan) {
        // This would contain a detailed comparison
        return `
            <div class="plan-comparison-table">
                <div class="current-plan">
                    <h4>Your ${currentPlan} Plan</h4>
                    <ul>
                        <li>Limited features</li>
                        <li>Basic support</li>
                    </ul>
                </div>
                <div class="next-plan">
                    <h4>${nextPlan} Plan</h4>
                    <ul>
                        <li>More contacts & products</li>
                        <li>Advanced features</li>
                        <li>Priority support</li>
                    </ul>
                </div>
            </div>
        `;
    }
    
    /**
     * Handle billing system failure
     */
    handleBillingFailure() {
        console.warn('🔧 Billing system in fallback mode');
        
        // Show minimal UI
        const billingElements = document.querySelectorAll('.billing-dependent');
        billingElements.forEach(element => {
            element.classList.add('billing-fallback');
        });
        
        // Show fallback message
        const fallbackMessage = document.createElement('div');
        fallbackMessage.className = 'billing-fallback-message';
        fallbackMessage.innerHTML = `
            <div class="fallback-notice">
                ⚠️ Billing system temporarily unavailable. Limited functionality active.
                <button onclick="window.billing.forceRefresh()">Retry</button>
            </div>
        `;
        
        document.body.prepend(fallbackMessage);
    }
    
    /**
     * Get current customer ID from DOM or session
     */
    getCurrentCustomerId() {
        // Try various methods to get customer ID
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (userId) return userId;
        
        const userDataScript = document.querySelector('script[id="user-data"]');
        if (userDataScript) {
            try {
                const userData = JSON.parse(userDataScript.textContent);
                return userData.id || userData.customer_id;
            } catch (e) {
                console.warn('Could not parse user data');
            }
        }
        
        return null;
    }
    
    /**
     * Clean up intervals when page unloads
     */
    cleanup() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Initialize billing system when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.billing = new SafariChatBilling();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.billing) {
        window.billing.cleanup();
    }
});