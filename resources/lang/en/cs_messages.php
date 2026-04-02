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

    // ── Daily usage / performance report (sent once per day per business) ──────

    'usage_report' => <<<MSG
📈 *Daily Performance — {{business_name}}*
📅 {{today_date}}

━━━━━━━━━━━━━━━━━━━━━
*Today's Activity*
━━━━━━━━━━━━━━━━━━━━━
💬 Conversations:       {{conversations}}
👥 New contacts:        {{new_contacts}}
🔥 Active leads:        {{active_leads}}
✅ Deals closed today:  {{deals_closed}}
🔄 Stage changes:       {{stage_changes}}

━━━━━━━━━━━━━━━━━━━━━
*AI Account Status*
━━━━━━━━━━━━━━━━━━━━━
💳 Plan:                {{plan_name}}
⚡ Credits remaining:   {{credit_balance}}

Reply *REPORT* for your full pipeline breakdown.
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

    // -------------------------------------------------------------------------
    // Phase 4 — Expansion & Retention
    // -------------------------------------------------------------------------

    'subscription_success' => <<<MSG
🎊 *You are now on SafariChat {{plan_name}}!*

Your AI sales agent is back online and fully powered.

*What you now have:*
{{features}}

*Your subscription renews on:* {{renewal_date}}

To get the most from {{plan_name}}, here is your next recommended action:
{{cta}}

Thank you for trusting SafariChat. 🙏

Visit your dashboard: {{dashboard_link}}
MSG,

    'upgrade_confirmation' => <<<MSG
🚀 *Upgrade successful — Welcome to {{to_plan}}!*

You have moved up from *{{from_plan}}* to *{{to_plan}}*.

*You now have access to:*
{{features}}

*Your subscription renews on:* {{renewal_date}}

Visit your dashboard to explore your new features:
{{dashboard_link}}
MSG,

    'usage_limit_warning' => <<<MSG
{{urgency_prefix}} *You are at {{usage_label}} your plan limit — SafariChat*

Your AI has used *{{percent_used}}%* of your monthly *{{plan_name}}* plan credits.
Remaining credits: *{{remaining}}*

{{#if percent_used >= 95}}⚠️ You have approximately {{remaining}} credits left. After that, your AI will pause until next billing cycle.{{/if}}

👉 Upgrade to a higher plan to unlock more capacity:
{{billing_link}}

Reply *UPGRADE* to choose a new plan right here.
MSG,

    'credit_low_warning' => <<<MSG
{{urgency_prefix}} *Your AI credits are running low — SafariChat*

Remaining credits: *{{remaining_credits}}* ({{percent_left}}% left)

Credits power every AI-generated message. When they run out, your AI agent will stop responding to customers.

To keep your AI running 24/7:
• Reply *BUY CREDITS* to top up now
• Or visit: {{billing_link}}
MSG,

    'credits_added' => <<<MSG
✅ *{{credits_added}} credits added to your account — SafariChat*

Your AI agent is fully powered again.
Current balance: *{{new_balance}} credits*

Visit your dashboard to see your AI at work:
{{dashboard_link}}
MSG,

    'cs_credit_packages' => <<<MSG
💳 *Top up your AI credits — SafariChat*

Choose a credit package:

1️⃣ *Small Top-up* — TZS 10,000 → ~100 credits
2️⃣ *Popular Pack* — TZS 50,000 → ~600 credits *(+20% bonus)*
3️⃣ *Power Pack*  — TZS 100,000 → ~1,400 credits *(+40% bonus)*

Reply with *1*, *2*, or *3* to select your package.
MSG,

    // ── Phase 5: Churn prevention ────────────────────────────────────────────────

    'inactivity_day3' => <<<MSG
👋 *Hey {{business_name}} — your AI agent misses you!*

It's been 3 days since your last customer conversation. That means potential leads waiting to be answered. 🕐

Here are 3 quick things to get back on track:

✅ Test your bot now by messaging *{{your_number}}* on WhatsApp
✅ Check your inbox for any missed customer messages
✅ Make sure your WhatsApp is connected on your dashboard

👉 *{{dashboard_link}}*

Your AI agent is ready — just say the word. 💪
MSG,

    'inactivity_day3_trial_note' => <<<MSG
⏳ *Reminder:* You have *{{days_left}} trial days* remaining — make them count!
MSG,

    'inactivity_day10_trial' => <<<MSG
🚨 *{{business_name}} — 10 days of silence. Let's fix that.*

Your AI agent hasn't had a conversation in 10 days. Trial time is precious — and yours is running out.

Here's what you're missing:
• Instant WhatsApp replies 24/7
• Automated lead capture while you sleep
• Product & service recommendations on autopilot

📲 *Log in now and send a test message:*
{{dashboard_link}}

Need help? Our team is here. Just reply *"Help"*.
MSG,

    'inactivity_day10_paid' => <<<MSG
💔 *{{business_name}} — we noticed you've been away for 10 days.*

Your *{{plan_name}}* subscription is active and your AI agent is ready — but there's been no action.

We don't want you to lose value on a plan you're paying for.

Here's how to get your AI working again today:
1. Reconnect your WhatsApp on the dashboard
2. Send a test message to confirm it's working
3. Share your business number with customers

👉 *{{dashboard_link}}*

Questions? Reply *"Help"* and we'll get you sorted.
Your next renewal is *{{renewal_date}}* — don't waste it.
MSG,

    'inactivity_abandoned' => <<<MSG
⚠️ *{{business_name}} — your AI agent is disconnected.*

It looks like your WhatsApp number is no longer connected to SafariChat. You won't be able to receive customer messages until it's reconnected.

This takes less than 2 minutes to fix:

1. Go to your dashboard: *{{dashboard_link}}*
2. Click *"Connect WhatsApp"*
3. Scan the QR code with your phone

Your AI agent will be back online immediately.

Need assistance? Our support team is standing by — just reply *"Help"*.
MSG,

    're_engagement_celebration' => <<<MSG
🎉 *Welcome back, {{business_name}}!*

Your AI agent is happy to see you active again! 🤖✨

You're all set — your agent is handling customer messages and working hard for your business.

Keep the momentum going:
• Check your conversation history on the dashboard
• Review any leads captured while you were away
• Consider upgrading for even more AI power

👉 *{{dashboard_link}}*

You've got this! 💪
MSG,

    // ── Onboarding nudge: user registered but WhatsApp not yet connected ───────

    'onboarding_connect_day1' => <<<MSG
👋 *Hi {{business_name}} — one small step left!*

You signed up for SafariChat but your WhatsApp number isn't connected yet.

Without it your AI agent can't respond to customers
and you're missing every inbound message right now.

Connecting takes *under 2 minutes:*
1️⃣ Open your dashboard: *{{connect_link}}*
2️⃣ Click *"Connect WhatsApp"*
3️⃣ Scan the QR code with your phone — done!

Your AI agent goes live the moment you connect. 🚀
MSG,

    'onboarding_connect_day3' => <<<MSG
⏰ *{{business_name}} — your AI agent is still waiting*

3 days since you signed up, and WhatsApp still isn't connected.

Every day without it means missed customer messages and leads going cold.

Here is exactly what to do *right now* (takes 2 minutes):
1️⃣ Click: *{{connect_link}}*
2️⃣ Click *"Connect WhatsApp"*
3️⃣ Scan the QR code

That's it — your AI handles every reply automatically after that. 💪

Stuck? Reply *"Help"* and we'll walk you through it.
MSG,

    'onboarding_connect_day7' => <<<MSG
🚨 *One last nudge, {{business_name}}*

7 days in and WhatsApp still isn't connected to SafariChat.

Your AI agent is set up, your account is ready — you're just missing one step.

👉 *Connect now in 2 minutes:*
{{connect_link}}

Once connected, your AI responds to every customer automatically — 24/7, while you sleep.

Reply *"Help"* if you need assistance — we're here.
MSG,

    // ── Zero-activity encouragement (sent instead of a bare zero-report) ────────

    'usage_report_zero' => <<<MSG
📭 *No conversations today — {{business_name}}*
📅 {{today_date}}

Your AI agent was on standby all day — no customer messages came in yet.

That's okay! Here are *3 quick ways to get your first message today:*

📲 Share your WhatsApp number with customers — word of mouth is fastest
📌 Add your WhatsApp link to your social media bios
✅ Make sure your AI agent is turned ON in the dashboard

👉 *{{dashboard_link}}*

Your next customer could be one share away. Keep going! 💪
MSG,

];
