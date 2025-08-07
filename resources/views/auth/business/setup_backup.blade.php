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
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
    display: block;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #25D366;
    box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
}

.btn-whatsapp {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-whatsapp:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
    color: white;
}

.btn-whatsapp:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: none;
}

.alert-info {
    background: #e7f3ff;
    border: 1px solid #bee5eb;
    border-radius: 10px;
    color: #0c5460;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.qr-container {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 15px;
    margin: 1rem 0;
}

.qr-placeholder {
    width: 200px;
    height: 200px;
    background: white;
    border: 2px dashed #ddd;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.1rem;
    color: #666;
}

.status-indicator {
    display: flex;
    align-items: center;
    padding: 1rem;
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
    margin: 1rem 0;
    box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
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

.connection-method-tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.connection-method-tabs .btn {
    margin: 0 0.5rem;
    transition: all 0.3s ease;
}

.connection-method-tabs .btn.active {
    background: #25D366;
    border-color: #25D366;
    color: white;
}

.method-content {
    animation: fadeIn 0.3s ease-in;
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
                    An instance is your dedicated WhatsApp connection that allows you to send and receive messages through our API. Each phone number needs its own instance.
                </div>

                <form id="instance-form">
                    <div class="form-group">
                        <label class="form-label">Instance Name</label>
                        <input type="text" id="instance-name" name="instance_name" class="form-control" 
                               placeholder="e.g., {{ Auth::user()->name ?? 'My Business' }} WhatsApp" 
                               value="{{ Auth::user()->name ?? '' }} WhatsApp" required>
                        <small class="text-muted">Give your WhatsApp connection a memorable name</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" id="phone-number" name="phone_number" class="form-control" 
                               placeholder="+255 700 000 000" 
                               value="{{ Auth::user()->phone ?? '' }}" required>
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
                    <p style="text-align: center; margin: 1rem 0 0 0; color: #666; font-size: 0.9rem;">
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
            
            <!-- Debug Panel (only show in development) -->
            <div class="setup-step" id="debug-panel" style="display: none;">
                <h4 style="text-align: center; margin-bottom: 1.5rem; color: #333;">🛠️ Debug Information</h4>
                
                <div style="background: #f8f9fa; border-radius: 15px; padding: 1.5rem; margin: 1rem 0;">
                    <h6 style="margin-bottom: 1rem; color: #333;">Connection Details:</h6>
                    <div id="debug-info">
                        <p><strong>WAAPI Token:</strong> <span id="debug-token">Loading...</span></p>
                        <p><strong>Instance ID:</strong> <span id="debug-instance-id">Not created</span></p>
                        <p><strong>Current Step:</strong> <span id="debug-current-step">1</span></p>
                        <p><strong>Last API Response:</strong> <pre id="debug-last-response" style="background: white; padding: 0.5rem; border-radius: 5px; font-size: 0.8rem;">None</pre></p>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-info btn-sm" onclick="testWaapiConnection()">Test WAAPI Connection</button>
                        <button class="btn btn-warning btn-sm" onclick="showDebugPanel()" style="margin-left: 0.5rem;">Show Debug</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentStep = 1;
    let instanceId = null;
    let statusCheckInterval = null;
    
    // WAAPI Configuration
    const WAAPI_BASE_URL = 'https://waapi.app/api/v1';
    const WAAPI_TOKEN = '{{ config("app.waapi_token", "ftXEQe1S8hncxJVzHRrc3JqB9eHqUmG6WIctlMPy8435fd42") }}'; // Add this to your .env file
    
    // Debug functions
    window.showDebugPanel = function() {
        $('#debug-token').text(WAAPI_TOKEN ? (WAAPI_TOKEN.substring(0, 10) + '...') : 'Not set');
        $('#debug-instance-id').text(instanceId || 'Not created');
        $('#debug-current-step').text(currentStep);
        $('.setup-step').removeClass('active');
        $('#debug-panel').addClass('active');
    };
    
    window.testWaapiConnection = function() {
        console.log('Testing WAAPI connection...');
        $('#debug-last-response').text('Testing connection...');
        
        // Test basic connection first
        $.ajax({
            url: WAAPI_BASE_URL + '/instances',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + WAAPI_TOKEN
            },
            success: function(response) {
                console.log('WAAPI instances list:', response);
                $('#debug-last-response').text('✅ Connection successful! Instances: ' + JSON.stringify(response, null, 2));
                
                // If we have an instance ID, test its endpoints
                if (instanceId) {
                    testInstanceEndpoints();
                } else {
                    alert('WAAPI connection successful! Check debug panel for details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('WAAPI connection failed:', xhr);
                $('#debug-last-response').text('❌ Connection failed: ' + JSON.stringify({
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                }, null, 2));
                alert('WAAPI connection failed! Check debug panel for details.');
            }
        });
    };
    
    // Test instance-specific endpoints
    function testInstanceEndpoints() {
        const endpoints = [
            '/instances/' + instanceId,
            '/instances/' + instanceId + '/status',
            '/instances/' + instanceId + '/client/status',
            '/instances/' + instanceId + '/qr',
            '/instances/' + instanceId + '/client/qr'
        ];
        
        let results = {};
        let completed = 0;
        
        endpoints.forEach(function(endpoint) {
            $.ajax({
                url: WAAPI_BASE_URL + endpoint,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + WAAPI_TOKEN
                },
                success: function(response) {
                    results[endpoint] = { status: '✅ Success', data: response };
                    completed++;
                    if (completed === endpoints.length) {
                        displayEndpointResults(results);
                    }
                },
                error: function(xhr, status, error) {
                    results[endpoint] = { 
                        status: '❌ Error ' + xhr.status, 
                        error: xhr.statusText || error 
                    };
                    completed++;
                    if (completed === endpoints.length) {
                        displayEndpointResults(results);
                    }
                }
            });
        });
    }
    
    // Display endpoint test results
    function displayEndpointResults(results) {
        let output = '🔍 Endpoint Test Results:\n\n';
        for (let endpoint in results) {
            output += endpoint + ': ' + results[endpoint].status + '\n';
            if (results[endpoint].error) {
                output += '  Error: ' + results[endpoint].error + '\n';
            }
            output += '\n';
        }
        
        $('#debug-last-response').text(output);
        alert('Instance endpoint tests completed! Check debug panel for details.');
    }
    
    // Add debug button to header (only in development)
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
        $('.setup-header').append(`
            <button onclick="showDebugPanel()" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 0.5rem; border-radius: 5px; font-size: 0.8rem;">
                <i class="fas fa-bug"></i> Debug
            </button>
        `);
    }
    
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
        
        // Create instance via WAAPI - Updated API call
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

                // Save instance info to database and wait for completion
                console.log('Saving instance to database...');
                saveInstanceToDatabase(instanceId, instanceName, phoneNumber, webhookUrl)
                    .done(function(response) {
                        console.log('Instance saved successfully, proceeding to connection step');
                        
                        // Move to connection step
                        showStep(2);
                        
                        // Start pairing code generation immediately after successful save
                        console.log('Starting pairing code generation...');
                        generatePairingCode();
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Failed to save instance to database, but continuing anyway');
                        
                        // Move to connection step anyway
                        showStep(2);
                        
                        // Add a longer delay since database save failed
                        setTimeout(function() {
                            console.log('Starting pairing code generation (after database save failure)...');
                            generatePairingCode();
                        }, 3000); // Wait 3 seconds if database save failed
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
                status: 0, // 0 = pending, 1 = approved
                is_paid: 1, // Set to 1 if user has paid
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
                // Continue anyway since the instance was created in WAAPI
            }
        });
    }
    
    // Generate Pairing Code
    function generatePairingCode() {
        console.log('generatePairingCode called, instanceId:', instanceId);
        
        if (!instanceId) {
            console.error('No instance ID available for pairing code generation');
            $('#pairing-code-display').html('<div style="color: #dc3545;">No instance ID available</div>');
            return;
        }
        
        console.log('Generating pairing code for instance:', instanceId);
        
        // Show waiting message
        $('#pairing-code-display').html(`
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
        const maxAttempts = 15; // 15 attempts * 3 seconds = 45 seconds max wait
        
        const statusPoller = setInterval(function() {
            attempts++;
            
            checkInstanceStatusForPairing(attempts, maxAttempts, statusPoller);
        }, 3000); // Check every 3 seconds
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
                
                // Handle your backend response structure
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
                // Continue polling for other states (booting, starting, etc.)
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
        
        // Handle your backend response format
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
        
        // Add code expiry timer and regenerate button below the container
        $('.pairing-code-container p').html(`
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
    
    // Generate QR Code
    function generateQRCode() {
        if (!instanceId) {
            console.error('No instance ID available for QR generation');
            $('#qr-code').html('<div style="color: #dc3545;">No instance ID available</div>');
            return;
        }
        
        console.log('Generating QR for instance:', instanceId);
        
        // Show waiting message
        $('#qr-code').html(`
            <div style="text-align: center;">
                <div class="spinner"></div>
                <div>Waiting for instance to be ready...</div>
                <small style="color: #666; margin-top: 0.5rem; display: block;">This may take 30-60 seconds</small>
            </div>
        `);
        
        // First wait for instance to be ready
        waitForInstanceReady();
    }
    
    // Wait for instance to be in QR mode
    function waitForInstanceReady() {
        let attempts = 0;
        const maxAttempts = 20; // 20 attempts * 3 seconds = 60 seconds max wait
        
        const statusPoller = setInterval(function() {
            attempts++;
            
            // Try multiple endpoints to check instance status
            checkInstanceStatus(attempts, maxAttempts, statusPoller);
        }, 3000); // Check every 3 seconds
    }
    
    // Check instance status with multiple endpoint fallbacks
    function checkInstanceStatus(attempts, maxAttempts, statusPoller) {
        // First try the client status endpoint
        $.ajax({
            url: WAAPI_BASE_URL + '/instances/' + instanceId + '/client/status',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + WAAPI_TOKEN
            },
            success: function(response) {
                console.log('Client status check:', response);
                handleStatusResponse(response, attempts, maxAttempts, statusPoller);
            },
            error: function(xhr, status, error) {
                console.log('Client status failed, trying alternative endpoint...');
                
                // Try alternative endpoint
                $.ajax({
                    url: WAAPI_BASE_URL + '/instances/' + instanceId,
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + WAAPI_TOKEN
                    },
                    success: function(response) {
                        console.log('Instance info check:', response);
                        handleStatusResponse(response, attempts, maxAttempts, statusPoller);
                    },
                    error: function(xhr2, status2, error2) {
                        console.error('Both status checks failed:', {
                            clientStatus: { status: xhr.status, error: error },
                            instanceInfo: { status: xhr2.status, error: error2 },
                            attempt: attempts
                        });
                        
                        if (xhr.status === 404 || xhr2.status === 404) {
                            clearInterval(statusPoller);
                            showInstanceError('Instance not found - may have been deleted');
                        } else if (attempts >= maxAttempts) {
                            clearInterval(statusPoller);
                            showInstanceTimeout();
                        }
                        // Continue trying for other errors
                    }
                });
            }
        });
    }
    
    // Handle status response from any endpoint
    function handleStatusResponse(response, attempts, maxAttempts, statusPoller) {
        let status = null;
        let instanceStatus = null;
        
        // Handle different response structures - prioritize the WAAPI clientStatus structure
        if (response.clientStatus) {
            status = response.clientStatus.status;
            instanceStatus = response.clientStatus.instanceStatus;
        } else if (response.data) {
            status = response.data.status || response.data.state;
            instanceStatus = response.data.instanceStatus;
        } else if (response.status) {
            status = response.status;
        } else if (response.state) {
            status = response.state;
        } else if (response.client && response.client.status) {
            status = response.client.status;
        }
        
        console.log('Parsed instance status:', status, 'Instance status:', instanceStatus, 'Attempt:', attempts);
        
        // Update UI with current status
        $('#qr-code').html(`
            <div style="text-align: center;">
                <div class="spinner"></div>
                <div>Instance Status: ${instanceStatus || status || 'Unknown'}</div>
                <small style="color: #666; margin-top: 0.5rem; display: block;">
                    Attempt ${attempts}/${maxAttempts} - Waiting for QR mode...
                </small>
            </div>
        `);
        
        // Check if ready for QR
        if (instanceStatus === 'qr' || instanceStatus === 'qr_code' || instanceStatus === 'waiting_for_qr' || instanceStatus === 'ready') {
            clearInterval(statusPoller);
            console.log('Instance ready, requesting QR code...');
            requestQRCode();
            
        } else if (instanceStatus === 'connected' || instanceStatus === 'authenticated' || instanceStatus === 'open') {
            clearInterval(statusPoller);
            console.log('Instance already connected!');
            updateInstanceStatus('connected');
            $('#instance-id-display').text(instanceId);
            showStep(3);
            
        } else if (instanceStatus === 'error' || instanceStatus === 'closed' || instanceStatus === 'timeout') {
            clearInterval(statusPoller);
            console.error('Instance in error state:', instanceStatus);
            showInstanceError(instanceStatus);
            
        } else if (attempts >= maxAttempts) {
            clearInterval(statusPoller);
            console.error('Timeout waiting for instance to be ready');
            showInstanceTimeout();
        }
        // Continue polling for other states (booting, starting, etc.)
    }
    
    // Request QR code when instance is ready
    function requestQRCode() {
        console.log('Requesting QR code for instance:', instanceId);
        
        // Try the client action endpoint first
        $.ajax({
            url: WAAPI_BASE_URL + '/instances/' + instanceId + '/client/action/get-qr',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + WAAPI_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({}),
            success: function(response) {
                console.log('QR request response:', response);
                handleQRResponse(response);
            },
            error: function(xhr, status, error) {
                console.log('Primary QR endpoint failed, trying alternatives...');
                
                // Try alternative endpoint 1: request-pairing-code
                $.ajax({
                    url: WAAPI_BASE_URL + '/instances/' + instanceId + '/client/action/request-pairing-code',
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + WAAPI_TOKEN,
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({}),
                    success: function(response) {
                        console.log('Pairing code request response:', response);
                        handleQRResponse(response);
                    },
                    error: function(xhr2, status2, error2) {
                        console.log('Pairing code endpoint failed, trying direct QR fetch...');
                        
                        // Try direct QR fetch
                        fetchQRCode();
                    }
                });
            }
        });
    }
    
    // Handle QR response from any endpoint
    function handleQRResponse(response) {
        let qrCode = null;
        
        console.log('Parsing QR response:', response);
        
        // Check the WAAPI response structure: response.qrCode.data.qr_code
        if (response.qrCode && response.qrCode.data && response.qrCode.data.qr_code) {
            qrCode = response.qrCode.data.qr_code;
        } else if (response.qrCode && response.qrCode.data && response.qrCode.data.qr) {
            qrCode = response.qrCode.data.qr;
        } else if (response.data && response.data.qr_code) {
            qrCode = response.data.qr_code;
        } else if (response.data && response.data.qr) {
            qrCode = response.data.qr;
        } else if (response.qr_code) {
            qrCode = response.qr_code;
        } else if (response.qr) {
            qrCode = response.qr;
        }
        
        console.log('Extracted QR code:', qrCode ? 'Found (length: ' + qrCode.length + ')' : 'Not found');
        
        if (qrCode) {
            displayQRCode(qrCode);
        } else {
            console.log('No QR code in response, trying direct fetch...');
            fetchQRCode();
        }
    }
    
    // Fetch QR code from alternative endpoint
    function fetchQRCode() {
        const qrEndpoints = [
            '/instances/' + instanceId + '/qr',
            '/instances/' + instanceId + '/client/qr',
            '/instances/' + instanceId + '/qr-code'
        ];
        
        let endpointIndex = 0;
        
        function tryNextEndpoint() {
            if (endpointIndex >= qrEndpoints.length) {
                console.error('All QR endpoints failed');
                showQRError('Unable to generate QR code from any endpoint');
                return;
            }
            
            const endpoint = qrEndpoints[endpointIndex];
            console.log('Trying QR endpoint:', WAAPI_BASE_URL + endpoint);
            
            $.ajax({
                url: WAAPI_BASE_URL + endpoint,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + WAAPI_TOKEN
                },
                success: function(response) {
                    console.log('QR fetch response from', endpoint, ':', response);
                    handleQRResponse(response);
                },
                error: function(xhr, status, error) {
                    console.log('QR endpoint failed:', endpoint, {
                        status: xhr.status,
                        error: error
                    });
                    
                    endpointIndex++;
                    tryNextEndpoint();
                }
            });
        }
        
        tryNextEndpoint();
    }
    
    // Display QR code
    function displayQRCode(qrCodeData) {
        console.log('Displaying QR code, data type:', typeof qrCodeData, 'starts with:', qrCodeData.substring(0, 50));
        
        // Check if it's already a data URL or if we need to add the prefix
        let imgSrc = qrCodeData;
        if (!qrCodeData.startsWith('data:image/')) {
            imgSrc = qrCodeData; // It's already a full data URL based on your response
        }
        
        $('#qr-code').html(`
            <img src="${imgSrc}" style="max-width: 200px; border-radius: 10px;" alt="WhatsApp QR Code">
            <div style="text-align: center; margin-top: 1rem;">
                <button class="btn btn-outline-primary btn-sm" onclick="generateQRCode()">
                    <i class="fas fa-redo"></i> Refresh QR Code
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="switchConnectionMethod('pairing')" style="margin-left: 0.5rem;">
                    <i class="fas fa-key"></i> Use Pairing Code Instead
                </button>
            </div>
        `);
        
        console.log('QR code displayed successfully');
        startStatusCheck();
    }
    
    // Show instance error
    function showInstanceError(errorStatus) {
        $('#qr-code').html(`
            <div style="text-align: center; color: #dc3545;">
                <i class="fas fa-exclamation-triangle fa-2x"></i><br>
                <strong>Instance Error</strong><br>
                <small>Status: ${errorStatus}</small><br>
                <button class="btn btn-danger btn-sm" onclick="location.reload()" style="margin-top: 1rem;">
                    <i class="fas fa-refresh"></i> Restart Setup
                </button>
            </div>
        `);
    }
    
    // Show instance timeout
    function showInstanceTimeout() {
        $('#qr-code').html(`
            <div style="text-align: center; color: #856404;">
                <i class="fas fa-clock fa-2x"></i><br>
                <strong>Instance Setup Timeout</strong><br>
                <small>The instance is taking too long to initialize</small><br>
                <button class="btn btn-warning btn-sm" onclick="retryInstanceSetup()" style="margin-top: 1rem;">
                    <i class="fas fa-redo"></i> Try Again
                </button>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()" style="margin-top: 1rem; margin-left: 0.5rem;">
                    <i class="fas fa-refresh"></i> Restart
                </button>
            </div>
        `);
    }
    
    // Show QR error
    function showQRError(errorMessage) {
        $('#qr-code').html(`
            <div style="text-align: center; color: #dc3545;">
                <i class="fas fa-times-circle fa-2x"></i><br>
                <strong>QR Generation Failed</strong><br>
                <small>${errorMessage}</small><br>
                <button class="btn btn-warning btn-sm" onclick="retryQRGeneration()" style="margin-top: 1rem;">
                    <i class="fas fa-redo"></i> Retry
                </button>
            </div>
        `);
    }
    
    // Show retry option for QR code generation - Updated to prevent suspicious activity
    function showQRRetryOption() {
        $('#qr-code').html(`
            <div style="text-align: center;">
                <div style="color: #856404; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    QR generation failed
                </div>
                <button class="btn btn-warning" onclick="retryQRGeneration()">
                    <i class="fas fa-redo"></i> Retry QR Generation
                </button>
                <div style="margin-top: 1rem;">
                    <button class="btn btn-secondary btn-sm" onclick="location.reload()">
                        <i class="fas fa-refresh"></i> Restart Setup
                    </button>
                </div>
                <div style="margin-top: 0.5rem;">
                    <small style="color: #666;">
                        Click retry when ready - no automatic retries to prevent rate limiting
                    </small>
                </div>
            </div>
        `);
    }
    
    // Check connection status - Updated for better flow
    function startStatusCheck() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
        
        console.log('Starting status monitoring for instance:', instanceId);
        
        statusCheckInterval = setInterval(function() {
            if (!instanceId) return;
            
            // Try multiple endpoints for status checking
            checkConnectionStatus();
        }, 5000); // Check every 5 seconds
    }
    
    // Check connection status with backend endpoint
    function checkConnectionStatus() {
        // Use your backend endpoint for status checking
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
                console.log('Connection status monitor response:', response);
                handleConnectionStatus(response);
            },
            error: function(xhr, status, error) {
                console.log('Connection status check error (continuing):', {
                    status: xhr.status,
                    error: error
                });
                
                // Handle critical errors
                if (xhr.status === 404) {
                    clearInterval(statusCheckInterval);
                    $('#connection-status').removeClass('connecting connected').addClass('error')
                        .html('<i class="fas fa-exclamation-triangle"></i><div><strong>Instance not found</strong><br><small>Please refresh and start over</small></div>');
                } else if (xhr.status === 401) {
                    clearInterval(statusCheckInterval);
                    $('#connection-status').removeClass('connecting connected').addClass('error')
                        .html('<i class="fas fa-exclamation-triangle"></i><div><strong>Authentication failed</strong><br><small>Please check your credentials</small></div>');
                }
                // Continue checking for other errors
            }
        });
    }
    
    // Handle connection status response
    function handleConnectionStatus(response) {
        let status = null;
        let instanceStatus = null;
        
        // Handle your backend response format
        if (response.clientStatus) {
            status = response.clientStatus.status;
            instanceStatus = response.clientStatus.instanceStatus;
        } else if (response.status === 'success' && response.data) {
            status = response.data.status;
            instanceStatus = response.data.instanceStatus;
        }
        
        console.log('Current connection status:', status, 'Instance status:', instanceStatus);
        
        if (status === 'connected' || status === 'authenticated' || status === 'open' || instanceStatus === 'ready') {
            clearInterval(statusCheckInterval);
            console.log('WhatsApp connected successfully!');
            
            // Update database status using backend endpoint
            updateInstanceStatus('connected');
            
            // Show success
            $('#instance-id-display').text(instanceId);
            showStep(3);
            
        } else if (status === 'qr' || status === 'qr_code' || status === 'waiting_for_qr' || instanceStatus === 'qr') {
            // Still waiting for scan
            let methodText = connectionMethod === 'pairing' ? 'enter the pairing code' : 'scan the QR code';
            $('#connection-status').removeClass('connected error').addClass('connecting')
                .html(`<div class="spinner"></div><div><strong>Waiting for connection...</strong><br><small>Please ${methodText} with your phone</small></div>`);
                
        } else if (status === 'timeout' || status === 'error' || status === 'closed') {
            clearInterval(statusCheckInterval);
            $('#connection-status').removeClass('connecting connected').addClass('error')
                .html(`
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Connection failed</strong><br>
                        <small>Status: ${status || instanceStatus}. Please refresh and try again</small>
                    </div>
                `);
        } else if (instanceStatus === 'booting' || instanceStatus === 'starting') {
            // Instance still starting up
            $('#connection-status').removeClass('connected error').addClass('connecting')
                .html(`<div class="spinner"></div><div><strong>Instance initializing...</strong><br><small>Status: ${instanceStatus}. Please wait...</small></div>`);
        } else {
            // Unknown status, keep checking but show info
            $('#connection-status').removeClass('connected error').addClass('connecting')
                .html(`<div class="spinner"></div><div><strong>Status: ${status || 'Unknown'}</strong><br><small>Instance: ${instanceStatus || 'Unknown'}. Please wait...</small></div>`);
        }
    }
    
    // Update instance status in database
    function updateInstanceStatus(status) {
        $.ajax({
            url: '{{ url("api/update-instance-status") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                instance_id: instanceId,
                status: status
            }),
            error: function(xhr, status, error) {
                console.error('Failed to update instance status:', xhr.responseText);
            }
        });
    }
    
    // Continue to dashboard
    $('#continue-to-dashboard').on('click', function() {
        window.location.href = '{{ url("home") }}';
    });
    
    // Cleanup intervals on page unload
    $(window).on('beforeunload', function() {
        if (qrCheckInterval) clearInterval(qrCheckInterval);
        if (statusCheckInterval) clearInterval(statusCheckInterval);
    });
    
    // Global functions - make them accessible outside this document ready block
    window.generatePairingCode = generatePairingCode;
    window.generateQRCode = generateQRCode;
    window.retryInstanceSetup = function() {
        generateQRCode();
    };
    window.retryQRGeneration = function() {
        console.log('Manual QR generation retry requested');
        $('#qr-code').html(`
            <div style="text-align: center;">
                <div class="spinner"></div>
                <div>Retrying QR Code Generation...</div>
            </div>
        `);
        
        // Add a small delay to prevent rapid requests
        setTimeout(function() {
            generateQRCode();
        }, 1000);
    };
});
</script>
@endsection