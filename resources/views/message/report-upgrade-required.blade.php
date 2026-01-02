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
</style>
@endsection