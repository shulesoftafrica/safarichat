# Contact Management API Documentation

## Overview
Simple API endpoints for managing contacts (event guests) in the SafariChat system. All endpoints work with the existing `events_guests` table.

## Authentication
All endpoints require API authentication using Laravel Sanctum. Include your API token in the Authorization header:

```
Authorization: Bearer your_api_token_here
```

## Base URL
```
https://yourdomain.com/api/contacts
```

---

## Endpoints

### 1. Add Single Contact

**POST** `/api/contacts`

Add a single contact to the system.

#### Request Body
```json
{
    "guest_name": "John Doe",
    "guest_phone": "+1234567890",
    "guest_email": "john@example.com",
    "event_id": 1,
    "guest_pledge": 100.00,
    "event_guest_category_id": 1,
    "contacted_for_sales": false
}
```

#### Required Fields
- `guest_name` (string, max 255 chars)
- `guest_phone` (string, max 20 chars)

#### Optional Fields
- `guest_email` (email format)
- `event_id` (integer)
- `guest_pledge` (decimal, minimum 0)
- `event_guest_category_id` (integer)
- `contacted_for_sales` (boolean, default: false)

#### Success Response (201)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "user_id": 1,
        "guest_name": "John Doe",
        "guest_phone": "+1234567890",
        "guest_email": "john@example.com",
        "event_id": 1,
        "guest_pledge": 100.00,
        "event_guest_category_id": 1,
        "contacted_for_sales": false,
        "contacted_at": null,
        "created_at": "2025-11-24T10:00:00.000000Z",
        "updated_at": "2025-11-24T10:00:00.000000Z"
    },
    "message": "Contact created successfully"
}
```

#### Error Responses
- **409 Conflict**: Phone number already exists for this user
- **422 Validation Error**: Invalid input data
- **500 Server Error**: Internal server error

---

### 2. Add Multiple Contacts (Bulk)

**POST** `/api/contacts/bulk`

Add multiple contacts at once (max 100 per request).

#### Request Body
```json
{
    "contacts": [
        {
            "guest_name": "John Doe",
            "guest_phone": "+1234567890",
            "guest_email": "john@example.com",
            "event_id": 1,
            "contacted_for_sales": false
        },
        {
            "guest_name": "Jane Smith", 
            "guest_phone": "+1234567891",
            "guest_email": "jane@example.com",
            "event_id": 1,
            "contacted_for_sales": true
        }
    ]
}
```

#### Success Response (201)
```json
{
    "success": true,
    "data": {
        "created": [
            {
                "id": 124,
                "user_id": 1,
                "guest_name": "John Doe",
                "guest_phone": "+1234567890",
                "guest_email": "john@example.com",
                "event_id": 1,
                "contacted_for_sales": false,
                "contacted_at": null,
                "created_at": "2025-11-24T10:00:00.000000Z",
                "updated_at": "2025-11-24T10:00:00.000000Z"
            }
        ],
        "created_count": 1,
        "error_count": 1,
        "errors": [
            "Contact at index 1: Phone number already exists"
        ]
    },
    "message": "Bulk contact creation completed"
}
```

---

### 3. Get All Contacts

**GET** `/api/contacts`

Retrieve contacts with optional filtering and pagination.

#### Query Parameters
- `contacted_for_sales` (boolean): Filter by contact status
- `event_id` (integer): Filter by specific event
- `search` (string): Search in name, phone, or email
- `per_page` (integer): Results per page (default: 15)
- `page` (integer): Page number (default: 1)

#### Example Request
```
GET /api/contacts?event_id=1&contacted_for_sales=false&per_page=10
```

#### Success Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "user_id": 1,
            "guest_name": "John Doe",
            "guest_phone": "+1234567890",
            "guest_email": "john@example.com",
            "event_id": 1,
            "guest_pledge": 100.00,
            "contacted_for_sales": false,
            "contacted_at": null,
            "created_at": "2025-11-24T10:00:00.000000Z",
            "updated_at": "2025-11-24T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50,
        "from": 1,
        "to": 10
    },
    "message": "Contacts retrieved successfully"
}
```

---

### 4. Update Contact Sales Status

**PUT** `/api/contacts/{contactId}/status`

Update whether a contact has been contacted for sales.

#### Request Body
```json
{
    "contacted_for_sales": true
}
```

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "user_id": 1,
        "guest_name": "John Doe",
        "guest_phone": "+1234567890",
        "guest_email": "john@example.com",
        "event_id": 1,
        "contacted_for_sales": true,
        "contacted_at": "2025-11-24T10:30:00.000000Z",
        "created_at": "2025-11-24T10:00:00.000000Z",
        "updated_at": "2025-11-24T10:30:00.000000Z"
    },
    "message": "Contact status updated successfully"
}
```

---

## Usage Examples

### PHP Example (using cURL)
```php
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://yourdomain.com/api/contacts',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer your_api_token_here',
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'guest_name' => 'John Doe',
        'guest_phone' => '+1234567890',
        'guest_email' => 'john@example.com',
        'event_id' => 1,
        'contacted_for_sales' => false
    ])
]);

$response = curl_exec($curl);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Contact created: " . $data['data']['id'];
} else {
    echo "Error: " . $data['message'];
}
```

### JavaScript Example (using fetch)
```javascript
const response = await fetch('https://yourdomain.com/api/contacts', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer your_api_token_here',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        guest_name: 'John Doe',
        guest_phone: '+1234567890',
        guest_email: 'john@example.com',
        event_id: 1,
        contacted_for_sales: false
    })
});

const data = await response.json();

if (data.success) {
    console.log('Contact created:', data.data.id);
} else {
    console.error('Error:', data.message);
}
```

### Python Example (using requests)
```python
import requests

url = 'https://yourdomain.com/api/contacts'
headers = {
    'Authorization': 'Bearer your_api_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
}
data = {
    'guest_name': 'John Doe',
    'guest_phone': '+1234567890',
    'guest_email': 'john@example.com',
    'event_id': 1,
    'contacted_for_sales': False
}

response = requests.post(url, json=data, headers=headers)
result = response.json()

if result['success']:
    print(f"Contact created: {result['data']['id']}")
else:
    print(f"Error: {result['message']}")
```

---

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

### Common HTTP Status Codes
- `200 OK`: Success
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `404 Not Found`: Resource not found
- `409 Conflict`: Resource already exists
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Server error

---

## Rate Limiting

API requests are rate limited to prevent abuse. The bulk endpoint has a maximum of 100 contacts per request.

## Security Notes

1. Always use HTTPS in production
2. Keep your API tokens secure
3. The API automatically scopes data to the authenticated user
4. Phone numbers are used as unique identifiers per user