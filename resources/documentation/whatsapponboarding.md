This logic on onboarding number to wasender is not valid

Modify this to follow the following steps as per their documentations

1. obtain and verify number from frontend if it match the format, or else return message to user to rectify if number is not in valid format
2. check first if session for this number is already created before or not, use this logic

require 'vendor/autoload.php'; // Assuming Guzzle is installed

use GuzzleHttp\Client;

$client = new Client();
$apiKey = 'YOUR_PERSONAL_ACCESS_TOKEN';
$url = 'https://www.wasenderapi.com/api/whatsapp-sessions/{whatsappSession}';

try {
    $response = $client->get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ]
    ]);

    echo $response->getBody();
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo "Request failed: " . $e->getMessage();
    if ($e->hasResponse()) {
        echo "\nResponse: " . $e->getResponse()->getBody();
    }
}

upon success, this will return
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Business WhatsApp",
        "phone_number": "+1234567890",
        "status": "connected",
        "account_protection": true,
        "log_messages": true,
        "webhook_url": "https://example.com/webhook",
        "webhook_enabled": true,
        "webhook_events": [
            "message",
            "group_update"
        ],
        "api_key": "75075a7bf6417bff59e76fb7205382c2dc74cf1769e76f382c2dc74cf176c0bf",
        "webhook_secret": "fb61be92ddb7935e0cedcec58e470f6c",
        "created_at": "2025-04-01T12:00:00Z",
        "updated_at": "2025-05-08T15:30:00Z"
    }
}

3. if session is not available from the above step, then create a new session as per codes below
require 'vendor/autoload.php'; // Assuming Guzzle is installed

use GuzzleHttp\Client;

$client = new Client();
$apiKey = 'YOUR_PERSONAL_ACCESS_TOKEN';
$url = 'https://www.wasenderapi.com/api/whatsapp-sessions';

try {
    $response = $client->post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'json' => [
            'name' => 'Sample Name',
            'phone_number' => 'Sample Phone_number',
            'account_protection' => true,
            'log_messages' => true,
            'read_incoming_messages' => false,
            'webhook_url' => 'Sample Webhook_url',
            'webhook_enabled' => true,
            'webhook_events' => [
                'messages.received',
                'session.status',
                'messages.update'
            ],
        ]
    ]);

    echo $response->getBody();
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo "Request failed: " . $e->getMessage();
    if ($e->hasResponse()) {
        echo "\nResponse: " . $e->getResponse()->getBody();
    }
}

upon success, this will return the following result
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Business WhatsApp",
    "phone_number": "+1234567890",
    "status": "connected",
    "account_protection": true,
    "log_messages": true,
    "read_incoming_messages": false,
    "webhook_url": "https://example.com/webhook",
    "webhook_enabled": true,
    "webhook_events": [
      "messages.received",
      "session.status",
      "messages.update"
    ],
    "api_key": "75075a7bf6417bff59e76fb7205382c2dc74cf1769e76f382c2dc74cf176c0bf",
    "webhook_secret": "fb61be92ddb7935e0cedcec58e470f6c",
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-05-08T15:30:00Z"
  }
}
4. once we get these details, now insert in a database whatsapp instance 
5. once this done, then generate the qr code that user will use to scan by sending this request

require 'vendor/autoload.php'; // Assuming Guzzle is installed

use GuzzleHttp\Client;

$client = new Client();
$apiKey = 'YOUR_PERSONAL_ACCESS_TOKEN';
$url = 'https://www.wasenderapi.com/api/whatsapp-sessions/{whatsappSession}/connect';

try {
    $response = $client->post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ]
    ]);

    echo $response->getBody();
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo "Request failed: " . $e->getMessage();
    if ($e->hasResponse()) {
        echo "\nResponse: " . $e->getResponse()->getBody();
    }
}

on this case, this {whatsappSession} is actual the ID obtained from number 2 or 3 after getting a session, that first ID in a data object
eg. {
  "success": true,
  "data": {
    "id": 1,  ==here this 1 is the id to use

This request will return the following response
{
  "success": true,
  "data": {
    "status": "NEED_SCAN",
    "qrCode": "2@DTMUHeYfa9/RMXr8A2IP3/...", // This is the QR string. Use a QR code library to generate an image.
  }
}

NOTE THIS status meaning :
NEED_SCAN, 
CONNECTED=Session already initialized or connecting. No QR code needed.
