@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-calendar-check text-success mr-2"></i>{{ __("appointments.details_title") }}
                    </h4>
                    <p class="text-muted mb-0">{{ __("appointments.details_subtitle") }}</p>
                </div>
                <div>
                    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>{{ __("appointments.actions.back_to_list") }}
                    </a>
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

    <div class="row">
        <!-- Main Appointment Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>{{ $appointment->title }}</h4>
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'secondary',
                                'no_show' => 'danger'
                            ];
                            $color = $statusColors[$appointment->status] ?? 'secondary';
                        @endphp
                        <span class="badge badge-{{ $color }} badge-pill px-3 py-2" style="font-size: 1rem;">{{ ucfirst($appointment->status) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Date, Time, Duration - Highlighted -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box text-center p-3 bg-light rounded">
                                <i class="far fa-calendar fa-2x text-primary mb-2"></i>
                                <h6 class="text-muted mb-1">{{ __("appointments.details.date") }}</h6>
                                <p class="mb-0 h5">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('M d, Y') }}</p>
                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('l') }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box text-center p-3 bg-light rounded">
                                <i class="far fa-clock fa-2x text-success mb-2"></i>
                                <h6 class="text-muted mb-1">{{ __("appointments.details.time") }}</h6>
                                <p class="mb-0 h5">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('g:i A') }}</p>
                                <p class="mb-0 text-muted small">{{ $appointment->duration_minutes ?? 60 }} {{ __("appointments.details.minutes") }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box text-center p-3 bg-light rounded">
                                <i class="fas fa-tag fa-2x text-info mb-2"></i>
                                <h6 class="text-muted mb-1">{{ __("appointments.details.type") }}</h6>
                                <p class="mb-0 h5">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</p>
                                <p class="mb-0 text-muted small">{{ $appointment->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($appointment->description)
                    <div class="mb-4">
                        <div class="border-left-primary p-3 bg-light">
                            <h6 class="text-primary mb-2"><i class="fas fa-file-alt mr-2"></i>{{ __("appointments.details.description") }}</h6>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $appointment->description }}</p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->location)
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-1"><i class="fas fa-map-marker-alt mr-1"></i>{{ __("appointments.details.location") }}</h6>
                            <p class="mb-0">{{ $appointment->location }}</p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->meeting_link)
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-1"><i class="fas fa-link mr-1"></i>{{ __("appointments.details.meeting_link") }}</h6>
                            <p class="mb-0">
                                <a href="{{ $appointment->meeting_link }}" target="_blank" class="text-primary">
                                    {{ $appointment->meeting_link }} <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->notes)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-1"><i class="fas fa-sticky-note mr-1"></i>{{ __("appointments.details.notes") }}</h6>
                            <p class="mb-0">{{ $appointment->notes }}</p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->cancellation_reason)
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <h6 class="mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>{{ __("appointments.details.cancellation_reason") }}</h6>
                        <p class="mb-0">{{ $appointment->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Customer Information -->
            @if($appointment->lead)
            <div class="card shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>{{ __("appointments.details.customer_info") }}</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <div class="media align-items-center">
                                <div class="avatar-circle bg-info text-white mr-3">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                                <div class="media-body">
                                    <h6 class="text-muted mb-1 small">{{ __("appointments.details.customer_name") }}</h6>
                                    <h4 class="mb-0 font-weight-bold">
                                        @if($appointment->lead->contact && $appointment->lead->contact->guest_name)
                                            {{ $appointment->lead->contact->guest_name }}
                                        @else
                                            {{ $appointment->lead->name ?? 'N/A' }}
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="media align-items-center">
                                <div class="avatar-circle bg-success text-white mr-3">
                                    <i class="fab fa-whatsapp fa-2x"></i>
                                </div>
                                <div class="media-body">
                                    <h6 class="text-muted mb-1 small">{{ __("appointments.details.phone_number") }}</h6>
                                    @php
                                        $phone = $appointment->lead->phone_number ?? ($appointment->lead->contact->guest_phone ?? 'N/A');
                                    @endphp
                                    @if($phone != 'N/A')
                                    <h4 class="mb-0">
                                        <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $phone) }}" target="_blank" class="text-success font-weight-bold text-decoration-none">
                                            {{ $phone }}
                                        </a>
                                    </h4>
                                    @else
                                    <h4 class="mb-0 text-muted">N/A</h4>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($appointment->lead->email)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-1 small"><i class="fas fa-envelope mr-1"></i>{{ __("appointments.details.email") }}</h6>
                            <p class="mb-0 h6">
                                <a href="mailto:{{ $appointment->lead->email }}" class="text-primary">{{ $appointment->lead->email }}</a>
                            </p>
                        </div>
                    </div>
                    @endif
                    @if($appointment->lead->company_name)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-1 small"><i class="fas fa-building mr-1"></i>Company</h6>
                            <p class="mb-0 h6">{{ $appointment->lead->company_name }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Action Buttons -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-tools mr-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    @if($appointment->status == 'pending')
                    <button class="btn-primary btn-block mb-2" onclick="confirmAppointment()">
                        <i class="fas fa-check mr-2"></i>Confirm Appointment
                    </button>
                    @endif

                    @if(in_array($appointment->status, ['pending', 'confirmed']))
                    <button class="btn-secondary btn-block mb-2" onclick="showRescheduleModal()">
                        <i class="fas fa-calendar-alt mr-2"></i>Reschedule
                    </button>
                    
                    <button class="btn btn-outline-danger btn-block mb-2" onclick="cancelAppointment()">
                        <i class="fas fa-times mr-2"></i>Cancel Appointment
                    </button>
                    @endif

                    @if($appointment->status == 'confirmed')
                    <hr>
                    <button class="btn btn-outline-success btn-block mb-2" onclick="completeAppointment()">
                        <i class="fas fa-check-circle mr-2"></i>Mark as Completed
                    </button>
                    
                    <button class="btn btn-outline-danger btn-block" onclick="markNoShow()">
                        <i class="fas fa-user-times mr-2"></i>Mark as No-Show
                    </button>
                    @endif
                </div>
            </div>

            <!-- Booking Slot Info -->
            @if($appointment->bookingSlot)
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Booking Slot</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Calendar</h6>
                        <p class="mb-0"><strong>{{ $appointment->bookingSlot->bookingCalendar->name }}</strong></p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Slot Status</h6>
                        <p class="mb-0">
                            @php
                                $slotStatusColors = [
                                    'available' => 'secondary',
                                    'reserved' => 'warning',
                                    'confirmed' => 'success',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                    'no_show' => 'dark'
                                ];
                                $slotColor = $slotStatusColors[$appointment->bookingSlot->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $slotColor }}">{{ ucfirst($appointment->bookingSlot->status) }}</span>
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Time Slot</h6>
                        <p class="mb-0">
                            {{ \Carbon\Carbon::parse($appointment->bookingSlot->start_time)->format('M d, Y g:i A') }}
                            <br>
                            <small class="text-muted">to {{ \Carbon\Carbon::parse($appointment->bookingSlot->end_time)->format('g:i A') }}</small>
                        </p>
                    </div>

                    <div class="mb-0">
                        <h6 class="text-muted mb-1">Confirmation #</h6>
                        <p class="mb-0">
                            <code>{{ strtoupper(substr(md5($appointment->bookingSlot->id), 0, 8)) }}</code>
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Legacy Appointment</strong>
                <p class="mb-0 small">This appointment was created before the booking calendar system was implemented.</p>
            </div>
            @endif

            <!-- Timeline -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <i class="fas fa-plus-circle text-primary"></i>
                            <div class="timeline-content">
                                <p class="mb-0 small"><strong>Created</strong></p>
                                <p class="mb-0 text-muted small">{{ $appointment->created_at->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>

                        @if($appointment->confirmed_at)
                        <div class="timeline-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <div class="timeline-content">
                                <p class="mb-0 small"><strong>Confirmed</strong></p>
                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($appointment->confirmed_at)->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($appointment->completed_at)
                        <div class="timeline-item">
                            <i class="fas fa-flag-checkered text-info"></i>
                            <div class="timeline-content">
                                <p class="mb-0 small"><strong>Completed</strong></p>
                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($appointment->completed_at)->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($appointment->cancelled_at)
                        <div class="timeline-item">
                            <i class="fas fa-times-circle text-danger"></i>
                            <div class="timeline-content">
                                <p class="mb-0 small"><strong>Cancelled</strong></p>
                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($appointment->cancelled_at)->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Modals -->
<form id="actionForm" method="POST">
    @csrf
</form>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt mr-2"></i>Reschedule Appointment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="rescheduleForm" method="POST" action="{{ route('appointments.reschedule', $appointment->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>New Date</label>
                        <input type="date" name="new_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>New Time</label>
                        <input type="time" name="new_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reason for Reschedule</label>
                        <textarea name="reschedule_reason" class="form-control" rows="3" placeholder="Optional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-secondary">Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Cancel Appointment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancelForm" method="POST" action="{{ route('appointments.cancel', $appointment->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cancellation Reason</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Enter reason for cancellation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.info-box {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
}

.info-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.border-left-primary {
    border-left: 4px solid #667eea;
}

.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    display: flex;
    align-items-start;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -23px;
    top: 20px;
    height: 100%;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-item i {
    position: absolute;
    left: -30px;
    font-size: 1rem;
    background-color: white;
    padding: 2px;
}

.timeline-content {
    margin-left: 10px;
}

.badge-lg {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}
</style>
@endpush

<script>
function confirmAppointment() {
    if (confirm('Are you sure you want to confirm this appointment?')) {
        const form = document.getElementById('actionForm');
        form.action = '{{ route('appointments.confirm', $appointment->id) }}';
        form.submit();
    }
}

function cancelAppointment() {
    $('#cancelModal').modal('show');
}

function completeAppointment() {
    if (confirm('Mark this appointment as completed?')) {
        const form = document.getElementById('actionForm');
        form.action = '{{ route('appointments.complete', $appointment->id) }}';
        form.submit();
    }
}

function markNoShow() {
    if (confirm('Mark this appointment as no-show?')) {
        const form = document.getElementById('actionForm');
        form.action = '{{ route('appointments.no-show', $appointment->id) }}';
        form.submit();
    }
}

function showRescheduleModal() {
    $('#rescheduleModal').modal('show');
}
</script>
@endsection
