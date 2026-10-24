{{-- Events & Automations — conversion events (lead → customer). Pro+ only. --}}
@php
    $afterSalePhone = optional($agents->first())->after_sale_agent_phone;
@endphp
<style>
    .ev-wrap{margin-top:26px}
    .ev-head h3{font-size:1.15rem;font-weight:700;margin:0 0 4px;display:flex;align-items:center}
    .ev-head p{color:#9196ab;font-size:.9rem;margin:0 0 16px;max-width:760px}
    .ev-card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:18px 20px;margin-bottom:16px}
    .ev-lock{background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fcd34d;border-radius:14px;padding:20px 22px;display:flex;gap:16px;align-items:flex-start}
    .ev-lock i{font-size:1.6rem;color:#b45309}
    .ev-lock h4{margin:0 0 4px;font-weight:700;color:#92400e}
    .ev-lock p{margin:0;color:#92400e;font-size:.9rem}
    .ev-lbl{display:block;font-size:.82rem;font-weight:600;color:#5b607a;margin-bottom:6px}
    .ev-inp,.ev-sel,.ev-ta{width:100%;border:1px solid #d7dae8;border-radius:10px;padding:10px 12px;font-size:.9rem;background:#fff;color:#2b2f45}
    .ev-ta{min-height:70px;resize:vertical}
    .ev-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:768px){.ev-row{grid-template-columns:1fr}}
    .ev-actions-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px}
    @media(max-width:768px){.ev-actions-grid{grid-template-columns:1fr}}
    .ev-chk{display:flex;align-items:flex-start;gap:8px;font-size:.85rem;color:#3b4060;background:#f6f7fb;border:1px solid #e7e9f2;border-radius:10px;padding:9px 11px;cursor:pointer}
    .ev-chk input{margin-top:2px}
    .ev-btn{background:linear-gradient(135deg,#6c5ce7,#4b3fa7);color:#fff;border:0;border-radius:10px;padding:10px 20px;font-weight:600;font-size:.88rem;cursor:pointer;transition:.15s}
    .ev-btn:hover{filter:brightness(1.07)}
    .ev-btn.sm{padding:7px 13px;font-size:.8rem}
    .ev-btn.ghost{background:#eef0f6;color:#4b3fa7}
    .ev-btn.danger{background:#fee2e2;color:#b91c1c}
    .ev-btn.try{background:linear-gradient(135deg,#0ea5e9,#0369a1)}
    .ev-item{border:1px solid #e7e9f2;border-radius:12px;padding:14px 16px;margin-bottom:12px}
    .ev-item.off{opacity:.65}
    .ev-item-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .ev-item-top .nm{font-weight:700;color:#2b2f45}
    .ev-badge{font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:999px;text-transform:uppercase;letter-spacing:.03em}
    .ev-badge.on{background:#dcfce7;color:#15803d}
    .ev-badge.offb{background:#eef0f6;color:#6b7280}
    .ev-badge.type{background:#ede9fe;color:#5b21b6}
    .ev-url{display:flex;gap:8px;align-items:center;margin-top:10px}
    .ev-url code{flex:1;background:#0f172a;color:#8ee6a8;font-size:.78rem;padding:9px 12px;border-radius:9px;overflow-x:auto;white-space:nowrap}
    .ev-doc{margin-top:10px;background:#f6f7fb;border:1px solid #e7e9f2;border-radius:10px;padding:10px 12px}
    .ev-doc pre{margin:0;font-size:.76rem;color:#334155;white-space:pre-wrap;word-break:break-word}
    .ev-spacer{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .ev-right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
    .ev-msg{font-size:.85rem;border-radius:9px;padding:9px 12px;margin-top:10px;display:none}
    .ev-msg.ok{background:#dcfce7;color:#15803d;display:block}
    .ev-msg.err{background:#fee2e2;color:#b91c1c;display:block}
    .dark-mode .ev-card,.dark-mode .ev-item{background:#232a3d;border-color:#323b52}
    .dark-mode .ev-inp,.dark-mode .ev-sel,.dark-mode .ev-ta{background:#1b2133;border-color:#394363;color:#e8eaf3}
    .dark-mode .ev-chk{background:#1b2133;border-color:#394363;color:#cfd3e6}
    .dark-mode .ev-doc{background:#1b2133;border-color:#394363}
</style>

<div class="ev-wrap" id="eventsSection">
    <div class="ev-head">
        <h3><i class="fas fa-bolt me-2" style="color:#f59e0b"></i>Events &amp; Automations</h3>
        <p>Tell SafariChat when a lead actually becomes a customer — a form filled, a payment made, an order placed.
           When the event arrives, the AI stops selling, congratulates the customer, hands them to after-sale support,
           and alerts you. Connect any system (your website, LineShop, ShuleSoft, a payment gateway) with a webhook.</p>
    </div>

    @unless($eventsEnabled)
        <div class="ev-lock">
            <i class="fas fa-lock"></i>
            <div>
                <h4>Available on the Pro plan and above</h4>
                <p>Upgrade to automate the moment a lead converts — stop-selling, after-sale handoff, revenue tracking and webhooks.
                   <a href="{{ url('/billing') }}" style="color:#92400e;text-decoration:underline;font-weight:700">Upgrade now</a></p>
            </div>
        </div>
    @else

    {{-- After-sale support number --}}
    <div class="ev-card">
        <label class="ev-lbl">After-sale support agent (WhatsApp number)</label>
        <div class="ev-row" style="grid-template-columns:1fr auto;align-items:end">
            <input type="tel" id="evAfterSalePhone" class="ev-inp" placeholder="e.g. 2557XXXXXXXXX" value="{{ $afterSalePhone }}">
            <button type="button" class="ev-btn" onclick="evSaveAfterSale()">Save</button>
        </div>
        <div class="oc-hint" style="margin-top:8px"><i class="fas fa-info-circle me-1"></i>Once a customer is won, the AI stops selling and hands the conversation to this person.</div>
        <div class="ev-msg" id="evAfterSaleMsg" aria-live="polite"></div>
    </div>

    {{-- Existing events --}}
    <div class="ev-card">
        <label class="ev-lbl" style="font-size:.95rem;margin-bottom:14px">Your events</label>

        @forelse($eventConfigs as $cfg)
            <div class="ev-item {{ $cfg->is_enabled ? '' : 'off' }}" data-id="{{ $cfg->id }}">
                <div class="ev-item-top">
                    <span class="nm">{{ $cfg->name }}</span>
                    <span class="ev-badge type">{{ $eventTypes[$cfg->event_type] ?? $cfg->event_type }}</span>
                    <span class="ev-badge {{ $cfg->is_enabled ? 'on' : 'offb' }}">{{ $cfg->is_enabled ? 'Live' : 'Off' }}</span>
                    @if($cfg->received_count)<span style="font-size:.78rem;color:#9196ab">· {{ $cfg->received_count }} received</span>@endif
                    <div class="ev-right">
                        <button type="button" class="ev-btn ghost sm" onclick="evToggle({{ $cfg->id }})">{{ $cfg->is_enabled ? 'Disable' : 'Enable' }}</button>
                        <button type="button" class="ev-btn try sm" onclick="evTest({{ $cfg->id }})"><i class="fas fa-paper-plane me-1"></i>Test</button>
                        <button type="button" class="ev-btn danger sm" onclick="evDelete({{ $cfg->id }})">Delete</button>
                    </div>
                </div>

                <div class="ev-url">
                    <code id="evUrl{{ $cfg->id }}">{{ $cfg->webhookUrl() }}</code>
                    <button type="button" class="ev-btn ghost sm" onclick="evCopy('evUrl{{ $cfg->id }}')"><i class="fas fa-copy"></i></button>
                </div>

                <div class="ev-doc">
                    <pre>POST this JSON to the URL above (Content-Type: application/json):

{
  "phone": "2557XXXXXXXXX",            {{-- matched to your contact (or use "email") --}}
  "reference": "ORDER-1234",           {{-- optional: order / receipt / application no --}}
  "amount": 50000,                     {{-- optional: recorded as revenue --}}
  "currency": "TZS",                   {{-- optional --}}
  "occurred_at": "2026-10-25T10:00:00Z", {{-- optional --}}
  "note": "optional note"              {{-- optional --}}
}

Matches by: {{ $cfg->match_by === 'email' ? 'email' : 'phone' }}. On match → sets status
{{ $cfg->set_status ?: 'automatically' }} and runs: {{ implode(', ', array_map(fn($a) => $eventActions[$a] ?? $a, $cfg->actions ?? [])) }}.</pre>
                </div>
                <div class="ev-msg" id="evMsg{{ $cfg->id }}" aria-live="polite"></div>
            </div>
        @empty
            <p style="color:#9196ab;font-size:.88rem;margin:0 0 6px">No events yet. Add your first one below.</p>
        @endforelse
    </div>

    {{-- Add new event --}}
    <div class="ev-card">
        <label class="ev-lbl" style="font-size:.95rem;margin-bottom:14px">Add an event</label>
        <form id="evAddForm" onsubmit="return evAdd(event)">
            <div class="ev-row">
                <div>
                    <label class="ev-lbl">Name</label>
                    <input type="text" name="name" class="ev-inp" placeholder="e.g. Website application submitted" required>
                </div>
                <div>
                    <label class="ev-lbl">Event type</label>
                    <select name="event_type" class="ev-sel" onchange="evSyncStatusHint(this)">
                        @foreach($eventTypes as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="ev-row" style="margin-top:14px">
                <div>
                    <label class="ev-lbl">Mark customer as</label>
                    <select name="set_status" class="ev-sel">
                        <option value="">Automatic (form → Converted · payment → Closed)</option>
                        <option value="CONVERTED">CONVERTED (trial / signed up, not paying yet)</option>
                        <option value="CLOSED">CLOSED (paid)</option>
                    </select>
                </div>
                <div>
                    <label class="ev-lbl">Match customer by</label>
                    <select name="match_by" class="ev-sel">
                        <option value="phone">Phone number</option>
                        <option value="email">Email address</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:14px">
                <label class="ev-lbl">When this event fires, the AI should…</label>
                <div class="ev-actions-grid">
                    @foreach($eventActions as $ak => $al)
                        <label class="ev-chk"><input type="checkbox" name="actions[]" value="{{ $ak }}" checked><span>{{ $al }}</span></label>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:14px">
                <label class="ev-lbl">Congratulations message (optional)</label>
                <textarea name="message_template" class="ev-ta" placeholder="Thank you {name}! 🎉 We've received your order {reference}. Welcome aboard!"></textarea>
                <div class="oc-hint" style="margin-top:6px">Placeholders: <code>{name}</code> <code>{reference}</code> <code>{amount}</code> <code>{currency}</code></div>
            </div>

            <div class="ev-spacer">
                <button type="submit" class="ev-btn"><i class="fas fa-plus me-1"></i>Create event &amp; get webhook URL</button>
            </div>
            <div class="ev-msg" id="evAddMsg" aria-live="polite"></div>
        </form>
    </div>

    <script>
    (function () {
        window.evCsrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    })();

    function evShow(el, ok, text){ if(!el) return; el.className = 'ev-msg ' + (ok ? 'ok' : 'err'); el.textContent = text; }

    function evReq(url, method, body){
        return fetch(url, {
            method: method,
            headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': window.evCsrf, 'Accept':'application/json', 'Content-Type':'application/json' },
            body: body ? JSON.stringify(body) : null
        }).then(function(r){ return r.json().then(function(d){ return { ok:r.ok, d:d }; }); });
    }

    function evCopy(id){
        var c = document.getElementById(id); if(!c) return;
        navigator.clipboard.writeText(c.textContent.trim()).then(function(){
            c.style.outline = '2px solid #22c55e'; setTimeout(function(){ c.style.outline='none'; }, 700);
        });
    }

    function evSaveAfterSale(){
        var phone = document.getElementById('evAfterSalePhone').value.trim();
        var msg = document.getElementById('evAfterSaleMsg');
        evReq('{{ route('ai-agents.events.after-sale-phone') }}', 'PATCH', { after_sale_agent_phone: phone })
            .then(function(res){ evShow(msg, res.ok && res.d.success, res.d.message || (res.ok ? 'Saved.' : 'Could not save.')); })
            .catch(function(){ evShow(msg, false, 'Network error.'); });
    }

    function evAdd(e){
        e.preventDefault();
        var form = document.getElementById('evAddForm');
        var msg = document.getElementById('evAddMsg');
        var actions = Array.prototype.map.call(form.querySelectorAll('input[name="actions[]"]:checked'), function(i){ return i.value; });
        var body = {
            name: form.name.value, event_type: form.event_type.value,
            set_status: form.set_status.value || null, match_by: form.match_by.value,
            actions: actions, message_template: form.message_template.value || null, is_enabled: true
        };
        evReq('{{ route('ai-agents.events.store') }}', 'POST', body)
            .then(function(res){
                evShow(msg, res.ok && res.d.success, res.d.message || (res.ok ? 'Created.' : 'Could not create.'));
                if(res.ok && res.d.success){ setTimeout(function(){ location.reload(); }, 800); }
            })
            .catch(function(){ evShow(msg, false, 'Network error.'); });
        return false;
    }

    function evToggle(id){
        evReq('{{ url('ai-agents/events') }}/' + id + '/toggle', 'PATCH', {})
            .then(function(res){ if(res.ok){ location.reload(); } else { evShow(document.getElementById('evMsg'+id), false, (res.d && res.d.message) || 'Failed.'); } })
            .catch(function(){ evShow(document.getElementById('evMsg'+id), false, 'Network error.'); });
    }

    function evDelete(id){
        if(!confirm('Delete this event? Its webhook URL will stop working.')) return;
        evReq('{{ url('ai-agents/events') }}/' + id, 'DELETE', {})
            .then(function(res){ if(res.ok){ location.reload(); } else { evShow(document.getElementById('evMsg'+id), false, (res.d && res.d.message) || 'Failed.'); } })
            .catch(function(){ evShow(document.getElementById('evMsg'+id), false, 'Network error.'); });
    }

    function evTest(id){
        var msg = document.getElementById('evMsg'+id);
        evShow(msg, true, 'Sending a test event…');
        evReq('{{ url('ai-agents/events') }}/' + id + '/test', 'POST', {})
            .then(function(res){ evShow(msg, res.ok && res.d.success, (res.d && res.d.message) || (res.ok ? 'Test processed.' : 'Test failed.')); })
            .catch(function(){ evShow(msg, false, 'Network error.'); });
    }
    </script>
    @endunless
</div>
