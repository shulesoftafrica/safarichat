<?php

namespace App\Http\Controllers;

use App\Models\BusinessContact;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Customer Engagements console — the conversations the AI is actively running
 * across WhatsApp, Email and SMS. A master list of live engagements on the
 * left, and the selected customer's unified cross-channel thread on the right,
 * with a one-click "Take over" to pull a conversation to a human.
 */
class EngagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Channels a conversation can run on, in display order. */
    private const CHANNELS = [
        'whatsapp' => 'WhatsApp',
        'email'    => 'Email',
        'sms'      => 'SMS',
        'bulk_sms' => 'Bulk SMS',
    ];

    public function index()
    {
        $business = Auth::user()->business;
        abort_if(!$business, 403, 'No business for this account.');

        $engagements = $this->buildEngagements($business->id);

        // Channel filter counts (All + per channel).
        $counts = ['all' => count($engagements)];
        foreach (array_keys(self::CHANNELS) as $key) {
            $counts[$key] = collect($engagements)->where('channel_key', $key)->count();
        }

        return view('engagements.index', [
            'engagements' => $engagements,
            'counts'      => $counts,
            'channels'    => self::CHANNELS,
        ]);
    }

    /**
     * The unified cross-channel thread for one customer (right pane), returned
     * as rendered HTML for the console to drop in.
     */
    public function thread($contactId)
    {
        $business = Auth::user()->business;
        abort_if(!$business, 403);

        $contact = BusinessContact::where('id', $contactId)
            ->where('business_id', $business->id)->first();
        abort_if(!$contact, 404, 'Customer not found.');

        $lead = $contact->lead;
        $messages = $this->buildThread($contact);
        $header   = $this->threadHeader($contact, $lead, $messages);

        $html = view('engagements.partials.thread', [
            'contact'  => $contact,
            'lead'     => $lead,
            'messages' => $messages,
            'header'   => $header,
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'header' => $header]);
    }

    /**
     * Pull the conversation to a human — hand the contact off to the current
     * user so the AI steps back and the person takes over.
     */
    public function takeOver(Request $request, $contactId)
    {
        $business = Auth::user()->business;
        abort_if(!$business, 403);

        $contact = BusinessContact::where('id', $contactId)
            ->where('business_id', $business->id)->first();
        abort_if(!$contact, 404, 'Customer not found.');

        // Set handoff fields directly (priority_level is an integer column with
        // its own default — leave it untouched).
        $contact->forceFill([
            'handoff_status'       => 'handed_off',
            'handoff_reason'       => 'Taken over from the Engagements console',
            'handoff_requested_at' => now(),
            'handoff_assigned_at'  => now(),
            'assigned_agent_id'    => Auth::id(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'You have taken over this conversation. The AI will stop replying.',
        ]);
    }

    // ---------------------------------------------------------------------
    // List building
    // ---------------------------------------------------------------------

    private function buildEngagements(int $businessId): array
    {
        $terminal = [
            Lead::STATUS_CONVERTED, Lead::STATUS_CLOSED, Lead::STATUS_LOST,
            Lead::STATUS_DO_NOT_CONTACT, Lead::STATUS_CHURNED,
        ];

        // Pick the leads that actually have recent activity — conversations or
        // inbound/outbound messages — rather than every lead. Won/dead leads are
        // not "running conversations", so they're excluded.
        $convLeadIds = DB::table('conversations')
            ->join('leads', 'leads.id', '=', 'conversations.lead_id')
            ->where('leads.business_id', $businessId)
            ->whereNotIn('leads.status', $terminal)
            ->orderByDesc('conversations.created_at')
            ->limit(400)
            ->pluck('conversations.lead_id');

        $inLeadIds = DB::table('incoming_messages')
            ->join('leads', 'leads.business_contact_id', '=', 'incoming_messages.business_contact_id')
            ->where('leads.business_id', $businessId)
            ->whereNotIn('leads.status', $terminal)
            ->orderByDesc('incoming_messages.created_at')
            ->limit(400)
            ->pluck('leads.id');

        $outLeadIds = DB::table('outgoing_messages')
            ->join('leads', 'leads.business_contact_id', '=', 'outgoing_messages.business_contact_id')
            ->where('leads.business_id', $businessId)
            ->whereNotIn('leads.status', $terminal)
            ->orderByDesc('outgoing_messages.created_at')
            ->limit(400)
            ->pluck('leads.id');

        $leadIds = $convLeadIds->merge($inLeadIds)->merge($outLeadIds)->unique()->take(120)->all();
        if (empty($leadIds)) {
            return [];
        }

        $leads = Lead::whereIn('id', $leadIds)
            ->whereNotNull('business_contact_id')
            ->with(['contact', 'leadProducts.product'])
            ->get();

        $contactIds = $leads->pluck('business_contact_id')->filter()->unique()->values()->all();
        if (empty($contactIds)) {
            return [];
        }

        $lastOut = $this->latestPerContact('outgoing_messages', $contactIds, [
            'message_body', 'message', 'selected_channel', 'is_ai_generated',
            'read_at', 'delivered_at', 'sent_at',
        ]);
        $lastIn = $this->latestPerContact('incoming_messages', $contactIds, [
            'message_body', 'received_at', 'message_timestamp',
        ]);
        // AI turns are stored in `conversations` (customer_message + ai_response),
        // so use the latest conversation per lead as a content fallback.
        $lastConv = $this->latestConversationPerLead($leads->pluck('id')->all());

        $items = [];
        foreach ($leads as $lead) {
            $cid = $lead->business_contact_id;
            $out = $lastOut[$cid] ?? null;
            $in  = $lastIn[$cid] ?? null;
            $conv = $lastConv[$lead->id] ?? null;

            $outAt = $out ? Carbon::parse($out->created_at) : null;
            $inAt  = $in ? Carbon::parse($in->created_at) : null;
            $convAt = $conv ? Carbon::parse($conv->created_at) : null;

            // Build candidates (timestamp, snippet, direction) from every source
            // and pick the most recent — that is the "last thing that happened".
            $candidates = [];
            if ($inAt)  { $candidates[] = [$inAt,  (string) $in->message_body, 'in']; }
            if ($out)   { $candidates[] = [$outAt, 'AI: ' . (string) ($out->message_body ?: $out->message), 'out']; }
            if ($conv) {
                if (!empty($conv->ai_response)) {
                    $candidates[] = [$convAt, 'AI: ' . (string) $conv->ai_response, 'out'];
                } elseif (!empty($conv->customer_message)) {
                    $candidates[] = [$convAt, (string) $conv->customer_message, 'in'];
                }
            }

            // No real activity on any channel — not a running engagement.
            if (empty($candidates)) {
                continue;
            }

            usort($candidates, fn ($a, $b) => $b[0]->timestamp <=> $a[0]->timestamp);
            [$at, $snippet, $dir] = $candidates[0];
            $customerSpokeLast = $dir === 'in';

            $channelKey = $this->normalizeChannel($out->selected_channel ?? null);
            $product    = optional($lead->getPrimaryProduct())->name;

            $phone = $lead->contact->guest_phone ?? $lead->phone_number ?? null;

            $items[] = [
                'contact_id'  => $cid,
                'lead_id'     => $lead->id,
                'phone'       => $phone,
                'phone_key'   => $phone ? preg_replace('/\D+/', '', $phone) : null,
                'name'        => $lead->contact->guest_name ?? $lead->name ?? 'Unknown',
                'initials'    => $this->initials($lead->contact->guest_name ?? $lead->name),
                'snippet'     => $this->truncate($snippet, 90),
                'at'          => $at,
                'at_label'    => $this->relativeLabel($at),
                'channel_key' => $channelKey,
                'channel'     => self::CHANNELS[$channelKey] ?? 'WhatsApp',
                'status'      => $this->engagementStatus($lead, $customerSpokeLast),
                'product'     => $product,
                'unread'      => $customerSpokeLast ? 1 : 0,
                'handed_off'  => in_array($lead->contact->handoff_status ?? '', ['handed_off', 'pending_handoff'], true),
            ];
        }

        // Most recent first.
        usort($items, fn ($a, $b) => $b['at']->timestamp <=> $a['at']->timestamp);

        // One card per customer. A person can have more than one lead/contact row
        // (e.g. separate campaigns, or a re-created contact) but is still one
        // conversation to the user — collapse by contact id and by phone number,
        // keeping the most recent (we're already sorted newest-first).
        $seenContact = [];
        $seenPhone   = [];
        $deduped = [];
        foreach ($items as $it) {
            if (isset($seenContact[$it['contact_id']])) {
                continue;
            }
            if (!empty($it['phone_key']) && isset($seenPhone[$it['phone_key']])) {
                continue;
            }
            $seenContact[$it['contact_id']] = true;
            if (!empty($it['phone_key'])) {
                $seenPhone[$it['phone_key']] = true;
            }
            $deduped[] = $it;
        }

        return $deduped;
    }

    /**
     * Latest conversation row per lead (Postgres DISTINCT ON).
     */
    private function latestConversationPerLead(array $leadIds)
    {
        if (empty($leadIds)) {
            return collect();
        }

        return DB::table('conversations')
            ->whereIn('lead_id', $leadIds)
            ->orderBy('lead_id')->orderByDesc('created_at')
            ->get([
                DB::raw('DISTINCT ON (lead_id) lead_id'),
                'created_at', 'customer_message', 'ai_response',
            ])
            ->keyBy('lead_id');
    }

    /**
     * Latest row per business_contact_id (Postgres DISTINCT ON).
     */
    private function latestPerContact(string $table, array $contactIds, array $columns)
    {
        $cols = array_merge(
            [DB::raw('DISTINCT ON (business_contact_id) business_contact_id'), 'created_at'],
            $columns
        );

        return DB::table($table)
            ->whereIn('business_contact_id', $contactIds)
            ->orderBy('business_contact_id')
            ->orderByDesc('created_at')
            ->get($cols)
            ->keyBy('business_contact_id');
    }

    private function engagementStatus(Lead $lead, bool $customerSpokeLast): string
    {
        if (($lead->contact->handoff_status ?? '') === 'handed_off') {
            return 'With agent';
        }
        if ($lead->is_churned || ($lead->chase_count ?? 0) > 0 && !$customerSpokeLast) {
            return 'Re-engaging';
        }
        return $customerSpokeLast ? 'Awaiting reply' : 'AI engaging';
    }

    // ---------------------------------------------------------------------
    // Thread building
    // ---------------------------------------------------------------------

    private function buildThread(BusinessContact $contact): array
    {
        $cid = $contact->id;

        $out = DB::table('outgoing_messages')
            ->where('business_contact_id', $cid)
            ->orderByDesc('created_at')->limit(60)
            ->get(['message_body', 'message', 'selected_channel', 'is_ai_generated',
                   'read_at', 'delivered_at', 'sent_at', 'created_at']);

        $in = DB::table('incoming_messages')
            ->where('business_contact_id', $cid)
            ->orderByDesc('created_at')->limit(60)
            ->get(['message_body', 'sender_name', 'received_at', 'created_at']);

        $entries = [];
        foreach ($out as $m) {
            $body = $m->message_body ?: $m->message;
            if (!$body) { continue; }
            $entries[] = [
                'direction' => 'out',
                'channel'   => self::CHANNELS[$this->normalizeChannel($m->selected_channel)] ?? 'WhatsApp',
                'text'      => $body,
                'at'        => Carbon::parse($m->created_at),
                'receipt'   => $this->receipt($m),
            ];
        }
        foreach ($in as $m) {
            if (!$m->message_body) { continue; }
            $entries[] = [
                'direction' => 'in',
                'channel'   => 'WhatsApp',
                'text'      => $m->message_body,
                'at'        => Carbon::parse($m->created_at),
                'receipt'   => null,
            ];
        }

        // Fall back to the AI conversation log when there are no channel messages
        // (some leads' turns live only in `conversations`). No channel there, so
        // these render as WhatsApp (the default channel).
        if (empty($entries) && $contact->lead) {
            $convos = DB::table('conversations')
                ->where('lead_id', $contact->lead->id)
                ->orderBy('created_at')->limit(60)
                ->get(['customer_message', 'ai_response', 'message_content', 'sender_type', 'created_at']);
            foreach ($convos as $c) {
                $at = Carbon::parse($c->created_at);
                if (!empty($c->customer_message)) {
                    $entries[] = ['direction' => 'in', 'channel' => 'WhatsApp', 'text' => $c->customer_message, 'at' => $at, 'receipt' => null];
                }
                if (!empty($c->ai_response)) {
                    $entries[] = ['direction' => 'out', 'channel' => 'WhatsApp', 'text' => $c->ai_response, 'at' => $at, 'receipt' => 'Sent'];
                }
                if (empty($c->customer_message) && empty($c->ai_response) && !empty($c->message_content)) {
                    $entries[] = [
                        'direction' => ($c->sender_type === 'customer') ? 'in' : 'out',
                        'channel' => 'WhatsApp', 'text' => $c->message_content, 'at' => $at,
                        'receipt' => ($c->sender_type === 'customer') ? null : 'Sent',
                    ];
                }
            }
        }

        usort($entries, fn ($a, $b) => $a['at']->timestamp <=> $b['at']->timestamp);
        return $entries;
    }

    private function threadHeader(BusinessContact $contact, ?Lead $lead, array $messages): array
    {
        $customerSpokeLast = !empty($messages) && end($messages)['direction'] === 'in';
        $handedOff = ($contact->handoff_status ?? '') === 'handed_off';

        return [
            'name'    => $contact->guest_name ?? ($lead->name ?? 'Unknown'),
            'initials'=> $this->initials($contact->guest_name ?? ($lead->name ?? '')),
            'company' => $lead->company_name ?? null,
            'phone'   => $contact->guest_phone ?? ($lead->phone_number ?? null),
            'product' => optional($lead ? $lead->getPrimaryProduct() : null)->name,
            'status'  => $lead ? $this->engagementStatus($lead, $customerSpokeLast) : ($handedOff ? 'With agent' : 'AI engaging'),
            'handed_off' => $handedOff,
            'next'    => $this->nextAction($lead, $customerSpokeLast, $handedOff),
        ];
    }

    private function nextAction(?Lead $lead, bool $customerSpokeLast, bool $handedOff): ?string
    {
        if ($handedOff) {
            return 'Handed to a human agent — the AI has stepped back.';
        }
        if ($customerSpokeLast) {
            return 'The AI is preparing a reply to the customer’s latest message.';
        }
        if ($lead && $lead->ai_waiting_since) {
            $when = Carbon::parse($lead->ai_waiting_since)->addHours(24);
            return 'Follow up if no reply by ' . $when->format('g:i A, M j') . '.';
        }
        return 'The AI will follow up automatically if the customer goes quiet.';
    }

    // ---------------------------------------------------------------------
    // Small helpers
    // ---------------------------------------------------------------------

    private function receipt($m): string
    {
        if (!empty($m->read_at)) {
            return ($this->normalizeChannel($m->selected_channel) === 'email') ? 'Opened' : 'Read';
        }
        if (!empty($m->delivered_at)) { return 'Delivered'; }
        if (!empty($m->sent_at)) { return 'Sent'; }
        return 'Sent';
    }

    private function normalizeChannel(?string $raw): string
    {
        $raw = strtolower((string) $raw);
        if ($raw === '' ) { return 'whatsapp'; }
        if (str_contains($raw, 'bulk')) { return 'bulk_sms'; }
        if (str_contains($raw, 'email') || str_contains($raw, 'mail')) { return 'email'; }
        if (str_contains($raw, 'sms') || str_contains($raw, 'phone')) { return 'sms'; }
        if (str_contains($raw, 'whats')) { return 'whatsapp'; }
        return 'whatsapp';
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') { return '?'; }
        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0], 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return strtoupper($first . $last);
    }

    private function truncate(string $text, int $len): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
    }

    private function relativeLabel(?Carbon $at): string
    {
        if (!$at) { return ''; }
        $now = Carbon::now();
        if ($at->isToday()) { return $at->format('H:i'); }
        if ($at->isYesterday()) { return 'Yesterday'; }
        if ($at->greaterThan($now->copy()->subDays(7))) { return $at->format('l'); }
        return $at->format('j M');
    }
}
