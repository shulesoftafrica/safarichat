/**
 * Booking Calendar JavaScript
 * Handles slot selection, availability checking, and dynamic form interactions
 */

// Booking Calendar Manager
const BookingCalendar = {
    /**
     * Initialize the booking calendar functionality
     */
    init() {
        this.initDatePickers();
        this.initSlotSelection();
        this.initWorkingHoursValidation();
    },

    /**
     * Initialize date pickers with restrictions
     */
    initDatePickers() {
        const today = new Date().toISOString().split('T')[0];
        const datePickers = document.querySelectorAll('input[type="date"]');
        
        datePickers.forEach(picker => {
            if (picker.hasAttribute('data-min-advance-hours')) {
                const minAdvanceHours = parseInt(picker.getAttribute('data-min-advance-hours'));
                const minDate = new Date();
                minDate.setHours(minDate.getHours() + minAdvanceHours);
                picker.min = minDate.toISOString().split('T')[0];
            }
            
            if (picker.hasAttribute('data-max-advance-days')) {
                const maxAdvanceDays = parseInt(picker.getAttribute('data-max-advance-days'));
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + maxAdvanceDays);
                picker.max = maxDate.toISOString().split('T')[0];
            }
        });
    },

    /**
     * Initialize slot selection and availability checking
     */
    initSlotSelection() {
        const calendarSelector = document.getElementById('calendar_selector');
        const dateSelector = document.getElementById('date_selector');
        const slotsContainer = document.getElementById('available_slots');

        if (calendarSelector && dateSelector && slotsContainer) {
            const loadSlots = () => {
                const calendarId = calendarSelector.value;
                const date = dateSelector.value;

                if (calendarId && date) {
                    this.fetchAvailableSlots(calendarId, date, slotsContainer);
                }
            };

            calendarSelector.addEventListener('change', loadSlots);
            dateSelector.addEventListener('change', loadSlots);
        }
    },

    /**
     * Fetch available slots from API
     */
    async fetchAvailableSlots(calendarId, date, container) {
        container.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading available slots...</p></div>';

        try {
            const response = await fetch(`/api/booking-slots/calendars/${calendarId}/available?date=${date}`, {
                headers: {
                    'Authorization': `Bearer ${this.getApiToken()}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch slots');
            }

            const data = await response.json();
            this.renderSlots(data.available_slots, container);
        } catch (error) {
            console.error('Error fetching slots:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Failed to load available slots. Please try again.
                </div>
            `;
        }
    },

    /**
     * Render available slots
     */
    renderSlots(slots, container) {
        if (!slots || slots.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-calendar-times mr-2"></i>
                    No available slots for this date.
                </div>
            `;
            return;
        }

        let html = '<div class="row">';
        slots.forEach(slot => {
            html += `
                <div class="col-md-3 mb-3">
                    <button type="button" 
                            class="btn btn-outline-primary btn-block slot-button" 
                            data-start="${slot.start}"
                            data-end="${slot.end}"
                            onclick="BookingCalendar.selectSlot(this)">
                        <i class="far fa-clock mr-2"></i>
                        ${this.formatTime(slot.start)}
                    </button>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    },

    /**
     * Select a time slot
     */
    selectSlot(button) {
        // Remove previous selection
        document.querySelectorAll('.slot-button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        // Mark as selected
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');

        // Update hidden fields
        const startTime = button.getAttribute('data-start');
        const endTime = button.getAttribute('data-end');

        const startInput = document.getElementById('selected_start_time');
        const endInput = document.getElementById('selected_end_time');

        if (startInput) startInput.value = startTime;
        if (endInput) endInput.value = endTime;
    },

    /**
     * Validate working hours don't overlap
     */
    initWorkingHoursValidation() {
        const workingHoursInputs = document.querySelectorAll('[name^="working_hours"]');
        
        workingHoursInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.validateWorkingHours();
            });
        });
    },

    /**
     * Validate working hours
     */
    validateWorkingHours() {
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        let hasErrors = false;

        days.forEach((day, index) => {
            const enabledCheckbox = document.getElementById(`day_enabled_${index}`);
            if (!enabledCheckbox || !enabledCheckbox.checked) return;

            const startInput = document.querySelector(`input[name="working_hours[${index}][start]"]`);
            const endInput = document.querySelector(`input[name="working_hours[${index}][end]"]`);

            if (startInput && endInput) {
                const start = startInput.value;
                const end = endInput.value;

                if (start && end && start >= end) {
                    endInput.setCustomValidity('End time must be after start time');
                    hasErrors = true;
                } else {
                    endInput.setCustomValidity('');
                }
            }
        });

        return !hasErrors;
    },

    /**
     * Format time for display
     */
    formatTime(datetime) {
        const date = new Date(datetime);
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    },

    /**
     * Get API token from meta tag or localStorage
     */
    getApiToken() {
        const metaToken = document.querySelector('meta[name="api-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }
        return localStorage.getItem('api_token') || '';
    },

    /**
     * Validate booking form before submission
     */
    validateBookingForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!this.validateWorkingHours()) {
            isValid = false;
        }

        return isValid;
    }
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => BookingCalendar.init());
} else {
    BookingCalendar.init();
}

// Export for use in other scripts
window.BookingCalendar = BookingCalendar;
