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
<div class="alert-inline alert-danger alert-dismissible fade show m-0 rounded-0 whatsapp-warning-banner" 
     role="alert" 
     style="position: sticky; top: 0; z-index: 1040; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <div class="container-fluid">
        <div class="d-flex align-items-center py-2">
            <i class="fas fa-exclamation-circle me-3" style="font-size: 28px;"></i>
            <div class="flex-grow-1">
                <strong style="font-size: 16px;">⚠️ WhatsApp Connection Alert!</strong>
                <p class="mb-0" style="font-size: 14px;">
                    @if($disconnectedInstances->count() === 1)
                        Your WhatsApp instance is disconnected. AI Sales Agent cannot send messages until you reconnect.
                    @else
                        {{ $disconnectedInstances->count() }} WhatsApp instances are disconnected. AI Sales Agent cannot send messages until you reconnect.
                    @endif
                </p>
            </div>
            <div class="ms-3">
                <a href="{{ route('ai-agents.index') }}" class="btn-sm btn-danger px-4" style="font-weight: 600;">
                    <i class="fas fa-plug me-1"></i> Reconnect Now
                </a>
            </div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
/* Fix btn-close icon in WhatsApp warning banner */
.whatsapp-warning-banner .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    width: 1em;
    height: 1em;
    opacity: 0.5;
    border: none;
    padding: 0;
}

.whatsapp-warning-banner .btn-close:hover {
    opacity: 0.75;
}

.dark-mode .whatsapp-warning-banner .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    opacity: 0.8;
    filter: none !important;
}

.dark-mode .whatsapp-warning-banner .btn-close:hover {
    opacity: 1;
}
</style>
@endif
@endauth
