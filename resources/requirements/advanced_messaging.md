moving from a **"Blast"** model (one-to-many) to a **"Hyper-Personalized Engagement"** model (one-to-one at scale).

The module to send message, needs to transition from a synchronous sender to an asynchronous processing pipeline. 

## Technical Requirements: AI-Driven Contextual Message Refinement

### 1. Objective

Replace the direct-send mechanism with an **Asynchronous Personalization Pipeline**. The system must intercept the "Send" command, analyze each recipient's historical context, and re-write the core message to match their specific language, tone, and relationship stage before delivering it.

### 2. Core Workflow (The Pipeline)

1. **Staging Phase:** When the user clicks "Send," the system creates a `Campaign` record and a `MessageQueue` entry for every recipient in the selected category. Status: `Pending_Analysis`.
2. **Context Retrieval:** For each queue item, the system fetches the last 5–10 messages from the `Conversations` database for that specific contact.
3. **AI Analysis & Refinement:** * **Input:** Original Message + Attachments + Interaction History.
* **Logic:** Determine primary language (English, Swahili, etc.) and tone (Formal, Casual, Urgent).
* **Transformation:** Re-write the original message to include the contact's name (using the `#name` tag) and align with the detected tone while preserving the original intent and call-to-action.


4. **Final Delivery:** The refined message is updated in the `MessageQueue` and sent via the WhatsApp API.

### 3. Database Schema Updates

* **`MessageQueue` Table:** Needs fields for `original_content`, `refined_content`, `status` (Staged, Analyzing, Refined, Sent, Failed), and `metadata` (detected tone/language).
* **`Contacts` Table:** Ensure there is a foreign key link to a `Messages/Chat` table to allow for rapid history lookups.

### 4. Logic Prompt for the AI Agent (The "Refiner")

> "Act as a Senior Sales Specialist. Take the [Original_Message] and [Attachments]. Look at the [Contact_History]. Re-write the message specifically for this contact.
> * **Constraint 1:** Keep the core offer/information identical.
> * **Constraint 2:** Match the contact's preferred language.
> * **Constraint 3:** Use a tone that matches their previous interactions.
> * **Constraint 4:** If the contact has a pending question in history, briefly acknowledge it in the intro."
> 
> 

---

## The "World-Class" Advice: Leveling Up Engagement

If you want this to be the most effective sales engagement tool in existence, don't just stop at "tone matching." I recommend adding these three "Pro" layers to your requirements:

### A. The "Best-Time-to-Send" Algorithm

Don't send all messages at once. Based on the `Contact_History`, have the AI determine when that specific user usually replies (e.g., mornings vs. late nights) and schedule the queue item for that specific window to stay at the top of their inbox.

### B. Sentiment-Based Filtering

If the AI detects that the last message from a contact was a complaint or a "Do not contact me" request, the system should **auto-block** the message and move it to a `Human_Review` status instead of sending an awkward sales pitch.

### C. Attachment Contextualization

If you upload an image (e.g., a flyer), have the AI "describe" why it's sending that specific image to *that* person.

* *Example:* "Hey John, I noticed you were interested in our premium plan last month; I’ve attached the new discount flyer below that fits your budget."

---
