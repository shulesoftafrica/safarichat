@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-calendar-day text-info mr-2"></i>Preview Available Slots
                    </h4>
                    <p class="text-muted mb-0">{{ $calendar->name }}</p>
                </div>
                <div>
                    <a href="{{ route('booking-calendars.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Calendars
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('booking-calendars.preview', $calendar->id) }}" class="row align-items-end">
                <div class="col-md-4">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
                </div>
                <div class="col-md-4">
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i>Update Preview
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="quickRange('today')">Today</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="quickRange('week')">This Week</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Calendar Info -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Slots</h6>
                    <h3 class="mb-0 text-primary">{{ $totalSlots }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Duration</h6>
                    <h3 class="mb-0 text-info">{{ $calendar->default_duration }} min</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Buffer Time</h6>
                    <h3 class="mb-0 text-warning">{{ $calendar->buffer_time ?? 0 }} min</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Max/Day</h6>
                    <h3 class="mb-0 text-success">{{ $calendar->max_bookings_per_day ?? 'Unlimited' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Slots Display -->
    @if(count($slots) > 0)
        @foreach($slots as $date => $daySlots)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="far fa-calendar mr-2"></i>
                    {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                    <span class="badge badge-light text-primary ml-2">{{ count($daySlots) }} slots</span>
                </h5>
            </div>
            <div class="card-body">
                @if(count($daySlots) > 0)
                <div class="row">
                    @foreach($daySlots as $slot)
                    <div class="col-md-2 col-sm-3 col-6 mb-3">
                        <div class="time-slot {{ $slot['is_available'] ? 'available' : 'unavailable' }}">
                            <i class="far fa-clock mr-1"></i>
                            {{ \Carbon\Carbon::parse($slot['start'])->format('g:i A') }}
                            @if(!$slot['is_available'])
                            <span class="badge badge-danger badge-sm d-block mt-1">Booked</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                    <p class="mb-0">No available slots for this day</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No Slots Available</h4>
            <p class="text-muted mb-0">No available time slots found for the selected date range.</p>
            <p class="text-muted">This could be due to calendar settings or working hours configuration.</p>
        </div>
    </div>
    @endif

    <!-- Calendar Working Hours Info -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-business-time mr-2"></i>Working Hours</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                    $workingHours = $calendar->working_hours ?? [];
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                @endphp
                @foreach($days as $day)
                <div class="col-md-3 mb-2">
                    <strong>{{ ucfirst($day) }}:</strong>
                    @if(isset($workingHours[$day]) && $workingHours[$day]['enabled'])
                        <span class="text-success">
                            {{ $workingHours[$day]['start'] }} - {{ $workingHours[$day]['end'] }}
                        </span>
                    @else
                        <span class="text-muted">Closed</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
.time-slot {
    padding: 10px;
    text-align: center;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid #e0e0e0;
}

.time-slot.available {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.time-slot.available:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.time-slot.unavailable {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
    opacity: 0.6;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 2px 6px;
}
</style>

<script>
function quickRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    if (range === 'today') {
        startDate = endDate = today.toISOString().split('T')[0];
    } else if (range === 'week') {
        startDate = today.toISOString().split('T')[0];
        const weekEnd = new Date(today);
        weekEnd.setDate(weekEnd.getDate() + 7);
        endDate = weekEnd.toISOString().split('T')[0];
    }
    
    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
    document.querySelector('form').submit();
}
</script>
@endsection
