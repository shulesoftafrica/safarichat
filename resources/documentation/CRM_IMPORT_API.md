# CRM Import API Documentation

## Overview
Simple APIs to import contacts and conversation context from external CRM systems into SafariChat.

## Endpoints

### 1. Import Contacts
**POST** `/api/crm/import/contacts`

Import contacts from external CRM into SafariChat.

#### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body
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

#### Response
```json
{
  "success": true,
  "data": {
    "imported": [
      {
        "id": 123,
        "crm_id": "CRM-12345",
        "name": "John Doe",
        "phone": "+1234567890",
        "email": "john@example.com"
      }
    ],
    "skipped": [],
    "imported_count": 1,
    "skipped_count": 0,
    "error_count": 0,
    "errors": [],
    "total_processed": 1
  },
  "message": "Contact import completed"
}
```

### 2. Import Conversation Context
**POST** `/api/crm/import/context`

Import conversation history and context for a contact from external CRM.

#### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body
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
    },
    {
      "message_content": "Thank you for your interest. Let me help you with that.",
      "sender_type": "agent",
      "timestamp": "2024-01-15T10:45:00Z",
      "crm_conversation_id": "CONV-002"
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

#### Response
```json
{
  "success": true,
  "data": {
    "contact": {
      "id": 123,
      "crm_id": "CRM-12345",
      "name": "John Doe"
    },
    "lead": {
      "id": 456,
      "status": "new"
    },
    "imported_conversations": [
      {
        "id": 789,
        "crm_conversation_id": "CONV-001",
        "message_type": "CUSTOMER",
        "sender_type": "customer",
        "timestamp": "2024-01-15T10:30:00Z",
        "message_preview": "Hi, I'm interested in your products..."
      }
    ],
    "imported_count": 2,
    "error_count": 0,
    "errors": [],
    "context_data": {
      "contact_background": true,
      "previous_interactions": true,
      "customer_preferences": true
    }
  },
  "message": "Context import completed successfully"
}
```

### 3. Get Contact Context
**GET** `/api/crm/import/contacts/{crm_id}/context`

Retrieve imported contact with full conversation context.

#### Headers
```
Authorization: Bearer {token}
```

#### Response
```json
{
  "success": true,
  "data": {
    "contact": {
      "id": 123,
      "crm_id": "CRM-12345",
      "name": "John Doe",
      "phone": "+1234567890",
      "email": "john@example.com",
      "crm_data": {
        "company": "Acme Corp",
        "industry": "Technology",
        "context_imported": true,
        "conversation_count": 2
      }
    },
    "lead": {
      "id": 456,
      "status": "new",
      "lead_score": 50,
      "metadata": {
        "crm_imported": true,
        "contact_background": {...},
        "previous_interactions": [...],
        "customer_preferences": {...}
      }
    },
    "conversations": [
      {
        "id": 789,
        "message_type": "CUSTOMER",
        "sender_type": "customer",
        "message_content": "Hi, I'm interested in your products.",
        "conversation_state": "INTRO",
        "is_imported": true,
        "original_timestamp": "2024-01-15T10:30:00Z",
        "created_at": "2024-01-15T10:30:00Z"
      }
    ]
  },
  "message": "Contact context retrieved successfully"
}
```

## Field Mappings

### Contact Fields
- `crm_id` → Unique identifier from external CRM
- `name` → Contact's full name
- `phone` → Primary phone number
- `email` → Primary email address
- `company` → Company/organization name
- `industry` → Business industry
- `crm_status` → Status in external CRM
- `tags` → Array of tags/labels
- `custom_fields` → Custom data from CRM
- `created_in_crm` → Original creation date
- `updated_in_crm` → Last update date

### Conversation Fields
- `message_content` → Full message text
- `sender_type` → Who sent the message:
  - `customer` → Contact/customer message
  - `agent` → Human agent message
  - `system` → System/automated message
- `timestamp` → When message was sent
- `crm_conversation_id` → Reference ID from CRM
- `metadata` → Additional message data
- `tags` → Message tags/labels

## Error Handling

### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "contacts.0.name": ["The name field is required."]
  }
}
```

### Contact Not Found (404)
```json
{
  "success": false,
  "message": "Contact not found. Please import the contact first."
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Error importing contacts"
}
```

## Usage Notes

1. **Contact Import First**: Always import contacts before importing their conversation context.

2. **Batch Limits**: 
   - Contacts: Maximum 1000 per request
   - Conversations: Maximum 500 per request

3. **Duplicate Handling**: Contacts are skipped if they already exist (matched by phone or CRM ID).

4. **Data Preservation**: All imported conversations are marked as historical (not active) and preserve original timestamps.

5. **Lead Creation**: A lead is automatically created or found for each contact when importing context.