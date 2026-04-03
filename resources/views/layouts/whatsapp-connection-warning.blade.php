@auth
@php
    // Cache the down-instances check for 60 s to avoid repeated DB queries.
    // Uses the same isOperational() logic as Home.php and CheckWhatsAppSetup so
    // status reporting is consistent across the entire app.
    $cacheKey = 'whatsapp_down_instances_' . Auth::id();
    $downInstances = Cache::remember($cacheKey, 60, function () {
        return \App\Models\WhatsappInstance::where('user_id', Auth::id())
            ->where('is_system_default', false)
            ->get()
            ->filter(fn($i) => !$i->isOperational())
            ->values();
    });
@endphp

@if($downInstances->isNotEmpty())
{{-- Non-dismissible: the user MUST reconnect before the app works correctly. --}}
<div class="alert-inline alert-danger fade show m-0 rounded-0 whatsapp-warning-banner"
     role="alert"
     style="position: sticky; top: 0; z-index: 1040; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <div class="container-fluid">
        <div class="d-flex align-items-center py-2">
            <i class="fas fa-exclamation-circle me-3" style="font-size: 28px; flex-shrink: 0;"></i>
            <div class="flex-grow-1">
                <strong style="font-size: 16px;">⚠️ WhatsApp Connection Alert!</strong>
                <p class="mb-0" style="font-size: 14px;">
                    @if($downInstances->count() === 1)
                        <strong>{{ $downInstances->first()->display_name }}</strong> is disconnected.
                        The AI Sales Agent cannot send messages until it is reconnected.
                    @else
                        The following instances are disconnected:
                        <strong>{{ $downInstances->pluck('display_name')->implode(', ') }}</strong>.
                        The AI Sales Agent cannot send messages until all instances are reconnected.
                    @endif
                </p>
            </div>
            <div class="ms-3" style="flex-shrink: 0;">
                <a href="{{ route('business.wasender') }}" class="btn btn-sm btn-danger px-4" style="font-weight: 600; white-space: nowrap;">
                    <i class="fas fa-plug me-1"></i> Reconnect Now
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endauth
