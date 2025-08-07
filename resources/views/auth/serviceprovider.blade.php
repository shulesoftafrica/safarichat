@extends('layouts.app')

@section('content')
<div class="form-card bg-white">
    <div class="step-indicator">Hatua 2 kati ya 5</div> {{-- Simple progress indicator --}}
    <div class="text-center mb-4">
        <img src="{{ asset('images/dikodiko-logo-color.png') }}" alt="Dikodiko Logo" class="logo-img">
        <h2 class="mb-3">Wewe ni nani kama Mtoa Huduma?</h2>
        <p class="text-muted">Chagua kama unatoa huduma kama mtu binafsi au kampuni/shirika.</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <form action="{{ route('onboarding.process_service_provider.type') }}" method="POST">
                @csrf
                <input type="hidden" name="provider_type" value="individual">
                <button type="submit" class="type-selection-card w-100 border-0 bg-transparent">
                    <div class="icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4>Mtu Binafsi</h4>
                    <p>Unatoa huduma wewe mwenyewe bila kampuni iliyosajiliwa.</p>
                </button>
            </form>
        </div>
        <div class="col-md-6">
            <form action="{{ route('onboarding.process_service_provider.type') }}" method="POST">
                @csrf
                <input type="hidden" name="provider_type" value="organization">
                <button type="submit" class="type-selection-card w-100 border-0 bg-transparent">
                    <div class="icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4>Kampuni/Shirika</h4>
                    <p>Unatoa huduma kupitia kampuni, NGO, au taasisi iliyosajiliwa.</p>
                </button>
            </form>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="{{ route('onboarding.choose_role') }}" class="btn btn-outline-secondary">Rudi Nyuma</a>
    </div>
</div>
@endsection