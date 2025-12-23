@extends('layouts.app')
@section('content')

<style>
    .instance-management-container {
        background: #f8fafc;
        min-height: 100vh;
        padding: 20px;
    }
    
    .instance-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }
    
    .instance-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .instance-card.primary {
        border-left: 4px solid #25d366;
    }
    
    .instance-card.active {
        border: 2px solid #25d366;
        background: linear-gradient(135deg, rgba(37, 211, 102, 0.05) 0%, rgba(32, 199, 89, 0.05) 100%);
    }
    
    .instance-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .instance-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .instance-schema {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .instance-badges {
        display: flex;
        gap: 8px;
        margin: 10px 0;
    }
    
    .instance-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .badge-primary {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .badge-active {
        background: #dbeafe;
        color: #1d4ed8;
    }
    
    .badge-purpose {
        background: #fef3c7;
        color: #d97706;
    }
    
    .instance-stats {
        display: flex;
        gap: 20px;
        margin: 15px 0;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 2px;
    }
    
    .stat-label {
        font-size: 0.8rem;
        color: #64748b;
    }
    
    .instance-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .action-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-select {
        background: #25d366;
        color: white;
    }
    
    .btn-select:hover {
        background: #20c759;
    }
    
    .btn-configure {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .btn-configure:hover {
        background: #e2e8f0;
        color: #475569;
    }
    
    .header-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .page-description {
        color: #64748b;
        margin-bottom: 20px;
    }
    
    .create-instance-btn {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .create-instance-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
    }
</style>

<div class="instance-management-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="page-title">
                    <i class="fab fa-whatsapp"></i> WhatsApp Instance Management
                </h1>
                <p class="page-description">
                    Manage your WhatsApp business lines. Each instance can have its own purpose, AI behavior, and configuration.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button class="create-instance-btn" onclick="showCreateInstanceModal()">
                    <i class="fas fa-plus"></i> Add New Instance
                </button>
            </div>
        </div>
    </div>
    
    <!-- Current Active Instance -->
    @if($activeInstance)
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Currently Active:</strong> 
        <span class="ms-2">{{ $activeInstance->display_name ?: $activeInstance->schema_name }}</span>
        @if($activeInstance->purpose)
            <span class="badge bg-light text-dark ms-2">{{ $activeInstance->purpose }}</span>
        @endif
    </div>
    @endif
    
    <!-- Instances List -->
    <div class="row">
        @forelse($instances as $instance)
            <div class="col-lg-6 col-xl-4">
                <div class="instance-card {{ $instance->is_primary ? 'primary' : '' }} {{ $activeInstance && $activeInstance->id == $instance->id ? 'active' : '' }}">
                    <div class="instance-header">
                        <div class="flex-grow-1">
                            <h3 class="instance-title">{{ $instance->display_name ?: 'Unnamed Instance' }}</h3>
                            <p class="instance-schema">{{ $instance->schema_name }}</p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link p-0" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="selectInstance('{{ $instance->id }}')">
                                    <i class="fas fa-check-circle"></i> Select as Active
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="configureInstance('{{ $instance->id }}')">
                                    <i class="fas fa-cog"></i> Configure
                                </a></li>
                                @if(!$instance->is_primary)
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteInstance('{{ $instance->id }}')">
                                    <i class="fas fa-trash"></i> Delete
                                </a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    
                    <div class="instance-badges">
                        @if($instance->is_primary)
                            <span class="instance-badge badge-primary">Primary</span>
                        @endif
                        @if($activeInstance && $activeInstance->id == $instance->id)
                            <span class="instance-badge badge-active">Active</span>
                        @endif
                        @if($instance->purpose)
                            <span class="instance-badge badge-purpose">{{ ucfirst($instance->purpose) }}</span>
                        @endif
                    </div>
                    
                    @if($instance->description)
                        <p class="text-muted small mb-3">{{ $instance->description }}</p>
                    @endif
                    
                    <!-- Instance Statistics -->
                    <div class="instance-stats">
                        <div class="stat-item">
                            <div class="stat-value" data-instance-id="{{ $instance->id }}" data-stat="conversations">-</div>
                            <div class="stat-label">Conversations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" data-instance-id="{{ $instance->id }}" data-stat="messages">-</div>
                            <div class="stat-label">Messages</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" data-instance-id="{{ $instance->id }}" data-stat="contacts">-</div>
                            <div class="stat-label">Contacts</div>
                        </div>
                    </div>
                    
                    <div class="instance-actions">
                        @if(!$activeInstance || $activeInstance->id != $instance->id)
                            <button class="action-btn btn-select" onclick="selectInstance('{{ $instance->id }}')">
                                <i class="fas fa-check"></i> Select
                            </button>
                        @else
                            <button class="action-btn btn-select" disabled>
                                <i class="fas fa-check-circle"></i> Active
                            </button>
                        @endif
                        <button class="action-btn btn-configure" onclick="configureInstance('{{ $instance->id }}')">
                            <i class="fas fa-cog"></i> Configure
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fab fa-whatsapp fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No WhatsApp Instances</h4>
                    <p class="text-muted">Create your first WhatsApp instance to get started.</p>
                    <button class="create-instance-btn" onclick="showCreateInstanceModal()">
                        <i class="fas fa-plus"></i> Create First Instance
                    </button>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Create Instance Modal -->
<div class="modal fade" id="createInstanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fab fa-whatsapp"></i> Create New WhatsApp Instance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createInstanceForm">
                    <div class="mb-3">
                        <label class="form-label">Schema Name *</label>
                        <input type="text" class="form-control" id="schemaName" placeholder="e.g., business_main" required>
                        <small class="form-text text-muted">Unique identifier for database schema (lowercase, underscores only)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="displayName" placeholder="e.g., Main Business Line">
                        <small class="form-text text-muted">Friendly name for this instance</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <select class="form-select" id="purpose">
                            <option value="">Select purpose...</option>
                            <option value="sales">Sales & Lead Generation</option>
                            <option value="support">Customer Support</option>
                            <option value="marketing">Marketing & Promotions</option>
                            <option value="personal">Personal Use</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="3" placeholder="Describe how this instance will be used..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="createInstance()">
                    <i class="fas fa-plus"></i> Create Instance
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Configure Instance Modal -->
<div class="modal fade" id="configureInstanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> Configure WhatsApp Instance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="configureInstanceForm">
                    <input type="hidden" id="configureInstanceId">
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="configureDisplayName" placeholder="e.g., Main Business Line">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <select class="form-select" id="configurePurpose">
                            <option value="">Select purpose...</option>
                            <option value="sales">Sales & Lead Generation</option>
                            <option value="support">Customer Support</option>
                            <option value="marketing">Marketing & Promotions</option>
                            <option value="personal">Personal Use</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="configureDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateInstance()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load instance statistics on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInstanceStats();
});

function loadInstanceStats() {
    const statElements = document.querySelectorAll('[data-stat]');
    const instanceIds = [...new Set([...statElements].map(el => el.dataset.instanceId))];
    
    instanceIds.forEach(instanceId => {
        fetch(`/api/whatsapp/instances/${instanceId}/stats`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    document.querySelector(`[data-instance-id="${instanceId}"][data-stat="conversations"]`).textContent = stats.conversations || 0;
                    document.querySelector(`[data-instance-id="${instanceId}"][data-stat="messages"]`).textContent = stats.messages || 0;
                    document.querySelector(`[data-instance-id="${instanceId}"][data-stat="contacts"]`).textContent = stats.contacts || 0;
                }
            })
            .catch(error => console.error('Error loading stats for instance', instanceId, error));
    });
}

function showCreateInstanceModal() {
    const modal = new bootstrap.Modal(document.getElementById('createInstanceModal'));
    modal.show();
}

function createInstance() {
    const form = document.getElementById('createInstanceForm');
    const formData = {
        schema_name: document.getElementById('schemaName').value,
        display_name: document.getElementById('displayName').value,
        purpose: document.getElementById('purpose').value,
        description: document.getElementById('description').value
    };
    
    if (!formData.schema_name) {
        alert('Schema name is required');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    btn.disabled = true;
    
    fetch('/api/whatsapp/instances', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createInstanceModal')).hide();
            window.location.reload();
        } else {
            alert('Error creating instance: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating instance');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function selectInstance(instanceId) {
    fetch('/api/whatsapp/instances/select', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ instance_id: instanceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error selecting instance: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error selecting instance');
    });
}

function configureInstance(instanceId) {
    // Load current instance data
    fetch(`/api/whatsapp/instances/${instanceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const instance = data.instance;
                document.getElementById('configureInstanceId').value = instanceId;
                document.getElementById('configureDisplayName').value = instance.display_name || '';
                document.getElementById('configurePurpose').value = instance.purpose || '';
                document.getElementById('configureDescription').value = instance.description || '';
                
                const modal = new bootstrap.Modal(document.getElementById('configureInstanceModal'));
                modal.show();
            }
        })
        .catch(error => console.error('Error loading instance data:', error));
}

function updateInstance() {
    const instanceId = document.getElementById('configureInstanceId').value;
    const formData = {
        display_name: document.getElementById('configureDisplayName').value,
        purpose: document.getElementById('configurePurpose').value,
        description: document.getElementById('configureDescription').value
    };
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    fetch(`/api/whatsapp/instances/${instanceId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('configureInstanceModal')).hide();
            window.location.reload();
        } else {
            alert('Error updating instance: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating instance');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function deleteInstance(instanceId) {
    if (!confirm('Are you sure you want to delete this instance? This action cannot be undone.')) {
        return;
    }
    
    fetch(`/api/whatsapp/instances/${instanceId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error deleting instance: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting instance');
    });
}
</script>

@endsection