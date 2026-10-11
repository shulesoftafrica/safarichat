@extends('layouts.app')

@section('content')
<style>
    .ls-wrap{max-width:1040px;margin:0 auto;padding:8px 0 40px;}
    .ls-hero{background:linear-gradient(135deg,#4b3fa7,#6c5ce7);color:#fff;border-radius:18px;padding:28px 30px;margin-bottom:22px;box-shadow:0 10px 30px rgba(75,63,167,.25);}
    .ls-hero h2{font-weight:800;margin:0 0 6px;display:flex;align-items:center;gap:10px;}
    .ls-hero p{opacity:.92;margin:0;max-width:720px;}
    .ls-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:20px;}
    @media(max-width:860px){.ls-grid{grid-template-columns:1fr;}}
    .ls-card{background:#fff;border:1px solid #ecedf5;border-radius:16px;padding:24px;box-shadow:0 4px 18px rgba(75,63,167,.05);}
    .dark-mode .ls-card{background:#232a3d;border-color:#323b52;color:#e8eaf3;}
    .ls-card h4{font-weight:700;color:#2b2f45;margin:0 0 4px;}
    .dark-mode .ls-card h4{color:#e8eaf3;}
    .ls-card .sub{color:#8b90a5;font-size:.9rem;margin-bottom:18px;}
    /* value prop */
    .ls-why{display:flex;gap:14px;margin-bottom:16px;}
    .ls-why .ic{flex:0 0 auto;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;}
    .ls-why .t{font-weight:700;color:#2b2f45;}
    .dark-mode .ls-why .t{color:#e8eaf3;}
    .ls-why .d{color:#7a7f94;font-size:.88rem;}
    .ic-green{background:linear-gradient(135deg,#22c55e,#16a34a);}
    .ic-blue{background:linear-gradient(135deg,#0ea5e9,#0369a1);}
    .ic-purple{background:linear-gradient(135deg,#6c5ce7,#4b3fa7);}
    .ic-amber{background:linear-gradient(135deg,#f59e0b,#d97706);}
    .ls-vs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:18px 0 4px;}
    @media(max-width:520px){.ls-vs{grid-template-columns:1fr;}}
    .ls-vs .col{border-radius:12px;padding:14px;font-size:.86rem;}
    .ls-vs .bad{background:#fef2f2;border:1px solid #fecaca;}
    .ls-vs .good{background:#ecfdf5;border:1px solid #bbf7d0;}
    .ls-vs h6{font-weight:700;margin:0 0 8px;}
    .ls-vs .bad h6{color:#b91c1c;} .ls-vs .good h6{color:#15803d;}
    .ls-vs ul{margin:0;padding-left:18px;color:#5b607a;}
    .dark-mode .ls-vs .bad{background:#3a2426;} .dark-mode .ls-vs .good{background:#1f3a2b;}
    /* form */
    .ls-label{font-size:.8rem;font-weight:600;color:#8b90a5;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px;display:block;}
    .ls-input{width:100%;border:1px solid #e3e5f0;border-radius:10px;padding:11px 14px;font-size:.92rem;background:#fbfbfe;transition:.15s;}
    .ls-input:focus{outline:none;border-color:#6c5ce7;background:#fff;box-shadow:0 0 0 3px rgba(108,92,231,.12);}
    .dark-mode .ls-input{background:#1b2133;border-color:#394363;color:#e8eaf3;}
    .ls-hint{font-size:.78rem;color:#9196ab;margin-top:5px;}
    .ls-btn{background:linear-gradient(135deg,#6c5ce7,#4b3fa7);color:#fff;border:0;border-radius:10px;padding:12px 22px;font-weight:600;font-size:.92rem;cursor:pointer;transition:.15s;}
    .ls-btn:hover{filter:brightness(1.07);box-shadow:0 6px 16px rgba(75,63,167,.3);}
    .ls-btn-ghost{background:#fff;color:#4b3fa7;border:1px solid #d9d6f0;border-radius:10px;padding:10px 18px;font-weight:600;cursor:pointer;}
    .ls-btn-danger{background:#fff;color:#b91c1c;border:1px solid #fecaca;border-radius:10px;padding:10px 18px;font-weight:600;cursor:pointer;}
    .ls-status{display:inline-flex;align-items:center;gap:7px;font-size:.8rem;font-weight:700;padding:4px 12px;border-radius:999px;text-transform:uppercase;letter-spacing:.03em;}
    .ls-status.on{background:#dcfce7;color:#15803d;} .ls-status.err{background:#fee2e2;color:#b91c1c;}
    .ls-stat{display:flex;gap:20px;flex-wrap:wrap;margin:14px 0;}
    .ls-stat .box{background:#f6f7fc;border:1px solid #eceef6;border-radius:12px;padding:12px 16px;min-width:130px;}
    .dark-mode .ls-stat .box{background:#1b2133;border-color:#323b52;}
    .ls-stat .n{font-size:1.5rem;font-weight:800;color:#4b3fa7;} .dark-mode .ls-stat .n{color:#b9b0ff;}
    .ls-stat .l{font-size:.78rem;color:#8b90a5;}
    .ls-alert{border-radius:12px;padding:12px 16px;font-size:.9rem;margin-bottom:16px;}
    .ls-alert.ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
    .ls-alert.err{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;}
    #ls-sync-result{display:none;margin-top:12px;font-size:.9rem;border-radius:10px;padding:10px 14px;}
    #ls-sync-result.ok{display:block;background:#dcfce7;color:#15803d;} #ls-sync-result.err{display:block;background:#fee2e2;color:#b91c1c;}
</style>

<div class="ls-wrap">
    <div class="ls-hero">
        <h2><i class="fas fa-store"></i> Connect your shop (LineShop)</h2>
        <p>Selling real products? Link your LineShop inventory and your AI assistant will always know what you sell, the price, and whether it's in stock — without you re-typing a single product.</p>
    </div>

    @if(session('lineshop_success'))<div class="ls-alert ok"><i class="fas fa-check-circle mr-1"></i>{{ session('lineshop_success') }}</div>@endif
    @if($errors->any())<div class="ls-alert err"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}</div>@endif

    <div class="ls-grid">
        {{-- LEFT: Why connect instead of uploading --}}
        <div class="ls-card">
            <h4>Why connect LineShop instead of uploading products here?</h4>
            <div class="sub">If you have dozens — or thousands — of products, this is the difference between minutes and days.</div>

            <div class="ls-why">
                <div class="ic ic-green"><i class="fas fa-bolt"></i></div>
                <div><div class="t">Thousands of products in minutes, not weeks</div>
                    <div class="d">Have 4,000 items? Adding them one-by-one here would take days. LineShop already has them — we pull the whole catalog in one sync.</div></div>
            </div>
            <div class="ls-why">
                <div class="ic ic-blue"><i class="fas fa-sync-alt"></i></div>
                <div><div class="t">Always up to date — one place to manage</div>
                    <div class="d">Change a price or add a product in LineShop and SafariChat follows. No double data-entry, no catalogs drifting out of sync.</div></div>
            </div>
            <div class="ls-why">
                <div class="ic ic-amber"><i class="fas fa-boxes"></i></div>
                <div><div class="t">Real stock levels — never oversell</div>
                    <div class="d">Your assistant checks live stock before confirming. When an item runs out, it knows instantly and can suggest an alternative instead of promising what you don't have.</div></div>
            </div>
            <div class="ls-why">
                <div class="ic ic-purple"><i class="fas fa-camera-retro"></i></div>
                <div><div class="t">Answer "do you have this?" from a photo</div>
                    <div class="d">When a customer sends a screenshot from Instagram, the assistant matches it to your real catalog and replies with the price and availability.</div></div>
            </div>

            <div class="ls-vs">
                <div class="col bad">
                    <h6><i class="fas fa-times-circle mr-1"></i>Uploading products here, by hand</h6>
                    <ul>
                        <li>One-by-one entry for every item</li>
                        <li>Update prices in two places, forever</li>
                        <li>No real stock — can promise sold-out items</li>
                        <li>Gets stale the moment your shop changes</li>
                    </ul>
                </div>
                <div class="col good">
                    <h6><i class="fas fa-check-circle mr-1"></i>Connecting LineShop</h6>
                    <ul>
                        <li>Entire catalog synced in one click</li>
                        <li>Manage products once, in LineShop</li>
                        <li>Live stock — never oversell</li>
                        <li>Stays accurate automatically</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- RIGHT: Connection panel --}}
        <div class="ls-card">
            @if($connection && $connection->status === \App\Models\LineShopConnection::STATUS_CONNECTED)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0">{{ $connection->shop_name ?: 'Your LineShop' }}</h4>
                    <span class="ls-status on"><i class="fas fa-check-circle"></i> Connected</span>
                </div>
                <div class="sub">Store: {{ $connection->base_url }}</div>

                <div class="ls-stat">
                    <div class="box"><div class="n">{{ number_format($connection->product_count) }}</div><div class="l">Products synced</div></div>
                    <div class="box"><div class="n" style="font-size:1rem;padding-top:8px;">{{ $connection->last_synced_at ? $connection->last_synced_at->diffForHumans() : 'Never' }}</div><div class="l">Last sync</div></div>
                </div>

                <button type="button" id="ls-sync-btn" class="ls-btn"><i class="fas fa-sync-alt mr-1"></i>Sync products now</button>
                <div id="ls-sync-result"></div>

                <form method="post" action="{{ route('lineshop.disconnect') }}" class="mt-3" onsubmit="return confirm('Disconnect LineShop? Your already-synced products stay, but they will stop updating.');">
                    @csrf
                    <button type="submit" class="ls-btn-danger"><i class="fas fa-unlink mr-1"></i>Disconnect</button>
                </form>
            @else
                <h4>Connect your store</h4>
                <div class="sub">Enter your LineShop details. You'll find these in LineShop under <strong>Settings → API / Integrations</strong>.</div>

                @if($connection && $connection->status === \App\Models\LineShopConnection::STATUS_ERROR && $connection->last_error)
                    <div class="ls-alert err"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $connection->last_error }}</div>
                @endif

                <form method="post" action="{{ route('lineshop.connect') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="ls-label">Store URL</label>
                        <input type="url" name="base_url" class="ls-input" placeholder="https://yourshop.lineshop.africa"
                               value="{{ old('base_url', $connection->base_url ?? '') }}" required>
                        <div class="ls-hint">The web address you use to open your LineShop.</div>
                    </div>
                    <div class="mb-3">
                        <label class="ls-label">Client ID</label>
                        <input type="number" name="client_id" class="ls-input" placeholder="e.g. 42"
                               value="{{ old('client_id', $connection->client_id ?? '') }}" required>
                        <div class="ls-hint">Your shop's ID in LineShop (shown on the API settings page).</div>
                    </div>
                    <div class="mb-3">
                        <label class="ls-label">API Key</label>
                        <input type="text" name="api_key" class="ls-input" placeholder="Paste your API key" value="{{ old('api_key') }}" required>
                        <div class="ls-hint">In LineShop, open <strong>API / Integrations</strong>, enable the API, and copy the key.</div>
                    </div>
                    <button type="submit" class="ls-btn"><i class="fas fa-link mr-1"></i>Connect &amp; verify</button>
                </form>

                @if($manualProducts > 0)
                    <div class="ls-hint mt-3"><i class="fas fa-info-circle mr-1"></i>You have {{ number_format($manualProducts) }} product(s) added manually here. They'll stay — connecting LineShop just adds your synced catalog alongside them.</div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('ls-sync-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var box = document.getElementById('ls-sync-result');
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Syncing…';
        box.className = ''; box.style.display = 'none';

        fetch('{{ route("lineshop.sync") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            box.className = (res.ok && res.d.success) ? 'ok' : 'err';
            box.innerHTML = '<i class="fas fa-' + ((res.ok && res.d.success) ? 'check-circle' : 'exclamation-circle') + ' mr-1"></i>' + (res.d.message || 'Done.');
            if (res.ok && res.d.success) { setTimeout(function(){ window.location.reload(); }, 1200); }
        })
        .catch(function () { box.className = 'err'; box.textContent = 'Sync failed — please try again.'; })
        .finally(function () { btn.disabled = false; btn.innerHTML = original; });
    });
})();
</script>
@endsection
