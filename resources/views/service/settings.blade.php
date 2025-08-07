<div class="settings-content">
    <div class="content-header">
        <h2 class="content-title">
            <i class="fas fa-cog" style="color: #6366f1;"></i>
            System Settings
        </h2>
        <button class="btn btn-success" onclick="saveAllSettings()">
            <i class="fas fa-save"></i>
            Save All Settings
        </button>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="fas fa-globe"></i>
                    General Settings
                </h4>
                <form id="generalSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name" value="SafariChat Ltd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="contact_email" value="info@safarichat.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time Zone</label>
                        <select class="form-select" name="timezone">
                            <option value="UTC">UTC</option>
                            <option value="Africa/Nairobi" selected>Africa/Nairobi</option>
                            <option value="Africa/Lagos">Africa/Lagos</option>
                            <option value="Africa/Cairo">Africa/Cairo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Language</label>
                        <select class="form-select" name="language">
                            <option value="en" selected>English</option>
                            <option value="sw">Swahili</option>
                            <option value="fr">French</option>
                            <option value="ar">Arabic</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="fas fa-bell"></i>
                    Notification Settings
                </h4>
                <form id="notificationSettingsForm">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">
                            Email Notifications
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="smsNotifications">
                        <label class="form-check-label" for="smsNotifications">
                            SMS Notifications
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="whatsappNotifications" checked>
                        <label class="form-check-label" for="whatsappNotifications">
                            WhatsApp Notifications
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notification Frequency</label>
                        <select class="form-select" name="notification_frequency">
                            <option value="instant" selected>Instant</option>
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </h4>
                <form id="securitySettingsForm">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                        <label class="form-check-label" for="twoFactorAuth">
                            Two-Factor Authentication
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="loginAlerts" checked>
                        <label class="form-check-label" for="loginAlerts">
                            Login Alerts
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Session Timeout (minutes)</label>
                        <input type="number" class="form-control" name="session_timeout" value="60" min="15" max="480">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Policy</label>
                        <select class="form-select" name="password_policy">
                            <option value="basic">Basic (8 characters)</option>
                            <option value="medium" selected>Medium (8 chars + special)</option>
                            <option value="strong">Strong (12 chars + complex)</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="settings-sidebar">
                <div class="info-card">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-primary"></i>
                        System Information
                    </h5>
                    <div class="info-item">
                        <span class="label">Version:</span>
                        <span class="value">v2.1.0</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Last Updated:</span>
                        <span class="value">August 3, 2025</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Environment:</span>
                        <span class="value badge bg-success">Production</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Storage Used:</span>
                        <span class="value">1.2 GB / 10 GB</span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line text-success"></i>
                        Quick Stats
                    </h5>
                    <div class="info-item">
                        <span class="label">Active Users:</span>
                        <span class="value">245</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Messages Sent:</span>
                        <span class="value">12,450</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Success Rate:</span>
                        <span class="value">98.5%</span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h5 class="card-title">
                        <i class="fas fa-tools text-warning"></i>
                        Actions
                    </h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="exportSettings()">
                            <i class="fas fa-download"></i>
                            Export Settings
                        </button>
                        <button class="btn btn-outline-secondary" onclick="importSettings()">
                            <i class="fas fa-upload"></i>
                            Import Settings
                        </button>
                        <button class="btn btn-outline-danger" onclick="resetSettings()">
                            <i class="fas fa-undo"></i>
                            Reset to Default
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-content .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .settings-content .content-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .settings-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }
    
    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .settings-sidebar {
        position: sticky;
        top: 2rem;
    }
    
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }
    
    .info-card .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-item .label {
        font-weight: 500;
        color: #64748b;
    }
    
    .info-item .value {
        font-weight: 600;
        color: #1e293b;
    }
    
    .form-check-label {
        font-weight: 500;
    }
</style>

<script>
function initializeSettingsForm() {
    // Auto-save settings on change
    const forms = document.querySelectorAll('.settings-content form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', autoSaveSettings);
        });
    });
}

function autoSaveSettings() {
    // Show auto-save indicator
    showNotification('Settings auto-saved', 'info');
}

function saveAllSettings() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('All settings saved successfully!', 'success');
    }, 1500);
}

function exportSettings() {
    showNotification('Exporting settings...', 'info');
    
    // Simulate file download
    setTimeout(() => {
        const element = document.createElement('a');
        const file = new Blob(['{"settings": "exported_data"}'], {type: 'application/json'});
        element.href = URL.createObjectURL(file);
        element.download = 'safarichat_settings.json';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
        
        showNotification('Settings exported successfully!', 'success');
    }, 1000);
}

function importSettings() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            showNotification('Importing settings...', 'info');
            setTimeout(() => {
                showNotification('Settings imported successfully!', 'success');
            }, 1000);
        }
    };
    input.click();
}

function resetSettings() {
    if (confirm('Are you sure you want to reset all settings to default? This action cannot be undone.')) {
        showNotification('Resetting settings...', 'info');
        setTimeout(() => {
            // Reset form values
            document.querySelectorAll('.settings-content form').forEach(form => {
                form.reset();
            });
            showNotification('Settings reset to default!', 'success');
        }, 1000);
    }
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : type === 'info' ? 'alert-info' : 'alert-warning';
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>
