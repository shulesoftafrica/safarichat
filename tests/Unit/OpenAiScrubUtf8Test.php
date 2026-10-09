<?php

namespace Tests\Unit;

use App\Services\OpenAiService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Production: "OpenAI API Error: Malformed UTF-8 characters, possibly incorrectly encoded" even though the
 * customer's own message was plain ASCII. A multibyte character cut in half (substr) somewhere in the system
 * prompt / product text / history made json_encode() of the whole request fail.
 */
class OpenAiScrubUtf8Test extends TestCase
{
    private function scrub($value)
    {
        $service = (new ReflectionClass(OpenAiService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($service, 'scrubUtf8');
        $method->setAccessible(true);

        return $method->invoke($service, $value);
    }

    public function test_a_half_cut_multibyte_character_no_longer_breaks_json_encoding_of_the_request(): void
    {
        $cut = substr('Habari 😀 njema', 0, 10); // cuts the 4-byte emoji in the middle
        $payload = [
            ['role' => 'system', 'content' => 'You sell: ' . $cut],
            ['role' => 'user', 'content' => 'Sawa itakuwa njema'],
        ];

        $this->assertFalse(json_encode($payload), 'precondition: this is the production failure');

        $clean = $this->scrub($payload);

        $this->assertNotFalse(json_encode($clean), 'the scrubbed request must encode');
        $this->assertSame('Sawa itakuwa njema', $clean[1]['content'], 'valid text is left exactly as it was');
        $this->assertStringStartsWith('You sell: Habari ', $clean[0]['content']);
    }

    public function test_valid_unicode_text_and_non_strings_are_untouched(): void
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'messages' => [['role' => 'user', 'content' => 'Karibu 🙂 — mteja wangu, café ñ 你好']],
            'stream' => false,
            'nothing' => null,
        ];

        $this->assertSame($payload, $this->scrub($payload));
    }

    public function test_nested_content_parts_such_as_image_messages_are_scrubbed_too(): void
    {
        $payload = [['role' => 'user', 'content' => [
            ['type' => 'text', 'text' => "caption \xC3"],
            ['type' => 'image_url', 'image_url' => ['url' => 'https://example.com/a.png']],
        ]]];

        $clean = $this->scrub($payload);

        $this->assertNotFalse(json_encode($clean));
        $this->assertSame('https://example.com/a.png', $clean[0]['content'][1]['image_url']['url']);
    }
}
