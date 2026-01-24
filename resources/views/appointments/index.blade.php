@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">
                    <i class="fas fa-calendar-check text-success mr-2"></i>Appointments
                </h4>
                <p class="text-muted">Manage AI-scheduled appointments and bookings</p>
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

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Upcoming</h6>
                            <h3 class="mb-0 text-primary">{{ $stats['upcoming'] ?? 0 }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-day fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h3 class="mb-0 text-warning">{{ $stats['pending'] ?? 0 }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Completed</h6>
                            <h3 class="mb-0 text-success">{{ $stats['completed'] ?? 0 }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">No-Show Rate</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($stats['no_show_rate'] ?? 0, 1) }}%</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-times fa-2x text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('appointments.index') }}" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Types</option>
                            <option value="demo" {{ request('type') == 'demo' ? 'selected' : '' }}>Demo</option>
                            <option value="consultation" {{ request('type') == 'consultation' ? 'selected' : '' }}>Consultation</option>
                            <option value="follow_up" {{ request('type') == 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                            <option value="meeting" {{ request('type') == 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="call" {{ request('type') == 'call' ? 'selected' : '' }}>Call</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" onchange="document.getElementById('filterForm').submit()">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" onchange="document.getElementById('filterForm').submit()">
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('appointments.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-redo mr-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list mr-2"></i>Appointments List
                </h5>
                <span class="badge badge-primary badge-pill">{{ $appointments->total() }} Total</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($appointments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Booking Slot</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appointment)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ \Carbon\Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time)->format('M d, Y') }}</strong>
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
                                </small>
                            </td>
                            <td>
                                <div>{{ $appointment->lead->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $appointment->lead->phone ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                            </td>
                            <td>{{ $appointment->duration_minutes ?? 60 }} min</td>
                            <td>
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
                                <span class="badge badge-{{ $color }}">{{ ucfirst($appointment->status) }}</span>
                            </td>
                            <td>
                                @if($appointment->bookingSlot)
                                    <i class="fas fa-check-circle text-success" title="Slot Reserved"></i>
                                    <small class="text-muted">{{ ucfirst($appointment->bookingSlot->status) }}</small>
                                @else
                                    <i class="fas fa-exclamation-triangle text-warning" title="No Slot"></i>
                                    <small class="text-muted">Legacy</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('appointments.show', $appointment->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($appointment->status == 'pending')
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="confirmAppointment({{ $appointment->id }})" title="Confirm">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif
                                    
                                    @if(in_array($appointment->status, ['pending', 'confirmed']))
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelAppointment({{ $appointment->id }})" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No appointments found</p>
                @if(request()->hasAny(['status', 'type', 'from_date', 'to_date']))
                <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-primary">Clear Filters</a>
                @endif
            </div>
            @endif
        </div>
        
        @if($appointments->hasPages())
        <div class="card-footer bg-white">
            {{ $appointments->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Confirm Appointment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="confirmForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to confirm this appointment?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Appointment</button>
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
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cancellation Reason</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Enter reason for cancellation..."></textarea>
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

@push('scripts')
<script>
function confirmAppointment(id) {
    const form = document.getElementById('confirmForm');
    form.action = '{{ url('/appointments') }}/' + id + '/confirm';
    $('#confirmModal').modal('show');
}

function cancelAppointment(id) {
    const form = document.getElementById('cancelForm');
    form.action = '{{ url('/appointments') }}/' + id + '/cancel';
    $('#cancelModal').modal('show');
}
</script>
@endpush
@endsection
