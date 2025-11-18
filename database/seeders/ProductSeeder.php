<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductFaq;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample Products Data
        $products = [
            [
                'name' => 'WhatsApp Business Package Pro',
                'sku' => 'WBP-001',
                'category' => 'Communication',
                'description' => 'Complete WhatsApp automation solution with AI integration for businesses. This package includes automated responses, lead management, customer support automation, and analytics dashboard.',
                'retail_price' => 299.00,
                'wholesale_price' => 249.00,
                'max_discount' => 15,
                'quantity' => null, // Unlimited
                'status' => 'active',
                'tags' => ['hot-deal', 'featured'],
                'ai_generated_description' => false,
                'faqs' => [
                    [
                        'question' => 'What features are included in the Pro package?',
                        'answer' => 'AI-powered responses, lead management, analytics dashboard, customer support automation, and priority support.'
                    ],
                    [
                        'question' => 'Is there a setup fee?',
                        'answer' => 'No, there are no setup fees. You only pay the monthly subscription.'
                    ]
                ]
            ],
            [
                'name' => 'SMS Marketing Starter',
                'sku' => 'SMS-001',
                'category' => 'Marketing',
                'description' => 'Affordable SMS marketing solution for small businesses. Send bulk SMS campaigns, manage contacts, and track delivery rates.',
                'retail_price' => 49.00,
                'wholesale_price' => 39.00,
                'max_discount' => 20,
                'quantity' => 100,
                'status' => 'active',
                'tags' => ['new-arrival', 'bestseller'],
                'ai_generated_description' => false,
                'faqs' => [
                    [
                        'question' => 'How many SMS can I send per month?',
                        'answer' => 'The starter package includes 1,000 SMS credits per month.'
                    ],
                    [
                        'question' => 'Can I schedule SMS campaigns?',
                        'answer' => 'Yes, you can schedule SMS campaigns for optimal delivery times.'
                    ]
                ]
            ],
            [
                'name' => 'Email Automation Suite',
                'sku' => 'EMAIL-001',
                'category' => 'Email Marketing',
                'description' => 'Comprehensive email marketing platform with drag-and-drop editor, automation workflows, and detailed analytics.',
                'retail_price' => 79.00,
                'wholesale_price' => 65.00,
                'max_discount' => 10,
                'quantity' => 50,
                'status' => 'active',
                'tags' => ['featured'],
                'ai_generated_description' => false,
                'faqs' => [
                    [
                        'question' => 'Does it include email templates?',
                        'answer' => 'Yes, we provide 50+ professional email templates ready to use.'
                    ],
                    [
                        'question' => 'What automation features are available?',
                        'answer' => 'Welcome series, abandoned cart recovery, birthday campaigns, and custom triggers.'
                    ]
                ]
            ],
            [
                'name' => 'Social Media Manager',
                'sku' => 'SMM-001',
                'category' => 'Social Media',
                'description' => 'All-in-one social media management tool supporting Facebook, Instagram, Twitter, and LinkedIn with scheduling and analytics.',
                'retail_price' => 159.00,
                'wholesale_price' => 129.00,
                'max_discount' => 25,
                'quantity' => 25,
                'status' => 'draft',
                'tags' => ['limited-stock'],
                'ai_generated_description' => false,
                'faqs' => [
                    [
                        'question' => 'Which social media platforms are supported?',
                        'answer' => 'Facebook, Instagram, Twitter, LinkedIn, and YouTube.'
                    ],
                    [
                        'question' => 'Can I manage multiple accounts?',
                        'answer' => 'Yes, you can manage up to 10 social media accounts with this package.'
                    ]
                ]
            ],
            [
                'name' => 'AI Chatbot Builder',
                'sku' => 'CHAT-001',
                'category' => 'AI Tools',
                'description' => 'Create intelligent chatbots for websites and messaging platforms with natural language processing and machine learning.',
                'retail_price' => 199.00,
                'wholesale_price' => 169.00,
                'max_discount' => 12,
                'quantity' => null, // Unlimited
                'status' => 'inactive',
                'tags' => ['ai-powered'],
                'ai_generated_description' => false,
                'faqs' => [
                    [
                        'question' => 'Do I need coding skills to build chatbots?',
                        'answer' => 'No, our drag-and-drop interface makes it easy for anyone to create chatbots.'
                    ],
                    [
                        'question' => 'Can the chatbot learn from conversations?',
                        'answer' => 'Yes, it uses machine learning to improve responses over time.'
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $faqs = $productData['faqs'];
            unset($productData['faqs']);
            
            $product = Product::create($productData);
            
            // Create FAQs for the product
            foreach ($faqs as $faqData) {
                ProductFaq::create([
                    'product_id' => $product->id,
                    'question' => $faqData['question'],
                    'answer' => $faqData['answer']
                ]);
            }
        }

        $this->command->info('Sample products created successfully!');
    }
}
