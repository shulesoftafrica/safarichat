
## 🚀 Upgrade Instructions for AI Sales Agent Focus

### **Phase 1: Database Schema & Model Updates (PostgreSQL)**

1.  **Products Table Field Update (`products`):**

      * Add a new column: `is_active_campaign` with data type **BOOLEAN** and a default value of `FALSE`.
      * Add a new column: `campaign_hook_text` with data type **VARCHAR(255)**. (The concise, irresistible benefit).
      * Add a new column: `campaign_pain_point` with data type **VARCHAR(255)**. (The core problem the product solves).
      * Add a new column: `campaign_attachment_path` with data type **VARCHAR(512)**. (File location of the brochure, flyer, etc.).

2.  **Enforce Single Active Campaign:**

      * Implement a **Partial Unique Index** on the `products` table to ensure that only one product can have `is_active_campaign = TRUE` at any time.

    <!-- end list -->

    ```sql
    CREATE UNIQUE INDEX one_active_campaign_check
    ON products (is_active_campaign)
    WHERE is_active_campaign = TRUE;
    ```

3.  **Products Model Update:**

      * Update your corresponding PHP model for product to map these new columns (`is_active_campaign`, `campaign_hook_text`, `campaign_pain_point`, `campaign_attachment_path`).

4.  **Company Credibility Kit:**

use the table `business` , add these column to store business information (mission, credibility_statistics)
      * Ensure there `business` table to store core, concise company information for quick retrieval by the AI:
          * `mission` (e.g., "We automate tasks for e-commerce businesses.")
          * `credibility_statistics` (e.g., "500 clients since 2018.")
          * `website` (The single official website/about link).

### **Phase 2: Product Management UI/UX Logic**

1.  **Active Campaign Selector:**

      * On the product add/edit form, replace the current logic with a single toggle/checkbox for "Set as Active Campaign."
      * **Enforcement:** When a user sets a product as the Active Campaign, the application logic must automatically set all *other* products' `is_active_campaign` status to `FALSE` before setting the selected one to `TRUE` (using the transactional SQL method).

2.  **Campaign Content Fields:**

      * Make the following fields **required** when the "Set as Active Campaign" checkbox is ticked:
          * `campaign_hook_text`
          * `campaign_pain_point`
          * An upload field for the single campaign attachment, saving the path to `campaign_attachment_path`.

### **Phase 3: AI Sales Message Generation Logic**

1.  **Update `Message.php` controller, method `generatePersonalizedSalesMessage()`:**

      * **Data Retrieval:** The method must first query the `products` table to retrieve **only** the single product where `is_active_campaign = TRUE`.
      * **AI Prompt Data Inputs:** The AI's prompt for generating the initial sales message must be fed the following data points:
          * The active product's `campaign_hook_text`.
          * The active product's `campaign_pain_point`.
          * The user's `user_preference_data` (tone, focus, etc.).
          * The `previous_conversation_history`.

2.  **Enforce Consultative Hook Structure:**

      * Instruct the AI to generate a message that follows this strict three-part structure, optimized for WhatsApp text:
          * **Part 1 (Context):** Acknowledge the user and relevant context (`{$name}, {$businessContext}`).
          * **Part 2 (Hook):** Use the `campaign_pain_point` to state the problem and immediately introduce the product using the `campaign_hook_text` as the solution.
          * **Part 3 (CTA & Offer):** End with a low-friction question and **offer** the attachment if `campaign_attachment_path` is not empty.

3.  **Attachment Logic:**

      * The attachment **must not be sent in the initial message.**
      * The message should *offer* it: "I can send you our [Brochure/Flyer Name] that details this. Shall I send it over?" but will be controlled in webhook not in this initial engagement
      * The AI logic must wait for a positive user response ("Yes," "Please send," etc.) before executing the action to send the file stored in `campaign_attachment_path`.

This focused, data-driven approach will ensure your automated sales messages are relevant, engaging, and maximize your chances of getting a reply.