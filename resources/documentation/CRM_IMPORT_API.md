## Postman Collection: CRM Import API Endpoints & Parameters

Below is a summary of the main endpoints and parameters for the SafariChat CRM Import API, formatted for easy use in Postman.

---

## Authentication Setup

Before using any API endpoints, you need to obtain a USER_TOKEN for authentication using your phone number and User UUID.

### **Get USER_TOKEN (CRM API Authentication)**

**Step 1: Request Access Information**
- **Method:** `POST`
- **URL:** `/api/crm-auth/request-access`
- **Headers:**
  - `Content-Type: application/json`
- **Body (JSON):**
  ```json
  {
    "phone": "+1234567890"
  }
  ```
  **Note:** Phone number supports both local format (e.g., "0714852469") and international format (e.g., "+255714852469").
- **Response:**
  ```json
  {
    "success": true,
    "message": "Account found. Use your phone number and user UUID for authentication.",
    "data": {
      "phone": "+1234567890",
      "user_exists": true,
      "instruction": "Find your User UUID in the Settings page of your dashboard"
    }
  }
  ```

**Step 2: Authenticate with Phone + UUID**
- **Method:** `POST`
- **URL:** `/api/crm-auth/authenticate-user`
- **Headers:**
  - `Content-Type: application/json`
- **Body (JSON):**
  ```json
  {
    "phone": "+1234567890",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }
  ```
  **Note:** Phone number supports both local format (e.g., "0714852469") and international format (e.g., "+255714852469").
- **Response:**
  ```json
  {
    "success": true,
    "access_token": "your_token_here",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "User Name",
      "phone": "+1234567890",
      "uuid": "550e8400-e29b-41d4-a716-446655440000"
    },
    "message": "Authentication successful",
    "permissions": [
      "crm:import:contacts",
      "crm:import:conversations",
      "crm:export:data"
    ]
  }
  ```

**Note:** 
- Copy the `access_token` value and use it as `{{USER_TOKEN}}` in subsequent API calls.
- Your User UUID can be found in the Settings page of your SafariChat dashboard.
- The authentication token has specific CRM permissions for security.

---

### 1. **Create API Key**

- **Method:** `POST`
- **URL:** `/api/api-keys`
- **Headers:**
  - `Content-Type: application/json`
  - `Authorization: Bearer {{USER_TOKEN}}`
- **Body (JSON):**
  ```json
  {
    "name": "CRM Integration - Production",
    "permissions": [
      "crm:import:contacts",
      "crm:import:conversations",
      "crm:export:data"
    ],
    "expires_at": "2025-12-31T23:59:59Z",
    "metadata": {
      "integration": "hubspot",
      "environment": "production"
    }
  }
  ```

---

### 2. **Import Contacts**

- **Method:** `POST`
- **URL:** `/api/crm/import/contacts`
- **Headers:**
  - `Content-Type: application/json`
  - `Authorization: Bearer {{API_KEY}}`
- **Body (JSON):**
  ```json
  {
    "contacts": [
      {
        "crm_id": "CRM-12345",
        "name": "John Doe",
        "phone": "+1234567890",
        "email": "john@example.com",
        "company": "Acme Corp",
        "industry": "Technology",
        "crm_status": "qualified",
        "tags": ["vip", "enterprise"],
        "custom_fields": {
          "budget": "$50,000",
          "decision_maker": true
        },
        "created_in_crm": "2024-01-15T10:00:00Z",
        "updated_in_crm": "2024-01-20T15:30:00Z"
      }
    ]
  }
  ```

---

### 3. **Import Conversation Context**

- **Method:** `POST`
- **URL:** `/api/crm/import/context`
- **Headers:**
  - `Content-Type: application/json`
  - `Authorization: Bearer {{API_KEY}}`
- **Body (JSON):**
  ```json
  {
    "contact_crm_id": "CRM-12345",
    "conversations": [
      {
        "message_content": "Hi, I'm interested in your products.",
        "sender_type": "customer",
        "timestamp": "2024-01-15T10:30:00Z",
        "crm_conversation_id": "CONV-001",
        "metadata": {
          "channel": "email",
          "priority": "high"
        },
        "tags": ["inquiry", "product_interest"]
      }
    ],
    "contact_background": {
      "company_size": "500+ employees",
      "previous_purchases": ["Product A", "Product B"]
    },
    "previous_interactions": [
      {
        "type": "demo",
        "date": "2024-01-10",
        "outcome": "interested"
      }
    ],
    "customer_preferences": {
      "contact_method": "email",
      "best_time": "morning"
    }
  }
  ```

---

### 4. **Get Contact Context**

- **Method:** `GET`
- **URL:** `/api/crm/import/contacts/{{crm_id}}/context`
- **Headers:**
  - `Authorization: Bearer {{API_KEY}}`

---

### 5. **Create Webhook Endpoint**

- **Method:** `POST`
- **URL:** `/api/crm/webhooks`
- **Headers:**
  - `Content-Type: application/json`
  - `Authorization: Bearer {{API_KEY}}`
- **Body (JSON):**
  ```json
  {
    "webhook_url": "https://your-crm.com/api/webhooks/safarichat",
    "events": [
      "conversation.new_message",
      "conversation.status_changed",
      "lead.updated",
      "conversation.completed"
    ],
    "secret": "your-webhook-secret-key",
    "active": true
  }
  ```

---

### 6. **API Key Management**

- **List API Keys**
  - **Method:** `GET`
  - **URL:** `/api/api-keys`
  - **Headers:** `Authorization: Bearer {{USER_TOKEN}}`

- **Update API Key**
  - **Method:** `PUT`
  - **URL:** `/api/api-keys/{{id}}`
  - **Headers:**
    - `Authorization: Bearer {{USER_TOKEN}}`
    - `Content-Type: application/json`
  - **Body (JSON):**
    ```json
    {
      "name": "Updated Name",
      "permissions": ["crm:import:contacts"],
      "expires_at": "2026-01-01T00:00:00Z"
    }
    ```

- **Test API Key**
  - **Method:** `GET`
  - **URL:** `/api/api-keys/{{id}}/test`
  - **Headers:** `Authorization: Bearer {{API_KEY}}`

- **Revoke API Key**
  - **Method:** `DELETE`
  - **URL:** `/api/api-keys/{{id}}`
  - **Headers:** `Authorization: Bearer {{USER_TOKEN}}`

---

### **Variables**

- `{{USER_TOKEN}}`: Your user authentication token (for API key management)
- `{{API_KEY}}`: The generated API key (for CRM import endpoints)
- `{{crm_id}}`: The CRM contact ID
- `{{id}}`: API key ID

---

**Base URLs:**
- Production: `https://safarichat.ai/api`
- Development: `http://localhost:8000/api`

---

**Tip:** You can import these endpoints into Postman as a collection and use variables for tokens and IDs.
