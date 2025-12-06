/**
 * Unified Notification API JavaScript Client
 * Provides frontend integration for WhatsApp notifications and session management
 */

class UnifiedNotificationAPI {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || 'https://notifcations.shulesoft.africa/api';
        this.bearerToken = config.bearerToken || 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn';
        this.localApiUrl = config.localApiUrl || '/api';
        this.useLocalApi = config.useLocalApi || true; // Prefer local API
        this.debug = config.debug || false;
    }

    // ===== NOTIFICATION METHODS =====

    /**
     * Send single WhatsApp message
     */
    async sendMessage(data) {
        const payload = {
            schema_name: data.schema_name || this.getCurrentUserSchema(),
            channel: 'whatsapp',
            to: data.to,
            message: data.message,
            priority: data.priority || 'normal',
            ...data
        };

        return this.makeRequest('POST', '/notifications/send', payload);
    }

    /**
     * Send bulk WhatsApp messages
     */
    async sendBulkMessages(data) {
        const payload = {
            schema_name: data.schema_name || this.getCurrentUserSchema(),
            channel: 'whatsapp',
            priority: data.priority || 'normal',
            rate_limit: data.rate_limit || 60,
            batch_size: data.batch_size || 50,
            messages: data.messages,
            ...data
        };

        return this.makeRequest('POST', '/notifications/bulk/send', payload);
    }

    /**
     * Get message status
     */
    async getMessageStatus(messageId) {
        return this.makeRequest('GET', `/notifications/${messageId}`);
    }

    /**
     * List all messages
     */
    async getMessages(filters = {}) {
        const params = new URLSearchParams({
            channel: 'whatsapp',
            ...filters
        }).toString();

        return this.makeRequest('GET', `/notifications?${params}`);
    }

    // ===== SESSION MANAGEMENT METHODS =====

    /**
     * Create WhatsApp session
     */
    async createSession(data) {
        const payload = {
            schema_name: data.schema_name || this.getCurrentUserSchema(),
            name: data.name,
            phone_number: data.phone_number,
            account_protection: data.account_protection ?? true,
            log_messages: data.log_messages ?? true,
            read_incoming_messages: data.read_incoming_messages ?? false,
            webhook_url: data.webhook_url,
            webhook_enabled: data.webhook_enabled ?? false,
            webhook_events: data.webhook_events || ['messages.received', 'session.status', 'messages.update'],
            ...data
        };

        return this.makeRequest('POST', '/wasender/sessions/create', payload);
    }

    /**
     * Connect session and get QR code
     */
    async connectSession(sessionId) {
        return this.makeRequest('POST', `/wasender/sessions/${sessionId}/connect`);
    }

    /**
     * Get session status
     */
    async getSessionStatus(sessionId) {
        return this.makeRequest('GET', `/wasender/sessions/${sessionId}/status`);
    }

    /**
     * Get all sessions
     */
    async getSessions() {
        return this.makeRequest('GET', '/wasender/sessions');
    }

    /**
     * Get single session
     */
    async getSession(sessionId) {
        return this.makeRequest('GET', `/wasender/sessions/${sessionId}`);
    }

    /**
     * Get QR code for session
     */
    async getQRCode(sessionId) {
        return this.makeRequest('GET', `/wasender/sessions/${sessionId}/qrcode`);
    }

    /**
     * Update session
     */
    async updateSession(sessionId, data) {
        return this.makeRequest('PUT', `/wasender/sessions/${sessionId}`, data);
    }

    /**
     * Delete session
     */
    async deleteSession(sessionId) {
        return this.makeRequest('DELETE', `/wasender/sessions/${sessionId}`);
    }

    // ===== UTILITY METHODS =====

    /**
     * Make API request with automatic fallback
     */
    async makeRequest(method, endpoint, data = null) {
        try {
            if (this.useLocalApi) {
                // Try local API first
                return await this.makeLocalRequest(method, endpoint, data);
            } else {
                // Use direct unified API
                return await this.makeDirectRequest(method, endpoint, data);
            }
        } catch (error) {
            if (this.debug) {
                console.error('API Request failed:', error);
            }

            // Fallback to alternative API if one fails
            if (this.useLocalApi) {
                if (this.debug) console.log('Local API failed, trying direct API...');
                return await this.makeDirectRequest(method, endpoint, data);
            } else {
                if (this.debug) console.log('Direct API failed, trying local API...');
                return await this.makeLocalRequest(method, endpoint, data);
            }
        }
    }

    /**
     * Make request to local Laravel API
     */
    async makeLocalRequest(method, endpoint, data = null) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCSRFToken()
            }
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(this.localApiUrl + endpoint, config);
        
        if (!response.ok) {
            throw new Error(`Local API error: ${response.status} ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Make request directly to unified notification API
     */
    async makeDirectRequest(method, endpoint, data = null) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.bearerToken}`
            }
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(this.baseUrl + endpoint, config);
        
        if (!response.ok) {
            throw new Error(`Direct API error: ${response.status} ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Get current user schema from page or localStorage
     */
    getCurrentUserSchema() {
        // Try to get from meta tag
        const metaSchema = document.querySelector('meta[name="user-schema"]');
        if (metaSchema) {
            return metaSchema.getAttribute('content');
        }

        // Try to get from localStorage
        const storedSchema = localStorage.getItem('user_schema');
        if (storedSchema) {
            return storedSchema;
        }

        // Try to get from global variable
        if (typeof window.userSchema !== 'undefined') {
            return window.userSchema;
        }

        // Default fallback
        return 'default';
    }

    /**
     * Get CSRF token for Laravel requests
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    /**
     * Format phone number for API
     */
    formatPhoneNumber(phone) {
        // Remove all non-digits
        const cleaned = phone.replace(/\D/g, '');
        
        // Add country code if missing (assuming +254 for Kenya)
        if (cleaned.length === 9 && !cleaned.startsWith('254')) {
            return '+254' + cleaned;
        }
        
        if (cleaned.length === 12 && cleaned.startsWith('254')) {
            return '+' + cleaned;
        }
        
        // Return with + prefix if not present
        return cleaned.startsWith('+') ? cleaned : '+' + cleaned;
    }

    /**
     * Validate message data before sending
     */
    validateMessageData(data) {
        const errors = [];

        if (!data.to) {
            errors.push('Phone number is required');
        } else if (!this.isValidPhoneNumber(data.to)) {
            errors.push('Invalid phone number format');
        }

        if (!data.message) {
            errors.push('Message content is required');
        }

        if (data.priority && !['low', 'normal', 'high', 'urgent'].includes(data.priority)) {
            errors.push('Invalid priority level');
        }

        return errors;
    }

    /**
     * Validate phone number format
     */
    isValidPhoneNumber(phone) {
        const phoneRegex = /^\+\d{10,15}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Handle API errors gracefully
     */
    handleError(error, context = 'API operation') {
        console.error(`${context} failed:`, error);
        
        let message = 'An unexpected error occurred';
        
        if (error.response && error.response.data) {
            message = error.response.data.message || error.response.data.error || message;
        } else if (error.message) {
            message = error.message;
        }

        return {
            success: false,
            error: message,
            details: error
        };
    }

    /**
     * Show loading state
     */
    showLoading(element, text = 'Processing...') {
        if (element) {
            element.disabled = true;
            element.textContent = text;
            element.classList.add('loading');
        }
    }

    /**
     * Hide loading state
     */
    hideLoading(element, originalText = 'Submit') {
        if (element) {
            element.disabled = false;
            element.textContent = originalText;
            element.classList.remove('loading');
        }
    }
}

// ===== UI HELPER FUNCTIONS =====

/**
 * Initialize notification API for the page
 */
function initializeNotificationAPI(config = {}) {
    window.NotificationAPI = new UnifiedNotificationAPI(config);
    
    // Add to global scope for easy access
    window.sendNotification = (data) => window.NotificationAPI.sendMessage(data);
    window.sendBulkNotifications = (data) => window.NotificationAPI.sendBulkMessages(data);
    window.createWhatsAppSession = (data) => window.NotificationAPI.createSession(data);
    
    console.log('Unified Notification API initialized');
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `notification toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
}

/**
 * Update message status in UI
 */
function updateMessageStatus(messageId, status, element = null) {
    const statusColors = {
        pending: 'orange',
        sent: 'blue',
        delivered: 'green',
        read: 'purple',
        failed: 'red'
    };

    const statusElement = element || document.querySelector(`[data-message-id="${messageId}"] .status`);
    
    if (statusElement) {
        statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusElement.className = `status ${status}`;
        statusElement.style.color = statusColors[status] || 'gray';
    }
}

/**
 * Format timestamp for display
 */
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // Less than 1 minute
    if (diff < 60000) {
        return 'Just now';
    }
    
    // Less than 1 hour
    if (diff < 3600000) {
        return `${Math.floor(diff / 60000)} minutes ago`;
    }
    
    // Less than 1 day
    if (diff < 86400000) {
        return `${Math.floor(diff / 3600000)} hours ago`;
    }
    
    // More than 1 day
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Auto-initialize if not in module context
if (typeof module === 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        // Check if config is provided via meta tags
        const configElement = document.querySelector('meta[name="notification-api-config"]');
        const config = configElement ? JSON.parse(configElement.getAttribute('content')) : {};
        
        initializeNotificationAPI(config);
    });
}