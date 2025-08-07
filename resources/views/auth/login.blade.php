@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        font-family: 'Inter', sans-serif;
    }
    
    body {
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        margin: 0;
        padding: 0;
    }
    
    .modern-layout {
        min-height: 100vh;
        display: flex;
    }
    
    /* Left scrollable content */
    .content-section {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
    }
    
    /* Right sticky login */
    .sticky-login {
        width: 420px;
        background: white;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.08);
        position: fixed;
        right: 0;
        top: 0;
        height: 100vh;
        padding: 40px 35px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        z-index: 1000;
    }
    
    .hero-section {
        padding: 80px 60px;
        text-align: center;
        background: white;
        margin: 0;
    }
    
    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 24px;
        line-height: 1.1;
    }
    
    .hero-subtitle {
        font-size: 1.4rem;
        color: #64748b;
        margin-bottom: 40px;
        font-weight: 400;
    }
    
    .hero-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 40px;
        box-shadow: 0 20px 40px rgba(37, 211, 102, 0.2);
    }
    
    .section {
        padding: 80px 60px;
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        text-align: center;
        margin-bottom: 60px;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin-bottom: 60px;
    }
    
    .feature-card {
        background: white;
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        background: #f8fafc;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2.5rem;
    }
    
    .feature-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
    }
    
    .feature-desc {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .target-users {
        background: white;
        border-radius: 20px;
        padding: 50px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        margin-bottom: 60px;
    }
    
    .user-avatars {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 40px;
        flex-wrap: wrap;
    }
    
    .user-avatar {
        text-align: center;
    }
    
    .avatar-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        color: white;
        font-size: 2rem;
    }
    
    .avatar-label {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .pricing-card {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        color: white;
        border-radius: 20px;
        padding: 50px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(37, 211, 102, 0.2);
        margin-bottom: 60px;
    }
    
    .price {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .price-period {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 30px;
    }
    
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .pricing-features li {
        padding: 12px 0;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .pricing-features li:before {
        content: "✓";
        margin-right: 12px;
        font-weight: bold;
    }
    
    .testimonial {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }
    
    .testimonial-text {
        font-size: 1.1rem;
        color: #475569;
        margin-bottom: 20px;
        font-style: italic;
        line-height: 1.6;
    }
    
    .testimonial-author {
        font-weight: 600;
        color: #1e293b;
    }
    
    .testimonial-role {
        font-size: 0.9rem;
        color: #64748b;
    }
    
    /* Login Form Styles */
    .login-logo {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-logo img {
        width: 60px;
        height: 60px;
    }
    
    .login-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        text-align: center;
        margin-bottom: 8px;
    }
    
    .login-subtitle {
        color: #64748b;
        text-align: center;
        margin-bottom: 40px;
        font-size: 1rem;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
        font-size: 0.95rem;
    }
    
    .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        background: #fafafa;
    }
    
    .form-control:focus {
        border-color: #25d366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        outline: none;
        background: white;
    }
    
    .input-group-text {
        background: #fafafa;
        border: 2px solid #e5e7eb;
        border-right: none;
        border-radius: 12px 0 0 12px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .btn-proceed {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border: none;
        border-radius: 12px;
        padding: 16px;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-proceed:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
        color: white;
    }
    
    .legal-text {
        text-align: center;
        font-size: 0.85rem;
        color: #6b7280;
        margin-top: 20px;
        line-height: 1.4;
    }
    
    .legal-text a {
        color: #25d366;
        text-decoration: none;
    }
    
    .legal-text a:hover {
        text-decoration: underline;
    }
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .sticky-login {
            width: 380px;
        }
        .content-section {
            margin-right: 380px;
        }
    }
    
    @media (max-width: 768px) {
        .modern-layout {
            flex-direction: column;
        }
        
        .sticky-login {
            position: relative;
            width: 100%;
            height: auto;
            order: 1;
        }
        
        .content-section {
            margin-right: 0;
            order: 2;
        }
        
        .section {
            padding: 40px 30px;
        }
        
        .hero-section {
            padding: 60px 30px;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        .section-title {
            font-size: 2rem;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .user-avatars {
            gap: 20px;
        }
    }
    
    .content-section {
        margin-right: 420px;
    }
</style>

<div class="modern-layout">
    {{-- Left Side: Scrollable Content --}}
    <div class="content-section">
        {{-- Hero Section --}}
        <div class="hero-section">
            <div class="hero-icon">
                <i class="fab fa-whatsapp" style="font-size: 4rem; color: white;"></i>
            </div>
            <h1 class="hero-title">Send unlimited WhatsApp messages to your customers. Instantly.</h1>
            <p class="hero-subtitle">No SMS bundles. No approvals. Just connect and chat.</p>
        </div>

        {{-- Key Features Section --}}
        <div class="section">
            <h2 class="section-title">Why Choose safarichat?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        🚀
                    </div>
                    <h3 class="feature-title">Unlimited WhatsApp Messaging</h3>
                    <p class="feature-desc">Send as many messages as you need without any restrictions or additional costs.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        💬
                    </div>
                    <h3 class="feature-title">Two-way Conversations</h3>
                    <p class="feature-desc">Engage in real conversations with your customers and build lasting relationships.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        🧾
                    </div>
                    <h3 class="feature-title">No Approval or Message Cost</h3>
                    <p class="feature-desc">Start messaging immediately without waiting for approvals or paying per message.</p>
                </div>
            </div>
        </div>

        {{-- Target Users Section --}}
        <div class="section">
            <div class="target-users">
                <h2 class="section-title">Perfect for shops, salons, delivery businesses, and local SMEs</h2>
                <div class="user-avatars">
                    <div class="user-avatar">
                        <div class="avatar-icon">🏪</div>
                        <div class="avatar-label">Retail Shops</div>
                    </div>
                    <div class="user-avatar">
                        <div class="avatar-icon">💇</div>
                        <div class="avatar-label">Salons & Spas</div>
                    </div>
                    <div class="user-avatar">
                        <div class="avatar-icon">🚚</div>
                        <div class="avatar-label">Delivery Services</div>
                    </div>
                    <div class="user-avatar">
                        <div class="avatar-icon">🏢</div>
                        <div class="avatar-label">Local SMEs</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pricing Section --}}
        <div class="section">
            <div class="pricing-card">
                <div class="price">Tsh 50,000</div>
                <div class="price-period">per month</div>
                <ul class="pricing-features">
                    <li>Unlimited WhatsApp messages</li>
                    <li>Easy contact import</li>
                    <li>Works with your WhatsApp</li>
                    <li>No technical setup required</li>
                    <li>No per-message charges</li>
                </ul>
            </div>
        </div>

        {{-- Testimonials Section --}}
        <div class="section">
            <h2 class="section-title">What Our Customers Say</h2>
            <div class="testimonial">
                <div class="testimonial-text">
                    "safarichat transformed how we communicate with our customers. We've seen a 40% increase in repeat business since we started using it."
                </div>
                <div class="testimonial-author">Sarah Mwalimu</div>
                <div class="testimonial-role">Owner, Upendo Beauty Salon</div>
            </div>
            
            <div class="testimonial">
                <div class="testimonial-text">
                    "Setting up was incredibly easy. Within 10 minutes, we were sending messages to all our customers about our daily specials."
                </div>
                <div class="testimonial-author">James Kilimo</div>
                <div class="testimonial-role">Manager, Fresh Foods Market</div>
            </div>
        </div>
    </div>

    {{-- Right Side: Sticky Login Form --}}
    <div class="sticky-login">
        <div class="login-logo">
            <img src="{{ asset(ROOT.'assets/images/safarichat.png')}}" alt="safarichat Logo">
        </div>
        
        <h2 class="login-title">Get Started Today</h2>
        <p class="login-subtitle">Enter your WhatsApp number to begin</p>

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 24px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ url('api/otp') }}" id="loginForm">
            @csrf
            
            <div class="form-group">
                <label for="phone2" class="form-label">
                    <i class="fab fa-whatsapp" style="color: #25d366; margin-right: 8px;"></i>
                    WhatsApp Number
                </label>
                <div class="input-group">
                    <input
                        id="phone2"
                        name="email"
                        type="tel"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Enter WhatsApp number"
                        value="{{ old('email') }}"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <input type="hidden" id="country_code2" name="country_code">
                    <input type="hidden" id="country_name2" name="country_name">
                    <input type="hidden" id="country_abbr2" name="country_abbr">
                </div>
                <span id="error-msg2" class="text-danger" style="font-size: 0.85rem; display: none;"></span>
                @error('email')
                    <div class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror
            </div>

            <button class="btn btn-proceed" type="submit" id="loginButton">
                Proceed
                <i class="fas fa-arrow-right ml-2"></i>
                <span class="spinner-border spinner-border-sm ml-2 d-none" role="status" aria-hidden="true" id="loadingSpinner"></span>
            </button>
            
            <div class="legal-text">
                By clicking Proceed, you agree to our
                <a href="{{ url('/terms-and-conditions') }}" target="_blank">Terms and Conditions</a>.
            </div>
        </form>
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
        var input = document.querySelector("#phone2"),
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
            const phoneInput = $('#phone2');
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
@endsection