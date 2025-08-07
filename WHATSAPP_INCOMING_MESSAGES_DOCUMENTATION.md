# WhatsApp Incoming Message Processing System

## Overview

This system provides comprehensive incoming message processing for WhatsApp Business using the WAAPI (WhatsApp API) integration. It automatically processes incoming messages, creates guest contacts for unknown numbers, and provides auto-reply functionality.

## System Architecture

### Database Structure

#### 1. `incoming_messages` Table
- **Purpose**: Store all incoming WhatsApp messages
- **Key Fields**:
  - `user_id`: Links message to the business user
  - `instance_id`: References the WhatsApp instance
  - `message_id`: Unique WAAPI message identifier
  - `chat_id`: WhatsApp chat identifier (e.g., "255712345678@c.us")
  - `phone_number`: Extracted phone number (e.g., "255712345678")
  - `message_body`: The actual message content
  - `message_type`: Type of message (text, image, document, etc.)
  - `media_data`: JSON data for media messages
  - `status`: Processing status (received, processed, replied)
  - `auto_reply`: Boolean indicating if auto-reply was sent
  - `metadata`: Additional WAAPI message data

#### 2. `outgoing_messages` Table
- **Purpose**: Track sent WhatsApp messages and their status
- **Key Fields**:
  - `user_id`: Message sender
  - `instance_id`: WhatsApp instance used
  - `waapi_message_id`: WAAPI response message ID
  - `phone_number`: Recipient phone number
  - `message_body`: Message content
  - `message_type`: Type of message sent
  - `status`: Delivery status (sent, delivered, read, failed)
  - `retry_count`: Number of retry attempts

#### 3. `whatsapp_instances` Table (Enhanced)
- **Purpose**: Manage WhatsApp Business instances
- **New Fields Added**:
  - `access_token`: WAAPI access token
  - `connect_status`: Connection status (Connected, Disconnected)
  - `webhook_verified`: Boolean for webhook verification
  - `last_message_sync`: Timestamp of last message sync

### Models and Relationships

#### 1. IncomingMessage Model
```php
// Relationships
belongsTo(User::class)
belongsTo(Guest::class, 'phone_number', 'phone')
belongsTo(WhatsappInstance::class, 'instance_id', 'id')

// Helper Methods
markAsProcessed()
markAsReplied()
```

#### 2. OutgoingMessage Model
```php
// Relationships
belongsTo(User::class)
belongsTo(WhatsappInstance::class, 'instance_id', 'id')

// Helper Methods
markAsSent()
markAsFailed()
canRetry()
```

#### 3. WhatsappInstance Model
```php
// Relationships
belongsTo(User::class)
hasMany(IncomingMessage::class, 'instance_id', 'id')
hasMany(OutgoingMessage::class, 'instance_id', 'id')

// Helper Methods
getTotalMessagesCount()
getUnprocessedMessagesCount()
getLastMessageTime()
```

## API Endpoints

### 1. Webhook Processing
**Endpoint**: `POST /api/waapi/webhook/{instanceId}`
- **Purpose**: Receive real-time messages from WAAPI
- **Processing**:
  1. Validates webhook payload
  2. Extracts message data
  3. Creates IncomingMessage record
  4. Creates Guest if phone number is unknown
  5. Sends auto-reply if configured
  6. Updates message status

### 2. Manual Message Processing
**Endpoint**: `POST /api/waapi/process-messages/{instanceId}`
- **Purpose**: Manually sync messages from WAAPI
- **Use Case**: Batch processing or catching missed messages

### 3. Message Retrieval
**Endpoint**: `GET /api/waapi/incoming-messages/{instanceId}`
- **Purpose**: Get paginated list of incoming messages
- **Parameters**:
  - `limit`: Number of messages per page
  - `offset`: Pagination offset
  - `status`: Filter by processing status
  - `phone`: Filter by phone number
  - `date`: Filter by date

### 4. Status Updates
**Endpoint**: `POST /api/waapi/mark-processed/{messageId}`
- **Purpose**: Mark message as processed manually

## Message Processing Flow

### 1. Webhook Reception
```
WAAPI → Webhook Endpoint → validateWebhookData() → processIncomingMessages()
```

### 2. Message Processing Steps
1. **Extract Message Data**: Parse WAAPI payload
2. **Phone Number Formatting**: Standardize phone numbers
3. **Guest Management**: Create guest if unknown number
4. **Message Storage**: Save to incoming_messages table
5. **Auto-Reply**: Send automatic response if configured
6. **Status Updates**: Update processing status

### 3. Phone Number Processing
```php
// Examples of phone number formatting:
"+255712345678" → "255712345678"
"0712345678"    → "255712345678"
"712345678"     → "255712345678"
```

## Auto-Reply System

### Configuration
Auto-replies can be configured per WhatsApp instance with:
- Welcome messages for new contacts
- Business hours responses
- Keyword-based responses
- Default fallback messages

### Implementation
```php
private function processAutoReply($whatsappInstance, $phoneNumber, $messageBody)
{
    // Check if auto-reply is enabled
    // Determine appropriate response
    // Send reply via WAAPI
    // Log outgoing message
}
```

## Admin Interface

### Incoming Messages Dashboard
**Route**: `/whatsapp/incoming-messages`

**Features**:
- **Instance Selection**: Switch between WhatsApp instances
- **Message Filtering**: By status, phone number, date
- **Real-time Processing**: Manual message sync
- **Message Details**: View full message content and metadata
- **Reply Functionality**: Send replies directly from dashboard
- **Status Management**: Mark messages as processed
- **Pagination**: Handle large message volumes

### UI Components
1. **Instance Selector**: Dropdown with all user instances
2. **Filter Bar**: Status, phone, date filters
3. **Messages Table**: Tabular view with key information
4. **Action Buttons**: View, Reply, Mark as Processed
5. **Modals**: Message details and reply composition

## Security Considerations

### 1. Webhook Verification
- Validate webhook source
- Verify payload integrity
- Check instance ownership

### 2. Access Control
- User-based instance isolation
- Permission-based access to messages
- Secure token handling

### 3. Data Protection
- Message content encryption at rest
- Secure API token storage
- Audit logging for access

## Error Handling

### 1. Webhook Processing Errors
- Invalid payload structure
- Missing required fields
- Database connection issues
- WAAPI API failures

### 2. Recovery Mechanisms
- Retry logic for failed operations
- Dead letter queue for problematic messages
- Manual intervention tools

### 3. Logging
- Comprehensive error logging
- Performance monitoring
- Webhook delivery tracking

## Configuration Options

### 1. Auto-Reply Settings
```php
// In whatsapp_instances table or config
'auto_reply_enabled' => true,
'welcome_message' => 'Thank you for contacting us!',
'business_hours' => '09:00-17:00',
'offline_message' => 'We are currently offline...'
```

### 2. Processing Limits
```php
// Rate limiting and batch sizes
'max_messages_per_batch' => 50,
'webhook_timeout' => 30,
'retry_attempts' => 3
```

## Monitoring and Analytics

### 1. Message Statistics
- Total messages received
- Response rates
- Processing times
- Error rates

### 2. Contact Growth
- New contacts per day
- Message volume trends
- Popular message types

### 3. Performance Metrics
- Webhook response times
- Database query performance
- Auto-reply success rates

## Testing

### 1. Unit Tests
- Message processing logic
- Phone number formatting
- Auto-reply functionality

### 2. Integration Tests
- Webhook endpoint testing
- WAAPI integration
- Database operations

### 3. Test File Location
`tests/Feature/IncomingMessageProcessingTest.php`

## Usage Examples

### 1. Setting Up Webhook
```bash
# Configure webhook URL in WAAPI dashboard
https://yourdomain.com/api/waapi/webhook/{instanceId}
```

### 2. Manual Message Sync
```javascript
// From admin dashboard
$.post('/api/waapi/process-messages/your-instance-id')
  .done(function(response) {
    console.log(`Processed ${response.processed_count} messages`);
  });
```

### 3. Sending Auto-Reply
```php
// Automatic in processIncomingMessages()
$this->processAutoReply($whatsappInstance, $phoneNumber, $messageBody);
```

## Troubleshooting

### Common Issues
1. **Webhook not receiving messages**: Check WAAPI webhook configuration
2. **Duplicate messages**: Verify message ID uniqueness checks
3. **Auto-replies not sending**: Check instance access token and WAAPI connectivity
4. **Phone number formatting issues**: Review formatPhoneNumber() logic

### Debug Tools
- Message processing logs in `storage/logs/laravel.log`
- Database query logs
- WAAPI API response monitoring
- Webhook payload inspection

## Future Enhancements

### Planned Features
1. **Message Templates**: Pre-defined response templates
2. **Advanced Auto-Reply**: AI-powered responses
3. **Message Scheduling**: Delayed message sending
4. **Bulk Messaging**: Mass message campaigns
5. **Analytics Dashboard**: Comprehensive reporting
6. **Multi-language Support**: Automatic language detection
7. **CRM Integration**: Connect with external CRM systems

### Scalability Considerations
- Message queue implementation
- Database sharding for high volume
- Caching layer for frequent queries
- API rate limiting compliance

## Conclusion

This incoming message processing system provides a complete solution for WhatsApp Business communication management. It handles real-time message processing, automatic contact creation, and provides comprehensive administrative tools for managing customer communications efficiently.

The system is designed to be scalable, secure, and easy to maintain, with comprehensive error handling and monitoring capabilities built in.
