<?php

namespace Tests\Unit;

use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Services\OpenAiService;
use App\Services\RagSearchService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Each business on the platform is independent. Healthbloom International (no products, no documents yet) was answered
 * as if it sold ShuleSoft's school software: its document search ran across every business's documents, and the shared
 * prompt itself named ShuleSoft in its examples.
 */
class BusinessIsolationTest extends TestCase
{
    public function test_document_search_without_products_returns_nothing_instead_of_searching_every_business(): void
    {
        $search = (new \ReflectionClass(RagSearchService::class))->newInstanceWithoutConstructor();

        $this->assertSame([], $search->searchDocuments('what services do you offer?', null, 3));
        $this->assertSame([], $search->searchDocuments('what services do you offer?', [], 3));
        $this->assertSame([], $search->searchDocuments('what services do you offer?', [0, null], 3));
    }

    public function test_the_shared_prompt_never_names_another_business_and_pins_the_assistant_to_its_own(): void
    {
        $service = (new \ReflectionClass(OpenAiService::class))->newInstanceWithoutConstructor();
        $agent = new AiSalesAgent(['assistant_name' => 'Amina']);
        $agent->setRelation('user', null); // no database in a unit test
        $method = new ReflectionMethod(OpenAiService::class, 'buildSystemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, $agent, new Lead(), null, null);

        // no business name configured: falls back to "our company", never to another business
        $this->assertStringNotContainsString('school management', strtolower($prompt));
        $this->assertStringNotContainsString('parent tools', strtolower($prompt));
        $this->assertStringContainsString('STRICT BOUNDARY', $prompt);
        $this->assertStringContainsString('Never mention, describe, compare or recommend any other company', $prompt);
        $this->assertStringContainsString('Do not guess or invent what the business sells', $prompt);

        // ShuleSoft appears only inside the "never mention" rule, not as an example or pitch
        $this->assertSame(1, substr_count($prompt, 'ShuleSoft'));
    }
}
