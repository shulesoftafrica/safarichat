@auth
@php
    // Cache the disconnected instances check for 1 minute to avoid repeated DB queries
    $cacheKey = 'whatsapp_disconnected_' . Auth::id();
    $disconnectedInstances = Cache::remember($cacheKey, 60, function () {
        return \App\Models\WhatsappInstance::where('user_id', Auth::id())
            ->where('connect_status', '!=', 'ready')
            ->get();
    });
@endphp

@if($disconnectedInstances->isNotEmpty())
<div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 whatsapp-warning-banner" 
     role="alert" 
     style="border-left: 5px solid #dc3545; background-color: #f8d7da; color: #721c24; position: sticky; top: 0; z-index: 1040; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <div class="container-fluid">
        <div class="d-flex align-items-center py-2">
            <i class="fas fa-exclamation-circle me-3" style="font-size: 28px; color: #dc3545;"></i>
            <div class="flex-grow-1">
                <strong style="font-size: 16px; color: #721c24;">⚠️ WhatsApp Connection Alert!</strong>
                <p class="mb-0" style="font-size: 14px; color: #721c24;">
                    @if($disconnectedInstances->count() === 1)
                        Your WhatsApp instance is disconnected. AI Sales Agent cannot send messages until you reconnect.
                    @else
                        {{ $disconnectedInstances->count() }} WhatsApp instances are disconnected. AI Sales Agent cannot send messages until you reconnect.
                    @endif
                </p>
            </div>
            <div class="ms-3">
                <a href="{{ route('ai-agents.index') }}" class="btn btn-danger btn-sm px-4" style="font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-plug me-1"></i> Reconnect Now
                </a>
            </div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close" style="font-size: 1.2rem;"></button>
        </div>
    </div>
</div>
@endif
@endauth
