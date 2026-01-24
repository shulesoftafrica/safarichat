@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-calendar-check text-success mr-2"></i>Appointment Details
                    </h4>
                    <p class="text-muted mb-0">View and manage appointment information</p>
                </div>
                <div>
                    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to List
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Appointment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Title</h6>
                            <p class="mb-0"><strong>{{ $appointment->title }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Status</h6>
                            <p class="mb-0">
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
                                <span class="badge badge-{{ $color }} badge-lg">{{ ucfirst($appointment->status) }}</span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1"><i class="far fa-calendar mr-1"></i>Date</h6>
                            <p class="mb-0"><strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, F j, Y') }}</strong></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1"><i class="far fa-clock mr-1"></i>Time</h6>
                            <p class="mb-0"><strong>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</strong></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1"><i class="fas fa-hourglass-half mr-1"></i>Duration</h6>
                            <p class="mb-0"><strong>{{ $appointment->duration_minutes ?? 60 }} minutes</strong></p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Type</h6>
                            <p class="mb-0">
                                <span class="badge badge-soft-primary">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Created</h6>
                            <p class="mb-0 text-muted">{{ $appointment->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                    </div>

                    @if($appointment->location)
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-1"><i class="fas fa-map-marker-alt mr-1"></i>Location</h6>
                            <p class="mb-0">{{ $appointment->location }}</p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->meeting_link)
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-1"><i class="fas fa-link mr-1"></i>Meeting Link</h6>
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
                            <h6 class="text-muted mb-1"><i class="fas fa-sticky-note mr-1"></i>Notes</h6>
                            <p class="mb-0">{{ $appointment->notes }}</p>
                        </div>
                    </div>
                    @endif

                    @if($appointment->cancellation_reason)
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <h6 class="mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Cancellation Reason</h6>
                        <p class="mb-0">{{ $appointment->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Customer Information -->
            @if($appointment->lead)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Name</h6>
                            <p class="mb-0"><strong>{{ $appointment->lead->name }}</strong></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Phone</h6>
                            <p class="mb-0">
                                <a href="https://wa.me/{{ $appointment->lead->phone }}" target="_blank" class="text-success">
                                    <i class="fab fa-whatsapp mr-1"></i>{{ $appointment->lead->phone }}
                                </a>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Email</h6>
                            <p class="mb-0">{{ $appointment->lead->email ?? 'N/A' }}</p>
                        </div>
                    </div>
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
                    <button class="btn btn-success btn-block mb-2" onclick="confirmAppointment()">
                        <i class="fas fa-check mr-2"></i>Confirm Appointment
                    </button>
                    @endif

                    @if(in_array($appointment->status, ['pending', 'confirmed']))
                    <button class="btn btn-warning btn-block mb-2" onclick="showRescheduleModal()">
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reschedule</button>
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

@push('scripts')
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
@endpush
@endsection
