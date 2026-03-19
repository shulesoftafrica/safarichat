/**
 * Phone Number Validation Module
 * Provides real-time validation for phone number input fields
 * 
 * Usage:
 *   <input type="tel" class="phone-validation" name="phone_number" />
 *   
 * Or manually initialize:
 *   PhoneValidator.init('#my-phone-input');
 */

const PhoneValidator = {
    /**
     * International phone number pattern
     * Allows: +, digits, spaces, hyphens, parentheses, dots
     * Format: +X(XXX)XXX-XXXX or variations
     */
    pattern: /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/,
    
    /**
     * Minimum and maximum digit counts
     */
    minDigits: 7,
    maxDigits: 15,
    
    /**
     * Initialize phone validation on specified elements
     * @param {string} selector - CSS selector for input fields
     */
    init: function(selector = '.phone-validation') {
        const phoneInputs = document.querySelectorAll(selector);
        
        phoneInputs.forEach(input => {
            this.attachValidation(input);
        });
    },
    
    /**
     * Attach validation event listeners to an input
     * @param {HTMLElement} input - The phone input element
     */
    attachValidation: function(input) {
        // Real-time validation on input
        input.addEventListener('input', (e) => {
            this.sanitizeInput(e.target);
            this.validateInput(e.target);
        });
        
        // Final validation on blur
        input.addEventListener('blur', (e) => {
            this.validateInput(e.target, true);
        });
        
        // Prevent pasting invalid characters
        input.addEventListener('paste', (e) => {
            setTimeout(() => {
                this.sanitizeInput(e.target);
                this.validateInput(e.target);
            }, 10);
        });
        
        // Set input attributes
        input.setAttribute('type', 'tel');
        input.setAttribute('pattern', this.pattern.source);
        input.setAttribute('title', 'Phone number format: +1234567890 or (123) 456-7890');
        
        // Add Bootstrap/custom classes for styling
        if (!input.classList.contains('form-control')) {
            input.classList.add('form-control');
        }
    },
    
    /**
     * Sanitize input by removing invalid characters in real-time
     * Allows only: digits, +, -, (, ), space, dot
     * @param {HTMLElement} input - The input element
     */
    sanitizeInput: function(input) {
        const cursorPosition = input.selectionStart;
        const originalLength = input.value.length;
        
        // Remove invalid characters (keep only allowed ones)
        const sanitized = input.value.replace(/[^0-9+\-\(\)\s\.]/g, '');
        
        if (sanitized !== input.value) {
            input.value = sanitized;
            
            // Restore cursor position accounting for removed characters
            const newPosition = cursorPosition - (originalLength - sanitized.length);
            input.setSelectionRange(newPosition, newPosition);
        }
    },
    
    /**
     * Validate phone input and show visual feedback
     * @param {HTMLElement} input - The input element
     * @param {boolean} showError - Whether to show error message
     */
    validateInput: function(input, showError = false) {
        const value = input.value.trim();
        const feedbackElement = this.getFeedbackElement(input);
        
        // Clear previous validation state
        this.clearValidationState(input);
        
        // Empty value - no validation (unless required)
        if (!value) {
            if (input.hasAttribute('required') && showError) {
                this.setInvalidState(input, 'Phone number is required', feedbackElement);
            }
            return;
        }
        
        // Extract digits only for length validation
        const digits = value.replace(/[^0-9]/g, '');
        
        // Validate digit count
        if (digits.length < this.minDigits) {
            if (showError) {
                this.setInvalidState(input, `Phone number must have at least ${this.minDigits} digits`, feedbackElement);
            }
            return;
        }
        
        if (digits.length > this.maxDigits) {
            this.setInvalidState(input, `Phone number must have at most ${this.maxDigits} digits`, feedbackElement);
            return;
        }
        
        // Validate format pattern
        if (!this.pattern.test(value)) {
            if (showError) {
                this.setInvalidState(input, 'Invalid phone format. Use: +1234567890 or (123) 456-7890', feedbackElement);
            }
            return;
        }
        
        // Valid phone number
        this.setValidState(input, feedbackElement);
    },
    
    /**
     * Check if a phone number is valid
     * @param {string} phoneNumber - The phone number to validate
     * @returns {boolean}
     */
    isValid: function(phoneNumber) {
        if (!phoneNumber) return false;
        
        const digits = phoneNumber.replace(/[^0-9]/g, '');
        
        if (digits.length < this.minDigits || digits.length > this.maxDigits) {
            return false;
        }
        
        return this.pattern.test(phoneNumber);
    },
    
    /**
     * Set valid state with visual feedback
     * @param {HTMLElement} input
     * @param {HTMLElement} feedbackElement
     */
    setValidState: function(input, feedbackElement) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        
        if (feedbackElement) {
            feedbackElement.textContent = '✓ Valid phone number';
            feedbackElement.className = 'valid-feedback d-block';
        }
    },
    
    /**
     * Set invalid state with error message
     * @param {HTMLElement} input
     * @param {string} message
     * @param {HTMLElement} feedbackElement
     */
    setInvalidState: function(input, message, feedbackElement) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        
        if (feedbackElement) {
            feedbackElement.textContent = message;
            feedbackElement.className = 'invalid-feedback d-block';
        }
    },
    
    /**
     * Clear validation state
     * @param {HTMLElement} input
     */
    clearValidationState: function(input) {
        input.classList.remove('is-valid', 'is-invalid');
        const feedbackElement = this.getFeedbackElement(input);
        if (feedbackElement) {
            feedbackElement.textContent = '';
            feedbackElement.className = 'form-text';
        }
    },
    
    /**
     * Get or create feedback element for validation messages
     * @param {HTMLElement} input
     * @returns {HTMLElement}
     */
    getFeedbackElement: function(input) {
        let feedback = input.parentElement.querySelector('.phone-validation-feedback');
        
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'phone-validation-feedback form-text';
            input.parentElement.appendChild(feedback);
        }
        
        return feedback;
    },
    
    /**
     * Format phone number with standard formatting
     * @param {string} phoneNumber - Raw phone number
     * @param {string} format - Format style ('international' or 'us')
     * @returns {string}
     */
    format: function(phoneNumber, format = 'international') {
        const digits = phoneNumber.replace(/[^0-9]/g, '');
        
        if (format === 'us' && digits.length === 10) {
            // US format: (XXX) XXX-XXXX
            return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
        } else if (format === 'international') {
            // International: +X XXX XXX XXXX
            if (phoneNumber.startsWith('+')) {
                return phoneNumber;
            }
            return `+${digits}`;
        }
        
        return phoneNumber;
    },
    
    /**
     * Sanitize phone number for storage (digits and + only)
     * @param {string} phoneNumber
     * @returns {string}
     */
    sanitize: function(phoneNumber) {
        if (!phoneNumber) return '';
        
        // Keep only digits and leading +
        let sanitized = phoneNumber.replace(/[^0-9+]/g, '');
        
        // Ensure only one + at the beginning
        if (sanitized.includes('+')) {
            sanitized = '+' + sanitized.replace(/\+/g, '');
        }
        
        return sanitized;
    }
};

// Auto-initialize on DOM ready
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            PhoneValidator.init();
        });
    } else {
        // DOM already loaded
        PhoneValidator.init();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhoneValidator;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PhoneValidator = PhoneValidator;
}
