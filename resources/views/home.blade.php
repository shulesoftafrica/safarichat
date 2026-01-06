@extends('layouts.app')
@section('content')

<!-- Onboarding Completion Message -->
@if(request('onboarding_complete') === 'true')
<div class="alert alert-success alert-dismissible fade show" style="background: linear-gradient(135deg, #28a745 0%, #17a2b8 100%); border: none; color: white; margin-bottom: 2rem;">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="fas fa-check-circle fa-3x"></i>
        </div>
        <div class="flex-grow-1">
            <h4 class="mb-1" style="color: white;"><strong>🎉 Onboarding Complete!</strong></h4>
            <p class="mb-0" style="font-size: 1.1rem;">Your WhatsApp AI Sales System is ready! You've successfully connected WhatsApp, added products, and configured your AI agent. You're all set to start converting leads into sales.</p>
        </div>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
</div>
@elseif(request('onboarding_complete') === 'proactive')
<div class="alert alert-info alert-dismissible fade show">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="fas fa-upload fa-2x"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="mb-1"><strong>Ready for Proactive Outreach!</strong></h5>
            <p class="mb-0">You can start importing contacts and sending targeted messages. Your AI will handle all conversations automatically.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .dashboard-container {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        padding: 20px;
        transition: background-color 0.3s ease;
    }
    
    /* Dark mode support for dashboard container */
    .dark-mode .dashboard-container {
        background: #1e2a40 !important;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(37, 211, 102, 0.2);
    }
    
    .welcome-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .welcome-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 20px;
    }
    
    .quick-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 10px;
    }
    
    .quick-action-btn:hover {
        background: white;
        color: #25d366;
        transform: translateY(-2px);
        text-decoration: none;
    }
    
    .metric-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    
    /* Dark mode support for metric cards */
    .dark .metric-card,
    [data-bs-theme="dark"] .metric-card,
    body.dark .metric-card {
        background: #1f2937;
        border: 1px solid #374151;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .dark .metric-card:hover,
    [data-bs-theme="dark"] .metric-card:hover,
    body.dark .metric-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    }
    
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-color, #e5e7eb);
    }
    
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 16px;
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    /* Dark mode support for metric values */
    .dark .metric-value,
    [data-bs-theme="dark"] .metric-value,
    body.dark .metric-value {
        color: #f9fafb;
    }
    
    .metric-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 12px;
    }
    
    /* Dark mode support for metric labels */
    .dark .metric-label,
    [data-bs-theme="dark"] .metric-label,
    body.dark .metric-label {
        color: #9ca3af;
    }
    
    .metric-trend {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
    }
    
    .trend-up {
        background: #dcfce7;
        color: #16a34a;
    }
    
    /* Dark mode support for trend indicators */
    .dark .trend-up,
    [data-bs-theme="dark"] .trend-up,
    body.dark .trend-up {
        background: #064e3b;
        color: #34d399;
    }
    
    .trend-down {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .dark .trend-down,
    [data-bs-theme="dark"] .trend-down,
    body.dark .trend-down {
        background: #7f1d1d;
        color: #f87171;
    }
    
    .action-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
        position: relative;
    }
    
    /* Dark mode support for action cards */
    .dark .action-card,
    [data-bs-theme="dark"] .action-card,
    body.dark .action-card {
        background: #1f2937;
        border: 1px solid #374151;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .action-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.3rem;
    }
    
    .action-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    /* Dark mode support for action titles */
    .dark .action-title,
    [data-bs-theme="dark"] .action-title,
    body.dark .action-title {
        color: #f9fafb;
    }
    
    .action-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.5;
    }
    
    /* Dark mode support for action descriptions */
    .dark .action-description,
    [data-bs-theme="dark"] .action-description,
    body.dark .action-description {
        color: #9ca3af;
    }
    
    .action-btn {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border: none;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .engagement-stats {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .stats-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .time-filter {
        display: flex;
        background: #f8fafc;
        border-radius: 8px;
        padding: 4px;
    }
    
    .time-filter-btn {
        padding: 6px 12px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .time-filter-btn.active {
        background: white;
        color: #25d366;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-ring {
        position: relative;
        display: inline-block;
        margin: 20px auto;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    
    .progress-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .progress-label {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .recent-activity {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1rem;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-text {
        font-size: 0.9rem;
        color: #374151;
        margin-bottom: 2px;
    }
    
    .activity-time {
        font-size: 0.8rem;
        color: #9ca3af;
    }
    
    .alert-banner {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .alert-content {
        display: flex;
        align-items: center;
    }
    
    .alert-icon {
        margin-right: 12px;
        font-size: 1.2rem;
    }
    
    .alert-text {
        font-weight: 500;
    }
    
    .alert-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .alert-btn:hover {
        background: white;
        color: #f59e0b;
    }
    
    .instance-selector-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
    }
    
    /* Dark mode support for instance selector */
    .dark .instance-selector-card,
    [data-bs-theme="dark"] .instance-selector-card,
    body.dark .instance-selector-card {
        background: #1f2937;
        border: 1px solid #374151;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .instance-select {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 10px 15px;
        font-weight: 500;
        min-width: 200px;
    }
    
    /* Dark mode support for instance select */
    .dark .instance-select,
    [data-bs-theme="dark"] .instance-select,
    body.dark .instance-select {
        background: #374151;
        border: 2px solid #4b5563;
        color: #f9fafb;
    }
    
    .instance-select:focus {
        border-color: #25d366;
        box-shadow: 0 0 0 0.25rem rgba(37, 211, 102, 0.15);
    }
    
    .instance-info {
        background: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        border-left: 4px solid #25d366;
    }
    
    /* Dark mode support for instance info */
    .dark .instance-info,
    [data-bs-theme="dark"] .instance-info,
    body.dark .instance-info {
        background: #374151;
        color: #f9fafb;
    }
    
    /* Dark mode support for any remaining text elements */
    .dark h1, .dark h2, .dark h3, .dark h4, .dark h5, .dark h6,
    [data-bs-theme="dark"] h1, [data-bs-theme="dark"] h2, [data-bs-theme="dark"] h3, 
    [data-bs-theme="dark"] h4, [data-bs-theme="dark"] h5, [data-bs-theme="dark"] h6,
    body.dark h1, body.dark h2, body.dark h3, body.dark h4, body.dark h5, body.dark h6 {
        color: #f9fafb !important;
    }
    
    .dark p, .dark span, .dark div,
    [data-bs-theme="dark"] p, [data-bs-theme="dark"] span, [data-bs-theme="dark"] div,
    body.dark p, body.dark span, body.dark div {
        color: #d1d5db;
    }
    
    /* Ensure text in cards remains visible */
    .dark .card-body, .dark .card-text,
    [data-bs-theme="dark"] .card-body, [data-bs-theme="dark"] .card-text,
    body.dark .card-body, body.dark .card-text {
        color: #d1d5db;
    }
    
    /* Form controls in dark mode */
    .dark .form-control, .dark .form-select,
    [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select,
    body.dark .form-control, body.dark .form-select {
        background-color: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .dark .form-control:focus, .dark .form-select:focus,
    [data-bs-theme="dark"] .form-control:focus, [data-bs-theme="dark"] .form-select:focus,
    body.dark .form-control:focus, body.dark .form-select:focus {
        background-color: #374151;
        border-color: #25d366;
        color: #f9fafb;
    }
</style>

<style>
    /* Dark mode overrides for entire dashboard */
    .dark-mode .dashboard-container {
        background: #1e2a40 !important;
    }
    
    .dark-mode .metric-card, 
    .dark-mode .action-card, 
    .dark-mode .engagement-stats, 
    .dark-mode .recent-activity, 
    .dark-mode .instance-selector-card {
        background: #222f48 !important;
        border: 1px solid #2d3951 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }
    
    .dark-mode .metric-card:hover, 
    .dark-mode .action-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4) !important;
    }
    
    .dark-mode .metric-value, 
    .dark-mode .action-title, 
    .dark-mode .stats-title, 
    .dark-mode .progress-value {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .metric-label, 
    .dark-mode .action-description, 
    .dark-mode .activity-text, 
    .dark-mode .progress-label {
        color: #cbd5e0 !important;
    }
    
    .dark-mode .activity-time {
        color: #a0aec0 !important;
    }
    
    .dark-mode .time-filter {
        background: #2d3951 !important;
    }
    
    .dark-mode .time-filter-btn {
        color: #8997bd !important;
    }
    
    .dark-mode .time-filter-btn.active {
        background: #1e2a40 !important;
        color: #25d366 !important;
    }
    
    .dark-mode .trend-up {
        background: #064e3b !important;
        color: #34d399 !important;
    }
    
    .dark-mode .trend-down {
        background: #7f1d1d !important;
        color: #f87171 !important;
    }
    
    .dark-mode .instance-select {
        background: #2d3951 !important;
        border: 2px solid #3d4a5c !important;
        color: #e2e8f0 !important;
    }
    
    .dark-mode .instance-info {
        background: #2d3951 !important;
        color: #e2e8f0 !important;
    }
    
    .dark-mode .form-control, 
    .dark-mode .form-select {
        background-color: #2d3951 !important;
        border-color: #3d4a5c !important;
        color: #e2e8f0 !important;
    }
    
    .dark-mode .form-control:focus, 
    .dark-mode .form-select:focus {
        background-color: #2d3951 !important;
        border-color: #25d366 !important;
        color: #e2e8f0 !important;
    }
    
    .dark-mode .activity-item {
        border-bottom: 1px solid #2d3951 !important;
    }
    
    /* Ensure all text is visible in dark mode */
    .dark-mode h1, 
    .dark-mode h2, 
    .dark-mode h3, 
    .dark-mode h4, 
    .dark-mode h5, 
    .dark-mode h6 {
        color: #f7fafc !important;
    }
    
    .dark-mode p, 
    .dark-mode span, 
    .dark-mode div, 
    .dark-mode .card-body, 
    .dark-mode .card-text {
        color: #e2e8f0 !important;
    }
    
    /* Improve text color for better readability */
    .dark-mode .text-muted {
        color: #cbd5e0 !important;
    }
    
    .dark-mode small, 
    .dark-mode .small {
        color: #a0aec0 !important;
    }
    
    /* Badge styling in dark mode */
    .dark-mode .badge.bg-light {
        background-color: #4a5568 !important;
        color: #e2e8f0 !important;
    }
    
    /* Links in dark mode */
    .dark-mode a {
        color: #63b3ed !important;
    }
    
    .dark-mode a:hover {
        color: #90cdf4 !important;
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="welcome-title">
                    <i class="fab fa-whatsapp"></i>Hello! Ready to connect with your customers?
                </h1>
                <p class="welcome-subtitle">You have <strong>{{$guests}}</strong> contacts and <strong>{{$active_conversations}}</strong> active conversations</p>
                <a href="{{url('message')}}" class="quick-action-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </a>
                <a href="{{url('guest')}}" class="quick-action-btn">
                    <i class="fas fa-upload"></i> Manage Contacts
                </a>
                <a href="{{url('whatsapp/incoming-messages')}}" class="quick-action-btn">
                    <i class="fas fa-comments"></i> View Messages
                </a>
            </div>
            <div class="col-md-4 text-center">
                <div style="font-size: 4rem; opacity: 0.3;">
                    <i class="fab fa-whatsapp"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Banner -->
    @if($guests > 0 && $messages_sent_today < 10)
    <div class="alert-banner">
        <div class="alert-content">
            <i class="fas fa-lightbulb alert-icon"></i>
            <span class="alert-text">You haven't sent many messages today. Engage more customers to grow your business!</span>
        </div>
        <a href="{{url('message')}}" class="alert-btn">Send Messages</a>
    </div>
    @endif

    <!-- WhatsApp Instance Selector -->
    @if(count($whatsapp_instances) > 1)
    <div class="instance-selector-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><i class="fas fa-mobile-alt"></i> WhatsApp Line</h5>
                <p class="mb-0 text-muted">Choose which WhatsApp line to manage</p>
            </div>
            <div>
                <select id="instanceSelector" class="form-select instance-select">
                    <option value="">All Lines</option>
                    @foreach($whatsapp_instances as $instance)
                        <option value="{{ $instance->id }}" 
                                {{ $active_instance_id == $instance->id ? 'selected' : '' }}>
                            {{ $instance->display_name ?: $instance->schema_name }}
                            @if($instance->is_primary) (Primary) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        @if($active_instance_id)
            @php $activeInstance = $whatsapp_instances->firstWhere('id', $active_instance_id); @endphp
            @if($activeInstance)
                <div class="instance-info mt-3">
                    <div class="row">
                        <div class="col-md-8">
                            <strong>{{ $activeInstance->display_name ?: $activeInstance->schema_name }}</strong>
                            @if($activeInstance->purpose)
                                <span class="badge bg-light text-dark ms-2">{{ $activeInstance->purpose }}</span>
                            @endif
                            @if($activeInstance->description)
                                <p class="mb-0 mt-1 text-muted small">{{ $activeInstance->description }}</p>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="showInstanceConfig('{{ $activeInstance->id }}')">
                                <i class="fas fa-cog"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
    @endif
    
    <!-- Key Metrics Row -->
    <div class="row">
        <!-- Subscription Status -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: 
                @if(Auth::user()->subscription_status === 'active') #10b981
                @elseif(Auth::user()->subscription_status === 'trial') #f59e0b  
                @else #ef4444 @endif;">
                <div class="metric-icon" style="background: 
                    @if(Auth::user()->subscription_status === 'active') #d1fae5; color: #059669;
                    @elseif(Auth::user()->subscription_status === 'trial') #fed7aa; color: #ea580c;
                    @else #fee2e2; color: #dc2626; @endif">
                    <i class="fas fa-
                        @if(Auth::user()->subscription_status === 'active') crown
                        @elseif(Auth::user()->subscription_status === 'trial') clock
                        @else exclamation-triangle @endif"></i>
                </div>
                <div class="metric-value" style="font-size: 1.2rem;">
                    @if(Auth::user()->subscription_status === 'active') 
                        Active
                    @elseif(Auth::user()->subscription_status === 'trial') 
                        Trial
                    @else 
                        Inactive
                    @endif
                </div>
                <div class="metric-label">Subscription Status</div>
                <span class="metric-trend" style="color: 
                    @if(Auth::user()->subscription_status === 'active') #059669
                    @elseif(Auth::user()->subscription_status === 'trial') #ea580c  
                    @else #dc2626 @endif;">
                    @if(Auth::user()->subscription_status === 'active')
                        <i class="fas fa-check-circle"></i> All features active
                    @elseif(Auth::user()->subscription_status === 'trial')
                        <i class="fas fa-clock"></i> {{ Auth::user()->trial_ends_at ? Auth::user()->trial_ends_at->diffInDays(now()) : 0 }} days left
                    @else
                        <i class="fas fa-exclamation"></i> <a href="{{ url('home/settings') }}" style="color: #dc2626;">Reactivate now</a>
                    @endif
                </span>
            </div>
        </div>

        <!-- Credits Balance -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #6366f1;">
                <div class="metric-icon" style="background: #e0e7ff; color: #4f46e5;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="metric-value">{{number_format(Auth::user()->available_credits ?? 0)}}</div>
                <div class="metric-label">Available Credits</div>
                <span class="metric-trend" style="color: #6b7280;">
                    <i class="fas fa-info-circle"></i> 1 credit = 4 AI tokens
                </span>
            </div>
        </div>

        <!-- WhatsApp Contacts -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #25d366;">
                <div class="metric-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="metric-value">{{number_format($guests)}}</div>
                <div class="metric-label">WhatsApp Contacts</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> +12% this month
                </span>
            </div>
        </div>

        <!-- Active Conversations -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #3b82f6;">
                <div class="metric-icon" style="background: #dbeafe; color: #2563eb;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-value">{{number_format($active_conversations)}}</div>
                <div class="metric-label">Active Conversations</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Last 30 days
                </span>
            </div>
        </div>
    </div>
    
    <!-- Secondary Metrics Row -->
    <div class="row">
        <!-- Messages Sent Today -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #8b5cf6;">
                <div class="metric-icon" style="background: #ede9fe; color: #7c3aed;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="metric-value">{{number_format($messages_sent_today)}}</div>
                <div class="metric-label">Messages Sent Today</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Today's activity
                </span>
            </div>
        </div>

        <!-- Response Rate -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #06b6d4;">
                <div class="metric-icon" style="background: #cffafe; color: #0891b2;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-value">{{$response_rate}}%</div>
                <div class="metric-label">Response Rate</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Last 7 days
                </span>
            </div>
        </div>
        
        <!-- Package Info -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #8b5cf6;">
                <div class="metric-icon" style="background: #f3e8ff; color: #7c3aed;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="metric-value" style="font-size: 1.2rem;">
                    @php $activeSubscription = Auth::user()->activeSubscription; @endphp
                    @if($activeSubscription)
                        {{ $activeSubscription->adminPackage->name ?? 'N/A' }}
                    @else
                        No Package
                    @endif
                </div>
                <div class="metric-label">Current Package</div>
                <span class="metric-trend" style="color: #7c3aed;">
                    <i class="fas fa-arrow-up"></i> <a href="{{ url('home/settings') }}" style="color: #7c3aed;">Upgrade</a>
                </span>
            </div>
        </div>
        
        <!-- Quick Action -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #f59e0b; cursor: pointer;" onclick="window.location.href='{{ url('home/settings') }}'">
                <div class="metric-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="metric-value" style="font-size: 1rem; line-height: 1.2;">Manage Subscription</div>
                <div class="metric-label">Settings & Billing</div>
                <span class="metric-trend" style="color: #d97706;">
                    <i class="fas fa-arrow-right"></i> Go to settings
                </span>
            </div>
        </div>
    </div>    <!-- Action Cards Row -->
    <div class="row">
        <!-- Quick Message -->
        <div class="col-lg-6 col-md-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="action-title">Quick Broadcast</h3>
                </div>
                <p class="action-description">
                    Send instant messages to all your customers about promotions, updates, or reminders.
                </p>
                <a href="{{url('message')}}" class="action-btn">
                    <i class="fas fa-paper-plane"></i> Send Now
                </a>
            </div>
        </div>

        <!-- Contact Management -->
        <div class="col-lg-6 col-md-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <h3 class="action-title">Contact Management</h3>
                </div>
                <p class="action-description">
                    Manage your customer contacts, import new ones, and organize your customer database.
                </p>
                <a href="{{url('guest')}}" class="action-btn">
                    <i class="fas fa-cog"></i> Manage Contacts
                </a>
            </div>
        </div>
    </div>

    <!-- Charts and Activity Row -->
    <div class="row">
        <!-- Engagement Chart -->
        <div class="col-lg-8">
            <div class="engagement-stats">
                <div class="stats-header">
                    <h3 class="stats-title">
                        <i class="fas fa-chart-area" style="color: #25d366; margin-right: 8px;"></i>
                        Message Engagement Trends
                    </h3>
                    <div class="time-filter">
                        <button class="time-filter-btn active">7 Days</button>
                        <button class="time-filter-btn">30 Days</button>
                        <button class="time-filter-btn">3 Months</button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div id="engagementChart" style="height: 300px;">
                            <!-- Chart will be rendered here -->
                            <script type="text/javascript">
                                $(function () {
                                    $('#engagementChart').highcharts({
                                        chart: {
                                            type: 'areaspline',
                                            backgroundColor: 'transparent'
                                        },
                                        title: {
                                            text: null
                                        },
                                        xAxis: {
                                            type: 'category',
                                            gridLineWidth: 0,
                                            lineWidth: 0,
                                            tickWidth: 0
                                        },
                                        yAxis: {
                                            title: {
                                                text: 'Messages'
                                            },
                                            gridLineWidth: 1,
                                            gridLineColor: '#f1f5f9'
                                        },
                                        legend: {
                                            enabled: true,
                                            align: 'center',
                                            verticalAlign: 'bottom'
                                        },
                                        plotOptions: {
                                            areaspline: {
                                                fillOpacity: 0.1,
                                                lineWidth: 3,
                                                marker: {
                                                    enabled: false,
                                                    states: {
                                                        hover: {
                                                            enabled: true,
                                                            radius: 5
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        colors: ['#25d366', '#3b82f6', '#8b5cf6'],
                                        series: [{
                                            name: 'Messages Sent',
                                            data: [
                                                @if(!empty($reports))
                                                    @foreach ($reports as $value)
                                                        ['{{ strtoupper($value->month_date) }}', {{ $value->sum }}],
                                                    @endforeach
                                                @else
                                                    ['No Data', 0]
                                                @endif
                                            ]
                                        }, {
                                            name: 'Active Conversations',
                                            data: [
                                                @if(!empty($reports))
                                                    @foreach ($reports as $value)
                                                        ['{{ strtoupper($value->month_date) }}', {{ intval($value->sum * 0.3) }}],
                                                    @endforeach
                                                @else
                                                    ['No Data', 0]
                                                @endif
                                            ]
                                        }]
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="progress-ring">
                                <svg width="120" height="120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#25d366" stroke-width="8" 
                                            stroke-dasharray="{{ ($response_rate / 100) * 314 }} 314"
                                            transform="rotate(-90 60 60)"/>
                                </svg>
                                <div class="progress-text">
                                    <div class="progress-value">{{ $response_rate }}%</div>
                                    <div class="progress-label">Response Rate</div>
                                </div>
                            </div>
                            <p style="color: #64748b; font-size: 0.9rem; margin-top: 15px;">
                                @if($response_rate > 50)
                                    Great! Your response rate is excellent.
                                @elseif($response_rate > 25)
                                    Good response rate. Keep engaging!
                                @else
                                    Try sending more engaging messages.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="recent-activity">
                <h3 class="stats-title" style="margin-bottom: 20px;">
                    <i class="fas fa-clock" style="color: #3b82f6; margin-right: 8px;"></i>
                    Recent Activity
                </h3>
                
                @if($recent_messages && $recent_messages->count() > 0)
                    @foreach($recent_messages as $message)
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                Message from {{ $message->phone_number }}
                                @if($message->guest)
                                    ({{ $message->guest->guest_name }})
                                @endif
                            </div>
                            <div class="activity-time">{{ $message->received_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $guests }} WhatsApp contacts available</div>
                            <div class="activity-time">Ready for messaging</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dbeafe; color: #2563eb;">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $messages_sent_today }} messages sent today</div>
                            <div class="activity-time">Today's activity</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $active_conversations }} active conversations</div>
                            <div class="activity-time">Last 30 days</div>
                        </div>
                    </div>
                @endif
                
                <div class="text-center" style="margin-top: 20px;">
                    <a href="{{url('whatsapp/incoming-messages')}}" style="color: #25d366; text-decoration: none; font-weight: 500; font-size: 0.9rem;">
                        View All Messages <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fas fa-target"></i>
                    </div>
                    <h3 class="action-title">Quick Actions</h3>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="{{url('guest')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-address-book" style="color: #3b82f6; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">View Contacts</div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{url('whatsapp/incoming-messages')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-chart-bar" style="color: #8b5cf6; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">View Messages</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{url('home/settings')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-cog" style="color: #6b7280; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">Settings</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{url('support')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-question-circle" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">Get Help</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart Library -->
<script src="<?=asset('assets/js/highchart.js')?>"></script>
<script src="<?=asset('assets/js/exporting.js')?>"></script>

<script>
// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Time filter functionality for charts
    const filterButtons = document.querySelectorAll('.time-filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            // In a real implementation, this would update chart data
            console.log('Filter changed to:', this.textContent);
        });
    });
    
    // Auto-refresh activity feed every 2 minutes
    setInterval(function() {
        // In real implementation, fetch latest activity via AJAX
        console.log('Activity feed would refresh here...');
    }, 120000);
});

// WhatsApp utility functions
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Quick navigation functions
function goToContacts() {
    window.location.href = '{{url('guest')}}';
}

function goToMessages() {
    window.location.href = '{{url('whatsapp/incoming-messages')}}';
}

function goToSettings() {
    window.location.href = '{{url('settings')}}';
}

// Success animations for metrics
function animateMetrics() {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(metric => {
        const finalValue = metric.textContent;
        metric.textContent = '0';
        
        const increment = parseInt(finalValue.replace(/,/g, '')) / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= parseInt(finalValue.replace(/,/g, ''))) {
                metric.textContent = finalValue;
                clearInterval(timer);
            } else {
                metric.textContent = formatNumber(Math.floor(current));
            }
        }, 20);
    });
}

// Initialize animations on page load
window.addEventListener('load', function() {
    setTimeout(animateMetrics, 500);
});

// WhatsApp Instance Management
document.addEventListener('DOMContentLoaded', function() {
    const instanceSelector = document.getElementById('instanceSelector');
    
    if (instanceSelector) {
        instanceSelector.addEventListener('change', function() {
            const selectedInstanceId = this.value;
            
            // Show loading state
            const originalHtml = instanceSelector.innerHTML;
            instanceSelector.disabled = true;
            
            // Make API call to select instance
            fetch('/api/whatsapp/instances/select', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    instance_id: selectedInstanceId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update dashboard with new instance data
                    window.location.reload();
                } else {
                    alert('Error selecting instance: ' + (data.message || 'Unknown error'));
                    instanceSelector.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error selecting instance');
                instanceSelector.disabled = false;
            });
        });
    }
});

function showInstanceConfig(instanceId) {
    // Create modal for instance configuration
    const modal = `
        <div class="modal fade" id="instanceConfigModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-cog"></i> Configure WhatsApp Instance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="instanceConfigForm">
                            <div class="mb-3">
                                <label class="form-label">Display Name</label>
                                <input type="text" class="form-control" id="displayName" placeholder="e.g., Main Business Line">
                                <small class="form-text text-muted">Friendly name for this WhatsApp line</small>
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
                                <textarea class="form-control" id="description" rows="3" placeholder="Describe how this line will be used..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveInstanceConfig('${instanceId}')">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('instanceConfigModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to DOM
    document.body.insertAdjacentHTML('beforeend', modal);
    
    // Load current instance data
    fetch(`/api/whatsapp/instances/${instanceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const instance = data.instance;
                document.getElementById('displayName').value = instance.display_name || '';
                document.getElementById('purpose').value = instance.purpose || '';
                document.getElementById('description').value = instance.description || '';
            }
        })
        .catch(error => console.error('Error loading instance data:', error));
    
    // Show modal
    const modalInstance = new bootstrap.Modal(document.getElementById('instanceConfigModal'));
    modalInstance.show();
}

function saveInstanceConfig(instanceId) {
    const form = document.getElementById('instanceConfigForm');
    const formData = {
        display_name: document.getElementById('displayName').value,
        purpose: document.getElementById('purpose').value,
        description: document.getElementById('description').value
    };
    
    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
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
            // Close modal and reload page
            bootstrap.Modal.getInstance(document.getElementById('instanceConfigModal')).hide();
            window.location.reload();
        } else {
            alert('Error saving configuration: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving configuration');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}
</script>

@endsection
