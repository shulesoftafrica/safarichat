@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title">
                        <i class="fas fa-plus-circle text-primary mr-2"></i>Create Booking Calendar
                    </h4>
                    <p class="text-muted mb-0">Set up a new availability schedule for appointments</p>
                </div>
                <div>
                    <a href="{{ route('booking-calendars.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Calendars
                    </a>
                </div>
            </div>
        </div>
    </div>

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

    <form action="{{ route('booking-calendars.store') }}" method="POST">
        @csrf
        
        @include('booking-calendars._form', ['calendar' => null])

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
                            <i class="fas fa-save mr-1"></i>Create Calendar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
