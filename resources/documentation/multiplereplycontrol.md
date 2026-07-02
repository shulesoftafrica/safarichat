Here's a clear, developer-focused prompt you can give your AI developer or another AI coding assistant.

---

## Prompt: Update Conversation Workflow to Prevent Multiple Consecutive AI Replies

### Problem

The current conversation workflow allows the AI to send multiple consecutive messages to the same WhatsApp number without waiting for the user to respond.

Example from the attached screenshot:

* AI sends one reply.
* Immediately sends another reply.
* Then sends another reply again.

This creates several problems:

* Poor user experience.
* Appears spammy.
* Reduces user trust.
* Increases unnecessary messaging costs.
* Can confuse users because multiple responses arrive without any new user input.

### Required Behavior

Implement a strict **one AI response per user message** policy.

The workflow should work as follows:

1. Whenever a user sends a message:

   * Generate **only one** AI response.
   * Send that response.
   * Mark the conversation as **Waiting for User Reply**.

2. While the conversation is in the **Waiting for User Reply** state:

   * Ignore any workflow triggers that would generate another AI response.
   * Do not send follow-up messages.
   * Do not continue the conversation automatically.

3. The AI should only generate another response after:

   * The user sends a new message.

4. The only exception is scheduled reminders.

### Reminder Exception

The system may send a follow-up message only if:

* At least one full day has passed since the last AI message.
* The user has not replied.
* A reminder is scheduled according to the reminder workflow.

Example:

Day 1

```
User: Tell me about your software.

AI:
Thanks for reaching out. Here's an overview...
```

(No more AI messages.)

Day 2 (if no reply)

```
AI:
Just checking in to see if you had any questions or would like to schedule a demo.
```

After this reminder, the system should again wait for the user to respond.

### State Machine

Implement the conversation as a state machine.

States:

```
IDLE
    ↓
User Message Received
    ↓
Generate ONE AI Response
    ↓
WAITING_FOR_USER
```

While in `WAITING_FOR_USER`:

* Block all AI-generated messages.
* Ignore duplicate workflow executions.
* Ignore delayed LLM completions.
* Ignore repeated triggers.
* Ignore webhook retries.
* Wait for the next user message.

Allowed transitions:

```
WAITING_FOR_USER
        ↓
User replies
        ↓
Generate ONE response
        ↓
WAITING_FOR_USER
```

or

```
WAITING_FOR_USER
        ↓
24+ hours elapsed
        ↓
Send ONE reminder
        ↓
WAITING_FOR_USER
```

### Technical Requirements

Please update the workflow so that:

* Only one outbound AI message can exist for each inbound user message.
* Prevent duplicate executions caused by multiple triggers.
* Use conversation state (or a database flag such as `waiting_for_user = true`) before generating a response.
* Ensure concurrent workflow executions cannot bypass this rule (implement locking, idempotency, or atomic updates if necessary).
* If the workflow is triggered while the conversation is already waiting for the user, terminate the workflow without sending a message.

### Acceptance Criteria

The implementation is complete when:

* ✅ Every user message receives exactly one AI response.
* ✅ No consecutive AI messages are sent without new user input.
* ✅ Duplicate workflow executions do not produce additional replies.
* ✅ The only automated follow-up allowed is the scheduled next-day reminder.
* ✅ After any AI message (including reminders), the system waits for the user's next message before responding again.

### Goal

The conversation should always follow this pattern:

```
User
   ↓
AI (one reply)
   ↓
WAIT
   ↓
User
   ↓
AI (one reply)
   ↓
WAIT
```

Never:

```
User
↓
AI
↓
AI
↓
AI
↓
AI
```

This behavior is critical to improve platform reliability, reduce message spam, lower messaging costs, and increase user trust.
