<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Merges contact records (and their leads) that are the SAME person stored under different phone formats
 * ("+255710242355", "0710242355", "255710242355"): same business + same last 9 digits.
 *
 * Why: WhatsApp delivers "255...", contacts were stored in several formats, and an exact-text lookup created a second
 * contact + lead for a person who already had one, so the AI answered "Yes, go on" with no history. The lookup is fixed
 * (AiWhatsAppService::findContactByPhone); this cleans up the records created before the fix.
 *
 * What it does per group, in ONE transaction:
 *   - survivor contact = the one with the most recent conversation (tie: lowest id);
 *   - survivor lead per user = the lead with the most recent conversation (tie: latest interaction, lowest id);
 *   - every other lead of that user is merged into the survivor lead: conversations, appointments, handoffs, booking slots,
 *     commitments, sales activities, conversion events, missed automations, products and offer progress are MOVED;
 *     status = the furthest in the pipeline (DO_NOT_CONTACT always wins), score = highest, dates = latest, counters summed,
 *     empty fields filled from the other lead, notes appended;
 *   - every other contact is merged into the survivor contact: incoming/outgoing messages, queue, payments, nurture rows,
 *     channel preferences/metrics, activities are MOVED; opt-out and "handed off" are kept if ANY record had them;
 *   - the emptied duplicate leads/contacts are deleted.
 * Before anything changes, the duplicate rows and the ids of what moves are written to contact_merge_backup
 * (restorable by hand). Nothing is merged when a lead has no clear owner (leads of different users stay separate).
 *
 *   php artisan contacts:merge-duplicates --dry-run                 # plan + totals, writes nothing
 *   php artisan contacts:merge-duplicates --phone=710242355         # one person
 *   php artisan contacts:merge-duplicates --business=10 --limit=20  # first 20 groups of a business
 */
class MergeDuplicateContactsCommand extends Command
{
    protected $signature = 'contacts:merge-duplicates
                            {--dry-run : Show what would be merged, write nothing}
                            {--business= : Only this business_id}
                            {--phone= : Only the number with these last 9 digits}
                            {--limit=0 : Stop after this many groups (0 = all)}';

    protected $description = 'Merge duplicate contacts/leads of the same phone number (different formats) into one record.';

    private const STATUS_RANK = [
        'NEW' => 0, 'OUTREACHED' => 1, 'REPLIED' => 2, 'ENGAGED' => 3, 'NEEDS_ATTENTION' => 3, 'QUALIFIED' => 4,
        'PITCHED' => 5, 'DEMO_SCHEDULED' => 6, 'PROPOSAL_SENT' => 7, 'NEGOTIATING' => 8, 'HANDED_OFF' => 9,
        'LOST' => 10, 'CHURNED' => 10, 'CLOSED' => 11, 'CONVERTED' => 12, 'DO_NOT_CONTACT' => 99,
    ];

    // table => column that holds the lead id (moved to the survivor lead)
    private const LEAD_CHILDREN = [
        ['appointments', 'lead_id'], ['booking_slots', 'lead_id'], ['conversations', 'lead_id'],
        ['conversion_events', 'lead_id'], ['handoffs', 'lead_id'], ['lead_commitments', 'lead_id'],
        ['missed_automations', 'lead_id'], ['sales_activities', 'lead_id'],
    ];
    // table => [lead column, unique column]: rows already present on the survivor are not duplicated
    private const LEAD_UNIQUE_CHILDREN = [['lead_products', 'lead_id', 'product_id'], ['lead_offer_progress', 'lead_id', 'product_id']];

    private const CONTACT_CHILDREN = [
        ['booking_slots', 'business_contact_id'], ['conversion_events', 'business_contact_id'],
        ['incoming_messages', 'business_contact_id'], ['lead_commitments', 'business_contact_id'],
        ['message_queue', 'contact_id'], ['nurture_analytics', 'contact_id'],
        ['outgoing_messages', 'business_contact_id'], ['payments', 'events_guests_id'],
        ['sales_activities', 'business_contact_id'],
    ];
    private const CONTACT_UNIQUE_CHILDREN = [['contact_channel_metrics', 'business_contact_id', 'channel_key'], ['contact_channel_preferences', 'business_contact_id', 'channel_key']];

    private const LEAD_NO_FILL = ['id', 'business_contact_id', 'user_id', 'business_id', 'created_at', 'updated_at'];
    private const CONTACT_NO_FILL = ['id', 'guest_phone', 'business_id', 'created_at', 'updated_at', 'code', 'uuid'];

    private array $tableOk = [];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $sql = "select business_id, right(regexp_replace(guest_phone, '[^0-9]', '', 'g'), 9) as pk, array_agg(id order by id) as ids
                from business_contacts
                where guest_phone is not null and length(regexp_replace(guest_phone, '[^0-9]', '', 'g')) >= 9";
        $bind = [];
        if ($this->option('business')) {
            $sql .= ' and business_id = ?';
            $bind[] = (int) $this->option('business');
        }
        $sql .= ' group by 1, 2 having count(*) > 1';
        if ($this->option('phone')) {
            $sql = "select * from ($sql) g where pk = ?";
            $bind[] = substr(preg_replace('/\D+/', '', (string) $this->option('phone')), -9);
        }
        $sql .= ' order by business_id, pk';
        $groups = DB::select($sql, $bind);

        $this->info(($dry ? '[DRY RUN] ' : '') . 'duplicate groups found: ' . count($groups) . ($limit ? " (processing at most $limit)" : ''));

        if (!$dry) {
            $this->ensureBackupTable();
        }

        $totals = ['groups' => 0, 'contacts_removed' => 0, 'leads_merged' => 0, 'leads_kept_separate' => 0, 'conversations_moved' => 0, 'messages_moved' => 0, 'errors' => 0];
        $done = 0;
        foreach ($groups as $g) {
            if ($limit && $done >= $limit) {
                break;
            }
            $done++;
            $ids = array_map('intval', explode(',', trim($g->ids, '{}')));
            try {
                $plan = $this->plan((int) $g->business_id, (string) $g->pk, $ids);
                if ($dry) {
                    $this->line(sprintf('%s  business %d  contacts %s -> keep %d  leads %s', $g->pk, $g->business_id, implode(',', $ids), $plan['survivor_contact'], json_encode($plan['lead_map'])));
                } else {
                    DB::transaction(fn () => $this->apply($plan));
                }
                $totals['groups']++;
                $totals['contacts_removed'] += count($plan['loser_contacts']);
                $totals['leads_merged'] += count($plan['lead_merge_pairs']);
                $totals['leads_kept_separate'] += $plan['kept_separate'];
                $totals['conversations_moved'] += $plan['conversations_to_move'];
                $totals['messages_moved'] += $plan['messages_to_move'];
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->error("group {$g->pk} (business {$g->business_id}) skipped: " . $e->getMessage());
                Log::error('contacts:merge-duplicates group failed', ['pk' => $g->pk, 'business_id' => $g->business_id, 'error' => $e->getMessage()]);
            }
        }

        $this->table(array_keys($totals), [array_values($totals)]);
        if (!$dry) {
            Log::info('contacts:merge-duplicates finished', $totals);
        }

        return $totals['errors'] ? self::FAILURE : self::SUCCESS;
    }

    // ------------------------------------------------------------------ planning (read only)

    private function plan(int $businessId, string $pk, array $contactIds): array
    {
        $contacts = DB::table('business_contacts')->whereIn('id', $contactIds)->get()->keyBy('id');
        $leads = DB::table('leads')->whereIn('business_contact_id', $contactIds)->get();
        $leadIds = $leads->pluck('id')->all();

        // newest conversation per lead
        $activity = $leadIds
            ? DB::table('conversations')->whereIn('lead_id', $leadIds)->select('lead_id', DB::raw('max(id) as m'), DB::raw('count(*) as n'))->groupBy('lead_id')->get()->keyBy('lead_id')
            : collect();
        $leadAct = fn ($l) => (int) ($activity[$l->id]->m ?? 0);

        // survivor contact: newest conversation across its leads, then lowest id
        $contactAct = [];
        foreach ($contactIds as $cid) {
            $contactAct[$cid] = $leads->where('business_contact_id', $cid)->map($leadAct)->max() ?? 0;
        }
        $survivorContact = $contactIds[0];
        foreach ($contactIds as $cid) {
            if ($contactAct[$cid] > $contactAct[$survivorContact]) {
                $survivorContact = $cid;
            }
        }
        $loserContacts = array_values(array_diff($contactIds, [$survivorContact]));

        // owners: leads with a user_id are merged per user; leads without one join the single user of the group, if clear
        $users = $leads->pluck('user_id')->filter()->unique()->values();
        $soleUser = $users->count() === 1 ? $users->first() : null;

        $byUser = [];
        $keptSeparate = 0;
        foreach ($leads as $l) {
            $owner = $l->user_id ?: $soleUser;
            if ($owner === null) {
                $keptSeparate++; // no clear owner: left as it is (only moved to the survivor contact)
                continue;
            }
            $byUser[$owner][] = $l;
        }

        $leadMap = [];       // survivor lead id => [loser lead ids]
        $pairs = [];         // [loser, survivor]
        foreach ($byUser as $owner => $list) {
            usort($list, function ($a, $b) use ($leadAct) {
                return [$leadAct($b), (string) $b->last_interaction_at, -$b->id] <=> [$leadAct($a), (string) $a->last_interaction_at, -$a->id];
            });
            $survivor = $list[0];
            foreach (array_slice($list, 1) as $loser) {
                $pairs[] = [$loser->id, $survivor->id];
                $leadMap[$survivor->id][] = $loser->id;
            }
        }

        $loserLeadIds = array_column($pairs, 0);
        $convMove = $loserLeadIds ? (int) DB::table('conversations')->whereIn('lead_id', $loserLeadIds)->count() : 0;
        $msgMove = 0;
        if ($loserContacts) {
            $msgMove = (int) DB::table('outgoing_messages')->whereIn('business_contact_id', $loserContacts)->count()
                + (int) DB::table('incoming_messages')->whereIn('business_contact_id', $loserContacts)->count();
        }

        return [
            'business_id' => $businessId,
            'pk' => $pk,
            'contacts' => $contacts,
            'leads' => $leads->keyBy('id'),
            'survivor_contact' => $survivorContact,
            'loser_contacts' => $loserContacts,
            'lead_map' => $leadMap,
            'lead_merge_pairs' => $pairs,
            'kept_separate' => $keptSeparate,
            'conversations_to_move' => $convMove,
            'messages_to_move' => $msgMove,
        ];
    }

    // ------------------------------------------------------------------ applying

    private function apply(array $plan): void
    {
        $survivorContact = $plan['survivor_contact'];
        $moved = [];

        // 1) backup: the rows that are about to disappear, and the survivors before they change
        $loserLeadIds = array_column($plan['lead_merge_pairs'], 0);
        $survivorLeadIds = array_keys($plan['lead_map']);
        DB::table('contact_merge_backup')->insert([
            'merged_at' => now(),
            'business_id' => $plan['business_id'],
            'phone_key' => $plan['pk'],
            'survivor_contact_id' => $survivorContact,
            'survivor_contact_before' => json_encode($plan['contacts'][$survivorContact]),
            'loser_contacts' => json_encode($plan['contacts']->only($plan['loser_contacts'])->values()),
            'survivor_leads_before' => json_encode(collect($survivorLeadIds)->map(fn ($id) => $plan['leads'][$id])->values()),
            'loser_leads' => json_encode(collect($loserLeadIds)->map(fn ($id) => $plan['leads'][$id])->values()),
            'moved' => json_encode(['lead_map' => $plan['lead_map'], 'conversation_ids' => $loserLeadIds ? DB::table('conversations')->whereIn('lead_id', $loserLeadIds)->pluck('id')->all() : []]),
        ]);

        // 2) leads: move children, merge fields, delete the duplicate lead
        foreach ($plan['lead_map'] as $survivorLeadId => $loserIds) {
            $survivor = $plan['leads'][$survivorLeadId];
            $losers = collect($loserIds)->map(fn ($id) => $plan['leads'][$id])->all();

            foreach ($loserIds as $loserId) {
                foreach (self::LEAD_CHILDREN as [$table, $col]) {
                    if ($this->has($table, $col)) {
                        DB::table($table)->where($col, $loserId)->update([$col => $survivorLeadId]);
                    }
                }
                foreach (self::LEAD_UNIQUE_CHILDREN as [$table, $col, $uniq]) {
                    if ($this->has($table, $col)) {
                        DB::update("update {$table} t set {$col} = ? where t.{$col} = ? and not exists (select 1 from {$table} s where s.{$col} = ? and s.{$uniq} = t.{$uniq})", [$survivorLeadId, $loserId, $survivorLeadId]);
                    }
                }
            }

            DB::table('leads')->where('id', $survivorLeadId)->update($this->mergedLeadFields($survivor, $losers));
            DB::table('leads')->whereIn('id', $loserIds)->delete(); // cascades only to rows that could not be moved (exact duplicates)
        }

        // 3) every remaining lead of the group lives under the survivor contact
        $remaining = array_diff(array_keys($plan['leads']->all()), $loserLeadIds);
        if ($remaining) {
            DB::table('leads')->whereIn('id', $remaining)->update(['business_contact_id' => $survivorContact]);
        }

        // 4) contacts: move children, merge fields, delete the duplicate contacts
        foreach ($plan['loser_contacts'] as $loserId) {
            foreach (self::CONTACT_CHILDREN as [$table, $col]) {
                if ($this->has($table, $col)) {
                    DB::table($table)->where($col, $loserId)->update([$col => $survivorContact]);
                }
            }
            foreach (self::CONTACT_UNIQUE_CHILDREN as [$table, $col, $uniq]) {
                if ($this->has($table, $col)) {
                    DB::update("update {$table} t set {$col} = ? where t.{$col} = ? and not exists (select 1 from {$table} s where s.{$col} = ? and s.{$uniq} = t.{$uniq})", [$survivorContact, $loserId, $survivorContact]);
                }
            }
        }
        $losers = collect($plan['loser_contacts'])->map(fn ($id) => $plan['contacts'][$id])->all();
        DB::table('business_contacts')->where('id', $survivorContact)->update($this->mergedContactFields($plan['contacts'][$survivorContact], $losers));
        DB::table('business_contacts')->whereIn('id', $plan['loser_contacts'])->delete();
    }

    private function mergedLeadFields(object $s, array $losers): array
    {
        $all = array_merge([$s], $losers);
        $upd = [];

        $upd['status'] = collect($all)->sortByDesc(fn ($l) => self::STATUS_RANK[$l->status] ?? 0)->first()->status;
        $upd['lead_score'] = collect($all)->max(fn ($l) => (int) $l->lead_score);
        foreach (['last_interaction_at', 'last_contact_at', 'follow_up_sent_at', 'last_activity_at', 'last_reply_at', 'last_chase_at', 'last_win_back_at', 'win_back_eligible_at'] as $f) {
            $upd[$f] = collect($all)->pluck($f)->filter()->max();
        }
        $upd['created_at'] = collect($all)->pluck('created_at')->filter()->min();
        foreach (['interaction_count', 'negative_sentiment_count', 'positive_sentiment_count'] as $f) {
            $vals = collect($all)->pluck($f)->filter(fn ($v) => $v !== null);
            $upd[$f] = $vals->isEmpty() ? null : (int) $vals->sum();
        }
        foreach (['win_back_attempts', 'chase_count'] as $f) {
            $upd[$f] = collect($all)->max(fn ($l) => (int) $l->$f);
        }
        // the waiting-for-reply marker follows the most recently active record
        $upd['ai_waiting_since'] = collect($all)->sortByDesc(fn ($l) => (string) $l->last_interaction_at)->first()->ai_waiting_since;

        // notes: survivor's, plus the other records' notes that are not already in it
        $notes = (string) $s->notes;
        foreach ($losers as $l) {
            if (!empty($l->notes) && !str_contains($notes, trim($l->notes))) {
                $notes = trim($notes . "\n[merged from lead {$l->id}] " . $l->notes);
            }
        }
        $upd['notes'] = $notes === '' ? null : $notes;

        // metadata: survivor's keys win
        $meta = [];
        foreach (array_reverse($all) as $l) {
            $m = is_string($l->metadata) ? json_decode($l->metadata, true) : $l->metadata;
            if (is_array($m)) {
                $meta = array_merge($meta, $m);
            }
        }
        $upd['metadata'] = $meta ? json_encode($meta) : $s->metadata;

        // any other empty field is filled from the most recently active duplicate
        $handled = array_keys($upd);
        $byRecent = collect($losers)->sortByDesc(fn ($l) => (string) $l->last_interaction_at);
        foreach ((array) $s as $col => $val) {
            if (in_array($col, self::LEAD_NO_FILL, true) || in_array($col, $handled, true)) {
                continue;
            }
            if ($val === null || $val === '' || $val === '[]') {
                foreach ($byRecent as $l) {
                    if ($l->$col !== null && $l->$col !== '' && $l->$col !== '[]') {
                        $upd[$col] = $l->$col;
                        break;
                    }
                }
            }
        }
        $upd['updated_at'] = now();

        return $upd;
    }

    private function mergedContactFields(object $s, array $losers): array
    {
        $all = array_merge([$s], $losers);
        $upd = [];

        // never lose a "do not message" or a human hand-off
        $upd['opt_out_status'] = collect($all)->contains(fn ($c) => (bool) $c->opt_out_status);
        if (collect($all)->contains(fn ($c) => $c->handoff_status === 'handed_off')) {
            $upd['handoff_status'] = 'handed_off';
        }
        $upd['contacted_for_sales'] = collect($all)->contains(fn ($c) => (bool) $c->contacted_for_sales);
        foreach (['engagement_score', 'lead_score', 'estimated_value'] as $f) {
            if (property_exists($s, $f)) {
                $vals = collect($all)->pluck($f)->filter(fn ($v) => $v !== null);
                $upd[$f] = $vals->isEmpty() ? null : $vals->max();
            }
        }
        foreach (['last_ai_interaction', 'last_human_interaction', 'contacted_at', 'opt_out_at'] as $f) {
            if (property_exists($s, $f)) {
                $upd[$f] = collect($all)->pluck($f)->filter()->max();
            }
        }

        // a real name beats the placeholder
        $generic = fn ($n) => $n === null || trim($n) === '' || in_array(mb_strtolower(trim($n)), ['whatsapp contact', 'unknown', 'guest'], true) || preg_match('/^\+?[\d\s-]+$/', $n);
        if ($generic($s->guest_name)) {
            foreach ($losers as $c) {
                if (!$generic($c->guest_name)) {
                    $upd['guest_name'] = $c->guest_name;
                    break;
                }
            }
        }

        $handled = array_keys($upd);
        foreach ((array) $s as $col => $val) {
            if (in_array($col, self::CONTACT_NO_FILL, true) || in_array($col, $handled, true)) {
                continue;
            }
            if ($val === null || $val === '' || $val === '[]') {
                foreach ($losers as $c) {
                    if ($c->$col !== null && $c->$col !== '' && $c->$col !== '[]') {
                        $upd[$col] = $c->$col;
                        break;
                    }
                }
            }
        }
        $upd['updated_at'] = now();

        return $upd;
    }

    // ------------------------------------------------------------------ helpers

    private function has(string $table, string $column): bool
    {
        return $this->tableOk["$table.$column"] ??= (Schema::hasTable($table) && Schema::hasColumn($table, $column));
    }

    private function ensureBackupTable(): void
    {
        if (Schema::hasTable('contact_merge_backup')) {
            return;
        }
        Schema::create('contact_merge_backup', function ($t) {
            $t->bigIncrements('id');
            $t->timestamp('merged_at')->useCurrent();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->string('phone_key', 20);
            $t->unsignedBigInteger('survivor_contact_id');
            $t->jsonb('survivor_contact_before')->nullable();
            $t->jsonb('loser_contacts')->nullable();
            $t->jsonb('survivor_leads_before')->nullable();
            $t->jsonb('loser_leads')->nullable();
            $t->jsonb('moved')->nullable();
            $t->index('phone_key');
        });
    }
}
