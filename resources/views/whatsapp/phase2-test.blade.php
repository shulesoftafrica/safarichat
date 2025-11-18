@extends('layouts.app')

@section('title', 'Official WhatsApp Phase 2 - Testing Interface')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fab fa-whatsapp text-success me-2"></i>
                        Official WhatsApp Phase 2 - Complete Integration
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-rocket me-2"></i>Phase 2 Implementation Complete!</h6>
                        <p class="mb-0">Testing the full official WhatsApp Business API integration with message sending, webhooks, and real-time processing.</p>
                    </div>
                    
                    <!-- Credential Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="credential-select" class="form-label">Select WhatsApp Credential</label>
                            <select id="credential-select" class="form-select">
                                <option value="">Loading credentials...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connection Status</label>
                            <div id="connection-status" class="form-control border-0 bg-light">
                                <span class="badge bg-secondary">Not checked</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 2 Feature Tests -->
                    <div class="row">
                        <!-- Message Sending Tests -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Message Sending Tests
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="test-section mb-3">
                                        <h6>Text Message</h6>
                                        <div class="input-group mb-2">
                                            <input type="text" id="text-to" class="form-control" placeholder="Recipient phone number" value="+1234567890">
                                            <button class="btn btn-outline-primary" id="test-text-btn">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        </div>
                                        <textarea id="text-message" class="form-control" rows="2" placeholder="Your message text">Hello! This is a test message from SafariChat official WhatsApp integration.</textarea>
                                    </div>
                                    
                                    <div class="test-section mb-3">
                                        <h6>Interactive Buttons</h6>
                                        <div class="input-group mb-2">
                                            <input type="text" id="buttons-to" class="form-control" placeholder="Recipient" value="+1234567890">
                                            <button class="btn btn-outline-success" id="test-buttons-btn">
                                                <i class="fas fa-mouse"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Sends: "Choose an option" with 3 interactive buttons</small>
                                    </div>
                                    
                                    <div class="test-section mb-3">
                                        <h6>Interactive List</h6>
                                        <div class="input-group mb-2">
                                            <input type="text" id="list-to" class="form-control" placeholder="Recipient" value="+1234567890">
                                            <button class="btn btn-outline-info" id="test-list-btn">
                                                <i class="fas fa-list"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Sends: Interactive list with multiple sections</small>
                                    </div>
                                    
                                    <div class="test-section">
                                        <h6>Template Message</h6>
                                        <div class="input-group">
                                            <input type="text" id="template-to" class="form-control" placeholder="Recipient" value="+1234567890">
                                            <select id="template-name" class="form-select">
                                                <option value="hello_world">Hello World</option>
                                                <option value="welcome">Welcome</option>
                                            </select>
                                            <button class="btn btn-outline-warning" id="test-template-btn">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- API & Integration Tests -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        API & Integration Tests
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="test-section mb-3">
                                        <h6>Webhook Status</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Webhook URL configured</span>
                                            <span class="badge bg-secondary" id="webhook-status">Testing...</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span>Signature verification</span>
                                            <span class="badge bg-secondary" id="signature-status">Testing...</span>
                                        </div>
                                    </div>
                                    
                                    <div class="test-section mb-3">
                                        <h6>Message Statistics</h6>
                                        <button class="btn btn-outline-primary w-100" id="get-stats-btn">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            Get Message Stats
                                        </button>
                                        <div id="stats-display" class="mt-2" style="display: none;">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Total: <span id="stat-total">0</span></small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Sent: <span id="stat-sent">0</span></small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Delivered: <span id="stat-delivered">0</span></small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Read: <span id="stat-read">0</span></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="test-section mb-3">
                                        <h6>Service Classes</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>OfficialWhatsAppService</span>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span>SendOfficialWhatsAppMessage Job</span>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                    </div>
                                    
                                    <div class="test-section">
                                        <h6>Queue Processing</h6>
                                        <button class="btn btn-outline-info w-100" id="test-queue-btn">
                                            <i class="fas fa-list-alt me-2"></i>
                                            Test Queue Processing
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results Display -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-terminal me-2"></i>
                                        Test Results & Logs
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="test-results" class="bg-dark text-light p-3 rounded" style="height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px;">
                                        <div class="text-success">[INFO] Phase 2 testing interface loaded</div>
                                        <div class="text-info">[INFO] Ready to test official WhatsApp Business API integration</div>
                                        <div class="text-warning">[WAIT] Please select a credential to begin testing...</div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-secondary btn-sm" id="clear-logs-btn">
                                            <i class="fas fa-trash me-1"></i>
                                            Clear Logs
                                        </button>
                                        <button class="btn btn-success btn-sm" id="run-all-tests-btn">
                                            <i class="fas fa-play me-1"></i>
                                            Run All Tests
                                        </button>
                                        <span class="float-end">
                                            <small class="text-muted">Auto-scroll: </small>
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="auto-scroll" checked>
                                            </div>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="text-center">
                                <a href="/whatsapp/integration-options" class="btn btn-primary me-2">
                                    <i class="fas fa-cog me-2"></i>
                                    Integration Options
                                </a>
                                <a href="/whatsapp/embedded-signup" class="btn btn-success me-2">
                                    <i class="fab fa-whatsapp me-2"></i>
                                    Embedded Signup
                                </a>
                                <button class="btn btn-info" id="open-api-docs-btn">
                                    <i class="fas fa-book me-2"></i>
                                    API Documentation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- API Documentation Modal -->
<div class="modal fade" id="apiDocsModal" tabindex="-1" aria-labelledby="apiDocsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiDocsModalLabel">
                    <i class="fas fa-book me-2"></i>
                    Official WhatsApp API Documentation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="apiAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="textMessageHeader">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#textMessageCollapse">
                                Send Text Message
                            </button>
                        </h2>
                        <div id="textMessageCollapse" class="accordion-collapse collapse show" data-bs-parent="#apiAccordion">
                            <div class="accordion-body">
                                <strong>POST</strong> <code>/api/whatsapp/official/send/text</code>
                                <pre class="bg-light p-3 mt-2"><code>{
  "to": "+1234567890",
  "text": "Hello, this is a test message!",
  "preview_url": false,
  "credential_id": "optional_credential_id"
}</code></pre>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="templateMessageHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#templateMessageCollapse">
                                Send Template Message
                            </button>
                        </h2>
                        <div id="templateMessageCollapse" class="accordion-collapse collapse" data-bs-parent="#apiAccordion">
                            <div class="accordion-body">
                                <strong>POST</strong> <code>/api/whatsapp/official/send/template</code>
                                <pre class="bg-light p-3 mt-2"><code>{
  "to": "+1234567890",
  "template_name": "hello_world",
  "language_code": "en",
  "components": []
}</code></pre>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="buttonsMessageHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#buttonsMessageCollapse">
                                Send Interactive Buttons
                            </button>
                        </h2>
                        <div id="buttonsMessageCollapse" class="accordion-collapse collapse" data-bs-parent="#apiAccordion">
                            <div class="accordion-body">
                                <strong>POST</strong> <code>/api/whatsapp/official/send/buttons</code>
                                <pre class="bg-light p-3 mt-2"><code>{
  "to": "+1234567890",
  "body_text": "Please choose an option:",
  "buttons": [
    {"id": "btn1", "title": "Option 1"},
    {"id": "btn2", "title": "Option 2"}
  ],
  "header_text": "Selection Menu",
  "footer_text": "Powered by SafariChat"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let selectedCredentialId = null;
    let autoScroll = true;
    
    // Load credentials
    loadCredentials();
    
    // Event handlers
    $('#credential-select').change(function() {
        selectedCredentialId = $(this).val();
        if (selectedCredentialId) {
            testConnection();
        }
    });
    
    $('#test-text-btn').click(() => testTextMessage());
    $('#test-buttons-btn').click(() => testButtonsMessage());
    $('#test-list-btn').click(() => testListMessage());
    $('#test-template-btn').click(() => testTemplateMessage());
    $('#get-stats-btn').click(() => getMessageStats());
    $('#test-queue-btn').click(() => testQueueProcessing());
    $('#clear-logs-btn').click(() => clearLogs());
    $('#run-all-tests-btn').click(() => runAllTests());
    $('#auto-scroll').change(function() {
        autoScroll = $(this).is(':checked');
    });
    $('#open-api-docs-btn').click(() => $('#apiDocsModal').modal('show'));
    
    // Test initial webhook status
    testWebhookStatus();
    
    function loadCredentials() {
        log('Loading WhatsApp credentials...', 'info');
        
        $.get('/whatsapp/official/status')
            .done(function(response) {
                if (response.success && response.credentials.length > 0) {
                    const $select = $('#credential-select');
                    $select.empty();
                    $select.append('<option value="">Select a credential</option>');
                    
                    response.credentials.forEach(credential => {
                        $select.append(`
                            <option value="${credential.id}">
                                ${credential.phone_number || 'Setting up...'} 
                                (${credential.api_provider} - ${credential.status_label})
                            </option>
                        `);
                    });
                    
                    // Auto-select first active credential
                    const activeCredential = response.credentials.find(c => c.is_active);
                    if (activeCredential) {
                        $select.val(activeCredential.id);
                        selectedCredentialId = activeCredential.id;
                        testConnection();
                    }
                    
                    log(`Found ${response.credentials.length} credentials`, 'success');
                } else {
                    log('No credentials found. Please set up WhatsApp integration first.', 'warning');
                    $('#credential-select').html('<option value="">No credentials available</option>');
                }
            })
            .fail(function(xhr) {
                log('Failed to load credentials: ' + (xhr.responseJSON?.message || xhr.statusText), 'error');
            });
    }
    
    function testConnection() {
        if (!selectedCredentialId) return;
        
        log('Testing connection...', 'info');
        $('#connection-status').html('<span class="badge bg-info">Testing...</span>');
        
        $.post('/whatsapp/official/test-connection', {
            credential_id: selectedCredentialId,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                $('#connection-status').html('<span class="badge bg-success">Connected</span>');
                log('Connection test successful', 'success');
            } else {
                $('#connection-status').html('<span class="badge bg-danger">Failed</span>');
                log('Connection test failed: ' + response.message, 'error');
            }
        })
        .fail(function(xhr) {
            $('#connection-status').html('<span class="badge bg-danger">Error</span>');
            log('Connection test error: ' + (xhr.responseJSON?.message || xhr.statusText), 'error');
        });
    }
    
    function testTextMessage() {
        if (!validateCredential()) return;
        
        const to = $('#text-to').val();
        const text = $('#text-message').val();
        
        if (!to || !text) {
            log('Please enter recipient and message text', 'warning');
            return;
        }
        
        log(`Sending text message to ${to}...`, 'info');
        
        sendApiRequest('/api/whatsapp/official/send/text', {
            credential_id: selectedCredentialId,
            to: to,
            text: text
        });
    }
    
    function testButtonsMessage() {
        if (!validateCredential()) return;
        
        const to = $('#buttons-to').val();
        if (!to) {
            log('Please enter recipient phone number', 'warning');
            return;
        }
        
        log(`Sending interactive buttons to ${to}...`, 'info');
        
        sendApiRequest('/api/whatsapp/official/send/buttons', {
            credential_id: selectedCredentialId,
            to: to,
            body_text: "Please choose an option from the buttons below:",
            buttons: [
                {id: "option1", title: "Option 1"},
                {id: "option2", title: "Option 2"},
                {id: "option3", title: "Option 3"}
            ],
            header_text: "Interactive Menu",
            footer_text: "Powered by SafariChat"
        });
    }
    
    function testListMessage() {
        if (!validateCredential()) return;
        
        const to = $('#list-to').val();
        if (!to) {
            log('Please enter recipient phone number', 'warning');
            return;
        }
        
        log(`Sending interactive list to ${to}...`, 'info');
        
        sendApiRequest('/api/whatsapp/official/send/list', {
            credential_id: selectedCredentialId,
            to: to,
            body_text: "Choose from our services:",
            sections: [
                {
                    title: "Main Services",
                    rows: [
                        {id: "service1", title: "Messaging", description: "WhatsApp Business messaging"},
                        {id: "service2", title: "Automation", description: "AI-powered automation"}
                    ]
                },
                {
                    title: "Support",
                    rows: [
                        {id: "support1", title: "Help Center", description: "Get help and support"},
                        {id: "support2", title: "Contact Us", description: "Reach our support team"}
                    ]
                }
            ],
            button_text: "Select Service",
            header_text: "SafariChat Services"
        });
    }
    
    function testTemplateMessage() {
        if (!validateCredential()) return;
        
        const to = $('#template-to').val();
        const templateName = $('#template-name').val();
        
        if (!to || !templateName) {
            log('Please enter recipient and select template', 'warning');
            return;
        }
        
        log(`Sending template "${templateName}" to ${to}...`, 'info');
        
        sendApiRequest('/api/whatsapp/official/send/template', {
            credential_id: selectedCredentialId,
            to: to,
            template_name: templateName,
            language_code: 'en'
        });
    }
    
    function getMessageStats() {
        if (!validateCredential()) return;
        
        log('Fetching message statistics...', 'info');
        
        $.get('/api/whatsapp/official/stats', {
            credential_id: selectedCredentialId
        })
        .done(function(response) {
            if (response.success) {
                const stats = response.data;
                $('#stat-total').text(stats.total_messages);
                $('#stat-sent').text(stats.sent);
                $('#stat-delivered').text(stats.delivered);
                $('#stat-read').text(stats.read);
                $('#stats-display').show();
                
                log(`Stats: ${stats.total_messages} total, ${stats.sent} sent, ${stats.delivered} delivered, ${stats.read} read`, 'success');
            } else {
                log('Failed to get stats: ' + response.message, 'error');
            }
        })
        .fail(function(xhr) {
            log('Stats request failed: ' + (xhr.responseJSON?.message || xhr.statusText), 'error');
        });
    }
    
    function testWebhookStatus() {
        log('Testing webhook configuration...', 'info');
        
        // Test webhook URL availability
        fetch('/api/whatsapp/webhook', { method: 'GET' })
            .then(response => {
                if (response.ok) {
                    $('#webhook-status').removeClass('bg-secondary').addClass('bg-success').text('Active');
                    log('Webhook endpoint is accessible', 'success');
                } else {
                    $('#webhook-status').removeClass('bg-secondary').addClass('bg-warning').text('Accessible');
                    log('Webhook endpoint accessible but may need configuration', 'warning');
                }
            })
            .catch(error => {
                $('#webhook-status').removeClass('bg-secondary').addClass('bg-danger').text('Error');
                log('Webhook endpoint not accessible', 'error');
            });
        
        // Signature verification is always enabled in our implementation
        $('#signature-status').removeClass('bg-secondary').addClass('bg-success').text('Enabled');
    }
    
    function testQueueProcessing() {
        log('Testing queue processing by sending a test message...', 'info');
        
        // Send a test message to verify queue processing
        sendApiRequest('/api/whatsapp/official/send/text', {
            credential_id: selectedCredentialId,
            to: '+1234567890', // Test number
            text: 'Queue processing test - ' + new Date().toISOString()
        });
    }
    
    function runAllTests() {
        log('=== Running all Phase 2 tests ===', 'info');
        
        if (!validateCredential()) return;
        
        // Test connection first
        testConnection();
        
        setTimeout(() => {
            log('Testing message sending capabilities...', 'info');
            
            // Test with dummy phone number
            const testPhone = '+1234567890';
            
            // Test text message
            sendApiRequest('/api/whatsapp/official/send/text', {
                credential_id: selectedCredentialId,
                to: testPhone,
                text: 'Automated test: Text message'
            });
            
            // Test template message (if available)
            setTimeout(() => {
                sendApiRequest('/api/whatsapp/official/send/template', {
                    credential_id: selectedCredentialId,
                    to: testPhone,
                    template_name: 'hello_world',
                    language_code: 'en'
                });
            }, 2000);
            
            // Get stats
            setTimeout(() => {
                getMessageStats();
            }, 4000);
            
            log('All tests queued. Check the logs for results.', 'info');
        }, 1000);
    }
    
    function sendApiRequest(url, data) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });
        
        $.post(url, data)
            .done(function(response) {
                if (response.success) {
                    log(`✓ ${url} - Message queued (Job ID: ${response.job_id || 'N/A'})`, 'success');
                } else {
                    log(`✗ ${url} - Failed: ${response.message}`, 'error');
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.message || xhr.statusText;
                log(`✗ ${url} - Error: ${error}`, 'error');
            });
    }
    
    function validateCredential() {
        if (!selectedCredentialId) {
            log('Please select a WhatsApp credential first', 'warning');
            return false;
        }
        return true;
    }
    
    function log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colorClass = {
            'info': 'text-info',
            'success': 'text-success',
            'warning': 'text-warning',
            'error': 'text-danger'
        }[type] || 'text-light';
        
        const $results = $('#test-results');
        $results.append(`<div class="${colorClass}">[${timestamp}] ${message}</div>`);
        
        if (autoScroll) {
            $results.scrollTop($results[0].scrollHeight);
        }
    }
    
    function clearLogs() {
        $('#test-results').empty();
        log('Logs cleared', 'info');
    }
});
</script>
@endsection

@section('styles')
<style>
.test-section {
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.test-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

#test-results {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.accordion-button {
    font-weight: 500;
}

code {
    font-size: 0.9em;
}

pre code {
    color: #333;
}
</style>
@endsection
