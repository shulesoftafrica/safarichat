<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer Success – English message templates
    |--------------------------------------------------------------------------
    | Variables are wrapped in {{double_curly_braces}}.
    | Keep each template under ~640 characters to stay well within WhatsApp
    | message limits and reduce truncation risk on older devices.
    */

    'welcome' => <<<MSG
Hi {{business_name}}! 👋

Welcome to *SafariChat* — your smart WhatsApp business inbox.

You're all set up and your number *{{your_number}}* is now connected. Here's what you can do right now:

✅ Start chatting with your customers from the dashboard
✅ Set up automated replies for common questions
✅ Add your products so customers can browse them here

Head to your dashboard to get started:
{{dashboard_link}}

Need help? Just reply to this message and our team will assist you.

– The SafariChat Team
MSG,

    'first_product' => <<<MSG
Great news, {{business_name}}! 🎉

Your first product *{{product_name}}* has been added to your catalogue.

Customers who message you on *{{your_number}}* can now discover and enquire about it directly in WhatsApp.

💡 *Tips to boost sales:*
• Add a clear photo and description to each product
• Turn on the AI sales assistant to answer product questions automatically
• Share your WhatsApp link on social media to drive traffic

View and manage your catalogue from the dashboard:
{{dashboard_link}}

– The SafariChat Team
MSG,

    // -------------------------------------------------------------------------
    // Phase 2 — Daily evening summary
    // -------------------------------------------------------------------------

    'daily_summary' => <<<MSG
📊 *Daily Report — {{business_name}}*
📅 {{today_date}}

━━━━━━━━━━━━━━━━━━━━━
*Today's Activity*
━━━━━━━━━━━━━━━━━━━━━
💬 Total conversations:  {{total_conversations}}{{conversations_delta}}
🆕 New prospects today:  {{new_prospects}}{{prospects_delta}}
🔥 Active leads:         {{active_leads}}
✅ Closed / Converted:   {{closed_today}}
🔄 Lead stage changes:   {{stage_changes}}

*Lead Breakdown*
  🟣 New:          {{lead_new_count}}
  🟡 Interested:   {{lead_interested_count}}
  🟠 Engaged:      {{lead_engaged_count}}
  🟢 Converted:    {{lead_converted_count}}
  🔴 Churned:      {{lead_churned_count}}

━━━━━━━━━━━━━━━━━━━━━
💡 *Today's Recommendation*
━━━━━━━━━━━━━━━━━━━━━
{{recommendation}}

Reply *REPORT* for a full PDF report link.
MSG,

    'disconnected_alert' => <<<MSG
🔴 *Your WhatsApp is disconnected — SafariChat*

Your AI sales agent is currently offline. Customers messaging you right now are receiving no response and leads are being lost.

👉 Reconnect now: {{reconnect_url}} → Settings → WhatsApp → Scan QR Code

Takes less than 1 minute. Don't let leads go cold.
MSG,

];
