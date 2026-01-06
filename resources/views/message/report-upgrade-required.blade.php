@extends('layouts.app')

@section('title', 'Sales Reports - Upgrade Required')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <h4 class="page-title">
                            <i class="fas fa-chart-line mr-2"></i>Sales Reports & Analytics
                        </h4>
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{url('home')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Sales Reports</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Upgrade Required Card -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chart-line text-primary" style="font-size: 5rem; opacity: 0.3;"></i>
                            </div>
                            
                            <h2 class="text-primary mb-3">
                                <i class="fas fa-lock mr-2"></i>Advanced Sales Reports
                            </h2>
                            
                            <p class="lead text-muted mb-4">
                                Detailed sales analytics and reporting features are available in the <strong>Premium</strong> plan.
                            </p>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="feature-preview">
                                        <h5><i class="fas fa-chart-bar text-success mr-2"></i>Revenue Analytics</h5>
                                        <p class="text-muted">Track revenue trends and performance metrics</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-preview">
                                        <h5><i class="fas fa-users text-info mr-2"></i>Customer Insights</h5>
                                        <p class="text-muted">Analyze customer behavior and engagement</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-preview">
                                        <h5><i class="fas fa-calendar-alt text-warning mr-2"></i>Time-based Reports</h5>
                                        <p class="text-muted">Monthly, quarterly, and yearly reports</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-preview">
                                        <h5><i class="fas fa-download text-danger mr-2"></i>Export Reports</h5>
                                        <p class="text-muted">Export data to PDF and Excel formats</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="plan-comparison mb-4">
                                <div class="current-plan">
                                    <span class="badge badge-light">Current Plan: {{ ucfirst($current_plan) }}</span>
                                </div>
                                <i class="fas fa-arrow-right mx-3 text-muted"></i>
                                <div class="required-plan">
                                    <span class="badge badge-primary">Required: {{ ucfirst($required_plan) }}</span>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="button" 
                                        class="btn btn-primary btn-lg px-5"
                                        onclick="showUpgradeModal('sales_reports')">
                                    <i class="fas fa-rocket mr-2"></i>Upgrade to Premium
                                </button>
                                
                                <a href="{{url('home')}}" class="btn btn-outline-secondary btn-lg px-4 ml-3">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                                </a>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top">
                                <p class="text-muted mb-0">
                                    <small>
                                        Need help choosing the right plan? 
                                        <a href="mailto:support@safarichat.com">Contact our support team</a>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.feature-preview {
    padding: 1rem;
    margin-bottom: 1rem;
}

.plan-comparison {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 2rem 0;
}

.action-buttons {
    margin: 2rem 0;
}

.feature-preview h5 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.feature-preview p {
    margin-bottom: 0;
    font-size: 0.9rem;
}

/* Dark Mode Styles */
.dark-mode .page-content-wrapper {
    background-color: #1a1a1a;
    color: #ffffff;
}

.dark-mode .page-content {
    background-color: #1a1a1a;
}

.dark-mode .container-fluid {
    background-color: #1a1a1a;
    color: #ffffff;
}

.dark-mode .page-title-box {
    background-color: #2d2d2d;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.dark-mode .page-title {
    color: #ffffff !important;
}

.dark-mode .breadcrumb {
    background-color: transparent;
}

.dark-mode .breadcrumb-item a {
    color: #007bff;
}

.dark-mode .breadcrumb-item.active {
    color: #cccccc;
}

.dark-mode .card {
    background-color: #2d2d2d;
    border: 1px solid #404040;
}

.dark-mode .card-body {
    background-color: #2d2d2d;
    color: #ffffff;
}

.dark-mode h2 {
    color: #ffffff !important;
}

.dark-mode .text-primary {
    color: #4a90ff !important;
}

.dark-mode .lead {
    color: #e0e0e0 !important;
}

.dark-mode .text-muted {
    color: #cccccc !important;
}

.dark-mode .feature-preview {
    background-color: #404040;
    border-radius: 8px;
    border: 1px solid #606060;
}

.dark-mode .feature-preview h5 {
    color: #ffffff !important;
}

.dark-mode .feature-preview p {
    color: #cccccc !important;
}

.dark-mode .text-success {
    color: #28a745 !important;
}

.dark-mode .text-info {
    color: #17a2b8 !important;
}

.dark-mode .text-warning {
    color: #ffc107 !important;
}

.dark-mode .text-danger {
    color: #dc3545 !important;
}

.dark-mode .plan-comparison {
    color: #ffffff;
}

.dark-mode .badge-light {
    background-color: #6c757d;
    color: #ffffff;
}

.dark-mode .badge-primary {
    background-color: #007bff;
    color: #ffffff;
}

.dark-mode .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    color: #ffffff;
}

.dark-mode .btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

.dark-mode .btn-outline-secondary {
    color: #ffffff;
    border-color: #6c757d;
}

.dark-mode .btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #ffffff;
}

.dark-mode .border-top {
    border-color: #606060 !important;
}

.dark-mode small {
    color: #cccccc !important;
}

.dark-mode a {
    color: #4a90ff;
}

.dark-mode a:hover {
    color: #6ab7ff;
}

/* Icon styling in dark mode */
.dark-mode .fas {
    opacity: 1;
}

.dark-mode .fa-chart-line {
    color: #4a90ff !important;
}

.dark-mode .fa-lock {
    color: #4a90ff !important;
}

.dark-mode .fa-chart-bar {
    color: #28a745 !important;
}

.dark-mode .fa-users {
    color: #17a2b8 !important;
}

.dark-mode .fa-calendar-alt {
    color: #ffc107 !important;
}

.dark-mode .fa-download {
    color: #dc3545 !important;
}

.dark-mode .fa-rocket {
    color: #ffffff !important;
}

.dark-mode .fa-arrow-left {
    color: #ffffff !important;
}

.dark-mode .fa-arrow-right {
    color: #cccccc !important;
}

/* Responsive adjustments for dark mode */
@media (max-width: 768px) {
    .dark-mode .feature-preview {
        margin-bottom: 1rem;
    }
    
    .dark-mode .plan-comparison {
        flex-direction: column;
        gap: 1rem;
    }
    
    .dark-mode .action-buttons .btn {
        display: block;
        margin: 0.5rem 0;
        width: 100%;
    }
}

/* Enhanced contrast for better readability */
.dark-mode strong {
    color: #ffffff !important;
    font-weight: 600;
}

.dark-mode .lead strong {
    color: #4a90ff !important;
}
</style>
@endsection