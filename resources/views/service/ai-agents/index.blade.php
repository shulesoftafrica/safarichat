@extends('layouts.app')
@section('content')
<div class="ai-sales-officer">
    <div class="container-fluid">
        <!-- Header -->
        <div class="reports-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="reports-title">
                        <i class="fas fa-robot"></i>
                        AI Sales Agents
                        <span class="ai-badge ms-3">
                            <i class="fas fa-brain me-1"></i>
                            AI Powered
                        </span>
                    </h1>
                    <p class="reports-subtitle mb-0">
                        Manage your intelligent WhatsApp sales assistants
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('ai-agents.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Create New Agent
                    </a>
                </div>
            </div>
        </div>

        <div class="main-layout d-flex">
            <!-- Sidebar Navigation (Compact) -->
            <nav class="sidebar shadow-sm">
                <ul class="sidebar-nav nav flex-column py-3">
                    <li>
                        <a href="{{ url('service/index') }}" class="nav-link">
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-agents.index') }}" class="nav-link active">
                            <span>AI Agents</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-agents.create') }}" class="nav-link">
                            <span>Create Agent</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main Content Area -->
            <div class="content-area flex-grow-1 p-3 ms-3">
                <div class="ai-agents-list">
                    @if($agents->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Your AI Sales Agents ({{ $agents->count() }})
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Assistant Name</th>
                                                <th>Company</th>
                                                <th>Target Category</th>
                                                <th>Language</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($agents as $agent)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                            <i class="fas fa-robot text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong>{{ $agent->assistant_name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ Str::limit($agent->personality_description, 50) }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong>{{ $agent->company_name ?? 'Not specified' }}</strong>
                                                    @if($agent->company_industry)
                                                        <br><small class="text-muted">{{ ucfirst($agent->company_industry) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ $agent->getUserTypeName() }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>{{ ucfirst($agent->primary_language ?? 'English') }}</strong>
                                                    @if($agent->additional_languages && count($agent->additional_languages) > 0)
                                                        <br>
                                                        <small class="text-muted">
                                                            +{{ count($agent->additional_languages) }} more
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($agent->status === 'active')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-pause-circle me-1"></i>
                                                            Inactive
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small>
                                                        {{ $agent->created_at->format('M d, Y') }}
                                                        <br>
                                                        <span class="text-muted">{{ $agent->created_at->format('H:i') }}</span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewAgent({{ $agent->id }})" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="editAgent({{ $agent->id }})" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-{{ $agent->status === 'active' ? 'warning' : 'success' }}" 
                                                                onclick="toggleStatus({{ $agent->id }})" title="{{ $agent->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                            <i class="fas fa-{{ $agent->status === 'active' ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteAgent({{ $agent->id }})" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Agent Statistics -->
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-0">{{ $agents->count() }}</h4>
                                                <small>Total Agents</small>
                                            </div>
                                            <i class="fas fa-robot fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-0">{{ $agents->where('status', 'active')->count() }}</h4>
                                                <small>Active Agents</small>
                                            </div>
                                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-0">{{ $agents->where('status', 'inactive')->count() }}</h4>
                                                <small>Inactive Agents</small>
                                            </div>
                                            <i class="fas fa-pause-circle fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-0">{{ $agents->where('allow_negotiation', true)->count() }}</h4>
                                                <small>With Negotiation</small>
                                            </div>
                                            <i class="fas fa-handshake fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-robot fa-4x text-muted mb-4"></i>
                                <h3 class="text-muted">No AI Sales Agents Yet</h3>
                                <p class="text-muted mb-4">
                                    Create your first AI sales assistant to start automating customer engagement.
                                </p>
                                <a href="{{ route('ai-agents.create') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>
                                    Create Your First Agent
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agent Details Modal -->
<div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel">Agent Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="agentModalBody">
                <!-- Agent details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
}

.empty-state {
    max-width: 500px;
    margin: 0 auto;
    padding: 2rem;
}

.sidebar {
    min-width: 200px;
    background: #f8f9fa;
    border-radius: 8px;
}

.sidebar .nav-link {
    color: #6c757d;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 0.25rem;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
}

.btn-group .btn {
    margin-right: 0.25rem;
}
</style>

<script>
function viewAgent(id) {
    fetch(`/ai-agents/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('agentModalBody').innerHTML = generateAgentDetails(data.agent);
                new bootstrap.Modal(document.getElementById('agentModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}

function editAgent(id) {
    window.location.href = `/ai-agents/${id}/edit`;
}

function toggleStatus(id) {
    if (confirm('Are you sure you want to change the agent status?')) {
        fetch(`/ai-agents/${id}/toggle-status`, {
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

function deleteAgent(id) {
    if (confirm('Are you sure you want to delete this agent? This action cannot be undone.')) {
        fetch(`/ai-agents/${id}`, {
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
</script>
@endsection
