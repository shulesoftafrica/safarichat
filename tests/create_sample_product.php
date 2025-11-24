<?php

// Include Laravel bootstrap
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

try {
    echo "🔧 Creating Sample Product for RAG Testing...\n\n";

    // Create a sample product with service features
    $product = Product::create([
        'name' => 'AI-Powered Safari Chat System',
        'description' => 'Complete WhatsApp business automation platform with AI integration, lead management, and customer service capabilities.',
        'price' => 299.99,
        'quantity' => 100,
        'product_type' => 'service',
        'service_delivery_type' => 'digital',
        'service_duration_days' => 365,
        'service_pricing_type' => 'subscription',
        'hourly_rate' => 49.99,
        'requires_consultation' => true,
        'ai_prompt' => 'You are a sales expert for Safari Chat, an AI-powered WhatsApp business automation platform. Highlight features like automated customer service, lead management, AI conversations, and business growth capabilities.',
        'ai_enabled' => true,
        'selling_points' => json_encode([
            'Automated WhatsApp customer service',
            'AI-powered lead qualification',
            'Multi-channel conversation management', 
            'Real-time analytics and reporting',
            '24/7 customer support automation',
            'CRM integration capabilities'
        ])
    ]);

    echo "✅ Created product: {$product->name} (ID: {$product->id})\n";
    echo "   Type: {$product->product_type}\n";
    echo "   Price: \${$product->price}\n\n";

    // Create sample documentation content
    $documentation = "
# Safari Chat AI System Documentation

## Product Overview
Safari Chat is a comprehensive WhatsApp business automation platform that leverages artificial intelligence to streamline customer communications and boost sales performance.

## Key Features

### AI-Powered Conversations
- Natural language processing for customer queries
- Intelligent response generation
- Context-aware conversation flow
- Multi-language support

### Lead Management
- Automated lead capture from WhatsApp
- Lead scoring and qualification
- Follow-up automation
- CRM integration

### Customer Service Automation  
- 24/7 automated support
- FAQ handling
- Escalation to human agents
- Customer satisfaction tracking

### Analytics & Reporting
- Real-time conversation metrics
- Sales performance tracking
- Customer engagement analytics
- ROI measurement tools

## Technical Specifications
- Cloud-based infrastructure
- 99.9% uptime guarantee
- Enterprise-grade security
- API integrations available
- Mobile-responsive dashboard

## Pricing Plans
- Starter: \$99/month (up to 1,000 conversations)
- Professional: \$299/month (up to 10,000 conversations)  
- Enterprise: \$999/month (unlimited conversations)

## Implementation Timeline
- Setup: 1-2 business days
- Training: 3-5 business days
- Go-live: Within 1 week
- Full optimization: 2-4 weeks

## Support & Maintenance
- 24/7 technical support
- Regular system updates
- Performance monitoring
- Backup and disaster recovery

## ROI Benefits
- 40% reduction in customer service costs
- 60% faster response times
- 25% increase in lead conversion
- 90% customer satisfaction rate
";

    // Create sample attachment with documentation
    $attachment = ProductAttachment::create([
        'product_id' => $product->id,
        'file_name' => 'safari_chat_documentation.txt',
        'file_path' => 'attachments/safari_chat_documentation.txt',
        'file_size' => strlen($documentation),
        'mime_type' => 'text/plain',
        'attachment_type' => 'documentation',
        'processing_status' => 'completed',
        'vector_count' => 0
    ]);

    echo "✅ Created sample documentation attachment (ID: {$attachment->id})\n";

    // Store the documentation file
    Storage::put('attachments/safari_chat_documentation.txt', $documentation);
    echo "✅ Saved documentation file\n\n";

    // Update attachment with processing complete
    $attachment->update([
        'processing_status' => 'completed',
        'processed_at' => now()
    ]);

    echo "📊 Product Summary:\n";
    echo "==================\n";
    echo "ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Type: {$product->product_type}\n";
    echo "Price: \${$product->price}\n";
    echo "Documentation: {$attachment->file_name}\n";
    echo "Status: Ready for RAG testing\n\n";

    echo "🎯 Next: Send WhatsApp message to test AI sales conversation!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}