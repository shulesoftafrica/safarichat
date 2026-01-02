/**
 * Local Billing Validator - Frontend Implementation
 * Revenue Protected Validation Functions
 */

class LocalBillingValidator {
    /**
     * REVENUE PROTECTION: Always check cache validity first
     */
    static validateCacheOrFail(status) {
        if (!status) {
            console.warn('Billing validation failed: no_cache');
            return { valid: false, reason: 'no_cache' };
        }
        
        if (status.expires_at) {
            const expiresAt = new Date(status.expires_at);
            if (expiresAt <= new Date()) {
                console.warn('Billing validation failed: cache_expired');
                return { valid: false, reason: 'cache_expired' };
            }
        }
        
        return { valid: true };
    }
    
    /**
     * Check if can add contact (REVENUE PROTECTED)
     */
    static canAddContact(status) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            return { allowed: false, reason: cacheCheck.reason };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        if (!status.permissions?.add_contact) {
            return { allowed: false, reason: 'permission_denied' };
        }
        
        const contacts = status.limits?.contacts || {};
        if (contacts.current >= contacts.max) {
            return { 
                allowed: false, 
                reason: 'limit_reached', 
                current: contacts.current, 
                max: contacts.max 
            };
        }
        
        return { allowed: true };
    }
    
    /**
     * Check if can add product (REVENUE PROTECTED)
     */
    static canAddProduct(status) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            return { allowed: false, reason: cacheCheck.reason };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        if (!status.permissions?.add_product) {
            return { allowed: false, reason: 'permission_denied' };
        }
        
        const products = status.limits?.products || {};
        if (products.current >= products.max) {
            return { 
                allowed: false, 
                reason: 'limit_reached', 
                current: products.current, 
                max: products.max 
            };
        }
        
        return { allowed: true };
    }
    
    /**
     * Check if can send message (REVENUE PROTECTED)
     */
    static canSendMessage(status) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            return { allowed: false, reason: cacheCheck.reason };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        if (!status.permissions?.send_message) {
            return { allowed: false, reason: 'permission_denied' };
        }
        
        return { allowed: true };
    }
    
    /**
     * CRITICAL: Check if can use AI with credits validation
     */
    static async canUseAI(status, creditsNeeded = 1) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            console.warn(`AI usage blocked due to cache validation failure for customer ${status.customer_id}`);
            return { allowed: false, reason: 'cache_refresh_required' };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        if (!status.permissions?.use_ai) {
            return { allowed: false, reason: 'permission_denied' };
        }
        
        // REVENUE PROTECTION: Check credits availability
        const balance = status.limits?.ai_credits?.balance || status.wallet?.ai_credits || 0;
        
        if (balance < creditsNeeded) {
            console.info(`AI usage blocked: insufficient credits (needed: ${creditsNeeded}, available: ${balance})`);
            return { 
                allowed: false, 
                reason: 'insufficient_credits', 
                needed: creditsNeeded, 
                available: balance 
            };
        }
        
        // REVENUE PROTECTION: Server-side verification for high credit usage
        if (creditsNeeded > 100) {
            const serverCheck = await this.verifyCreditsServerSide(status.customer_id, creditsNeeded);
            if (!serverCheck.allowed) {
                return serverCheck;
            }
        }
        
        return { allowed: true, credits_available: balance };
    }
    
    /**
     * Check if can add WhatsApp channel
     */
    static canAddWhatsAppChannel(status) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            return { allowed: false, reason: cacheCheck.reason };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        const channels = status.limits?.whatsapp_channels || {};
        if (channels.current >= channels.max) {
            return { 
                allowed: false, 
                reason: 'limit_reached', 
                current: channels.current, 
                max: channels.max 
            };
        }
        
        return { allowed: true };
    }
    
    /**
     * Check feature permissions
     */
    static hasFeaturePermission(status, feature) {
        const cacheCheck = this.validateCacheOrFail(status);
        if (!cacheCheck.valid) {
            return { allowed: false, reason: cacheCheck.reason };
        }
        
        if (!status.subscription?.active) {
            return { allowed: false, reason: 'subscription_inactive' };
        }
        
        const hasPermission = status.permissions?.[feature] ?? false;
        
        return {
            allowed: hasPermission,
            reason: hasPermission ? null : 'feature_not_in_plan',
            plan: status.subscription?.plan
        };
    }
    
    /**
     * REVENUE PROTECTION: Server-side credit verification for high-value operations
     */
    static async verifyCreditsServerSide(customerId, creditsNeeded) {
        try {
            const response = await fetch(`${window.API_BASE_URL}/billing/verify-credits`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ 
                    customer_id: customerId, 
                    credits_needed: creditsNeeded 
                })
            });
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Credit verification failed:', error);
            // FAIL SAFE: Block operation if verification fails
            return { allowed: false, reason: 'verification_failed' };
        }
    }
}

/**
 * Local Credit Manager - Frontend Implementation
 * Handles credit reservations and deductions with revenue protection
 */
class LocalCreditManager {
    static reservations = new Map();
    
    /**
     * REVENUE PROTECTION: Reserve credits before AI operation
     */
    static async reserveCredits(customerId, amount, description = 'AI operation') {
        const billing = window.billing;
        if (!billing || !billing.billingStatus) {
            return { success: false, reason: 'billing_not_available' };
        }
        
        const status = billing.billingStatus;
        const currentBalance = status.limits?.ai_credits?.balance || status.wallet?.ai_credits || 0;
        
        if (currentBalance < amount) {
            console.warn(`Credit reservation failed for customer ${customerId}: insufficient credits`);
            return { success: false, reason: 'insufficient_credits', available: currentBalance };
        }
        
        // Generate reservation ID
        const reservationId = 'RSV_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Store reservation locally
        this.reservations.set(reservationId, {
            customerId,
            amount,
            description,
            createdAt: new Date(),
            expiresAt: new Date(Date.now() + 10 * 60 * 1000) // 10 minutes
        });
        
        // Update local balance
        status.limits.ai_credits.balance = currentBalance - amount;
        status.limits.ai_credits.canUse = status.limits.ai_credits.balance > 0;
        
        // Update cache
        billing.cacheStatus(status);
        
        console.info(`Credits reserved for customer ${customerId}`, {
            reservation_id: reservationId,
            amount: amount,
            new_balance: currentBalance - amount
        });
        
        return { success: true, reservation_id: reservationId, new_balance: currentBalance - amount };
    }
    
    /**
     * REVENUE PROTECTION: Finalize credit deduction with actual amount
     */
    static finalizeCredits(customerId, reservationId, actualAmount) {
        const reservation = this.reservations.get(reservationId);
        if (!reservation) {
            console.warn(`Attempted to finalize non-existent reservation ${reservationId}`);
            return false;
        }
        
        const billing = window.billing;
        if (!billing || !billing.billingStatus) {
            return false;
        }
        
        const status = billing.billingStatus;
        const difference = actualAmount - reservation.amount;
        
        if (difference !== 0) {
            // Adjust credits based on actual vs reserved
            const currentBalance = status.limits?.ai_credits?.balance || 0;
            status.limits.ai_credits.balance = currentBalance - difference;
            status.limits.ai_credits.canUse = status.limits.ai_credits.balance > 0;
            
            billing.cacheStatus(status);
            
            console.info('Credit adjustment made', {
                customer_id: customerId,
                reservation_id: reservationId,
                reserved: reservation.amount,
                actual: actualAmount,
                difference: difference,
                new_balance: status.limits.ai_credits.balance
            });
        }
        
        // Queue for server sync (this would be handled by background sync)
        this.queueCreditSync(customerId, actualAmount, reservation.description, {
            reserved: reservation.amount,
            actual: actualAmount,
            reservation_id: reservationId
        });
        
        // Clear reservation
        this.reservations.delete(reservationId);
        return true;
    }
    
    /**
     * REVENUE PROTECTION: Release reservation on failure
     */
    static releaseReservation(customerId, reservationId) {
        const reservation = this.reservations.get(reservationId);
        if (!reservation) {
            return false;
        }
        
        const billing = window.billing;
        if (!billing || !billing.billingStatus) {
            return false;
        }
        
        // Restore reserved credits
        const status = billing.billingStatus;
        const currentBalance = status.limits?.ai_credits?.balance || 0;
        
        status.limits.ai_credits.balance = currentBalance + reservation.amount;
        status.limits.ai_credits.canUse = true;
        
        billing.cacheStatus(status);
        
        // Clear reservation
        this.reservations.delete(reservationId);
        
        console.info('Credit reservation released', {
            customer_id: customerId,
            reservation_id: reservationId,
            amount_restored: reservation.amount,
            new_balance: status.limits.ai_credits.balance
        });
        
        return true;
    }
    
    /**
     * Queue credit operation for sync (placeholder for background sync)
     */
    static queueCreditSync(customerId, amount, description, metadata = {}) {
        // In a full implementation, this would queue the operation for background sync
        console.log('Credit sync queued', {
            customerId,
            amount,
            description,
            metadata
        });
    }
    
    /**
     * Cleanup expired reservations
     */
    static cleanupExpiredReservations() {
        const now = new Date();
        for (const [reservationId, reservation] of this.reservations.entries()) {
            if (reservation.expiresAt <= now) {
                this.releaseReservation(reservation.customerId, reservationId);
                console.warn(`Expired reservation cleaned up: ${reservationId}`);
            }
        }
    }
}

// Cleanup expired reservations every 5 minutes
setInterval(() => {
    LocalCreditManager.cleanupExpiredReservations();
}, 5 * 60 * 1000);

// Export for global use
window.LocalBillingValidator = LocalBillingValidator;
window.LocalCreditManager = LocalCreditManager;