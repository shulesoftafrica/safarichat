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

/* ========== BILLING & SUBSCRIPTION SECTION LIGHT MODE STYLES ========== */

/* Light Mode - Ensure billing card text is visible */
body:not(.dark-mode) .billing-card-body h5,
body:not(.dark-mode) .billing-card-body p,
body:not(.dark-mode) .billing-card-body h6 {
    color: #1e293b !important;
}

body:not(.dark-mode) .billing-card-body .border.rounded strong {
    color: #1e293b !important;
    font-size: 1.5rem !important;
    font-weight: 700 !important;
}

body:not(.dark-mode) .billing-card-body .border.rounded small.text-muted {
    color: #64748b !important;
}

body:not(.dark-mode) .billing-card-body .text-muted {
    color: #64748b !important;
}

body:not(.dark-mode) .credit-display {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

body:not(.dark-mode) .credit-display .credit-amount {
    color: white !important;
}

body:not(.dark-mode) .credit-display .credit-label {
    color: white !important;
}

body:not(.dark-mode) .credit-display small {
    color: rgba(255, 255, 255, 0.9) !important;
}

body:not(.dark-mode) .billing-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

/* ========== BILLING & SUBSCRIPTION SECTION DARK MODE STYLES ========== */

/* Billing Card Container */
.dark-mode .billing-card {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
    border: 1px solid #4a5568 !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    margin-bottom: 20px !important;
}

.dark-mode .billing-card-header {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    color: #f7fafc !important;
    font-weight: 600 !important;
    padding: 16px 20px !important;
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 2px solid #2c5282 !important;
}

.dark-mode .billing-card-header i {
    color: #ffd700 !important;
    margin-right: 8px !important;
}

.dark-mode .billing-card-body {
    background-color: #2d3748 !important;
    color: #f7fafc !important;
    padding: 20px !important;
}

/* Plan Badges - Tier-based Styling */
.dark-mode .plan-badge {
    display: inline-block !important;
    padding: 8px 16px !important;
    border-radius: 20px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
    margin: 4px !important;
}

/* Plan Tier Colors */
.dark-mode .plan-badge.trial {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #a0aec0 !important;
}

.dark-mode .plan-badge.starter {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #22d3ee !important;
}

.dark-mode .plan-badge.pro {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #63b3ed !important;
}

.dark-mode .plan-badge.premium {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #fbbf24 !important;
}

/* Status Badge Colors */
.dark-mode .plan-badge.active {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #68d391 !important;
}

.dark-mode .plan-badge.inactive,
.dark-mode .plan-badge.expired {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%) !important;
    color: #f7fafc !important;
    border: 1px solid #fc8181 !important;
}

/* Feature Grid Boxes */
.dark-mode .billing-card-body .border.rounded {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
    border: 2px solid #4b5563 !important;
    border-radius: 10px !important;
    padding: 16px 12px !important;
    transition: all 0.3s ease !important;
}

.dark-mode .billing-card-body .border.rounded:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
    border-color: #4299e1 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3) !important;
}

.dark-mode .billing-card-body .border.rounded strong {
    display: block !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #f7fafc !important;
    margin-bottom: 4px !important;
}

.dark-mode .billing-card-body .border.rounded small,
.dark-mode .billing-card-body .border.rounded .text-muted {
    color: #cbd5e0 !important;
    font-size: 12px !important;
    font-weight: 500 !important;
}

/* Credit Display Section */
.dark-mode .credit-display {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
    border: 2px solid #4a5568 !important;
    border-radius: 12px !important;
    padding: 24px !important;
    text-align: center !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    margin-top: 20px !important;
}

.dark-mode .credit-amount {
    font-size: 36px !important;
    font-weight: 700 !important;
    color: #f7fafc !important;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
    margin-bottom: 8px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}

.dark-mode .credit-label {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #cbd5e0 !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
    margin-bottom: 16px !important;
}

/* Top Up Wallet Button */
.dark-mode .credit-display .btn-sm.btn-primary,
.dark-mode .btn-sm.btn-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    border: none !important;
    color: #f7fafc !important;
    padding: 10px 20px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 8px rgba(66, 153, 225, 0.3) !important;
    transition: all 0.3s ease !important;
}

.dark-mode .credit-display .btn-sm.btn-primary:hover,
.dark-mode .btn-sm.btn-primary:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 12px rgba(66, 153, 225, 0.4) !important;
}

.dark-mode .credit-display .btn-sm.btn-primary i,
.dark-mode .btn-sm.btn-primary i {
    margin-right: 6px !important;
}

/* Quick Actions Panel */
.dark-mode .quick-actions {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
    border: 1px solid #4a5568 !important;
    border-radius: 12px !important;
    padding: 20px !important;
    margin-top: 20px !important;
}

.dark-mode .quick-actions h5 {
    color: #f7fafc !important;
    font-weight: 600 !important;
    margin-bottom: 16px !important;
}

/* Fix text color in billing section */
.dark-mode .billing-card-body h5,
.dark-mode .billing-card-body p {
    color: #f7fafc !important;
}

.dark-mode .billing-card-body .row .col-6 {
    margin-bottom: 12px !important;
}
</style>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __("settings.breadcrumb.home") }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __("settings.breadcrumb.profile") }}</a></li>
                        <li class="breadcrumb-item active">{{ __("settings.breadcrumb.settings") }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ __("settings.page_title.general_settings") }}</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">{{ __("settings.page_header.list_of_items") }}</h4>
                    <p class="text-muted mb-3">{{ __("settings.page_header.settings_description") }}
                    </p>


                    <div class="row">
                        <div class="col-sm-3">
                            <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <a class="nav-link waves-effect waves-light active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">{{ __("settings.tabs.user_accounts") }}</a>
                                <a class="nav-link waves-effect waves-light " id="v-pills-subscription-tab" data-toggle="pill" href="#v-pills-subscription" role="tab" aria-controls="v-pills-subscription" aria-selected="false">{{ __("settings.tabs.subscription_billing") }}</a>
                                <!-- <a class="nav-link waves-effect waves-light " id="v-pills-settings-tab" data-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings" aria-selected="false">Customer Category</a> -->
                                <a class="nav-link waves-effect waves-light " id="v-pills-business-tab" data-toggle="pill" href="#v-pills-business" role="tab" aria-controls="v-pills-business" aria-selected="false">{{ __("settings.tabs.business_settings") }}</a>
                            </div>
                        </div>
                        <div class="col-sm-9">
                            <div class="tab-content mo-mt-2" id="v-pills-tabContent">
                                <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="table-responsive">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="mt-0 header-title mb-1">{{ __("settings.user_accounts.title") }}</h4>
                                                <p class="text-muted mb-0">{{ __("settings.user_accounts.description") }}</p>
                                                <small class="text-muted">Users: {{ $current_user_count }} / {{ $max_users }}</small>
                                            </div>
                                            @if($current_user_count < $max_users)
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                                                    <i class="las la-plus"></i> Add New User
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-warning" onclick="showUpgradeModal('team_members', 'You have reached the maximum number of team members ({{ $max_users }}) for your {{ ucfirst($subscription_plan) }} plan. Upgrade to add more users.', false)">
                                                    <i class="las la-arrow-up"></i> Upgrade to Add More
                                                </button>
                                            @endif
                                        </div>
                                        <table class="table-standard mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>{{ __("settings.user_accounts.table.hash") }}</th>
                                                    <th>{{ __("settings.user_accounts.table.name") }}</th>
                                                    <th>{{ __("settings.user_accounts.table.email") }}</th>
                                                    <th>{{ __("settings.user_accounts.table.phone") }}</th>
                                                    <th>Role</th>
                                                    <th>{{ __("settings.user_accounts.table.date_registered") }}</th>
                                                    <th>{{ __("settings.user_accounts.table.action") }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($user_accounts as $account) {
                                                    $isOwner = $account->user->id == $business->user_id;
                                                    $isCurrentUser = $account->user->id == Auth::user()->id;
                                                    $userRole = $isOwner ? 'Owner' : ucfirst($account->user->role ?? 'Member');
                                                    ?>
                                                    <tr>
                                                        <th><?= $i ?></th>
                                                        <th>
                                                            <?= $account->user->name ?>
                                                            @if($isOwner)
                                                                <span class="badge badge-success ml-1">Owner</span>
                                                            @endif
                                                        </th>
                                                        <th><?= $account->user->email ?></th>
                                                        <th><?= $account->user->phone ?></th>
                                                        <th><?= $userRole ?></th>
                                                        <th><?= date('d M Y', strtotime($account->user->created_at)) ?></th>
                                                        <th>
                                                            @if($isCurrentUser)
                                                                <a onclick="editGuest('<?= $account->user->id ?>')" data-toggle="modal" href="#user_accounts">
                                                                    <i class="las la-pen text-info font-18"></i> {{ __("settings.user_accounts.action.edit") }}
                                                                </a>
                                                            @elseif(!$isOwner)
                                                                <a href="javascript:void(0)" onclick="deleteUser(<?= $account->user->id ?>, '<?= $account->user->name ?>')">
                                                                    <i class="las la-trash-alt text-danger font-18"></i> Delete
                                                                </a>
                                                            @endif
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
                                        <h4 class="mt-0 header-title mb-1">{{ __("settings.subscription.title") }}</h4>
                                        <p class="text-muted mb-4">{{ __("settings.subscription.description") }}</p>
                                        
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
                                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                                                        <i class="fas fa-crown"></i> {{ __("settings.subscription.current_subscription") }}
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
                                                                    {{ __("settings.subscription.status_label") }} 
                                                                    <span class="plan-badge {{ $subscription_status }}">
                                                                        {{ ucfirst(__("settings.status." . $subscription_status)) }}
                                                                    </span>
                                                                </p>
                                                                @if($subscription_started_at)
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-calendar-alt"></i> 
                                                                    {{ __("settings.subscription.started_label") }} {{ \Carbon\Carbon::parse($subscription_started_at)->format('M d, Y') }}
                                                                </p>
                                                                @endif
                                                                @if($subscription_expires_at)
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-calendar-check"></i> 
                                                                    @if($subscription_status === 'trial')
                                                                        {{ __("settings.subscription.trial_expires") }} {{ \Carbon\Carbon::parse($subscription_expires_at)->format('M d, Y') }}
                                                                        <small>({{ \Carbon\Carbon::parse($subscription_expires_at)->diffInDays(now()) }} {{ __("settings.subscription.days_left") }})</small>
                                                                    @else
                                                                        {{ __("settings.subscription.next_billing") }} {{ \Carbon\Carbon::parse($subscription_expires_at)->format('M d, Y') }}
                                                                    @endif
                                                                </p>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-6 text-center">
                                                                @if($billing_account)
                                                                <div class="mt-3">
                                                                    <h6 class="text-muted">{{ __("settings.subscription.plan_features") }}</h6>
                                                                    <div class="row mt-2">
                                                                        <div class="col-6">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->max_contacts }}</strong>
                                                                                <small class="d-block text-muted">{{ __("settings.subscription.contacts") }}</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->max_products }}</strong>
                                                                                <small class="d-block text-muted">{{ __("settings.subscription.products") }}</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6 mt-2">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->whatsapp_channels }}</strong>
                                                                                <small class="d-block text-muted">{{ __("settings.subscription.whatsapp_lines") }}</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6 mt-2">
                                                                            <div class="text-center p-2 border rounded">
                                                                                <strong>{{ $billing_account->customer_followups ? __("settings.subscription.yes") : __("settings.subscription.no") }}</strong>
                                                                                <small class="d-block text-muted">{{ __("settings.subscription.followups") }}</small>
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
                                                    <div class="credit-label">{{ __("settings.credits.available_credits") }}</div>
                                                    <small class="d-block mt-2" style="opacity: 0.8;">{{ __("settings.credits.conversion_rate") }}</small>
                                                    <a href="{{ route('billing.wallet') }}" class="btn-sm btn-primary mt-3">
                                                        <i class="fas fa-wallet"></i> {{ __("settings.credits.top_up_wallet") }}
                                                    </a>
                                                </div>
                                                
                                                <!-- Quick Actions -->
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="mb-3">{{ __("settings.quick_actions.title") }}</h6>
                                                        @if($subscription_plan !== 'premium')
                                                        <button class="quick-action-btn btn btn-primary mb-2" onclick="scrollToPlans()">
                                                            <i class="fas fa-arrow-up"></i> {{ __("settings.quick_actions.upgrade_plan") }}
                                                        </button>
                                                        @endif
                                                        <button class="quick-action-btn btn-secondary mb-2" onclick="showBillingHistoryModal()">
                                                            <i class="fas fa-history"></i> {{ __("settings.quick_actions.billing_history") }}
                                                        </button>
                                                        @if($subscription_status === 'inactive' || $subscription_status === 'expired')
                                                        <button class="quick-action-btn btn-primary" onclick="if(window.pricingControls) { window.pricingControls.showModal(null, 'Your subscription has expired. Please reactivate to continue.', true); } else { showUpgradeModal(null, 'Your subscription has expired. Please reactivate to continue.', true); }">
                                                            <i class="fas fa-redo"></i> {{ __("settings.quick_actions.reactivate_now") }}
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Available Plans -->
                                        <div id="availablePlansSection">
                                            <h5 class="mb-3"><i class="fas fa-box"></i> {{ __("settings.packages.title") }}</h5>
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
                                                                <span class="recommended-badge">{{ __("settings.packages.recommended") }}</span>
                                                                @endif
                                                                @if($isCurrentPlan)
                                                                <span class="recommended-badge" style="background: #10b981;">{{ __("settings.packages.current") }}</span>
                                                                @endif
                                                                
                                                                <h5 class="text-uppercase" style="color: #667eea; font-weight: 700;">
                                                                    {{ $planCode === 'trial' ? __("settings.packages.free_trial") : ucfirst(__("settings.plan." . $planCode)) }}
                                                                </h5>
                                                                
                                                                <div class="plan-price">
                                                                    TZS {{ number_format($plan['price']) }}
                                                                    <small class="text-muted" style="font-size: 0.5em;">{{ __("settings.packages.per_month") }}</small>
                                                                </div>
                                                                
                                                                <p class="text-muted" style="font-size: 0.9rem; min-height: 40px;">
                                                                    {{ $plan['description'] ?? __("settings.packages.description_default") }}
                                                                </p>
                                                                
                                                                <ul class="plan-features">
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['max_contacts'] }} {{ __("settings.subscription.contacts") }}</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['max_products'] }} {{ __("settings.subscription.products") }}</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ $plan['limits']['whatsapp_channels'] }} WhatsApp {{ $plan['limits']['whatsapp_channels'] > 1 ? __("settings.packages.line_plural") : __("settings.packages.line_singular") }}</span>
                                                                    </li>
                                                                    <li>
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ number_format($plan['price']) }} {{ __("settings.packages.ai_credits") }}</span>
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
                                                                    <i class="fas fa-check"></i> {{ __("settings.packages.current_plan_button") }}
                                                                </button>
                                                                @elseif($canUpgrade)
                                                                <button class="btn btn-primary btn-block" onclick="window.upgradeToPlan('{{ $planCode }}', {{ $plan['price'] }})">
                                                                    <i class="fas fa-arrow-up"></i> {{ __("settings.packages.upgrade_now") }}
                                                                </button>
                                                                @else
                                                                <button class="btn btn-outline-secondary btn-block" disabled>
                                                                    {{ __("settings.packages.not_available") }}
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
                                            <label>{{ __("settings.business.form.name_label") }}</label>
                                            <input type="text" class="form-control" name="name" value="{{ $business->name ?? '' }}" placeholder="{{ __("settings.business.form.name_placeholder") }}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>{{ __("settings.business.form.email_label") }}</label>
                                            <input type="email" class="form-control" name="email" value="{{ $business->email ?? '' }}" placeholder="{{ __("settings.business.form.email_placeholder") }}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>{{ __("settings.business.form.phone_label") }}</label>
                                            <input type="tel" class="form-control phone-validation" name="phone" 
                                                   value="{{ $business->phone ?? '' }}" 
                                                   pattern="^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$"
                                                   title="Phone format: +1234567890 or (123) 456-7890"
                                                   placeholder="{{ __("settings.business.form.phone_placeholder") }}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>{{ __("settings.business.form.description_label") }}</label>
                                            <textarea class="form-control" name="descriptions" rows="4" placeholder="{{ __("settings.business.form.description_placeholder") }}">{{ $business->descriptions ?? '' }}</textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>{{ __("settings.business.form.website_label") }}</label>
                                            <input type="url" class="form-control" name="website" value="{{ $business->website ?? '' }}" placeholder="{{ __("settings.business.form.website_placeholder") }}">
                                        </div>

                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn-primary">
                                                {{ __("settings.business.form.save_button") }}
                                            </button>
                                            <input type="hidden" value="business" name="table"/>
                                            <?= csrf_field() ?>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane fade " id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                    <h4 class="mt-0 header-title">Customer Categories</h4>
                                    <p class="text-muted mb-3">Manage list of Customer categories</p>
                                    <p>  <button type="button" class="btn-primary" data-toggle="modal" data-target="#myModal">
                                            Add New Category
                                        </button></p>
                                    <!--<button type="button" class="btn btn-gradient-primary waves-effect waves-light" id="ajax-alert">Click me</button>-->
                                    <br/>
                                    <div class="table-responsive">
                                        <table class="table-standard mb-0">
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
                <button type="button" class="btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn-primary" data-toggle="tooltip" data-placement="top">Save</button>
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
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __("settings.modal.user.title") }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">


                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{ __("settings.modal.user.name_label") }}</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group1" value="<?= Auth::user()->name ?>" name="name" class="form-control" placeholder="{{ __("settings.modal.user.name_placeholder") }}">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{ __("settings.modal.user.email_label") }}</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->email ?>" name="email" class="form-control" placeholder="{{ __("settings.modal.user.email_placeholder") }}">
                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{ __("settings.modal.user.phone_label") }}</label>
                        <div class="input-group">
                            <input type="tel" id="example-input2-group2" value="<?= Auth::user()->phone ?>" name="phone" 
                                   class="form-control phone-validation" 
                                   pattern="^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$"
                                   title="Phone format: +1234567890 or (123) 456-7890"
                                   placeholder="{{ __("settings.modal.user.phone_placeholder") }}">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{ __("settings.modal.user.uuid_label") }}</label>
                        <div class="input-group">
                            <input type="text" id="user-uuid" value="<?= Auth::user()->uuid ?>" class="form-control" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyUUID()" title="Copy UUID">
                                    <i class="las la-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">{{ __("settings.modal.user.uuid_help") }}</small>                                                    
                    </div>


                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="user" name="table"/>
                <button type="button" class="btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn-primary" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>

<!-- Add New User Modal -->
<div class="modal fade planner-modal-bx" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" action="<?= url('home/settings') ?>" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0">Add New Team Member</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_user_name" class="col-form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="new_user_name" name="name" class="form-control" placeholder="Enter full name" required="">
                    </div>

                    <div class="form-group">
                        <label for="new_user_email" class="col-form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="new_user_email" name="email" class="form-control" placeholder="email@example.com" required="">
                    </div>

                    <div class="form-group">
                        <label for="new_user_phone" class="col-form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" id="new_user_phone" name="phone" 
                               class="form-control phone-validation" 
                               pattern="^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$"
                               title="Phone format: +1234567890 or (123) 456-7890"
                               placeholder="+255XXXXXXXXX" required="">
                    </div>

                    <div class="form-group">
                        <label for="new_user_password" class="col-form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" id="new_user_password" name="password" class="form-control" placeholder="Minimum 6 characters" required="" minlength="6">
                        <small class="text-muted">User will use this password to login</small>
                    </div>
                </div>

                <div class="modal-footer text-center">
                    <?= csrf_field() ?>
                    <input type="hidden" name="table" value="add_user"/>
                    <button type="button" class="btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary">Add User</button>
                </div>
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
    
    function deleteUser(userId, userName) {
        if (confirm('Are you sure you want to delete ' + userName + '? This action cannot be undone.')) {
            // Create form and submit
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('home/settings') ?>';
            
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '<?= csrf_token() ?>';
            form.appendChild(csrfInput);
            
            var tableInput = document.createElement('input');
            tableInput.type = 'hidden';
            tableInput.name = 'table';
            tableInput.value = 'delete_user';
            form.appendChild(tableInput);
            
            var userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            form.appendChild(userIdInput);
            
            document.body.appendChild(form);
            form.submit();
        }
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
        alert('{{ __("settings.js.alert.contact_sales") }}');
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
                            <button type="button" class="btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn-primary" onclick="checkPaymentStatus()">
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
                alert('{{ __("settings.js.payment.initiation_failed") }} ' + result.message);
            }
        } catch (error) {
            console.error('Payment initiation error:', error);
            alert('{{ __("settings.js.payment.failed_generic") }}');
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
                alert('{{ __("settings.js.payment.not_received") }}');
            }
        } catch (error) {
            console.error('Payment check error:', error);
            alert('{{ __("settings.js.payment.check_failed") }}');
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
                    <table class="table-standard">
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
                <button type="button" class="btn-primary" onclick="buyCreditPackage()">Purchase Credits</button>
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
        alert('{{ __("settings.js.uuid.copy_failed") }}');
    }
}
</script>
@endsection