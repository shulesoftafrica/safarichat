@extends('layouts.app')

@section('content')
<style>
/* Enhanced Typography */
.page-title {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
}

.page-title-box p {
    font-size: 0.95rem !important;
}

/* Dark Mode - Page Header */
.dark-mode .page-title {
    color: #f7fafc !important;
}

.dark-mode .page-title i {
    color: #63b3ed !important;
}

.dark-mode .page-title-box p {
    color: #cbd5e0 !important;
}

/* Dark Mode - Cards */
.dark-mode .card {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
}

.dark-mode .card.shadow-sm {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
}

.dark-mode .card.border-success {
    border-color: #48bb78 !important;
    border-width: 2px !important;
}

.dark-mode .card.border-warning {
    border-color: #ed8936 !important;
    border-width: 2px !important;
}

.dark-mode .card.border-primary {
    border-color: #4299e1 !important;
    border-width: 2px !important;
}

.dark-mode .card.border-secondary {
    border-color: #718096 !important;
    border-width: 2px !important;
}

/* Dark Mode - Override Bootstrap bg classes */
.dark-mode .bg-white {
    background-color: #2d3748 !important;
}

.dark-mode .bg-light {
    background-color: #1a202c !important;
}

/* Dark Mode - Card Headers */
.dark-mode .card-header {
    background: #1a202c !important;
    border-bottom-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .card-header.bg-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    color: #ffffff !important;
}

.dark-mode .card-header.bg-secondary {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;
    color: #f7fafc !important;
}

.dark-mode .card-header h5,
.dark-mode .card-header h6 {
    color: #ffffff !important;
    font-size: 1.125rem !important;
    font-weight: 600 !important;
}

.dark-mode .card-header small {
    color: rgba(255, 255, 255, 0.85) !important;
    font-size: 0.875rem !important;
}

/* Dark Mode - Card Body */
.dark-mode .card-body {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .card-body p {
    color: #e2e8f0 !important;
    font-size: 0.95rem !important;
    line-height: 1.6 !important;
}

.dark-mode .card-body p.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .card-body small {
    color: #cbd5e0 !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
}

.dark-mode .card-body small.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .card-body strong {
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 1rem !important;
}

.dark-mode .card-body h6 {
    color: #f7fafc !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
}

.dark-mode .card-body h4 {
    color: #f7fafc !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
}

/* Dark Mode - Card Footer */
.dark-mode .card-footer {
    background: #1a202c !important;
    border-top-color: #4a5568 !important;
}

.dark-mode .card-footer.bg-light {
    background: #1a202c !important;
}

.dark-mode .card-footer small {
    color: #cbd5e0 !important;
    font-size: 0.875rem !important;
}

/* Dark Mode - Badges */
.dark-mode .badge {
    font-weight: 500 !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.875rem !important;
}

.dark-mode .badge-soft-success {
    background: rgba(72, 187, 120, 0.25) !important;
    color: #68d391 !important;
}

.dark-mode .badge-soft-warning {
    background: rgba(237, 137, 54, 0.25) !important;
    color: #f6ad55 !important;
}

.dark-mode .badge-soft-secondary {
    background: rgba(113, 128, 150, 0.25) !important;
    color: #cbd5e0 !important;
}

.dark-mode .badge-light {
    background: rgba(237, 242, 247, 0.2) !important;
    color: #e2e8f0 !important;
    border: 1px solid rgba(203, 213, 224, 0.3) !important;
}

/* Dark Mode - Progress Bars */
.dark-mode .progress {
    background: #1a202c !important;
    border: 1px solid #4a5568 !important;
}

.dark-mode .progress-bar.bg-success {
    background: linear-gradient(90deg, #48bb78 0%, #38a169 100%) !important;
}

.dark-mode .progress-bar.bg-warning {
    background: linear-gradient(90deg, #ed8936 0%, #dd6b20 100%) !important;
}

/* Dark Mode - Text Colors */
.dark-mode .text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .text-warning {
    color: #f6ad55 !important;
    font-weight: 500 !important;
}

.dark-mode .text-success {
    color: #68d391 !important;
}

.dark-mode h4.text-muted {
    color: #cbd5e0 !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
}

.dark-mode .fa-4x {
    color: #718096 !important;
}

/* Dark Mode - Alerts */
.dark-mode .alert {
    border-width: 1px !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
}

.dark-mode .alert-success {
    background: rgba(72, 187, 120, 0.15) !important;
    border-color: #48bb78 !important;
    color: #9ae6b4 !important;
}

.dark-mode .alert-danger {
    background: rgba(245, 101, 101, 0.15) !important;
    border-color: #f56565 !important;
    color: #fc8181 !important;
}

.dark-mode .alert-warning {
    background: rgba(237, 137, 54, 0.2) !important;
    border-color: #ed8936 !important;
    color: #fbd38d !important;
}

.dark-mode .alert-info {
    background: rgba(66, 153, 225, 0.15) !important;
    border-color: #4299e1 !important;
    color: #90cdf4 !important;
}

.dark-mode .alert i {
    color: inherit !important;
}

/* Dark Mode - Horizontal Rules */
.dark-mode hr {
    border-top-color: #4a5568 !important;
    opacity: 1 !important;
}

/* Dark Mode - Button Groups */
.dark-mode .btn-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%) !important;
    border-color: #3182ce !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    box-shadow: 0 2px 6px rgba(66, 153, 225, 0.3) !important;
}

.dark-mode .btn-primary:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%) !important;
    border-color: #2c5282 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.5) !important;
}

.dark-mode .btn-primary:focus,
.dark-mode .btn-primary:active {
    background: linear-gradient(135deg, #2c5282 0%, #2a4365 100%) !important;
    border-color: #2a4365 !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 0.2rem rgba(66, 153, 225, 0.5) !important;
}

.dark-mode .btn-secondary {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;
    border-color: #4a5568 !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    box-shadow: 0 2px 6px rgba(74, 85, 104, 0.3) !important;
}

.dark-mode .btn-secondary:hover {
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;
    border-color: #2d3748 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(74, 85, 104, 0.5) !important;
}

.dark-mode .btn-secondary:focus,
.dark-mode .btn-secondary:active {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
    border-color: #1a202c !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 0.2rem rgba(74, 85, 104, 0.5) !important;
}

.dark-mode .btn-sm.btn-secondary {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;
    border-color: #4a5568 !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    padding: 0.375rem 0.75rem !important;
    box-shadow: 0 2px 4px rgba(74, 85, 104, 0.3) !important;
}

.dark-mode .btn-sm.btn-secondary:hover {
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;
    border-color: #2d3748 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(74, 85, 104, 0.5) !important;
}

.dark-mode .btn-warning {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%) !important;
    border-color: #dd6b20 !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    box-shadow: 0 2px 6px rgba(237, 137, 54, 0.3) !important;
}

.dark-mode .btn-warning:hover {
    background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%) !important;
    border-color: #c05621 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(237, 137, 54, 0.5) !important;
}

.dark-mode .btn-sm.btn-warning {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%) !important;
    border-color: #dd6b20 !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    box-shadow: 0 2px 4px rgba(237, 137, 54, 0.3) !important;
}

.dark-mode .btn-outline-primary {
    color: #63b3ed !important;
    border-color: #4299e1 !important;
    background: transparent !important;
}

.dark-mode .btn-outline-primary:hover {
    background: #4299e1 !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-info {
    color: #76e4f7 !important;
    border-color: #0bc5ea !important;
    background: transparent !important;
}

.dark-mode .btn-outline-info:hover {
    background: #0bc5ea !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-danger {
    color: #fc8181 !important;
    border-color: #f56565 !important;
    background: transparent !important;
}

.dark-mode .btn-outline-danger:hover {
    background: #f56565 !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-light {
    color: #e2e8f0 !important;
    border-color: #cbd5e0 !important;
    background: rgba(226, 232, 240, 0.1) !important;
}

.dark-mode .btn-outline-light:hover {
    background: rgba(226, 232, 240, 0.2) !important;
    color: #f7fafc !important;
}

.dark-mode .btn-sm.btn-light {
    background: rgba(237, 242, 247, 0.2) !important;
    color: #2d3748 !important;
    border-color: rgba(203, 213, 224, 0.3) !important;
}

.dark-mode .btn-sm.btn-outline-light {
    color: #f7fafc !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
}

/* Dark Mode - Icons */
.dark-mode .card-body i.fas,
.dark-mode .card-body i.far {
    color: #90cdf4 !important;
}

.dark-mode .card-body i.text-success {
    color: #68d391 !important;
}

.dark-mode i.fas,
.dark-mode i.far {
    color: #90cdf4 !important;
}

/* Dark Mode - Button Icons */
.dark-mode .btn i.fas,
.dark-mode .btn i.far {
    color: inherit !important;
}

.dark-mode .btn-primary i,
.dark-mode .btn-secondary i,
.dark-mode .btn-warning i,
.dark-mode .btn-danger i {
    color: #ffffff !important;
}

/* General Button Improvements */
.btn {
    transition: all 0.2s ease !important;
}

.btn i {
    transition: all 0.2s ease !important;
}

/* Dark Mode - Modal */
.dark-mode .modal-content {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
}

.dark-mode .modal-header {
    background: #1a202c !important;
    border-bottom-color: #4a5568 !important;
}

.dark-mode .modal-header.bg-danger {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%) !important;
}

.dark-mode .modal-title {
    color: #ffffff !important;
    font-size: 1.125rem !important;
    font-weight: 600 !important;
}

.dark-mode .modal-body {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-body p {
    color: #e2e8f0 !important;
    font-size: 1rem !important;
}

.dark-mode .modal-footer {
    background: #2d3748 !important;
    border-top-color: #4a5568 !important;
}

.dark-mode .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
}

/* Dark Mode - Empty State */
.dark-mode .card-body.text-center {
    padding: 4rem 2rem !important;
}

.dark-mode .card-body.text-center p {
    font-size: 1rem !important;
    line-height: 1.6 !important;
}

/* Enhanced Spacing */
.card-body .row.mb-3 {
    margin-bottom: 1.25rem !important;
}

.card-body .row.mb-3 p {
    margin-bottom: 0.25rem !important;
}

/* Improved readability for nested content */
.dark-mode .d-flex strong {
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .opacity-75 {
    opacity: 0.9 !important;
}

/* Dark Mode - All divs inside cards */
.dark-mode .card div {
    color: #e2e8f0 !important;
}

/* Dark Mode - Force text visibility */
.dark-mode .col-6 p,
.dark-mode .col-6 strong,
.dark-mode .col-6 small {
    color: #e2e8f0 !important;
}

.dark-mode .col-6 small.text-muted {
    color: #cbd5e0 !important;
}

/* Dark Mode - Ensure all paragraph text is visible */
.dark-mode p.mb-0 {
    color: #e2e8f0 !important;
}

.dark-mode p.mb-0 strong {
    color: #ffffff !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-calendar-alt text-primary mr-2"></i>Booking Calendars
                    </h4>
                    <p class="text-muted mb-0">Manage availability schedules for appointments</p>
                </div>
                <div>
                    @if($limitCheck['can_create'])
                    <a href="{{ route('booking-calendars.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Create Calendar
                    </a>
                    @else
                    <button class="btn btn-secondary" onclick="showUpgradeModal('Booking Calendars', '{{ $limitCheck['message'] }}')" title="{{ $limitCheck['message'] }}">
                        <i class="fas fa-lock mr-1"></i>Upgrade to Create
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Subscription Limit Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-{{ $limitCheck['can_create'] ? 'success' : 'warning' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-info-circle mr-1"></i>Calendar Usage
                            </h6>
                            <p class="mb-0 text-muted">
                                {{ $limitCheck['current'] }} of {{ $limitCheck['max'] == -1 ? 'Unlimited' : $limitCheck['max'] }} calendars used
                                <span class="badge badge-soft-{{ $limitCheck['can_create'] ? 'success' : 'warning' }} ml-2">
                                    {{ ucfirst($limitCheck['plan']) }} Plan
                                </span>
                            </p>
                        </div>
                        @if($limitCheck['upgrade_required'])
                        <div>
                            <button type="button" class="btn-sm btn-secondary" onclick="showUpgradeModal('Booking Calendars', '{{ $limitCheck['message'] }}')">
                                <i class="fas fa-arrow-up mr-1"></i>Upgrade Plan
                            </button>
                        </div>
                        @endif
                    </div>
                    
                    @if(!$limitCheck['can_create'])
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="mb-0 mt-2 small text-warning">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $limitCheck['message'] }}
                    </p>
                    @else
                    <div class="progress mt-3" style="height: 8px;">
                        @php
                            $percentage = $limitCheck['max'] == -1 ? 0 : ($limitCheck['current'] / $limitCheck['max']) * 100;
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%"></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Calendars List -->
    @if($calendars->count() > 0)
    <div class="row">
        @foreach($calendars as $calendar)
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm {{ !$calendar->is_active ? 'border-secondary' : 'border-primary' }}">
                <div class="card-header {{ $calendar->is_active ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-alt fa-lg mr-2"></i>
                            <div>
                                <h5 class="mb-0">{{ $calendar->name }}</h5>
                                <small class="opacity-75">
                                    <span class="badge badge-light">{{ ucfirst(str_replace('_', ' ', $calendar->calendar_type)) }}</span>
                                </small>
                            </div>
                        </div>
                        <div>
                            <form action="{{ route('booking-calendars.toggle', $calendar->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $calendar->is_active ? 'btn-light' : 'btn-outline-light' }}" title="Toggle Status">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($calendar->description)
                    <p class="text-muted mb-3">{{ $calendar->description }}</p>
                    @endif

                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Default Duration</small>
                            <p class="mb-0"><strong>{{ $calendar->default_duration_minutes }} min</strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Buffer Time</small>
                            <p class="mb-0"><strong>{{ $calendar->buffer_minutes }} min</strong></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Advance Notice</small>
                            <p class="mb-0"><strong>{{ $calendar->min_advance_hours }}h</strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Max Advance</small>
                            <p class="mb-0"><strong>{{ $calendar->max_advance_days }} days</strong></p>
                        </div>
                    </div>

                    @if($calendar->max_bookings_per_day || $calendar->max_bookings_per_week)
                    <div class="row mb-3">
                        @if($calendar->max_bookings_per_day)
                        <div class="col-6">
                            <small class="text-muted">Max/Day</small>
                            <p class="mb-0"><strong>{{ $calendar->max_bookings_per_day }}</strong></p>
                        </div>
                        @endif
                        @if($calendar->max_bookings_per_week)
                        <div class="col-6">
                            <small class="text-muted">Max/Week</small>
                            <p class="mb-0"><strong>{{ $calendar->max_bookings_per_week }}</strong></p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="fas fa-calendar-check text-success mr-1"></i>
                            <strong>{{ $calendar->upcoming_bookings ?? 0 }}</strong> upcoming bookings
                        </div>
                        <div>
                            <span class="badge badge-soft-{{ $calendar->allow_ai_booking ? 'success' : 'secondary' }}">
                                <i class="fas fa-robot mr-1"></i>AI {{ $calendar->allow_ai_booking ? 'Enabled' : 'Disabled' }}
                            </span>
                            <span class="badge badge-soft-{{ $calendar->allow_manual_booking ? 'success' : 'secondary' }}">
                                <i class="fas fa-user mr-1"></i>Manual {{ $calendar->allow_manual_booking ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>

                    @if($calendar->require_confirmation)
                    <div class="alert alert-info mb-3 py-2">
                        <small><i class="fas fa-info-circle mr-1"></i>Bookings require confirmation</small>
                    </div>
                    @endif

                    <div class="btn-group btn-group-sm w-100" role="group">
                        <a href="{{ route('booking-calendars.edit', $calendar->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('booking-calendars.preview', $calendar->id) }}?start_date={{ date('Y-m-d') }}&end_date={{ date('Y-m-d', strtotime('+7 days')) }}" 
                           class="btn btn-outline-info">
                            <i class="fas fa-eye"></i> Preview Slots
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteCalendar({{ $calendar->id }}, '{{ $calendar->name }}', {{ $calendar->upcoming_bookings ?? 0 }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="far fa-clock mr-1"></i>Created {{ $calendar->created_at->diffForHumans() }}
                        @if($calendar->user)
                        by {{ $calendar->user->name }}
                        @endif
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-calendar-alt fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No Booking Calendars Yet</h4>
            <p class="text-muted mb-4">Create your first booking calendar to start managing appointments efficiently.</p>
            @if($limitCheck['can_create'])
            <a href="{{ route('booking-calendars.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Create Your First Calendar
            </a>
            @else
            <div class="alert alert-warning d-inline-block">
                <i class="fas fa-lock mr-2"></i>{{ $limitCheck['message'] }}
                <br>
                <a href="{{ url('/billing/payment') }}" class="btn btn-sm btn-warning mt-2">
                    <i class="fas fa-arrow-up mr-1"></i>Upgrade Now
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash mr-2"></i>Delete Calendar</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p id="deleteMessage"></p>
                    <div id="warningMessage" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-danger" id="confirmDeleteBtn">Delete Calendar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deleteCalendar(id, name, upcomingBookings) {
    const form = document.getElementById('deleteForm');
    const message = document.getElementById('deleteMessage');
    const warningMessage = document.getElementById('warningMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    form.action = '{{ url('/booking-calendars') }}/' + id;
    message.textContent = 'Are you sure you want to delete the calendar "' + name + '"?';
    
    if (upcomingBookings > 0) {
        warningMessage.style.display = 'block';
        warningMessage.textContent = 'Warning: This calendar has ' + upcomingBookings + ' upcoming booking(s). Deletion may be prevented.';
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Cannot Delete';
    } else {
        warningMessage.style.display = 'none';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Delete Calendar';
    }
    
    $('#deleteModal').modal('show');
}
</script>
@endpush
@endsection
