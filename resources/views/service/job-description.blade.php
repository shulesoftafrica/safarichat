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
                        AI Sales Officer
                        <span class="ai-badge ms-3">
                            <i class="fas fa-brain me-1"></i>
                            AI Powered
                        </span>
                    </h1>
                    <p class="reports-subtitle mb-0">
                        Configure your intelligent WhatsApp sales assistant for automated customer engagement
                    </p>
                </div>
            </div>
        </div>
        
        <div class="main-layout d-flex">
            <!-- Sidebar Navigation (Compact) -->
            <nav class="sidebar shadow-sm">
                <ul class="sidebar-nav nav flex-column py-3">
                    <li>
                        <a href="{{ url('service/index') }}" class="nav-link{{ request()->is('service/index') ? ' active' : '' }}">
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('service/jd') }}" class="nav-link{{ request()->is('service/jd') ? ' active' : '' }}">
                            <span>Job Description</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main Content Area -->
            <div class="content-area flex-grow-1 p-3 ms-3 mb-4" style="width:80%">
                <div class="job-description-page">
                    <div class="page-header">
                        <h2 class="page-title">
                            <i class="fas fa-clipboard-list"></i>
                            AI Job Description Configuration
                        </h2>
                        <div class="header-actions">
                            @if(!isset($existingAgent) || !$existingAgent)
                                <button class="btn btn-primary" onclick="showCreateForm()">
                                    <i class="fas fa-plus"></i>
                                    Add Job Description
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    @if(isset($existingAgent) && $existingAgent)
                        <!-- Debug Information (remove in production) -->
                        <div class="alert alert-info mb-3" id="debug-info">
                            <small>
                                <strong>Debug Info:</strong>
                                User ID: {{ auth()->id() }} | 
                                Agent ID: {{ $existingAgent->id }} | 
                                Agent User ID: {{ $existingAgent->user_id }} |
                                Update Route: {{ route('ai-agents.update', $existingAgent) }} |
                                Current URL: {{ request()->fullUrl() }} |
                                Auth Check: {{ auth()->check() ? 'true' : 'false' }}
                            </small>
                        </div>
                        
                        <!-- Existing Agents Table -->
                        <div class="agents-table-section mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-robot me-2"></i>
                                        Your AI Sales Agent
                                    </h5>
                                    <span class="badge bg-{{ $existingAgent->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($existingAgent->status) }}</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Agent Name</th>
                                                    <th>Target Audience</th>
                                                    <th>Status</th>
                                                    <th>Availability</th>
                                                    <th>Language</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-robot text-primary me-2"></i>
                                                            <strong>{{ $existingAgent->assistant_name }}</strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">{{ ucfirst(str_replace('-', ' ', $existingAgent->target_audience)) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $existingAgent->status === 'active' ? 'success' : 'secondary' }}">
                                                            {{ ucfirst($existingAgent->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">
                                                            {{ $existingAgent->always_available ? '24/7' : 'Business Hours' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">{{ strtoupper($existingAgent->primary_language) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">{{ $existingAgent->created_at->format('M d, Y') }}</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" onclick="editAgent({{ $existingAgent->id }})" title="Edit Configuration">
                                                            <i class="fas fa-edit me-1"></i>
                                                            Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- No Agents Found -->
                        <div class="no-agents-section mb-4">
                            <div class="card text-center">
                                <div class="card-body py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-robot fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">No AI Sales Agents Defined</h4>
                                        <p class="text-muted mb-4">
                                            Create your first AI sales assistant to start automating customer engagement and sales processes.
                                        </p>
                                        <button class="btn btn-primary btn-lg" onclick="showCreateForm()">
                                            <i class="fas fa-plus me-2"></i>
                                            Create Your First AI Agent
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Create/Edit Form (Initially Hidden) -->
                    <div id="agent-form-section" style="display: none;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" id="form-title">
                                    <i class="fas fa-plus me-2"></i>
                                    Create New AI Sales Agent
                                </h5>
                                <button class="btn btn-outline-secondary btn-sm" onclick="hideCreateForm()">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </button>
                            </div>
                            <div class="card-body">
                    
                    <form id="ai-agent-form" method="POST" action="{{ isset($existingAgent) && $existingAgent ? route('ai-agents.update', $existingAgent) : route('ai-agents.store') }}">
                        @csrf
                        
                        @if(isset($existingAgent) && $existingAgent)
                        <!-- Hidden field for agent ID when editing -->
                        <input type="hidden" id="editing-agent-id" name="agent_id" value="">
                        @endif
                        
                        <div class="configuration-wizard">
                            <!-- Progress Steps -->
                            <div class="steps-progress">
                                <div class="step active" data-step="1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Assistant Info</div>
                                </div>
                                <div class="step" data-step="2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Working Hours</div>
                                </div>
                                <div class="step" data-step="3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Negotiation</div>
                                </div>
                                <div class="step" data-step="4">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Terms & Review</div>
                                </div>
                            </div>
                            
                            <!-- Step 1: Assistant Information -->
                            <div class="step-content active" id="step-1">
                                <div class="step-card">
                                    <h4 class="step-title">
                                        <i class="fas fa-robot text-primary"></i>
                                        Step 1: Assistant Information
                                    </h4>
                                    <p class="step-description">Give your AI sales assistant a name and define its basic identity.</p>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Assistant Name *</label>
                                        <input type="text" class="form-control" name="assistant_name" 
                                               placeholder="e.g., Sarah, Alex, SalesBot Pro" 
                                               value="{{ old('assistant_name', $existingAgent->assistant_name ?? '') }}"
                                               required>
                                        <small class="text-muted">Choose a friendly name that customers will interact with</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Target Audience *</label>
                                        <select class="form-select" name="target_audience" required>
                                            <option value="">Select target audience</option>
                                            <option value="small-businesses" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'small-businesses' ? 'selected' : '' }}>Small Businesses (1-10 employees)</option>
                                            <option value="medium-businesses" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'medium-businesses' ? 'selected' : '' }}>Medium Businesses (11-50 employees)</option>
                                            <option value="enterprises" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'enterprises' ? 'selected' : '' }}>Large Enterprises (50+ employees)</option>
                                            <option value="individuals" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'individuals' ? 'selected' : '' }}>Individual Customers</option>
                                            <option value="mixed" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'mixed' ? 'selected' : '' }}>Mixed (All types)</option>
                                        </select>
                                        <small class="text-muted">Your assistant will be optimized to sell to these customer types</small>
                                    </div>
                                    
                                    <!-- Hidden target user types field (auto-populated based on target audience) -->
                                    <input type="hidden" name="target_user_types[]" value="1">
                                    
                                    <!-- Hidden timezone field -->
                                    <input type="hidden" name="timezone" value="Africa/Nairobi">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Communication Tone *</label>
                                        <select class="form-select" name="communication_tone" required>
                                            <option value="">Select communication style</option>
                                            <option value="professional" {{ old('communication_tone', $existingAgent->communication_tone ?? '') == 'professional' ? 'selected' : '' }}>Professional & Formal</option>
                                            <option value="friendly" {{ old('communication_tone', $existingAgent->communication_tone ?? '') == 'friendly' ? 'selected' : '' }}>Friendly & Casual</option>
                                            <option value="consultative" {{ old('communication_tone', $existingAgent->communication_tone ?? '') == 'consultative' ? 'selected' : '' }}>Consultative & Advisory</option>
                                            <option value="direct" {{ old('communication_tone', $existingAgent->communication_tone ?? '') == 'direct' ? 'selected' : '' }}>Direct & To-the-point</option>
                                        </select>
                                        <small class="text-muted">How your assistant will communicate with customers</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 2: Working Hours & Language -->
                            <div class="step-content" id="step-2">
                                <div class="step-card">
                                    <h4 class="step-title">
                                        <i class="fas fa-clock text-primary"></i>
                                        Step 2: Working Hours & Language
                                    </h4>
                                    <p class="step-description">Set when your AI sales officer should be active and which language to use.</p>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="always_available" name="always_available" 
                                                   {{ old('always_available', $existingAgent->always_available ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="always_available">
                                                <strong>Available 24/7</strong>
                                                <small class="d-block text-muted">AI will respond immediately at any time</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Primary Language *</label>
                                        <select class="form-select" name="primary_language" required>
                                            <option value="en" {{ old('primary_language', $existingAgent->primary_language ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                            <option value="sw" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'sw' ? 'selected' : '' }}>Swahili</option>
                                            <option value="fr" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'fr' ? 'selected' : '' }}>French</option>
                                            <option value="ar" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'ar' ? 'selected' : '' }}>Arabic</option>
                                            <option value="pt" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'pt' ? 'selected' : '' }}>Portuguese</option>
                                            <option value="am" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'am' ? 'selected' : '' }}>Amharic</option>
                                        </select>
                                        <small class="text-muted">Primary language for customer communication</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Out-of-Hours Message</label>
                                        <textarea class="form-control" name="out_of_hours_message" rows="3" placeholder="Message to send when AI is not available...">{{ old('out_of_hours_message', $existingAgent->out_of_hours_message ?? 'Thank you for contacting us! Our AI assistant is currently offline. Our business hours are Monday-Friday, 8:00 AM - 6:00 PM EAT. We\'ll respond to your message as soon as we\'re back online.') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 3: Negotiation & Fallback -->
                            <div class="step-content" id="step-3">
                                <div class="step-card">
                                    <h4 class="step-title">
                                        <i class="fas fa-handshake text-primary"></i>
                                        Step 3: Negotiation & Fallback
                                    </h4>
                                    <p class="step-description">Configure pricing negotiations and fallback contact information.</p>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allow_negotiation" name="allow_negotiation" 
                                                   {{ old('allow_negotiation', $existingAgent->allow_negotiation ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_negotiation">
                                                <strong>Allow AI to negotiate prices?</strong>
                                                <small class="d-block text-muted">Enable price negotiations within defined limits</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="negotiation-settings">
                                        <div class="form-group">
                                            <label class="form-label">Maximum Discount Allowed *</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="max_discount_allowed" min="0" max="50" 
                                                       value="{{ old('max_discount_allowed', $existingAgent->max_discount_allowed ?? 15) }}">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <small class="text-muted">Maximum discount AI can offer</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Fallback Phone Number *</label>
                                        <input type="tel" class="form-control" name="fallback_number" placeholder="+254700000000" 
                                               value="{{ old('fallback_number', $existingAgent->fallback_number ?? '') }}"
                                               required>
                                        <small class="text-muted">Number to transfer customers when AI cannot help</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Fallback Person Name</label>
                                        <input type="text" class="form-control" name="fallback_person" placeholder="e.g., John - Sales Manager"
                                               value="{{ old('fallback_person', $existingAgent->fallback_person ?? '') }}">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 4: Terms & Review -->
                            <div class="step-content" id="step-4">
                                <div class="step-card">
                                    <h4 class="step-title">
                                        <i class="fas fa-file-contract text-primary"></i>
                                        Step 4: Terms & Review
                                    </h4>
                                    <p class="step-description">Review your AI Sales Agent configuration and accept terms.</p>
                                    
                                    <div class="terms-section">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-info-circle text-info me-2"></i>
                                                    AI Sales Agent Service Agreement
                                                </h6>
                                                <p class="card-text">
                                                    By using our AI Sales Agent service, you agree to our terms of service, privacy policy, and acceptable use guidelines.
                                                </p>
                                                <div class="key-points mb-3">
                                                    <h6>Key Points:</h6>
                                                    <ul class="small">
                                                        <li>Your data is protected and encrypted</li>
                                                        <li>Service availability is 99.9% uptime target</li>
                                                        <li>You can modify or cancel anytime</li>
                                                        <li>Support is available during business hours</li>
                                                        <li>Billing is monthly based on usage</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="accepted_terms" name="accepted_terms" required>
                                            <label class="form-check-label" for="accepted_terms">
                                                <strong>I have read and accept the Terms & Conditions and Privacy Policy *</strong>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="configuration-summary" class="mt-4">
                                        <h6>Configuration Summary:</h6>
                                        <div class="configuration-summary">
                                            <div class="summary-section">
                                                <div class="summary-title">ðŸ¤– Assistant Information</div>
                                                <div class="summary-value">Name: <span id="review-assistant-name">-</span></div>
                                                <div class="summary-value">Target Audience: <span id="review-target-audience">-</span></div>
                                                <div class="summary-value">Communication Tone: <span id="review-communication-tone">-</span></div>
                                            </div>
                                            <div class="summary-section">
                                                <div class="summary-title">â° Working Hours & Language</div>
                                                <div class="summary-value">Availability: <span id="review-availability">-</span></div>
                                                <div class="summary-value">Primary Language: <span id="review-language">-</span></div>
                                            </div>
                                            <div class="summary-section">
                                                <div class="summary-title">ðŸ¤ Negotiation & Fallback</div>
                                                <div class="summary-value">Negotiation: <span id="review-negotiation">-</span></div>
                                                <div class="summary-value">Fallback Number: <span id="review-fallback">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="confirm-settings" name="confirm-settings" required>
                                            <label class="form-check-label" for="confirm-settings">
                                                I confirm that all settings are correct and want to activate this AI sales officer configuration.
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Navigation Buttons -->
                            <div class="wizard-navigation">
                                <button class="btn btn-outline-secondary" id="prev-step" onclick="previousStep()" style="display: none;">
                                    <i class="fas fa-arrow-left"></i>
                                    Previous
                                </button>
                                <button class="btn btn-primary" id="next-step" onclick="nextStep()">
                                    Next
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                                <button class="btn btn-success" id="save-config" onclick="finalSave()" style="display: none;">
                                    <i class="fas fa-save"></i>
                                    Save & Activate Configuration
                                </button>
                            </div>
                        </div>
                    </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .job-description-page {
        padding: 0;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .header-actions {
        display: flex;
        gap: 1rem;
    }
    
    .configuration-wizard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .steps-progress {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 2rem;
        overflow-x: auto;
    }
    
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        min-width: 120px;
        padding: 0 1rem;
        position: relative;
        color: #64748b;
    }
    
    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 15px;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }
    
    .step.active {
        color: #6366f1;
    }
    
    .step.active .step-number {
        background: #6366f1;
        color: white;
    }
    
    .step.completed .step-number {
        background: #10b981;
        color: white;
    }
    
    .step.completed::after {
        background: #10b981;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        position: relative;
        z-index: 2;
    }
    
    .step-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .step-content {
        display: none;
        padding: 2rem;
    }
    
    .step-content.active {
        display: block;
    }
    
    .step-card {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .step-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .step-description {
        color: #64748b;
        margin-bottom: 2rem;
        font-size: 1rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .wizard-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    
    .configuration-summary {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
    }
    
    .summary-section {
        margin-bottom: 1rem;
    }
    
    .summary-section:last-child {
        margin-bottom: 0;
    }
    
    .summary-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }
    
    .summary-value {
        color: #64748b;
        margin-left: 1rem;
        font-size: 0.85rem;
    }
    
    .terms-section {
        margin-bottom: 1.5rem;
    }
    
    /* Use the application's base font and sizing for consistency */
    body, .ai-sales-officer {
        font-family: inherit !important;
        font-size: 1rem;
        background: #f8fafc;
    }

    .ai-sales-officer {
        min-height: 100vh;
        padding-bottom: 24px;
    }

    .reports-header {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 14px;
        padding: 18px 18px 12px 18px;
        color: white;
        margin-bottom: 18px;
        box-shadow: 0 4px 16px rgba(37, 211, 102, 0.10);
    }

    .reports-title {
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .reports-subtitle {
        font-size: 0.97rem;
        opacity: 0.92;
        margin-bottom: 0;
    }

    .ai-badge {
        font-size: 0.78rem;
        font-weight: 500;
        background: #0ea5e9 !important;
        color: #fff !important;
        border-radius: 10px;
        padding: 2px 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .main-layout {
        gap: 18px;
    }

    .sidebar {
        width: 140px;
        min-width: 120px;
        max-width: 140px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        position: sticky;
        top: 24px;
        height: fit-content;
        padding: 0;
    }

    .sidebar-nav .nav-link {
        border: none;
        background: none;
        color: #334155;
        font-weight: 500;
        padding: 8px 10px;
        border-radius: 8px;
        transition: background 0.18s, color 0.18s;
        font-size: 0.98rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sidebar-nav .nav-link.active,
    .sidebar-nav .nav-link:hover {
        background: #e0f2fe;
        color: #0ea5e9;
    }

    .content-area {
        min-height: 400px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
        position: relative;
        transition: box-shadow 0.18s;
        font-size: 1rem;
    }

    @media (max-width: 991px) {
        .main-layout {
            flex-direction: column;
            gap: 12px;
        }
        .sidebar {
            width: 100%;
            min-width: unset;
            max-width: unset;
            position: static;
            margin-bottom: 10px;
        }
        .content-area {
            margin-left: 0 !important;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-actions {
            width: 100%;
        }
        
        .steps-progress {
            padding: 1rem;
        }
        
        .step {
            min-width: 80px;
            padding: 0 0.5rem;
        }
        
        .step-label {
            font-size: 0.65rem;
        }
        
        .step-content {
            padding: 1rem;
        }
        
        .wizard-navigation {
            padding: 1rem;
        }
    }

    @media (max-width: 600px) {
        .reports-header {
            padding: 10px;
        }
        .reports-title {
            font-size: 1rem;
        }
        .main-layout {
            gap: 6px;
        }
    }
</style>

<script type="text/javascript">
let currentStep = 1;
const totalSteps = 4;

// Table management functions
function showCreateForm() {
    document.getElementById('agent-form-section').style.display = 'block';
    document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>Create AI Sales Agent';
    
    // Reset form for new creation
    const form = document.getElementById('ai-agent-form');
    form.reset();
    form.action = "{{ route('ai-agents.store') }}";
    
    // Remove method field if it exists
    const methodField = form.querySelector('input[name="_method"]');
    if (methodField) {
        methodField.remove();
    }
    
    // Clear editing agent ID
    const editingIdField = document.getElementById('editing-agent-id');
    if (editingIdField) {
        editingIdField.value = '';
    }
    
    resetToFirstStep();
}

function hideCreateForm() {
    document.getElementById('agent-form-section').style.display = 'none';
}

function editAgent(agentId) {
    // Debug logging
    console.log('editAgent called with agentId:', agentId);
    @if(isset($existingAgent) && $existingAgent)
        console.log('Existing agent from page:', @json($existingAgent));
        console.log('Current user can edit agent ID:', agentId, 'User ID:', {{ auth()->id() }});
    @endif
    
    // Check if we already have agent data on the page
    @if(isset($existingAgent) && $existingAgent)
        const existingAgent = @json($existingAgent);
        
        // Verify that the agent belongs to the current user
        if (existingAgent.user_id !== {{ auth()->id() }}) {
            console.error('Security violation: Agent does not belong to current user', {
                agent_user_id: existingAgent.user_id,
                current_user_id: {{ auth()->id() }},
                agent_id: agentId
            });
            showNotification('Access denied. This agent does not belong to you.', 'error');
            return;
        }
        
        // Additional safety check - ensure the agentId matches the existing agent
        if (existingAgent.id !== agentId) {
            console.error('Agent ID mismatch', {
                existing_agent_id: existingAgent.id,
                requested_agent_id: agentId
            });
            showNotification('Invalid agent ID. Please refresh the page and try again.', 'error');
            return;
        }
        
        // Show form and populate with existing data
        document.getElementById('agent-form-section').style.display = 'block';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>Configure AI Sales Agent';
        
        // Update form action for editing using Laravel route helper
        const form = document.getElementById('ai-agent-form');
        const updateUrl = "{{ route('ai-agents.update', ':id') }}".replace(':id', agentId);
        form.action = updateUrl;
        
        console.log('Form action set to:', form.action);
        console.log('Current page URL:', window.location.href);
        console.log('Form method via hidden field:', form.querySelector('input[name="_method"]')?.value);
        console.log('Form method attribute:', form.method);
        
        console.log('Form action set to:', form.action);
        
        // Add method field for PUT
        let methodField = form.querySelector('input[name="_method"]');
        if (!methodField) {
            methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            form.appendChild(methodField);
        }
        methodField.value = 'PUT';
        
        console.log('Method field set to PUT');
        
        // Set editing agent ID
        const editingIdField = document.getElementById('editing-agent-id');
        if (editingIdField) {
            editingIdField.value = agentId;
        }
        
        // Populate form fields with existing agent data
        populateFormWithAgent(existingAgent);
        resetToFirstStep();
        
    @else
        // Fallback to AJAX if no existing agent data
        console.log('No existing agent data, falling back to AJAX');
        fetch(`/ai-agents/${agentId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('Authentication required. Please refresh the page and log in.');
                } else if (response.status === 404) {
                    throw new Error('Agent not found. It may have been deleted.');
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }
            
            // Enhanced content type validation to prevent JSON parsing errors
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response. Please check your login status and try again.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const agent = data.agent;
                document.getElementById('agent-form-section').style.display = 'block';
                document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>Configure AI Sales Agent';
                
                // Update form action for editing
                const form = document.getElementById('ai-agent-form');
                form.action = `/ai-agents/${agentId}`;
                
                // Add method field for PUT
                let methodField = form.querySelector('input[name="_method"]');
                if (!methodField) {
                    methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    form.appendChild(methodField);
                }
                methodField.value = 'PUT';
                
                // Set editing agent ID
                const editingIdField = document.getElementById('editing-agent-id');
                if (editingIdField) {
                    editingIdField.value = agentId;
                }
                
                // Populate form fields
                populateFormWithAgent(agent);
                resetToFirstStep();
            } else {
                throw new Error(data.message || 'Failed to load agent data');
            }
        })
        .catch(error => {
            console.error('Error loading agent:', error);
            let errorMessage = 'Error loading agent data: ' + error.message;
            if (error.message.includes('HTML instead of JSON') || 
                error.message.includes('Unexpected token') || 
                error.message.includes('<!DOCTYPE')) {
                errorMessage = 'Session expired or authentication required. Please refresh the page and try again.';
            } else if (error.message.includes('HTTP error! status: 401')) {
                errorMessage = 'Authentication required. Please log in and try again.';
            } else if (error.message.includes('HTTP error! status: 404')) {
                errorMessage = 'Agent not found. It may have been deleted.';
            }
            showNotification(errorMessage, 'error');
        });
    @endif
}

function populateFormWithAgent(agent) {
    // Populate form fields with agent data
    const fields = {
        'assistant_name': agent.assistant_name,
        'target_audience': agent.target_audience,
        'communication_tone': agent.communication_tone,
        'always_available': agent.always_available,
        'primary_language': agent.primary_language,
        'out_of_hours_message': agent.out_of_hours_message,
        'allow_negotiation': agent.allow_negotiation,
        'max_discount_allowed': agent.max_discount_allowed,
        'fallback_number': agent.fallback_number,
        'fallback_person': agent.fallback_person
    };
    
    Object.keys(fields).forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = !!fields[fieldName];
            } else {
                field.value = fields[fieldName] || '';
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeJobDescription();
});

function initializeJobDescription() {
    setupFormInteractions();
    updateStepVisibility();
    updateNavigationButtons();
}

function setupFormInteractions() {
    // Always available toggle
    const alwaysAvailableToggle = document.getElementById('always_available');
    if (alwaysAvailableToggle) {
        alwaysAvailableToggle.addEventListener('change', function() {
            // This can be used to show/hide custom hours section in future updates
        });
    }
    
    // Negotiation toggle
    const allowNegotiationToggle = document.getElementById('allow_negotiation');
    const negotiationSettings = document.getElementById('negotiation-settings');
    if (allowNegotiationToggle && negotiationSettings) {
        allowNegotiationToggle.addEventListener('change', function() {
            negotiationSettings.style.display = this.checked ? 'block' : 'none';
        });
    }
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepVisibility();
            updateNavigationButtons();
            
            if (currentStep === totalSteps) {
                generateSummary();
            }
        } else if (currentStep === totalSteps) {
            // On final step, submit the form
            submitConfiguration();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepVisibility();
        updateNavigationButtons();
    }
}

function updateStepVisibility() {
    // Update step indicators
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else if (stepNumber < currentStep) {
            step.classList.add('completed');
        }
    });
    
    // Update step content
    document.querySelectorAll('.step-content').forEach((content, index) => {
        const stepNumber = index + 1;
        content.classList.toggle('active', stepNumber === currentStep);
    });
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prev-step');
    const nextBtn = document.getElementById('next-step');
    const saveBtn = document.getElementById('save-config');
    
    if (prevBtn) prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    if (nextBtn) nextBtn.style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
    if (saveBtn) saveBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
}

function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step-${currentStep}`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    // Special validation for Terms & Review step (step 4)
    if (currentStep === 4) {
        const termsCheckbox = document.getElementById('accepted_terms');
        if (termsCheckbox && !termsCheckbox.checked) {
            termsCheckbox.focus();
            showNotification('You must accept the Terms & Conditions to proceed', 'warning');
            return false;
        }
        
        const confirmCheckbox = document.getElementById('confirm-settings');
        if (confirmCheckbox && !confirmCheckbox.checked) {
            confirmCheckbox.focus();
            showNotification('Please confirm the settings before saving.', 'warning');
            return false;
        }
    }
    
    for (let field of requiredFields) {
        if (!field.value.trim() && field.type !== 'checkbox') {
            field.focus();
            const label = field.previousElementSibling?.textContent || field.name;
            showNotification(`Please fill in the required field: ${label}`, 'warning');
            return false;
        }
        
        if (field.type === 'checkbox' && field.required && !field.checked) {
            field.focus();
            const label = field.nextElementSibling?.textContent || field.name;
            showNotification(`Please check the required field: ${label}`, 'warning');
            return false;
        }
    }
    
    return true;
}

function populateReviewStep() {
    // Populate Assistant Information
    const assistantName = document.querySelector('input[name="assistant_name"]');
    const targetAudience = document.querySelector('select[name="target_audience"]');
    const communicationTone = document.querySelector('select[name="communication_tone"]');
    const primaryLanguage = document.querySelector('select[name="primary_language"]');
    const alwaysAvailable = document.getElementById('always_available');
    const allowNegotiation = document.getElementById('allow_negotiation');
    const fallbackNumber = document.querySelector('input[name="fallback_number"]');
    
    // Update summary display
    const reviewAssistantName = document.getElementById('review-assistant-name');
    if (reviewAssistantName) reviewAssistantName.textContent = assistantName?.value || '-';
    
    const reviewTargetAudience = document.getElementById('review-target-audience');
    if (reviewTargetAudience) reviewTargetAudience.textContent = targetAudience?.selectedOptions[0]?.text || '-';
    
    const reviewCommunicationTone = document.getElementById('review-communication-tone');
    if (reviewCommunicationTone) reviewCommunicationTone.textContent = communicationTone?.selectedOptions[0]?.text || '-';
    
    const reviewAvailability = document.getElementById('review-availability');
    if (reviewAvailability) reviewAvailability.textContent = alwaysAvailable?.checked ? '24/7 Available' : 'Custom Schedule';
    
    const reviewLanguage = document.getElementById('review-language');
    if (reviewLanguage) reviewLanguage.textContent = primaryLanguage?.selectedOptions[0]?.text || '-';
    
    const reviewNegotiation = document.getElementById('review-negotiation');
    if (reviewNegotiation) reviewNegotiation.textContent = allowNegotiation?.checked ? 'Enabled' : 'Disabled';
    
    const reviewFallback = document.getElementById('review-fallback');
    if (reviewFallback) reviewFallback.textContent = fallbackNumber?.value || '-';
}

function generateSummary() {
    // Alias for populateReviewStep to maintain backwards compatibility
    populateReviewStep();
}

function finalSave() {
    const confirmCheckbox = document.getElementById('confirm-settings');
    if (!confirmCheckbox.checked) {
        showNotification('Please confirm the settings before saving.', 'warning');
        return;
    }
    
    const form = document.getElementById('ai-agent-form');
    const saveBtn = document.getElementById('save-config');
    const originalText = saveBtn.innerHTML;
    
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Configuration...';
    saveBtn.disabled = true;
    
    // Submit the form
    const formData = new FormData(form);
    
    // Ensure all boolean fields have proper values (1 for true, 0 for false)
    const booleanFields = [
        'always_available',
        'allow_negotiation', 
        'accept_installments',
        'stop_orders_low_stock',
        'auto_followup',
        'notify_on_deal'
    ];
    
    booleanFields.forEach(fieldName => {
        const checkbox = document.getElementById(fieldName);
        formData.set(fieldName, checkbox?.checked ? '1' : '0');
    });
    
    // Debug: Log form data and action
    console.log('Form action:', form.action);
    console.log('Form data being submitted:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    fetch(form.action, {
        method: 'POST', // Always use POST for Laravel forms with method spoofing
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response URL:', response.url);
        
        if (!response.ok) {
            // Handle HTTP errors
            if (response.status === 404) {
                // For 404, try to get more info from the response
                return response.text().then(htmlText => {
                    console.log('404 Response body:', htmlText.substring(0, 500));
                    throw new Error('Route not found or agent does not belong to current user. Please refresh and try again.');
                });
            } else if (response.status === 422) {
                // Validation error - try to parse JSON for detailed errors
                return response.json().then(data => {
                    throw new Error(data.message || 'Validation failed');
                }).catch(() => {
                    throw new Error('Validation failed. Please check your inputs.');
                });
            } else if (response.status === 401) {
                throw new Error('Authentication required. Please refresh the page and log in.');
            } else if (response.status === 403) {
                throw new Error('Access denied. You do not have permission to modify this agent.');
            } else {
                throw new Error(`Server error: ${response.status}`);
            }
        }
        
        // Check content type to ensure we got JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(htmlText => {
                console.log('Non-JSON response:', htmlText.substring(0, 500));
                throw new Error('Server returned non-JSON response. Please check your login status.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showNotification('AI Sales Agent configured successfully!', 'success');
            // Reload to show updated agent in table
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Configuration failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving configuration: ' + error.message, 'error');
    })
    .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function saveConfiguration() {
    showNotification('Quick save completed!', 'success');
}

function resetConfiguration() {
    if (confirm('Are you sure you want to reset all configuration to default values? This will clear all your current settings.')) {
        // Reset form
        document.querySelectorAll('.configuration-wizard input, .configuration-wizard select, .configuration-wizard textarea').forEach(field => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = field.defaultChecked;
            } else {
                field.value = field.defaultValue;
            }
        });
        
        resetToFirstStep();
        showNotification('Configuration reset to default values.', 'info');
    }
}

function resetToFirstStep() {
    currentStep = 1;
    updateStepVisibility();
    updateNavigationButtons();
    setupFormInteractions();
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'warning' ? 'alert-warning' : 
                     type === 'info' ? 'alert-info' : 'alert-danger';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.borderRadius = '8px';
    notification.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
    
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
@endsection
    
