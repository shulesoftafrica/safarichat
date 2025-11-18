@extends('layouts.app')

@section('title', 'WhatsApp Integration Success')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        WhatsApp Integration Successful!
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fab fa-whatsapp text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="text-success mb-3">Congratulations!</h5>
                    <p class="lead mb-4">
                        Your WhatsApp Business API has been successfully connected to SafariChat.
                    </p>
                    
                    <div class="alert alert-info text-start">
                        <h6><i class="fas fa-info-circle me-2"></i>What's Next?</h6>
                        <ul class="mb-0">
                            <li>Your WhatsApp Business API is now active and ready to use</li>
                            <li>You can start sending and receiving messages through the platform</li>
                            <li>Set up message templates for better engagement</li>
                            <li>Configure webhooks for real-time message processing</li>
                        </ul>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <i class="fas fa-comments text-primary mb-2" style="font-size: 2rem;"></i>
                                    <h6>Start Messaging</h6>
                                    <p class="small text-muted">Begin sending messages to your customers</p>
                                    <a href="{{ route('guest.index') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Go to Messages
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <i class="fas fa-cog text-secondary mb-2" style="font-size: 2rem;"></i>
                                    <h6>Manage Integration</h6>
                                    <p class="small text-muted">View and manage your WhatsApp settings</p>
                                    <a href="{{ route('whatsapp.integration-options') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Integration Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your integration is secured with enterprise-grade encryption
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

.fab.fa-whatsapp {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}
</style>
@endsection
