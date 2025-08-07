@extends('layouts.app')
@section('content')
<style>
.status-page {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 0;
}

.status-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.status-header {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.instance-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.instance-card:hover {
    transform: translateY(-2px);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
}

.status-connected {
    background: #d4edda;
    color: #155724;
}

.status-connecting {
    background: #fff3cd;
    color: #856404;
}

.status-disconnected {
    background: #f8d7da;
    color: #721c24;
}

.status-error {
    background: #f8d7da;
    color: #721c24;
}

.test-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.btn-test {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-test:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.connection-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.info-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #25D366;
}

.info-label {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}
</style>

<div class="status-page">
    <div class="status-container">
        <!-- Header -->
        <div class="status-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; color: #333;">WhatsApp Instance Status</h2>
                    <p style="margin: 0.5rem 0 0 0; color: #666;">Monitor your WhatsApp connections and WAAPI integration</p>
                </div>
                <div>
                    <button class="btn-test" onclick="refreshInstances()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn-test" onclick="testWaapiConnection()" style="margin-left: 0.5rem;">
                        <i class="fas fa-plug"></i> Test WAAPI
                    </button>
                </div>
            </div>

            <!-- Connection Overview -->
            <div class="connection-info">
                <div class="info-item">
                    <div class="info-value" id="total-instances">-</div>
                    <div class="info-label">Total Instances</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="connected-instances">-</div>
                    <div class="info-label">Connected</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="connecting-instances">-</div>
                    <div class="info-label">Connecting</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="error-instances">-</div>
                    <div class="info-label">Errors</div>
                </div>
            </div>
        </div>

        <!-- Instances List -->
        <div id="instances-container">
            <div class="text-center" style="padding: 2rem;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p style="margin-top: 1rem;">Loading instances...</p>
            </div>
        </div>

        <!-- Test Section -->
        <div class="test-section">
            <h4 style="margin-bottom: 1.5rem;">Send Test Message</h4>
            <form id="test-message-form">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-control" id="test-instance" required>
                            <option value="">Select Instance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="test-chat-id" placeholder="Chat ID (e.g., 255700000000@c.us)" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="test-message" placeholder="Test message" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-test w-100">Send</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    loadInstances();
    
    // Auto-refresh every 30 seconds
    setInterval(loadInstances, 30000);
});

function loadInstances() {
    $.ajax({
        url: '{{ url("api/waapi/user-instances") }}',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                displayInstances(response.data);
                updateStats(response.data);
                populateTestInstanceSelect(response.data);
            } else {
                showError('Failed to load instances: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showError('Error loading instances: ' + error);
        }
    });
}

function displayInstances(instances) {
    const container = $('#instances-container');
    
    if (!instances || instances.length === 0) {
        container.html(`
            <div class="text-center" style="padding: 2rem;">
                <i class="fas fa-inbox fa-3x text-muted"></i>
                <h5 style="margin-top: 1rem; color: #666;">No WhatsApp instances found</h5>
                <p style="color: #999;">Go to <a href="{{ url('auth/business/setup') }}">Setup Page</a> to connect your WhatsApp</p>
            </div>
        `);
        return;
    }

    let html = '';
    instances.forEach(instance => {
        const statusClass = `status-${instance.status}`;
        const lastSeen = instance.last_seen ? new Date(instance.last_seen).toLocaleString() : 'Never';
        const createdAt = new Date(instance.created_at).toLocaleString();
        
        html += `
            <div class="instance-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex: 1;">
                        <h5 style="margin: 0 0 0.5rem 0; color: #333;">${instance.instance_name}</h5>
                        <p style="margin: 0; color: #666;"><i class="fas fa-phone"></i> ${instance.phone_number}</p>
                        <p style="margin: 0.25rem 0 0 0; color: #999; font-size: 0.875rem;">
                            <i class="fas fa-clock"></i> Created: ${createdAt}
                            ${instance.last_seen ? `<br><i class="fas fa-eye"></i> Last seen: ${lastSeen}` : ''}
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge ${statusClass}">${instance.status.toUpperCase()}</span>
                        <div style="margin-top: 0.5rem;">
                            <small style="color: #666;">ID: ${instance.instance_id}</small>
                        </div>
                        ${instance.webhook_url ? `<div style="margin-top: 0.25rem;"><small style="color: #666;"><i class="fas fa-link"></i> Webhook configured</small></div>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function updateStats(instances) {
    const total = instances.length;
    const connected = instances.filter(i => i.status === 'connected').length;
    const connecting = instances.filter(i => i.status === 'connecting').length;
    const errors = instances.filter(i => i.status === 'error' || i.status === 'disconnected').length;
    
    $('#total-instances').text(total);
    $('#connected-instances').text(connected);
    $('#connecting-instances').text(connecting);
    $('#error-instances').text(errors);
}

function populateTestInstanceSelect(instances) {
    const select = $('#test-instance');
    select.html('<option value="">Select Instance</option>');
    
    instances.filter(i => i.status === 'connected').forEach(instance => {
        select.append(`<option value="${instance.instance_id}">${instance.instance_name} (${instance.phone_number})</option>`);
    });
}

function refreshInstances() {
    loadInstances();
}

function testWaapiConnection() {
    $.ajax({
        url: '{{ url("api/waapi/test-connection") }}',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('WAAPI connection successful!');
            } else {
                alert('WAAPI connection failed: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('WAAPI connection error: ' + error);
        }
    });
}

$('#test-message-form').on('submit', function(e) {
    e.preventDefault();
    
    const instanceId = $('#test-instance').val();
    const chatId = $('#test-chat-id').val();
    const message = $('#test-message').val();
    
    if (!instanceId || !chatId || !message) {
        alert('Please fill all fields');
        return;
    }
    
    $.ajax({
        url: '{{ url("api/waapi/send-test-message") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            instance_id: instanceId,
            chat_id: chatId,
            message: message
        }),
        success: function(response) {
            if (response.success) {
                alert('Test message sent successfully!');
                $('#test-message-form')[0].reset();
            } else {
                alert('Failed to send message: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error sending message: ' + error);
        }
    });
});

function showError(message) {
    $('#instances-container').html(`
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
            <h5 style="margin-top: 1rem; color: #dc3545;">Error</h5>
            <p style="color: #666;">${message}</p>
            <button class="btn-test" onclick="loadInstances()">Try Again</button>
        </div>
    `);
}
</script>
@endsection
