<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The onboarding demo video on the client dashboard home. Set from the ShuleSoft admin panel
 * (SafariChat › Onboarding & Videos, through Api\AdminPanelController@onboardingVideo) and
 * kept as onboarding.video_* rows in the settings table.
 */
class OnboardingVideo
{
    const KEYS = ['video_id', 'video_title', 'video_visible'];
    const CACHE_KEY = 'onboarding_video';
    const DEFAULT_TITLE = 'Jinsi ya kutumia Safaricha AI';

    /** ['video_id' => ?string, 'video_title' => string, 'video_visible' => bool] */
    public static function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $rows = DB::table('settings')->whereIn('key', array_map(fn ($k) => "onboarding.$k", self::KEYS))->pluck('value', 'key');

            return [
                'video_id' => $rows['onboarding.video_id'] ?? null,
                'video_title' => ($rows['onboarding.video_title'] ?? '') !== '' ? $rows['onboarding.video_title'] : self::DEFAULT_TITLE,
                'video_visible' => ($rows['onboarding.video_visible'] ?? '0') === '1',
            ];
        });
    }

    /** What the dashboard shows: the video, or null when none is set or it is switched off. */
    public static function forDashboard(): ?array
    {
        $s = self::settings();

        return $s['video_visible'] && $s['video_id'] ? $s : null;
    }

    public static function save(string $videoId, string $title, bool $visible): void
    {
        $values = ['video_id' => $videoId, 'video_title' => $title, 'video_visible' => $visible ? '1' : '0'];
        $descriptions = ['video_id' => 'YouTube ID of the onboarding demo video on the client dashboard',
            'video_title' => 'Title of the onboarding demo video card', 'video_visible' => 'Show the onboarding demo video (1/0)'];

        DB::transaction(function () use ($values, $descriptions) {
            foreach ($values as $k => $v) {
                DB::table('settings')->updateOrInsert(['key' => "onboarding.$k"],
                    ['value' => $v, 'type' => $k === 'video_visible' ? 'boolean' : 'string', 'description' => $descriptions[$k], 'updated_at' => now()]);
            }
        });
        Cache::forget(self::CACHE_KEY);
    }
}
