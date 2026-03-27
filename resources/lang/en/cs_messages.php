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

    // -------------------------------------------------------------------------
    // Phase 3 — Trial lifecycle & conversational billing
    // -------------------------------------------------------------------------

    'trial_reminder' => <<<MSG
⏳ *Trial Update — SafariChat*

Your free trial {{days_left_text}} on *{{trial_ends}}*.

{{trial_urgency_note}}

Upgrade now to keep your AI sales agent running without interruption:
{{billing_link}}

Reply *UPGRADE* to see available plans.
MSG,

    'trial_warning_3h' => <<<MSG
🚨 *Your trial ends in less than 3 hours — SafariChat*

When your trial expires your WhatsApp AI assistant will stop responding to customers.

👉 Upgrade now (takes 2 minutes):
{{billing_link}}

Reply *UPGRADE* to choose a plan right here.
MSG,

    'trial_expired' => <<<MSG
🔴 *Your trial has ended — SafariChat*

Your AI sales agent is now paused. Customers messaging your WhatsApp number will not receive automated replies.

✅ Reactivate in 2 minutes:
{{billing_link}}

Reply *UPGRADE* to pick a plan and restore your service immediately.
MSG,

    'cs_plan_list' => <<<MSG
📦 *Choose a SafariChat Plan*

{{plan_list}}

Reply with the *number* of the plan you'd like (e.g. *1* for Starter).
MSG,

    'cs_payment_details' => <<<MSG
✅ *You chose: {{plan_name}} — TZS {{amount}}/month*

Pay using any of the options below:

🏦 *UCN (bank / mobile money):* {{ucn}}
💳 *Card (Stripe):* {{stripe_link}}
📱 *Mobile Money (Flutterwave):* {{flutterwave_link}}

Once payment is confirmed your plan will activate within minutes.

Send *DONE* once you've completed payment, or *BACK* to choose a different plan.
MSG,

    'cs_payment_reminder' => <<<MSG
⏳ *Awaiting your payment — {{plan_name}} (TZS {{amount}})*

🏦 UCN: {{ucn}}

Complete payment to activate your plan, or reply *BACK* to choose a different plan.
MSG,

    'cs_payment_received' => <<<MSG
🎉 *Thank you!*

We've received your payment confirmation. Your plan will be activated within a few minutes.

You'll receive a confirmation message once it's live. Welcome to SafariChat! 🚀
MSG,

    'cs_invalid_choice' => <<<MSG
❓ Please reply with a number between *1* and *{{max}}* to select your plan.

Reply *HELP* at any time to see all available options.
MSG,

    'cs_billing_error' => <<<MSG
⚠️ *Something went wrong*

We couldn't generate a payment link right now. Please try again in a few minutes or visit:
{{dashboard_link}}

Our team has been notified. Sorry for the inconvenience!
MSG,

    'cs_help_menu' => <<<MSG
👋 *SafariChat — How can we help?*

Reply with any of the following:

📦 *UPGRADE*   — View plans & upgrade your account
📊 *REPORT*    — Get your latest business snapshot
💳 *BUY CREDITS* — Purchase AI credits
❓ *HELP*      — Show this menu again

Or visit your dashboard:
{{dashboard_link}}
MSG,

];
