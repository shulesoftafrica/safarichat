<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Works out WHEN a customer asked for an appointment, from the chat text.
 *
 * Why this exists: the AI reply promised "11:00 AM tomorrow" but nothing ever read the time the customer
 * asked for. The appointment action carried no datetime, so every booking fell back to "tomorrow 10:00",
 * and a plain "can we do it at 11:00am" (no booking keyword) triggered no action at all. The customer
 * was told 11:00 while the system kept 10:00 (and, on older code, a second 10:00 row).
 *
 * Pure logic: no database, no AI call. Understands English and the common Swahili day words.
 */
class AppointmentTimeResolver
{
    /** Words in a customer message that show they want to change an existing booking. */
    private const RESCHEDULE_CUES = [
        'reschedule', 're-schedule', 'postpone', 'move it', 'move the', 'move our', 'change the time',
        'change it', 'change our', 'instead', 'rather', 'can we do it', 'can we make it', 'make it',
        'later', 'earlier', 'another time', 'different time', 'forgot', 'forgotten', 'cannot make',
        "can't make", 'cant make', 'badilisha', 'ahirisha', 'sogeza',
    ];

    /** Words in the AI's own reply that show it confirmed a change of the booking. */
    private const AI_CHANGE_CUES = ['reschedul', 're-schedul', 'moved', 'move the', 'changed', 'new time', 'update'];

    private const WEEKDAYS = [
        'monday' => Carbon::MONDAY, 'tuesday' => Carbon::TUESDAY, 'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY, 'friday' => Carbon::FRIDAY, 'saturday' => Carbon::SATURDAY,
        'sunday' => Carbon::SUNDAY,
        'jumatatu' => Carbon::MONDAY, 'jumanne' => Carbon::TUESDAY, 'jumatano' => Carbon::WEDNESDAY,
        'alhamisi' => Carbon::THURSDAY, 'ijumaa' => Carbon::FRIDAY, 'jumamosi' => Carbon::SATURDAY,
        'jumapili' => Carbon::SUNDAY,
    ];

    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /**
     * Does the customer message look like a request to change an existing booking?
     */
    public static function looksLikeChange(string $customerMessage, ?string $aiReply = null): bool
    {
        $msg = mb_strtolower($customerMessage);
        foreach (self::RESCHEDULE_CUES as $cue) {
            if (str_contains($msg, $cue)) {
                return true;
            }
        }

        $reply = mb_strtolower((string) $aiReply);
        foreach (self::AI_CHANGE_CUES as $cue) {
            if ($reply !== '' && str_contains($reply, $cue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Put the requested time into the appointment action.
     *
     * - A booking keyword already produced `schedule_appointment` (without a time): add the time.
     * - The lead already has a live appointment ($existingAt) and the message reads like a change
     *   ("can we do it at 11:00am"), or the AI confirmed a change: create the action even though no booking
     *   keyword matched, so the existing appointment is moved in place.
     * - No time found: return the actions untouched, so an existing booking is never altered by a guess.
     *
     * @param array       $actions    the actions the AI produced for this message
     * @param Carbon|null $existingAt the lead's current live appointment time, null when there is none
     */
    public static function applyToActions(array $actions, string $customerMessage, string $aiReply, ?Carbon $existingAt, ?Carbon $now = null): array
    {
        $hasBookingAction = isset($actions['schedule_appointment']);
        $isChange = $existingAt !== null && self::looksLikeChange($customerMessage, $aiReply);

        if (!$hasBookingAction && !$isChange) {
            return $actions;
        }

        $requested = self::resolve($customerMessage, $aiReply, $existingAt, $now);
        if ($requested === null) {
            return $actions;
        }

        $base = is_array($actions['schedule_appointment'] ?? null)
            ? $actions['schedule_appointment']
            : ['type' => 'demo', 'urgency' => 'normal'];

        $actions['schedule_appointment'] = array_merge($base, [
            'detected' => true,
            'datetime' => $requested->toDateTimeString(),
        ]);

        return $actions;
    }

    /**
     * @param string      $customerMessage what the customer just wrote
     * @param string|null $aiReply         what the AI is about to tell them (it states the agreed time)
     * @param Carbon|null $existing        the lead's current appointment time, if any
     * @param Carbon|null $now             injectable clock for tests
     *
     * @return Carbon|null the requested date and time, or null when no time was given
     */
    public static function resolve(string $customerMessage, ?string $aiReply = null, ?Carbon $existing = null, ?Carbon $now = null): ?Carbon
    {
        $now = $now ? $now->copy() : Carbon::now();

        // The customer's own words win; the AI reply is the fallback (it repeats the agreed time).
        $time = self::extractTime($customerMessage);
        $dateSource = $customerMessage;

        if ($time === null && $aiReply) {
            $time = self::extractTime($aiReply);
            $dateSource = $aiReply;
        }

        if ($time === null) {
            return null;
        }

        // Date: the one in the same text as the time, else the one in the other text, else the existing
        // appointment's day, else the next time that clock time occurs.
        $date = self::extractDate($dateSource, $now)
            ?? self::extractDate($dateSource === $customerMessage ? (string) $aiReply : $customerMessage, $now);

        if ($date === null) {
            if ($existing) {
                $date = $existing->copy()->startOfDay();
            } else {
                $date = $now->copy()->startOfDay();
                $candidate = $date->copy()->setTime($time[0], $time[1]);
                if ($candidate->lte($now)) {
                    $date->addDay();
                }
            }
        }

        return $date->copy()->setTime($time[0], $time[1], 0);
    }

    /**
     * @return array{0:int,1:int}|null [hour24, minute]
     */
    private static function extractTime(string $text): ?array
    {
        $t = mb_strtolower($text);

        // 11:00am, 11.30 pm, 11:00, 14:30, 10h00
        if (preg_match('/\b(\d{1,2})\s*[:.h]\s*(\d{2})\s*(a\.?m\.?|p\.?m\.?)?/u', $t, $m)) {
            return self::normalise((int) $m[1], (int) $m[2], $m[3] ?? null, false);
        }

        // 11am, 11 am, 3 pm
        if (preg_match('/\b(\d{1,2})\s*(a\.?m\.?|p\.?m\.?)(?![a-z])/u', $t, $m)) {
            return self::normalise((int) $m[1], 0, $m[2], false);
        }

        // "at 11", "saa 11" (no am/pm): guess business hours
        if (preg_match('/\b(?:at|by|around|saa)\s+(\d{1,2})\b(?!\s*[:.]\s*\d)/u', $t, $m)) {
            return self::normalise((int) $m[1], 0, null, true);
        }

        return null;
    }

    private static function normalise(int $hour, int $minute, ?string $meridiem, bool $guess): ?array
    {
        if ($minute > 59 || $hour > 24) {
            return null;
        }

        $meridiem = $meridiem ? str_replace('.', '', $meridiem) : null;

        if ($meridiem === 'pm' && $hour < 12) {
            $hour += 12;
        } elseif ($meridiem === 'am' && $hour === 12) {
            $hour = 0;
        } elseif ($meridiem === null && $guess) {
            // A bare "at 3" in a business chat means 3pm, "at 11" means 11am.
            if ($hour >= 1 && $hour <= 6) {
                $hour += 12;
            }
        }

        return $hour === 24 ? [0, $minute] : [$hour, $minute];
    }

    private static function extractDate(string $text, Carbon $now): ?Carbon
    {
        $t = mb_strtolower($text);
        $today = $now->copy()->startOfDay();

        if (preg_match('/\b(day after tomorrow|keshokutwa)\b/u', $t)) {
            return $today->addDays(2);
        }
        if (preg_match('/\b(tomorrow|tommorow|tomorow|kesho)\b/u', $t)) {
            return $today->addDay();
        }
        if (preg_match('/\b(today|tonight|leo)\b/u', $t)) {
            return $today;
        }

        foreach (self::WEEKDAYS as $name => $dow) {
            if (preg_match('/\b' . $name . '\b/u', $t)) {
                $d = $today->copy();
                $d->next($dow);   // the next such weekday after today

                return $d;
            }
        }

        // "9 oct", "9th october", "oct 9", "october 9th"
        $monthPattern = '(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*';
        $found = null;
        if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s*(?:of\s+)?' . $monthPattern . '\b/u', $t, $m)) {
            $found = [(int) $m[1], self::MONTHS[$m[2]]];
        } elseif (preg_match('/\b' . $monthPattern . '\s+(\d{1,2})(?:st|nd|rd|th)?\b/u', $t, $m)) {
            $found = [(int) $m[2], self::MONTHS[$m[1]]];
        }
        if ($found && $found[0] >= 1 && $found[0] <= 31) {
            $d = Carbon::create($today->year, $found[1], $found[0], 0, 0, 0, $today->getTimezone());
            if ($d && $d->lt($today)) {
                $d->addYear();
            }
            if ($d) {
                return $d;
            }
        }

        return null;
    }
}
