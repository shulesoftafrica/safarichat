
<?php $__env->startSection('content'); ?>

<div class="ai-agents-management">
    <div class="container-fluid">
        <!-- Modern Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="header-text">
                            <h1 class="page-title">AI Sales Agents</h1>
                            <p class="page-subtitle">
                                Create and manage intelligent WhatsApp sales assistants to automate customer conversations
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <?php if($agents->count() === 0): ?>
                        <a href="<?php echo e(route('ai-agents.create')); ?>" class="btn btn-create">
                            <i class="fas fa-plus-circle me-2"></i>
                            Create AI Agent
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <?php if($agents->count() > 0): ?>
                <!-- Agents Grid View -->
                <div class="agents-grid">
                    <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="agent-card">
                            <div class="agent-card-header">
                                <div class="agent-avatar">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="agent-status">
                                    <span class="status-badge <?php echo e($agent->status === 'active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e(ucfirst($agent->status)); ?>

                                    </span>
                                </div>
                            </div>
                            
                            <div class="agent-card-body">
                                <h3 class="agent-name"><?php echo e($agent->assistant_name); ?></h3>
                                <p class="agent-company"><?php echo e($agent->company_name ?? 'No company specified'); ?></p>
                                <p class="agent-description">
                                    <?php echo e(Str::limit($agent->personality_description, 100)); ?>

                                </p>
                                
                                <div class="agent-details">
                                    <div class="detail-item">
                                        <span class="label">Language:</span>
                                        <span class="value"><?php echo e(ucfirst($agent->primary_language ?? 'English')); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Industry:</span>
                                        <span class="value"><?php echo e(ucfirst($agent->company_industry ?? 'General')); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Created:</span>
                                        <span class="value"><?php echo e($agent->created_at->format('M d, Y')); ?></span>
                                    </div>
                                </div>
                                
                                <?php
                                    $userTypes = $agent->getTargetUserTypeNames();
                                ?>
                                <?php if(!empty($userTypes)): ?>
                                    <div class="agent-tags">
                                        <?php $__currentLoopData = array_slice($userTypes, 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $userType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="tag"><?php echo e($userType); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php if(count($userTypes) > 3): ?>
                                            <span class="tag more">+<?php echo e(count($userTypes) - 3); ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="agent-card-footer">
                                <div class="action-buttons">
                                    <button class="btn-action primary" onclick="viewAgent('<?php echo e($agent->uuid); ?>')" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action warning" onclick="editAgent('<?php echo e($agent->uuid); ?>')" title="Edit Agent">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action <?php echo e($agent->status === 'active' ? 'danger' : 'success'); ?>" 
                                            onclick="toggleStatus('<?php echo e($agent->uuid); ?>')" 
                                            title="<?php echo e($agent->status === 'active' ? 'Deactivate' : 'Activate'); ?> Agent">
                                        <i class="fas fa-<?php echo e($agent->status === 'active' ? 'pause' : 'play'); ?>"></i>
                                    </button>
                                    <button class="btn-action danger" onclick="deleteAgent('<?php echo e($agent->uuid); ?>')" title="Delete Agent">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo e($agents->count()); ?></h3>
                            <p>Total Agents</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo e($agents->where('status', 'active')->count()); ?></h3>
                            <p>Active Agents</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo e($agents->where('status', 'inactive')->count()); ?></h3>
                            <p>Inactive Agents</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo e($agents->where('allow_negotiation', true)->count()); ?></h3>
                            <p>Negotiation Enabled</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h2>No AI Sales Agents Yet</h2>
                    <p>Create your first intelligent sales assistant to start automating customer conversations on WhatsApp.</p>
                    <div class="empty-actions">
                        <a href="<?php echo e(route('ai-agents.create')); ?>" class="btn btn-primary-lg">
                            <i class="fas fa-plus-circle me-2"></i>
                            Create Your First Agent
                        </a>
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
            <?php endif; ?>
    </div>
</div>

<!-- Agent Details Modal -->
<div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel">
                    <i class="fas fa-robot me-2"></i>Agent Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="agentModalBody">
                <!-- Agent details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Modern AI Agents Management Styles */
.ai-agents-management {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0.5rem 0 0 0;
}

.btn-create {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
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
}

.stat-card {
    padding: 1.5rem;
    border-radius: 15px;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon i {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-info h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.btn-primary-lg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
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
</style>

<script>
function viewAgent(uuid) {
    fetch(`<?php echo e(url('/ai-agents')); ?>/${uuid}`)
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
    window.location.href = "<?php echo e(url('/ai-agents')); ?>/" + uuid + "/edit";
}

function toggleStatus(uuid) {
    if (confirm('Are you sure you want to change the agent status?')) {
        fetch(`/ai-agents/${uuid}/toggle-status`, {
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
        fetch(`/ai-agents/${uuid}`, {
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\safarichat\resources\views/service/ai-agents/index.blade.php ENDPATH**/ ?>