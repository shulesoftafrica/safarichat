@extends('layouts.app')

@section('content')
<div class="form-card bg-white">
    <div class="step-indicator">Hatua 3 kati ya 5</div>
    <div class="text-center mb-4">
        <img src="{{ asset('images/dikodiko-logo-color.png') }}" alt="Dikodiko Logo" class="logo-img">
        <h2 class="mb-3">Aina gani ya huduma unatoa kama Mtu Binafsi?</h2>
        <p class="text-muted">Chagua kundi kuu la huduma zako.</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <form action="{{ url('onboarding.individual.process_service_type') }}" method="POST">
                @csrf
                <input type="hidden" name="service_subtype" value="professional">
                <button type="submit" class="type-selection-card w-100 border-0 bg-transparent">
                    <div class="icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h4>Huduma za Kitaalamu</h4>
                    <p>Huduma zinazohitaji elimu maalum, ujuzi wa hali ya juu, au leseni.</p>
                </button>
            </form>
        </div>
        <div class="col-md-6">
            <form action="{{ url('onboarding.individual.process_service_type') }}" method="POST">
                @csrf
                <input type="hidden" name="service_subtype" value="non_professional">
                <button type="submit" class="type-selection-card w-100 border-0 bg-transparent">
                    <div class="icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h4>Huduma zisizo za Kitaalamu</h4>
                    <p>Huduma za ujuzi wa jumla, au kazi za mikono zinazohitaji nguvu.</p>
                </button>
            </form>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="{{ url('onboarding.service_provider.type') }}" class="btn btn-outline-secondary">Rudi Nyuma</a>
    </div>
</div>
@endsection