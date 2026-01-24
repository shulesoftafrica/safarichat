@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">
                    <i class="fas fa-calendar-check text-success mr-2"></i>Appointments
                </h4>
                <p class="text-muted">Manage AI-scheduled appointments and booking calendars</p>
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

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="appointmentTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="appointments-tab" href="#appointmentsContent" role="tab">
                <i class="fas fa-list mr-2"></i>Appointments
                @if(isset($stats['pending']) && $stats['pending'] > 0)
                <span class="badge badge-danger ml-1">{{ $stats['pending'] }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="calendars-tab" href="{{ route('booking-calendars.index') }}" role="tab">
                <i class="fas fa-calendar-alt mr-2"></i>Booking Calendars
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div id="appointmentsContent">
        @include('appointments._appointments_list')
    </div>
</div>

@include('appointments._modals')

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
