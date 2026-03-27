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

];
