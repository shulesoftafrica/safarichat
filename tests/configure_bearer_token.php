<?php
// Helper script to configure Bearer token for WhatsApp QR generation

echo "=== WhatsApp QR Configuration Helper ===\n\n";

$envFile = __DIR__ . '/.env';
$exampleFile = __DIR__ . '/.env.example';

// Check if .env exists
if (!file_exists($envFile)) {
    echo "❌ .env file not found!\n";
    echo "Creating .env from .env.example...\n";
    
    if (file_exists($exampleFile)) {
        copy($exampleFile, $envFile);
        echo "✅ .env file created\n\n";
    } else {
        echo "❌ .env.example not found. Please create .env manually\n";
        exit(1);
    }
}

// Read current .env content
$envContent = file_get_contents($envFile);

// Check current token configuration
$hasNotificationToken = strpos($envContent, 'NOTIFICATION_API_TOKEN') !== false;
$hasUnifiedToken = strpos($envContent, 'UNIFIED_NOTIFICATION_TOKEN') !== false;

echo "Current Configuration Status:\n";
echo "NOTIFICATION_API_TOKEN: " . ($hasNotificationToken ? "✅ Present" : "❌ Missing") . "\n";
echo "UNIFIED_NOTIFICATION_TOKEN: " . ($hasUnifiedToken ? "✅ Present" : "❌ Missing") . "\n\n";

// Get token from user
echo "Please enter your Bearer token from notifications.shulesoft.africa:\n";
echo "(Press Enter to skip if you don't have it yet)\n";
echo "Token: ";

$handle = fopen("php://stdin", "r");
$bearerToken = trim(fgets($handle));
fclose($handle);

if (empty($bearerToken)) {
    echo "\n⚠️  No token provided.\n";
    echo "The system will continue to use mock QR codes until you configure a valid token.\n\n";
    
    // Add placeholder entries to .env if they don't exist
    if (!$hasNotificationToken) {
        $envContent .= "\n# Unified Notification Service Configuration\n";
        $envContent .= "NOTIFICATION_BASE_URL=https://notifications.shulesoft.africa/api\n";
        $envContent .= "NOTIFICATION_API_TOKEN=your_bearer_token_here\n";
        $hasNotificationToken = true;
    }
    
    if (!$hasUnifiedToken) {
        $envContent .= "UNIFIED_NOTIFICATION_TOKEN=your_bearer_token_here\n";
    }
    
    file_put_contents($envFile, $envContent);
    echo "✅ Placeholder configuration added to .env file\n";
    
} else {
    // Validate token format (basic check)
    if (strlen($bearerToken) < 20) {
        echo "\n⚠️  Token seems too short. Are you sure this is correct?\n";
    }
    
    // Update or add token to .env
    if ($hasNotificationToken) {
        // Replace existing token
        $envContent = preg_replace('/NOTIFICATION_API_TOKEN=.*/', 'NOTIFICATION_API_TOKEN=' . $bearerToken, $envContent);
        echo "✅ Updated existing NOTIFICATION_API_TOKEN\n";
    } else {
        // Add new token
        $envContent .= "\n# Unified Notification Service Configuration\n";
        $envContent .= "NOTIFICATION_BASE_URL=https://notifications.shulesoft.africa/api\n";
        $envContent .= "NOTIFICATION_API_TOKEN=" . $bearerToken . "\n";
        echo "✅ Added NOTIFICATION_API_TOKEN to .env\n";
    }
    
    // Also add/update unified token
    if ($hasUnifiedToken) {
        $envContent = preg_replace('/UNIFIED_NOTIFICATION_TOKEN=.*/', 'UNIFIED_NOTIFICATION_TOKEN=' . $bearerToken, $envContent);
    } else {
        $envContent .= "UNIFIED_NOTIFICATION_TOKEN=" . $bearerToken . "\n";
    }
    
    // Save updated .env file
    file_put_contents($envFile, $envContent);
    echo "✅ Configuration saved to .env file\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Clear Laravel config cache: php artisan config:clear\n";
echo "2. Test the configuration: php test_bearer_token.php\n";
echo "3. Access WhatsApp setup: http://localhost/safarichat/wasender\n";
echo "4. Generate QR code and test with WhatsApp mobile app\n\n";

echo "=== Troubleshooting ===\n";
if (empty($bearerToken)) {
    echo "• Get Bearer token from notifications.shulesoft.africa support\n";
    echo "• Re-run this script: php configure_bearer_token.php\n";
}
echo "• Check Laravel logs: tail -f storage/logs/laravel.log\n";
echo "• Test API directly: php test_bearer_token.php\n";
echo "• Review troubleshooting guide: WHATSAPP_QR_TROUBLESHOOTING.md\n";

echo "\nConfiguration complete!\n";
?>