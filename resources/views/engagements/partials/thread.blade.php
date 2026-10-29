@php
    $chColors = ['WhatsApp'=>'#25d366','Email'=>'#3b82f6','SMS'=>'#6366f1','Bulk SMS'=>'#f59e0b'];
    $chIcons  = ['WhatsApp'=>'fab fa-whatsapp','Email'=>'fas fa-envelope','SMS'=>'fas fa-mobile-alt','Bulk SMS'=>'fas fa-bullhorn'];
    $statusColors = ['AI engaging'=>'#16a34a','Awaiting reply'=>'#d97706','Re-engaging'=>'#2563eb','With agent'=>'#7c3aed'];
    $stColor = $statusColors[$header['status']] ?? '#6b7280';
    $lastDay = null;
@endphp

<style>
    .et-head{display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #eef0f6;}
    .dark-mode .et-head{border-color:#323b52;}
    .et-av{width:42px;height:42px;border-radius:50%;background:#6c5ce7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;}
    .et-h-main{min-width:0;flex:1;}
    .et-h-name{font-weight:700;color:#232746;font-size:1rem;}
    .dark-mode .et-h-name{color:#e8eaf3;}
    .et-h-sub{color:#9aa0b8;font-size:.8rem;margin-top:1px;}
    .et-h-sub .st{font-weight:700;}
    .et-body{flex:1;overflow-y:auto;max-height:58vh;padding:16px 18px;background:#f7f8fc;}
    .dark-mode .et-body{background:#1b2030;}
    .et-day{text-align:center;margin:14px 0;}
    .et-day span{background:#e6e9f2;color:#6b7190;font-size:.72rem;font-weight:600;border-radius:999px;padding:3px 12px;}
    .dark-mode .et-day span{background:#2c3447;color:#aab0c7;}
    .et-row{display:flex;margin-bottom:10px;}
    .et-row.out{justify-content:flex-end;}
    .et-bubble{max-width:78%;border-radius:12px;padding:9px 12px;position:relative;}
    .et-row.out .et-bubble{background:#dcf8c6;color:#122;border:1px solid #cdeeb4;}
    .et-row.in .et-bubble{background:#fff;color:#122;border:1px solid #e6e8f0;}
    .dark-mode .et-row.out .et-bubble{background:#1f3d2a;color:#e8f6e9;border-color:#2c5338;}
    .dark-mode .et-row.in .et-bubble{background:#232a3d;color:#e8eaf3;border-color:#323b52;}
    .et-chan{display:inline-flex;align-items:center;gap:5px;font-size:.68rem;font-weight:700;color:#fff;border-radius:5px;padding:1px 7px;margin-bottom:5px;}
    .et-sender{font-size:.72rem;font-weight:700;color:#4b3fa7;margin-left:6px;}
    .et-text{white-space:pre-wrap;word-break:break-word;font-size:.88rem;line-height:1.4;}
    .et-meta{text-align:right;font-size:.68rem;color:#8a90a8;margin-top:3px;}
    .et-footer{border-top:1px solid #eef0f6;padding:12px 18px;display:flex;align-items:center;gap:12px;background:#fff;}
    .dark-mode .et-footer{background:#232a3d;border-color:#323b52;}
    .et-footer .ic{width:34px;height:34px;border-radius:9px;background:#f0eefe;color:#6c5ce7;display:flex;align-items:center;justify-content:center;flex:0 0 34px;}
    .et-footer .txt{min-width:0;flex:1;}
    .et-footer .txt .t{font-weight:700;font-size:.82rem;color:#232746;}
    .dark-mode .et-footer .txt .t{color:#e8eaf3;}
    .et-footer .txt .n{font-size:.76rem;color:#8a90a8;}
    .eng-takeover{background:linear-gradient(135deg,#6c5ce7,#4b3fa7);color:#fff;border:0;border-radius:10px;padding:9px 16px;font-weight:600;font-size:.84rem;cursor:pointer;white-space:nowrap;flex:0 0 auto;transition:.15s;}
    .eng-takeover:hover{filter:brightness(1.08);}
    .eng-takeover.done{background:#16a34a;cursor:default;}
    .eng-takeover[disabled]{opacity:.7;}
</style>

<div class="et-head">
    <div class="et-av">{{ $header['initials'] }}</div>
    <div class="et-h-main">
        <div class="et-h-name">{{ $header['name'] }}</div>
        <div class="et-h-sub">
            <span class="st" style="color:{{ $stColor }}">{{ $header['status'] }}</span>
            @if($header['company']) · {{ $header['company'] }}@endif
            @if($header['phone']) · {{ $header['phone'] }}@endif
            @if($header['product']) · {{ \Illuminate\Support\Str::limit($header['product'], 28) }}@endif
        </div>
    </div>
    @if($lead)
        <a href="{{ route('crm.customer', $contact->id) }}" class="btn btn-sm btn-light" title="Open full CRM profile"><i class="fas fa-user"></i></a>
    @endif
</div>

<div class="et-body">
    @forelse($messages as $m)
        @php
            $day = $m['at']->isToday() ? 'Today' : ($m['at']->isYesterday() ? 'Yesterday' : $m['at']->format('l, M j'));
            $chColor = $chColors[$m['channel']] ?? '#25d366';
            $chIcon  = $chIcons[$m['channel']] ?? 'fab fa-whatsapp';
        @endphp
        @if($day !== $lastDay)
            <div class="et-day"><span>{{ $day }}</span></div>
            @php $lastDay = $day; @endphp
        @endif
        <div class="et-row {{ $m['direction'] }}">
            <div class="et-bubble">
                <div>
                    <span class="et-chan" style="background:{{ $chColor }}"><i class="{{ $chIcon }}"></i> {{ $m['channel'] }}</span>
                    @if($m['direction']==='out')<span class="et-sender">Safari AI</span>@endif
                </div>
                <div class="et-text">{{ $m['text'] }}</div>
                <div class="et-meta">
                    {{ $m['at']->format('H:i') }}
                    @if($m['direction']==='out' && !empty($m['receipt'])) · {{ $m['receipt'] }}@endif
                </div>
            </div>
        </div>
    @empty
        <div class="eng-thread-placeholder" style="min-height:40vh">No messages recorded on this conversation yet.</div>
    @endforelse
</div>

<div class="et-footer">
    <div class="ic"><i class="fas {{ $header['handed_off'] ? 'fa-user-check' : 'fa-robot' }}"></i></div>
    <div class="txt">
        <div class="t">{{ $header['handed_off'] ? 'A human agent is handling this conversation' : 'AI is handling this conversation' }}</div>
        <div class="n eng-footer-note">{{ $header['next'] }}</div>
    </div>
    @unless($header['handed_off'])
        <button class="eng-takeover" type="button">Take over</button>
    @else
        <button class="eng-takeover done" type="button" disabled>✓ With agent</button>
    @endunless
</div>
