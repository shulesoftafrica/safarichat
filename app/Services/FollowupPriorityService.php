<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decides WHICH leads the smart follow-up writes to first, and which it leaves alone.
 *
 * Before this existed the follow-up took any 20 leads that passed the basic filters, in database order, so a contact
 * who last replied 8 months ago was as likely to be picked as a customer who asked for a demo yesterday.
 *
 * Effective score (0-100+) = the HIGHEST of
 *   - the stored lead_score,
 *   - a floor for the lead's pipeline status (demo scheduled, proposal sent, negotiating ... are "almost closing"),
 *   - a floor for buying intent found in the customer's OWN messages (asked for a demo, asked to pay, asked the price,
 *     asked for a call/meeting),
 * plus a small recency bonus (replied within 7 / 30 days).
 * The stored lead_score alone is not enough because it is mostly status-driven (most leads sit at 15-25).
 * The conversion_probability column is deliberately NOT used: it is a batch value written when contacts were
 * imported (528 leads share 85, 405 share 88) and only 14 of the 115 leads scoring 60+ show any buying wording.
 *
 * Who is eligible:
 *   - replied within the freshness window (config outreach.smart_followup_freshness_days, default 30), OR
 *   - an old contact whose effective score is high (outreach.smart_followup_old_min_score, default 60):
 *     "asked for a demo and went quiet" / "was about to close and stopped" are still worth a message.
 *
 * Never contacted (skipped and logged with the reason):
 *   - status DO_NOT_CONTACT (the "do not engage" status), CLOSED, LOST, CONVERTED, CHURNED, HANDED_OFF,
 *   - churned leads, contacts with opt_out_status, contacts handed off to a human,
 *   - contacts who told us to stop in their own words ("not interested", "unsubscribe", "wrong number", "sitaki", ...).
 */
class FollowupPriorityService
{
    public const EXCLUDED_STATUSES = [
        Lead::STATUS_CLOSED, Lead::STATUS_LOST, Lead::STATUS_DO_NOT_CONTACT, Lead::STATUS_CONVERTED,
        Lead::STATUS_CHURNED, Lead::STATUS_HANDED_OFF,
    ];

    private const STATUS_FLOOR = [
        Lead::STATUS_NEGOTIATING => 90,
        Lead::STATUS_PROPOSAL_SENT => 85,
        Lead::STATUS_DEMO_SCHEDULED => 80,
        Lead::STATUS_PITCHED => 70,
        Lead::STATUS_QUALIFIED => 60,
        Lead::STATUS_NEEDS_ATTENTION => 45,
        Lead::STATUS_ENGAGED => 35,
    ];

    // Intent in the customer's own words (English + Swahili) => floor for the effective score.
    private const INTENT = [
        'demo'  => ['floor' => 70, 'regex' => 'demo|demonstration|onyesho|maonyesho'],
        'buy'   => ['floor' => 65, 'regex' => 'nataka|i want|we want|sign up|register|subscribe|jisajili|let.s start|tuanze|how do i pay|how to pay|payment|lipa|namna ya kulipa'],
        'call'  => ['floor' => 55, 'regex' => 'call me|call us|nipigie|nipigieni|piga simu|appointment|kutana|visit us|tembelea'],
        'price' => ['floor' => 50, 'regex' => 'price|pricing|cost|how much|bei |gharama|kiasi gani|sh[ .]?[0-9]'],
    ];

    // The contact asked us to stop, or it is the wrong person.
    private const STOP_REGEX = '^\s*(stop|acha|ondoa|usinitumie)\W*$|utapeli|scam|unsubscribe|remove me|do not (contact|message|disturb|text)|don.t (contact|message|disturb|text)|not interested|stop (messag|texting|sending)|wrong number|usinitumie|acha kunitumia|sitaki|nimekosea namba|hapana asante';

    /**
     * @param  Collection<int, Lead>  $leads  already filtered by the basic follow-up rules
     * @return array{ranked: array<int, array>, skipped: array<int, array>}  ranked = best first, each
     *         ['id','user_id','score','status','last_reply_days','reasons'[]]; skipped = id => reason
     */
    public function rank(Collection $leads): array
    {
        $freshDays = (int) config('outreach.smart_followup_freshness_days', 30);
        $oldMin = (int) config('outreach.smart_followup_old_min_score', 60);

        $signals = $this->messageSignals($leads->pluck('id')->all());
        $ranked = [];
        $skipped = [];

        foreach ($leads as $lead) {
            $sig = $signals[$lead->id] ?? ['stop' => false, 'demo' => false, 'buy' => false, 'call' => false, 'price' => false];

            if ($sig['stop']) {
                $skipped[$lead->id] = 'contact asked us to stop / not interested (own words)';
                continue;
            }

            $reasons = [];
            $score = (int) ($lead->lead_score ?? 0);
            $reasons['lead_score'] = $score;

            $floor = self::STATUS_FLOOR[$lead->status] ?? 0;
            if ($floor > $score) {
                $score = $floor;
                $reasons['status_floor'] = $lead->status . '=' . $floor;
            }

            $intents = [];
            foreach (self::INTENT as $name => $cfg) {
                if ($sig[$name]) {
                    $intents[] = $name;
                    if ($cfg['floor'] > $score) {
                        $score = $cfg['floor'];
                        $reasons['intent_floor'] = $name . '=' . $cfg['floor'];
                    }
                }
            }
            if ($intents) {
                $reasons['intent'] = $intents;
            }

            $days = $lead->last_reply_at ? (int) $lead->last_reply_at->diffInDays(now()) : null;
            if ($days !== null && $days <= 7) {
                $score += 10;
                $reasons['recency'] = '+10 (replied within 7 days)';
            } elseif ($days !== null && $days <= $freshDays) {
                $score += 5;
                $reasons['recency'] = '+5 (replied within ' . $freshDays . ' days)';
            }

            $fresh = $days !== null && $days <= $freshDays;
            if (!$fresh && $score < $oldMin) {
                $skipped[$lead->id] = "last reply {$days} days ago and score {$score} is below {$oldMin} (old contacts need high interest)";
                continue;
            }

            $ranked[] = [
                'id' => $lead->id,
                'user_id' => $lead->user_id,
                'score' => $score,
                'status' => $lead->status,
                'last_reply_days' => $days,
                'fresh' => $fresh,
                'reasons' => $reasons,
            ];
        }

        // The same person often exists as several contact records ("+255...", "0...", "255..."). Do not write to a lead
        // whose number was already followed up (7 days) or was active (3 days) under another record.
        if ($ranked) {
            $blocked = array_flip($this->contactedViaOtherRecord(array_column($ranked, 'id')));
            if ($blocked) {
                $ranked = array_values(array_filter($ranked, function ($r) use ($blocked, &$skipped) {
                    if (isset($blocked[$r['id']])) {
                        $skipped[$r['id']] = 'same phone number already followed up or active under another contact record';
                        return false;
                    }
                    return true;
                }));
            }
        }

        // Highest effective score first; for equal scores the most recent reply first.
        usort($ranked, function ($a, $b) {
            return [$b['score'], -($b['last_reply_days'] ?? PHP_INT_MAX)] <=> [$a['score'], -($a['last_reply_days'] ?? PHP_INT_MAX)];
        });

        return ['ranked' => $ranked, 'skipped' => $skipped];
    }

    /** Lead ids whose phone number (last 9 digits, same business) has another record that was followed up / active recently. */
    private function contactedViaOtherRecord(array $leadIds): array
    {
        if (!$leadIds) {
            return [];
        }

        $rows = DB::select(
            "with k as (
                select bc.id as cid, bc.business_id, right(regexp_replace(bc.guest_phone, '[^0-9]', '', 'g'), 9) as pkey
                from business_contacts bc where bc.guest_phone is not null
             )
             select distinct l.id
             from leads l
             join k on k.cid = l.business_contact_id
             join k k2 on k2.pkey = k.pkey and k2.business_id = k.business_id and k2.cid <> k.cid
             join leads l2 on l2.business_contact_id = k2.cid and l2.user_id = l.user_id
             where l.id = any(?::bigint[])
               and (l2.follow_up_sent_at > now() - interval '7 days' or l2.last_interaction_at > now() - interval '3 days')",
            ['{' . implode(',', array_map('intval', $leadIds)) . '}']
        );

        return array_map(fn ($r) => (int) $r->id, $rows);
    }

    /** One query for all candidate leads: which intent / stop words appear in what the customer wrote. */
    private function messageSignals(array $leadIds): array
    {
        if (!$leadIds) {
            return [];
        }

        $select = ['c.lead_id', 'bool_or(c.customer_message ~* ?) as stop'];
        $bindings = [self::STOP_REGEX];
        foreach (self::INTENT as $name => $cfg) {
            $select[] = "bool_or(c.customer_message ~* ?) as i_{$name}";
            $bindings[] = $cfg['regex'];
        }
        $bindings[] = '{' . implode(',', array_map('intval', $leadIds)) . '}';

        $rows = DB::select(
            'select ' . implode(', ', $select) . ' from conversations c where c.lead_id = any(?::bigint[]) and c.customer_message is not null group by c.lead_id',
            $bindings
        );

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->lead_id] = [
                'stop' => (bool) $r->stop, 'demo' => (bool) $r->i_demo, 'buy' => (bool) $r->i_buy,
                'call' => (bool) $r->i_call, 'price' => (bool) $r->i_price,
            ];
        }
        return $out;
    }
}
