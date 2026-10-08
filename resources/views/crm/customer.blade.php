@extends('layouts.app')
@section('content')
@php
    $leadStatus = $lead->status ?? 'NEW';
    $statusHex = [
        'NEW' => '#2563eb', 'OUTREACHED' => '#7c3aed', 'REPLIED' => '#0891b2',
        'ENGAGED' => '#16a34a', 'QUALIFIED' => '#d97706', 'PITCHED' => '#ea580c',
        'DEMO_SCHEDULED' => '#9333ea', 'PROPOSAL_SENT' => '#0d9488',
        'NEGOTIATING' => '#4f46e5', 'CLOSED' => '#15803d', 'LOST' => '#dc2626',
        'HANDED_OFF' => '#0284c7', 'DO_NOT_CONTACT' => '#374151',
        'NEEDS_ATTENTION' => '#ca8a04', 'CONVERTED' => '#059669', 'CHURNED' => '#b91c1c',
    ];
    $curHex = $statusHex[$leadStatus] ?? '#6c757d';
    $handoff = $contact->handoff_status ?? 'ai';
    if ($handoff === 'pending_handoff') { $hLabel = 'Pending handoff'; $hHex = '#d97706'; }
    elseif (in_array($handoff, ['handed_off','completed'], true)) { $hLabel = ($contact->assignedAgent->name ?? 'Sales person'); $hHex = '#0d9488'; }
    else { $hLabel = 'AI'; $hHex = '#2563eb'; }
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('guest.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="las la-arrow-left"></i> Back to Customers
        </a>
        <h4 class="mb-0"><i class="mdi mdi-account-box-outline mr-1"></i> Customer Profile</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        {{-- LEFT: details + convert + products + stats --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 mb-1">{{ $contact->guest_name ?: 'Unknown Contact' }}</h4>
                    <p class="mb-2">
                        <span class="badge" style="background:{{ $curHex }};color:#fff;padding:5px 10px;">{{ str_replace('_',' ', $leadStatus) }}</span>
                        <span class="badge" style="background:{{ $hHex }};color:#fff;padding:5px 10px;"><i class="mdi mdi-robot-outline mr-1"></i>{{ $hLabel }}</span>
                    </p>
                    <ul class="list-unstyled mb-0 text-muted">
                        <li><i class="mdi mdi-phone mr-2"></i>{{ $contact->guest_phone ?: '—' }}</li>
                        <li><i class="mdi mdi-email-outline mr-2"></i>{{ $contact->guest_email ?: '—' }}</li>
                        <li><i class="mdi mdi-calendar mr-2"></i>Added {{ \Carbon\Carbon::parse($contact->created_at)->format('d M Y') }}</li>
                        @if($lead)<li><i class="mdi mdi-star-outline mr-2"></i>Lead score: <strong>{{ $lead->lead_score ?? 0 }}</strong></li>@endif
                    </ul>
                </div>
            </div>

            {{-- Convert / move pipeline --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="mt-0 mb-3"><i class="mdi mdi-swap-horizontal-bold mr-1"></i> Convert / Move stage</h5>
                    <form method="post" action="{{ route('crm.customer.status', $contact->id) }}">
                        @csrf
                        <div class="form-group">
                            <select name="status" class="form-control">
                                @foreach($statuses as $val => $label)
                                    <option value="{{ $val }}" {{ $leadStatus === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Update stage</button>
                    </form>
                    <div class="mt-3 d-flex flex-wrap" style="gap:6px;">
                        @foreach(['QUALIFIED'=>'Qualify','CONVERTED'=>'Mark as Customer','LOST'=>'Mark Lost'] as $val=>$label)
                        <form method="post" action="{{ route('crm.customer.status', $contact->id) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="{{ $val }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ $label }}</button>
                        </form>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Products --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="mt-0 mb-3"><i class="mdi mdi-package-variant-closed mr-1"></i> Products / Interest</h5>
                    @forelse($products as $p)
                        <span class="badge badge-light border mr-1 mb-1" style="padding:6px 10px;">{{ $p->name }}</span>
                    @empty
                        <span class="text-muted">No product linked.</span>
                    @endforelse
                </div>
            </div>

            {{-- Stats --}}
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4"><h4 class="mb-0">{{ $stats['engagements'] }}</h4><small class="text-muted">Engagements</small></div>
                        <div class="col-4"><h4 class="mb-0">{{ $stats['sent'] }}</h4><small class="text-muted">Sent</small></div>
                        <div class="col-4"><h4 class="mb-0">{{ $stats['received'] }}</h4><small class="text-muted">Replies</small></div>
                    </div>
                    @if($stats['last_at'])
                        <p class="text-center text-muted mt-2 mb-0"><small>Last engagement: {{ \Carbon\Carbon::parse($stats['last_at'])->diffForHumans() }}</small></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: WhatsApp engagement history --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="mt-0 mb-3"><i class="mdi mdi-whatsapp mr-1"></i> WhatsApp Engagement History</h5>
                    <div style="max-height:640px;overflow-y:auto;padding:4px;">
                        @forelse($timeline as $msg)
                            @if(($msg['direction'] ?? '') === 'activity')
                                {{-- Logged sales activity (call / visit / ...) --}}
                                <div class="d-flex mb-2 justify-content-center">
                                    <div style="max-width:92%;padding:8px 14px;border-radius:10px;
                                                background:#fff7e6;color:#8a6d1b;border:1px solid #ffe0a3;">
                                        <div style="white-space:pre-wrap;word-break:break-word;">
                                            <i class="mdi mdi-clipboard-text-outline mr-1"></i>{{ $msg['text'] }}
                                        </div>
                                        <div class="text-right" style="font-size:0.7rem;color:#b08a2e;margin-top:2px;">
                                            {{ $msg['by'] ?? 'Sales' }} · {{ \Carbon\Carbon::parse($msg['at'])->format('d M, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                            @php $out = $msg['direction'] === 'out'; @endphp
                            <div class="d-flex mb-2 {{ $out ? 'justify-content-end' : 'justify-content-start' }}">
                                <div style="max-width:78%;padding:8px 12px;border-radius:10px;
                                            background:{{ $out ? '#dcf8c6' : '#f1f0f0' }};color:#111;
                                            border:1px solid rgba(0,0,0,0.05);">
                                    <div style="white-space:pre-wrap;word-break:break-word;">{{ $msg['text'] }}</div>
                                    <div class="text-right" style="font-size:0.7rem;color:#667781;margin-top:2px;">
                                        {{ $out ? 'You / AI' : 'Customer' }} · {{ \Carbon\Carbon::parse($msg['at'])->format('d M, H:i') }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                            <p class="text-muted text-center py-4">No WhatsApp engagements recorded yet for this customer.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
