/**
 * SafariChat Billing Usage Examples
 * Practical implementation patterns for common operations
 */

/**
 * EXAMPLE 1: Contact Addition with Billing Check
 */
async function addNewContact(contactData) {
    const billing = window.billing;
    if (!billing || !billing.billingStatus) {
        alert('Billing system not available');
        return false;
    }
    
    const validation = LocalBillingValidator.canAddContact(billing.billingStatus);
    
    if (!validation.allowed) {
        switch (validation.reason) {
            case 'limit_reached':
                billing.showUpgradeModal('contacts');
                return false;
            
            case 'subscription_inactive':
                showSubscriptionRenewalModal();
                return false;
            
            case 'cache_expired':
                await billing.forceRefresh();
                return addNewContact(contactData); // Retry after refresh
            
            default:
                alert('Cannot add contact: ' + validation.reason);
                return false;
        }
    }
    
    // Proceed with contact creation
    try {
        const response = await fetch('/api/contacts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(contactData)
        });
        
        if (response.ok) {
            // Update local count for immediate UI feedback
            billing.billingStatus.limits.contacts.current += 1;
            billing.cacheStatus(billing.billingStatus);
            billing.configureContactUI(billing.billingStatus.permissions, billing.billingStatus.limits);
            
            console.log('✅ Contact added successfully');
            return true;
        } else {
            throw new Error('Failed to create contact');
        }
        
    } catch (error) {
        console.error('Contact creation failed:', error);
        alert('Failed to add contact. Please try again.');
        return false;
    }
}

/**
 * EXAMPLE 2: Product Creation with Billing Validation
 */
async function createNewProduct(productData) {
    const billing = window.billing;
    const validation = LocalBillingValidator.canAddProduct(billing.billingStatus);
    
    if (!validation.allowed) {
        if (validation.reason === 'limit_reached') {
            const nextPlan = billing.getRecommendedUpgrade(billing.billingStatus.subscription.plan, 'products');
            
            if (confirm(`Product limit reached! Upgrade to ${nextPlan} plan for more products?`)) {
                window.location.href = `/billing/upgrade?to=${nextPlan}`;
            }
            return false;
        }
        
        alert('Cannot create product: ' + validation.reason);
        return false;
    }
    
    // Create product
    try {
        const formData = new FormData();
        Object.keys(productData).forEach(key => {
            formData.append(key, productData[key]);
        });
        
        const response = await fetch('/products', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            // Update local product count
            billing.billingStatus.limits.products.current += 1;
            billing.cacheStatus(billing.billingStatus);
            billing.configureProductUI(billing.billingStatus.permissions, billing.billingStatus.limits);
            
            return true;
        } else {
            throw new Error('Product creation failed');
        }
        
    } catch (error) {
        console.error('Product creation error:', error);
        return false;
    }
}

/**
 * EXAMPLE 3: AI Message Processing with Credit Management
 */
async function sendAIMessage(messageText, conversationId) {
    const billing = window.billing;
    const customerId = billing.customerId;
    
    // Estimate credits needed (rough calculation)
    const estimatedTokens = messageText.length * 1.3;
    const creditsNeeded = Math.ceil(estimatedTokens / 3.846);
    
    // CRITICAL: Check AI permissions and credits
    const validation = await LocalBillingValidator.canUseAI(billing.billingStatus, creditsNeeded);
    
    if (!validation.allowed) {
        switch (validation.reason) {
            case 'insufficient_credits':
                showCreditTopUpModal(validation.needed, validation.available);
                return { error: 'insufficient_credits' };
            
            case 'permission_denied':
                billing.showUpgradeModal('ai');
                return { error: 'ai_not_in_plan' };
            
            case 'cache_refresh_required':
                await billing.forceRefresh();
                return sendAIMessage(messageText, conversationId); // Retry after refresh
            
            default:
                return { error: validation.reason };
        }
    }
    
    // REVENUE PROTECTION: Reserve credits before AI call
    const reservation = await LocalCreditManager.reserveCredits(customerId, creditsNeeded, `AI conversation ${conversationId}`);
    
    if (!reservation.success) {
        return { error: 'credit_reservation_failed' };
    }
    
    try {
        // Call AI API
        const response = await fetch('/api/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                message: messageText,
                conversation_id: conversationId,
                reservation_id: reservation.reservation_id
            })
        });
        
        if (!response.ok) {
            throw new Error('AI API call failed');
        }
        
        const aiResponse = await response.json();
        
        // REVENUE PROTECTION: Finalize credits with actual usage
        const actualTokens = aiResponse.usage?.total_tokens || estimatedTokens;
        const actualCredits = Math.ceil(actualTokens / 3.846);
        
        LocalCreditManager.finalizeCredits(customerId, reservation.reservation_id, actualCredits);
        
        // Update UI to show new credit balance
        billing.configureAIUI(billing.billingStatus.permissions, billing.billingStatus.limits);
        
        return {
            success: true,
            response: aiResponse.response,
            credits_used: actualCredits,
            tokens_used: actualTokens
        };
        
    } catch (error) {
        // REVENUE PROTECTION: Release reserved credits on failure
        LocalCreditManager.releaseReservation(customerId, reservation.reservation_id);
        
        console.error('AI message failed:', error);
        return { error: 'ai_call_failed' };
    }
}

/**
 * EXAMPLE 4: WhatsApp Message Handling with Limits Check
 */
async function handleIncomingWhatsAppMessage(messageData) {
    const billing = window.billing;
    
    // Check if can handle new contact
    if (isNewContact(messageData.from)) {
        const validation = LocalBillingValidator.canAddContact(billing.billingStatus);
        
        if (!validation.allowed) {
            // REVENUE PROTECTION: Log blocked contact for revenue tracking
            console.warn('New contact blocked due to limits', {
                phone: messageData.from,
                reason: validation.reason,
                current_contacts: validation.current,
                max_contacts: validation.max
            });
            
            // Notify business owner
            showContactLimitNotification(messageData);
            
            // Send polite response to customer
            return sendCustomerResponse(messageData.from, 
                "Thank you for your message! We'll get back to you soon. 🙏"
            );
        }
    }
    
    // Check message sending permission
    const canSend = LocalBillingValidator.canSendMessage(billing.billingStatus);
    if (!canSend.allowed) {
        return handleMessageSendingBlocked(messageData);
    }
    
    // Process message normally
    return processWhatsAppMessage(messageData);
}

/**
 * EXAMPLE 5: Feature Access Check
 */
function initializeReportsFeature() {
    const billing = window.billing;
    const reportsSection = document.getElementById('sales-reports-section');
    
    if (!reportsSection) return;
    
    const permission = LocalBillingValidator.hasFeaturePermission(billing.billingStatus, 'sales_reports');
    
    if (!permission.allowed) {
        reportsSection.innerHTML = `
            <div class="feature-locked">
                <div class="lock-icon">🔒</div>
                <h3>Sales Reports</h3>
                <p>Upgrade to Pro or Premium plan to access detailed sales reports and analytics.</p>
                <button class="upgrade-btn" onclick="window.billing.showUpgradeModal('sales_reports')">
                    Upgrade Now
                </button>
            </div>
        `;
        return;
    }
    
    // Initialize reports normally
    loadSalesReports();
}

/**
 * EXAMPLE 6: Batch Operation with Bulk Validation
 */
async function bulkAddContacts(contactsArray) {
    const billing = window.billing;
    const status = billing.billingStatus;
    
    // Check if we can add all contacts
    const currentContacts = status.limits.contacts.current;
    const maxContacts = status.limits.contacts.max;
    const availableSlots = maxContacts - currentContacts;
    
    if (contactsArray.length > availableSlots) {
        const nextPlan = billing.getRecommendedUpgrade(status.subscription.plan, 'contacts');
        
        if (confirm(`You can only add ${availableSlots} more contacts with your current plan. Upgrade to ${nextPlan} for unlimited contacts?`)) {
            window.location.href = `/billing/upgrade?to=${nextPlan}`;
            return;
        }
        
        // Truncate to available slots
        contactsArray = contactsArray.slice(0, availableSlots);
    }
    
    // Process contacts in batches
    const results = [];
    for (let i = 0; i < contactsArray.length; i++) {
        const result = await addNewContact(contactsArray[i]);
        results.push(result);
        
        // Update progress
        updateBulkProgress(i + 1, contactsArray.length);
    }
    
    return results;
}

/**
 * HELPER FUNCTIONS
 */

function showCreditTopUpModal(needed, available) {
    const deficit = needed - available;
    const modal = document.createElement('div');
    modal.className = 'billing-modal-overlay';
    modal.innerHTML = `
        <div class="billing-modal">
            <div class="modal-header">
                <h3>💳 Insufficient AI Credits</h3>
            </div>
            <div class="modal-body">
                <p>You need ${needed} credits but only have ${available}.</p>
                <p>You need ${deficit} more credits to continue.</p>
                <div class="credit-options">
                    <button class="credit-option" onclick="buyCreditPackage(1000)">
                        Buy 1,000 credits - TZS 2,600
                    </button>
                    <button class="credit-option" onclick="buyCreditPackage(5000)">
                        Buy 5,000 credits - TZS 13,000
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showContactLimitNotification(messageData) {
    // This would send notification to business owner
    console.log('Contact limit notification:', messageData);
}

function isNewContact(phoneNumber) {
    // Check if this phone number exists in contacts
    // This would typically check against your contacts database
    return !window.existingContacts?.includes(phoneNumber);
}

// Initialize examples when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Set up event handlers for forms
    const addContactForm = document.getElementById('add-contact-form');
    if (addContactForm) {
        addContactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const contactData = Object.fromEntries(formData);
            await addNewContact(contactData);
        });
    }
    
    // Initialize feature-dependent UI elements
    setTimeout(() => {
        if (window.billing && window.billing.billingStatus) {
            initializeReportsFeature();
        }
    }, 1000);
});