/**
 * SafariChat AI Sales Agent Landing Page JavaScript
 * Enhanced interactivity and functionality
 */

class LandingPageManager {
    constructor() {
        this.currentLocale = 'en';
        this.currentCurrency = 'USD';
        this.chatMessages = [];
        this.isTyping = false;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeAnimations();
        this.loadUserPreferences();
        this.trackPageView();
    }

    setupEventListeners() {
        // Language switcher
        const languageSelector = document.getElementById('languageSelector');
        if (languageSelector) {
            languageSelector.addEventListener('change', (e) => {
                this.changeLanguage(e.target.value);
            });
        }

        // Chat functionality
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.querySelector('.chat-send-btn');
        
        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.sendChatMessage();
                }
            });
        }

        if (chatSendBtn) {
            chatSendBtn.addEventListener('click', () => {
                this.sendChatMessage();
            });
        }

        // ROI Calculator
        const roiInputs = document.querySelectorAll('.roi-input');
        roiInputs.forEach(input => {
            input.addEventListener('input', this.debounce(() => {
                this.calculateROI();
            }, 500));
        });

        // Contact form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => {
                this.handleContactSubmit(e);
            });
        }

        // Pricing plan selection
        const pricingButtons = document.querySelectorAll('.pricing-select-btn');
        pricingButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handlePricingSelection(e.target.dataset.plan);
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Scroll tracking for analytics
        window.addEventListener('scroll', this.throttle(() => {
            this.trackScrollPosition();
        }, 1000));
    }

    initializeAnimations() {
        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    
                    // Track section views
                    this.trackSectionView(entry.target.id || entry.target.className);
                }
            });
        }, observerOptions);

        // Observe all major sections
        document.querySelectorAll('section, .feature-card, .pricing-card').forEach(el => {
            observer.observe(el);
        });
    }

    loadUserPreferences() {
        // Load saved language preference
        const savedLocale = localStorage.getItem('safarichat_locale');
        if (savedLocale && savedLocale !== this.currentLocale) {
            this.changeLanguage(savedLocale);
        }

        // Load saved currency preference
        const savedCurrency = localStorage.getItem('safarichat_currency');
        if (savedCurrency) {
            this.currentCurrency = savedCurrency;
        }

        // Auto-detect timezone and suggest appropriate currency
        this.detectOptimalCurrency();
    }

    changeLanguage(locale) {
        if (locale === this.currentLocale) return;

        // Save preference
        localStorage.setItem('safarichat_locale', locale);
        this.currentLocale = locale;

        // Track language change
        this.trackInteraction('language_change', { 
            from: this.currentLocale, 
            to: locale 
        });

        // Redirect to new locale URL
        const currentPath = window.location.pathname;
        const newUrl = locale === 'en' ? '/' : `/${locale}`;
        window.location.href = newUrl;
    }

    async sendChatMessage() {
        const input = document.getElementById('chatInput');
        const messagesContainer = document.getElementById('chatMessages');
        
        if (!input || !messagesContainer) return;

        const message = input.value.trim();
        if (!message || this.isTyping) return;

        // Add user message
        this.addChatMessage(message, 'user');
        input.value = '';

        // Show typing indicator
        this.showTypingIndicator();

        try {
            const response = await fetch('/demo-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({ 
                    message: message,
                    locale: this.currentLocale 
                })
            });

            const data = await response.json();
            
            // Remove typing indicator and add AI response
            this.hideTypingIndicator();
            this.addChatMessage(data.response, 'ai');

            // Track chat interaction
            this.trackInteraction('chat_message', { 
                message_length: message.length,
                response_received: true 
            });

        } catch (error) {
            console.error('Chat error:', error);
            this.hideTypingIndicator();
            this.addChatMessage('Sorry, I\'m having trouble responding right now. Please try again.', 'ai');
            
            this.trackInteraction('chat_error', { error: error.message });
        }
    }

    addChatMessage(message, sender) {
        const messagesContainer = document.getElementById('chatMessages');
        if (!messagesContainer) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-bubble ${sender}`;
        messageDiv.textContent = message;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Store message for context
        this.chatMessages.push({ message, sender, timestamp: Date.now() });
    }

    showTypingIndicator() {
        const messagesContainer = document.getElementById('chatMessages');
        if (!messagesContainer) return;

        this.isTyping = true;
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chat-bubble ai typing-indicator';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = 'Typing<span class="dots">...</span>';
        
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Animate dots
        this.animateTypingDots();
    }

    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
        this.isTyping = false;
    }

    animateTypingDots() {
        const dots = document.querySelector('#typingIndicator .dots');
        if (!dots) return;

        let dotCount = 0;
        const interval = setInterval(() => {
            dotCount = (dotCount + 1) % 4;
            dots.textContent = '.'.repeat(dotCount);

            if (!document.getElementById('typingIndicator')) {
                clearInterval(interval);
            }
        }, 500);
    }

    async calculateROI() {
        const inputs = {
            business_type: document.getElementById('businessType')?.value || 'other',
            team_size: parseInt(document.getElementById('teamSize')?.value) || 1,
            avg_deal_size: parseInt(document.getElementById('avgDealSize')?.value) || 1000,
            monthly_leads: parseInt(document.getElementById('monthlyLeads')?.value) || 100,
            conversion_rate: parseFloat(document.getElementById('conversionRate')?.value) || 10,
            hourly_wage: parseFloat(document.getElementById('hourlyWage')?.value) || 25
        };

        const resultsContainer = document.getElementById('roiResults');
        if (!resultsContainer) return;

        // Show loading state
        resultsContainer.innerHTML = '<div class="text-center loading">Calculating your ROI...</div>';

        try {
            const response = await fetch('/calculate-roi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(inputs)
            });

            const data = await response.json();
            this.displayROIResults(data.results);

            // Track ROI calculation
            this.trackInteraction('roi_calculated', { 
                business_type: inputs.business_type,
                team_size: inputs.team_size,
                roi_percentage: data.results.roi_percentage 
            });

        } catch (error) {
            console.error('ROI calculation error:', error);
            resultsContainer.innerHTML = '<div class="text-center text-red-600">Error calculating ROI. Please try again.</div>';
            
            this.trackInteraction('roi_error', { error: error.message });
        }
    }

    displayROIResults(results) {
        const container = document.getElementById('roiResults');
        if (!container) return;

        const currency = this.getCurrencySymbol();
        
        container.innerHTML = `
            <div class="space-y-6">
                <!-- Key Metrics -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-700">+${Math.round(results.roi_percentage)}%</div>
                        <div class="text-sm text-green-600 font-semibold">ROI Percentage</div>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-700">${currency}${this.formatNumber(results.net_roi)}</div>
                        <div class="text-sm text-blue-600 font-semibold">Net Annual Benefit</div>
                    </div>
                </div>

                <!-- Detailed Breakdown -->
                <div class="roi-result-card">
                    <h3 class="font-semibold text-gray-800 mb-3">Revenue Impact</h3>
                    <div class="roi-metric">
                        <span class="text-gray-600">Additional Annual Revenue:</span>
                        <span class="roi-value positive">${currency}${this.formatNumber(results.additional_revenue)}</span>
                    </div>
                    <div class="roi-metric">
                        <span class="text-gray-600">Annual Cost Savings:</span>
                        <span class="roi-value positive">${currency}${this.formatNumber(results.cost_savings)}</span>
                    </div>
                    <div class="roi-metric">
                        <span class="text-gray-600">Annual AI Investment:</span>
                        <span class="roi-value primary">${currency}${this.formatNumber(results.annual_ai_cost)}</span>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-lg text-center">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Bottom Line</h3>
                    <p class="text-gray-700">
                        Your AI Sales Agent will generate <strong class="text-green-700">${currency}${this.formatNumber(results.net_roi)}</strong> 
                        in additional annual profit, representing a <strong class="text-green-700">${Math.round(results.roi_percentage)}% return</strong> 
                        on your AI investment.
                    </p>
                </div>
            </div>
        `;
    }

    async handleContactSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('/contact-submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Thank you! We\'ll be in touch soon.', 'success');
                form.reset();
                
                // Track successful contact submission
                this.trackInteraction('contact_submitted', {
                    industry: data.industry,
                    company_size: data.company ? 'provided' : 'not_provided'
                });
            } else {
                this.showNotification('Something went wrong. Please try again.', 'error');
            }

        } catch (error) {
            console.error('Contact form error:', error);
            this.showNotification('Something went wrong. Please try again.', 'error');
            
            this.trackInteraction('contact_error', { error: error.message });
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }

    handlePricingSelection(plan) {
        // Track pricing plan interest
        this.trackInteraction('pricing_plan_selected', { plan: plan });

        // Scroll to contact form
        const contactSection = document.getElementById('contact');
        if (contactSection) {
            contactSection.scrollIntoView({ behavior: 'smooth' });
            
            // Pre-fill form with plan info
            const messageField = document.querySelector('textarea[name="message"]');
            if (messageField && !messageField.value) {
                messageField.value = `I'm interested in the ${plan.charAt(0).toUpperCase() + plan.slice(1)} plan. Please provide more information.`;
            }
        }
    }

    detectOptimalCurrency() {
        try {
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const currencyMap = {
                'Africa/Dar_es_Salaam': 'TSH',
                'Africa/Lagos': 'NGN',
                'America/Sao_Paulo': 'BRL',
                'Asia/Jakarta': 'IDR',
                'Asia/Kolkata': 'INR',
                'Europe/': 'EUR',
                'America/New_York': 'USD'
            };

            for (const [tz, currency] of Object.entries(currencyMap)) {
                if (timezone.includes(tz)) {
                    this.currentCurrency = currency;
                    localStorage.setItem('safarichat_currency', currency);
                    break;
                }
            }
        } catch (error) {
            console.log('Could not detect timezone, using default currency');
        }
    }

    trackPageView() {
        this.trackInteraction('page_view', {
            page: 'landing',
            locale: this.currentLocale,
            currency: this.currentCurrency,
            referrer: document.referrer,
            user_agent: navigator.userAgent.substring(0, 200)
        });
    }

    trackSectionView(section) {
        this.trackInteraction('section_view', { 
            section: section,
            locale: this.currentLocale 
        });
    }

    trackScrollPosition() {
        const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
        
        // Track at 25%, 50%, 75%, 100%
        if ([25, 50, 75, 100].includes(scrollPercent)) {
            this.trackInteraction('scroll_depth', { 
                depth: scrollPercent,
                page: 'landing' 
            });
        }
    }

    async trackInteraction(event, data = {}) {
        try {
            await fetch('/api/track-interaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({
                    event: event,
                    page: 'landing',
                    data: {
                        ...data,
                        timestamp: Date.now(),
                        session_id: this.getSessionId()
                    }
                })
            });
        } catch (error) {
            console.log('Analytics tracking error:', error);
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    formatNumber(number) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
    }

    getCurrencySymbol() {
        const symbols = {
            'TSH': 'TSh ',
            'USD': '$',
            'BRL': 'R$',
            'INR': '₹',
            'NGN': '₦',
            'IDR': 'Rp',
            'EUR': '€'
        };
        return symbols[this.currentCurrency] || '$';
    }

    getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    getSessionId() {
        let sessionId = localStorage.getItem('safarichat_session_id');
        if (!sessionId) {
            sessionId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('safarichat_session_id', sessionId);
        }
        return sessionId;
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, wait) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, wait);
            }
        };
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.landingPageManager = new LandingPageManager();
});

// Additional utility functions for global use
window.scrollToDemo = () => {
    document.getElementById('demo')?.scrollIntoView({ behavior: 'smooth' });
};

window.scrollToROI = () => {
    document.getElementById('roi')?.scrollIntoView({ behavior: 'smooth' });
};

window.scrollToPricing = () => {
    document.getElementById('pricing')?.scrollIntoView({ behavior: 'smooth' });
};

// Export for use in other scripts if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LandingPageManager;
}