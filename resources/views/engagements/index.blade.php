@extends('layouts.app')
@section('content')
@php
    $channelMeta = [
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp',       'color' => '#25d366'],
        'email'    => ['label' => 'Email',    'icon' => 'fas fa-envelope',       'color' => '#3b82f6'],
        'sms'      => ['label' => 'SMS',       'icon' => 'fas fa-mobile-alt',     'color' => '#6366f1'],
        'bulk_sms' => ['label' => 'Bulk SMS',  'icon' => 'fas fa-bullhorn',       'color' => '#f59e0b'],
    ];
    $statusMeta = [
        'AI engaging'    => '#16a34a',
        'Awaiting reply' => '#d97706',
        'Re-engaging'    => '#2563eb',
        'With agent'     => '#7c3aed',
    ];
@endphp

<style>
    .eng-wrap{padding:6px 2px 24px;}
    .eng-title{font-size:1.6rem;font-weight:800;color:#1f2340;display:flex;align-items:center;gap:10px;margin:0;}
    .eng-title .cnt{background:#6c5ce7;color:#fff;font-size:.8rem;font-weight:700;border-radius:8px;padding:3px 9px;}
    .eng-sub{color:#8990a9;font-size:.92rem;margin:4px 0 16px;}

    .eng-channels{background:linear-gradient(120deg,#6c5ce7,#8b7ff0 60%,#a29bfe);border-radius:16px;padding:16px 18px;color:#fff;margin-bottom:16px;box-shadow:0 10px 28px rgba(108,92,231,.25);}
    .eng-channels .hd{display:flex;align-items:center;gap:9px;font-weight:700;font-size:.95rem;margin-bottom:12px;opacity:.95;}
    .eng-chips{display:flex;flex-wrap:wrap;gap:9px;}
    .eng-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);color:#fff;border-radius:11px;padding:9px 14px;font-weight:600;font-size:.9rem;cursor:pointer;transition:.15s;}
    .eng-chip:hover{background:rgba(255,255,255,.24);}
    .eng-chip.active{background:#fff;color:#4b3fa7;border-color:#fff;}
    .eng-chip .n{background:rgba(0,0,0,.14);color:inherit;border-radius:7px;padding:1px 8px;font-size:.78rem;font-weight:700;}
    .eng-chip.active .n{background:#eceaff;color:#4b3fa7;}

    .eng-grid{display:grid;grid-template-columns:380px 1fr;gap:16px;align-items:start;}
    @media(max-width:991px){.eng-grid{grid-template-columns:1fr;}}

    .eng-card{background:#fff;border:1px solid #e9ebf3;border-radius:16px;overflow:hidden;}
    .dark-mode .eng-card{background:#232a3d;border-color:#323b52;}

    .eng-search{padding:12px;border-bottom:1px solid #eef0f6;}
    .dark-mode .eng-search{border-color:#323b52;}
    .eng-search input{width:100%;border:1px solid #e1e4f0;border-radius:10px;padding:10px 12px 10px 36px;font-size:.9rem;background:#f8f9fc url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%239aa0b8' stroke-width='2' viewBox='0 0 24 24'><circle cx='11' cy='11' r='7'/><path d='M21 21l-4.3-4.3'/></svg>") 12px center no-repeat;}
    .dark-mode .eng-search input{background-color:#1b2133;border-color:#394363;color:#e8eaf3;}

    .eng-list{max-height:72vh;overflow-y:auto;}
    .eng-item{display:flex;gap:12px;padding:13px 14px;border-bottom:1px solid #f1f2f8;cursor:pointer;transition:.12s;align-items:flex-start;}
    .eng-item:hover{background:#f7f8fe;}
    .eng-item.active{background:#f0eefe;border-left:3px solid #6c5ce7;padding-left:11px;}
    .dark-mode .eng-item{border-color:#2c3447;}
    .dark-mode .eng-item:hover{background:#273049;}
    .dark-mode .eng-item.active{background:#2b2550;}

    .eng-av{width:44px;height:44px;border-radius:50%;flex:0 0 44px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;position:relative;}
    .eng-av .dot{position:absolute;right:-1px;bottom:-1px;width:14px;height:14px;border-radius:50%;border:2px solid #fff;background:#25d366;}
    .eng-it-main{min-width:0;flex:1;}
    .eng-it-top{display:flex;justify-content:space-between;gap:8px;align-items:baseline;}
    .eng-it-name{font-weight:700;color:#232746;font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .dark-mode .eng-it-name{color:#e8eaf3;}
    .eng-it-time{color:#9aa0b8;font-size:.74rem;white-space:nowrap;flex:0 0 auto;}
    .eng-it-snip{color:#6b7190;font-size:.83rem;margin:2px 0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .dark-mode .eng-it-snip{color:#aab0c7;}
    .eng-it-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
    .eng-badge{font-size:.69rem;font-weight:700;border-radius:6px;padding:2px 7px;display:inline-flex;align-items:center;gap:4px;}
    .eng-chn{color:#fff;}
    .eng-st{font-size:.72rem;font-weight:600;}
    .eng-prod{color:#9aa0b8;font-size:.72rem;}
    .eng-unread{background:#ef4444;color:#fff;border-radius:999px;min-width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;padding:0 5px;}

    .eng-empty{padding:40px 16px;text-align:center;color:#9aa0b8;font-size:.9rem;}

    /* Right pane */
    .eng-thread-wrap{min-height:72vh;display:flex;flex-direction:column;}
    .eng-thread-placeholder{flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#9aa0b8;padding:40px;text-align:center;}
    .eng-thread-placeholder i{font-size:2.4rem;margin-bottom:12px;color:#c9cde0;}
</style>

<div class="eng-wrap">
    <h2 class="eng-title">Customer Engagements <span class="cnt">{{ $counts['all'] }}</span></h2>
    <p class="eng-sub">Conversations the AI is actively running across WhatsApp, Email and SMS.</p>

    {{-- Channel filter banner --}}
    <div class="eng-channels">
        <div class="hd"><i class="fas fa-random"></i> AI Engagement Channels</div>
        <div class="eng-chips" id="engChips">
            <button class="eng-chip active" data-channel="all"><i class="fas fa-th-large"></i> All <span class="n">{{ $counts['all'] }}</span></button>
            @foreach($channelMeta as $key => $meta)
                <button class="eng-chip" data-channel="{{ $key }}"><i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }} <span class="n">{{ $counts[$key] ?? 0 }}</span></button>
            @endforeach
        </div>
    </div>

    <div class="eng-grid">
        {{-- LEFT: list --}}
        <div class="eng-card">
            <div class="eng-search">
                <input type="text" id="engSearch" placeholder="Search customers or messages…" autocomplete="off">
            </div>
            <div class="eng-list" id="engList">
                @forelse($engagements as $i => $e)
                    @php
                        $cm = $channelMeta[$e['channel_key']] ?? $channelMeta['whatsapp'];
                        $stColor = $statusMeta[$e['status']] ?? '#6b7280';
                    @endphp
                    <div class="eng-item" data-contact="{{ $e['contact_id'] }}" data-channel="{{ $e['channel_key'] }}"
                         data-search="{{ strtolower($e['name'].' '.$e['snippet'].' '.$e['product']) }}">
                        <div class="eng-av" style="background:{{ $cm['color'] }}">
                            {{ $e['initials'] }}
                            @if($e['channel_key']==='whatsapp')<span class="dot"></span>@endif
                        </div>
                        <div class="eng-it-main">
                            <div class="eng-it-top">
                                <span class="eng-it-name">{{ $e['name'] }}</span>
                                <span class="eng-it-time">{{ $e['at_label'] }}</span>
                            </div>
                            <div class="eng-it-snip">{{ $e['snippet'] }}</div>
                            <div class="eng-it-meta">
                                <span class="eng-badge eng-chn" style="background:{{ $cm['color'] }}"><i class="{{ $cm['icon'] }}"></i> {{ $cm['label'] }}</span>
                                <span class="eng-st" style="color:{{ $stColor }}">{{ $e['status'] }}</span>
                                @if($e['product'])<span class="eng-prod">· {{ \Illuminate\Support\Str::limit($e['product'], 22) }}</span>@endif
                                @if($e['unread'])<span class="eng-unread" style="margin-left:auto">{{ $e['unread'] }}</span>@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="eng-empty"><i class="fas fa-comments fa-2x d-block mb-2" style="color:#c9cde0"></i>No active engagements right now.<br>New conversations appear here as customers reply.</div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT: thread --}}
        <div class="eng-card eng-thread-wrap" id="engThread">
            <div class="eng-thread-placeholder" id="engThreadPlaceholder">
                <i class="far fa-comment-dots"></i>
                <div>Select a conversation to see the full story across WhatsApp, Email and SMS.</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    var list = document.getElementById('engList');
    var thread = document.getElementById('engThread');
    var threadUrl = function (cid) { return "{{ url('engagements') }}/" + cid + "/thread"; };

    function selectItem(el) {
        if (!el) return;
        document.querySelectorAll('.eng-item').forEach(function (i) { i.classList.remove('active'); });
        el.classList.add('active');
        var cid = el.getAttribute('data-contact');
        thread.innerHTML = '<div class="eng-thread-placeholder"><i class="fas fa-spinner fa-spin"></i><div>Loading conversation…</div></div>';
        fetch(threadUrl(cid), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.success) { thread.innerHTML = d.html; bindTakeOver(cid); }
                else { thread.innerHTML = '<div class="eng-thread-placeholder">Could not load this conversation.</div>'; }
            })
            .catch(function () { thread.innerHTML = '<div class="eng-thread-placeholder">Network error — please try again.</div>'; });
    }

    function bindTakeOver(cid) {
        var btn = thread.querySelector('.eng-takeover');
        if (!btn) return;
        btn.addEventListener('click', function () {
            btn.disabled = true; btn.textContent = 'Taking over…';
            fetch("{{ url('engagements') }}/" + cid + "/take-over", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.textContent = (d && d.success) ? '✓ You have taken over' : 'Try again';
                if (d && d.success) {
                    var bar = thread.querySelector('.eng-footer-note');
                    if (bar) bar.textContent = d.message;
                    btn.classList.add('done');
                }
            })
            .catch(function () { btn.disabled = false; btn.textContent = 'Take over'; });
        });
    }

    if (list) {
        list.addEventListener('click', function (e) {
            var item = e.target.closest('.eng-item');
            if (item) selectItem(item);
        });
    }

    // Channel filter
    var chips = document.getElementById('engChips');
    if (chips) {
        chips.addEventListener('click', function (e) {
            var chip = e.target.closest('.eng-chip');
            if (!chip) return;
            chips.querySelectorAll('.eng-chip').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            var ch = chip.getAttribute('data-channel');
            document.querySelectorAll('.eng-item').forEach(function (it) {
                it.style.display = (ch === 'all' || it.getAttribute('data-channel') === ch) ? '' : 'none';
            });
        });
    }

    // Search
    var search = document.getElementById('engSearch');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('.eng-item').forEach(function (it) {
                it.style.display = (!q || it.getAttribute('data-search').indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    // Auto-open the first conversation.
    var first = document.querySelector('.eng-item');
    if (first) selectItem(first);
})();
</script>
@endsection
