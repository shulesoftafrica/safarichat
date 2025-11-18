@extends('layouts.app')

@section('title', 'WhatsApp Integration Options')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fab fa-whatsapp text-success me-2"></i>
                        WhatsApp Integration Options
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Unofficial WhatsApp Integration -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        Unofficial Integration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <span class="badge bg-info mb-2">Quick Setup</span>
                                        <span class="badge bg-warning mb-2">Personal Use</span>
                                    </div>
                                    
                                    <p class="card-text">
                                        Connect your personal WhatsApp account using QR code scanning. 
                                        Perfect for small businesses and personal use.
                                    </p>
                                    
                                    <div class="features mb-4">
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Easy QR code setup</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Personal phone number</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Basic messaging</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Limited API features</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>No official support</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="status mb-3">
                                        @if($unofficialInstances > 0)
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                You have {{ $unofficialInstances }} unofficial instance(s) connected
                                            </div>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No unofficial instances connected
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-primary w-100" onclick="window.location.href='{{ route('whatsapp.instances') }}'">
                                        <i class="fas fa-plus me-2"></i>
                                        Manage Unofficial Instances
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Official WhatsApp Business API -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fab fa-whatsapp me-2"></i>
                                        Official Business API
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <span class="badge bg-success mb-2">Enterprise Ready</span>
                                        <span class="badge bg-primary mb-2">Official</span>
                                        <span class="badge bg-info mb-2">Full Features</span>
                                    </div>
                                    
                                    <p class="card-text">
                                        Connect using the official WhatsApp Business API through certified Business Solution Providers. 
                                        Ideal for businesses requiring reliability and advanced features.
                                    </p>
                                    
                                    <div class="features mb-4">
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Official WhatsApp support</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Business phone number</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Advanced messaging</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Message templates</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Webhooks & APIs</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Enterprise reliability</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="status mb-3" id="official-status">
                                        @if($officialCredentials->count() > 0)
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                You have {{ $officialCredentials->count() }} official integration(s)
                                            </div>
                                            
                                            <div class="credentials-list">
                                                @foreach($officialCredentials as $credential)
                                                    <div class="credential-item border rounded p-2 mb-2">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <small class="text-muted">{{ $credential->api_provider }}</small>
                                                                <div class="fw-bold">{{ $credential->display_phone_number ?: 'Setting up...' }}</div>
                                                                @if($credential->verified_name)
                                                                    <small class="text-success">{{ $credential->verified_name }}</small>
                                                                @endif
                                                            </div>
                                                            <span class="badge bg-{{ $credential->status_color }}">
                                                                {{ $credential->status_label }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No official integrations configured
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-success w-100" id="setup-official-btn">
                                        <i class="fas fa-plus me-2"></i>
                                        Setup Official Integration
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparison Table -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-balance-scale me-2"></i>
                                        Feature Comparison
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Feature</th>
                                                    <th class="text-center">Unofficial</th>
                                                    <th class="text-center">Official Business API</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Setup Difficulty</td>
                                                    <td class="text-center"><span class="badge bg-success">Easy</span></td>
                                                    <td class="text-center"><span class="badge bg-warning">Moderate</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Cost</td>
                                                    <td class="text-center"><span class="badge bg-success">Free</span></td>
                                                    <td class="text-center"><span class="badge bg-info">Paid</span></td>
                                                </tr>
                                                <tr>
                                                    <td>WhatsApp Support</td>
                                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                                </tr>
                                                <tr>
                                                    <td>Business Verification</td>
                                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                                </tr>
                                                <tr>
                                                    <td>Message Templates</td>
                                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                                </tr>
                                                <tr>
                                                    <td>Webhook Support</td>
                                                    <td class="text-center"><span class="badge bg-warning">Limited</span></td>
                                                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                                </tr>
                                                <tr>
                                                    <td>API Rate Limits</td>
                                                    <td class="text-center"><span class="badge bg-danger">Strict</span></td>
                                                    <td class="text-center"><span class="badge bg-success">Enterprise</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Reliability</td>
                                                    <td class="text-center"><span class="badge bg-warning">Medium</span></td>
                                                    <td class="text-center"><span class="badge bg-success">High</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Official Integration Setup Modal -->
<div class="modal fade" id="officialSetupModal" tabindex="-1" aria-labelledby="officialSetupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="officialSetupModalLabel">
                    <i class="fab fa-whatsapp text-success me-2"></i>
                    Setup Official WhatsApp Business API
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="setup-step-1" class="setup-step">
                    <h6>Step 1: Choose Business Solution Provider</h6>
                    <p class="text-muted">Select a certified Business Solution Provider to handle your WhatsApp Business API integration:</p>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card provider-card" data-provider="360dialog">
                                <div class="card-body text-center">
                                    <img src="/images/providers/360dialog-logo.png" alt="360Dialog" class="mb-2" style="height: 40px;" onerror="this.style.display='none'">
                                    <h6>360Dialog</h6>
                                    <p class="small text-muted">Global coverage, competitive pricing</p>
                                    <span class="badge bg-primary">Recommended</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card provider-card" data-provider="twilio">
                                <div class="card-body text-center">
                                    <img src="/images/providers/twilio-logo.png" alt="Twilio" class="mb-2" style="height: 40px;" onerror="this.style.display='none'">
                                    <h6>Twilio</h6>
                                    <p class="small text-muted">Enterprise features, reliable infrastructure</p>
                                    <span class="badge bg-info">Enterprise</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card provider-card" data-provider="facebook">
                                <div class="card-body text-center">
                                    <img src="/images/providers/meta-logo.png" alt="Meta" class="mb-2" style="height: 40px;" onerror="this.style.display='none'">
                                    <h6>Meta Direct</h6>
                                    <p class="small text-muted">Direct from WhatsApp/Meta</p>
                                    <span class="badge bg-success">Official</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> You'll need to complete verification and payment setup with your chosen provider.
                    </div>
                </div>
                
                <div id="setup-step-2" class="setup-step d-none">
                    <h6>Step 2: Initialize Integration</h6>
                    <p class="text-muted">We'll redirect you to complete the setup with your chosen provider.</p>
                    
                    <div class="selected-provider-info">
                        <div class="card">
                            <div class="card-body">
                                <h6 id="selected-provider-name"></h6>
                                <p id="selected-provider-description"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress mb-3 d-none" id="initialization-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                    </div>
                    
                    <div id="initialization-status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="continue-setup-btn" disabled>Continue Setup</button>
                <button type="button" class="btn btn-success d-none" id="initialize-btn">Initialize Integration</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let selectedProvider = null;
    let setupModal = null;
    
    // Initialize modal
    setupModal = new bootstrap.Modal(document.getElementById('officialSetupModal'));
    
    // Setup official integration button
    $('#setup-official-btn').click(function() {
        setupModal.show();
        resetSetupFlow();
    });
    
    // Provider selection
    $('.provider-card').click(function() {
        selectedProvider = $(this).data('provider');
        $('.provider-card').removeClass('border-primary');
        $(this).addClass('border-primary');
        $('#continue-setup-btn').prop('disabled', false);
        
        // Update provider info
        const providerNames = {
            '360dialog': '360Dialog',
            'twilio': 'Twilio',
            'facebook': 'Meta Direct'
        };
        
        const providerDescriptions = {
            '360dialog': 'Global Business Solution Provider with competitive pricing and excellent support.',
            'twilio': 'Enterprise-grade communication platform with advanced features and reliability.',
            'facebook': 'Direct integration with Meta/WhatsApp for the most up-to-date features.'
        };
        
        $('#selected-provider-name').text(providerNames[selectedProvider]);
        $('#selected-provider-description').text(providerDescriptions[selectedProvider]);
    });
    
    // Continue to step 2
    $('#continue-setup-btn').click(function() {
        showStep2();
    });
    
    // Initialize integration
    $('#initialize-btn').click(function() {
        initializeIntegration();
    });
    
    function resetSetupFlow() {
        selectedProvider = null;
        $('.provider-card').removeClass('border-primary');
        $('#continue-setup-btn').prop('disabled', true);
        showStep1();
    }
    
    function showStep1() {
        $('#setup-step-1').removeClass('d-none');
        $('#setup-step-2').addClass('d-none');
        $('#continue-setup-btn').removeClass('d-none');
        $('#initialize-btn').addClass('d-none');
    }
    
    function showStep2() {
        $('#setup-step-1').addClass('d-none');
        $('#setup-step-2').removeClass('d-none');
        $('#continue-setup-btn').addClass('d-none');
        $('#initialize-btn').removeClass('d-none');
    }
    
    function initializeIntegration() {
        const $btn = $('#initialize-btn');
        const $progress = $('#initialization-progress');
        const $status = $('#initialization-status');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Initializing...');
        $progress.removeClass('d-none');
        updateProgress(20);
        
        $.ajax({
            url: '{{ route("whatsapp.official.initialize") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                provider: selectedProvider
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(60);
                    $status.html('<div class="alert alert-success"><i class="fas fa-check me-2"></i>Integration initialized successfully!</div>');
                    
                    // Redirect to embedded signup
                    setTimeout(() => {
                        updateProgress(100);
                        window.location.href = response.redirect_url;
                    }, 1500);
                } else {
                    showError(response.message || 'Failed to initialize integration');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to initialize integration';
                showError(message);
            }
        });
    }
    
    function updateProgress(percent) {
        $('#initialization-progress .progress-bar').css('width', percent + '%');
    }
    
    function showError(message) {
        const $btn = $('#initialize-btn');
        const $status = $('#initialization-status');
        
        $btn.prop('disabled', false).html('Initialize Integration');
        $status.html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + message + '</div>');
        $('#initialization-progress').addClass('d-none');
    }
    
    // Refresh status periodically
    setInterval(refreshOfficialStatus, 30000);
    
    function refreshOfficialStatus() {
        $.ajax({
            url: '{{ route("whatsapp.official.status") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateOfficialStatusDisplay(response.credentials);
                }
            },
            error: function(xhr) {
                console.log('Failed to refresh status:', xhr.responseJSON?.message);
            }
        });
    }
    
    function updateOfficialStatusDisplay(credentials) {
        // Update the status display with fresh data
        // This would update the main page status section
        console.log('Updated credentials:', credentials);
    }
});
</script>
@endsection

@section('styles')
<style>
.provider-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.provider-card:hover {
    border-color: #0d6efd !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.provider-card.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
}

.credential-item {
    background-color: #f8f9fa;
}

.setup-step {
    min-height: 300px;
}

.features ul li {
    padding: 2px 0;
}

.table td, .table th {
    vertical-align: middle;
}
</style>
@endsection
