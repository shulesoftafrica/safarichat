@extends('layouts.app')

@section('title', 'WhatsApp Integration Error')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        WhatsApp Integration Failed
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="text-danger mb-3">Integration Error</h5>
                    <p class="lead mb-4">
                        We encountered an issue while setting up your WhatsApp Business API integration.
                    </p>
                    
                    @if(session('error'))
                        <div class="alert alert-danger text-start">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Error Details:</h6>
                            <p class="mb-0">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    <div class="alert alert-info text-start">
                        <h6><i class="fas fa-lightbulb me-2"></i>What Can You Do?</h6>
                        <ul class="mb-0">
                            <li>Try the integration process again</li>
                            <li>Check your internet connection</li>
                            <li>Ensure you have the necessary permissions</li>
                            <li>Contact support if the issue persists</li>
                        </ul>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <i class="fas fa-redo text-primary mb-2" style="font-size: 2rem;"></i>
                                    <h6>Try Again</h6>
                                    <p class="small text-muted">Attempt the integration process again</p>
                                    <a href="{{ route('whatsapp.integration-options') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Retry Integration
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <i class="fas fa-life-ring text-warning mb-2" style="font-size: 2rem;"></i>
                                    <h6>Get Support</h6>
                                    <p class="small text-muted">Contact our support team for assistance</p>
                                    <a href="{{ route('support') }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <i class="fas fa-mobile-alt text-info mb-2" style="font-size: 2rem;"></i>
                                    <h6>Alternative: Unofficial Integration</h6>
                                    <p class="small text-muted">
                                        Use our unofficial WhatsApp integration while we resolve this issue
                                    </p>
                                    <a href="{{ route('whatsapp.instances') }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Setup Unofficial Integration
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Error occurred at {{ now()->format('M d, Y H:i:s') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.card {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.bg-light .card-body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
}

.fas.fa-times-circle {
    animation: shake 0.8s ease-in-out;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}
</style>
@endsection
