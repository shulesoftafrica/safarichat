@extends('layouts.app')

@section('content')
<style>
    .ca-wrap{max-width:1040px;margin:0 auto;padding:8px 0 40px;}
    .ca-hero{background:linear-gradient(135deg,#0369a1,#0ea5e9);color:#fff;border-radius:18px;padding:26px 30px;margin-bottom:22px;box-shadow:0 10px 30px rgba(3,105,161,.22);}
    .ca-hero h2{font-weight:800;margin:0 0 6px;display:flex;align-items:center;gap:10px;}
    .ca-hero p{opacity:.93;margin:0;max-width:760px;}
    .ca-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    @media(max-width:860px){.ca-grid{grid-template-columns:1fr;}}
    .ca-card{background:#fff;border:1px solid #ecedf5;border-radius:16px;padding:22px;box-shadow:0 4px 18px rgba(3,105,161,.05);}
    .dark-mode .ca-card{background:#232a3d;border-color:#323b52;color:#e8eaf3;}
    .ca-card h4{font-weight:700;margin:0 0 4px;}
    .ca-card .sub{color:#8b90a5;font-size:.9rem;margin-bottom:16px;}
    .ca-label{font-size:.78rem;font-weight:600;color:#8b90a5;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px;display:block;}
    .ca-input{width:100%;border:1px solid #e3e5f0;border-radius:10px;padding:10px 13px;font-size:.92rem;background:#fbfbfe;}
    .ca-input:focus{outline:none;border-color:#0ea5e9;background:#fff;box-shadow:0 0 0 3px rgba(14,165,233,.12);}
    .dark-mode .ca-input{background:#1b2133;border-color:#394363;color:#e8eaf3;}
    .ca-hint{font-size:.78rem;color:#9196ab;margin-top:5px;}
    .ca-btn{background:linear-gradient(135deg,#0ea5e9,#0369a1);color:#fff;border:0;border-radius:10px;padding:11px 20px;font-weight:600;cursor:pointer;}
    .ca-btn:hover{filter:brightness(1.07);}
    .ca-btn-danger{background:#fff;color:#b91c1c;border:1px solid #fecaca;border-radius:10px;padding:9px 16px;font-weight:600;cursor:pointer;}
    .ca-status{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:999px;text-transform:uppercase;}
    .ca-status.on{background:#dcfce7;color:#15803d;}
    .ca-alert{border-radius:10px;padding:11px 15px;font-size:.9rem;margin-bottom:14px;}
    .ca-alert.ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
    .ca-alert.err{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;}
    pre.ca-code{background:#0b1220;color:#d6e2ff;border-radius:10px;padding:14px;font-size:.78rem;overflow:auto;}
    #ca-sync-result{display:none;margin-top:12px;border-radius:10px;padding:10px 14px;font-size:.9rem;}
    #ca-sync-result.ok{display:block;background:#dcfce7;color:#15803d;}
    #ca-sync-result.err{display:block;background:#fee2e2;color:#b91c1c;}
</style>

<div class="ca-wrap">
    <div class="ca-hero">
        <h2><i class="fas fa-plug"></i> Connect your stock software</h2>
        <p>Run your own inventory/POS system? Point SafariChat at its product API and your catalog + stock sync in automatically — no re-entering products. Your assistant then answers from the synced catalog.</p>
    </div>

    @if(session('api_success'))<div class="ca-alert ok"><i class="fas fa-check-circle mr-1"></i>{{ session('api_success') }}</div>@endif
    @if($errors->any())<div class="ca-alert err"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}</div>@endif

    <div class="ca-grid">
        {{-- LEFT: the contract (for the business's developer) --}}
        <div class="ca-card">
            <h4>What your API should return</h4>
            <div class="sub">Give this to whoever maintains your stock software. Any system that returns this shape works — keys are matched flexibly.</div>
            <p style="font-size:.86rem;"><strong>GET</strong> <code>{base_url}{catalog_path}?page=1&amp;limit=200</code> with your auth header, returning JSON:</p>
            <pre class="ca-code">{
  "data": [
    {
      "code": "SKU-001",         // or "sku" / "id"
      "name": "Panadol Extra 20s",
      "price": 3500,              // selling price
      "cost": 2800,              // optional
      "quantity": 42,            // stock on hand (or "stock")
      "category": "Pain Relief",
      "image": "https://.../img.jpg",
      "description": "..."
    }
  ]
}</pre>
            <div class="ca-hint">
                A plain top-level array also works, as do <code>products</code>/<code>items</code>/<code>results</code> wrappers.
                We paginate with your page parameter until a short page is returned.
                Prices &amp; stock refresh on each sync.
            </div>
        </div>

        {{-- RIGHT: connection panel --}}
        <div class="ca-card">
            @if($connection && $connection->isConnected())
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0">{{ $connection->name ?: 'Your stock API' }}</h4>
                    <span class="ca-status on"><i class="fas fa-check-circle"></i> Connected</span>
                </div>
                <div class="sub">{{ $connection->base_url }}{{ $connection->catalog_path }}</div>
                <p style="margin:10px 0;"><strong>{{ number_format($connection->product_count) }}</strong> products synced · last {{ $connection->last_synced_at ? $connection->last_synced_at->diffForHumans() : 'never' }}</p>

                <button type="button" id="ca-sync-btn" class="ca-btn"><i class="fas fa-sync-alt mr-1"></i>Sync products now</button>
                <div id="ca-sync-result"></div>

                <form method="post" action="{{ route('catalog-api.disconnect') }}" class="mt-3" onsubmit="return confirm('Disconnect this API? Synced products stay but stop updating.');">
                    @csrf
                    <button type="submit" class="ca-btn-danger"><i class="fas fa-unlink mr-1"></i>Disconnect</button>
                </form>
            @else
                <h4>Connect your API</h4>
                <div class="sub">Enter your product API details.</div>
                <form method="post" action="{{ route('catalog-api.connect') }}"
                      onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.innerHTML='<i class=&quot;fas fa-spinner fa-spin mr-1&quot;></i>Verifying…';">
                    @csrf
                    <div class="mb-3"><label class="ca-label">Label (optional)</label>
                        <input type="text" name="name" class="ca-input" placeholder="e.g. My POS" value="{{ old('name', $connection->name ?? '') }}"></div>
                    <div class="mb-3"><label class="ca-label">Base URL</label>
                        <input type="text" name="base_url" class="ca-input" placeholder="https://api.mystore.com" value="{{ old('base_url', $connection->base_url ?? '') }}" required></div>
                    <div class="mb-3"><label class="ca-label">Catalog path</label>
                        <input type="text" name="catalog_path" class="ca-input" placeholder="/products" value="{{ old('catalog_path', $connection->catalog_path ?? '/products') }}">
                        <div class="ca-hint">Appended to the base URL.</div></div>
                    <div class="mb-3"><label class="ca-label">Authentication</label>
                        <select name="auth_type" class="ca-input" id="ca-auth-type">
                            <option value="bearer" {{ old('auth_type', $connection->auth_type ?? 'bearer')==='bearer'?'selected':'' }}>Bearer token (Authorization: Bearer …)</option>
                            <option value="header" {{ old('auth_type', $connection->auth_type ?? '')==='header'?'selected':'' }}>Custom header (e.g. X-API-Key)</option>
                            <option value="none" {{ old('auth_type', $connection->auth_type ?? '')==='none'?'selected':'' }}>None</option>
                        </select></div>
                    <div class="mb-3" id="ca-header-wrap"><label class="ca-label">Header name</label>
                        <input type="text" name="auth_header" class="ca-input" placeholder="X-API-Key" value="{{ old('auth_header', $connection->auth_header ?? 'Authorization') }}"></div>
                    <div class="mb-3" id="ca-value-wrap"><label class="ca-label">Token / key</label>
                        <input type="text" name="auth_value" class="ca-input" placeholder="Paste your token / key" value="{{ old('auth_value') }}"></div>
                    <button type="submit" class="ca-btn"><i class="fas fa-link mr-1"></i>Connect &amp; verify</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    // show/hide the header-name field based on auth type
    var at = document.getElementById('ca-auth-type');
    function toggle() {
        var t = at.value;
        var hw = document.getElementById('ca-header-wrap');
        var vw = document.getElementById('ca-value-wrap');
        if (hw) hw.style.display = (t === 'header') ? 'block' : 'none';
        if (vw) vw.style.display = (t === 'none') ? 'none' : 'block';
    }
    if (at) { at.addEventListener('change', toggle); toggle(); }

    var sync = document.getElementById('ca-sync-btn');
    if (sync) {
        sync.addEventListener('click', function () {
            var box = document.getElementById('ca-sync-result');
            var original = sync.innerHTML; sync.disabled = true;
            sync.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Syncing…';
            box.className = ''; box.style.display = 'none';
            fetch('{{ route("catalog-api.sync") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                box.className = (res.ok && res.d.success) ? 'ok' : 'err';
                box.innerHTML = '<i class="fas fa-' + ((res.ok && res.d.success) ? 'check-circle' : 'exclamation-circle') + ' mr-1"></i>' + (res.d.message || 'Done.');
                if (res.ok && res.d.success) { setTimeout(function(){ window.location.reload(); }, 1200); }
            })
            .catch(function () { box.className = 'err'; box.textContent = 'Sync failed — please try again.'; })
            .finally(function () { sync.disabled = false; sync.innerHTML = original; });
        });
    }
})();
</script>
@endsection
