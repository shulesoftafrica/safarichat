# SafariChat Unified Notification API Documentation

## Overview

The SafariChat Unified Notification API provides a comprehensive solution for sending WhatsApp messages through a centralized notification system. It integrates with the unified notification service at `https://notifcations.shulesoft.africa/api` to deliver messages reliably and efficiently.

## Base URL
```
Production: https://your-production-domain.com/api
Development: http://localhost/safarichat/public/api
```

## Authentication

All API endpoints require Bearer token authentication using Laravel Sanctum.

### Getting an API Token
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "your@email.com",
  "password": "your_password"
}
```

Response:
```json
{
  "access_token": "your_token_here",
  "token_type": "Bearer"
}
```

### Using the Token
Include the token in the Authorization header:
```
Authorization: Bearer your_token_here
```

## Rate Limiting

- **Standard API**: 1000 requests per hour per IP
- **Per User**: 500 requests per hour per authenticated user
- **Bulk Operations**: 50 requests per hour per user

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in window
- `X-RateLimit-Reset`: Window reset timestamp

## API Endpoints

### 1. Send Single Notification

Send a single WhatsApp message.

**Endpoint:** `POST /notifications`

**Request Body:**
```json
{
  "phone_number": "+254700000000",
  "message": "Your notification message here",
  "message_type": "text",
  "priority": "normal",
  "metadata": {
    "campaign_id": "summer_2025",
    "user_segment": "premium"
  }
}
```

**Response:**
```json
{
  "id": 123,
  "external_id": "ext_abc123",
  "status": "pending",
  "phone_number": "+254700000000",
  "message": "Your notification message here",
  "priority": "normal",
  "provider": "unified_api",
  "created_at": "2025-12-05T09:30:00Z",
  "estimated_delivery": "2025-12-05T09:31:00Z"
}
```

**Status Codes:**
- `201`: Notification created successfully
- `400`: Invalid request data
- `401`: Authentication required
- `429`: Rate limit exceeded
- `500`: Internal server error

### 2. Send Bulk Notifications

Send multiple notifications in a single request.

**Endpoint:** `POST /notifications/bulk`

**Request Body:**
```json
{
  "notifications": [
    {
      "phone_number": "+254700000001",
      "message": "Message for user 1",
      "message_type": "text"
    },
    {
      "phone_number": "+254700000002",
      "message": "Message for user 2",
      "message_type": "text"
    }
  ],
  "priority": "normal",
  "metadata": {
    "batch_id": "batch_001",
    "campaign": "product_launch"
  }
}
```

**Response:**
```json
{
  "batch_id": "batch_abc123",
  "total": 2,
  "created": 2,
  "failed": 0,
  "notifications": [
    {
      "id": 124,
      "phone_number": "+254700000001",
      "status": "pending"
    },
    {
      "id": 125,
      "phone_number": "+254700000002",
      "status": "pending"
    }
  ]
}
```

### 3. Get Notifications List

Retrieve paginated list of notifications.

**Endpoint:** `GET /notifications`

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `status`: Filter by status (pending, sent, delivered, failed)
- `phone_number`: Filter by phone number
- `date_from`: Filter from date (Y-m-d format)
- `date_to`: Filter to date (Y-m-d format)

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "phone_number": "+254700000000",
      "message": "Your notification message",
      "status": "delivered",
      "created_at": "2025-12-05T09:30:00Z",
      "delivered_at": "2025-12-05T09:31:15Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

### 4. Get Single Notification

Retrieve details of a specific notification.

**Endpoint:** `GET /notifications/{id}`

**Response:**
```json
{
  "id": 123,
  "external_id": "ext_abc123",
  "user_id": 1,
  "phone_number": "+254700000000",
  "message": "Your notification message",
  "message_type": "text",
  "status": "delivered",
  "priority": "normal",
  "provider": "unified_api",
  "metadata": {
    "campaign_id": "summer_2025",
    "delivery_attempts": 1
  },
  "created_at": "2025-12-05T09:30:00Z",
  "queued_at": "2025-12-05T09:30:05Z",
  "delivered_at": "2025-12-05T09:31:15Z"
}
```

### 5. Get Notification Status

Get the current status of a notification.

**Endpoint:** `GET /notifications/{id}/status`

**Response:**
```json
{
  "id": 123,
  "status": "delivered",
  "status_history": [
    {
      "status": "pending",
      "timestamp": "2025-12-05T09:30:00Z"
    },
    {
      "status": "sent",
      "timestamp": "2025-12-05T09:30:30Z"
    },
    {
      "status": "delivered",
      "timestamp": "2025-12-05T09:31:15Z"
    }
  ],
  "delivery_info": {
    "attempts": 1,
    "last_attempt": "2025-12-05T09:30:30Z",
    "response_time_ms": 1250
  }
}
```

### 6. Update Notification

Update a notification (limited fields).

**Endpoint:** `PATCH /notifications/{id}`

**Request Body:**
```json
{
  "status": "cancelled",
  "metadata": {
    "cancellation_reason": "user_request",
    "cancelled_by": "admin"
  }
}
```

### 7. Delete Notification

Delete a notification record.

**Endpoint:** `DELETE /notifications/{id}`

**Response:** `204 No Content`

### 8. Get Dashboard Statistics

Get comprehensive statistics for dashboard.

**Endpoint:** `GET /notifications/stats/dashboard`

**Response:**
```json
{
  "overview": {
    "total_notifications": 15420,
    "sent_today": 342,
    "success_rate": 98.5,
    "average_delivery_time": 45.2
  },
  "status_breakdown": {
    "pending": 23,
    "sent": 156,
    "delivered": 14890,
    "failed": 351
  },
  "hourly_stats": [
    {
      "hour": "2025-12-05T09:00:00Z",
      "sent": 45,
      "delivered": 43,
      "failed": 2
    }
  ],
  "top_failure_reasons": [
    {
      "reason": "invalid_number",
      "count": 145
    },
    {
      "reason": "network_error",
      "count": 89
    }
  ]
}
```

### 9. Get Summary Statistics

Get summary statistics for current user.

**Endpoint:** `GET /notifications/stats/summary`

**Response:**
```json
{
  "user_id": 1,
  "period": "last_30_days",
  "total_sent": 1250,
  "delivered": 1205,
  "failed": 45,
  "success_rate": 96.4,
  "total_cost": 125.50,
  "most_used_priority": "normal",
  "peak_sending_hour": 14
}
```

## Session Management API

### 1. Create WaSender Session

Create a new WhatsApp session for message sending.

**Endpoint:** `POST /wasender/sessions/create`

**Request Body:**
```json
{
  "instance_name": "business_account_1",
  "webhook_url": "https://your-domain.com/webhooks/whatsapp",
  "webhook_events": ["messages.received", "session.status"]
}
```

**Response:**
```json
{
  "session_id": "session_abc123",
  "instance_name": "business_account_1",
  "status": "initializing",
  "qr_code": "data:image/png;base64,iVBOR...",
  "qr_expires_at": "2025-12-05T09:35:00Z",
  "webhook_url": "https://your-domain.com/webhooks/whatsapp"
}
```

### 2. Get Session Status

Check the status of a WhatsApp session.

**Endpoint:** `GET /wasender/sessions/{sessionId}/status`

**Response:**
```json
{
  "session_id": "session_abc123",
  "status": "connected",
  "connected_phone": "+254700000000",
  "last_activity": "2025-12-05T09:45:00Z",
  "message_capacity": {
    "current": 45,
    "limit": 1000,
    "reset_time": "2025-12-05T10:00:00Z"
  },
  "health": {
    "connection_stable": true,
    "last_heartbeat": "2025-12-05T09:49:30Z",
    "response_time_ms": 120
  }
}
```

### 3. Get QR Code

Get the current QR code for session authentication.

**Endpoint:** `GET /wasender/sessions/{sessionId}/qr`

**Response:**
```json
{
  "session_id": "session_abc123",
  "qr_code": "data:image/png;base64,iVBOR...",
  "expires_at": "2025-12-05T09:35:00Z",
  "status": "waiting_for_scan"
}
```

### 4. List All Sessions

Get all sessions for the authenticated user.

**Endpoint:** `GET /wasender/sessions`

**Response:**
```json
{
  "sessions": [
    {
      "session_id": "session_abc123",
      "instance_name": "business_account_1",
      "status": "connected",
      "connected_phone": "+254700000000",
      "created_at": "2025-12-05T09:00:00Z",
      "last_activity": "2025-12-05T09:45:00Z"
    }
  ],
  "total": 1,
  "active": 1,
  "inactive": 0
}
```

### 5. Destroy Session

Terminate a WhatsApp session.

**Endpoint:** `DELETE /wasender/sessions/{sessionId}`

**Response:** `204 No Content`

## Status Codes and Values

### Notification Statuses
- `pending`: Notification created, waiting to be sent
- `queued`: Added to processing queue
- `processing`: Currently being processed
- `sent`: Successfully sent to provider
- `delivered`: Confirmed delivered to recipient
- `read`: Message read by recipient (if supported)
- `failed`: Delivery failed
- `cancelled`: Manually cancelled

### Priority Levels
- `low`: Processed with delay, lower rate limits
- `normal`: Standard processing (default)
- `high`: Higher rate limits, faster processing
- `urgent`: Immediate processing, highest priority

### Message Types
- `text`: Plain text message
- `image`: Image with optional caption
- `document`: Document file
- `audio`: Audio file
- `video`: Video file

## Error Handling

### Error Response Format
```json
{
  "error": "validation_error",
  "message": "The phone number field is required.",
  "code": 422,
  "timestamp": "2025-12-05T09:30:00Z",
  "request_id": "req_abc123",
  "details": {
    "phone_number": ["The phone number field is required."]
  }
}
```

### Common Error Codes
- `400`: Bad Request - Invalid request data
- `401`: Unauthorized - Authentication required
- `403`: Forbidden - Insufficient permissions
- `404`: Not Found - Resource not found
- `422`: Unprocessable Entity - Validation errors
- `429`: Too Many Requests - Rate limit exceeded
- `500`: Internal Server Error - Server error
- `503`: Service Unavailable - Maintenance mode

## Webhooks

Configure webhooks to receive real-time updates about notification status changes.

### Webhook Events
- `notification.sent`: Notification sent to provider
- `notification.delivered`: Delivery confirmed
- `notification.failed`: Delivery failed
- `notification.read`: Message read (if supported)
- `session.connected`: WhatsApp session connected
- `session.disconnected`: WhatsApp session disconnected

### Webhook Payload
```json
{
  "event": "notification.delivered",
  "notification_id": 123,
  "external_id": "ext_abc123",
  "status": "delivered",
  "timestamp": "2025-12-05T09:31:15Z",
  "data": {
    "phone_number": "+254700000000",
    "delivery_time_ms": 1250,
    "provider_response": {
      "message_id": "provider_msg_123"
    }
  }
}
```

## SDKs and Examples

### cURL Examples

**Send Notification:**
```bash
curl -X POST https://your-domain.com/api/notifications \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+254700000000",
    "message": "Hello from SafariChat!",
    "message_type": "text",
    "priority": "normal"
  }'
```

**Get Statistics:**
```bash
curl -X GET https://your-domain.com/api/notifications/stats/dashboard \
  -H "Authorization: Bearer your_token"
```

### PHP Example
```php
use GuzzleHttp\Client;

$client = new Client();

$response = $client->post('https://your-domain.com/api/notifications', [
    'headers' => [
        'Authorization' => 'Bearer your_token',
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'phone_number' => '+254700000000',
        'message' => 'Hello from SafariChat!',
        'message_type' => 'text',
        'priority' => 'normal'
    ]
]);

$notification = json_decode($response->getBody(), true);
```

### JavaScript/Node.js Example
```javascript
const axios = require('axios');

async function sendNotification() {
    try {
        const response = await axios.post('https://your-domain.com/api/notifications', {
            phone_number: '+254700000000',
            message: 'Hello from SafariChat!',
            message_type: 'text',
            priority: 'normal'
        }, {
            headers: {
                'Authorization': 'Bearer your_token',
                'Content-Type': 'application/json'
            }
        });
        
        console.log('Notification sent:', response.data);
    } catch (error) {
        console.error('Error:', error.response.data);
    }
}
```

## Best Practices

### 1. Phone Number Formatting
- Always use international format: `+[country_code][number]`
- Example: `+254700000000` for Kenya

### 2. Message Content
- Keep messages concise and clear
- Use proper encoding for special characters
- Consider character limits (4096 for text messages)

### 3. Rate Limiting
- Implement exponential backoff for retries
- Monitor rate limit headers
- Use bulk endpoints for multiple messages

### 4. Error Handling
- Always check response status codes
- Implement proper retry logic
- Log errors for debugging

### 5. Security
- Store API tokens securely
- Use HTTPS in production
- Validate webhook signatures
- Rotate tokens regularly

### 6. Performance
- Use bulk operations when possible
- Implement caching for frequently accessed data
- Monitor response times
- Use appropriate priority levels

## Support

For technical support and questions:
- Email: support@your-domain.com
- Documentation: https://docs.your-domain.com
- Status Page: https://status.your-domain.com

---

**API Version:** 1.0  
**Last Updated:** December 5, 2025  
**Documentation Generated:** Phase 4 Production Deployment