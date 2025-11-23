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
                
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Two Options Available</strong><br>
                    Choose your preferred method to connect WhatsApp.
                </div>

                <!-- Authentication Method Selection -->
                <div class="form-group">
                    <label class="form-label">Authentication Method</label>
                    <div class="auth-method-selector">
                        <div class="auth-option" data-method="qr">
                            <i class="fas fa-qrcode"></i>
                            <h5>QR Code</h5>
                            <p>Scan with your phone</p>
                        </div>
                        <div class="auth-option" data-method="phone">
                            <i class="fas fa-sms"></i>
                            <h5>Phone Code</h5>
                            <p>Receive verification code</p>
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
                    <div class="qr-code-display">
                        <img id="qr-code-image" src="" alt="QR Code" class="qr-code-image">
                    </div>
                </div>

                <div class="status-indicator waiting">
                    <div class="status-spinner"></div>
                    <div>
                        <strong>Waiting for scan...</strong><br>
                        <small>Please scan the QR code with your WhatsApp app</small>
                    </div>
                </div>
            </div>

            <!-- Phone Code Section -->
            <div class="setup-section" id="phone-code-section">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">Enter Verification Code</h4>
                
                <div class="alert-info">
                    <i class="fas fa-sms"></i>
                    <strong>Code Sent!</strong><br>
                    We sent a verification code to your WhatsApp number. Enter it below.
                </div>

                <form id="verify-code-form">
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <input
                            id="verification_code"
                            name="verification_code"
                            type="text"
                            class="form-control"
                            placeholder="Enter 6-digit code"
                            maxlength="6"
                            autocomplete="off"
                            required
                        >
                        <small class="text-muted">Enter the code you received on WhatsApp</small>
                    </div>

                    <button type="submit" class="btn-whatsapp" id="verify-code-btn">
                        <span class="spinner d-none" id="verify-spinner"></span>
                        <span id="verify-text">Verify Code</span>
                        <i class="fas fa-check ml-2"></i>
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1rem;">
                    <button class="btn-secondary" onclick="showSection('phone-input-section')">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
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
                        <strong>Your WhatsApp session is now active</strong><br>
                        <small>You can now send and receive messages through the platform</small>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button class="btn-whatsapp" onclick="window.location.href='{{ url('/dashboard') }}'">
                            Go to Dashboard
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        <button class="btn-secondary" style="width: 100%;" onclick="location.reload()">
                            <i class="fas fa-plus"></i> Setup Another Number
                        </button>
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

    function showSection(sectionId) {
        $('.setup-section').removeClass('active');
        $('#' + sectionId).addClass('active');
    }

    function showError(message) {
        showSection('error-section');
        $('#error-message').text(message);
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

                    // Handle QR code - either base64 or URL
                    let qrCodeData = data.qr_code;
                    console.log('Received QR Code data type:', typeof qrCodeData);
                    
                    const qrImage = $('#qr-code-image');
                    
                    // Check if it's base64 encoded (starts with data:image/ or is just base64 string)
                    if (qrCodeData && (qrCodeData.startsWith('data:image/') || qrCodeData.length > 500)) {
                        // It's base64 data
                        let qrSrc = qrCodeData.startsWith('data:image/') ? qrCodeData : 'data:image/png;base64,' + qrCodeData;
                        qrImage.attr('src', qrSrc);
                        console.log('QR code set as base64 data');
                    } else if (qrCodeData && qrCodeData.startsWith('http')) {
                        // It's a URL
                        qrImage.on('error', function() {
                            console.error('Failed to load QR code image from URL:', qrCodeData);
                            $(this).attr('alt', 'QR Code failed to load');
                            $('.qr-code-display').html('<div style="padding: 2rem; color: #dc3545; text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>QR Code failed to load. Please try again.</div>');
                        });
                        
                        qrImage.on('load', function() {
                            console.log('QR code image loaded successfully from URL:', qrCodeData);
                        });
                        
                        const cacheBuster = '?t=' + Date.now();
                        qrImage.attr('src', qrCodeData + cacheBuster);
                    } else {
                        console.error('Invalid QR code data format:', qrCodeData);
                        $('.qr-code-display').html('<div style="padding: 2rem; color: #dc3545; text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>Invalid QR code format. Please try again.</div>');
                    }
                    
                    checkSessionStatus(data.session_id);
                } else {
                    showSection('phone-code-section');
                    $('#verification_code').focus();
                }
            } else {
                alert('Error: ' + data.message);
                generateBtn.prop('disabled', false);
                $('#btn-spinner').addClass('d-none');
            }
        } catch (error) {
            alert('Connection error. Please try again.');
            generateBtn.prop('disabled', false);
            $('#btn-spinner').addClass('d-none');
        }
    }

    async function verifyCode() {
        const verifyBtn = $('#verify-code-btn');
        const code = $('#verification_code').val();

        if (!code) {
            alert('Please enter the verification code');
            return;
        }

        verifyBtn.prop('disabled', true);
        $('#verify-spinner').removeClass('d-none');
        
        try {
            const response = await fetch('{{ route("wasender.verify-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    session_id: currentSessionId,
                    code: code
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSection('success-section');
            } else {
                alert('Error: ' + data.message);
                verifyBtn.prop('disabled', false);
                $('#verify-spinner').addClass('d-none');
            }
        } catch (error) {
            alert('Connection error. Please try again.');
            verifyBtn.prop('disabled', false);
            $('#verify-spinner').addClass('d-none');
        }
    }

    async function checkSessionStatus(sessionId) {
        // Clear any existing interval
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }

        statusCheckInterval = setInterval(async () => {
            try {
                const response = await fetch(`{{ url("wasender/session-status") }}/${sessionId}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.success && data.status === 'connected') {
                    clearInterval(statusCheckInterval);
                    showSection('success-section');
                }
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }, 3000); // Check every 3 seconds
    }

    $(document).ready(function() {
        initializePhoneValidation();

        // Authentication method selection
        $('.auth-option').click(function() {
            $('.auth-option').removeClass('selected');
            $(this).addClass('selected');
            
            const method = $(this).data('method');
            $('#auth_method').val(method);
            
            // Update button text and icon
            if (method === 'qr') {
                $('#btn-text').text('Generate QR Code');
                $('#btn-icon').removeClass('fa-sms').addClass('fa-qrcode');
            } else {
                $('#btn-text').text('Send Code');
                $('#btn-icon').removeClass('fa-qrcode').addClass('fa-sms');
            }
        });

        // Set default selection
        $('.auth-option[data-method="qr"]').addClass('selected');

        // Handle form submission
        $('#whatsapp-form').submit(function(e) {
            e.preventDefault();
            generateSession();
        });

        // Handle code verification
        $('#verify-code-form').submit(function(e) {
            e.preventDefault();
            verifyCode();
        });

        // Make functions globally available
        window.showSection = showSection;
        window.showError = showError;
    });
</script>