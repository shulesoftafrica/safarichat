### **Technical Requirements Document: Official WhatsApp Business API Integration via Self-Onboarding**

**Project Name:** Official WhatsApp Business Onboarding
**Target Platform:** Safarichat App (Web, Mobile App)
**Feature Description:** Implement a user-facing self-onboarding flow for businesses to connect their WhatsApp Business Accounts (WABA) to our platform via the Official WhatsApp Business API. This feature will exist as an alternative to the current "unofficial" WhatsApp integration.

---

#### **1. High-Level Logic & User Flow**

The system must provide two distinct paths during business account setup:

* **Option 1: Unofficial Integration (Existing)**: The user proceeds with the current setup method.
* **Option 2: Official Integration (New)**: The user initiates a guided self-onboarding process using Meta's Embedded Signup flow.

The new user flow will be as follows:

1.  **Selection Screen:** After a business user registers on Safarichat, they will be presented with a choice: "Connect Unofficial WhatsApp" or "Connect Official WhatsApp."
2.  **Official Onboarding Trigger:** If "Official WhatsApp" is selected, the application will display a button (e.g., "Connect with Facebook").
3.  **Embedded Signup Launch:** Clicking this button will launch a new pop-up window or modal containing Meta's Embedded Signup flow.
4.  **Meta's Guided Process:** The user will be guided by Meta to:
    * Log in to their Facebook account.
    * Select or create a Facebook Business Manager.
    * Select or create a WhatsApp Business Account (WABA).
    * Select or create a WhatsApp Business Profile.
    * Enter a phone number to be used for the WABA.
    * Verify the phone number via SMS or voice call.
    * Grant the necessary permissions to our application.
5.  **Redirect and Data Capture:** Upon successful completion, the pop-up will close, and Meta will send a callback to our application's pre-configured URL. This callback will contain critical information.
6.  **Server-Side Processing:** Our backend will process the callback, exchange the temporary token for a long-lived one, and store all necessary credentials securely in our database.
7.  **Success Confirmation:** The user interface will update to show a "WhatsApp Connected" or similar success message, indicating the official integration is complete.

---

#### **2. Technical Requirements**

**2.1. Prerequisites (External Dependencies)**

* **Meta for Developers Account:** We must have an active Meta for Developers account with a verified Business Manager.
* **Meta App:** A Meta app must be created with the "WhatsApp" product added and configured.
* **Partner/Tech Provider Status:** We must have an active relationship with a Meta Business Solution Provider (BSP) that supports Embedded Signup (e.g., 360dialog, Twilio, etc.). This provider will give us a unique `partner_id` or `solution_id`.
* **SSL/HTTPS:** The domain where the Embedded Signup redirect URL is hosted **must** be secured with HTTPS.

**2.2. Frontend Requirements**

* **User Interface (UI):**
    * A clear, non-technical UI for the user to select the "Official WhatsApp" option.
    * A prominent button (e.g., "Connect with Facebook") to initiate the flow.
    * A loading state or spinner to display while the user is completing the Meta flow.
    * A success message to confirm completion, and a clear error message if the process fails.
* **Embedded Signup SDK:**
    * Integrate the Meta JavaScript SDK into the relevant frontend page.
    * Configure the SDK with our `app_id` and the `config_id` provided by the BSP.
    * The frontend must handle the `onSuccess` and `onError` callbacks returned by the Meta SDK.

**2.3. Backend Requirements**

* **API Endpoint:** Create a secure, publicly accessible API endpoint to serve as the redirect URL for the Embedded Signup callback (e.g., `https://api.safarichat.com/onboarding/whatsapp/official/callback`).
* **Data Handling:** The backend endpoint must:
    * Receive the temporary token (`code`), `waba_id`, and `phone_number_id` from the callback.
    * Validate the request to ensure it is from a trusted source.
    * Make a server-to-server call to the BSP's API to exchange the temporary token for a long-lived access token.
* **Database Schema:**
    * Extend the existing `business` table or create a new `official_whatsapp_credentials` table.
    * The new table must securely store the following information for each business:
        * `business_id` (Foreign Key to Safarichat business)
        * `waba_id` (WhatsApp Business Account ID)
        * `phone_number_id` (Phone Number ID)
        * `access_token` (Long-lived token)
        * `token_expiration` (Timestamp of token expiration)
        * `api_provider` (e.g., "360dialog", "Twilio")
        * `status` (e.g., 'pending', 'connected', 'disconnected', 'suspended')
* **Token Refresh:** Implement a process (e.g., a scheduled job) to automatically refresh the long-lived access token before it expires, as per the BSP's documentation.

**2.4. Integration with Messaging Logic**

* **Routing Logic:** Modify the messaging logic to route messages based on the business's chosen WhatsApp integration type (official or unofficial).
* **Official API Calls:** The messaging engine must use the newly stored `access_token` and `phone_number_id` to make API calls to the official WhatsApp Business API (via the BSP's API endpoint) for sending and receiving messages.
* **Webhook Listener:** Set up a webhook listener to receive incoming messages and status updates from Meta/BSP and route them to the correct business within Safarichat.

---

#### **3. Development & Testing Plan**

1.  **Phase 1: Setup & Prerequisities:**
    * Create a Meta App and configure it for the WhatsApp product.
    * Obtain necessary credentials (`app_id`, `config_id`, `partner_id`) from our chosen BSP.
    * Configure the redirect URL and webhook URL in the BSP's partner hub.
2.  **Phase 2: Backend Development:**
    * Build the secure redirect API endpoint.
    * Implement the logic to handle the token exchange and data storage.
3.  **Phase 3: Frontend Development:**
    * Build the user-facing UI for the two options.
    * Integrate the Meta JavaScript SDK and the "Connect" button.
    * Handle the success and failure states.
4.  **Phase 4: Integration Testing:**
    * Perform end-to-end tests of the entire onboarding flow using a test business account.
    * Verify that all data (`waba_id`, `phone_number_id`, `access_token`) is correctly captured and stored.
5.  **Phase 5: Production Deployment:**
    * Deploy the new code to production.
    * Monitor the onboarding flow for any issues.

