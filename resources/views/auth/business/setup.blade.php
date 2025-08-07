@extends('layouts.app')
@section('content')
<style>
.whatsapp-setup {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.setup-container {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.setup-header {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 2rem;
    text-align: center;
    position: relative;
}

.setup-header h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.setup-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.whatsapp-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.setup-content {
    padding: 2rem;
}

.step-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 0.5rem;
    position: relative;
}

.step.active {
    background: #25D366;
    color: white;
}

.step.completed {
    background: #128C7E;
    color: white;
}

.step.pending {
    background: #f0f0f0;
    color: #999;
}

.step-line {
    width: 50px;
    height: 3px;
    background: #f0f0f0;
}

.step-line.completed {
    background: #25D366;
}

.setup-step {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.setup-step.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #25D366;
    box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
}

.btn-whatsapp {
    width: 100%;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-whatsapp:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
}

.btn-whatsapp:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.alert-info {
    background: #e8f4fd;
    border: 1px solid #bee5eb;
    color: #0c5460;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.pairing-code-container {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 2rem;
    margin: 2rem 0;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.pairing-code-container:hover {
    border-color: #25D366;
    box-shadow: 0 5px 20px rgba(37, 211, 102, 0.1);
}

.pairing-code-display {
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    background: white;
    border: 2px dashed #dee2e6;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.pairing-code-display.has-code {
    border: 2px solid #25D366;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
}

.pairing-code {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    font-size: 2rem;
    font-weight: bold;
    letter-spacing: 0.5rem;
    font-family: 'Courier New', monospace;
    box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
    width: 100%;
}

.status-indicator {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin: 1rem 0;
}

.status-indicator.connecting {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.status-indicator.connected {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.status-indicator.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(0,0,0,.3);
    border-radius: 50%;
    border-top-color: #25D366;
    animation: spin 1s ease-in-out infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
}

.feature-list li::before {
    content: "✓";
    color: #25D366;
    font-weight: bold;
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.text-muted {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Debug panel styles */
#debug-panel {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.debug-info {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 1rem;
    margin: 0.5rem 0;
    font-family: monospace;
    font-size: 0.875rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #25D366;
    border-color: #25D366;
    color: white;
}

.btn-outline-primary {
    background: transparent;
    border-color: #25D366;
    color: #25D366;
}

.btn-outline-primary:hover {
    background: #25D366;
    color: white;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<div class="whatsapp-setup">
    <div class="setup-container">
        <!-- Header -->
        <div class="setup-header">
            <div class="whatsapp-icon">
                <i class="fab fa-whatsapp"></i>
            </div>
            <h2>Connect Your WhatsApp</h2>
            <p>Set up your business WhatsApp to start sending messages to customers</p>
        </div>

        <!-- Content -->
        <div class="setup-content">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step-1-indicator">1</div>
                <div class="step-line" id="line-1"></div>
                <div class="step pending" id="step-2-indicator">2</div>
                <div class="step-line" id="line-2"></div>
                <div class="step pending" id="step-3-indicator">3</div>
            </div>

            <!-- Step 1: Instance Setup -->
            <div class="setup-step active" id="step-1">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">Setup WhatsApp Instance</h4>
                
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>What is a WhatsApp Instance?</strong><br>
                    An instance is your dedicated WhatsApp connection that allows you to send and receive messages through our Platform. Each phone number needs its own instance.
                </div>

                <form id="instance-form">
                    <div class="form-group">
                        <label class="form-label">Instance Name</label>
                        <input type="text" id="instance-name" name="instance_name" class="form-control" 
                               placeholder="e.g., {{ Auth::user()->name ?? 'My Business' }} WhatsApp" 
                               value="{{ Auth::user()->name ?? '' }}" required>
                        <small class="text-muted">Give your WhatsApp connection a memorable name</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        
                                <div class="input-group">
                    <input
                        id="phone-number"
                        name="phone_number"
                        type="tel"
                        class="form-control @error('phone_number') is-invalid @enderror"
                        placeholder="Enter WhatsApp number"
                        value="{{ Auth::user()->phone ?? '' }}"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <input type="hidden" id="country_code2" name="country_code">
                    <input type="hidden" id="country_name2" name="country_name">
                    <input type="hidden" id="country_abbr2" name="country_abbr">
                </div>
                        <small class="text-muted">The phone number you want to connect to WhatsApp</small>
                    </div>

                    <div class="form-group" style="display:none">
                        <label class="form-label">Webhook URL (Optional)</label>
                        <input type="url" id="webhook-url" name="webhook_url" class="form-control" 
                               placeholder="https://yourwebsite.com/webhook" 
                               value="{{ url('api/waapi-webhook') }}">
                        <small class="text-muted">URL to receive incoming messages and status updates</small>
                    </div>

                    <button type="button" class="btn-whatsapp" id="create-instance-btn">
                        Create WhatsApp Instance
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>

            <!-- Step 2: WhatsApp Connection -->
            <div class="setup-step" id="step-2">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">Connect Your WhatsApp</h4>
                
                <!-- Pairing Code Instructions -->
                <div class="alert-info" style="margin-bottom: 2rem;">
                    <i class="fas fa-mobile-alt"></i>
                    <strong>Follow these steps to connect:</strong><br>
                    1. Open WhatsApp on your phone<br>
                    2. Go to Settings → Linked Devices<br>
                    3. Tap "Link a Device"<br>
                    4. Choose "Link with phone number instead"<br>
                    5. Enter the code shown below
                </div>

                <!-- Pairing Code Display -->
                <div class="pairing-code-container">
                    <div class="pairing-code-display" id="pairing-code-display">
                        <div style="text-align: center;">
                            <div class="spinner"></div>
                            <div>Generating Pairing Code...</div>
                            <small style="color: #666; margin-top: 0.5rem; display: block;">Please wait while we prepare your connection code</small>
                        </div>
                    </div>
                    <p style="text-align: center; margin: 1rem 0 0 0; color: #666; font-size: 0.9rem;" id="pairing-instructions">
                        <i class="fas fa-info-circle"></i> Enter this code in your WhatsApp mobile app
                    </p>
                </div>

                <div class="status-indicator connecting" id="connection-status">
                    <div class="spinner"></div>
                    <div>
                        <strong>Waiting for connection...</strong><br>
                        <small>Please enter the pairing code in your WhatsApp app</small>
                    </div>
                </div>
            </div>

            <!-- Step 3: Success -->
            <div class="setup-step" id="step-3">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">🎉 WhatsApp Connected Successfully!</h4>
                
                <div class="status-indicator connected">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                    <div>
                        <strong>Your WhatsApp is now connected</strong><br>
                        <small>Instance ID: <span id="instance-id-display"></span></small>
                    </div>
                </div>

                <div style="background: #f8f9fa; border-radius: 15px; padding: 1.5rem; margin: 1rem 0;">
                    <h6 style="margin-bottom: 1rem; color: #333;">What you can do now:</h6>
                    <ul class="feature-list">
                        <li>Send messages to your customers</li>
                        <li>Receive and manage incoming messages</li>
                        <li>Set up automated responses</li>
                        <li>Broadcast promotions to customer groups</li>
                        <li>Track message delivery and read status</li>
                    </ul>
                </div>

                <button type="button" class="btn-whatsapp" id="continue-to-dashboard">
                    Continue to Dashboard
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- External Styles and Scripts for intl-tel-input --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script type="text/javascript">
    // Wait for the intlTelInput library to load
    function initializePhoneValidation() {
        if (typeof window.intlTelInput === 'undefined') {
            console.log('intlTelInput not loaded yet, retrying...');
            setTimeout(initializePhoneValidation, 100);
            return;
        }
        
        validate_phone2();
    }

    // Intl-Tel-Input validation logic
    var validate_phone2 = function () {
        var input = document.querySelector("#phone-number"),
            errorMsg = document.querySelector("#error-msg2");

        if (!input) {
            console.error('Phone input element not found');
            return;
        }

        var errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

        try {
            var iti = window.intlTelInput(input, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                preferredCountries: ['tz'], // Set preferred country to Tanzania
                separateDialCode: true, // Show dial code separately
                initialCountry: "tz", // Default to Tanzania
                autoInsertDialCode: true,
                formatOnDisplay: true,
                nationalMode: false,
                placeholderNumberType: "MOBILE"
            });

            var reset = function () {
                input.classList.remove("is-invalid", "is-valid");
                errorMsg.innerHTML = "";
                errorMsg.style.display = "none";
            };

            // on blur: validate
            input.addEventListener('blur', function () {
                reset();
                if (input.value.trim()) {
                    if (iti.isValidNumber()) {
                        input.classList.add("is-valid");
                        // Update hidden fields
                        var countryData = iti.getSelectedCountryData();
                        var fullNumber = iti.getNumber();
                        
                        document.getElementById("country_code2").value = countryData.dialCode;
                        document.getElementById("country_name2").value = countryData.name;
                        document.getElementById("country_abbr2").value = countryData.iso2;
                        
                        // Update the input value with the full international number
                        input.value = fullNumber;
                    } else {
                        input.classList.add("is-invalid");
                        var errorCode = iti.getValidationError();
                        errorMsg.innerHTML = errorMap[errorCode] || "Invalid number";
                        errorMsg.style.display = "block";
                    }
                }
            });

            // on keyup / change flag: reset
            input.addEventListener('change', reset);
            input.addEventListener('keyup', reset);

            // Handle country change
            input.addEventListener('countrychange', function() {
                reset();
                var countryData = iti.getSelectedCountryData();
                document.getElementById("country_code2").value = countryData.dialCode;
                document.getElementById("country_name2").value = countryData.name;
                document.getElementById("country_abbr2").value = countryData.iso2;
            });

            console.log('Phone validation initialized successfully');
            
        } catch (error) {
            console.error('Error initializing intlTelInput:', error);
            // Fallback: just use regular input validation
            input.addEventListener('blur', function() {
                if (input.value.trim()) {
                    // Basic validation for phone numbers
                    var phoneRegex = /^[\+]?[\d\s\-\(\)]+$/;
                    if (phoneRegex.test(input.value.trim())) {
                        input.classList.add("is-valid");
                        input.classList.remove("is-invalid");
                        errorMsg.style.display = "none";
                    } else {
                        input.classList.add("is-invalid");
                        input.classList.remove("is-valid");
                        errorMsg.innerHTML = "Please enter a valid phone number";
                        errorMsg.style.display = "block";
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        // Initialize phone validation when document is ready
        initializePhoneValidation();

        // Login Button Loading State
        $('#loginForm').on('submit', function(e) {
            const phoneInput = $('#phone-number');
            const phoneValue = phoneInput.val().trim();
            
            // Basic validation before submit
            if (!phoneValue) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }

            const loginButton = $('#loginButton');
            const loadingSpinner = $('#loadingSpinner');

            loginButton.attr('disabled', true);
            loadingSpinner.removeClass('d-none');
            
            // Set a timeout to re-enable button in case of network issues
            setTimeout(function() {
                loginButton.attr('disabled', false);
                loadingSpinner.addClass('d-none');
            }, 10000); // 10 seconds timeout
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentStep = 1;
    let instanceId = null;
    let statusCheckInterval = null;
    
    // WAAPI Configuration
    const WAAPI_BASE_URL = 'https://waapi.app/api/v1';
    const WAAPI_TOKEN = '{{ config("app.waapi_token", "ftXEQe1S8hncxJVzHRrc3JqB9eHqUmG6WIctlMPy8435fd42") }}';
    
    // Step management
    function showStep(step) {
        $('.setup-step').removeClass('active');
        $(`#step-${step}`).addClass('active');
        
        // Update step indicators
        for (let i = 1; i <= 3; i++) {
            const indicator = $(`#step-${i}-indicator`);
            const line = $(`#line-${i}`);
            
            if (i < step) {
                indicator.removeClass('active pending').addClass('completed');
                if (line.length) line.addClass('completed');
            } else if (i === step) {
                indicator.removeClass('completed pending').addClass('active');
            } else {
                indicator.removeClass('active completed').addClass('pending');
                if (line.length) line.removeClass('completed');
            }
        }
        
        currentStep = step;
    }
    
    // Create WhatsApp Instance
    $('#create-instance-btn').on('click', function() {
        const instanceName = $('#instance-name').val().trim();
        const phoneNumber = $('#phone-number').val().trim();
        const webhookUrl = $('#webhook-url').val().trim();
        
        if (!instanceName || !phoneNumber) {
            alert('Please fill in all required fields');
            return;
        }
        
        $(this).prop('disabled', true).html('<div class="spinner"></div> Creating instance...');
        
        // Create instance via WAAPI
        $.ajax({
            url: WAAPI_BASE_URL + '/instances',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + WAAPI_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                name: instanceName,
                webhook_url: webhookUrl || null
            }),
            success: function(response) {
                console.log('Instance creation response:', response);
                
                // Handle different response formats
                let instanceIdValue = null;
                if (response.instance && response.instance.id) {
                    instanceIdValue = response.instance.id;
                } else if (response.data && response.data.id) {
                    instanceIdValue = response.data.id;
                } else if (response.id) {
                    instanceIdValue = response.id;
                } else {
                    console.error('Unexpected response format:', response);
                    throw new Error('Could not find instance ID in response');
                }
                
                instanceId = instanceIdValue;
                console.log('Created instance ID:', instanceId);

                // Save instance info to database
                saveInstanceToDatabase(instanceId, instanceName, phoneNumber, webhookUrl)
                    .done(function(response) {
                        console.log('Instance saved successfully, proceeding to connection step');
                        showStep(2);
                        generatePairingCode();
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Failed to save instance to database, but continuing anyway');
                        showStep(2);
                        setTimeout(function() {
                            generatePairingCode();
                        }, 3000);
                    });
            },
            error: function(xhr, status, error) {
                console.error('Instance creation failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                let errorMessage = 'Failed to create WhatsApp instance.';
                if (xhr.status === 401) {
                    errorMessage = 'Authentication failed. Please check your WAAPI token.';
                } else if (xhr.status === 429) {
                    errorMessage = 'Rate limit exceeded. Please try again later.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                alert(errorMessage + ' Please try again.');
                $('#create-instance-btn').prop('disabled', false).html('Create WhatsApp Instance <i class="fas fa-arrow-right ml-2"></i>');
            }
        });
    });
    
    // Save instance to database
    function saveInstanceToDatabase(instanceId, instanceName, phoneNumber, webhookUrl) {
        console.log('Saving instance to database:', {
            instance_id: instanceId,
            type: 'whatsapp',
            name: instanceName,
            phone_number: phoneNumber,
            user_id: {{ Auth::id() }}
        });
        
        return $.ajax({
            url: '{{ url("message/createNewInstance") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                instance_id: instanceId,
                type: 'whatsapp',
                name: instanceName,
                owner: '{{ Auth::user()->name }}',
                user_id: {{ Auth::id() }},
                connect_status: 'connecting',
                phone_number: phoneNumber,
                webhook_url: webhookUrl || '',
                webhook_events: '',
                // Fetch country info directly from intlTelInput instance
                country_code: (window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance) 
                    ? window.intlTelInputGlobals.getInstance(document.querySelector("#phone-number")).getSelectedCountryData().dialCode 
                    : $('#country_code2').val(),
                country_name: (window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance) 
                    ? window.intlTelInputGlobals.getInstance(document.querySelector("#phone-number")).getSelectedCountryData().name 
                    : $('#country_name2').val(),
                country_abbr: (window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance) 
                    ? window.intlTelInputGlobals.getInstance(document.querySelector("#phone-number")).getSelectedCountryData().iso2 
                    : $('#country_abbr2').val(),
                status: 0,
                is_paid: 1,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString()
            }),
            success: function(response) {
                console.log('Instance saved to database successfully:', response);
            },
            error: function(xhr, status, error) {
                console.error('Failed to save instance to database:', {
                    status: xhr.status,
                    responseText: xhr.responseText,
                    error: error
                });
            }
        });
    }
    
    // Generate Pairing Code
    function generatePairingCode() {
        console.log('generatePairingCode called, instanceId:', instanceId);
        
        if (!instanceId) {
            console.error('No instance ID available for pairing code generation');
            showPairingError('No instance ID available');
            return;
        }
        
        console.log('Generating pairing code for instance:', instanceId);
        
        // Show waiting message
        $('#pairing-code-display').removeClass('has-code').html(`
            <div style="text-align: center;">
                <div class="spinner"></div>
                <div>Waiting for instance to be ready...</div>
                <small style="color: #666; margin-top: 0.5rem; display: block;">Preparing pairing code...</small>
            </div>
        `);
        
        // Wait for instance to be ready, then request pairing code
        waitForInstanceReadyForPairing();
    }
    
    // Wait for instance to be ready for pairing
    function waitForInstanceReadyForPairing() {
        let attempts = 0;
        const maxAttempts = 15;
        
        const statusPoller = setInterval(function() {
            attempts++;
            checkInstanceStatusForPairing(attempts, maxAttempts, statusPoller);
        }, 3000);
    }
    
    // Check instance status for pairing code
    function checkInstanceStatusForPairing(attempts, maxAttempts, statusPoller) {
        $.ajax({
            url: "{{ url('message/getinstancestatus') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                instance_id: instanceId
            }),
            success: function(response) {
                console.log('Instance status for pairing:', response);
                
                let status = null;
                let instanceStatus = null;
                
                if (response.clientStatus) {
                    status = response.clientStatus.status;
                    instanceStatus = response.clientStatus.instanceStatus;
                } else if (response.status === 'success' && response.data) {
                    status = response.data.status;
                    instanceStatus = response.data.instanceStatus;
                }
                
                console.log('Parsed status:', status, 'Instance status:', instanceStatus, 'Attempt:', attempts);
                
                // Update UI with current status
                $('#pairing-code-display').html(`
                    <div style="text-align: center;">
                        <div class="spinner"></div>
                        <div>Instance Status: ${instanceStatus || status || 'Unknown'}</div>
                        <small style="color: #666; margin-top: 0.5rem; display: block;">
                            Attempt ${attempts}/${maxAttempts} - Waiting for ready state...
                        </small>
                    </div>
                `);
                
                // Check if ready for pairing
                if (instanceStatus === 'qr' || instanceStatus === 'qr_code' || instanceStatus === 'waiting_for_qr' || instanceStatus === 'ready') {
                    clearInterval(statusPoller);
                    console.log('Instance ready for pairing, requesting pairing code...');
                    requestPairingCode();
                    
                } else if (instanceStatus === 'connected' || instanceStatus === 'authenticated' || instanceStatus === 'open') {
                    clearInterval(statusPoller);
                    console.log('Instance already connected!');
                    updateInstanceStatus('connected');
                    $('#instance-id-display').text(instanceId);
                    showStep(3);
                    
                } else if (instanceStatus === 'error' || instanceStatus === 'closed' || instanceStatus === 'timeout') {
                    clearInterval(statusPoller);
                    console.error('Instance in error state:', instanceStatus);
                    showPairingError('Instance error: ' + instanceStatus);
                    
                } else if (attempts >= maxAttempts) {
                    clearInterval(statusPoller);
                    console.error('Timeout waiting for instance to be ready for pairing');
                    showPairingTimeout();
                }
            },
            error: function(xhr, status, error) {
                console.error('Pairing status check failed:', {
                    status: xhr.status,
                    error: error,
                    attempt: attempts
                });
                
                if (xhr.status === 404) {
                    clearInterval(statusPoller);
                    showPairingError('Instance not found');
                } else if (attempts >= maxAttempts) {
                    clearInterval(statusPoller);
                    showPairingTimeout();
                }
            }
        });
    }
    
    // Request pairing code
    function requestPairingCode() {
        console.log('Requesting pairing code for instance:', instanceId);
        
        if (!instanceId) {
            console.error('No instance ID available for pairing code request');
            showPairingError('No instance ID available');
            return;
        }
        
        $.ajax({
            url: "{{ url('message/requestPairCode') }}/" + instanceId,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Pairing code response:', response);
                handlePairingCodeResponse(response);
            },
            error: function(xhr, status, error) {
                console.error('Pairing code request failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    instanceId: instanceId
                });
                
                let errorMessage = 'Failed to generate pairing code';
                if (xhr.status === 401) {
                    errorMessage = 'Authentication failed';
                } else if (xhr.status === 404) {
                    errorMessage = 'Pairing endpoint not found';
                } else if (xhr.status === 403) {
                    errorMessage = 'Instance not found in database. Please try creating the instance again.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showPairingError(errorMessage);
            }
        });
    }
    
    // Handle pairing code response
    function handlePairingCodeResponse(response) {
        console.log('Processing pairing code response:', response);
        
        let pairingCode = null;
        
        if (response.code) {
            pairingCode = response.code;
        } else if (response.data && response.data.pairingCode) {
            pairingCode = response.data.pairingCode;
        } else if (response.data && response.data.code) {
            pairingCode = response.data.code;
        } else if (response.pairingCode) {
            pairingCode = response.pairingCode;
        }
        
        if (pairingCode) {
            console.log('Pairing code extracted:', pairingCode);
            displayPairingCode(pairingCode);
        } else {
            console.error('No pairing code in response:', response);
            if (response.message) {
                showPairingError(response.message);
            } else {
                showPairingError('Pairing code not available in response');
            }
        }
    }
    
    // Display pairing code
    function displayPairingCode(pairingCode) {
        console.log('Displaying pairing code:', pairingCode);
        
        $('#pairing-code-display').addClass('has-code').html(`
            <div class="pairing-code">
                ${pairingCode}
            </div>
        `);
        
        // Update instructions
        $('#pairing-instructions').html(`
            <i class="fas fa-info-circle"></i> Enter this code in your WhatsApp mobile app
            <br><small style="color: #dc3545; margin-top: 0.5rem; display: block;">
                <i class="fas fa-clock"></i> This code expires in 60 seconds
            </small>
            <div style="margin-top: 1rem;">
                <button class="btn btn-outline-primary btn-sm" onclick="generatePairingCode()">
                    <i class="fas fa-redo"></i> Generate New Code
                </button>
            </div>
        `);
        
        // Start monitoring connection status
        startStatusCheck();
    }
    
    // Show pairing error
    function showPairingError(errorMessage) {
        $('#pairing-code-display').removeClass('has-code').html(`
            <div style="text-align: center; color: #dc3545; padding: 2rem;">
                <i class="fas fa-times-circle fa-2x" style="margin-bottom: 1rem;"></i><br>
                <strong style="font-size: 1.1rem;">Pairing Code Failed</strong><br>
                <small style="margin-top: 0.5rem; display: block;">${errorMessage}</small>
                <div style="margin-top: 1.5rem;">
                    <button class="btn btn-primary btn-sm" onclick="generatePairingCode()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            </div>
        `);
    }
    
    // Show pairing timeout
    function showPairingTimeout() {
        $('#pairing-code-display').removeClass('has-code').html(`
            <div style="text-align: center; color: #856404; padding: 2rem;">
                <i class="fas fa-clock fa-2x" style="margin-bottom: 1rem;"></i><br>
                <strong style="font-size: 1.1rem;">Instance Setup Timeout</strong><br>
                <small style="margin-top: 0.5rem; display: block;">The instance is taking too long to initialize</small>
                <div style="margin-top: 1.5rem;">
                    <button class="btn btn-primary btn-sm" onclick="generatePairingCode()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()" style="margin-left: 0.5rem;">
                        <i class="fas fa-refresh"></i> Restart
                    </button>
                </div>
            </div>
        `);
    }
    
    // Start status monitoring
    function startStatusCheck() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
        
        statusCheckInterval = setInterval(function() {
            checkConnectionStatus();
        }, 5000); // Check every 5 seconds
    }
    
    // Check connection status
    function checkConnectionStatus() {
        if (!instanceId) return;
        
        $.ajax({
            url: "{{ url('message/getinstancestatus') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                instance_id: instanceId
            }),
            success: function(response) {
                let instanceStatus = null;
                
                if (response.clientStatus) {
                    instanceStatus = response.clientStatus.instanceStatus;
                } else if (response.status === 'success' && response.data) {
                    instanceStatus = response.data.instanceStatus;
                }
                
                console.log('Connection status check:', instanceStatus);
                
                if (instanceStatus === 'ready' || instanceStatus === 'connected' || instanceStatus === 'authenticated' || instanceStatus === 'open') {
                    clearInterval(statusCheckInterval);
                    updateInstanceStatus('connected');
                    $('#instance-id-display').text(instanceId);
                    showStep(3);
                }
            },
            error: function(xhr, status, error) {
                console.error('Status check failed:', error);
            }
        });
    }
    
    // Update instance status
    function updateInstanceStatus(status) {
        if (!instanceId) return;
        
        $.ajax({
            url: "{{ url('message/finalizePairing') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                instance_id: instanceId
            }),
            success: function(response) {
                console.log('Instance status updated:', response);
            },
            error: function(xhr, status, error) {
                console.error('Failed to update instance status:', error);
            }
        });
    }
    
    // Continue to dashboard
    $('#continue-to-dashboard').on('click', function() {
        window.location.href = '{{ url("/dashboard") }}';
    });
    
    // Make generatePairingCode available globally
    window.generatePairingCode = generatePairingCode;
});
</script>

@endsection
