<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-schema" content="{{ auth()->user()->uuid ?? auth()->id() ?? 'default' }}">
    <title>Unified Notification API Test Interface</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #3498db; color: white; padding: 15px; border-radius: 8px 8px 0 0; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .row { display: flex; gap: 20px; }
        .col-6 { flex: 1; }
        .status { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status.pending { background: #f39c12; color: white; }
        .status.sent { background: #3498db; color: white; }
        .status.delivered { background: #27ae60; color: white; }
        .status.failed { background: #e74c3c; color: white; }
        .notification { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .toast { background: #333; color: white; padding: 15px; border-radius: 4px; margin-bottom: 10px; }
        .toast.success { background: #27ae60; }
        .toast.error { background: #e74c3c; }
        .toast.warning { background: #f39c12; }
        .qr-code { text-align: center; padding: 20px; }
        .qr-code img { max-width: 300px; border: 2px solid #ddd; }
        .session-list { list-style: none; }
        .session-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: between; align-items: center; }
        .session-info { flex: 1; }
        .session-actions { display: flex; gap: 10px; }
        .message-list { max-height: 400px; overflow-y: auto; }
        .message-item { padding: 10px; border-bottom: 1px solid #eee; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
            🚀 Unified Notification API Test Interface
        </h1>

        <!-- Single Message Test -->
        <div class="card">
            <div class="card-header">
                <h3>📱 Send Single WhatsApp Message</h3>
            </div>
            <div class="card-body">
                <form id="singleMessageForm">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" id="singlePhone" placeholder="+254712345678" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Priority</label>
                                <select class="form-control" id="singlePriority">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="form-control" id="singleMessage" rows="3" placeholder="Hello, this is a test message from the unified API!" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="singleSendBtn">Send Message</button>
                </form>
                <div id="singleResult" class="hidden" style="margin-top: 15px;">
                    <h4>Result:</h4>
                    <pre id="singleResultContent"></pre>
                </div>
            </div>
        </div>

        <!-- Bulk Message Test -->
        <div class="card">
            <div class="card-header">
                <h3>📊 Send Bulk WhatsApp Messages</h3>
            </div>
            <div class="card-body">
                <form id="bulkMessageForm">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Rate Limit (messages/minute)</label>
                                <input type="number" class="form-control" id="bulkRateLimit" value="60" min="10" max="300">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Batch Size</label>
                                <input type="number" class="form-control" id="bulkBatchSize" value="25" min="10" max="100">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Recipients (JSON format)</label>
                        <textarea class="form-control" id="bulkRecipients" rows="5" placeholder='[
  {"to": "+254712345678", "message": "Hello John!"},
  {"to": "+254723456789", "message": "Hello Jane!"}
]'></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning" id="bulkSendBtn">Send Bulk Messages</button>
                </form>
                <div id="bulkResult" class="hidden" style="margin-top: 15px;">
                    <h4>Result:</h4>
                    <pre id="bulkResultContent"></pre>
                </div>
            </div>
        </div>

        <!-- Session Management -->
        <div class="card">
            <div class="card-header">
                <h3>🔗 WhatsApp Session Management</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Create New Session</h4>
                        <form id="sessionForm">
                            <div class="form-group">
                                <label>Session Name</label>
                                <input type="text" class="form-control" id="sessionName" placeholder="My Business WhatsApp" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" id="sessionPhone" placeholder="+254712345678" required>
                            </div>
                            <button type="submit" class="btn btn-success" id="createSessionBtn">Create Session</button>
                        </form>
                    </div>
                    <div class="col-6">
                        <h4>Session QR Code</h4>
                        <div id="qrCodeContainer" class="qr-code">
                            <p>Create a session to generate QR code</p>
                        </div>
                        <div style="text-align: center;">
                            <button class="btn btn-primary" id="refreshQRBtn" disabled>Refresh QR Code</button>
                        </div>
                    </div>
                </div>
                <hr style="margin: 20px 0;">
                <div>
                    <h4>Active Sessions</h4>
                    <ul id="sessionsList" class="session-list">
                        <li>Loading sessions...</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Message Status Monitoring -->
        <div class="card">
            <div class="card-header">
                <h3>📈 Message Status Monitoring</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Message ID</label>
                            <input type="text" class="form-control" id="statusMessageId" placeholder="Enter message ID">
                        </div>
                        <button class="btn btn-primary" id="checkStatusBtn">Check Status</button>
                    </div>
                    <div class="col-6">
                        <div id="statusResult">
                            <p>Enter a message ID to check status</p>
                        </div>
                    </div>
                </div>
                <hr style="margin: 20px 0;">
                <div>
                    <h4>Recent Messages</h4>
                    <button class="btn btn-primary" id="loadMessagesBtn">Load Recent Messages</button>
                    <div id="messagesList" class="message-list" style="margin-top: 15px;">
                        <p>Click "Load Recent Messages" to view message history</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification container -->
    <div id="notificationContainer" class="notification"></div>

    <!-- Load the unified API JavaScript -->
    <script src="{{ asset('js/unified-notification-api.js') }}"></script>
    
    <script>
        let currentSessionId = null;
        let NotificationAPI = null;

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for API to initialize
            setTimeout(() => {
                if (window.NotificationAPI) {
                    NotificationAPI = window.NotificationAPI;
                    loadSessions();
                    setupEventHandlers();
                } else {
                    console.error('Unified Notification API not loaded');
                    showNotification('Failed to initialize API', 'error');
                }
            }, 100);
        });

        function setupEventHandlers() {
            // Single message form
            document.getElementById('singleMessageForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const phone = document.getElementById('singlePhone').value;
                const message = document.getElementById('singleMessage').value;
                const priority = document.getElementById('singlePriority').value;
                
                const btn = document.getElementById('singleSendBtn');
                NotificationAPI.showLoading(btn, 'Sending...');

                try {
                    const result = await NotificationAPI.sendMessage({
                        to: NotificationAPI.formatPhoneNumber(phone),
                        message: message,
                        priority: priority
                    });

                    document.getElementById('singleResult').classList.remove('hidden');
                    document.getElementById('singleResultContent').textContent = JSON.stringify(result, null, 2);
                    
                    if (result.success) {
                        showNotification('Message sent successfully!', 'success');
                    } else {
                        showNotification(result.error || 'Failed to send message', 'error');
                    }
                } catch (error) {
                    showNotification('Error: ' + error.message, 'error');
                } finally {
                    NotificationAPI.hideLoading(btn, 'Send Message');
                }
            });

            // Bulk message form
            document.getElementById('bulkMessageForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const rateLimit = parseInt(document.getElementById('bulkRateLimit').value);
                const batchSize = parseInt(document.getElementById('bulkBatchSize').value);
                const recipients = document.getElementById('bulkRecipients').value;
                
                const btn = document.getElementById('bulkSendBtn');
                NotificationAPI.showLoading(btn, 'Sending Bulk...');

                try {
                    const messages = JSON.parse(recipients);
                    
                    const result = await NotificationAPI.sendBulkMessages({
                        messages: messages,
                        rate_limit: rateLimit,
                        batch_size: batchSize,
                        priority: 'normal'
                    });

                    document.getElementById('bulkResult').classList.remove('hidden');
                    document.getElementById('bulkResultContent').textContent = JSON.stringify(result, null, 2);
                    
                    if (result.success) {
                        showNotification(`Bulk messages queued: ${result.queued_messages}`, 'success');
                    } else {
                        showNotification(result.error || 'Failed to send bulk messages', 'error');
                    }
                } catch (error) {
                    showNotification('Error: ' + error.message, 'error');
                } finally {
                    NotificationAPI.hideLoading(btn, 'Send Bulk Messages');
                }
            });

            // Session creation form
            document.getElementById('sessionForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const name = document.getElementById('sessionName').value;
                const phone = document.getElementById('sessionPhone').value;
                
                const btn = document.getElementById('createSessionBtn');
                NotificationAPI.showLoading(btn, 'Creating...');

                try {
                    const result = await NotificationAPI.createSession({
                        name: name,
                        phone_number: NotificationAPI.formatPhoneNumber(phone),
                        webhook_enabled: false
                    });

                    if (result.success) {
                        currentSessionId = result.data.id;
                        showNotification('Session created successfully!', 'success');
                        loadSessions();
                        connectAndGetQR(currentSessionId);
                        
                        // Enable QR refresh button
                        document.getElementById('refreshQRBtn').disabled = false;
                    } else {
                        showNotification(result.error || 'Failed to create session', 'error');
                    }
                } catch (error) {
                    showNotification('Error: ' + error.message, 'error');
                } finally {
                    NotificationAPI.hideLoading(btn, 'Create Session');
                }
            });

            // Status check
            document.getElementById('checkStatusBtn').addEventListener('click', async function() {
                const messageId = document.getElementById('statusMessageId').value;
                
                if (!messageId) {
                    showNotification('Please enter a message ID', 'warning');
                    return;
                }

                try {
                    const result = await NotificationAPI.getMessageStatus(messageId);
                    
                    const statusDiv = document.getElementById('statusResult');
                    if (result.success) {
                        statusDiv.innerHTML = `
                            <div class="message-item">
                                <strong>Status:</strong> <span class="status ${result.data.status}">${result.data.status}</span><br>
                                <strong>Recipient:</strong> ${result.data.recipient}<br>
                                <strong>Sent:</strong> ${formatTimestamp(result.data.sent_at)}<br>
                                ${result.data.delivered_at ? `<strong>Delivered:</strong> ${formatTimestamp(result.data.delivered_at)}<br>` : ''}
                            </div>
                        `;
                    } else {
                        statusDiv.innerHTML = `<p style="color: red;">Error: ${result.error}</p>`;
                    }
                } catch (error) {
                    showNotification('Error checking status: ' + error.message, 'error');
                }
            });

            // Load messages
            document.getElementById('loadMessagesBtn').addEventListener('click', loadRecentMessages);

            // QR refresh
            document.getElementById('refreshQRBtn').addEventListener('click', function() {
                if (currentSessionId) {
                    connectAndGetQR(currentSessionId);
                }
            });
        }

        async function connectAndGetQR(sessionId) {
            try {
                // Connect session
                await NotificationAPI.connectSession(sessionId);
                
                // Get QR code
                const qrResult = await NotificationAPI.getQRCode(sessionId);
                
                if (qrResult.success && qrResult.data.qr_code) {
                    document.getElementById('qrCodeContainer').innerHTML = `
                        <img src="${qrResult.data.qr_code}" alt="QR Code">
                        <p>Scan this QR code with WhatsApp</p>
                    `;
                } else {
                    document.getElementById('qrCodeContainer').innerHTML = '<p>Failed to generate QR code</p>';
                }
            } catch (error) {
                showNotification('Error generating QR code: ' + error.message, 'error');
            }
        }

        async function loadSessions() {
            try {
                const result = await NotificationAPI.getSessions();
                
                const sessionsList = document.getElementById('sessionsList');
                
                if (result.success && result.data.length > 0) {
                    sessionsList.innerHTML = result.data.map(session => `
                        <li class="session-item">
                            <div class="session-info">
                                <strong>${session.name}</strong><br>
                                <small>${session.phone_number} - <span class="status ${session.status}">${session.status}</span></small>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-primary" onclick="connectAndGetQR(${session.id})">QR Code</button>
                                <button class="btn btn-danger" onclick="deleteSession(${session.id})">Delete</button>
                            </div>
                        </li>
                    `).join('');
                } else {
                    sessionsList.innerHTML = '<li>No sessions found</li>';
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
                document.getElementById('sessionsList').innerHTML = '<li>Error loading sessions</li>';
            }
        }

        async function deleteSession(sessionId) {
            if (!confirm('Are you sure you want to delete this session?')) {
                return;
            }

            try {
                const result = await NotificationAPI.deleteSession(sessionId);
                
                if (result.success) {
                    showNotification('Session deleted successfully', 'success');
                    loadSessions();
                } else {
                    showNotification('Failed to delete session', 'error');
                }
            } catch (error) {
                showNotification('Error deleting session: ' + error.message, 'error');
            }
        }

        async function loadRecentMessages() {
            try {
                const result = await NotificationAPI.getMessages({
                    per_page: 10
                });
                
                const messagesList = document.getElementById('messagesList');
                
                if (result.success && result.data.length > 0) {
                    messagesList.innerHTML = result.data.map(msg => `
                        <div class="message-item">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>ID: ${msg.id}</strong> - ${msg.recipient}<br>
                                    <small>${msg.message}</small>
                                </div>
                                <span class="status ${msg.status}">${msg.status}</span>
                            </div>
                            <small style="color: #666;">${formatTimestamp(msg.sent_at)}</small>
                        </div>
                    `).join('');
                } else {
                    messagesList.innerHTML = '<p>No messages found</p>';
                }
            } catch (error) {
                showNotification('Error loading messages: ' + error.message, 'error');
            }
        }

        function showNotification(message, type = 'info', duration = 5000) {
            const container = document.getElementById('notificationContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button style="float: right; background: none; border: none; color: white; cursor: pointer;" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, duration);
        }
    </script>
</body>
</html>