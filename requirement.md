AI Sales Agent Technical Specification (Laravel & PostgreSQL)

This document outlines the detailed requirements for building the backend logic, database schema, and process automation for a high-efficiency, multi-stage AI Sales Agent.

Target Stack: Laravel (Backend/Cron), PostgreSQL (Database), External WhatsApp API (Integration), Gemini API (AI Core).

1. Database Schema (PostgreSQL)

1.1. leads Table (The Potential Customer List)

This table holds all potential customers and their calculated priority scores.

Column Name

Data Type

Constraint

Description

id

SERIAL

Primary Key

Unique lead ID.

phone_number

VARCHAR(15)

NOT NULL, UNIQUE

Customer contact for WhatsApp.

name

VARCHAR(255)

NULLABLE

Customer/Contact Name.

company_name

VARCHAR(255)

NULLABLE

Lead's company.

industry

VARCHAR(100)

NULLABLE

Lead's industry (for AI context).

lead_score

INTEGER

NOT NULL

Lead Scoring Value (0-100).

status

VARCHAR(50)

NOT NULL

Current state: NEW, QUEUED, OUTREACHED, REPLIED, HANDED_OFF, DO_NOT_CONTACT.

last_outreach_at

TIMESTAMP

NULLABLE

Last attempt to send an intro message.

created_at

TIMESTAMP

NOT NULL

Record creation date.

updated_at

TIMESTAMP

NOT NULL

Last update date.

1.2. conversations Table (The Chat History & State)

Stores the entire chat history, including AI and customer messages, and manages follow-up logistics.

Column Name

Data Type

Constraint

Description

id

SERIAL

Primary Key

Unique conversation ID.

lead_id

INTEGER

Foreign Key (leads.id)

Links to the specific customer.

message_type

VARCHAR(10)

NOT NULL

AI or CUSTOMER.

message_content

TEXT

NOT NULL

The text of the message.

outbound_ref

VARCHAR(255)

NULLABLE

ID from the external WhatsApp API for delivery tracking.

conversation_state

VARCHAR(50)

NOT NULL

AI's current state: INTRO, DISCOVERY, OBJECTION_HANDLING, SOFT_CLOSE, HARD_STOP.

followup_attempt_at

TIMESTAMP

NULLABLE

CRON uses this: The next time the system should check for a reply or send a follow-up.

followup_scheduled_by_customer

TIMESTAMP

NULLABLE

The specific date/time the customer requested for a follow-up (if applicable).

is_active

BOOLEAN

NOT NULL

TRUE if the chat is currently with the AI. FALSE once handed off or stopped.

created_at

TIMESTAMP

NOT NULL



1.3. handoffs Table (Human Escalation)

Records qualified leads and provides the context required for a human salesperson.

Column Name

Data Type

Constraint

Description

id

SERIAL

Primary Key

Unique handoff ID.

lead_id

INTEGER

Foreign Key (leads.id)

Lead being handed off.

handoff_reason

VARCHAR(255)

NOT NULL

CALL_REQUEST, MEETING_REQUEST, AI_HARDSHIP, QUALIFIED_WIN.

ai_summary

TEXT

NOT NULL

Crucial: AI-generated 3-point summary of the conversation.

meeting_invite_data

JSONB

NULLABLE

Details for a scheduled meeting (e.g., date, time, link).

status

VARCHAR(50)

NOT NULL

PENDING, CLAIMED, CLOSED_WON, CLOSED_LOST.

claimed_by_user_id

INTEGER

NULLABLE

Internal ID of the human who claimed the lead.

created_at

TIMESTAMP

NOT NULL



2. Backend Logic (Laravel Cron Jobs)

All jobs must be configured to run as Laravel scheduled commands.

2.1. Cron Job 1: Initial Outreach (daily-outreach)

Schedule: Runs every day at 08:00h UTC, but executes logic based on local time zones (see requirement below).

Target: leads where status = 'NEW' and lead_score > 0.

Logic:

Prioritization: Select the 50 highest-scored leads who have not been contacted today.

Time Zone Check: For each lead, determine local time. If local time is between 09:30 and 16:30, proceed. If not, re-queue for the next day's run.

A/B Test: Assign a random introduction_message_variant (A, B, or C) to the lead (requires an outreach_variants table for tracking).

Send Message: Call the WhatsApp API with the selected message.

Update Database: Set leads.status to OUTREACHED and leads.last_outreach_at to NOW(). Insert the sent message into conversations.

2.2. Cron Job 2: Conversation & Follow-up (conversation-engine)

Schedule: Runs every 10 minutes (or based on expected response time).

Target A (New Replies): Leads where status = 'OUTREACHED' and a new customer reply webhook has been received since the last run.

Target B (Scheduled Follow-ups): Leads where conversations.is_active = TRUE and conversations.followup_attempt_at <= NOW().

Logic:

Process New Replies (Target A):
a.  Set leads.status to REPLIED.
b.  Call AI Role 1 (Response Generator) to draft a reply.
c.  Send the AI reply via WhatsApp API.

Process Scheduled Follow-ups (Target B):
a.  Call AI Role 1 (Response Generator) to generate a contextual follow-up message.
b.  Send the AI reply via WhatsApp API.

Handoff Check (Mandatory): After every customer reply (or AI-generated message), run the Handoff Logic (see Section 4.2).

2.3. Cron Job 3: "No Response" Chase (no-reply-chaser)

Schedule: Runs once daily at 14:00h UTC.

Target: leads where status = 'OUTREACHED' and last_outreach_at is older than 5 days.

Logic:

Send Message: Send a very short, non-intrusive follow-up ("Did you receive my last message?") via WhatsApp API.

Update Database: Set leads.last_outreach_at to NOW() and update the followup_attempt_at to 5 days from now.

3. AI Integration (Gemini API)

The system relies on the Gemini API for two distinct roles. All calls must include the lead's name, company, and industry in the prompt's context.

3.1. AI Role 1: Response Generator (Core Chat Logic)

Model: gemini-2.5-flash-preview-09-2025

Input Context:

The complete conversations history for this lead_id.

The lead's specific data (name, company_name, industry).

The AI's current conversation_state.

System Instruction (Key Directives):

"Act as a professional, empathetic, and persistent B2B sales development representative."

"Your goal is to qualify the lead and book a meeting or call. Do not sell the service, sell the next step."

"If the customer shows any sign of hardship, explicit resistance, or requests a specific meeting/call time, trigger a Handoff Flag."

"If the lead is unresponsive or resistant, pivot by asking for a specific future date and time for follow-up (e.g., 'Does next Tuesday at 2 PM work for you?')."

Output Requirement: The AI must return a JSON object, not just raw text, for easy processing.

{
  "message_text": "The message to send to the customer.",
  "new_conversation_state": "DISCOVERY | OBJECTION_HANDLING | HARD_STOP",
  "handoff_flag": "BOOLEAN",
  "handoff_reason_code": "NULL | CALL_REQUEST | AI_HARDSHIP"
}


3.2. AI Role 2: Handoff Summarizer

Trigger: Executed whenever handoff_flag is TRUE.

Model: gemini-2.5-flash-preview-09-2025

Input Context: The full conversation history, lead data, and the specific reason for the handoff.

System Instruction: "You are an internal analyst. Review the provided chat history. Generate a concise, 3-point bulleted summary designed for a human sales agent to understand the context immediately."

Output Requirement: Plain text summary (to be saved in handoffs.ai_summary).

4. Handoff & Notification Requirements

4.1. Handoff Process Flow

Trigger: Handoff is flagged by AI Role 1 OR a human salesperson manually flags the lead.

Summary Generation: Call AI Role 2 to generate the summary.

Database Insert: Insert a new record into the handoffs table, including the ai_summary, lead_id, and handoff_reason.

Lead Status Update: Set leads.status to HANDED_OFF and conversations.is_active to FALSE.

Notification: Send an immediate notification (e.g., Slack or Email) to the human sales team that a new, qualified lead requires attention. The notification must include the lead's name, phone number, and the 3-point AI Summary.

4.2. Human Interaction & Meeting Scheduling

Tooling: The human team will use an internal Laravel interface to view all handoffs.status = 'PENDING'.

Meeting Data: The system must allow the human agent to log the scheduled meeting details (date, time, platform) which updates the handoffs.meeting_invite_data JSONB field.

Handoff SLA: The human sales agent is expected to claim a lead (handoffs.status = 'CLAIMED') within 4 hours of the notification.

5. Security & Data Management

DO_NOT_CONTACT Enforcement: Any lead with status = 'DO_NOT_CONTACT' must be automatically excluded from ALL Cron Jobs (1, 2, and 3).

Error Handling: Implement robust try...catch blocks around all API calls (WhatsApp, Gemini) with clear logging for failures and automated exponential backoff/retry logic (up to 3 times) before marking a message as failed.

Data Masking: Ensure sensitive data (e.g., conversation content) is handled according to relevant privacy laws. (Developer should confirm this with a legal team, but the requirement is to be mindful of it).r