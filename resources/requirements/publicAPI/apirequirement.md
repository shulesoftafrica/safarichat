# SafariChat Public API Requirements

**Document Version:** 1.0  
**Last Updated:** March 19, 2026  
**Author:** SafariChat Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [API Authentication & Security](#api-authentication--security)
3. [API 1: School Management System Integration](#api-1-school-management-system-integration)
4. [API 2: E-commerce & POS Integration](#api-2-e-commerce--pos-integration)
5. [Common Requirements](#common-requirements)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)
8. [Webhooks (Future Enhancement)](#webhooks-future-enhancement)

---

## Overview

### Purpose
This document defines the requirements for two public API endpoints that enable third-party systems to integrate with SafariChat, automating the onboarding process for businesses.

### Integration Goals
- **Automated Account Creation**: Create SafariChat accounts programmatically
- **Business Profile Setup**: Configure business type and information
- **Product Catalog Import**: Bulk import products from external systems
- **Minimal Manual Steps**: Reduce onboarding to just login + WhatsApp QR scan

### Target Integrations
1. **School Management Systems** (e.g., Shulesoft)
2. **E-commerce Platforms** (e.g., WooCommerce, Shopify, Magento)
3. **POS & Inventory Systems** (e.g., Square, Clover, custom POS)

---

## API Authentication & Security

### Authentication Method
**API Key Authentication** (Bearer Token)

```
Authorization: Bearer {api_key}
```

### API Key Management
- Each integration partner receives a unique API key
- API keys should be stored in `api_keys` table with:
  - `id` (primary key)
  - `partner_name` (e.g., "Shulesoft", "WooCommerce Plugin")
  - `api_key` (hashed, 64-character token)
  - `api_secret` (for future HMAC signing)
  - `allowed_endpoints` (JSON array of permitted endpoints)
  - `rate_limit_per_hour` (default: 100 requests/hour)
  - `is_active` (boolean)
  - `created_at`, `updated_at`, `last_used_at`

### Security Requirements
1. **HTTPS Only**: All API calls must use HTTPS
2. **IP Whitelisting** (optional): Configure allowed IP ranges per partner
3. **Request Signing** (future): HMAC-SHA256 signature for request verification
4. **Input Validation**: Strict validation on all input fields
5. **SQL Injection Prevention**: Use parameterized queries
6. **Rate Limiting**: Prevent abuse with request limits

---

## API 1: School Management System Integration

### Use Case
**Integration Partner:** Shulesoft School Management System

**Scenario:**  
A school using Shulesoft wants to offer WhatsApp-based communication (announcements, fee reminders, etc.) via SafariChat. The school administrator clicks "Enable SafariChat" in Shulesoft, which triggers this API call to:
1. Create a SafariChat account for the school
2. Set the business type to "School"
3. Import school information (name, contact, etc.)
4. Create products/services (e.g., tuition fees, uniform sales, event tickets)

After API call succeeds, the school admin logs into SafariChat and scans WhatsApp QR code to complete setup.

---

### Endpoint Specification

#### Endpoint
```
POST /api/v1/integrations/school/provision
```

#### Request Headers
```
Content-Type: application/json
Authorization: Bearer {api_key}
X-Request-ID: {unique_request_id} (optional, for idempotency)
```

#### Request Body Schema

```json
{
  "external_system": {
    "name": "shulesoft",
    "version": "2.1.3",
    "school_id": "SHULE_12345"
  },
  "account": {
    "email": "info@greenvalleyschool.ac.tz",
    "password": "auto-generated-if-not-provided",
    "full_name": "Green Valley Secondary School",
    "phone": "+255712345678",
    "country_code": "TZ",
    "language": "en",
    "timezone": "Africa/Dar_es_Salaam"
  },
  "business": {
    "business_type": "school",
    "business_name": "Green Valley Secondary School",
    "business_category": "education",
    "registration_number": "EDU-2020-4567",
    "address": {
      "street": "Plot 123, Bagamoyo Road",
      "city": "Dar es Salaam",
      "region": "Kinondoni",
      "postal_code": "14110",
      "country": "Tanzania"
    },
    "contact": {
      "phone": "+255712345678",
      "email": "info@greenvalleyschool.ac.tz",
      "website": "https://greenvalley.ac.tz"
    },
    "metadata": {
      "student_count": 450,
      "staff_count": 35,
      "established_year": 2015
    }
  },
  "products": [
    {
      "external_id": "PROD_TUITION_FORM1",
      "name": "Form 1 Tuition Fee",
      "description": "Annual tuition fee for Form 1 students",
      "category": "fees",
      "price": 800000,
      "currency": "TZS",
      "unit": "year",
      "is_active": true,
      "metadata": {
        "academic_year": "2026/2027",
        "grade_level": "Form 1"
      }
    },
    {
      "external_id": "PROD_UNIFORM_SET",
      "name": "School Uniform Set",
      "description": "Complete uniform set (2 shirts, 2 trousers/skirts)",
      "category": "uniforms",
      "price": 45000,
      "currency": "TZS",
      "stock_quantity": 150,
      "is_active": true
    },
    {
      "external_id": "PROD_LUNCH_MONTHLY",
      "name": "Monthly Lunch Plan",
      "description": "22 school days lunch subscription",
      "category": "meals",
      "price": 60000,
      "currency": "TZS",
      "unit": "month",
      "is_active": true
    }
  ],
  "settings": {
    "auto_generate_password": true,
    "send_welcome_email": true,
    "enable_ai_agent": false,
    "default_message_template": "school_announcements"
  }
}
```

#### Field Descriptions

**external_system** (required)
- `name`: Integration partner identifier (e.g., "shulesoft")
- `version`: Partner system version for compatibility tracking
- `school_id`: Unique school identifier in partner system

**account** (required)
- `email`: Login email (must be unique)
- `password`: User password (auto-generated if not provided)
- `full_name`: Account holder's full name
- `phone`: Contact phone number (E.164 format recommended)
- `country_code`: ISO 3166-1 alpha-2 country code
- `language`: ISO 639-1 language code (default: "en")
- `timezone`: IANA timezone identifier

**business** (required)
- `business_type`: Must be "school" for this endpoint
- `business_name`: School's official name
- `business_category`: "education"
- `registration_number`: Official school registration number
- `address`: Complete postal address
- `contact`: School contact information
- `metadata`: Additional school-specific information

**products** (optional array)
- `external_id`: Unique product ID from partner system (for sync)
- `name`: Product/service name (max 255 chars)
- `description`: Detailed description (max 1000 chars)
- `category`: Product category (fees, uniforms, meals, books, events, etc.)
- `price`: Price in smallest currency unit (e.g., cents)
- `currency`: ISO 4217 currency code
- `unit`: Billing unit (year, month, term, piece, etc.)
- `stock_quantity`: Available quantity (null for services)
- `is_active`: Whether product is currently offered
- `metadata`: Additional product attributes

**settings** (optional)
- `auto_generate_password`: Generate random secure password if not provided
- `send_welcome_email`: Send onboarding email to account email
- `enable_ai_agent`: Enable AI-powered chat assistant
- `default_message_template`: Default template for school communications

#### Response Schema

**Success Response (HTTP 201 Created)**

```json
{
  "success": true,
  "message": "School account provisioned successfully",
  "data": {
    "account": {
      "user_id": 12345,
      "email": "info@greenvalleyschool.ac.tz",
      "username": "greenvalleyschool",
      "login_url": "https://app.safarichat.co.tz/login",
      "temporary_password": "Gv2x9@mKpL4n",
      "password_reset_required": true
    },
    "business": {
      "business_id": 98765,
      "business_name": "Green Valley Secondary School",
      "business_type": "school",
      "account_status": "active"
    },
    "products": {
      "created_count": 3,
      "products": [
        {
          "product_id": 5001,
          "external_id": "PROD_TUITION_FORM1",
          "name": "Form 1 Tuition Fee",
          "status": "active"
        },
        {
          "product_id": 5002,
          "external_id": "PROD_UNIFORM_SET",
          "name": "School Uniform Set",
          "status": "active"
        },
        {
          "product_id": 5003,
          "external_id": "PROD_LUNCH_MONTHLY",
          "name": "Monthly Lunch Plan",
          "status": "active"
        }
      ]
    },
    "next_steps": {
      "step_1": "User logs in at https://app.safarichat.co.tz/login",
      "step_2": "Navigate to WhatsApp Connection settings",
      "step_3": "Scan WhatsApp QR code to link school WhatsApp number",
      "documentation": "https://docs.safarichat.co.tz/school-setup"
    },
    "sync": {
      "external_system": "shulesoft",
      "external_school_id": "SHULE_12345",
      "sync_token": "sync_abc123def456",
      "webhook_url": "https://api.safarichat.co.tz/webhooks/shulesoft/SHULE_12345"
    }
  },
  "request_id": "req_20260319_abc123",
  "timestamp": "2026-03-19T14:30:00Z"
}
```

#### Validation Rules

1. **Email Validation**
   - Must be valid email format
   - Must be unique (not already registered)
   - Domain should be resolvable (optional warning)

2. **Phone Validation**
   - Must be valid phone number format
   - E.164 format recommended (+255712345678)
   - Must match country_code if provided

3. **Business Type**
   - Must be "school" for this endpoint
   - Other values should return 400 Bad Request

4. **Products**
   - Maximum 100 products per request
   - If more products needed, use separate product import API
   - Price must be positive integer
   - Currency must be valid ISO 4217 code

5. **Idempotency**
   - If `X-Request-ID` header provided, check for duplicate requests
   - If same request_id exists within 24 hours, return original response
   - Prevents duplicate account creation on retry

#### Business Logic

1. **Account Creation**
   ```
   - Check if email already exists → return 409 Conflict
   - Generate username from business_name (lowercase, no spaces)
   - Hash password using bcrypt
   - Create user record in `users` table
   - Set user_type = 'school'
   - Set account_status = 'active'
   - Store external_system mapping for sync
   ```

2. **Business Profile Creation**
   ```
   - Create business record in `businesses` table
   - Link to user via user_id
   - Store address in `business_addresses` table
   - Set business_type = 'school'
   - Set billing_plan = 'trial' (14-day trial)
   ```

3. **Product Creation**
   ```
   - Iterate through products array
   - Create product records in `products` table
   - Link to business via business_id
   - Store external_id for future sync operations
   - Set initial product_status = 'active'
   - Store metadata in JSON field
   ```

4. **Notification**
   ```
   - If send_welcome_email = true:
     - Send welcome email with login credentials
     - Include setup guide link
     - Add "Next Steps" instructions
   ```

5. **Audit Trail**
   ```
   - Log API request in `api_request_logs` table
   - Record: request_id, partner, timestamp, response_status
   - Store request/response payload for debugging
   ```

---

## API 2: E-commerce & POS Integration

### Use Case
**Integration Partners:** WooCommerce, Shopify, Magento, Square POS, Custom POS systems

**Scenario:**  
An online store or retail business wants to sell products via WhatsApp using SafariChat. The business owner installs SafariChat plugin/integration in their e-commerce platform or POS system, which triggers this API call to:
1. Create a SafariChat account for the business
2. Set the business type to "e-commerce" or "retail"
3. Import business information
4. Bulk import product catalog from the platform

After API succeeds, the business owner logs into SafariChat and scans WhatsApp QR code to start selling.

---

### Endpoint Specification

#### Endpoint
```
POST /api/v1/integrations/ecommerce/provision
```

#### Request Headers
```
Content-Type: application/json
Authorization: Bearer {api_key}
X-Request-ID: {unique_request_id} (optional, for idempotency)
```

#### Request Body Schema

```json
{
  "external_system": {
    "name": "woocommerce",
    "version": "8.5.2",
    "platform": "wordpress",
    "store_id": "WOO_STORE_789",
    "store_url": "https://myshop.co.tz"
  },
  "account": {
    "email": "owner@myshop.co.tz",
    "password": "auto-generated-if-not-provided",
    "full_name": "Jane Doe",
    "phone": "+255754321098",
    "country_code": "TZ",
    "language": "en",
    "timezone": "Africa/Dar_es_Salaam"
  },
  "business": {
    "business_type": "ecommerce",
    "business_name": "MyShop Electronics",
    "business_category": "electronics",
    "registration_number": "BIZ-2023-8901",
    "tax_id": "101156789Z",
    "address": {
      "street": "Shop 45, Mlimani City Mall",
      "city": "Dar es Salaam",
      "region": "Kinondoni",
      "postal_code": "14115",
      "country": "Tanzania"
    },
    "contact": {
      "phone": "+255754321098",
      "email": "support@myshop.co.tz",
      "website": "https://myshop.co.tz"
    },
    "metadata": {
      "industry": "electronics_retail",
      "business_model": "b2c",
      "average_monthly_orders": 150,
      "has_physical_store": true
    }
  },
  "products": [
    {
      "external_id": "PROD_WOO_101",
      "sku": "SMSG-A54-BLK",
      "name": "Samsung Galaxy A54 5G (Black)",
      "description": "6.4-inch Super AMOLED display, 128GB storage, 8GB RAM, 50MP camera",
      "category": "Smartphones",
      "subcategory": "Android",
      "brand": "Samsung",
      "price": 850000,
      "compare_at_price": 950000,
      "currency": "TZS",
      "cost_price": 720000,
      "stock_quantity": 25,
      "low_stock_threshold": 5,
      "weight": 202,
      "weight_unit": "g",
      "dimensions": {
        "length": 158.2,
        "width": 76.7,
        "height": 8.2,
        "unit": "mm"
      },
      "images": [
        {
          "url": "https://myshop.co.tz/wp-content/uploads/samsung-a54-1.jpg",
          "is_primary": true,
          "alt_text": "Samsung Galaxy A54 front view"
        },
        {
          "url": "https://myshop.co.tz/wp-content/uploads/samsung-a54-2.jpg",
          "is_primary": false,
          "alt_text": "Samsung Galaxy A54 back view"
        }
      ],
      "variants": [
        {
          "external_variant_id": "VAR_101_1",
          "sku": "SMSG-A54-BLK-128",
          "name": "Black, 128GB",
          "attributes": {
            "color": "Black",
            "storage": "128GB"
          },
          "price": 850000,
          "stock_quantity": 15
        },
        {
          "external_variant_id": "VAR_101_2",
          "sku": "SMSG-A54-BLU-128",
          "name": "Blue, 128GB",
          "attributes": {
            "color": "Blue",
            "storage": "128GB"
          },
          "price": 850000,
          "stock_quantity": 10
        }
      ],
      "is_active": true,
      "is_featured": true,
      "tags": ["5G", "smartphone", "android", "bestseller"],
      "metadata": {
        "warranty_period": "12 months",
        "supplier": "Official Samsung Distributor",
        "last_restocked": "2026-03-15"
      }
    },
    {
      "external_id": "PROD_WOO_102",
      "sku": "AIRD-PRO2-WHT",
      "name": "Apple AirPods Pro 2nd Gen",
      "description": "Active Noise Cancellation, Adaptive Transparency, USB-C charging",
      "category": "Audio",
      "subcategory": "Earbuds",
      "brand": "Apple",
      "price": 420000,
      "currency": "TZS",
      "cost_price": 350000,
      "stock_quantity": 40,
      "weight": 50,
      "weight_unit": "g",
      "images": [
        {
          "url": "https://myshop.co.tz/wp-content/uploads/airpods-pro-2.jpg",
          "is_primary": true,
          "alt_text": "AirPods Pro 2nd Generation"
        }
      ],
      "is_active": true,
      "is_featured": false,
      "tags": ["wireless", "earbuds", "apple", "anc"]
    }
  ],
  "settings": {
    "auto_generate_password": true,
    "send_welcome_email": true,
    "enable_ai_agent": true,
    "enable_inventory_sync": true,
    "sync_frequency": "realtime",
    "order_notification_webhook": "https://myshop.co.tz/wp-json/safarichat/v1/orders",
    "currency_settings": {
      "default_currency": "TZS",
      "accept_multiple_currencies": false
    }
  }
}
```

#### Field Descriptions

**external_system** (required)
- `name`: Platform identifier (woocommerce, shopify, magento, square, custom_pos, etc.)
- `version`: Platform version
- `platform`: Underlying platform (wordpress, standalone, etc.)
- `store_id`: Unique store identifier in external system
- `store_url`: Store's primary URL

**account** (required)
- Same as School API (refer to API 1)

**business** (required)
- `business_type`: "ecommerce", "retail", "wholesale", or "hybrid"
- `business_name`: Business/store name
- `business_category`: Industry category (electronics, fashion, groceries, etc.)
- `tax_id`: Tax identification number (TIN)
- Other fields: Same as School API

**products** (optional array, recommended)
- `external_id`: Unique product ID from e-commerce platform
- `sku`: Stock Keeping Unit code
- `name`: Product name (max 500 chars)
- `description`: Rich description (max 5000 chars, HTML allowed)
- `category`: Primary category
- `subcategory`: Secondary category (optional)
- `brand`: Brand/manufacturer name
- `price`: Current selling price (smallest currency unit)
- `compare_at_price`: Original price (for showing discounts)
- `cost_price`: Cost per unit (for profit calculations)
- `currency`: ISO 4217 currency code
- `stock_quantity`: Available units
- `low_stock_threshold`: Alert threshold for low stock
- `weight`: Product weight
- `weight_unit`: g, kg, lb, oz
- `dimensions`: Physical dimensions (for shipping)
- `images`: Array of product images (max 10 per product)
- `variants`: Product variations (color, size, etc.)
- `is_active`: Whether product is available for sale
- `is_featured`: Show in featured section
- `tags`: Searchable tags/keywords
- `metadata`: Additional attributes

**products.images** (optional array)
- `url`: Direct URL to image file
- `is_primary`: Primary product image (true for main image)
- `alt_text`: Accessibility text

**products.variants** (optional array)
- `external_variant_id`: Variant ID from external system
- `sku`: Unique SKU for variant
- `name`: Variant display name
- `attributes`: Key-value pairs (color: "Blue", size: "Large")
- `price`: Variant-specific price (if different from base)
- `stock_quantity`: Variant-specific inventory

**settings** (optional)
- `enable_ai_agent`: Enable AI sales assistant
- `enable_inventory_sync`: Enable real-time inventory sync
- `sync_frequency`: "realtime", "hourly", "daily"
- `order_notification_webhook`: URL to receive order notifications
- `currency_settings`: Currency configuration

#### Response Schema

**Success Response (HTTP 201 Created)**

```json
{
  "success": true,
  "message": "E-commerce account provisioned successfully",
  "data": {
    "account": {
      "user_id": 67890,
      "email": "owner@myshop.co.tz",
      "username": "myshopelectronics",
      "login_url": "https://app.safarichat.co.tz/login",
      "temporary_password": "Xy7@pQm9Rn2k",
      "password_reset_required": true
    },
    "business": {
      "business_id": 54321,
      "business_name": "MyShop Electronics",
      "business_type": "ecommerce",
      "account_status": "active"
    },
    "products": {
      "created_count": 2,
      "skipped_count": 0,
      "products": [
        {
          "product_id": 8001,
          "external_id": "PROD_WOO_101",
          "sku": "SMSG-A54-BLK",
          "name": "Samsung Galaxy A54 5G (Black)",
          "variants_count": 2,
          "status": "active"
        },
        {
          "product_id": 8002,
          "external_id": "PROD_WOO_102",
          "sku": "AIRD-PRO2-WHT",
          "name": "Apple AirPods Pro 2nd Gen",
          "variants_count": 0,
          "status": "active"
        }
      ]
    },
    "next_steps": {
      "step_1": "User logs in at https://app.safarichat.co.tz/login",
      "step_2": "Navigate to WhatsApp Connection settings",
      "step_3": "Scan WhatsApp QR code to link business WhatsApp number",
      "step_4": "Configure payment methods (M-Pesa, bank transfer, etc.)",
      "documentation": "https://docs.safarichat.co.tz/ecommerce-setup"
    },
    "sync": {
      "external_system": "woocommerce",
      "external_store_id": "WOO_STORE_789",
      "sync_token": "sync_xyz789uvw012",
      "inventory_sync_enabled": true,
      "webhook_url": "https://api.safarichat.co.tz/webhooks/woocommerce/WOO_STORE_789",
      "webhook_secret": "whsec_abc123def456"
    },
    "ai_agent": {
      "enabled": true,
      "agent_id": "agent_myshop_001",
      "capabilities": [
        "product_recommendations",
        "order_taking",
        "inventory_check",
        "customer_support"
      ],
      "training_status": "pending",
      "training_eta_minutes": 5
    }
  },
  "request_id": "req_20260319_xyz789",
  "timestamp": "2026-03-19T15:45:00Z"
}
```

#### Validation Rules

1. **Product Limits**
   - Maximum 500 products per request
   - For larger catalogs (500+), use paginated approach:
     - Initial request: Create account + first 500 products
     - Subsequent requests: Use product bulk import API
   - Maximum 10 images per product
   - Maximum 50 variants per product

2. **SKU Validation**
   - Must be unique within the business
   - Alphanumeric, hyphens, underscores allowed
   - Max 100 characters

3. **Price Validation**
   - Must be positive integer
   - compare_at_price must be >= price (if provided)
   - cost_price should be < price (warning if not)

4. **Image Validation**
   - URLs must be valid and accessible
   - Supported formats: JPG, PNG, WebP
   - File size limit: 5MB per image (recommendation)
   - SafariChat will download and cache images

5. **Stock Validation**
   - stock_quantity must be non-negative integer
   - If stock_quantity = 0, product marked as "Out of Stock"

6. **Business Type**
   - Allowed values: "ecommerce", "retail", "wholesale", "hybrid"
   - Other values return 400 Bad Request

#### Business Logic

1. **Account Creation**
   - Same as School API (refer to API 1)
   - Set user_type = 'business' or 'ecommerce'

2. **Business Profile Creation**
   - Same as School API
   - Set billing_plan = 'trial' (14-day trial)
   - Store tax_id for invoicing

3. **Product Import**
   ```
   FOR EACH product in products array:
     - Check if external_id already exists (for idempotency)
     - If exists AND same business: Skip or update
     - Create product record in `products` table
     - Store external_id for sync
     - Download and cache product images
     - Create image records in `product_images` table
     - If variants exist:
       - Create variant records in `product_variants` table
       - Each variant gets unique SKU
       - Track inventory per variant
     - Create tag records in `product_tags` table
     - Store metadata in JSON field
   ```

4. **AI Agent Setup** (if enabled)
   ```
   - Create AI agent instance
   - Train on product catalog
   - Configure default responses
   - Set business hours
   - Enable auto-responses
   ```

5. **Webhook Configuration**
   ```
   - Register webhook URL for order notifications
   - Generate webhook secret for verification
   - Store in `webhook_subscriptions` table
   - Events: order.created, order.updated, inventory.low
   ```

6. **Inventory Sync Setup**
   ```
   - If enable_inventory_sync = true:
     - Create sync job in `inventory_sync_jobs` table
     - Schedule based on sync_frequency
     - Store sync_token for authentication
   ```

---

## Common Requirements

### Both APIs Must Implement

1. **Idempotency**
   - Use `X-Request-ID` header to prevent duplicate processing
   - Store processed request IDs with TTL of 24 hours
   - Return same response if duplicate request detected

2. **Data Validation**
   - Validate all required fields
   - Sanitize inputs to prevent XSS/SQL injection
   - Return detailed validation errors (422 Unprocessable Entity)

3. **Atomic Operations**
   - Use database transactions
   - If any step fails, rollback entire operation
   - Return partial success information if applicable

4. **Audit Logging**
   - Log all API requests to `api_request_logs` table:
     - `request_id`, `api_key_id`, `endpoint`, `http_method`
     - `request_payload` (JSON), `response_payload` (JSON)
     - `response_status`, `execution_time_ms`
     - `ip_address`, `user_agent`
     - `created_at`

5. **Response Time**
   - Target: < 5 seconds for accounts with < 100 products
   - For large product imports (100+), consider async processing:
     - Return 202 Accepted immediately
     - Process in background job
     - Provide status check endpoint

6. **Content Type**
   - Accept: `application/json`
   - Return: `application/json; charset=utf-8`
   - UTF-8 encoding for international characters

7. **CORS Headers** (if web-based integrations)
   ```
   Access-Control-Allow-Origin: [configured-domains]
   Access-Control-Allow-Methods: POST, OPTIONS
   Access-Control-Allow-Headers: Authorization, Content-Type, X-Request-ID
   ```

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "One or more validation errors occurred",
    "details": [
      {
        "field": "account.email",
        "error": "Email is already registered",
        "value": "existing@example.com"
      },
      {
        "field": "products[0].price",
        "error": "Price must be a positive integer",
        "value": -1000
      }
    ]
  },
  "request_id": "req_20260319_error123",
  "timestamp": "2026-03-19T16:00:00Z"
}
```

### HTTP Status Codes

| Status Code | Error Code | Description |
|-------------|------------|-------------|
| 200 | - | Success (informational responses) |
| 201 | - | Created (account provisioned successfully) |
| 202 | - | Accepted (async processing initiated) |
| 400 | BAD_REQUEST | Invalid request format or parameters |
| 401 | UNAUTHORIZED | Missing or invalid API key |
| 403 | FORBIDDEN | API key lacks permission for this endpoint |
| 404 | NOT_FOUND | Endpoint not found |
| 409 | CONFLICT | Resource already exists (e.g., duplicate email) |
| 422 | VALIDATION_ERROR | Validation failed on one or more fields |
| 429 | RATE_LIMIT_EXCEEDED | Too many requests, rate limit hit |
| 500 | INTERNAL_ERROR | Server error (log and alert engineering) |
| 502 | BAD_GATEWAY | Upstream service error |
| 503 | SERVICE_UNAVAILABLE | Maintenance mode or overload |

### Common Error Codes

1. **INVALID_API_KEY**
   ```json
   {
     "success": false,
     "error": {
       "code": "INVALID_API_KEY",
       "message": "The provided API key is invalid or has been revoked"
     }
   }
   ```

2. **DUPLICATE_EMAIL**
   ```json
   {
     "success": false,
     "error": {
       "code": "DUPLICATE_EMAIL",
       "message": "An account with this email already exists",
       "field": "account.email",
       "value": "existing@example.com"
     }
   }
   ```

3. **INVALID_BUSINESS_TYPE**
   ```json
   {
     "success": false,
     "error": {
       "code": "INVALID_BUSINESS_TYPE",
       "message": "Business type 'restaurant' is not valid for this endpoint. Use 'ecommerce' or 'retail'",
       "allowed_values": ["ecommerce", "retail", "wholesale", "hybrid"]
     }
   }
   ```

4. **PRODUCT_LIMIT_EXCEEDED**
   ```json
   {
     "success": false,
     "error": {
       "code": "PRODUCT_LIMIT_EXCEEDED",
       "message": "Maximum 500 products per request. Received 750 products.",
       "limit": 500,
       "received": 750
     }
   }
   ```

5. **EXTERNAL_ID_CONFLICT**
   ```json
   {
     "success": false,
     "error": {
       "code": "EXTERNAL_ID_CONFLICT",
       "message": "Product with external_id 'PROD_123' already exists for this business",
       "field": "products[5].external_id",
       "existing_product_id": 8005
     }
   }
   ```

---

## Rate Limiting

### Rate Limit Policy

**Default Limits:**
- 100 requests per hour per API key
- 10 requests per minute per API key
- 3 provisioning requests per hour per API key (account creation endpoints)

**Response Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1710862800 (Unix timestamp)
```

**Rate Limit Exceeded Response (429):**
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Maximum 100 requests per hour allowed.",
    "limit": 100,
    "window": "1 hour",
    "retry_after": 3600
  },
  "request_id": "req_20260319_ratelimit",
  "timestamp": "2026-03-19T16:30:00Z"
}
```

### Custom Rate Limits
- Partners can request higher limits based on usage patterns
- Enterprise partners: Custom rate limits negotiated in SLA

---

## Webhooks (Future Enhancement)

### Outbound Webhooks
SafariChat will send webhooks to partner systems for:

1. **Order Created**
   ```json
   POST {order_notification_webhook}
   {
     "event": "order.created",
     "timestamp": "2026-03-19T17:00:00Z",
     "order_id": "ORD_SC_12345",
     "external_store_id": "WOO_STORE_789",
     "customer": {
       "phone": "+255712345678",
       "name": "John Doe"
     },
     "items": [
       {
         "external_id": "PROD_WOO_101",
         "sku": "SMSG-A54-BLK-128",
         "quantity": 1,
         "price": 850000
       }
     ],
     "total": 850000,
     "currency": "TZS",
     "status": "pending"
   }
   ```

2. **Inventory Low**
   ```json
   {
     "event": "inventory.low",
     "timestamp": "2026-03-19T18:00:00Z",
     "product_id": 8001,
     "external_id": "PROD_WOO_101",
     "sku": "SMSG-A54-BLK-128",
     "current_quantity": 3,
     "low_stock_threshold": 5,
     "recommended_reorder_quantity": 20
   }
   ```

### Webhook Security
- HMAC-SHA256 signature in `X-SafariChat-Signature` header
- Verify signature using webhook_secret
- Retry failed webhooks: 3 attempts with exponential backoff

---

## Post-Integration Workflow

### After API Call Succeeds

**Step 1: User Receives Credentials**
- Welcome email sent to `account.email` (if enabled)
- Email contains:
  - Login URL
  - Temporary password (if auto-generated)
  - Setup guide link
  - Support contact

**Step 2: User Logs In**
- Navigate to `https://app.safarichat.co.tz/login`
- Enter email and temporary password
- Required to change password on first login

**Step 3: WhatsApp Connection**
- Navigate to Settings > WhatsApp Connection
- Click "Connect WhatsApp"
- Scan QR code with WhatsApp mobile app
- System verifies connection
- WhatsApp number linked to business account

**Step 4: Configuration (School)**
- Review imported products (fees, uniforms, etc.)
- Set up message templates for announcements
- Configure automated reminders (fee due dates)
- Add staff members (optional)

**Step 4: Configuration (E-commerce)**
- Review imported product catalog
- Configure payment methods (M-Pesa, bank transfer)
- Set delivery options and fees
- Configure AI agent responses
- Test order flow

**Step 5: Go Live**
- System ready to receive messages
- Customers can WhatsApp business number
- Orders/inquiries processed via SafariChat
- AI agent handles common queries

---

## Implementation Checklist

### Backend Development

- [ ] Create `api_keys` table for partner authentication
- [ ] Create API key generation and management endpoints (admin)
- [ ] Implement authentication middleware for API routes
- [ ] Create `api_request_logs` table for audit trail
- [ ] Create `external_system_mappings` table for sync
- [ ] Implement idempotency check via `X-Request-ID`
- [ ] Create controller: `Api\V1\SchoolProvisionController`
- [ ] Create controller: `Api\V1\EcommerceProvisionController`
- [ ] Implement request validation classes
- [ ] Implement account creation logic (transactional)
- [ ] Implement business profile creation logic
- [ ] Implement bulk product import logic
- [ ] Implement product variant support
- [ ] Implement product image download and caching
- [ ] Create background job: `ProcessProvisioningRequest`
- [ ] Implement rate limiting middleware
- [ ] Implement error handling and response formatting
- [ ] Create webhook subscriber table
- [ ] Implement webhook notification system
- [ ] Create sync token generation and validation
- [ ] Write unit tests for validation rules
- [ ] Write integration tests for both endpoints
- [ ] Document API in OpenAPI/Swagger format

### Database Schema

**api_keys**
```sql
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    api_secret VARCHAR(255),
    allowed_endpoints JSON,
    rate_limit_per_hour INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_partner (partner_name)
);
```

**external_system_mappings**
```sql
CREATE TABLE external_system_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED,
    external_system VARCHAR(50) NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    sync_token VARCHAR(255),
    sync_enabled BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_external_mapping (external_system, external_id),
    INDEX idx_business (business_id)
);
```

**api_request_logs**
```sql
CREATE TABLE api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(100) UNIQUE,
    api_key_id BIGINT UNSIGNED,
    endpoint VARCHAR(255),
    http_method VARCHAR(10),
    request_payload JSON,
    response_payload JSON,
    response_status INT,
    execution_time_ms INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_api_key (api_key_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL
);
```

**product_images** (if not exists)
```sql
CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(500),
    cached_path VARCHAR(500),
    is_primary BOOLEAN DEFAULT FALSE,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);
```

**product_variants** (if not exists)
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    external_variant_id VARCHAR(255),
    sku VARCHAR(100) UNIQUE,
    name VARCHAR(255),
    attributes JSON,
    price BIGINT,
    cost_price BIGINT,
    stock_quantity INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_sku (sku)
);
```

### Testing Requirements

1. **Unit Tests**
   - Validate request schema parsing
   - Test validation rules
   - Test error response formatting

2. **Integration Tests**
   - Test full account provisioning flow
   - Test with 0 products, 1 product, 100 products
   - Test duplicate email handling
   - Test idempotency with same request_id
   - Test rate limiting enforcement

3. **Load Tests**
   - 100 concurrent requests
   - Large product catalogs (500 products)
   - Image download performance

### Documentation Requirements

- [ ] Create API documentation (Postman/Swagger)
- [ ] Create partner integration guides
- [ ] Document authentication process
- [ ] Create code examples (PHP, Python, Node.js)
- [ ] Document webhook setup
- [ ] Create troubleshooting guide
- [ ] Document rate limits and quotas

---

## Security Considerations

1. **API Key Storage**
   - Hash API keys before storing (bcrypt or Argon2)
   - Never return full API key after creation
   - Provide key rotation mechanism

2. **Input Sanitization**
   - Strip HTML tags from text fields (except description)
   - Validate URLs before downloading images
   - Prevent path traversal in file names
   - Sanitize external_id to prevent injection

3. **Image Download Security**
   - Validate image URLs are accessible
   - Check image file type (magic bytes, not just extension)
   - Limit image size (5MB max)
   - Use separate storage bucket for external images
   - Scan for malware (optional)

4. **Webhook Security**
   - Generate strong webhook secrets (32+ bytes)
   - Require HMAC signature verification
   - Implement replay attack prevention
   - Use HTTPS for webhook URLs only

5. **Data Privacy**
   - Don't log sensitive data (passwords, payment info)
   - Implement GDPR compliance for EU customers
   - Allow data export and deletion via API

---

## Monitoring & Alerting

### Key Metrics to Track

1. **API Health**
   - Request success rate (target: > 99%)
   - Average response time (target: < 3s)
   - Error rate by status code

2. **Business Metrics**
   - Accounts provisioned per day
   - Products imported per day
   - Top integration partners by volume

3. **Performance Metrics**
   - Database query time
   - Image download time
   - Queue processing time (for async jobs)

### Alerts

1. **Critical Alerts**
   - Error rate > 5% for 5 minutes
   - API key authentication failure spike
   - Database connection failures

2. **Warning Alerts**
   - Response time > 10s for 5 minutes
   - Rate limit hit by same API key > 10 times
   - Failed image downloads > 20%

---

## Future Enhancements

1. **Bulk Update API**
   - Update existing products in bulk
   - Sync inventory changes
   - Update prices

2. **Order Management API**
   - Fetch orders placed via SafariChat
   - Update order status
   - Add tracking information

3. **Customer API**
   - Fetch customer list
   - Get customer conversation history
   - Customer segmentation

4. **Analytics API**
   - Get sales reports
   - Popular products
   - Customer insights

5. **GraphQL Support**
   - Flexible querying
   - Reduce over-fetching
   - Real-time subscriptions

---

## Appendix

### A. Example API Key Generation

```php
// Generate secure API key
$apiKey = 'scapi_' . bin2hex(random_bytes(32)); // 64 chars
$apiSecret = bin2hex(random_bytes(32));

// Store hashed version
$hashedKey = Hash::make($apiKey);

DB::table('api_keys')->insert([
    'partner_name' => 'Shulesoft',
    'api_key' => $hashedKey,
    'api_secret' => $apiSecret,
    'allowed_endpoints' => json_encode([
        '/api/v1/integrations/school/provision'
    ]),
    'rate_limit_per_hour' => 100,
    'is_active' => true
]);

// Return to partner ONCE (never shown again)
return [
    'api_key' => $apiKey,
    'api_secret' => $apiSecret
];
```

### B. Example cURL Requests

**School Provisioning:**
```bash
curl -X POST https://api.safarichat.co.tz/api/v1/integrations/school/provision \
  -H "Authorization: Bearer scapi_abc123..." \
  -H "Content-Type: application/json" \
  -H "X-Request-ID: req_unique_12345" \
  -d '{
    "external_system": {
      "name": "shulesoft",
      "school_id": "SHULE_12345"
    },
    "account": {
      "email": "info@school.ac.tz",
      "full_name": "Green Valley School",
      "phone": "+255712345678"
    },
    "business": {
      "business_type": "school",
      "business_name": "Green Valley Secondary School"
    }
  }'
```

**E-commerce Provisioning:**
```bash
curl -X POST https://api.safarichat.co.tz/api/v1/integrations/ecommerce/provision \
  -H "Authorization: Bearer scapi_xyz789..." \
  -H "Content-Type: application/json" \
  -d @store-data.json
```

### C. Integration Partner Examples

1. **Shulesoft Plugin** (School Management)
   - PHP-based WordPress-like system
   - Integration button in admin panel
   - Syncs: Student fees, uniforms, books

2. **WooCommerce Plugin** (E-commerce)
   - WordPress plugin
   - One-click integration
   - Real-time inventory sync

3. **Shopify App** (E-commerce)
   - Shopify App Store listing
   - OAuth authentication
   - Webhook-based sync

4. **Square Integration** (POS)
   - Square App Marketplace
   - OAuth 2.0 flow
   - Product catalog import

### D. Support Contacts

**API Support:**
- Email: api-support@safarichat.co.tz
- Slack: #api-partners (for approved partners)
- Documentation: https://docs.safarichat.co.tz/api

**Integration Partnerships:**
- Email: partnerships@safarichat.co.tz
- Request API key: https://safarichat.co.tz/partners/apply

---

**End of Document**
