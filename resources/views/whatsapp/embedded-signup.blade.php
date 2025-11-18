<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WhatsApp Business API - Embedded Signup</title>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .signup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }
        .signup-header {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .signup-body {
            padding: 2rem;
        }
        .provider-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .provider-card:hover {
            border-color: #25D366;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.2);
        }
        .provider-card.selected {
            border-color: #25D366;
            background: rgba(37, 211, 102, 0.05);
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: all 0.3s ease;
        }
        .progress-step.completed {
            background: #25D366;
            color: white;
        }
        .progress-step.current {
            background: #007bff;
            color: white;
        }
        .progress-step.pending {
            background: #e9ecef;
            color: #6c757d;
        }
        .meta-signup-button {
            background: #1877f2;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .meta-signup-button:hover {
            background: #166fe5;
            transform: translateY(-1px);
        }
        .meta-signup-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <i class="fab fa-whatsapp" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h2>WhatsApp Business API Setup</h2>
            <p class="mb-0">Connect your business with the official WhatsApp Business API</p>
        </div>
        
        <div class="signup-body">
            <!-- Progress Indicator -->
            <div class="row mb-4">
                <div class="col-4 text-center">
                    <div class="progress-step completed" id="step1-indicator">1</div>
                    <small class="d-block mt-2">Choose Provider</small>
                </div>
                <div class="col-4 text-center">
                    <div class="progress-step current" id="step2-indicator">2</div>
                    <small class="d-block mt-2">Meta Signup</small>
                </div>
                <div class="col-4 text-center">
                    <div class="progress-step pending" id="step3-indicator">3</div>
                    <small class="d-block mt-2">Complete</small>
                </div>
            </div>

            <!-- Step 1: Provider Selection (Hidden after completion) -->
            <div class="step" id="step1">
                <h4 class="mb-4">Select Your Business Solution Provider</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="provider-card p-3 text-center" data-provider="360dialog">
                            <img src="/images/providers/360dialog-logo.png" alt="360Dialog" style="height: 40px; margin-bottom: 1rem;" onerror="this.style.display='none'">
                            <h6>360Dialog</h6>
                            <p class="small text-muted mb-2">Global coverage, competitive pricing</p>
                            <span class="badge bg-primary">Recommended</span>
                            <div class="mt-2">
                                <small class="text-success">✓ Easy setup</small><br>
                                <small class="text-success">✓ Global support</small><br>
                                <small class="text-success">✓ Cost effective</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="provider-card p-3 text-center" data-provider="twilio">
                            <img src="/images/providers/twilio-logo.png" alt="Twilio" style="height: 40px; margin-bottom: 1rem;" onerror="this.style.display='none'">
                            <h6>Twilio</h6>
                            <p class="small text-muted mb-2">Enterprise features, reliable infrastructure</p>
                            <span class="badge bg-info">Enterprise</span>
                            <div class="mt-2">
                                <small class="text-success">✓ Enterprise grade</small><br>
                                <small class="text-success">✓ Advanced features</small><br>
                                <small class="text-success">✓ High reliability</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="provider-card p-3 text-center" data-provider="facebook">
                            <img src="/images/providers/meta-logo.png" alt="Meta" style="height: 40px; margin-bottom: 1rem;" onerror="this.style.display='none'">
                            <h6>Meta Direct</h6>
                            <p class="small text-muted mb-2">Direct from WhatsApp/Meta</p>
                            <span class="badge bg-success">Official</span>
                            <div class="mt-2">
                                <small class="text-success">✓ Direct access</small><br>
                                <small class="text-success">✓ Latest features</small><br>
                                <small class="text-success">✓ Official support</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button class="btn btn-primary btn-lg" id="continue-btn" disabled>
                        Continue with Selected Provider
                    </button>
                </div>
            </div>

            <!-- Step 2: Meta Embedded Signup -->
            <div class="step active" id="step2">
                <h4 class="mb-4">Connect with Meta</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6>Selected Provider</h6>
                                <div id="selected-provider-info">
                                    <div class="d-flex align-items-center">
                                        <img id="provider-logo" src="" alt="" style="height: 30px; margin-right: 10px;">
                                        <div>
                                            <div class="fw-bold" id="provider-name">360Dialog</div>
                                            <small class="text-muted" id="provider-description">Global coverage, competitive pricing</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6>What happens next?</h6>
                                <ul class="small">
                                    <li>Connect your Meta/Facebook Business account</li>
                                    <li>Grant WhatsApp Business API permissions</li>
                                    <li>Verify your business phone number</li>
                                    <li>Complete the setup with your chosen provider</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="text-center">
                            <div class="mb-4">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/8/89/Facebook_Logo_%282019%29.svg" 
                                     alt="Meta" style="height: 60px; opacity: 0.8;">
                            </div>
                            
                            <h5>Ready to Connect?</h5>
                            <p class="text-muted mb-4">
                                Click the button below to start the Meta Embedded Signup process. 
                                You'll be redirected to Meta to authorize the connection.
                            </p>
                            
                            <button class="meta-signup-button" id="meta-signup-btn">
                                <i class="fab fa-facebook me-2"></i>
                                Connect with Meta
                            </button>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Secured by Meta's enterprise-grade authentication
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="signup-status" class="mt-4" style="display: none;">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status"></div>
                            <div>
                                <strong>Connecting...</strong>
                                <div class="small">Please complete the authorization in the popup window.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Completion -->
            <div class="step" id="step3">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h4 class="text-success">Setup Complete!</h4>
                    <p class="lead">Your WhatsApp Business API is now connected and ready to use.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-3">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h6>Connection Details</h6>
                                    <div id="connection-details">
                                        <!-- Will be populated with actual data -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-success btn-lg" onclick="window.location.href='/whatsapp/integration-success'">
                            <i class="fas fa-arrow-right me-2"></i>
                            Continue to Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <script>
        // Configuration from backend
        const CONFIG = {
            appId: '{{ $config["app_id"] ?? "" }}',
            configId: '{{ $config["config_id"] ?? "" }}',
            businessId: '{{ $config["business_id"] ?? "" }}',
            redirectUrl: '{{ $config["redirect_url"] ?? "" }}',
            credentialId: '{{ $credential_id ?? "" }}',
            selectedProvider: '{{ $selected_provider ?? "360dialog" }}'
        };

        let selectedProvider = CONFIG.selectedProvider;
        let FB = null;

        // Initialize Facebook SDK
        window.fbAsyncInit = function() {
            FB = window.FB;
            FB.init({
                appId: CONFIG.appId,
                cookie: true,
                xfbml: true,
                version: 'v18.0'
            });

            console.log('Facebook SDK initialized');
            document.getElementById('meta-signup-btn').disabled = false;
        };

        // Provider selection
        document.addEventListener('DOMContentLoaded', function() {
            const providerCards = document.querySelectorAll('.provider-card');
            const continueBtn = document.getElementById('continue-btn');

            providerCards.forEach(card => {
                card.addEventListener('click', function() {
                    providerCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedProvider = this.dataset.provider;
                    continueBtn.disabled = false;
                    updateProviderInfo();
                });
            });

            continueBtn.addEventListener('click', function() {
                goToStep(2);
            });

            // Pre-select provider if already chosen
            if (selectedProvider) {
                const providerCard = document.querySelector(`[data-provider="${selectedProvider}"]`);
                if (providerCard) {
                    providerCard.classList.add('selected');
                    continueBtn.disabled = false;
                    updateProviderInfo();
                    // Skip to step 2 if provider already selected
                    goToStep(2);
                }
            }

            // Meta signup button
            document.getElementById('meta-signup-btn').addEventListener('click', function() {
                if (!FB) {
                    alert('Facebook SDK not loaded. Please refresh the page and try again.');
                    return;
                }
                startEmbeddedSignup();
            });
        });

        function updateProviderInfo() {
            const providers = {
                '360dialog': {
                    name: '360Dialog',
                    description: 'Global coverage, competitive pricing',
                    logo: '/images/providers/360dialog-logo.png'
                },
                'twilio': {
                    name: 'Twilio',
                    description: 'Enterprise features, reliable infrastructure',
                    logo: '/images/providers/twilio-logo.png'
                },
                'facebook': {
                    name: 'Meta Direct',
                    description: 'Direct from WhatsApp/Meta',
                    logo: '/images/providers/meta-logo.png'
                }
            };

            const provider = providers[selectedProvider];
            if (provider) {
                document.getElementById('provider-name').textContent = provider.name;
                document.getElementById('provider-description').textContent = provider.description;
                document.getElementById('provider-logo').src = provider.logo;
            }
        }

        function goToStep(stepNumber) {
            // Hide all steps
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });

            // Show target step
            document.getElementById(`step${stepNumber}`).classList.add('active');

            // Update progress indicators
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById(`step${i}-indicator`);
                if (i < stepNumber) {
                    indicator.className = 'progress-step completed';
                    indicator.innerHTML = '<i class="fas fa-check"></i>';
                } else if (i === stepNumber) {
                    indicator.className = 'progress-step current';
                    indicator.textContent = i;
                } else {
                    indicator.className = 'progress-step pending';
                    indicator.textContent = i;
                }
            }
        }

        function startEmbeddedSignup() {
            document.getElementById('signup-status').style.display = 'block';
            document.getElementById('meta-signup-btn').disabled = true;

            const setupParams = {
                business_id: CONFIG.businessId,
                partner_id: getProviderPartnerId(),
                solution_id: getProviderSolutionId(),
                credential_id: CONFIG.credentialId
            };

            FB.login(function(response) {
                if (response.status === 'connected') {
                    // User is logged in and authenticated
                    launchEmbeddedSignup(setupParams);
                } else {
                    console.error('Facebook login failed:', response);
                    showError('Facebook login failed. Please try again.');
                }
            }, {
                config_id: CONFIG.configId,
                response_type: 'code',
                override_default_response_type: true,
                extras: {
                    setup: setupParams
                }
            });
        }

        function launchEmbeddedSignup(setupParams) {
            // This will trigger the embedded signup flow
            const embeddedSignupUrl = `https://www.facebook.com/v18.0/dialog/oauth?` +
                `client_id=${CONFIG.appId}&` +
                `redirect_uri=${encodeURIComponent(CONFIG.redirectUrl)}&` +
                `config_id=${CONFIG.configId}&` +
                `response_type=code&` +
                `state=${CONFIG.credentialId}&` +
                `extras=${encodeURIComponent(JSON.stringify({ setup: setupParams }))}`;

            // Open in same window for embedded signup
            window.location.href = embeddedSignupUrl;
        }

        function getProviderPartnerId() {
            const partnerIds = {
                '360dialog': '360dialog_partner_id',
                'twilio': 'twilio_partner_id',
                'facebook': 'meta_partner_id'
            };
            return partnerIds[selectedProvider] || '';
        }

        function getProviderSolutionId() {
            const solutionIds = {
                '360dialog': '360dialog_solution_id',
                'twilio': 'twilio_solution_id',
                'facebook': 'meta_solution_id'
            };
            return solutionIds[selectedProvider] || '';
        }

        function showError(message) {
            document.getElementById('signup-status').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
            document.getElementById('meta-signup-btn').disabled = false;
        }

        function showSuccess(data) {
            document.getElementById('connection-details').innerHTML = `
                <div class="text-start">
                    <div class="row">
                        <div class="col-6"><strong>Provider:</strong></div>
                        <div class="col-6">${data.provider || selectedProvider}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Phone Number:</strong></div>
                        <div class="col-6">${data.phone_number || 'Setting up...'}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Status:</strong></div>
                        <div class="col-6">
                            <span class="badge bg-success">Connected</span>
                        </div>
                    </div>
                </div>
            `;
            goToStep(3);
        }

        // Handle URL parameters for callback success
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === 'true') {
            const data = {
                provider: urlParams.get('provider'),
                phone_number: urlParams.get('phone_number')
            };
            showSuccess(data);
        } else if (urlParams.get('error')) {
            showError(urlParams.get('error'));
        }
    </script>
</body>
</html>
