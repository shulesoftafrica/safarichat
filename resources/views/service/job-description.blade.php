@extends('layouts.app')
@section('content')

<div class="ai-agent-creator">
    <div class="container-fluid">
        <!-- Onboarding Message -->
        @if(request('onboarding') === 'true')
        <div class="onboarding-alert">
            <div class="alert alert-success alert-dismissible fade show" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; color: white; margin-bottom: 2rem;">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-robot fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1" style="color: white;"><strong>🤖 Final Step: Set Up Your AI Sales Agent</strong></h5>
                        <p class="mb-0">Perfect! Now let's create your intelligent sales assistant. This AI will handle customer conversations, answer questions about your products, and help convert leads into sales.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif
        
        <!-- Modern Header with Breadcrumb -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('ai-agents.index') }}">
                                    <i class="fas fa-robot me-1"></i>AI Agents
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Create New Agent</li>
                        </ol>
                    </nav>
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="header-text">
                            <h1 class="page-title">Configure Sales Settings</h1>
                            <p class="page-subtitle">
                                Set up your sales agent's rules, negotiation, and fallback options. The agent name is set when you create a WhatsApp instance.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('ai-agents.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Agents
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            @if(isset($existingAgent) && $existingAgent)
                <!-- Show existing agent summary first -->
                <div class="current-agent-card">
                    <div class="agent-summary">
                        <div class="agent-info">
                            <div class="agent-avatar-large">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="agent-details">
                                <h3>{{ $existingAgent->assistant_name }}</h3>
                                <p class="company">{{ $existingAgent->company_name ?? 'No company specified' }}</p>
                                <div class="status-info">
                                    <span class="status-badge {{ $existingAgent->status === 'active' ? 'active' : 'inactive' }}">
                                        {{ ucfirst($existingAgent->status) }}
                                    </span>
                                    <span class="created-date">Created {{ $existingAgent->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="agent-actions">
                            <button class="btn-edit" onclick="showEditForm()">
                                <i class="fas fa-edit me-2"></i>
                                Edit Agent
                            </button>
                            <a href="{{ route('ai-agents.index') }}" class="btn-secondary">
                                <i class="fas fa-list me-2"></i>
                                View All Agents
                            </a>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Agent Form (Create/Edit) -->
            <div id="agent-form-section" class="{{ isset($existingAgent) && $existingAgent ? 'editing-mode' : 'creating-mode' }}" 
                 style="{{ isset($existingAgent) && $existingAgent ? 'display: none;' : 'display: block;' }}">
                
                <div class="form-container">
                    <div class="form-header">
                        <h2 id="form-title">
                            Configure Sales Settings
                        </h2>
                        <p id="form-subtitle">
                            Define how this WhatsApp number (Sales Agent) will handle sales, negotiation, and fallback.
                        </p>
                    </div>

                    <!-- Progress Wizard -->
                    <div class="wizard-progress">
                        <div class="progress-step active" data-step="1">
                            <div class="step-circle">1</div>
                            <span class="step-label">Basic Info</span>
                        </div>
                        <div class="progress-step" data-step="2">
                            <div class="step-circle">2</div>
                            <span class="step-label">Personality</span>
                        </div>
                        <div class="progress-step" data-step="3">
                            <div class="step-circle">3</div>
                            <span class="step-label">Capabilities</span>
                        </div>
                        <div class="progress-step" data-step="4">
                            <div class="step-circle">4</div>
                            <span class="step-label">Review</span>
                        </div>
                    </div>

                    <!-- Show validation errors if present -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>There were some problems with your input:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="ai-agent-form" method="POST" action="{{ isset($existingAgent) && $existingAgent ? route('ai-agents.update', $existingAgent->uuid) : route('ai-agents.store') }}">
                        @csrf
                        @if(isset($existingAgent) && $existingAgent)
                            @method('PUT')
                        @endif

                        <!-- Step 1: Basic Information -->
                        <div class="wizard-step active" id="step-1">
                            <h3 class="step-title">Basic Information</h3>
                            <p class="step-subtitle">Let's start with the basic details of your AI sales assistant</p>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control" name="company_name" 
                                           value="{{ old('company_name', $existingAgent->company_name ?? '') }}" 
                                           placeholder="Your company name">
                                    <small class="form-hint">This helps personalize conversations</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Target Audience *</label>
                                    <select class="form-control" name="target_audience" required>
                                        <option value="">Select target audience</option>
                                        <option value="small-businesses" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'small-businesses' ? 'selected' : '' }}>Small Businesses (1-10 employees)</option>
                                        <option value="medium-businesses" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'medium-businesses' ? 'selected' : '' }}>Medium Businesses (11-50 employees)</option>
                                        <option value="enterprises" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'enterprises' ? 'selected' : '' }}>Large Enterprises (50+ employees)</option>
                                        <option value="individuals" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'individuals' ? 'selected' : '' }}>Individual Customers</option>
                                        <option value="mixed" {{ old('target_audience', $existingAgent->target_audience ?? '') == 'mixed' ? 'selected' : '' }}>Mixed (All types)</option>
                                    </select>
                                    <small class="form-hint">Your assistant will be optimized to sell to these customer types</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Primary Language *</label>
                                    <select class="form-control" name="primary_language" required>
                                        <option value="en" {{ old('primary_language', $existingAgent->primary_language ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="sw" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'sw' ? 'selected' : '' }}>Swahili</option>
                                        <option value="fr" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'fr' ? 'selected' : '' }}>French</option>
                                        <option value="ar" {{ old('primary_language', $existingAgent->primary_language ?? '') == 'ar' ? 'selected' : '' }}>Arabic</option>
                                    </select>
                                    <small class="form-hint">Main language for customer interactions</small>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Personality & Style -->
                        <div class="wizard-step" id="step-2">
                            <h3 class="step-title">Personality & Communication Style</h3>
                            <p class="step-subtitle">Define how your AI assistant should interact with customers</p>
                            
                            <div class="form-group">
                                <label class="form-label">Communication Tone *</label>
                                <div class="tone-options">
                                    <label class="tone-option">
                                        <input type="radio" name="communication_tone" value="professional" 
                                               {{ old('communication_tone', $existingAgent->communication_tone ?? 'friendly') == 'professional' ? 'checked' : '' }} required>
                                        <div class="tone-card">
                                            <i class="fas fa-briefcase"></i>
                                            <h4>Professional</h4>
                                            <p>Formal, business-appropriate language</p>
                                        </div>
                                    </label>
                                    <label class="tone-option">
                                        <input type="radio" name="communication_tone" value="friendly" 
                                               {{ old('communication_tone', $existingAgent->communication_tone ?? 'friendly') == 'friendly' ? 'checked' : '' }} required>
                                        <div class="tone-card">
                                            <i class="fas fa-smile"></i>
                                            <h4>Friendly</h4>
                                            <p>Warm, approachable, and conversational</p>
                                        </div>
                                    </label>
                                    <label class="tone-option">
                                        <input type="radio" name="communication_tone" value="consultative" 
                                               {{ old('communication_tone', $existingAgent->communication_tone ?? 'friendly') == 'consultative' ? 'checked' : '' }} required>
                                        <div class="tone-card">
                                            <i class="fas fa-handshake"></i>
                                            <h4>Consultative</h4>
                                            <p>Advisory, asking thoughtful questions</p>
                                        </div>
                                    </label>
                                    <label class="tone-option">
                                        <input type="radio" name="communication_tone" value="direct" 
                                               {{ old('communication_tone', $existingAgent->communication_tone ?? 'friendly') == 'direct' ? 'checked' : '' }} required>
                                        <div class="tone-card">
                                            <i class="fas fa-bolt"></i>
                                            <h4>Direct</h4>
                                            <p>Clear, concise, to-the-point</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            

                            
                            <div class="form-group">
                                <label class="form-label">Products/Services Description</label>
                                <textarea class="form-control" name="products_services" rows="3" 
                                          placeholder="Briefly describe what you sell (this helps the AI understand your business)">{{ old('products_services', $existingAgent->products_services ?? '') }}</textarea>
                                <small class="form-hint">Optional: Helps AI provide better product recommendations</small>
                            </div>
                        </div>

                        <!-- Step 3: Capabilities & Settings -->
                        <div class="wizard-step" id="step-3">
                            <h3 class="step-title">Capabilities & Availability</h3>
                            <p class="step-subtitle">Configure your AI assistant's capabilities and working hours</p>
                            
                            <div class="capability-section">
                                <h4>Availability Settings</h4>
                                <div class="form-group">
                                    <div class="toggle-option">
                                        <input type="checkbox" id="always_available" name="always_available" 
                                               {{ old('always_available', $existingAgent->always_available ?? true) ? 'checked' : '' }}>
                                        <label for="always_available">
                                            <span class="toggle-slider"></span>
                                            <div class="toggle-content">
                                                <h4>24/7 Availability</h4>
                                                <p>AI assistant will respond to messages at any time</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Out-of-Hours Message</label>
                                    <textarea class="form-control" name="out_of_hours_message" rows="3" 
                                              placeholder="Message when AI is not available">{{ old('out_of_hours_message', $existingAgent->out_of_hours_message ?? 'Thank you for contacting us! Our AI assistant is currently offline. We will respond as soon as possible.') }}</textarea>
                                </div>
                            </div>
                            
                            <div class="capability-section">
                                <h4>Sales Capabilities</h4>
                                <div class="form-group">
                                    <div class="toggle-option">
                                        <input type="checkbox" id="allow_negotiation" name="allow_negotiation" 
                                               {{ old('allow_negotiation', $existingAgent->allow_negotiation ?? true) ? 'checked' : '' }}>
                                        <label for="allow_negotiation">
                                            <span class="toggle-slider"></span>
                                            <div class="toggle-content">
                                                <h4>Price Negotiation</h4>
                                                <p>Allow AI to negotiate prices within set limits</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="negotiation-settings" class="form-group">
                                    <label class="form-label">Maximum Discount Allowed</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="max_discount_allowed" 
                                               min="0" max="50" value="{{ old('max_discount_allowed', $existingAgent->max_discount_allowed ?? 15) }}">
                                        <span class="input-addon">%</span>
                                    </div>
                                    <small class="form-hint">Maximum discount AI can offer to customers</small>
                                </div>
                            </div>
                            
                            <div class="capability-section">
                                <h4>Fallback Contact</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Fallback Phone Number *</label>
                                        <input type="tel" class="form-control" name="fallback_number" 
                                               placeholder="+254700000000" 
                                               value="{{ old('fallback_number', $existingAgent->fallback_number ?? '') }}" required>
                                        <small class="form-hint">Number to transfer when AI can't help</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" name="fallback_person" 
                                               placeholder="e.g., John - Sales Manager"
                                               value="{{ old('fallback_person', $existingAgent->fallback_person ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Review & Activate -->
                        <div class="wizard-step" id="step-4">
                            <h3 class="step-title">Review & Activate</h3>
                            <p class="step-subtitle">Review your AI assistant configuration and activate</p>
                            
                            <div class="review-card">
                                <div class="agent-preview">
                                    <div class="preview-avatar">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="preview-info">
                                        <h3 id="preview-name">AI Assistant</h3>
                                        <p id="preview-company">Your Company</p>
                                        <div class="preview-tags">
                                            <span class="tag" id="preview-industry">Industry</span>
                                            <span class="tag" id="preview-language">Language</span>
                                            <span class="tag" id="preview-tone">Tone</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-capabilities">
                                    <h4>Capabilities:</h4>
                                    <div class="capability-list">
                                        <div class="capability" id="preview-availability">
                                            <i class="fas fa-clock"></i>
                                            <span>24/7 Available</span>
                                        </div>
                                        <div class="capability" id="preview-negotiation">
                                            <i class="fas fa-handshake"></i>
                                            <span>Price Negotiation</span>
                                        </div>
                                        <div class="capability">
                                            <i class="fas fa-phone"></i>
                                            <span>Fallback Support</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="terms-section">
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="accepted_terms" name="accepted_terms" required>
                                        <span class="checkmark"></span>
                                        I agree to the <a href="{{ route('ai-agent-terms') }}" target="_blank">Terms of Service</a> 
                                        and <a href="{{ route('privacy-policy') }}" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="target_user_types[]" value="1">
                        <input type="hidden" name="timezone" value="Africa/Nairobi">

                        <!-- Navigation Buttons -->
                        <div class="wizard-navigation">
                            <button type="button" class="btn-nav secondary" id="prev-btn" onclick="previousStep()" style="display: none;">
                                <i class="fas fa-arrow-left me-2"></i>
                                Previous
                            </button>
                            <button type="button" class="btn-nav primary" id="next-btn" onclick="nextStep()">
                                Next Step
                                <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                            <button type="submit" class="btn-nav success" id="submit-btn" style="display: none;">
                                <i class="fas fa-rocket me-2"></i>
                                Create AI Agent
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern AI Agent Creator Styles */
.ai-agent-creator {
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

.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item a:hover {
    color: #495057;
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

.btn-secondary {
    background: #e9ecef;
    border: 1px solid #dee2e6;
    color: #6c757d;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
}

.content-wrapper {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* Current Agent Card */
.current-agent-card {
    padding: 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.agent-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.agent-info {
    display: flex;
    align-items: center;
}

.agent-avatar-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
}

.agent-avatar-large i {
    font-size: 2.5rem;
    color: white;
}

.agent-details h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    color: #2c3e50;
}

.agent-details .company {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin: 0.25rem 0;
}

.status-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.5rem;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.inactive {
    background: #fff3cd;
    color: #856404;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.created-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.agent-actions {
    display: flex;
    gap: 1rem;
}

.btn-edit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Form Container */
.form-container {
    padding: 2rem;
}

.form-header {
    text-align: center;
    margin-bottom: 3rem;
}

.form-header h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
}

.form-header p {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0;
}

/* Wizard Progress */
.wizard-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3rem;
    padding: 0 2rem;
    position: relative;
}

.wizard-progress::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 2rem;
    right: 2rem;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.progress-step.active .step-circle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.progress-step.completed .step-circle {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #6c757d;
    text-align: center;
}

.progress-step.active .step-label {
    color: #2c3e50;
}

/* Wizard Steps */
.wizard-step {
    display: none;
    animation: fadeIn 0.3s ease-in-out;
}

.wizard-step.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
}

.step-subtitle {
    color: #6c757d;
    font-size: 1rem;
    margin: 0 0 2rem 0;
}

/* Form Fields */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    display: block;
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Tone Options */
.tone-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.tone-option {
    cursor: pointer;
}

.tone-option input {
    display: none;
}

.tone-card {
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    text-align: center;
    transition: all 0.3s ease;
}

.tone-card i {
    font-size: 2rem;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.tone-card h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 0.25rem 0;
}

.tone-card p {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}

.tone-option:hover .tone-card {
    border-color: #667eea;
    transform: translateY(-2px);
}

.tone-option input:checked + .tone-card {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.tone-option input:checked + .tone-card i,
.tone-option input:checked + .tone-card h4,
.tone-option input:checked + .tone-card p {
    color: white;
}

/* Capability Sections */
.capability-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.capability-section h4 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 1rem 0;
}

/* Toggle Options */
.toggle-option {
    position: relative;
}

.toggle-option input {
    display: none;
}

.toggle-option label {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.toggle-option:hover label {
    border-color: #667eea;
}

.toggle-option input:checked + label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.toggle-slider {
    width: 50px;
    height: 26px;
    background: #ccc;
    border-radius: 26px;
    margin-right: 1rem;
    position: relative;
    transition: all 0.3s ease;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 3px;
    left: 3px;
    transition: all 0.3s ease;
}

.toggle-option input:checked + label .toggle-slider {
    background: #667eea;
}

.toggle-option input:checked + label .toggle-slider::before {
    transform: translateX(24px);
}

.toggle-content h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.toggle-content p {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0.25rem 0 0 0;
}

/* Input Groups */
.input-group {
    display: flex;
}

.input-addon {
    background: #e9ecef;
    border: 2px solid #e9ecef;
    border-left: none;
    padding: 0.75rem;
    border-radius: 0 10px 10px 0;
    color: #6c757d;
    font-weight: 500;
}

.input-group .form-control {
    border-radius: 10px 0 0 10px;
}

/* Review Section */
.review-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.agent-preview {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
}

.preview-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
}

.preview-avatar i {
    font-size: 2rem;
    color: white;
}

.preview-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.preview-info p {
    color: #6c757d;
    margin: 0.25rem 0 0.75rem 0;
}

.preview-tags {
    display: flex;
    gap: 0.5rem;
}

.tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
}

.preview-capabilities h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 1rem 0;
}

.capability-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.capability {
    display: flex;
    align-items: center;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #2c3e50;
}

.capability i {
    margin-right: 0.5rem;
    color: #667eea;
}

/* Terms Section */
.terms-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.95rem;
    color: #2c3e50;
}

.checkbox-label input {
    margin-right: 0.75rem;
}

.checkbox-label a {
    color: #667eea;
    text-decoration: none;
}

.checkbox-label a:hover {
    text-decoration: underline;
}

/* Navigation Buttons */
.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 0 0 0;
    border-top: 1px solid #e9ecef;
    margin-top: 2rem;
}

.btn-nav {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-nav.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-nav.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-nav.secondary {
    background: #e9ecef;
    color: #6c757d;
}

.btn-nav.secondary:hover {
    background: #f8f9fa;
}

.btn-nav.success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-nav.success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .tone-options {
        grid-template-columns: 1fr;
    }
    
    .wizard-progress {
        padding: 0 1rem;
    }
    
    .wizard-progress::before {
        left: 1rem;
        right: 1rem;
    }
    
    .agent-summary {
        flex-direction: column;
        gap: 1rem;
    }
    
    .agent-info {
        flex-direction: column;
        text-align: center;
    }
    
    .agent-avatar-large {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .capability-list {
        flex-direction: column;
    }
    
    .wizard-navigation {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
let currentStep = 1;
const totalSteps = 4;

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateWizard();
            updatePreview();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateWizard();
    }
}

function updateWizard() {
    // Update progress
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        const stepNumber = index + 1;
        if (stepNumber < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });

    // Update steps
    document.querySelectorAll('.wizard-step').forEach((step, index) => {
        const stepNumber = index + 1;
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    // Update navigation
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const submitBtn = document.getElementById('submit-btn');

    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
    }

    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'flex';
    } else {
        nextBtn.style.display = 'flex';
        submitBtn.style.display = 'none';
    }
}

function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step-${currentStep}`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            field.focus();
            alert('Please fill in all required fields before continuing.');
            return false;
        }
    }
    
    return true;
}

function updatePreview() {
    const assistantName = document.querySelector('[name="assistant_name"]').value || 'AI Assistant';
    const companyName = document.querySelector('[name="company_name"]').value || 'Your Company';
    const industry = document.querySelector('[name="company_industry"]').value;
    const language = document.querySelector('[name="primary_language"]').value;
    const tone = document.querySelector('[name="communication_tone"]:checked')?.value;
    const alwaysAvailable = document.querySelector('[name="always_available"]').checked;
    const allowNegotiation = document.querySelector('[name="allow_negotiation"]').checked;

    document.getElementById('preview-name').textContent = assistantName;
    document.getElementById('preview-company').textContent = companyName;
    document.getElementById('preview-industry').textContent = industry ? industry.replace('-', ' ').toUpperCase() : 'INDUSTRY';
    document.getElementById('preview-language').textContent = language ? language.toUpperCase() : 'ENGLISH';
    document.getElementById('preview-tone').textContent = tone ? tone.toUpperCase() : 'FRIENDLY';
    
    document.getElementById('preview-availability').innerHTML = 
        `<i class="fas fa-clock"></i><span>${alwaysAvailable ? '24/7 Available' : 'Business Hours'}</span>`;
    document.getElementById('preview-negotiation').innerHTML = 
        `<i class="fas fa-handshake"></i><span>${allowNegotiation ? 'Price Negotiation' : 'Fixed Pricing'}</span>`;
}

function showEditForm() {
    document.getElementById('agent-form-section').style.display = 'block';
    document.querySelector('.current-agent-card').style.display = 'none';
}

// Initialize wizard
document.addEventListener('DOMContentLoaded', function() {
    updateWizard();
    
    // Auto-update preview as user types
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('change', updatePreview);
        field.addEventListener('input', updatePreview);
    });
    
    // Handle negotiation settings visibility
    const allowNegotiation = document.getElementById('allow_negotiation');
    const negotiationSettings = document.getElementById('negotiation-settings');
    
    if (allowNegotiation && negotiationSettings) {
        function toggleNegotiationSettings() {
            negotiationSettings.style.display = allowNegotiation.checked ? 'block' : 'none';
        }
        
        allowNegotiation.addEventListener('change', toggleNegotiationSettings);
        toggleNegotiationSettings(); // Initial state
    }
    
    // Initial preview update
    updatePreview();
});
</script>


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
    // Intercept form submission
    const form = document.getElementById('ai-agent-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitConfiguration();
        });
    }
    
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

function submitConfiguration() {
    const form = document.getElementById('ai-agent-form');
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn?.innerHTML || 'Create AI Agent';
    
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        submitBtn.disabled = true;
    }
    
    // Submit the form via AJAX
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
        const checkbox = document.getElementById(fieldName) || document.querySelector(`input[name="${fieldName}"]`);
        formData.set(fieldName, checkbox?.checked ? '1' : '0');
    });
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('AI Sales Agent configured successfully!', 'success');
            
            // Check if we're in onboarding mode
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('onboarding') === 'true') {
                // Show onboarding completion modal
                setTimeout(() => {
                    const completionModal = document.createElement('div');
                    completionModal.className = 'modal fade show';
                    completionModal.style.display = 'block';
                    completionModal.innerHTML = `
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border: none; border-radius: 15px;">
                                <div class="modal-body text-center" style="background: linear-gradient(135deg, #28a745 0%, #17a2b8 100%); color: white; padding: 3rem;">
                                    <div style="font-size: 4rem; margin-bottom: 1rem;">🚀</div>
                                    <h3 style="color: white; margin-bottom: 1rem;">System Ready!</h3>
                                    <p style="font-size: 1.1rem; margin-bottom: 2rem;">Excellent! Your WhatsApp sales system is now fully configured. Choose how you want to start selling:</p>
                                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                                        <button class="btn btn-light btn-lg" onclick="chooseProactiveOutreach()" style="min-width: 180px;">
                                            <i class="fas fa-upload"></i><br>
                                            <small>Option A: Proactive</small><br>
                                            Import Contacts
                                        </button>
                                        <button class="btn btn-outline-light btn-lg" onclick="chooseInboundSales()" style="border-color: rgba(255,255,255,0.8); min-width: 180px;">
                                            <i class="fas fa-phone"></i><br>
                                            <small>Option B: Inbound</small><br>
                                            Wait for Messages
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-light" onclick="goToDashboard()" style="border-color: rgba(255,255,255,0.5);">
                                            Skip - Go to Dashboard
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-backdrop fade show"></div>
                    `;
                    document.body.appendChild(completionModal);
                }, 1500);
            } else {
                // Normal flow - reload page
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } else {
            // Show validation errors
            if (data.errors) {
                let errorMsg = 'Please fix the following errors:\n';
                Object.keys(data.errors).forEach(field => {
                    errorMsg += `\n• ${data.errors[field].join(', ')}`;
                });
                showNotification(errorMsg, 'error');
            } else {
                showNotification(data.message || 'Configuration failed', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving configuration. Please try again.', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
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

// Onboarding completion functions
function chooseProactiveOutreach() {
    // Remove completion modal
    const modal = document.querySelector('.modal.show');
    if (modal) {
        modal.remove();
    }
    
    // Navigate to contacts import or lead management
    window.location.href = '{{ url("/guest") }}?onboarding_complete=proactive';
}

function chooseInboundSales() {
    // Remove completion modal
    const modal = document.querySelector('.modal.show');
    if (modal) {
        modal.remove();
    }
    
    // Show WhatsApp number and marketing tips
    const inboundModal = document.createElement('div');
    inboundModal.className = 'modal fade show';
    inboundModal.style.display = 'block';
    inboundModal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #25d366; color: white;">
                    <h5 class="modal-title">
                        <i class="fab fa-whatsapp"></i>
                        Your WhatsApp Business Number
                    </h5>
                </div>
                <div class="modal-body text-center" style="padding: 2rem;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Your AI is ready!</strong> Share this number so customers can start conversations.
                    </div>
                    <div class="phone-display" style="background: #f8f9fa; border-radius: 10px; padding: 2rem; margin: 1rem 0;">
                        <h3 style="color: #25d366; margin-bottom: 1rem;">
                            <i class="fab fa-whatsapp"></i>
                            {{ auth()->user()->phone ?? '+255XXXXXXXXX' }}
                        </h3>
                        <p class="text-muted">Customers can message this number directly</p>
                    </div>
                    <div class="marketing-tips">
                        <h6><i class="fas fa-bullhorn"></i> Marketing Tips:</h6>
                        <ul class="list-unstyled">
                            <li>✓ Add this number to your business cards</li>
                            <li>✓ Share on social media profiles</li>
                            <li>✓ Include in email signatures</li>
                            <li>✓ Display on your website</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success btn-lg w-100" onclick="goToDashboard()">
                        <i class="fas fa-tachometer-alt"></i>
                        Go to Dashboard
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    `;
    document.body.appendChild(inboundModal);
}

function goToDashboard() {
    // Remove any modal
    const modal = document.querySelector('.modal.show');
    if (modal) {
        modal.remove();
    }
    
    // Navigate to dashboard with completion flag
    window.location.href = '{{ url("/dashboard") }}?onboarding_complete=true';
}
</script>
@endsection
    
