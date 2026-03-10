{{-- Billing Alerts Component --}}
@if(isset($billingAlerts) && count($billingAlerts) > 0)
<div class="billing-alerts-container mb-4">
    @foreach($billingAlerts as $alert)
        @php
            $alertClass = match($alert['severity']) {
                'critical' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-secondary'
            };
            
            $iconColor = match($alert['severity']) {
                'critical' => 'text-danger',
                'warning' => 'text-warning',
                'info' => 'text-info',
                default => 'text-secondary'
            };
        @endphp
        
        <div class="alert {{ $alertClass }} alert-dismissible fade show d-flex align-items-center" role="alert">
            <span class="me-2" style="font-size: 1.5rem;">{{ $alert['icon'] ?? '⚠️' }}</span>
            
            <div class="flex-grow-1">
                <strong>{{ $alert['title'] }}</strong>
                <div class="mt-1">{{ $alert['message'] }}</div>
                
                @if(isset($alert['stats']))
                    <div class="progress mt-2" style="height: 8px;">
                        @php
                            $progressClass = match(true) {
                                $alert['stats']['percentage'] >= 95 => 'bg-danger',
                                $alert['stats']['percentage'] >= 80 => 'bg-warning',
                                default => 'bg-info'
                            };
                        @endphp
                        <div class="progress-bar {{ $progressClass }}" 
                             role="progressbar" 
                             style="width: {{ min($alert['stats']['percentage'], 100) }}%"
                             aria-valuenow="{{ $alert['stats']['percentage'] }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted">
                        @if(isset($alert['stats']['remaining']))
                            {{ number_format($alert['stats']['remaining']) }} {{ __('common.remaining') }}
                        @else
                            {{ $alert['stats']['current'] }}/{{ $alert['stats']['max'] }} {{ __('common.used') }}
                        @endif
                    </small>
                @endif
            </div>
            
            @if(isset($alert['action']))
                <a href="{{ $alert['action']['url'] }}" class="btn btn-sm btn-{{ $alert['severity'] === 'critical' ? 'danger' : 'primary' }} ms-3">
                    {{ $alert['action']['text'] }}
                </a>
            @endif
            
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
</div>
@endif

{{-- Optional: Billing Summary Widget for Sidebar/Dashboard --}}
@if(isset($billingSummary) && !empty($billingSummary))
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
            <i class="fas fa-credit-card me-2"></i>
            {{ __('common.subscription') }}: {{ $billingSummary['plan_type'] }}
        </h6>
    </div>
    <div class="card-body">
        {{-- AI Credits --}}
        @if(!$billingSummary['credits']['unlimited'])
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">{{ __('common.ai_credits') }}</small>
                <small class="fw-bold">{{ number_format($billingSummary['credits']['remaining']) }}</small>
            </div>
            <div class="progress" style="height: 6px;">
                @php
                    $creditPercent = $billingSummary['credits']['percentage'] ?? 0;
                    $creditClass = match(true) {
                        $creditPercent <= 10 => 'bg-danger',
                        $creditPercent <= 20 => 'bg-warning',
                        default => 'bg-success'
                    };
                @endphp
                <div class="progress-bar {{ $creditClass }}" 
                     style="width: {{ $creditPercent }}%"></div>
            </div>
        </div>
        @else
        <div class="mb-3">
            <small class="text-muted">{{ __('common.ai_credits') }}:</small>
            <span class="badge bg-success ms-2">{{ __('common.unlimited') }}</span>
        </div>
        @endif

        {{-- Contacts --}}
        @if(!$billingSummary['contacts']['unlimited'])
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">{{ __('common.contacts') }}</small>
                <small class="fw-bold">{{ $billingSummary['contacts']['current'] }}/{{ $billingSummary['contacts']['limit'] }}</small>
            </div>
            <div class="progress" style="height: 6px;">
                @php
                    $contactPercent = $billingSummary['contacts']['percentage'] ?? 0;
                    $contactClass = match(true) {
                        $contactPercent >= 95 => 'bg-danger',
                        $contactPercent >= 80 => 'bg-warning',
                        default => 'bg-success'
                    };
                @endphp
                <div class="progress-bar {{ $contactClass }}" 
                     style="width: {{ $contactPercent }}%"></div>
            </div>
        </div>
        @else
        <div class="mb-3">
            <small class="text-muted">{{ __('common.contacts') }}:</small>
            <span class="badge bg-success ms-2">{{ __('common.unlimited') }}</span>
        </div>
        @endif

        {{-- Booking Calendars --}}
        <div class="mb-0">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">{{ __('common.booking_calendars') }}</small>
                @if($billingSummary['calendars']['unlimited'])
                    <span class="badge bg-success">{{ __('common.unlimited') }}</span>
                @else
                    <small class="fw-bold">{{ $billingSummary['calendars']['current'] }}/{{ $billingSummary['calendars']['limit'] }}</small>
                @endif
            </div>
        </div>

        {{-- Trial Expiration --}}
        @if(isset($billingSummary['trial_ends_at']) && $billingSummary['trial_ends_at'])
        <hr>
        <div class="alert alert-warning p-2 mb-0">
            <small>
                <i class="fas fa-clock me-1"></i>
                {{ __('common.trial_ends') }}: {{ \Carbon\Carbon::parse($billingSummary['trial_ends_at'])->diffForHumans() }}
            </small>
        </div>
        @endif
    </div>
</div>
@endif

<style>
.billing-alerts-container .alert {
    border-left: 4px solid;
}
.billing-alerts-container .alert-danger {
    border-left-color: #dc3545;
}
.billing-alerts-container .alert-warning {
    border-left-color: #ffc107;
}
.billing-alerts-container .alert-info {
    border-left-color: #0dcaf0;
}

/* Dark Mode Support for Billing Alerts */
.dark-mode .billing-alerts-container .alert {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .billing-alerts-container .alert-danger {
    background: rgba(239, 68, 68, 0.15) !important;
    border-left-color: #ef4444 !important;
    color: #fecaca !important;
}

.dark-mode .billing-alerts-container .alert-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    border-left-color: #f59e0b !important;
    color: #fde68a !important;
}

.dark-mode .billing-alerts-container .alert-info {
    background: rgba(59, 130, 246, 0.15) !important;
    border-left-color: #3b82f6 !important;
    color: #bfdbfe !important;
}

.dark-mode .billing-alerts-container .alert strong {
    color: #f7fafc !important;
    font-weight: 700 !important;
}

.dark-mode .billing-alerts-container .alert div {
    color: #e2e8f0 !important;
}

.dark-mode .billing-alerts-container .alert .text-muted,
.dark-mode .billing-alerts-container .alert small {
    color: #cbd5e0 !important;
}

.dark-mode .card {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .card-header {
    background: #374151 !important;
    border-bottom-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .card-header h6 {
    color: #f7fafc !important;
}

.dark-mode .card-body {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .card-body small {
    color: #cbd5e0 !important;
}

.dark-mode .card-body .text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .badge.bg-success {
    background: #10b981 !important;
    color: white !important;
}

.dark-mode .alert.alert-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    border-color: #f59e0b !important;
    color: #fde68a !important;
}

.dark-mode .progress {
    background-color: #4a5568 !important;
}
</style>
