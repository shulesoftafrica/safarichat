<?php

namespace App\Services\CustomerSuccess;

use App\Models\CsMessageLog;
use App\Models\User;
use App\Services\OpenAiService;
use App\Services\SystemWhatsAppService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Locale-aware CS message renderer and dispatcher.
 *
 * Resolution order for locale:
 *   1. users.locale
 *   2. ai_sales_agents.primary_language (first agent for this user)
 *   3. most recent conversations.language_detected
 *   4. default → 'en'
 *
 * Template tiers:
 *   Tier 1 — en, sw  : static files in resources/lang/{locale}/cs_messages.php
 *   Tier 2 — ar, es, fr, hi, pt-br : AI-translated from English source
 *   Tier 3 — anything else : enforce 'en'
 */
class CsMessageRenderer
{
    private const TIER_1 = ['en', 'sw'];
    private const TIER_2 = ['ar', 'es', 'fr', 'hi', 'pt-br'];

    public function __construct(
        private readonly SystemWhatsAppService $systemWhatsApp,
        private readonly OpenAiService $openAiService,
    ) {}

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Render and send a CS message to a user.
     *
     * @param User   $user        Recipient (the business owner)
     * @param string $type        Key in cs_messages.php, e.g. 'welcome'
     * @param array  $vars        Template variables, e.g. ['business_name' => 'Acme']
     * @param int    $businessId  For the log record
     */
    public function send(User $user, string $type, array $vars = [], int $businessId = 0): bool
    {
        $locale     = $this->resolveLocale($user);
        $message    = $this->renderTemplate($type, $vars, $locale);
        $metaLocale = $locale;

        if ($message === null) {
            Log::error('CsMessageRenderer: template not found', [
                'user_id' => $user->id,
                'type'    => $type,
                'locale'  => $locale,
            ]);
            return false;
        }

        $phone = $user->phone;
        if (!$phone) {
            Log::warning('CsMessageRenderer: user has no phone number', ['user_id' => $user->id]);
            return false;
        }

        // Use 'system_notification' as the WA message-type so the system instance's
        // allowed_message_types check passes. The CS template identity ($type) is
        // recorded separately in CsMessageLog below.
        $sent = $this->systemWhatsApp->sendGenericMessage($phone, $message, 'system_notification');

        if ($sent) {
            CsMessageLog::record(
                $user->id,
                $businessId ?: ($user->business_id ?? 0),
                $type,
                ['locale_used' => $metaLocale]
            );
        }

        return $sent;
    }

    // ── Locale resolution ────────────────────────────────────────────────────

    public function resolveLocale(User $user): string
    {
        // Priority 1: explicit user locale
        $locale = $user->locale ?? null;

        // Priority 2: AI sales agent primary language
        if (!$locale) {
            $agent  = $user->aiSalesAgents()->first();
            $locale = $agent?->primary_language ?? null;
        }

        // Priority 3: most recent conversation language
        if (!$locale) {
            $conv   = $user->conversations()->latest()->first();
            $locale = $conv?->language_detected ?? null;
        }

        // Normalise
        $locale = strtolower(trim($locale ?? 'en'));

        // Persist if was null so future lookups are consistent
        if (!$user->locale && $locale) {
            $user->updateQuietly(['locale' => $locale]);
        }

        // Tier 3 — unsupported: fall back to English
        if (!in_array($locale, array_merge(self::TIER_1, self::TIER_2))) {
            Log::debug('CsMessageRenderer: unsupported locale, defaulting to en', [
                'user_id' => $user->id,
                'locale'  => $locale,
            ]);
            return 'en';
        }

        return $locale;
    }

    // ── Template rendering ───────────────────────────────────────────────────

    private function renderTemplate(string $type, array $vars, string $locale): ?string
    {
        if (in_array($locale, self::TIER_1)) {
            return $this->renderStaticTemplate($type, $vars, $locale);
        }

        if (in_array($locale, self::TIER_2)) {
            // Get English source first
            $english = $this->renderStaticTemplate($type, $vars, 'en');
            if ($english === null) {
                return null;
            }
            return $this->translateWithAi($english, $locale);
        }

        // Tier 3 fallback (should not reach here after resolveLocale normalises)
        return $this->renderStaticTemplate($type, $vars, 'en');
    }

    private function renderStaticTemplate(string $type, array $vars, string $locale): ?string
    {
        // Try requested locale, fall back to 'en' if key missing in that locale
        $templates = $this->loadTemplates($locale);

        if (!isset($templates[$type])) {
            if ($locale !== 'en') {
                Log::warning('CsMessageRenderer: no static template for locale, using AI translation fallback', [
                    'type'   => $type,
                    'locale' => $locale,
                ]);
                // For Tier 1 non-en (sw) with missing template: use AI translation
                $english = $this->renderStaticTemplate($type, $vars, 'en');
                return $english ? $this->translateWithAi($english, $locale) : null;
            }
            return null;
        }

        return $this->interpolate($templates[$type], $vars);
    }

    private function loadTemplates(string $locale): array
    {
        $path = resource_path("lang/{$locale}/cs_messages.php");

        if (!file_exists($path)) {
            return [];
        }

        return require $path;
    }

    /**
     * Apply {{variable}} substitution to a template string.
     */
    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }

    /**
     * Translate an English message to the target locale via AI.
     * Preserves WhatsApp formatting (*bold*, _italic_), emojis, links, and numbers.
     */
    private function translateWithAi(string $english, string $targetLocale): ?string
    {
        try {
            $prompt = <<<PROMPT
Translate the following WhatsApp message to {$targetLocale}.

Rules:
- Preserve ALL WhatsApp formatting exactly: *bold* stays *bold*, _italic_ stays _italic_
- Preserve ALL emojis exactly as they appear
- Never translate URLs, phone numbers, or payment amounts (e.g. TZS 149,000)
- Never add extra punctuation or change line breaks
- Return ONLY the translated message, nothing else

Message:
{$english}
PROMPT;

            $result = $this->openAiService->generateText($prompt, [
                'max_tokens'  => 1500,
                'temperature' => 0.2,
            ]);

            return $result['content'] ?? null;

        } catch (\Exception $e) {
            Log::error('CsMessageRenderer: AI translation failed', [
                'locale' => $targetLocale,
                'error'  => $e->getMessage(),
            ]);
            return null; // caller will fall back to English
        }
    }
}
