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

    // ── Ripoti ya matumizi ya kila siku (inatumwa mara moja kwa siku kwa biashara) ──

    'usage_report' => <<<MSG
📈 *Utendaji wa Leo — {{business_name}}*
📅 {{today_date}}

━━━━━━━━━━━━━━━━━━━━━
*Shughuli za Leo*
━━━━━━━━━━━━━━━━━━━━━
💬 Mazungumzo:          {{conversations}}
👥 Mawasiliano mapya:   {{new_contacts}}
🔥 Wateja wa kufuatilia: {{active_leads}}
✅ Mikataba iliyofungwa: {{deals_closed}}
🔄 Mabadiliko ya hatua: {{stage_changes}}

━━━━━━━━━━━━━━━━━━━━━
*Hali ya Akaunti ya AI*
━━━━━━━━━━━━━━━━━━━━━
💳 Mpango:              {{plan_name}}
⚡ Mikopo iliyobaki:    {{credit_balance}}

Jibu *RIPOTI* kwa muhtasari kamili wa mstari wa mauzo.
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

    // -------------------------------------------------------------------------
    // Awamu ya 4 — Upanuzi na Uhifadhi
    // -------------------------------------------------------------------------

    'subscription_success' => <<<MSG
🎊 *Sasa uko kwenye SafariChat {{plan_name}}!*

Wakala wako wa AI wa mauzo yuko mtandaoni tena na ana nguvu kamili.

*Unachopata sasa:*
{{features}}

*Usajili wako unahuishwa tarehe:* {{renewal_date}}

Ili kupata manufaa zaidi kutoka {{plan_name}}, hapa ndipo uanzie:
{{cta}}

Asante kwa kuamini SafariChat. 🙏

Tembelea dashibodi yako: {{dashboard_link}}
MSG,

    'upgrade_confirmation' => <<<MSG
🚀 *Usasishaji umefaulu — Karibu {{to_plan}}!*

Umepanda kutoka *{{from_plan}}* hadi *{{to_plan}}*.

*Sasa una uwezo wa:*
{{features}}

*Usajili wako unahuishwa tarehe:* {{renewal_date}}

Tembelea dashibodi yako kuangalia vipengele vipya:
{{dashboard_link}}
MSG,

    'usage_limit_warning' => <<<MSG
{{urgency_prefix}} *Uko katika {{usage_label}} kikomo cha mpango wako — SafariChat*

AI yako imetumia *{{percent_used}}%* ya mikopo ya kila mwezi ya mpango wako *{{plan_name}}*.
Mikopo iliyobaki: *{{remaining}}*

Sasisha mpango wa juu zaidi ili kupata uwezo zaidi:
{{billing_link}}

Jibu *UPGRADE* kuchagua mpango mpya hapa hapa.
MSG,

    'credit_low_warning' => <<<MSG
{{urgency_prefix}} *Mikopo yako ya AI inakwisha — SafariChat*

Mikopo iliyobaki: *{{remaining_credits}}* ({{percent_left}}% iliyobaki)

Mikopo inawezesha kila ujumbe unaozalishwa na AI. Itakapoisha, wakala wako wa AI ataacha kujibu wateja.

Ili AI yako iendelee kufanya kazi masaa 24/7:
• Jibu *NUNUA CREDITS* kuongeza sasa
• Au tembelea: {{billing_link}}
MSG,

    'credits_added' => <<<MSG
✅ *Mikopo {{credits_added}} imeongezwa kwenye akaunti yako — SafariChat*

Wakala wako wa AI ana nguvu kamili tena.
Salio la sasa: *Mikopo {{new_balance}}*

Tembelea dashibodi yako kuona AI yako ikifanya kazi:
{{dashboard_link}}
MSG,

    'cs_credit_packages' => <<<MSG
💳 *Ongeza mikopo ya AI yako — SafariChat*

Chagua kifurushi cha mikopo:

1️⃣ *Kidogo* — TZS 10,000 → ~credits 100
2️⃣ *Maarufu* — TZS 50,000 → ~credits 600 *(+bonasi 20%)*
3️⃣ *Nguvu*   — TZS 100,000 → ~credits 1,400 *(+bonasi 40%)*

Jibu na *1*, *2*, au *3* kuchagua kifurushi chako.
MSG,

    // ── Awamu 5: Kuzuia Kutoroka ─────────────────────────────────────────────────

    'inactivity_day3' => <<<MSG
👋 *Habari {{business_name}} — wakala wako wa AI anakukosa!*

Imepita siku 3 tangu mazungumzo yako ya mwisho na mteja. Hii ina maana ya wateja wanaoweza kujibu. 🕐

Hapa kuna mambo 3 ya haraka ya kurudi kwenye mstari:

✅ Jaribu bot yako sasa kwa kutuma ujumbe kwa *{{your_number}}* kwenye WhatsApp
✅ Angalia sanduku lako la ujumbe kwa ujumbe wowote uliokosekana
✅ Hakikisha WhatsApp yako imeunganishwa kwenye dashibodi yako

👉 *{{dashboard_link}}*

Wakala wako wa AI yuko tayari — sema neno tu. 💪
MSG,

    'inactivity_day3_trial_note' => <<<MSG
⏳ *Kumbusho:* Una *siku {{days_left}} za majaribio* zilizobaki — zitumie vizuri!
MSG,

    'inactivity_day10_trial' => <<<MSG
🚨 *{{business_name}} — siku 10 za ukimya. Hebu tusuluhishe.*

Wakala wako wa AI hajafanya mazungumzo kwa siku 10. Wakati wa majaribio ni muhimu — na wako unaisha.

Hapa kuna unachokosa:
• Majibu ya papo hapo ya WhatsApp masaa 24/7
• Kukusanya risasi kiatomati unapokuwa umelala
• Mapendekezo ya bidhaa na huduma kwa otomatiki

📲 *Ingia sasa na tuma ujumbe wa majaribio:*
{{dashboard_link}}

Unahitaji msaada? Timu yetu ipo hapa. Jibu *"Msaada"*.
MSG,

    'inactivity_day10_paid' => <<<MSG
💔 *{{business_name}} — tumeona umekuwa mbali kwa siku 10.*

Usajili wako wa *{{plan_name}}* unafanya kazi na wakala wako wa AI yuko tayari — lakini hakuna shughuli.

Hatutaki upoteze thamani kwenye mpango unaolipa.

Hapa jinsi ya kuanzisha AI yako kufanya kazi leo:
1. Unganisha tena WhatsApp yako kwenye dashibodi
2. Tuma ujumbe wa majaribio kuthibitisha inafanya kazi
3. Shiriki nambari yako ya biashara na wateja

👉 *{{dashboard_link}}*

Maswali? Jibu *"Msaada"* na tutakusaidia.
Upya wako ujao ni *{{renewal_date}}* — usipoteze.
MSG,

    'inactivity_abandoned' => <<<MSG
⚠️ *{{business_name}} — wakala wako wa AI amekatika.*

Inaonekana nambari yako ya WhatsApp haijaunganishwa tena na SafariChat. Hutaweza kupokea ujumbe wa wateja hadi uiunganishe tena.

Hii inachukua chini ya dakika 2 kurekebisha:

1. Nenda kwenye dashibodi yako: *{{dashboard_link}}*
2. Bonyeza *"Unganisha WhatsApp"*
3. Scan msimbo wa QR na simu yako

Wakala wako wa AI atarudi mtandaoni mara moja.

Unahitaji msaada? Timu yetu ya usaidizi ipo — jibu *"Msaada"*.
MSG,

    're_engagement_celebration' => <<<MSG
🎉 *Karibu tena, {{business_name}}!*

Wakala wako wa AI anafurahi kukuona ukiwa hai tena! 🤖✨

Uko tayari — wakala wako anashughulikia ujumbe wa wateja na kufanya kazi kwa bidii kwa biashara yako.

Endelea na kasi:
• Angalia historia yako ya mazungumzo kwenye dashibodi
• Kagua risasi zozote zilizokusanywa ulipokuwa mbali
• Fikiria kuboreshwa kwa nguvu zaidi za AI

👉 *{{dashboard_link}}*

Unaweza! 💪
MSG,

    // ── Kidokezo cha kuunganisha: mtumiaji alisajili lakini WhatsApp bado haijaunganishwa ──

    'onboarding_connect_day1' => <<<MSG
👋 *Habari {{business_name}} — hatua moja ndogo imebaki!*

Umejisajili kwa SafariChat lakini nambari yako ya WhatsApp bado haijaunganishwa.

Bila kuunganisha, wakala wako wa AI hawezi kujibu wateja
na unakosa kila ujumbe unaoingia sasa hivi.

Kuunganisha kunachukua *chini ya dakika 2:*
1️⃣ Fungua dashibodi yako: *{{connect_link}}*
2️⃣ Bonyeza *"Unganisha WhatsApp"*
3️⃣ Scan msimbo wa QR na simu yako — tayari!

Wakala wako wa AI atawaka mara moja ukiunganisha. 🚀
MSG,

    'onboarding_connect_day3' => <<<MSG
⏰ *{{business_name}} — wakala wako wa AI bado anasubiri*

Siku 3 tangu usajili wako, na WhatsApp bado haijaunganishwa.

Kila siku bila kuunganisha maana yake ujumbe wa wateja uliokosekana na wateja waliovukia.

Hapa kuna unapaswa kufanya *saa hii* (inachukua dakika 2):
1️⃣ Bonyeza: *{{connect_link}}*
2️⃣ Bonyeza *"Unganisha WhatsApp"*
3️⃣ Scan msimbo wa QR

Baada ya hapo — wakala wako wa AI atashughulikia kila majibu kiotomatiki. 💪

Umekwama? Jibu *"Msaada"* nasi tutakuongoza.
MSG,

    'onboarding_connect_day7' => <<<MSG
🚨 *Kumbusho la mwisho, {{business_name}}*

Siku 7 zimepita na WhatsApp bado haijaunganishwa na SafariChat.

Wakala wako wa AI yuko tayari, akaunti yako ipo — unahitaji hatua moja tu.

👉 *Unganisha sasa ndani ya dakika 2:*
{{connect_link}}

Ukiunganisha, AI yako itajibu kila mteja kiotomatiki — masaa 24/7, hata unapokuwa umelala.

Jibu *"Msaada"* ukihitaji usaidizi — tuko hapa.
MSG,

    // ── Kidokezo cha siku ya shughuli sifuri (badala ya ripoti tupu) ──────────────

    'usage_report_zero' => <<<MSG
📭 *Hakuna mazungumzo leo — {{business_name}}*
📅 {{today_date}}

Wakala wako wa AI alikuwa tayari leo yote — lakini hakuna ujumbe wa wateja ulioingia bado.

Sawa! Hapa kuna *njia 3 za haraka za kupata ujumbe wako wa kwanza leo:*

📲 Shiriki nambari yako ya WhatsApp na wateja — mdomo kwa mdomo ni haraka zaidi
📌 Ongeza kiungo cha WhatsApp yako kwenye maelezo ya mitandao ya kijamii
✅ Hakikisha wakala wako wa AI amewashwa kwenye dashibodi

👉 *{{dashboard_link}}*

Mteja wako wa pili anaweza kuwa kwa kugawana moja. Endelea! 💪
MSG,

];

