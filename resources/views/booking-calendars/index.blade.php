@extends('layouts.app')

@section('content')
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
                            <button type="button" class="btn btn-sm btn-warning" onclick="showUpgradeModal('Booking Calendars', '{{ $limitCheck['message'] }}')">
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
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Delete Calendar</button>
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
