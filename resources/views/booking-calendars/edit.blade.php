@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-edit text-primary mr-2"></i>Edit Booking Calendar
                    </h4>
                    <p class="text-muted mb-0">Update availability schedule for "{{ $calendar->name }}"</p>
                </div>
                <div>
                    <a href="{{ route('booking-calendars.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Calendars
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Error Messages -->
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-circle mr-2"></i>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Calendar Status Banner -->
    <div class="alert alert-{{ $calendar->is_active ? 'success' : 'warning' }} mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Status:</strong> This calendar is currently <strong>{{ $calendar->is_active ? 'ACTIVE' : 'INACTIVE' }}</strong>
            </div>
            <form action="{{ route('booking-calendars.toggle', $calendar->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-{{ $calendar->is_active ? 'warning' : 'success' }}">
                    <i class="fas fa-power-off mr-1"></i>{{ $calendar->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <form action="{{ route('booking-calendars.update', $calendar->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        @include('booking-calendars._form', ['calendar' => $calendar])

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle mr-1"></i>
                            All fields marked with <span class="text-danger">*</span> are required
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('booking-calendars.index') }}" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Update Calendar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Additional Info -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Calendar Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="text-muted mb-1">Total Bookings</p>
                            <h4 class="mb-0">{{ $calendar->bookingSlots()->count() }}</h4>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Upcoming Bookings</p>
                            <h4 class="mb-0 text-success">
                                {{ $calendar->bookingSlots()->where('status', 'confirmed')->where('start_time', '>', now())->count() }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-history mr-2"></i>Calendar Info</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Created:</strong> {{ $calendar->created_at->format('M d, Y g:i A') }}
                    </p>
                    <p class="mb-2">
                        <strong>Last Updated:</strong> {{ $calendar->updated_at->format('M d, Y g:i A') }}
                    </p>
                    <p class="mb-0">
                        <strong>Created By:</strong> {{ $calendar->user->name ?? 'System' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
