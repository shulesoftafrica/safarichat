@extends('layouts.app')
@section('content')

<div class="ai-agents-management">
    <div class="container-fluid">
        <!-- Modern Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-12 d-flex align-items-center">
                    <div class="header-content flex-grow-1">
                        <div class="header-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="header-text">
                            <h1 class="page-title">Sales Settings</h1>
                            <p class="page-subtitle">
                                Configure your WhatsApp sales automation and manage your sales agent settings here.
                            </p>
                        </div>
                    </div>
                    <div class="header-actions ms-auto" style="display: flex; gap: 0.75rem; align-items: center;">
                        
                        <!-- Compact Subscription Plan Badge -->
                        <div class="plan-badge-compact">
                            <span class="badge plan-badge" data-plan="{{ $subscription_plan }}">
                                {{ strtoupper($subscription_plan) }} PLAN
                            </span>
                        </div>
                        
                        <!-- Compact AI Credits with Quick Actions -->
                        <div class="credits-section-compact">
                            <div class="credits-display-compact">
                                <i class="fas fa-coins"></i>
                                <div class="credits-info">
                                    <span class="credits-number">{{ number_format($ai_credits) }}</span>
                                    <span class="credits-label">AI Credits</span>
                                </div>
                            </div>
                            <div class="credits-actions">
                                <button class="btn-sm btn-secondary" onclick="showPurchaseCreditsModal()" title="Add Credits">
                                    <i class="fas fa-plus"></i>
                                    Add Credits
                                </button>
                                @if($subscription_plan !== 'premium')
                                    <button class="btn-sm btn-primary" onclick="showUpgradeModal('general')" title="Upgrade Plan">
                                        <i class="fas fa-arrow-up"></i>
                                        Upgrade
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons-group">
                            <button class="btn-action primary" onclick="viewAgent('{{ optional($agents->first())->uuid }}')" title="View Sales Settings" aria-label="View Sales Settings" @if($agents->count() === 0) disabled @endif>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action warning" onclick="editAgent('{{ optional($agents->first())->uuid }}')" title="Click here to manage Sales Setting" aria-label="Click here to manage Sales Setting" @if($agents->count() === 0) disabled @endif>
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        @if($agents->count() === 0)
                            <a href="{{ route('ai-agents.create') }}" class="btn-primary ms-2">
                                <i class="fas fa-plus-circle me-2"></i>
                                Create AI Agent
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="mb-4">
                <h2 class="mb-2" style="font-weight:700; color:#4b3fa7;">Configure and Manage New Sales Agents</h2>
                <p class="mb-3" style="color:#6c757d;">This section allows you to add, configure, and manage your WhatsApp sales agents. Each agent can automate and personalize your customer conversations.</p>
                <button id="createAgentBtn" class="btn-primary" onclick="handleCreateAgent()">
                    <i class="fas fa-plus-circle me-2"></i>
                    Create New Sales Agent
                </button>
            </div>
            @if($agents->count() > 0)
                <!-- Agents Grid View -->
                <div class="agents-grid">
                    @foreach($agents as $agent)
                        <div class="agent-card">
                            <!-- ...existing code... -->
                            <div class="agent-card-header">
                                <div class="agent-avatar">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="agent-status">
                                    <span class="status-badge {{ $agent->status === 'active' ? 'active' : 'inactive' }}">
                                        {{ ucfirst($agent->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="agent-card-body">
                                @if($whatsappInstance ?? false)
                                    <h3 class="agent-name">{{ $whatsappInstance->display_name ?? $whatsappInstance->instance_name ?? 'Unnamed Instance' }}</h3>
                                    <p class="agent-company">WhatsApp Instance</p>
                                    <div class="agent-details">
                                        <div class="detail-item">
                                            <span class="label">Phone:</span>
                                            <span class="value">{{ $whatsappInstance->phone_number ?? 'Not configured' }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Instance Name:</span>
                                            <span class="value">{{ $whatsappInstance->instance_name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Status:</span>
                                            <span class="value">
                                                @php
                                                    // Use real-time status from WaSender API if available, otherwise fallback to database
                                                    $currentStatus = $realTimeStatus ?? $whatsappInstance->connect_status;
                                                    $isConnected = in_array(strtolower($currentStatus), ['connected', 'ready', 'open']);
                                                    $needsScan = in_array(strtoupper($currentStatus), ['NEED_SCAN', 'PENDING', 'QR_REQUIRED']);
                                                @endphp
                                                
                                                @if($isConnected)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Connected
                                                    </span>
                                                    <small class="text-muted ms-1" style="font-size: 0.75rem;">
                                                        (Live from WaSender)
                                                    </small>
                                                @elseif($needsScan)
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-qrcode"></i> Needs QR Scan
                                                    </span>
                                                    <button class="btn btn-link p-0 ms-2" style="color:#0d6efd; font-size:0.9rem;" onclick="showReconnectModal('{{ $whatsappInstance->id }}')">
                                                        <i class="fas fa-qrcode"></i> Reconnect
                                                    </button>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle"></i> {{ ucfirst($currentStatus) }}
                                                    </span>
                                                    <button class="btn btn-link p-0 ms-2" style="color:#0d6efd; font-size:0.9rem;" onclick="showReconnectModal('{{ $whatsappInstance->id }}')">
                                                        <i class="fas fa-qrcode"></i> Reconnect
                                                    </button>
                                                @endif
                                                
                                                @if($realTimeStatus)
                                                    <button class="btn btn-link p-0 ms-1" style="color:#6c757d; font-size:0.75rem;" onclick="refreshStatus('{{ $whatsappInstance->id }}')" title="Refresh status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="agent-details">
                                        <div class="detail-item">
                                            <span class="label">Created:</span>
                                            <span class="value">{{ $whatsappInstance->created_at ? $whatsappInstance->created_at->format('M d, Y') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                @else
                                    <h3 class="agent-name">No WhatsApp Instance</h3>
                                    <p class="agent-company">Please create a WhatsApp instance to enable this agent.</p>
                                @endif
                                {{-- Optionally, show more WhatsApp instance details or tags here --}}
                            </div>
                            <div class="agent-card-footer">
                                <div class="action-buttons" style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="btn-action primary" onclick="viewInstanceStats('{{ $whatsappInstance->id ?? '' }}')" title="View Instance Performance" aria-label="View Instance Performance" @if(!$whatsappInstance) disabled @endif>
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                    <button class="btn-action warning" onclick="editWhatsappInstance('{{ $whatsappInstance->id ?? '' }}')" title="Edit WhatsApp Instance" aria-label="Edit WhatsApp Instance" @if(!$whatsappInstance) disabled @endif>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action danger" onclick="deleteWhatsappInstance('{{ $whatsappInstance->id ?? '' }}')" title="Delete WhatsApp Instance" @if(!$whatsappInstance) disabled @endif>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <!-- Reconnect Modal -->
                            

                              
                                </div>
                                <!-- Move JS to global script block below -->
                                            </div>

                                            
                                            <script>
                                            let statusCheckInterval = null;
                                            
                                            function showReconnectModal(instanceId) {
                                                // Clear any existing status check interval
                                                if (statusCheckInterval) {
                                                    clearInterval(statusCheckInterval);
                                                    statusCheckInterval = null;
                                                }
                                                
                                                fetch(`{{ url('/api/whatsapp/instances') }}/${instanceId}/reconnect`)
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        if (data.success && data.instance && data.instance.qr_code) {
                                                            // Create proper data URL for base64 QR code
                                                            const qrCodeUrl = `data:image/png;base64,${data.instance.qr_code}`;
                                                            document.getElementById('reconnectModalBody').innerHTML = `
                                                                <div class='text-center'>
                                                                    <img src='${qrCodeUrl}' alt='QR Code' style='max-width:300px; border: 1px solid #ddd; padding: 10px;'>
                                                                    <p class='mt-3'>Scan this QR code with your WhatsApp to reconnect.</p>
                                                                    <small class='text-muted'>QR code generated at: ${new Date(data.instance.qr_code_generated_at).toLocaleString()}</small>
                                                                    <div class='mt-3' id='connectionStatus'>
                                                                        <div class='spinner-border spinner-border-sm text-primary me-2' role='status'>
                                                                            <span class='visually-hidden'>Loading...</span>
                                                                        </div>
                                                                        <span class='text-muted'>Waiting for scan...</span>
                                                                    </div>
                                                                </div>
                                                            `;
                                                            
                                                            // Start polling for connection status
                                                            startStatusPolling(instanceId);
                                                        } else {
                                                            document.getElementById('reconnectModalBody').innerHTML = `<div class='alert alert-danger'>Unable to load QR code. Please try again later.</div>`;
                                                        }
                                                        
                                                        const modalElement = document.getElementById('reconnectModal');
                                                        const modal = new bootstrap.Modal(modalElement);
                                                        modal.show();
                                                        
                                                        // Clear interval when modal is closed
                                                        modalElement.addEventListener('hidden.bs.modal', function () {
                                                            if (statusCheckInterval) {
                                                                clearInterval(statusCheckInterval);
                                                                statusCheckInterval = null;
                                                            }
                                                        });
                                                    })
                                                    .catch(error => {
                                                        console.error('Reconnect error:', error);
                                                        document.getElementById('reconnectModalBody').innerHTML = `<div class='alert alert-danger'>Error loading QR code.</div>`;
                                                        new bootstrap.Modal(document.getElementById('reconnectModal')).show();
                                                    });
                                            }
                                            
                                            function startStatusPolling(instanceId) {
                                                // Poll every 2 seconds
                                                statusCheckInterval = setInterval(function() {
                                                    fetch(`{{ url('/api/whatsapp/instances') }}/${instanceId}/status`, {
                                                        method: 'GET',
                                                        headers: {
                                                            'Accept': 'application/json',
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                        },
                                                        credentials: 'same-origin'
                                                    })
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        if (data.success && data.status) {
                                                            const status = data.status.toLowerCase();
                                                            const connectionStatusEl = document.getElementById('connectionStatus');
                                                            
                                                            if (status === 'connected' || status === 'ready' || status === 'open') {
                                                                // Connected! Show success message and reload
                                                                if (connectionStatusEl) {
                                                                    connectionStatusEl.innerHTML = `
                                                                        <div class='alert alert-success mb-0'>
                                                                            <i class='fas fa-check-circle me-2'></i>
                                                                            <strong>Connected successfully!</strong> Reloading page...
                                                                        </div>
                                                                    `;
                                                                }
                                                                
                                                                // Clear the interval
                                                                clearInterval(statusCheckInterval);
                                                                statusCheckInterval = null;
                                                                
                                                                // Hide the warning banner immediately
                                                                const warningBanner = document.querySelector('.whatsapp-warning-banner');
                                                                if (warningBanner) {
                                                                    warningBanner.style.transition = 'opacity 0.3s ease-out';
                                                                    warningBanner.style.opacity = '0';
                                                                    setTimeout(function() {
                                                                        warningBanner.style.display = 'none';
                                                                    }, 300);
                                                                }
                                                                
                                                                // Reload the page after 1.5 seconds to show updated status
                                                                setTimeout(function() {
                                                                    location.reload();
                                                                }, 1500);
                                                            }
                                                        }
                                                    })
                                                    .catch(error => {
                                                        console.error('Status check error:', error);
                                                    });
                                                }, 2000); // Check every 2 seconds
                                            }

                                            function viewInstanceStats(instanceId) {
                                                var modalEl = document.getElementById('instanceStatsModal');
                                                // Hide any open modals and remove modal-backdrop if present
                                                $(modalEl).modal('hide');
                                                $('.modal-backdrop').remove();
                                                document.body.classList.remove('modal-open');
                                                document.body.style.paddingRight = '';
                                                // Reset modal content
                                                document.getElementById('instanceStatsModalBody').innerHTML = '';
                                                fetch(`{{ url('/whatsapp/instances') }}/${instanceId}/stats`, {
                                                    method: 'GET',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        if (data.success && data.stats) {
                                                            let html = `<ul class='list-group'>`;
                                                            for (const [key, value] of Object.entries(data.stats)) {
                                                                html += `<li class='list-group-item d-flex justify-content-between align-items-center'><span>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span><span class='fw-bold'>${value}</span></li>`;
                                                            }
                                                            html += `</ul>`;
                                                            document.getElementById('instanceStatsModalBody').innerHTML = html;
                                                        } else {
                                                            document.getElementById('instanceStatsModalBody').innerHTML = `<div class='alert alert-danger'>Unable to load performance data.</div>`;
                                                        }
                                                        $(modalEl).modal('show');
                                                    })
                                                    .catch(error => {
                                                        document.getElementById('instanceStatsModalBody').innerHTML = `<div class='alert alert-danger'>Error loading performance data.</div>`;
                                                        $(modalEl).modal('show');
                                                    });
                                            }

                                            function editWhatsappInstance(instanceId) {
                                                window.location.href = `{{ url('/whatsapp/instances') }}/${instanceId}/edit`;
                                            }

                                            function deleteWhatsappInstance(instanceId) {
                                                fetch('{{ url('/api/whatsapp/instances/count') }}')
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        if (data.count <= 1) {
                                                            alert('You must have at least one WhatsApp instance. Deletion is not allowed.');
                                                            return;
                                                        }
                                                        if (!confirm('Are you sure you want to delete this WhatsApp instance? This action cannot be undone.')) {
                                                            return;
                                                        }
                                                        fetch(`{{ url('/api/whatsapp/instances') }}/${instanceId}`, {
                                                            method: 'DELETE',
                                                            headers: {
                                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                            }
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                location.reload();
                                                            } else {
                                                                alert('Error deleting WhatsApp instance: ' + (data.message || 'Unknown error'));
                                                            }
                                                        })
                                                        .catch(error => {
                                                            console.error('Error:', error);
                                                            alert('Error deleting WhatsApp instance');
                                                        });
                                                    });
                                            }
                                            
                                            function refreshStatus(instanceId) {
                                                // Show loading indicator
                                                const refreshIcon = event.target.closest('button').querySelector('i');
                                                if (refreshIcon) {
                                                    refreshIcon.classList.add('fa-spin');
                                                }
                                                
                                                // Fetch real-time status from WaSender API
                                                fetch(`{{ url('/api/whatsapp/instances') }}/${instanceId}/status`, {
                                                    method: 'GET',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (refreshIcon) {
                                                        refreshIcon.classList.remove('fa-spin');
                                                    }
                                                    
                                                    if (data.success) {
                                                        // Reload the page to show updated status
                                                        location.reload();
                                                    } else {
                                                        alert('Failed to refresh status: ' + (data.message || 'Unknown error'));
                                                    }
                                                })
                                                .catch(error => {
                                                    if (refreshIcon) {
                                                        refreshIcon.classList.remove('fa-spin');
                                                    }
                                                    console.error('Error refreshing status:', error);
                                                    alert('Error refreshing status. Please try again.');
                                                });
                                            }
                                            </script>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- Statistics Cards -->
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ $agents->count() }}</h3>
                            <p>Total Agents</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ $agents->where('status', 'active')->count() }}</h3>
                            <p>Active Agents</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ $agents->where('status', 'inactive')->count() }}</h3>
                            <p>Inactive Agents</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ $agents->where('allow_negotiation', true)->count() }}</h3>
                            <p>Negotiation Enabled</p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h2>No AI Sales Agents Yet</h2>
                    <p>Create your first intelligent sales assistant to start automating customer conversations on WhatsApp.</p>
                    <div class="empty-actions">
                        <button id="createAgentBtn" class="btn-primary" onclick="handleCreateAgent()">
                            <i class="fas fa-plus-circle me-2"></i>
                            Create Your First Agent
                        </button>
                    </div>
                    <div class="empty-features">
                        <div class="feature-item">
                            <i class="fas fa-comments text-primary"></i>
                            <span>Automated Conversations</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-brain text-success"></i>
                            <span>AI-Powered Responses</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line text-warning"></i>
                            <span>Sales Automation</span>
                        </div>
                    </div>
                </div>
            @endif
    </div>
</div>

    <div class="modal fade" id="reconnectModal" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-qrcode"></i> Reconnect WhatsApp Instance</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body" id="reconnectModalBody">
                                                <!-- QR code will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
<!-- Agent Details Modal (single instance, outside loop) -->
<div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel">
                    <i class="fas fa-robot me-2"></i>Sales Agent Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="agentModalBody">
                <!-- Agent details will be loaded here -->
            </div>
        </div>
    </div>
</div>
  <!-- Instance Performance Modal -->
                                <div class="modal" id="instanceStatsModal" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-chart-bar"></i> Instance Performance Summary</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body" id="instanceStatsModalBody">
                                                <!-- Stats will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
<style>
/* Modern AI Agents Management Styles */
.ai-agents-management {
    background: var(--gray-50);
    min-height: 100vh;
    padding: 2rem 0;
}

.page-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    align-items: center;
}

.header-icon {
    width: 80px;
    height: 80px;
    background: var(--primary-brand);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
}

.header-icon i {
    font-size: 2rem;
    color: white;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--primary-brand);
}

.page-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0.5rem 0 0 0;
}

.content-wrapper {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Agents Grid */
.agents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.agent-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.agent-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.agent-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.agent-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.agent-avatar i {
    font-size: 1.5rem;
    color: white;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #fff3cd;
    color: #856404;
}

.agent-name {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.agent-company {
    color: #7f8c8d;
    font-weight: 500;
    margin: 0 0 1rem 0;
}

.agent-description {
    color: #6c757d;
    line-height: 1.5;
    margin-bottom: 1.5rem;
}

.agent-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.detail-item .label {
    font-weight: 500;
    color: #6c757d;
}

.detail-item .value {
    color: #2c3e50;
}

.agent-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
}

.tag.more {
    background: #f5f5f5;
    color: #6c757d;
}

.agent-card-footer {
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-action.primary {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-action.primary:hover {
    background: #1976d2;
    color: white;
}

.btn-action.warning {
    background: #fff8e1;
    color: #f57c00;
}

.btn-action.warning:hover {
    background: #f57c00;
    color: white;
}

.btn-action.success {
    background: #e8f5e8;
    color: #2e7d32;
}

.btn-action.success:hover {
    background: #2e7d32;
    color: white;
}

.btn-action.danger {
    background: #ffebee;
    color: #c62828;
}

.btn-action.danger:hover {
    background: #c62828;
    color: white;
}

/* Statistics Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card {
    padding: 2rem 1.75rem;
    border-radius: 15px;
    color: white;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #5a67d8 100%);
    color: #ffffff;
}

.stat-card.success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: #ffffff;
}

.stat-card.warning {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
    color: #ffffff;
}

.stat-card.info {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: #ffffff;
}

.stat-icon {
    background: rgba(255, 255, 255, 0.15);
    padding: 1rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 70px;
    min-height: 70px;
}

.stat-icon i {
    font-size: 2.5rem;
    opacity: 1;
    color: #ffffff;
}

.stat-info {
    flex: 1;
}

.stat-info h3 {
    font-size: 2.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #ffffff;
    line-height: 1.2;
}

.stat-info p {
    margin: 0;
    opacity: 0.95;
    font-size: 1rem;
    font-weight: 500;
    color: #ffffff;
    letter-spacing: 0.3px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    width: 120px;
    height: 120px;
    background: var(--primary-brand);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem auto;
}

.empty-icon i {
    font-size: 3rem;
    color: white;
}

.empty-state h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.empty-state p {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.empty-features {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 3rem;
}

.feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.feature-item i {
    font-size: 2rem;
}

.feature-item span {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Breadcrumb */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .agents-grid {
        grid-template-columns: 1fr;
    }
    
    .empty-features {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .stat-card {
        padding: 1.5rem 1.25rem;
    }

    .stat-icon {
        min-width: 60px;
        min-height: 60px;
        padding: 0.875rem;
    }

    .stat-icon i {
        font-size: 2rem;
    }

    .stat-info h3 {
        font-size: 1.875rem;
    }

    .stat-info p {
        font-size: 0.875rem;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .header-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}
/* Compact Billing Information Styles */
.plan-badge-compact {
    margin-right: 0.5rem;
}

.plan-badge {
    padding: 0.25rem 0.75rem !important;
    border-radius: 15px !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
}

.plan-badge[data-plan="premium"] {
    background: var(--primary-brand) !important;
    color: white !important;
}

.plan-badge[data-plan="pro"] {
    background: var(--success-bg) !important;
    color: var(--success-text) !important;
}

.plan-badge[data-plan="starter"] {
    background: var(--warning-bg) !important;
    color: var(--warning-text) !important;
}

.plan-badge[data-plan="trial"] {
    background: var(--gray-500) !important;
    color: white !important;
}

.credits-section-compact {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e3e6f0;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.credits-display-compact {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.credits-display-compact i {
    color: #ffc107;
    font-size: 1rem;
}

.credits-info {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.credits-number {
    font-size: 0.85rem;
    font-weight: 700;
    color: #333;
}

.credits-label {
    font-size: 0.65rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.credits-actions {
    display: flex;
    gap: 0.3rem;
    margin-left: 0.5rem;
    padding-left: 0.5rem;
    border-left: 1px solid #dee2e6;
}

.action-buttons-group {
    display: flex;
    gap: 0.4rem;
    margin-left: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .credits-section-compact {
        flex-direction: column;
        gap: 0.4rem;
        padding: 0.5rem;
    }
    
    .credits-actions {
        margin-left: 0;
        padding-left: 0;
        border-left: none;
        border-top: 1px solid #dee2e6;
        padding-top: 0.4rem;
    }
}

@media (max-width: 768px) {
    .header-actions {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem !important;
    }
    
    .credits-section-compact {
        order: 1;
        width: 100%;
        justify-content: space-between;
    }
    
    .action-buttons-group {
        order: 2;
        margin-left: 0;
    }
    
    .plan-badge-compact {
        order: 0;
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
}

/* Remove old billing styles that are no longer needed */
.billing-info-section,
.plan-badge .badge,
.credits-display,
.credit-package,
.current-balance {
    display: none;
}

/* Dark Mode Styles */
.dark-mode .ai-agents-management {
    background: var(--gray-800);
}

.dark-mode .page-header {
    background: #2d3748;
    border: 1px solid #4a5568;
    color: #f7fafc;
}

.dark-mode .page-title {
    color: var(--primary-brand);
}

.dark-mode .page-subtitle {
    color: #a0aec0;
}

.dark-mode .content-wrapper {
    background: #2d3748;
    border: 1px solid #4a5568;
    color: #f7fafc;
}

.dark-mode .content-wrapper h2 {
    color: #f7fafc !important;
}

.dark-mode .content-wrapper p {
    color: #a0aec0 !important;
}

.dark-mode .agent-card {
    background: #4a5568;
    border: 1px solid #718096;
    color: #f7fafc;
}

.dark-mode .agent-card:hover {
    background: #718096;
}

.dark-mode .agent-name {
    color: #f7fafc;
}

.dark-mode .agent-company {
    color: #a0aec0;
}

.dark-mode .agent-description {
    color: #e2e8f0;
}

.dark-mode .detail-item .label {
    color: #a0aec0;
}

.dark-mode .detail-item .value {
    color: #f7fafc;
}

.dark-mode .agent-card-footer {
    border-top: 1px solid #718096;
}

.dark-mode .stat-card {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

.dark-mode .stat-card.primary {
    background: linear-gradient(135deg, #5a67d8 0%, #4c51bf 100%);
    color: #ffffff;
}

.dark-mode .stat-card.success {
    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
    color: #ffffff;
}

.dark-mode .stat-card.warning {
    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
    color: #ffffff;
}

.dark-mode .stat-card.info {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
    color: #ffffff;
}

.dark-mode .stat-icon {
    background: rgba(255, 255, 255, 0.2);
}

.dark-mode .stat-icon i {
    color: #ffffff;
    opacity: 1;
}

.dark-mode .stat-info h3 {
    color: #ffffff;
    font-weight: 700;
}

.dark-mode .stat-info p {
    color: #ffffff;
    opacity: 0.95;
    font-weight: 500;
}

.dark-mode .empty-state h2 {
    color: #f7fafc;
}

.dark-mode .empty-state p {
    color: #a0aec0;
}

.dark-mode .feature-item span {
    color: #a0aec0;
}

.dark-mode .breadcrumb-item a {
    color: #a0aec0;
}

.dark-mode .breadcrumb-item a:hover {
    color: #f7fafc;
}

/* Dark mode for modals */
.dark-mode .modal-content {
    background: #2d3748;
    border: 1px solid #4a5568;
    color: #f7fafc;
}

.dark-mode .modal-header {
    border-bottom: 1px solid #4a5568;
    color: #f7fafc;
}

.dark-mode .modal-title {
    color: #f7fafc;
}

.dark-mode .modal-body {
    color: #f7fafc;
}

.dark-mode .modal-footer {
    border-top: 1px solid #4a5568;
}

.dark-mode .btn-close {
    filter: invert(1);
}

/* Dark mode for forms and inputs */
.dark-mode .form-control {
    background: #4a5568;
    border: 1px solid #718096;
    color: #f7fafc;
}

.dark-mode .form-control:focus {
    background: #4a5568;
    border-color: #90cdf4;
    color: #f7fafc;
    box-shadow: 0 0 0 0.2rem rgba(144, 205, 244, 0.25);
}

.dark-mode .form-control::placeholder {
    color: #a0aec0;
}

.dark-mode .form-select {
    background: #4a5568;
    border: 1px solid #718096;
    color: #f7fafc;
}

.dark-mode .form-select:focus {
    background: #4a5568;
    border-color: #90cdf4;
    color: #f7fafc;
    box-shadow: 0 0 0 0.2rem rgba(144, 205, 244, 0.25);
}

/* Dark mode for badges */
.dark-mode .badge {
    background: #4a5568 !important;
    color: #f7fafc !important;
    border: 1px solid #718096;
}

.dark-mode .badge.bg-success {
    background: #38a169 !important;
    color: #f7fafc !important;
}

.dark-mode .badge.bg-danger {
    background: #e53e3e !important;
    color: #f7fafc !important;
}

.dark-mode .badge.bg-warning {
    background: #d69e2e !important;
    color: #2d3748 !important;
}

.dark-mode .badge.bg-info {
    background: #3182ce !important;
    color: #f7fafc !important;
}

/* Dark mode for buttons */
.dark-mode .btn-secondary {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .btn-secondary:hover {
    background: #718096;
    border-color: #a0aec0;
    color: #f7fafc;
}

.dark-mode .btn-outline-secondary {
    color: #a0aec0;
    border-color: #718096;
}

.dark-mode .btn-outline-secondary:hover {
    background: #718096;
    border-color: #a0aec0;
    color: #f7fafc;
}

/* Dark mode for alerts */
.dark-mode .alert {
    background: #4a5568;
    border: 1px solid #718096;
    color: #f7fafc;
}

.dark-mode .alert-danger {
    background: #742a2a;
    border-color: #e53e3e;
    color: #fed7d7;
}

.dark-mode .alert-success {
    background: #276749;
    border-color: #38a169;
    color: #c6f6d5;
}

.dark-mode .alert-warning {
    background: #975a16;
    border-color: #d69e2e;
    color: #faf089;
}

.dark-mode .alert-info {
    background: #2c5282;
    border-color: #3182ce;
    color: #bee3f8;
}

/* Dark mode for list groups */
.dark-mode .list-group-item {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .list-group-item:hover {
    background: #718096;
}

/* Dark mode for tables */
.dark-mode .table {
    color: #f7fafc;
}

.dark-mode .table th {
    border-color: #4a5568;
    color: #f7fafc;
}

.dark-mode .table td {
    border-color: #4a5568;
    color: #e2e8f0;
}

.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > td,
.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > th {
    background: rgba(74, 85, 104, 0.5);
}

/* Dark mode for billing components */
.dark-mode .credits-section-compact {
    background: rgba(74, 85, 104, 0.95);
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .credits-number {
    color: #f7fafc;
}

.dark-mode .credits-label {
    color: #a0aec0;
}

.dark-mode .credits-actions {
    border-left-color: #718096;
}

/* Dark mode for status badges */
.dark-mode .status-badge.active {
    background: #276749;
    color: #c6f6d5;
}

.dark-mode .status-badge.inactive {
    background: #975a16;
    color: #faf089;
}

/* Dark mode for tag elements */
.dark-mode .tag {
    background: #4a5568;
    color: #90cdf4;
    border: 1px solid #718096;
}

.dark-mode .tag.more {
    background: #718096;
    color: #a0aec0;
}

/* Dark mode for text muted elements */
.dark-mode .text-muted {
    color: #a0aec0 !important;
}

/* Dark mode for small text */
.dark-mode small {
    color: #a0aec0;
}

/* Dark mode for credit package cards (in modals) */
.dark-mode .credit-package {
    background: #4a5568 !important;
    border-color: #718096 !important;
    color: #f7fafc;
}

.dark-mode .credit-package:hover {
    background: #718096 !important;
}

.dark-mode .current-balance {
    background: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .current-balance strong {
    color: #f7fafc !important;
}

.dark-mode .current-balance .text-muted {
    color: #a0aec0 !important;
}

/* Dark mode for definition lists */
.dark-mode dl dt {
    color: #a0aec0;
}

.dark-mode dl dd {
    color: #f7fafc;
}

/* Dark mode for pricing/upgrade modals */
.dark-mode .pricing-card,
.dark-mode .plan-card {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode .pricing-card.recommended,
.dark-mode .plan-card.recommended {
    background: #553c9a !important;
    border: 2px solid #805ad5 !important;
}

.dark-mode .pricing-card h3,
.dark-mode .plan-card h3,
.dark-mode .pricing-card h4,
.dark-mode .plan-card h4 {
    color: #f7fafc !important;
}

.dark-mode .pricing-card .price,
.dark-mode .plan-card .price {
    color: #90cdf4 !important;
    font-weight: 700;
}

.dark-mode .pricing-card .price-period,
.dark-mode .plan-card .price-period {
    color: #a0aec0 !important;
}

.dark-mode .pricing-card ul li,
.dark-mode .plan-card ul li {
    color: #e2e8f0 !important;
}

.dark-mode .pricing-card .feature-list li,
.dark-mode .plan-card .feature-list li {
    color: #e2e8f0 !important;
}

.dark-mode .pricing-card .feature-list li::before,
.dark-mode .plan-card .feature-list li::before {
    color: #68d391 !important;
}

/* Dark mode for current plan section */
.dark-mode .current-plan-section,
.dark-mode .current-plan-card {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .current-plan-badge {
    background: #4a5568 !important;
    color: #a0aec0 !important;
    border: 1px solid #718096;
}

.dark-mode .current-plan-features {
    color: #a0aec0 !important;
}

.dark-mode .plan-expiry,
.dark-mode .plan-remaining {
    color: #fbb6ce !important;
}

/* Dark mode for recommended badge */
.dark-mode .recommended-badge {
    background: #805ad5 !important;
    color: #f7fafc !important;
}

/* Dark mode for purchase credits section */
.dark-mode .credits-purchase-section {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .credits-input-group {
    background: #4a5568 !important;
}

.dark-mode .credits-input-group input {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode .credits-input-group input:focus {
    background: #4a5568 !important;
    border-color: #90cdf4 !important;
    color: #f7fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(144, 205, 244, 0.25) !important;
}

.dark-mode .credits-input-group input::placeholder {
    color: #a0aec0 !important;
}

.dark-mode .credits-currency {
    background: #718096 !important;
    color: #f7fafc !important;
    border: 1px solid #718096 !important;
}

.dark-mode .credits-minimum {
    color: #a0aec0 !important;
}

/* Dark mode for upgrade buttons */
.dark-mode .btn-upgrade {
    background: var(--primary-brand) !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode .btn-upgrade:hover {
    background: var(--primary-hover) !important;
    color: #ffffff !important;
}

.dark-mode .btn-upgrade-outline {
    background: transparent !important;
    border: 2px solid #667eea !important;
    color: #90cdf4 !important;
}

.dark-mode .btn-upgrade-outline:hover {
    background: #667eea !important;
    color: #ffffff !important;
}

.dark-mode .btn-buy-credits {
    background: #38a169 !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode .btn-buy-credits:hover {
    background: #2f855a !important;
    color: #ffffff !important;
}

/* Dark mode for modal backdrop overlay */
.dark-mode .modal-backdrop {
    background-color: rgba(45, 55, 72, 0.8) !important;
}

/* Dark mode for pricing modal specific elements */
.dark-mode .pricing-modal .modal-content {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
}

.dark-mode .pricing-modal .modal-header {
    border-bottom: 1px solid #4a5568 !important;
    background: #2d3748 !important;
}

.dark-mode .pricing-modal .modal-body {
    background: #2d3748 !important;
}

.dark-mode .pricing-modal .modal-footer {
    border-top: 1px solid #4a5568 !important;
    background: #2d3748 !important;
}

/* Dark mode for feature icons */
.dark-mode .feature-icon {
    color: #68d391 !important;
}

.dark-mode .feature-check {
    color: #68d391 !important;
}

/* Dark mode for price display */
.dark-mode .price-display {
    color: #90cdf4 !important;
}

.dark-mode .price-currency {
    color: #a0aec0 !important;
}

/* Dark mode for plan titles */
.dark-mode .plan-title {
    color: #f7fafc !important;
    font-weight: 700;
}

.dark-mode .plan-subtitle {
    color: #a0aec0 !important;
}

/* Dark mode for upgrade modal lock icon */
.dark-mode .upgrade-lock-icon {
    color: #fbb6ce !important;
}

/* Dark mode for feature restrictions text */
.dark-mode .feature-restriction-text {
    color: #a0aec0 !important;
}

/* Dark mode for billing period selectors */
.dark-mode .billing-period-selector {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode .billing-period-selector.active {
    background: #667eea !important;
    color: #ffffff !important;
}

/* Dark mode for specific pricing modal IDs */
.dark-mode #pricingControlsModal .modal-content {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .modal-header {
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode #pricingControlsModal .modal-body {
    background: #2d3748 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .card {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .card-title {
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .card-body {
    background: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .text-muted {
    color: #a0aec0 !important;
}

.dark-mode #pricingControlsModal .badge {
    background: #718096 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .form-control {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .form-control:focus {
    background: #4a5568 !important;
    border-color: #90cdf4 !important;
    color: #f7fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(144, 205, 244, 0.25) !important;
}

.dark-mode #pricingControlsModal .form-control::placeholder {
    color: #a0aec0 !important;
}

.dark-mode #pricingControlsModal .input-group-text {
    background: #718096 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .btn-outline-secondary {
    background: transparent !important;
    border: 1px solid #718096 !important;
    color: #a0aec0 !important;
}

.dark-mode #pricingControlsModal .btn-outline-secondary:hover {
    background: #718096 !important;
    border-color: #a0aec0 !important;
    color: #f7fafc !important;
}

.dark-mode #pricingControlsModal .btn-success {
    background: #38a169 !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode #pricingControlsModal .btn-success:hover {
    background: #2f855a !important;
    color: #ffffff !important;
}

.dark-mode #pricingControlsModal .btn-primary {
    background: #667eea !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode #pricingControlsModal .btn-primary:hover {
    background: #5a6fd8 !important;
    color: #ffffff !important;
}

.dark-mode #pricingControlsModal .spinner-border {
    color: #90cdf4 !important;
}

.dark-mode #pricingControlsModal small {
    color: #a0aec0 !important;
}

.dark-mode #pricingControlsModal .fw-bold {
    color: #f7fafc !important;
}

/* Dark mode for plan cards when they load */
.dark-mode #availablePlans .card {
    background: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #f7fafc !important;
}

.dark-mode #availablePlans .card.border-primary {
    border-color: #667eea !important;
    background: #553c9a !important;
}

.dark-mode #availablePlans .card-header {
    background: #718096 !important;
    border-bottom: 1px solid #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode #availablePlans .list-group-item {
    background: #4a5568 !important;
    border-color: #718096 !important;
    color: #e2e8f0 !important;
}

.dark-mode #availablePlans .text-success {
    color: #68d391 !important;
}

.dark-mode #availablePlans .text-primary {
    color: #90cdf4 !important;
}

.dark-mode #availablePlans h5 {
    color: #f7fafc !important;
}

.dark-mode #availablePlans h6 {
    color: #f7fafc !important;
}

/* Dark mode for feature restriction elements */
.dark-mode .fas.fa-lock {
    color: #fbb6ce !important;
}

.dark-mode h5 {
    color: #f7fafc !important;
}

.dark-mode p {
    color: #e2e8f0 !important;
}
</style>

<script>
function viewAgent(uuid) {
    fetch(`{{ url('/ai-agents') }}/${uuid}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('agentModalBody').innerHTML = generateAgentDetails(data.agent);
                new bootstrap.Modal(document.getElementById('agentModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}

function editAgent(uuid) {
    window.location.href = "{{ url('/ai-agents') }}/" + uuid + "/edit";
}

function toggleStatus(uuid) {
    if (confirm('Are you sure you want to change the agent status?')) {
        fetch(`{{ url('/ai-agents') }}/${uuid}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update status');
            }
        });
    }
}

function deleteAgent(uuid) {
    if (confirm('Are you sure you want to delete this agent? This action cannot be undone.')) {
        fetch(`{{ url('/ai-agents') }}/${uuid}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete agent');
            }
        });
    }
}

function generateAgentDetails(agent) {
    return `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-robot me-2"></i>Assistant Information</h6>
                <dl class="row">
                    <dt class="col-sm-4">Name:</dt>
                    <dd class="col-sm-8">${agent.assistant_name}</dd>
                    <dt class="col-sm-4">Company:</dt>
                    <dd class="col-sm-8">${agent.company_name || 'Not specified'}</dd>
                    <dt class="col-sm-4">Industry:</dt>
                    <dd class="col-sm-8">${agent.company_industry || 'Not specified'}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-cogs me-2"></i>Configuration</h6>
                <dl class="row">
                    <dt class="col-sm-4">Language:</dt>
                    <dd class="col-sm-8">${agent.primary_language || 'English'}</dd>
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-${agent.status === 'active' ? 'success' : 'warning'}">
                            ${agent.status}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Negotiation:</dt>
                    <dd class="col-sm-8">${agent.allow_negotiation ? 'Enabled' : 'Disabled'}</dd>
                </dl>
            </div>
        </div>
        ${agent.products_services ? `
        <div class="mt-3">
            <h6><i class="fas fa-box me-2"></i>Products/Services</h6>
            <p class="text-muted">${agent.products_services}</p>
        </div>
        ` : ''}
    `;
}

// Check user package/plan and agent creation limits
function userCanCreateAgent() {
    // Get current user's billing status
    const subscriptionPlan = '{{ $subscriptionPlan ?? "trial" }}';
    const currentAgentCount = {{ $agents->count() }};
    
    // Define WhatsApp channels limits per plan (since agents are tied to WhatsApp instances)
    const planLimits = {
        'trial': 1,
        'starter': 1,
        'pro': 3,
        'premium': 7
    };
    
    const maxAllowed = planLimits[subscriptionPlan] || 1;
    return currentAgentCount < maxAllowed;
}

function handleCreateAgent() {
    if (userCanCreateAgent()) {
        window.location.href = "{{ route('ai-agents.create') }}";
    } else {
        // Show the upgrade modal instead of a simple alert
        if (typeof showUpgradeModal === 'function') {
            showUpgradeModal('whatsapp_channels');
        } else {
            // Fallback if upgrade modal is not available
            const subscriptionPlan = '{{ $subscriptionPlan ?? "trial" }}';
            const requiredPlan = subscriptionPlan === 'trial' || subscriptionPlan === 'starter' ? 'pro' : 'premium';
            
            alert(`Your ${subscriptionPlan.toUpperCase()} plan does not allow creating more WhatsApp Sales Agents. Please upgrade to ${requiredPlan.toUpperCase()} to add more agents.`);
        }
    }
}

// Function to show purchase credits modal
function showPurchaseCreditsModal() {
    // Check if the global pricing controls modal exists
    if (typeof showUpgradeModal === 'function') {
        // Use the existing upgrade modal with credits parameter
        showUpgradeModal('credits');
    } else {
        // Fallback to a simple modal or redirect
        const currentCredits = parseInt('{{ $aiCredits ?? 0 }}');
        const creditPackages = [
            { credits: 1000, price: 'TZS 2,600', value: 1000 },
            { credits: 5000, price: 'TZS 13,000', value: 5000 },
            { credits: 10000, price: 'TZS 25,000', value: 10000 },
            { credits: 50000, price: 'TZS 120,000', value: 50000 }
        ];
        
        let modalHTML = `
            <div class="modal fade" id="purchaseCreditsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-coins text-warning me-2"></i>Purchase AI Credits
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="current-balance mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-wallet text-primary me-2"></i>
                                    <div>
                                        <strong>Current Balance: ${currentCredits.toLocaleString()} AI Credits</strong>
                                        <small class="d-block text-muted">Each credit allows ~3.8 AI tokens</small>
                                    </div>
                                </div>
                            </div>
                            <h6 class="mb-3">Select a Credit Package:</h6>
                            <div class="row">`;
        
        creditPackages.forEach(pkg => {
            modalHTML += `
                <div class="col-md-6 mb-3">
                    <div class="credit-package p-3 border rounded" style="cursor: pointer;" onclick="purchaseCredits(${pkg.value})">
                        <div class="text-center">
                            <h6 class="text-primary">${pkg.credits.toLocaleString()} Credits</h6>
                            <p class="mb-0 fw-bold">${pkg.price}</p>
                            <small class="text-muted">~${Math.round(pkg.credits * 3.8).toLocaleString()} AI tokens</small>
                        </div>
                    </div>
                </div>`;
        });
        
        modalHTML += `
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>`;
        
        // Remove existing modal if present
        const existingModal = document.getElementById('purchaseCreditsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to DOM and show
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('purchaseCreditsModal'));
        modal.show();
    }
}

// Function to handle credit purchase
function purchaseCredits(amount) {
    if (confirm(`Purchase ${amount.toLocaleString()} AI credits?`)) {
        fetch('{{ url('/api/billing/credits') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ amount: amount })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully purchased ${amount.toLocaleString()} AI credits!`);
                location.reload(); // Refresh to show updated credits
            } else {
                alert('Credit purchase failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to purchase credits. Please try again.');
        });
        
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('purchaseCreditsModal'));
        if (modal) {
            modal.hide();
        }
    }
}

// Auto-hide warning banner when connection is successful
document.addEventListener('DOMContentLoaded', function() {
    const warningBanner = document.querySelector('.whatsapp-warning-banner');
    if (warningBanner) {
        // When status polling detects a connection, hide the banner
        const observer = new MutationObserver(function(mutations) {
            // Check if any instance shows as connected
            const connectedBadges = document.querySelectorAll('.badge.bg-success');
            if (connectedBadges.length > 0) {
                // Fade out the warning banner
                warningBanner.style.transition = 'opacity 0.5s ease-out';
                warningBanner.style.opacity = '0';
                setTimeout(function() {
                    warningBanner.style.display = 'none';
                }, 500);
            }
        });
        
        // Observe changes to the page (for when status updates)
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});
</script>

@endsection
