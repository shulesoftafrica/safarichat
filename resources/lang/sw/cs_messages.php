<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer Success – Swahili (sw) message templates
    |--------------------------------------------------------------------------
    | Variables are wrapped in {{double_curly_braces}}.
    */

    'welcome' => <<<MSG
Habari {{business_name}}! 👋

Karibu *SafariChat* — kisanduku chako cha akili cha biashara kwenye WhatsApp.

Umefanikiwa! Nambari yako *{{your_number}}* sasa imeunganishwa. Unaweza kufanya hivi sasa hivi:

✅ Anza kuzungumza na wateja wako kutoka dashibodi
✅ Weka majibu ya kiotomatiki kwa maswali ya kawaida
✅ Ongeza bidhaa zako ili wateja waweze kuzitazama hapa

Nenda kwenye dashibodi yako kuanza:
{{dashboard_link}}

Unahitaji msaada? Jibu ujumbe huu na timu yetu itakusaidia.

– Timu ya SafariChat
MSG,

    'first_product' => <<<MSG
Habari njema, {{business_name}}! 🎉

Bidhaa yako ya kwanza *{{product_name}}* imeongezwa kwenye orodha yako.

Wateja wanaokutumia ujumbe kwenye *{{your_number}}* sasa wanaweza kuipata na kuuliza maswali kuihusu moja kwa moja kwenye WhatsApp.

💡 *Vidokezo vya kuongeza mauzo:*
• Ongeza picha wazi na maelezo kwa kila bidhaa
• Washa msaidizi wa AI wa mauzo ili ajibu maswali ya bidhaa kiotomatiki
• Shiriki kiungo chako cha WhatsApp kwenye mitandao ya kijamii kutoa trafiki

Tazama na simamia katalogi yako kutoka dashibodi:
{{dashboard_link}}

– Timu ya SafariChat
MSG,

    // -------------------------------------------------------------------------
    // Phase 2 — Muhtasari wa kila jioni
    // -------------------------------------------------------------------------

    'daily_summary' => <<<MSG
📊 *Ripoti ya Kila Siku — {{business_name}}*
📅 {{today_date}}

━━━━━━━━━━━━━━━━━━━━━
*Shughuli za Leo*
━━━━━━━━━━━━━━━━━━━━━
💬 Mazungumzo yote:      {{total_conversations}}{{conversations_delta}}
🆕 Wateja wapya leo:     {{new_prospects}}{{prospects_delta}}
🔥 Wateja wa kufuatilia: {{active_leads}}
✅ Wakubaliano/Mauzo:    {{closed_today}}
🔄 Mabadiliko ya hatua:  {{stage_changes}}

*Mgawanyiko wa Wateja*
  🟣 Wapya:         {{lead_new_count}}
  🟡 Wanaovutiwa:   {{lead_interested_count}}
  🟠 Wanaoshiriki:  {{lead_engaged_count}}
  🟢 Wakubaliano:   {{lead_converted_count}}
  🔴 Walioondoka:   {{lead_churned_count}}

━━━━━━━━━━━━━━━━━━━━━
💡 *Ushauri wa Leo*
━━━━━━━━━━━━━━━━━━━━━
{{recommendation}}

Jibu *RIPOTI* kwa kiungo cha ripoti kamili ya PDF.
MSG,

    'disconnected_alert' => <<<MSG
🔴 *WhatsApp yako imekatika — SafariChat*

Wakala wako wa AI wa mauzo sasa yuko nje ya mtandao. Wateja wanaokutumia ujumbe saa hii hawapati majibu na wateja wanaopotea.

👉 Unganisha tena sasa: {{reconnect_url}} → Mipangilio → WhatsApp → Scan QR Code

Inachukua chini ya dakika 1. Usiacha wateja wakuepuke.
MSG,

    // -------------------------------------------------------------------------
    // Awamu ya 3 — Mzunguko wa majaribio na malipo ya mazungumzo
    // -------------------------------------------------------------------------

    'trial_reminder' => <<<MSG
⏳ *Habari za Jaribio — SafariChat*

Jaribio lako la bure {{days_left_text}} tarehe *{{trial_ends}}*.

{{trial_urgency_note}}

Sasisha sasa ili msaidizi wako wa AI aendelee kufanya kazi bila usumbufu:
{{billing_link}}

Jibu *UPGRADE* kuona mipango inayopatikana.
MSG,

    'trial_warning_3h' => <<<MSG
🚨 *Jaribio lako linaisha ndani ya saa 3 — SafariChat*

Jaribio lako litakapokwisha, msaidizi wako wa WhatsApp AI ataacha kujibu wateja.

👉 Sasisha sasa (inachukua dakika 2):
{{billing_link}}

Jibu *UPGRADE* kuchagua mpango hapa hapa.
MSG,

    'trial_expired' => <<<MSG
🔴 *Jaribio lako limeisha — SafariChat*

Wakala wako wa AI wa mauzo sasa umesimama. Wateja wanaokutumia ujumbe kwenye WhatsApp yako hawatapata majibu ya kiotomatiki.

✅ Rejesha huduma ndani ya dakika 2:
{{billing_link}}

Jibu *UPGRADE* kuchagua mpango na kurejesha huduma yako mara moja.
MSG,

    'cs_plan_list' => <<<MSG
📦 *Chagua Mpango wa SafariChat*

{{plan_list}}

Jibu na *nambari* ya mpango unaotaka (mfano: *1* kwa Starter).
MSG,

    'cs_payment_details' => <<<MSG
✅ *Umechagua: {{plan_name}} — TZS {{amount}}/mwezi*

Lipa kwa njia yoyote kati ya hizi:

🏦 *UCN (benki / pesa ya simu):* {{ucn}}
💳 *Kadi (Stripe):* {{stripe_link}}
📱 *Pesa ya Simu (Flutterwave):* {{flutterwave_link}}

Malipo yako yakithibitishwa, mpango wako utaanzishwa ndani ya dakika chache.

Tuma *DONE* ukimaliza malipo, au *BACK* kuchagua mpango tofauti.
MSG,

    'cs_payment_reminder' => <<<MSG
⏳ *Tunasubiri malipo yako — {{plan_name}} (TZS {{amount}})*

🏦 UCN: {{ucn}}

Maliza malipo kuanzisha mpango wako, au jibu *BACK* kuchagua mpango tofauti.
MSG,

    'cs_payment_received' => <<<MSG
🎉 *Asante!*

Tumepokea uthibitisho wako wa malipo. Mpango wako utaanzishwa ndani ya dakika chache.

Utapokea ujumbe wa uthibitisho ukithibitishwa. Karibu SafariChat! 🚀
MSG,

    'cs_invalid_choice' => <<<MSG
❓ Tafadhali jibu na nambari kati ya *1* na *{{max}}* kuchagua mpango wako.

Jibu *HELP* wakati wowote kuona chaguo zote zinazopatikana.
MSG,

    'cs_billing_error' => <<<MSG
⚠️ *Hitilafu imetokea*

Halikuwezekana kuunda kiungo cha malipo saa hii. Tafadhali jaribu tena baada ya dakika chache au tembelea:
{{dashboard_link}}

Timu yetu imearifiwa. Samahani kwa usumbufu!
MSG,

    'cs_help_menu' => <<<MSG
👋 *SafariChat — Tunaweza kukusaidiaje?*

Jibu na moja ya yafuatayo:

📦 *UPGRADE*   — Tazama mipango na sasisha akaunti yako
📊 *RIPOTI*    — Pata picha ya hivi karibuni ya biashara yako
💳 *NUNUA CREDITS* — Nunua mikopo ya AI
❓ *HELP*      — Onyesha menyu hii tena

Au tembelea dashibodi yako:
{{dashboard_link}}
MSG,

];
