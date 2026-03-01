@extends('layouts.app')
@section('content')

<style>
/* Dark Mode Styles for Settings Page */
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

.dark-mode .page-title,
.dark-mode .header-title {
    color: #ffffff;
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

.dark-mode .text-muted {
    color: #cccccc !important;
}

/* Navigation Pills */
.dark-mode .nav-pills .nav-link {
    background-color: #404040;
    border: 1px solid #606060;
    color: #ffffff;
    margin-bottom: 5px;
}

.dark-mode .nav-pills .nav-link:hover {
    background-color: #505050;
    color: #ffffff;
}

.dark-mode .nav-pills .nav-link.active {
    background-color: #007bff;
    border-color: #007bff;
    color: #ffffff;
}

/* Tab Content */
.dark-mode .tab-content {
    background-color: #2d2d2d;
    color: #ffffff;
}

.dark-mode .tab-pane {
    color: #ffffff;
}

/* Tables */
.dark-mode .table {
    color: #ffffff;
    background-color: #2d2d2d;
}

.dark-mode .table thead th {
    background-color: #404040;
    border-color: #606060;
    color: #ffffff;
}

.dark-mode .table-bordered {
    border: 1px solid #606060;
}

.dark-mode .table-bordered th,
.dark-mode .table-bordered td {
    border: 1px solid #606060;
}

.dark-mode .thead-light th {
    background-color: #404040;
    border-color: #606060;
    color: #ffffff;
}

.dark-mode .table-responsive {
    background-color: #2d2d2d;
}

/* Forms */
.dark-mode .form-control {
    background-color: #404040;
    border: 1px solid #606060;
    color: #ffffff;
}

.dark-mode .form-control:focus {
    background-color: #404040;
    border-color: #007bff;
    color: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.dark-mode .form-control::placeholder {
    color: #999999;
}

.dark-mode label {
    color: #ffffff;
}

/* Modals */
.dark-mode .modal-content {
    background-color: #2d2d2d;
    border: 1px solid #606060;
}

.dark-mode .modal-header {
    background-color: #404040;
    border-bottom: 1px solid #606060;
    color: #ffffff;
}

.dark-mode .modal-title {
    color: #ffffff;
}

.dark-mode .modal-body {
    background-color: #2d2d2d;
    color: #ffffff;
}

.dark-mode .modal-footer {
    background-color: #404040;
    border-top: 1px solid #606060;
}

.dark-mode .close {
    color: #ffffff;
    opacity: 0.8;
}

.dark-mode .close:hover {
    color: #ffffff;
    opacity: 1;
}

/* Buttons */
.dark-mode .btn-secondary {
    background-color: #606060;
    border-color: #606060;
    color: #ffffff;
}

.dark-mode .btn-secondary:hover {
    background-color: #707070;
    border-color: #707070;
}

.dark-mode .btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.dark-mode .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.dark-mode .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.dark-mode .btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

/* Alerts */
.dark-mode .alert-info {
    background-color: #1f4e79;
    border-color: #2e5f8a;
    color: #b3d4fc;
}

.dark-mode .alert-warning {
    background-color: #856404;
    border-color: #d39e00;
    color: #fff3cd;
}

.dark-mode .alert-danger {
    background-color: #721c24;
    border-color: #d6336c;
    color: #f5c6cb;
}

/* Progress bars */
.dark-mode .progress {
    background-color: #404040;
}

.dark-mode .progress-bar {
    background-color: #007bff;
}

/* List groups */
.dark-mode .list-group-item {
    background-color: #404040;
    border: 1px solid #606060;
    color: #ffffff;
}

.dark-mode .list-group-item:hover {
    background-color: #505050;
}

/* Badges */
.dark-mode .badge-success {
    background-color: #28a745;
    color: #ffffff;
}

.dark-mode .badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.dark-mode .badge-primary {
    background-color: #007bff;
    color: #ffffff;
}

/* Input groups */
.dark-mode .input-group-text {
    background-color: #404040;
    border: 1px solid #606060;
    color: #ffffff;
}

/* Custom subscription cards */
.dark-mode .subscription-status-card {
    background-color: #404040;
    border: 1px solid #606060;
}

.dark-mode .subscription-info {
    color: #ffffff;
}

.dark-mode .credit-counter {
    background-color: #1a1a1a;
    border: 1px solid #606060;
    color: #ffffff;
}

/* Notification styles */
.dark-mode .alert-dismissible {
    background-color: #856404;
    border-color: #d39e00;
    color: #fff3cd;
}

/* Table hover effects */
.dark-mode .table-hover tbody tr:hover {
    background-color: #404040;
}

/* Dropdown menus */
.dark-mode .dropdown-menu {
    background-color: #2d2d2d;
    border: 1px solid #606060;
}

.dark-mode .dropdown-item {
    color: #ffffff;
}

.dark-mode .dropdown-item:hover {
    background-color: #404040;
    color: #ffffff;
}

/* Status indicators */
.dark-mode .status-active {
    color: #28a745;
}

.dark-mode .status-inactive {
    color: #dc3545;
}

.dark-mode .status-pending {
    color: #ffc107;
}

/* Improve text readability - fix blurry/dim text */
.dark-mode h4,
.dark-mode h5,
.dark-mode h6 {
    color: #ffffff !important;
    font-weight: 600;
}

.dark-mode .text-muted,
.dark-mode p.text-muted {
    color: #e0e0e0 !important;
    opacity: 1;
}

/* Section headers and titles */
.dark-mode .header-title {
    color: #ffffff !important;
    font-weight: 600;
}

.dark-mode .page-title {
    color: #ffffff !important;
}

/* Tab content headers */
.dark-mode .tab-pane h4,
.dark-mode .tab-pane h5 {
    color: #ffffff !important;
}

/* Package table headers */
.dark-mode .table th {
    color: #ffffff !important;
    font-weight: 600;
}

.dark-mode .table td {
    color: #e0e0e0 !important;
}

/* Quick Actions and other section labels */
.dark-mode .col-lg-4 h5,
.dark-mode .col-md-6 h6,
.dark-mode .subscription-section h5,
.dark-mode .billing-section h6 {
    color: #ffffff !important;
    font-weight: 600;
}

/* Card titles and content */
.dark-mode .card-title {
    color: #ffffff !important;
    font-weight: 600;
}

.dark-mode .card-text {
    color: #e0e0e0 !important;
}

/* Improve paragraph text */
.dark-mode p {
    color: #e0e0e0 !important;
}

/* Package information text */
.dark-mode .package-info,
.dark-mode .billing-info {
    color: #ffffff !important;
}

/* Labels and form text */
.dark-mode label,
.dark-mode .form-label {
    color: #ffffff !important;
    font-weight: 500;
}

/* Available credits text */
.dark-mode .credit-info,
.dark-mode .credit-text {
    color: #ffffff !important;
    font-weight: 500;
}

/* Subscription status text */
.dark-mode .subscription-text {
    color: #ffffff !important;
}

/* General text improvements */
.dark-mode span,
.dark-mode small,
.dark-mode .small {
    color: #e0e0e0 !important;
}

/* Fix any remaining dim text */
.dark-mode * {
    text-shadow: none !important;
}

.dark-mode .text-dark {
    color: #ffffff !important;
}

.dark-mode .text-secondary {
    color: #cccccc !important;
}
</style>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Profile</a></li>
                        <li class="breadcrumb-item active">settings</li>
                    </ol>
                </div>
                <h4 class="page-title">General Settings</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">List of items to be set</h4>
                    <p class="text-muted mb-3">Put the correct setting value to get the best out of the system
                    </p>


                    <div class="row">
                        <div class="col-sm-3">
                            <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <a class="nav-link waves-effect waves-light active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">User Accounts</a>
                                <a class="nav-link waves-effect waves-light " id="v-pills-subscription-tab" data-toggle="pill" href="#v-pills-subscription" role="tab" aria-controls="v-pills-subscription" aria-selected="false">Subscription & Billing</a>
                                <!-- <a class="nav-link waves-effect waves-light " id="v-pills-settings-tab" data-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings" aria-selected="false">Customer Category</a> -->
                                <a class="nav-link waves-effect waves-light " id="v-pills-business-tab" data-toggle="pill" href="#v-pills-business" role="tab" aria-controls="v-pills-business" aria-selected="false">Business Settings</a>
                            </div>
                        </div>
                        <div class="col-sm-9">
                            <div class="tab-content mo-mt-2" id="v-pills-tabContent">
                                <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="table-responsive">
                                        <h4 class="mt-0 header-title">Manage User Accounts</h4>
                                        <p class="text-muted mb-3">Each user account is able to login, and manage  activities, view reports and much more.. </p>
                                        <div>
                                            <p> 
                    

                                            </p>
                                        </div>
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Date Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($user_accounts as $account) {
                                                    ?>
                                                    <tr>
                                                        <th><?= $i ?></th>
                                                        <th><?= $account->user->name ?></th>
                                                        <th><?= $account->user->email ?></th>
                                                        <th><?= $account->user->phone ?></th>
                                                        <th><?= date('d M Y', strtotime($account->user->created_at)) ?></th>
                                                        <th>
                                                            <?php
                                                            if ($account->user->id == Auth::user()->id) {
                                                                ?>
                                                                <a onclick="editGuest('<?= $account->user->id ?>')" data-toggle="modal" href="#user_accounts"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php } ?>
                                                            <!-- <a href="#"> <i class="las la-trash-alt text-danger font-18"></i> Delete</a> -->
                                                        </th>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Subscription & Billing Tab -->
                                <div class="tab-pane fade" id="v-pills-subscription" role="tabpanel" aria-labelledby="v-pills-subscription-tab">
                                    <div class="subscription-billing-container">
                                        <h4 class="mt-0 header-title mb-1">Subscription & Billing</h4>
                                        <p class="text-muted mb-4">Manage your subscription, view usage, and handle billing</p>
                                        
                                        <style>
                                            .subscription-billing-container {
                                                padding: 10px;
                                            }
                                            .billing-card {
                                                border-radius: 12px;
                                                border: 1px solid #e2e8f0;
                                                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                                                margin-bottom: 24px;
                                                overflow: hidden;
                                            }
                                            .billing-card-header {
                                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                color: white;
                                                padding: 20px;
                                                font-weight: 600;
                                                font-size: 1.1rem;
                                            }
                                            .billing-card-body {
                                                padding: 24px;
                                            }
                                            .plan-badge {
                                                display: inline-block;
                                                padding: 6px 16px;
                                                border-radius: 20px;
                                                font-weight: 600;
                                                font-size: 0.85rem;
                                                text-transform: uppercase;
                                            }
                                            .plan-badge.trial {
                                                background: #fef3c7;
                                                color: #92400e;
                                            }
                                            .plan-badge.starter {
                                                background: #dbeafe;
                                                color: #1e40af;
                                            }
                                            .plan-badge.pro {
                                                background: #ddd6fe;
                                                color: #5b21b6;
                                            }
                                            .plan-badge.premium {
                                                background: #fef3c7;
                                                color: #92400e;
                                            }
                                            .plan-badge.active {
                                                background: #d1fae5;
                                                color: #065f46;
                                            }
                                            .plan-badge.inactive {
                                                background: #fee2e2;
                                                color: #991b1b;
                                            }
                                            .credit-display {
                                                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                                                color: white;
                                                padding: 24px;
                                                border-radius: 12px;
                                                text-align: center;
                                                margin-bottom: 24px;
                                            }
                                            .credit-amount {
                                                font-size: 2.5rem;
                                                font-weight: 700;
                                                margin-bottom: 8px;
                                            }
                                            .credit-label {
                                                font-size: 0.9rem;
                                                opacity: 0.9;
                                            }
                                            .plan-comparison-card {
                                                border: 2px solid #e2e8f0;
                                                border-radius: 12px;
                                                padding: 24px;
                                                height: 100%;
                                                transition: all 0.3s ease;
                                                position: relative;
                                            }
                                            .plan-comparison-card:hover {
                                                border-color: #667eea;
                                                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
                                                transform: translateY(-4px);
                                            }
                                            .plan-comparison-card.recommended {
                                                border-color: #667eea;
                                                border-width: 3px;
                                            }
                                            .plan-comparison-card.current-plan {
                                                background: #f8fafc;
                                                border-color: #10b981;
                                            }
                                            .recommended-badge {
                                                position: absolute;
                                                top: -12px;
                                                right: 20px;
                                                background: #667eea;
                                                color: white;
                                                padding: 4px 12px;
                                                border-radius: 12px;
                                                font-size: 0.75rem;
                                                font-weight: 600;
                                            }
                                            .plan-price {
                                                font-size: 2rem;
                                                font-weight: 700;
                                                color: #1e293b;
                                                margin: 16px 0;
                                            }
                                            .plan-features {
                                                list-style: none;
                                                padding: 0;
                                                margin: 20px 0;
                                            }
                                            .plan-features li {
                                                padding: 8px 0;
                                                display: flex;
                                                align-items: center;
                                            }
                                            .plan-features li i {
                                                color: #10b981;
                                                margin-right: 8px;
                                            }
                                            .quick-action-btn {
                                                border-radius: 8px;
                                                padding: 12px 24px;
                                                font-weight: 600;
                                                transition: all 0.3s ease;
                                                border: none;
                                                cursor: pointer;
                                                width: 100%;
                                            }
                                            .quick-action-btn:hover {
                                                transform: translateY(-2px);
                                                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                            }
                                        </style>
                                        
                                        <!-- Current Plan Overview -->
                                        <div class="row mb-4">
                                            <div class="col-lg-8">
                                                <div class="billing-card">
                                                    <div class="billing-card-header">
                                                        <i class="fas fa-crown"></i> Current Subscription
                                                    </div>
                                                    <div class="billing-card-body">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6">
                                                                <h5 class="mb-2">
                                                                    <span class="plan-badge {{ $subscription_plan }}">
                                                                        {{ ucfirst($subscription_plan) }} Plan
                                                                    </span>
                                                                </h5>
                                                                <p class="text-muted mb-2">
                                                                    Status: 
                                                                    <span class="plan-badge {{ $subscription_status }}">
                                                                        {{ ucfirst($subscription_status) }}
                                                                    </span>
                                                                </p>
                                                                @if($subscription_started_at)
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-calendar-alt"></i> 
                                                                    Started: {{ \Carbon\Carbon::parse($subscription_started_at)->format('M d, Y') }}
                                                                </p>
                                                                @endif
                                                                @if($subscription_expires_at)
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-calendar-check"></i> 
                                                                    @if($subscription_status === 'trial')
                                                                        Trial Expires: {{ \Carbon\Carbon::parse($subscription_expires_at)->format('M d, Y') }}
                                                                        <small>({{ \Carbon\Carbon::parse($subscription_expires_at)->diffInDays(now()) }} days left)</small>
                                                                    @else
                                                                        Next Billing: {{ \Carbon\Carbon::parse($subscription_expires_at)->format('M d, Y') }}
                                                                    @endif
                                                                </p>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-6 text-center">
                                                                @if($billing_account)
                                                                <div class="mt-3">
                                                                    <h6 class="text-muted">Plan Features</h6>
                                                                    <div class="row mt-2">
                                                                        <div class="col-6">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->max_contacts }}</strong>
                                                                                <small class="d-block text-muted">Contacts</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->max_products }}</strong>
                                                                                <small class="d-block text-muted">Products</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6 mt-2">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->whatsapp_channels }}</strong>
                                                                                <small class="d-block text-muted">WhatsApp Lines</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6 mt-2">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->customer_followups ? 'Yes' : 'No' }}</strong>
                                                                                <small class="d-block text-muted">Followups</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <!-- Credits Display -->
                                                <div class="credit-display">
                                                    <div class="credit-amount">{{ number_format($available_credits) }}</div>
                                                    <div class="credit-label">Available AI Credits</div>
                                                    <small class="d-block mt-2" style="opacity: 0.8;">1 Credit = 4 AI Tokens</small>
                                                    <a href="{{ route('billing.wallet') }}" class="btn btn-success btn-sm mt-3">
                                                        <i class="fas fa-wallet"></i> Top Up Wallet
                                                    </a>
                                                </div>
                                                
                                                <!-- Quick Actions -->
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="mb-3">Quick Actions</h6>
                                                        @if($subscription_plan !== 'premium')
                                                        <button class="quick-action-btn btn btn-primary mb-2" onclick="scrollToPlans()">
                                                            <i class="fas fa-arrow-up"></i> Upgrade Plan
                                                        </button>
                                                        @endif
                                                        <button class="quick-action-btn btn btn-info mb-2" onclick="showBillingHistoryModal()">
                                                            <i class="fas fa-history"></i> Billing History
                                                        </button>
                                                        @if($subscription_status === 'inactive' || $subscription_status === 'expired')
                                                        <button class="quick-action-btn btn btn-success" onclick="if(window.pricingControls) { window.pricingControls.showModal(null, 'Your subscription has expired. Please reactivate to continue.', true); } else { showUpgradeModal(null, 'Your subscription has expired. Please reactivate to continue.', true); }">
                                                            <i class="fas fa-redo"></i> Reactivate Now
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Available Plans -->
                                        <div id="availablePlansSection">
                                            <h5 class="mb-3"><i class="fas fa-box"></i> Available Packages</h5>
                                            <div class="row">
                                                @php
                                                    $planOrder = ['trial', 'starter', 'pro', 'premium'];
                                                    $currentPlanIndex = array_search($subscription_plan, $planOrder);
                                                @endphp
                                                
                                                @foreach($planOrder as $index => $planCode)
                                                    @if(isset($available_plans[$planCode]))
                                                        @php
                                                            $plan = $available_plans[$planCode];
                                                            $isCurrentPlan = $planCode === $subscription_plan;
                                                            $isRecommended = $planCode === 'pro';
                                                            $canUpgrade = $index > $currentPlanIndex;
                                                        @endphp
                                                        
                                                        <div class="col-lg-3 col-md-6 mb-4">
                                                            <div class="plan-comparison-card {{ $isCurrentPlan ? 'current-plan' : '' }} {{ $isRecommended ? 'recommended' : '' }}">
                                                                @if($isRecommended && !$isCurrentPlan)
                                                                <span class="recommended-badge">RECOMMENDED</span>
                                                                @endif
                                                                @if($isCurrentPlan)
                                                                <span class="recommended-badge" style="background: #10b981;">CURRENT</span>
                                                                @endif
                                                                
                                                                <h5 class="text-uppercase" style="color: #667eea; font-weight: 700;">
                                                                    {{ $planCode === 'trial' ? 'Free Trial' : ucfirst($planCode) }}
                                                                </h5>
                                                                
                                                                <div class="plan-price">
                                                                    TZS {{ number_format($plan['price']) }}
                                                                    <small class="text-muted" style="font-size: 0.5em;">/month</small>
                                                                </div>
                                                                
                                                                <p class="text-muted" style="font-size: 0.9rem; min-height: 40px;">
                                                                    {{ $plan['description'] ?? 'Perfect for getting started' }}
                                                                </p>
                                                                
                                                                <ul class="plan-features">
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['max_contacts'] }} Contacts</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['max_products'] }} Products</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['whatsapp_channels'] }} WhatsApp {{ $plan['limits']['whatsapp_channels'] > 1 ? 'Lines' : 'Line' }}</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ number_format($plan['price']) }} AI Credits</span>
                                                                    </li>
                                                                    @if(isset($plan['features']))
                                                                        @foreach(array_slice(array_keys(array_filter($plan['features'])), 0, 3) as $feature)
                                                                        <li>
                                                                            <i class="fas fa-check-circle"></i>
                                                                            <span>{{ ucfirst(str_replace('_', ' ', $feature)) }}</span>
                                                                        </li>
                                                                        @endforeach
                                                                    @endif
                                                                </ul>
                                                                
                                                                @if($isCurrentPlan)
                                                                <button class="btn btn-outline-success btn-block" disabled>
                                                                    <i class="fas fa-check"></i> Current Plan
                                                                </button>
                                                                @elseif($canUpgrade)
                                                                <button class="btn btn-primary btn-block" onclick="window.upgradeToPlan('{{ $planCode }}', {{ $plan['price'] }})">
                                                                    <i class="fas fa-arrow-up"></i> Upgrade Now
                                                                </button>
                                                                @else
                                                                <button class="btn btn-outline-secondary btn-block" disabled>
                                                                    Not Available
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                    <h4 class="mt-0 header-title">Business Settings</h4>
                                    <p class="text-muted mb-3">Configure your business information and preferences.</p>
                                    <p>Business settings are now managed through the dedicated Business Profile section.</p>
                                </div>
                                <div class="tab-pane fade" id="v-pills-business" role="tabpanel" aria-labelledby="v-pills-business-tab">
                                    <form class="form-parsley"  novalidate="" action="{{url('home/settings')}}" method="post">
                                        <div class="form-group">
                                            <label>Business Name</label>
                                            <input type="text" class="form-control" name="name" value="{{ $business->name ?? '' }}" placeholder="Business Name">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Email</label>
                                            <input type="email" class="form-control" name="email" value="{{ $business->email ?? '' }}" placeholder="Business Email">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Phone</label>
                                            <input type="text" class="form-control" name="phone" value="{{ $business->phone ?? '' }}" placeholder="Business Phone">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Description</label>
                                            <textarea class="form-control" name="descriptions" rows="4" placeholder="Describe your business">{{ $business->descriptions ?? '' }}</textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Website URL</label>
                                            <input type="url" class="form-control" name="website" value="{{ $business->website ?? '' }}" placeholder="https://example.com">
                                        </div>

                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-success waves-effect waves-light">
                                                Save Business Settings
                                            </button>
                                            <input type="hidden" value="business" name="table"/>
                                            <?= csrf_field() ?>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane fade " id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                    <h4 class="mt-0 header-title">Customer Categories</h4>
                                    <p class="text-muted mb-3">Manage list of Customer categories</p>
                                    <p>  <button type="button" class="btn btn-success" data-toggle="modal" data-target="#myModal">
                                            Add New Category
                                        </button></p>
                                    <!--<button type="button" class="btn btn-gradient-primary waves-effect waves-light" id="ajax-alert">Click me</button>-->
                                    <br/>
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Event Name</th>
                                                    <th>Customer Category</th>
                                                    <th>Total Customer</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($categories as $category) {
                                                    // Check if category has a business, fallback to 0 if no business
                                                    $total_guests = $category->business ? $category->business->businessGuests()->where('event_guest_category_id', $category->id)->count() : 0;
                                                    ?>
                                                    <tr>
                                                        <th scope="row"><?= $i ?></th>
                                                        <th><?= $category->business ? $category->business->name : 'Legacy Category' ?></th>
                                                        <td><span id="category<?= $category->id ?>"><?= $category->name ?></span></td>
                                                        <th><?= $total_guests ?></th>
                                                        <td> 
                                                            <a onclick="editGuest('<?= $category->id ?>')" data-toggle="modal" href="#myModal"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php
                                                            if ((int) $total_guests == 0) {
                                                                ?>
                                                                <a href="<?= url('guest/guestcategory/' . $category->id) ?>"><i class="las la-trash-alt text-danger font-18"></i> Delete</a>
                                                            <?php } else { ?>
                                                                <button type="button" class="btn btn-outline-light uitooltip" data-toggle="tooltip" data-placement="top" title="There are Customers in this category, You cannot delete. Delete first customers in this category if you want to delete it">
                                                                    <i class="las la-trash-alt text-danger font-18"></i> Delete
                                                                </button>
                                                            <?php } ?>

                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>
                                        </table><!--end /table-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!--end card-body-->
            </div><!--end card-->
        </div> <!-- end col -->
    </div> <!-- end row -->

</div>
<div class="modal fade planner-modal-bx" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Category Name</label>
                        <input type="text" name="name" id="edit_guest_name" class="form-control" placeholder="Customer Category Name" required="">
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="event_guest_category" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>

<div class="modal fade planner-modal-bx" id="user_accounts" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="<?= url('home/settings') ?>" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Edit your information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">


                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Name</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group1" value="<?= Auth::user()->name ?>" name="name" class="form-control" placeholder="Name">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Email</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->email ?>" name="email" class="form-control" placeholder="Email">
                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Phone</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->phone ?>" name="phone" class="form-control" placeholder="Phone">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">User UUID (for API access)</label>
                        <div class="input-group">
                            <input type="text" id="user-uuid" value="<?= Auth::user()->uuid ?>" class="form-control" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyUUID()" title="Copy UUID">
                                    <i class="las la-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Use this UUID with your phone number for CRM API authentication</small>                                                    
                    </div>


                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="user" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>
<!-- Sweet-Alert  -->
<!--<script src="../plugins/sweet-alert2/sweetalert2.min.js"></script>
<script src="../assets/pages/jquery.sweet-alert.init.js"></script>-->

<script type="text/javascript">
    function editGuest(a) {
        $('#edit_guest_name').val($('#category' + a).text());
        $('#edit_id').val(a);
    }
    
    function setCriteria(value) {
        return false;
    }
    
    // Subscription Management Functions
    // Subscription status is now handled by checkpayment.blade.php
    // All billing data is loaded server-side
    
    function showUpgradeModal(feature = null, message = null, isHardBlock = false) {
        // Use the global pricing modal from checkpayment.blade.php
        if (window.pricingControls) {
            window.pricingControls.showModal(feature, message, isHardBlock);
        } else {
            // Fallback to legacy modal
            $('#packageSelectionModal').modal('show');
        }
    }
    
    function showCreditTopup() {
        $('#creditTopupModal').modal('show');
    }
    
    function showBillingHistory() {
        $('#billingHistoryModal').modal('show');
        loadBillingHistory();
    }
    
    function selectPackage(packageType, price) {
        if (confirm(`Upgrade to ${packageType.toUpperCase()} package for $${price}/month?`)) {
            initiatePayment(packageType, price);
        }
    }
    
    function contactSales() {
        alert('Contact sales@shulesoft.africa for custom Enterprise pricing');
    }
    
    // Subscription status checking removed - now handled by checkpayment.blade.php
    // The billing data is already loaded server-side and available in the page
    
    function createPaywallModal(data) {
        const modalHTML = `
            <div class="modal fade" id="paywallModal" tabindex="-1" role="dialog" aria-labelledby="paywallModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="paywallModalLabel">
                                <i class="las la-exclamation-triangle"></i> Reactivate Your AI Sales Agent
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <h6><i class="las la-info-circle"></i> Your Credits Are Waiting!</h6>
                                <p>You still have <strong>${data.available_credits || 0} credits</strong> available.</p>
                            </div>
                            
                            ${data.missed_automations && data.missed_automations.length > 0 ? `
                                <div class="alert alert-warning">
                                    <h6><i class="las la-clock"></i> Missed Opportunities Today:</h6>
                                    <ul class="mb-0">
                                        ${data.missed_automations.map(item => `<li>${item.type}: ${item.target}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Choose Your Package:</h6>
                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <strong>Winga - $29/month</strong><br>
                                            50 contacts, 3 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('winga', 29)">Select</button>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Pro - $149/month</strong><br>
                                            150 contacts, 50 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('pro', 149)">Select</button>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Enterprise - $399/month</strong><br>
                                            300 contacts, 200 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('enterprise', 399)">Select</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Payment Method:</h6>
                                    ${data.country_code === 'TZ' ? `
                                        <div class="text-center">
                                            <h6>Lipa Namba Payment</h6>
                                            ${data.payment_options && data.payment_options.qr_code ? 
                                                `<img src="${data.payment_options.qr_code}" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">` : 
                                                '<div class="alert alert-info">QR code will be generated after package selection</div>'
                                            }
                                            ${data.payment_options && data.payment_options.merchant_id ? 
                                                `<p><strong>Lipa Namba:</strong> ${data.payment_options.merchant_id}</p>` : 
                                                ''
                                            }
                                        </div>
                                    ` : `
                                        <div class="text-center">
                                            <h6>International Payment</h6>
                                            <p>Secure payment via Stripe</p>
                                            <i class="las la-credit-card" style="font-size: 48px; color: #007bff;"></i>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" onclick="checkPaymentStatus()">
                                <i class="las la-sync"></i> Check Payment Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        $('#paywallModal').remove();
        $('body').append(modalHTML);
        $('#paywallModal').modal('show');
    }
    
    async function initiatePayment(packageType, price) {
        try {
            const response = await fetch('/subscription/initiate-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ package: packageType, amount: price })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.gateway === 'stripe') {
                    window.location.href = result.checkout_url;
                } else {
                    // Update Lipa Number details in modal
                    updateLipaNumberDetails(result.merchant_id, result.qr_code);
                }
            } else {
                alert('Payment initiation failed: ' + result.message);
            }
        } catch (error) {
            console.error('Payment initiation error:', error);
            alert('Failed to initiate payment. Please try again.');
        }
    }
    
    function updateLipaNumberDetails(merchantId, qrCode) {
        // Update the modal with new payment details
        const paymentSection = document.querySelector('#paywallModal .col-md-6:last-child');
        if (paymentSection) {
            paymentSection.innerHTML = `
                <h6>Payment Method:</h6>
                <div class="text-center">
                    <h6>Lipa Namba Payment</h6>
                    <img src="${qrCode}" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                    <p><strong>Lipa Namba:</strong> ${merchantId}</p>
                    <div class="alert alert-info">
                        <small>Scan QR code or use Lipa Namba number above to complete payment</small>
                    </div>
                </div>
            `;
        }
    }
    
    async function checkPaymentStatus() {
        const btn = document.querySelector('[onclick="checkPaymentStatus()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> Checking...';
        btn.disabled = true;
        
        try {
            const response = await fetch('/subscription/check-payment-status', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#paywallModal').modal('hide');
                location.reload(); // Refresh page to show updated subscription
            } else {
                alert('Payment not yet received. Please complete payment and try again.');
            }
        } catch (error) {
            console.error('Payment check error:', error);
            alert('Failed to check payment status.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    async function loadBillingHistory() {
        try {
            // For now, show a placeholder since we don't have payment history table yet
            const historyHTML = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    <p>No billing history yet</p>
                                    <small>Your payment transactions will appear here</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('billingHistoryContent').innerHTML = historyHTML;
        } catch (error) {
            console.error('Error loading billing history:', error);
            document.getElementById('billingHistoryContent').innerHTML = '<div class="alert alert-danger">Failed to load billing history</div>';
        }
    }
    
    function showBillingHistoryModal() {
        $('#billingHistoryModal').modal('show');
        loadBillingHistory();
    }
    
    function scrollToPlans() {
        document.getElementById('availablePlansSection').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
    
    // Subscription status checking now handled by checkpayment.blade.php
    // The modal will auto-show on page load if subscription is expired/inactive
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Settings page loaded with subscription status:', '{{ $subscription_status }}');
    });
</script>

<!-- Subscription Modals -->
<div class="modal fade" id="billingHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Billing History</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="billingHistoryContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="creditTopupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buy Credits</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Purchase additional credits for AI conversations</p>
                <div class="form-group">
                    <label>Credit Amount</label>
                    <select class="form-control" id="creditAmount">
                        <option value="100">100 Credits - $25</option>
                        <option value="500">500 Credits - $100</option>
                        <option value="1000">1000 Credits - $180</option>
                        <option value="2000">2000 Credits - $320</option>
                    </select>
                </div>
                <p class="text-muted"><small>1 Credit = 4 AI Tokens</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="buyCreditPackage()">Purchase Credits</button>
            </div>
        </div>
    </div>
</div>

<script>
function buyCreditPackage() {
    const amount = document.getElementById('creditAmount').value;
    const prices = { 100: 25, 500: 100, 1000: 180, 2000: 320 };
    const price = prices[amount];
    
    if (confirm(`Purchase ${amount} credits for $${price}?`)) {
        initiatePayment('credit_topup', price);
    }
}

function copyUUID() {
    const uuidField = document.getElementById('user-uuid');
    uuidField.select();
    uuidField.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        // Show success message
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="las la-check"></i>';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    } catch (err) {
        alert('Unable to copy UUID. Please select and copy manually.');
    }
}
</script>
@endsection