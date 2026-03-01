<?php

/**
 * Meta WhatsApp Service - Usage Examples
 * 
 * This file demonstrates how to use the MetaWhatsAppService in your application.
 * You can run these examples in Laravel Tinker or create a test route.
 * 
 * Usage in Tinker:
 * php artisan tinker
 * >>> include 'resources/documentation/officialwhatsapp/examples.php';
 * >>> testOtpSending();
 */

use App\Services\MetaWhatsAppService;
use App\Services\SystemWhatsAppService;
use Illuminate\Support\Facades\Log;

/**
 * Example 1: Send OTP via System Service (Recommended)
 * This uses SystemWhatsAppService which automatically tries Meta first, then WaSender
 */
function testOtpSending()
{
    $systemService = app(SystemWhatsAppService::class);
    
    $phoneNumber = '+255714825469';  // Replace with test number
    $otpCode = '123456';
    $userName = 'John Doe';
    
    try {
        $result = $systemService->sendOtpVerification($phoneNumber, $otpCode, $userName);
        
        if ($result) {
            echo "✅ OTP sent successfully to {$phoneNumber}\n";
        } else {
            echo "❌ Failed to send OTP\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

/**
 * Example 2: Send OTP directly via Meta (Advanced)
 * This bypasses WaSender fallback - only use if you need strict Meta-only delivery
 */
function testMetaOtpDirect()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $otpCode = '789012';
    
    $response = $metaService->sendOtpTemplate($phoneNumber, $otpCode);
    
    if ($response['success'] ?? false) {
        echo "✅ OTP sent via: " . ($response['via'] ?? 'meta') . "\n";
        
        if (isset($response['data']['messages'][0]['id'])) {
            echo "📧 Message ID: {$response['data']['messages'][0]['id']}\n";
        }
        
        if ($response['via'] === 'wasender') {
            echo "⚠️ Note: Fallback to WaSender was used\n";
            echo "Meta Error: {$response['meta_error']}\n";
        }
    } else {
        echo "❌ Failed to send OTP\n";
        echo "Error: {$response['error']}\n";
        
        if (isset($response['error_code'])) {
            echo "Error Code: {$response['error_code']}\n";
        }
    }
}

/**
 * Example 3: Send Text Message with URL Preview
 */
function testTextMessage()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $message = "Hello! Check out our website: https://example.com";
    
    $response = $metaService->sendTextMessage($phoneNumber, $message, true);  // true = enable URL preview
    
    if ($response['success']) {
        echo "✅ Text message sent\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 4: Send Image with Caption
 */
function testImageMessage()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $imageUrl = 'https://example.com/product-image.jpg';  // Must be publicly accessible
    $caption = 'Check out our new product! 🎉';
    
    $response = $metaService->sendImage($phoneNumber, $imageUrl, $caption);
    
    if ($response['success']) {
        echo "✅ Image sent\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 5: Send PDF Document (Invoice)
 */
function testDocumentMessage()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $documentUrl = 'https://example.com/invoices/invoice-2024-001.pdf';
    $filename = 'Invoice-January-2024.pdf';
    $caption = 'Your invoice for January 2024. Thank you for your business!';
    
    $response = $metaService->sendDocument($phoneNumber, $documentUrl, $filename, $caption);
    
    if ($response['success']) {
        echo "✅ Document sent\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 6: Send Location
 */
function testLocationMessage()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $latitude = -6.7924;   // Dar es Salaam
    $longitude = 39.2083;
    $name = 'SafariChat Office';
    $address = '123 Uhuru Street, Dar es Salaam, Tanzania';
    
    $response = $metaService->sendLocation($phoneNumber, $latitude, $longitude, $name, $address);
    
    if ($response['success']) {
        echo "✅ Location sent\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 7: Send Custom Template Message
 */
function testTemplateMessage()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $templateName = 'payment_reminder';  // Must be pre-approved in Meta Business Manager
    $languageCode = 'en';
    
    $components = [
        [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => 'John Doe'],
                ['type' => 'text', 'text' => 'TZS 50,000'],
                ['type' => 'text', 'text' => 'March 31, 2024']
            ]
        ]
    ];
    
    $response = $metaService->sendTemplate($phoneNumber, $templateName, $languageCode, $components);
    
    if ($response['success']) {
        echo "✅ Template message sent\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 8: Mark Message as Read
 */
function testMarkAsRead()
{
    $metaService = app(MetaWhatsAppService::class);
    
    // You would get this message ID from webhook when receiving a message
    $messageId = 'wamid.HBgLMTY1MDUwNzY1OTAVAgARGBI5QTNDQTVCM0Q0Q0Q2RTY3RTcA';
    
    $response = $metaService->markAsRead($messageId);
    
    if ($response['success']) {
        echo "✅ Message marked as read\n";
    } else {
        echo "❌ Error: {$response['error']}\n";
    }
}

/**
 * Example 9: Check Service Health
 */
function checkMetaWhatsAppHealth()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $health = $metaService->getHealthStatus();
    
    echo "Meta WhatsApp Service Health Check:\n";
    echo "====================================\n";
    echo "Configured: " . ($health['configured'] ? '✅ Yes' : '❌ No') . "\n";
    echo "Access Token: " . ($health['access_token'] ? '✅ Set' : '❌ Missing') . "\n";
    echo "Phone Number ID: " . ($health['phone_number_id'] ? '✅ Set' : '❌ Missing') . "\n";
    echo "Fallback Enabled: " . ($health['fallback_enabled'] ? '✅ Yes' : '❌ No') . "\n";
    echo "API Version: {$health['api_version']}\n";
    
    if ($health['configured']) {
        echo "\n✅ Service is ready to use!\n";
    } else {
        echo "\n⚠️ Service needs configuration. Check your .env file.\n";
    }
}

/**
 * Example 10: Password Reset Flow
 */
function testPasswordReset()
{
    $systemService = app(SystemWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $otpCode = '987654';
    $userName = 'Jane Smith';
    
    try {
        $result = $systemService->sendPasswordResetMessage($phoneNumber, $otpCode, $userName);
        
        if ($result) {
            echo "✅ Password reset OTP sent successfully\n";
        } else {
            echo "❌ Failed to send password reset OTP\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

/**
 * Example 11: Error Handling with Detailed Logging
 */
function testWithErrorHandling()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $phoneNumber = '+255714825469';
    $message = 'Test message with comprehensive error handling';
    
    try {
        $response = $metaService->sendTextMessage($phoneNumber, $message);
        
        if ($response['success']) {
            echo "✅ Success!\n";
            echo "Via: " . ($response['via'] ?? 'meta') . "\n";
            
            // Check if fallback was used
            if (isset($response['fallback']) && $response['fallback']) {
                echo "⚠️ Warning: Fallback to WaSender was used\n";
                echo "Original Meta Error: {$response['meta_error']}\n";
                
                // Log for monitoring
                Log::warning('Meta WhatsApp fallback triggered', [
                    'phone' => $phoneNumber,
                    'meta_error' => $response['meta_error']
                ]);
            }
            
            // Get message ID if available
            if (isset($response['data']['messages'][0]['id'])) {
                $messageId = $response['data']['messages'][0]['id'];
                echo "Message ID: {$messageId}\n";
                
                // Store message ID for tracking delivery status
                // You could update your database here
            }
            
        } else {
            echo "❌ Failed to send message\n";
            echo "Error: {$response['error']}\n";
            
            if (isset($response['error_code'])) {
                echo "Error Code: {$response['error_code']}\n";
                
                // Handle specific error codes
                switch ($response['error_code']) {
                    case 131016:
                        echo "⚠️ Action: Access token has expired. Please refresh.\n";
                        break;
                    case 133016:
                        echo "⚠️ Action: Rate limit hit. Implement delay.\n";
                        break;
                    case 131026:
                        echo "⚠️ Action: Message undeliverable. Check phone number.\n";
                        break;
                }
            }
            
            // Log the failure
            Log::error('Meta WhatsApp message failed', [
                'phone' => $phoneNumber,
                'error' => $response['error'],
                'error_code' => $response['error_code'] ?? 'unknown'
            ]);
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: {$e->getMessage()}\n";
        Log::error('Meta WhatsApp exception', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * Example 12: Phone Number Formatting Test
 */
function testPhoneNumberFormatting()
{
    $metaService = app(MetaWhatsAppService::class);
    
    // Test various phone number formats
    $testNumbers = [
        '0714825469',           // National format
        '714825469',            // Without leading zero
        '+255714825469',        // International format
        '255714825469',         // International without +
        '+255 714 825 469',     // With spaces
        '+255-714-825-469',     // With dashes
    ];
    
    echo "Phone Number Formatting Test:\n";
    echo "=============================\n";
    
    foreach ($testNumbers as $number) {
        // The service will format it correctly
        // We're just showing what happens, not actually sending
        echo "Input: {$number}\n";
        
        // Use reflection to access protected method (for testing only)
        $reflection = new ReflectionClass($metaService);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);
        $formatted = $method->invoke($metaService, $number);
        
        echo "Output: {$formatted}\n\n";
    }
}

/**
 * Example 13: Batch Sending (Queue)
 */
function testBatchSending()
{
    $metaService = app(MetaWhatsAppService::class);
    
    $recipients = [
        ['phone' => '+255714825469', 'name' => 'John Doe'],
        ['phone' => '+255723456789', 'name' => 'Jane Smith'],
        ['phone' => '+255734567890', 'name' => 'Bob Johnson'],
    ];
    
    $message = "Hello {name}, this is a test message from SafariChat!";
    
    $results = [];
    
    foreach ($recipients as $recipient) {
        $personalizedMessage = str_replace('{name}', $recipient['name'], $message);
        
        $response = $metaService->sendTextMessage($recipient['phone'], $personalizedMessage);
        
        $results[] = [
            'phone' => $recipient['phone'],
            'name' => $recipient['name'],
            'success' => $response['success'] ?? false,
            'via' => $response['via'] ?? 'unknown',
            'error' => $response['error'] ?? null
        ];
        
        // Add delay to respect rate limits (if not queued)
        usleep(100000); // 100ms delay
    }
    
    // Summary
    $successful = count(array_filter($results, fn($r) => $r['success']));
    $failed = count($results) - $successful;
    
    echo "Batch Send Summary:\n";
    echo "==================\n";
    echo "Total: " . count($results) . "\n";
    echo "Successful: {$successful}\n";
    echo "Failed: {$failed}\n\n";
    
    // Detailed results
    foreach ($results as $result) {
        $status = $result['success'] ? '✅' : '❌';
        echo "{$status} {$result['name']} ({$result['phone']}) - Via: {$result['via']}\n";
        
        if (!$result['success']) {
            echo "   Error: {$result['error']}\n";
        }
    }
}

// Quick test runner - Run all basic tests
function runAllTests()
{
    echo "===========================================\n";
    echo "Meta WhatsApp Service - Comprehensive Tests\n";
    echo "===========================================\n\n";
    
    // 1. Health check first
    echo "1. Health Check\n";
    echo "---------------\n";
    checkMetaWhatsAppHealth();
    echo "\n";
    
    // If not configured, stop here
    $metaService = app(MetaWhatsAppService::class);
    if (!$metaService->isConfigured()) {
        echo "⚠️ Service not configured. Please set up your .env file first.\n";
        return;
    }
    
    // Run other tests (comment out to avoid spamming)
    /*
    echo "2. OTP Test\n";
    echo "-----------\n";
    testOtpSending();
    echo "\n";
    
    echo "3. Text Message Test\n";
    echo "--------------------\n";
    testTextMessage();
    echo "\n";
    
    echo "4. Phone Number Formatting Test\n";
    echo "--------------------------------\n";
    testPhoneNumberFormatting();
    echo "\n";
    */
    
    echo "✅ Tests complete!\n";
}

// To run in Tinker:
// php artisan tinker
// >>> include 'resources/documentation/officialwhatsapp/examples.php';
// >>> runAllTests();
