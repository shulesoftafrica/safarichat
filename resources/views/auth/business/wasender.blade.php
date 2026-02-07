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

.setup-section {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.setup-section.active {
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

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.alert-danger {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.qr-code-container {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 2rem;
    margin: 2rem 0;
    border: 2px solid #e9ecef;
    text-align: center;
}

.qr-code-display {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    background: white;
    border: 2px dashed #dee2e6;
    margin-bottom: 1rem;
    padding: 1rem;
}

.qr-code-display canvas,
.qr-code-display img {
    border-radius: 10px;
    max-width: 100%;
    height: auto;
}

.qr-code-image {
    max-width: 280px;
    border-radius: 10px;
}

.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.status-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin: 1rem 0;
}

.status-indicator.waiting {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.status-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(0,0,0,.3);
    border-radius: 50%;
    border-top-color: #25D366;
    animation: spin 1s ease-in-out infinite;
    margin-right: 0.5rem;
}

.success-icon {
    font-size: 4rem;
    color: #25D366;
    margin-bottom: 1rem;
}

.text-muted {
    color: #6c757d;
    font-size: 0.875rem;
}

.auth-method-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin: 1rem 0;
}

.auth-option {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.auth-option:hover {
    border-color: #25D366;
    background: #f8fff9;
}

.auth-option.selected {
    border-color: #25D366;
    background: #f8fff9;
}

.auth-option i {
    font-size: 2rem;
    color: #25D366;
    margin-bottom: 0.5rem;
}

.auth-option h5 {
    margin: 0.5rem 0;
    color: #333;
}

.auth-option p {
    margin: 0;
    color: #6c757d;
    font-size: 0.875rem;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
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
            <p>Scan QR code to link your WhatsApp account</p>
        </div>

        <!-- Content -->
        <div class="setup-content">
            <!-- Phone Input Section -->
            <div class="setup-section active" id="phone-input-section">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">Choose Authentication Method</h4>
                
             

                <!-- Authentication Method Selection -->
                <div class="form-group">
                    <label class="form-label">Authentication Method</label>
                    <div class="auth-method-selector">
                        <div class="auth-option" data-method="qr">
                            <i class="fas fa-qrcode"></i>
                            <h5>QR Code</h5>
                            <p>Scan with your phone</p>
                        </div>

                    </div>
                </div>

                <form id="whatsapp-form">
                    <input type="hidden" id="auth_method" name="auth_method" value="qr">
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <div class="input-group">
                            <input
                                id="phone_number"
                                name="phone_number"
                                type="tel"
                                class="form-control"
                                placeholder="Enter WhatsApp number"
                                value="{{ Auth::user()->phone ?? '' }}"
                                autocomplete="off"
                                required
                                autofocus
                            >
                            <input type="hidden" id="country_code" name="country_code">
                            <input type="hidden" id="country_name" name="country_name">
                            <input type="hidden" id="country_abbr" name="country_abbr">
                        </div>
                        <small class="text-muted">Enter your phone number with country code</small>
                    </div>

                    <button type="submit" class="btn-whatsapp" id="generate-qr-btn">
                        <span class="spinner d-none" id="btn-spinner"></span>
                        <span id="btn-text">Generate QR Code</span>
                        <i class="fas fa-qrcode ml-2" id="btn-icon"></i>
                    </button>
                </form>
            </div>

            <!-- QR Code Section -->
            <div class="setup-section" id="qr-code-section">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">Scan QR Code</h4>
                
                <div class="alert-info">
                    <i class="fas fa-mobile-alt"></i>
                    <strong>How to scan:</strong><br>
                    1. Open WhatsApp on your phone<br>
                    2. Go to Settings → Linked Devices<br>
                    3. Tap "Link a Device"<br>
                    4. Scan this QR code
                </div>

                <div class="qr-code-container">
                    <div class="qr-code-display" id="qr-code-display">
                        <!-- QR code will be generated here by QRCode.js -->
                    </div>
                </div>

                <div class="status-indicator waiting">
                    <div class="status-spinner"></div>
                    <div>
                        <strong>Waiting for scan...</strong><br>
                        <small>QR code expires in: <span id="qr-timer">120</span> seconds</small>
                    </div>
                </div>
            </div>



            <!-- Success Section -->
            <div class="setup-section" id="success-section">
                <div style="text-align: center; padding: 2rem 0;">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 1rem;">WhatsApp Connected Successfully!</h4>
                    
                    <div class="alert-success">
                        <strong>Great! Next step: Set up your products or services</strong><br>
                        <small>Before you can start selling, you need to define at least one product or service</small>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button class="btn-whatsapp" onclick="window.location.href='{{ url('/service/index?onboarding=true') }}'">
                            Set Up Products/Services
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        <!-- <button class="btn-secondary" style="width: 100%;" onclick="location.reload()">
                            <i class="fas fa-plus"></i> Setup Another Number
                        </button> -->
                    </div>
                </div>
            </div>

            <!-- Error Section -->
            <div class="setup-section" id="error-section">
                <div style="text-align: center; padding: 2rem 0;">
                    <div style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 1rem;">Connection Failed</h4>
                    
                    <div class="alert-danger" id="error-message">
                        An error occurred while connecting to WhatsApp.
                    </div>

                    <button class="btn-secondary" style="width: 100%;" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- External Styles and Scripts for intl-tel-input --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script type="text/javascript">
    // Initialize phone validation
    function initializePhoneValidation() {
        if (typeof window.intlTelInput === 'undefined') {
            console.log('intlTelInput not loaded yet, retrying...');
            setTimeout(initializePhoneValidation, 100);
            return;
        }
        
        validate_phone();
    }

    // Intl-Tel-Input validation logic
    var validate_phone = function () {
        var input = document.querySelector("#phone_number");

        if (!input) {
            console.error('Phone input element not found');
            return;
        }

        var errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

        try {
            var iti = window.intlTelInput(input, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                preferredCountries: ['tz'],
                separateDialCode: true,
                initialCountry: "tz",
                autoInsertDialCode: true,
                formatOnDisplay: true,
                nationalMode: false,
                placeholderNumberType: "MOBILE"
            });

            var reset = function () {
                input.classList.remove("is-invalid", "is-valid");
            };

            // on blur: validate
            input.addEventListener('blur', function () {
                reset();
                if (input.value.trim()) {
                    if (iti.isValidNumber()) {
                        input.classList.add("is-valid");
                        var countryData = iti.getSelectedCountryData();
                        var fullNumber = iti.getNumber();
                        
                        document.getElementById("country_code").value = countryData.dialCode;
                        document.getElementById("country_name").value = countryData.name;
                        document.getElementById("country_abbr").value = countryData.iso2;
                        
                        input.value = fullNumber;
                    } else {
                        input.classList.add("is-invalid");
                    }
                }
            });

            input.addEventListener('change', reset);
            input.addEventListener('keyup', reset);

            input.addEventListener('countrychange', function() {
                reset();
                var countryData = iti.getSelectedCountryData();
                document.getElementById("country_code").value = countryData.dialCode;
                document.getElementById("country_name").value = countryData.name;
                document.getElementById("country_abbr").value = countryData.iso2;
            });

            console.log('Phone validation initialized successfully');
            
        } catch (error) {
            console.error('Error initializing intlTelInput:', error);
        }
    };

    let currentSessionId = null;
    let statusCheckInterval = null;
    let countdownInterval = null;
    let qrCodeInstance = null;
    let connectionTimeout = null;
    let maxConnectionAttempts = 60; // 30 minutes (60 * 30 seconds)
    let currentAttempts = 0;
    let qrRefreshInterval = null;
    let lastQRGeneration = 0;

    function showSection(sectionId) {
        $('.setup-section').removeClass('active');
        $('#' + sectionId).addClass('active');
    }

    function showError(message) {
        // Clean up intervals and session
        clearAllIntervals();
        
        showSection('error-section');
        $('#error-message').text(message);
        
        // Clean up session on server if it exists
        if (currentSessionId) {
            cleanupSession(currentSessionId);
        }
    }

    function clearAllIntervals() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        if (connectionTimeout) {
            clearTimeout(connectionTimeout);
            connectionTimeout = null;
        }
        if (qrRefreshInterval) {
            clearInterval(qrRefreshInterval);
            qrRefreshInterval = null;
        }
    }

    async function cleanupSession(sessionId) {
        try {
            await fetch(`{{ url("wasender/cleanup-session") }}/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        } catch (error) {
            console.error('Failed to cleanup session:', error);
        }
        currentSessionId = null;
    }

    function startCountdown() {
        let timeLeft = 120; // Extend to 2 minutes
        $('#qr-timer').text(timeLeft);
        
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        
        countdownInterval = setInterval(() => {
            timeLeft--;
            $('#qr-timer').text(timeLeft);
            
            if (timeLeft <= 0) {
                // QR code expired, regenerate
                clearInterval(countdownInterval);
                regenerateQRCode();
            }
        }, 1000);
    }

    function displayQRCode(qrString) {
        if (!qrString) {
            console.error('No QR code string provided');
            const qrContainer = document.getElementById('qr-code-display');
            qrContainer.innerHTML = '<div style="padding: 2rem; color: #dc3545; text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>No QR code data received. Please try again.</div>';
            return;
        }

        // Clear previous QR code
        const qrContainer = document.getElementById('qr-code-display');
        qrContainer.innerHTML = '';
        
        // Add cache-busting timestamp to ensure fresh render
        const timestamp = Date.now();
        lastQRGeneration = timestamp;
        
        try {
            // Generate QR code using QRCode.js from the string payload
            qrCodeInstance = new QRCode(qrContainer, {
                text: qrString, // Use the raw string from WaSender API
                width: 280,
                height: 280,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            
            console.log('QR code generated successfully at:', new Date(timestamp).toISOString());
            console.log('QR string length:', qrString.length);
        } catch (error) {
            console.error('Error generating QR code:', error);
            qrContainer.innerHTML = '<div style="padding: 2rem; color: #dc3545; text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>QR Code generation failed. Please try again.</div>';
        }
    }

    async function refreshQRCode(sessionId) {
        try {
            // Fetch fresh QR code from server with cache-busting
            const timestamp = Date.now();
            const response = await fetch(`{{ url("wasender/session-qr") }}/${sessionId}?t=${timestamp}`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.qr_code) {
                console.log('QR code refreshed successfully');
                displayQRCode(data.qr_code);
            } else {
                console.warn('QR refresh failed:', data.message);
            }
        } catch (error) {
            console.error('Failed to refresh QR code:', error);
        }
    }

    function startQRRefresh(sessionId) {
        // Clear any existing refresh interval
        if (qrRefreshInterval) {
            clearInterval(qrRefreshInterval);
        }
        
        // Refresh QR code every 45 seconds (before it expires)
        qrRefreshInterval = setInterval(() => {
            console.log('Auto-refreshing QR code to prevent expiry...');
            refreshQRCode(sessionId);
        }, 45000); // 45 seconds
    }

    async function generateSession() {
        const generateBtn = $('#generate-qr-btn');
        const phoneNumber = $('#phone_number').val();
        const authMethod = $('#auth_method').val();

        if (!phoneNumber) {
            alert('Please enter your phone number');
            return;
        }

        generateBtn.prop('disabled', true);
        $('#btn-spinner').removeClass('d-none');
        
        try {
            const response = await fetch('{{ route("wasender.create-session") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    phone_number: phoneNumber,
                    auth_method: authMethod
                })
            });
            
            const data = await response.json();
            
            console.log('QR Generation Response:', data);
            console.log('QR Code data:', data.qr_code ? data.qr_code.substring(0, 100) + '...' : 'No QR code');
            
            if (data.success) {
                currentSessionId = data.session_id;
                
                if (data.auth_method === 'qr') {
                    showSection('qr-code-section');
                    startCountdown(); // Start the countdown timer

                    // Display the initial QR code
                    displayQRCode(data.qr_code);
                    
                    // Start QR refresh mechanism (refresh every 30 seconds to avoid expiry)
                    startQRRefresh(data.session_id);
                    
                    checkSessionStatus(data.session_id);
                } else {
                    showError('QR code generation failed: ' + (data.message || 'Please try again.'));
                }
            } else {
                showError('Error: ' + (data.message || 'Connection failed'));
                generateBtn.prop('disabled', false);
                $('#btn-spinner').addClass('d-none');
            }
        } catch (error) {
            console.error('Generate session error:', error);
            showError('Connection error. Please check your internet connection and try again.');
        }
    }



    async function checkSessionStatus(sessionId) {
        // Clear any existing interval
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }

        currentAttempts = 0;

        statusCheckInterval = setInterval(async () => {
            currentAttempts++;
            
            // Timeout after max attempts
            if (currentAttempts >= maxConnectionAttempts) {
                clearInterval(statusCheckInterval);
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
                showError('Connection timeout. The QR code scanning took too long. Please try again.');
                return;
            }

            try {
                const response = await fetch(`{{ url("wasender/session-status") }}/${sessionId}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Status check failed');
                }
                
                const data = await response.json();
                console.log('Status check response:', data);
                
                if (data.success) {
                    if (data.status === 'connected' && data.verified === true) {
                        // Double-check connection is actually working
                        const verificationResponse = await fetch(`{{ url("wasender/verify-connection") }}/${sessionId}`, {
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        
                        const verificationData = await verificationResponse.json();
                        
                        if (verificationData.success && verificationData.connected === true) {
                            clearInterval(statusCheckInterval);
                            if (countdownInterval) {
                                clearInterval(countdownInterval);
                            }
                            showSection('success-section');
                            console.log('WhatsApp connection verified successfully');
                        } else {
                            console.log('Connection verification failed, continuing to wait...');
                        }
                    } else if (data.status === 'failed' || data.status === 'error') {
                        clearInterval(statusCheckInterval);
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                        showError(data.message || 'WhatsApp connection failed. Please try again.');
                    }
                    // Continue waiting for other statuses (pending, scanning, etc.)
                } else {
                    console.error('Status check returned error:', data.message);
                }
            } catch (error) {
                console.error('Status check failed:', error);
                // Don't show error immediately, continue retrying
                if (currentAttempts >= 3) {
                    // Only show error after multiple failures
                    console.warn('Multiple status check failures, but continuing...');
                }
            }
        }, 4*5000); // Check every 20 seconds instead of 30
    }

    $(document).ready(function() {
        initializePhoneValidation();

        // Authentication method selection (QR only)
        $('.auth-option').click(function() {
            $('.auth-option').removeClass('selected');
            $(this).addClass('selected');
            $('#auth_method').val('qr');
        });

        // Set default selection
        $('.auth-option[data-method="qr"]').addClass('selected');

        // Handle form submission
        $('#whatsapp-form').submit(function(e) {
            e.preventDefault();
            generateSession();
        });

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            clearAllIntervals();
            if (currentSessionId) {
                // Use sendBeacon for cleanup on page unload
                navigator.sendBeacon(
                    `{{ url("wasender/cleanup-session") }}/${currentSessionId}`,
                    new FormData()
                );
            }
        });



        // Make functions globally available
        window.showSection = showSection;
        window.showError = showError;
    });
</script>