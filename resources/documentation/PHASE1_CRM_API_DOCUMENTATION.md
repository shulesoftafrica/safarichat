# Phase 1 CRM Integration API Documentation

## Overview

This document outlines the comprehensive Lead Management API endpoints implemented in Phase 1 of the CRM integration. These APIs provide full lead lifecycle management, contact-lead relationships, and product-lead associations.

## Authentication

All endpoints require API authentication using Laravel Sanctum:

```
Authorization: Bearer your_api_token_here
Content-Type: application/json
Accept: application/json
```

## Base URL

```
https://yourdomain.com/api
```

---

## Lead Management Endpoints

### 1. Create Lead

**POST** `/api/leads`

Create a new lead from an existing contact with product associations.

#### Request Body
```json
{
    "events_guest_id": 123,
    "product_ids": [1, 2, 3],
    "primary_product_id": 1,
    "ai_sales_agent_id": 1,
    "company_name": "Tech Solutions Ltd",
    "industry": "Software",
    "source": "api",
    "notes": "Interested in our enterprise solutions",
    "metadata": {
        "campaign": "Q4_2025",
        "referral_source": "linkedin"
    }
}
```

#### Required Fields
- `events_guest_id` - ID of existing contact
- `product_ids` - Array of product IDs (minimum 1)

#### Success Response (201)
```json
{
    "success": true,
    "data": {
        "id": 456,
        "contact": {
            "id": 123,
            "name": "John Doe",
            "phone": "+255789123456",
            "email": "john@techsolutions.com"
        },
        "company_name": "Tech Solutions Ltd",
        "industry": "Software",
        "status": "NEW",
        "source": "api",
        "lead_score": 50,
        "is_churned": false,
        "products": [
            {
                "id": 1,
                "name": "Enterprise CRM",
                "status": "INTERESTED",
                "is_primary": true,
                "quoted_price": null
            }
        ],
        "created_at": "2025-12-08T10:00:00.000000Z"
    },
    "message": "Lead created successfully"
}
```

---

### 2. List Leads

**GET** `/api/leads`

Retrieve all leads for the authenticated user with filtering options.

#### Query Parameters
- `status` - Filter by lead status (NEW, OUTREACHED, etc.)
- `source` - Filter by lead source
- `product_id` - Filter by associated product
- `is_churned` - Filter by churn status (true/false)
- `min_score` - Minimum lead score
- `max_score` - Maximum lead score
- `search` - Search in name, email, phone, company
- `sort_by` - Sort field (created_at, lead_score, name, status)
- `sort_direction` - Sort direction (asc, desc)
- `per_page` - Items per page (max 50)

#### Success Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 456,
            "contact": {
                "id": 123,
                "name": "John Doe",
                "phone": "+255789123456",
                "email": "john@techsolutions.com"
            },
            "status": "NEW",
            "lead_score": 75,
            "products": [...],
            "created_at": "2025-12-08T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 67
    },
    "message": "Leads retrieved successfully"
}
```

---

### 3. Get Lead Details

**GET** `/api/leads/{id}`

Retrieve detailed information about a specific lead including conversation history.

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "id": 456,
        "contact": {...},
        "status": "QUALIFIED",
        "products": [...],
        "conversations": [
            {
                "id": 789,
                "message_type": "AI_AGENT",
                "content": "Hi John, I'd like to discuss our CRM solution...",
                "conversation_state": "PITCH",
                "confidence_score": 0.85,
                "created_at": "2025-12-08T11:30:00.000000Z"
            }
        ],
        "created_at": "2025-12-08T10:00:00.000000Z"
    },
    "message": "Lead retrieved successfully"
}
```

---

### 4. Update Lead Status

**PUT** `/api/leads/{id}/status`

Update lead status and add notes.

#### Request Body
```json
{
    "status": "QUALIFIED",
    "notes": "Customer confirmed budget and timeline",
    "assigned_agent_id": 5
}
```

#### Valid Status Values
- NEW, OUTREACHED, REPLIED, QUALIFIED, PITCHED
- DEMO_SCHEDULED, PROPOSAL_SENT, NEGOTIATING
- CLOSED, LOST, HANDED_OFF, DO_NOT_CONTACT

---

### 5. Assign Lead

**POST** `/api/leads/{id}/assign`

Assign a lead to a human agent.

#### Request Body
```json
{
    "assigned_agent_id": 5,
    "notes": "Complex technical requirements, needs expert consultation"
}
```

---

### 6. Lead Timeline

**GET** `/api/leads/{id}/timeline`

Get chronological activity timeline for a lead.

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "lead_id": 456,
        "timeline": [
            {
                "id": 789,
                "type": "conversation",
                "message_type": "CUSTOMER",
                "content": "I'm interested in your CRM solution",
                "conversation_state": "INTRO",
                "timestamp": "2025-12-08T11:30:00.000000Z"
            }
        ]
    },
    "message": "Lead timeline retrieved successfully"
}
```

---

## Bulk Operations

### 7. Bulk Create Leads

**POST** `/api/leads/bulk-create`

Create multiple leads from contacts in a single request.

#### Request Body
```json
{
    "leads": [
        {
            "events_guest_id": 123,
            "product_ids": [1, 2],
            "company_name": "Tech Corp",
            "industry": "Technology",
            "source": "import"
        },
        {
            "events_guest_id": 124,
            "product_ids": [1],
            "company_name": "Design Studio",
            "industry": "Creative"
        }
    ]
}
```

#### Success Response (201)
```json
{
    "success": true,
    "data": {
        "created": [...],
        "created_count": 2,
        "error_count": 0,
        "errors": []
    },
    "message": "Bulk lead creation completed"
}
```

---

### 8. Bulk Update Leads

**PUT** `/api/leads/bulk-update`

Update status of multiple leads simultaneously.

#### Request Body
```json
{
    "updates": [
        {
            "lead_id": 456,
            "status": "OUTREACHED",
            "notes": "Initial outreach completed"
        },
        {
            "lead_id": 457,
            "status": "QUALIFIED",
            "notes": "Budget confirmed"
        }
    ]
}
```

---

## Sales Pipeline & Analytics

### 9. Sales Pipeline

**GET** `/api/leads/pipeline`

Get sales pipeline summary and analytics.

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "pipeline": {
            "NEW": {"count": 15, "avg_score": 65.2},
            "OUTREACHED": {"count": 8, "avg_score": 70.5},
            "QUALIFIED": {"count": 5, "avg_score": 85.0},
            "CLOSED": {"count": 3, "avg_score": 95.0}
        },
        "total_leads": 31,
        "recent_activity": [
            {
                "lead_id": 456,
                "contact_name": "John Doe",
                "status": "QUALIFIED",
                "last_interaction_at": "2025-12-08T11:30:00.000000Z"
            }
        ]
    },
    "message": "Pipeline data retrieved successfully"
}
```

---

## Churn Management

### 10. Mark Lead as Churned

**POST** `/api/leads/{id}/churn`

Mark a lead as churned with reason and date.

#### Request Body
```json
{
    "churn_reason": "Price too high",
    "churn_date": "2025-12-08",
    "notes": "Customer found cheaper alternative"
}
```

### 11. Reactivate Churned Lead

**POST** `/api/leads/{id}/reactivate`

Reactivate a previously churned lead.

#### Request Body
```json
{
    "notes": "Customer situation changed, ready to re-engage"
}
```

---

## Product-Lead Relationships

### 12. Associate Products with Lead

**POST** `/api/leads/{leadId}/products`

Add products to an existing lead.

#### Request Body
```json
{
    "product_ids": [3, 4],
    "primary_product_id": 3
}
```

### 13. Get Lead Products

**GET** `/api/leads/{leadId}/products`

Get all products associated with a lead.

### 14. Update Product Status

**PUT** `/api/leads/{leadId}/products/{productId}/status`

Update status for a specific product on a lead.

#### Request Body
```json
{
    "status": "PITCHED",
    "quoted_price": 1500.00,
    "discount_applied": 150.00,
    "sales_notes": "Customer interested in premium features",
    "demo_scheduled_date": "2025-12-15",
    "next_followup_at": "2025-12-16T09:00:00Z"
}
```

#### Valid Product Status Values
- INTERESTED, PITCHED, DEMO_REQUESTED, DEMO_COMPLETED
- PROPOSAL_SENT, NEGOTIATING, CLOSED, LOST

### 15. Remove Product from Lead

**DELETE** `/api/leads/{leadId}/products/{productId}`

Remove product association from lead (if not the only product).

### 16. Set Primary Product

**PUT** `/api/leads/{leadId}/products/{productId}/primary`

Set a specific product as primary for the lead.

---

## Contact-Lead Relationships

### 17. Get Leads for Contact

**GET** `/api/contacts/{contactId}/leads`

Get all leads associated with a specific contact.

### 18. Get Leads for Product

**GET** `/api/products/{productId}/leads`

Get all leads interested in a specific product.

#### Query Parameters
- `product_status` - Filter by product-specific status
- `lead_status` - Filter by overall lead status
- `sort_by` - Sort by various fields including `product_interaction`

---

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "events_guest_id": ["The events guest id field is required."],
        "product_ids": ["The product ids field must contain at least 1 items."]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Lead not found"
}
```

### Access Denied (403)
```json
{
    "success": false,
    "message": "Contact not found or access denied"
}
```

### Conflict (409)
```json
{
    "success": false,
    "message": "Active lead already exists for this contact with one or more of the specified products"
}
```

---

## Usage Examples

### PHP Example
```php
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://yourdomain.com/api/leads',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer your_api_token_here',
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'events_guest_id' => 123,
        'product_ids' => [1, 2],
        'company_name' => 'Tech Solutions',
        'industry' => 'Software'
    ])
]);

$response = curl_exec($curl);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Lead created: " . $data['data']['id'];
}
```

### JavaScript Example
```javascript
const response = await fetch('https://yourdomain.com/api/leads', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer your_api_token_here',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        events_guest_id: 123,
        product_ids: [1, 2],
        company_name: 'Tech Solutions',
        industry: 'Software'
    })
});

const data = await response.json();
console.log('Lead created:', data.data.id);
```

---

## Implementation Notes

1. **User Isolation**: All leads are automatically filtered by the authenticated user
2. **Duplicate Prevention**: System prevents creating duplicate active leads for same contact-product combinations
3. **Relationship Integrity**: Lead-product relationships are properly maintained with primary product designation
4. **Audit Trail**: All interactions update `last_interaction_at` timestamps
5. **Bulk Operations**: Support for efficient batch processing with detailed error reporting
6. **Pagination**: All list endpoints support pagination with configurable page sizes
7. **Search & Filtering**: Comprehensive filtering options for lead discovery and management

---

## Phase 2 Roadmap

The next phase will implement:
- **Conversation Management APIs** - Direct conversation history access and management
- **Real-time Updates** - WebSocket/SSE support for live lead updates
- **Advanced Analytics** - Detailed metrics and reporting endpoints
- **External CRM Sync** - Bidirectional synchronization with external CRM systems

This Phase 1 implementation provides a solid foundation for comprehensive lead lifecycle management within the SafariChat CRM integration.