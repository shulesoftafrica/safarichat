/**
 * SafariChat Lead Error Handler
 * 
 * Provides comprehensive error handling for lead/customer data operations
 * Handles AJAX form submissions, validation errors, and server errors
 * 
 * Usage:
 * - Automatically attaches to forms with class 'lead-form' or 'customer-form'
 * - Can be manually invoked: LeadErrorHandler.handleFormSubmit(form, options)
 * - Can display errors: LeadErrorHandler.displayErrors(errors, container)
 */

const LeadErrorHandler = {
    /**
     * Initialize error handling for all forms
     */
    init() {
        console.log('LeadErrorHandler initialized');
        
        // Attach to all relevant forms
        document.querySelectorAll('.lead-form, .customer-form, .guest-form').forEach(form => {
            this.attachToForm(form);
        });
        
        // Setup global AJAX error handler
        this.setupGlobalErrorHandler();
    },

    /**
     * Attach error handling to a specific form
     * @param {HTMLFormElement} form 
     */
    attachToForm(form) {
        const originalSubmitHandler = form.onsubmit;
        
        form.onsubmit = (e) => {
            // If there's a custom submit handler, let it run first
            if (originalSubmitHandler && originalSubmitHandler.call(form, e) === false) {
                return false;
            }
            
            // Only handle if form is using AJAX
            if (form.dataset.ajax !== 'true' && !form.classList.contains('ajax-form')) {
                return true; // Let normal form submission proceed
            }
            
            e.preventDefault();
            this.handleFormSubmit(form);
            return false;
        };
    },

    /**
     * Handle AJAX form submission with error handling
     * @param {HTMLFormElement} form 
     * @param {Object} options 
     */
    async handleFormSubmit(form, options = {}) {
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        const url = options.url || form.action;
        const method = options.method || form.method || 'POST';
        
        // Disable submit button and show loading state
        if (submitButton) {
            submitButton.disabled = true;
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitButton.dataset.originalText = originalText;
        }
        
        // Clear previous errors
        this.clearErrors(form);
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // Success
                this.handleSuccess(data, form, options);
            } else {
                // Error response
                this.handleError(data, form, response.status);
            }
            
        } catch (error) {
            // Network or parsing error
            this.handleNetworkError(error, form);
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.dataset.originalText || submitButton.innerHTML;
            }
        }
    },

    /**
     * Handle successful response
     * @param {Object} data 
     * @param {HTMLFormElement} form 
     * @param {Object} options 
     */
    handleSuccess(data, form, options) {
        console.log('Lead operation successful', data);
        
        // Show success message
        if (data.message) {
            this.showSuccessMessage(data.message, form);
        }
        
        // Show warnings if any
        if (data.warnings && data.warnings.length > 0) {
            this.showWarnings(data.warnings, form);
        }
        
        // Call custom success callback if provided
        if (options.onSuccess) {
            options.onSuccess(data);
        }
        
        // Redirect if URL provided
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        }
        
        // Reset form if requested
        if (options.resetOnSuccess !== false) {
            form.reset();
        }
        
        // Trigger custom event
        form.dispatchEvent(new CustomEvent('leadOperationSuccess', { detail: data }));
    },

    /**
     * Handle error response
     * @param {Object} data 
     * @param {HTMLFormElement} form 
     * @param {Number} status 
     */
    handleError(data, form, status) {
        console.error('Lead operation failed', data);
        
        // Display general error message
        const message = data.message || 'Failed to save customer data. Please check the form and try again.';
        this.showErrorMessage(message, form);
        
        // Display specific field errors
        if (data.errors) {
            if (typeof data.errors === 'object') {
                // Laravel validation errors format
                this.displayFieldErrors(data.errors, form);
            } else if (Array.isArray(data.errors)) {
                // Array of error strings
                this.displayErrorList(data.errors, form);
            }
        }
        
        // Handle specific status codes
        if (status === 422) {
            // Validation error - already handled above
            this.showErrorMessage('Please correct the highlighted fields', form);
        } else if (status === 403) {
            this.showErrorMessage('You do not have permission to perform this action', form);
        } else if (status === 404) {
            this.showErrorMessage('The requested resource was not found', form);
        } else if (status === 500) {
            this.showErrorMessage('Server error. Please try again or contact support if the problem persists.', form);
        }
        
        // Trigger custom event
        form.dispatchEvent(new CustomEvent('leadOperationError', { detail: { data, status } }));
    },

    /**
     * Handle network errors
     * @param {Error} error 
     * @param {HTMLFormElement} form 
     */
    handleNetworkError(error, form) {
        console.error('Network error', error);
        this.showErrorMessage(
            'Network error. Please check your internet connection and try again.',
            form
        );
    },

    /**
     * Display validation errors for specific fields
     * @param {Object} errors - Laravel validation errors object
     * @param {HTMLFormElement} form 
     */
    displayFieldErrors(errors, form) {
        Object.keys(errors).forEach(fieldName => {
            const messages = Array.isArray(errors[fieldName]) ? errors[fieldName] : [errors[fieldName]];
            const field = form.querySelector(`[name="${fieldName}"]`);
            
            if (field) {
                // Mark field as invalid
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                
                // Create or update error message element
                let errorElement = field.parentElement.querySelector('.invalid-feedback');
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'invalid-feedback';
                    field.parentElement.appendChild(errorElement);
                }
                
                errorElement.textContent = messages.join(', ');
                errorElement.style.display = 'block';
                
                // Add focus listener to clear error
                field.addEventListener('focus', () => {
                    field.classList.remove('is-invalid');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                }, { once: true });
            }
        });
    },

    /**
     * Display list of general errors
     * @param {Array} errors 
     * @param {HTMLFormElement} form 
     */
    displayErrorList(errors, form) {
        const errorContainer = this.getOrCreateErrorContainer(form);
        const errorList = document.createElement('ul');
        errorList.className = 'mb-0';
        
        errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            errorList.appendChild(li);
        });
        
        errorContainer.querySelector('.alert-body')?.remove();
        const body = document.createElement('div');
        body.className = 'alert-body';
        body.appendChild(errorList);
        errorContainer.appendChild(body);
    },

    /**
     * Show error message
     * @param {String} message 
     * @param {HTMLFormElement} form 
     */
    showErrorMessage(message, form) {
        const errorContainer = this.getOrCreateErrorContainer(form);
        errorContainer.querySelector('.alert-body')?.remove();
        
        const body = document.createElement('div');
        body.className = 'alert-body';
        body.textContent = message;
        errorContainer.appendChild(body);
    },

    /**
     * Show success message
     * @param {String} message 
     * @param {HTMLFormElement} form 
     */
    showSuccessMessage(message, form) {
        const container = this.getOrCreateMessageContainer(form, 'success');
        container.querySelector('.alert-body')?.remove();
        
        const body = document.createElement('div');
        body.className = 'alert-body';
        body.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        container.appendChild(body);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            container.style.display = 'none';
        }, 5000);
    },

    /**
     * Show warning messages
     * @param {Array} warnings 
     * @param {HTMLFormElement} form 
     */
    showWarnings(warnings, form) {
        const container = this.getOrCreateMessageContainer(form, 'warning');
        container.querySelector('.alert-body')?.remove();
        
        const body = document.createElement('div');
        body.className = 'alert-body';
        
        if (warnings.length === 1) {
            body.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${warnings[0]}`;
        } else {
            const list = document.createElement('ul');
            list.className = 'mb-0';
            warnings.forEach(warning => {
                const li = document.createElement('li');
                li.textContent = warning;
                list.appendChild(li);
            });
            body.appendChild(list);
        }
        
        container.appendChild(body);
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            container.style.display = 'none';
        }, 8000);
    },

    /**
     * Get or create error message container
     * @param {HTMLFormElement} form 
     * @returns {HTMLElement}
     */
    getOrCreateErrorContainer(form) {
        let container = form.querySelector('.lead-error-container');
        
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert alert-danger lead-error-container';
            container.style.marginBottom = '1rem';
            form.insertBefore(container, form.firstChild);
        }
        
        container.style.display = 'block';
        return container;
    },

    /**
     * Get or create message container
     * @param {HTMLFormElement} form 
     * @param {String} type - success, warning, info
     * @returns {HTMLElement}
     */
    getOrCreateMessageContainer(form, type = 'success') {
        let container = form.querySelector(`.lead-${type}-container`);
        
        if (!container) {
            container = document.createElement('div');
            container.className = `alert alert-${type} lead-${type}-container`;
            container.style.marginBottom = '1rem';
            form.insertBefore(container, form.firstChild);
        }
        
        container.style.display = 'block';
        return container;
    },

    /**
     * Clear all error messages
     * @param {HTMLFormElement} form 
     */
    clearErrors(form) {
        // Hide error containers
        form.querySelectorAll('.lead-error-container, .lead-success-container, .lead-warning-container')
            .forEach(container => {
                container.style.display = 'none';
            });
        
        // Remove field-level errors
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.style.display = 'none';
        });
    },

    /**
     * Setup global AJAX error handler for jQuery-based requests
     */
    setupGlobalErrorHandler() {
        // Check if jQuery is available
        if (typeof window.$ !== 'undefined' && window.$.ajaxSetup) {
            window.$(document).ajaxError((event, jqXHR, settings, thrownError) => {
                // Only handle lead-related requests
                if (settings.url && (
                    settings.url.includes('/lead') || 
                    settings.url.includes('/guest') ||
                    settings.url.includes('/appointment')
                )) {
                    console.error('AJAX error:', {
                        url: settings.url,
                        status: jqXHR.status,
                        error: thrownError,
                        response: jqXHR.responseJSON
                    });
                    
                    // Show global error notification
                    if (jqXHR.status === 500) {
                        this.showGlobalError('Server error occurred. Please try again or contact support.');
                    } else if (jqXHR.status === 0) {
                        this.showGlobalError('Network error. Please check your internet connection.');
                    }
                }
            });
        }
    },

    /**
     * Show global error notification (outside of forms)
     * @param {String} message 
     */
    showGlobalError(message) {
        // Try to use existing notification system
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                timer: 5000
            });
        } else {
            // Fallback to alert
            alert(message);
        }
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => LeadErrorHandler.init());
} else {
    LeadErrorHandler.init();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LeadErrorHandler;
}
if (typeof window !== 'undefined') {
    window.LeadErrorHandler = LeadErrorHandler;
}
