<?php

namespace Tests\Unit;

use App\Services\OpenAiService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use ReflectionClass;
use Tests\TestCase;

/**
 * Production: the OpenAI account ran out of credits (HTTP 429 insufficient_quota, shown by the client library as
 * "Request rate limit has been exceeded") and no customer got an AI reply for hours. Chat completions now fall back to
 * Gemini's OpenAI-compatible endpoint.
 *
 * Both clients are replaced with fakes; nothing leaves the machine.
 */
class OpenAiGeminiFallbackTest extends TestCase
{
    private function fakeClient(callable $create): object
    {
        return new class($create) {
            public function __construct(private $create) {}

            public function chat(): object
            {
                $create = $this->create;

                return new class($create) {
                    public array $calls = [];

                    public function __construct(private $create) {}

                    public function create(array $params)
                    {
                        $this->calls[] = $params;

                        return ($this->create)($params);
                    }
                };
            }
        };
    }

    private function service($openAi, $gemini = null): OpenAiService
    {
        $service = (new ReflectionClass(OpenAiService::class))->newInstanceWithoutConstructor();
        $ref = new ReflectionClass(OpenAiService::class);

        foreach (['client' => $openAi, 'fallbackClient' => $gemini ?? false] as $prop => $value) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($service, $value);
        }

        return $service;
    }

    private function chat(OpenAiService $service, array $params = [])
    {
        $m = new \ReflectionMethod($service, 'chatCreate');
        $m->setAccessible(true);

        return $m->invoke($service, $params + [
            'model' => 'gpt-4o',
            'messages' => [['role' => 'user', 'content' => 'hi']],
            'max_tokens' => 100,
            'temperature' => 0.7,
            'presence_penalty' => 0.1,
            'frequency_penalty' => 0.1,
        ]);
    }

    private function quotaError(): RateLimitException
    {
        return new RateLimitException(new Response(429, [], json_encode([
            'error' => ['message' => 'You have no credits remaining.', 'type' => 'insufficient_quota', 'code' => 'credit_balance_exhausted'],
        ])));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('openai_unavailable_until_probe');
        config(['services.gemini.model' => 'gemini-test-model']);
    }

    protected function tearDown(): void
    {
        Cache::forget('openai_unavailable_until_probe');
        parent::tearDown();
    }

    public function test_exhausted_openai_credits_fall_back_to_gemini_with_adapted_params(): void
    {
        $openAi = $this->fakeClient(fn () => throw $this->quotaError());
        $gemini = $this->fakeClient(fn () => 'GEMINI-RESPONSE');

        $result = $this->chat($this->service($openAi, $gemini));

        $this->assertSame('GEMINI-RESPONSE', $result);

        $sent = $gemini->chat()->calls ?? null;
        // chat() builds a new recorder each call, so assert through a captured copy instead
        $captured = [];
        $gemini2 = $this->fakeClient(function ($p) use (&$captured) {
            $captured = $p;

            return 'ok';
        });
        $this->chat($this->service($this->fakeClient(fn () => throw $this->quotaError()), $gemini2));

        $this->assertSame('gemini-test-model', $captured['model']);
        $this->assertArrayNotHasKey('presence_penalty', $captured);
        $this->assertArrayNotHasKey('frequency_penalty', $captured);
        $this->assertSame(100, $captured['max_tokens']);
        $this->assertSame('hi', $captured['messages'][0]['content']);
    }

    public function test_openai_is_skipped_while_it_is_known_to_be_down(): void
    {
        $openAiCalls = 0;
        $openAi = $this->fakeClient(function () use (&$openAiCalls) {
            $openAiCalls++;
            throw $this->quotaError();
        });
        $gemini = $this->fakeClient(fn () => 'G');
        $service = $this->service($openAi, $gemini);

        $this->chat($service);
        $this->chat($service);
        $this->chat($service);

        $this->assertSame(1, $openAiCalls, 'after the first failure the next calls go straight to Gemini');
    }

    public function test_a_healthy_openai_never_touches_gemini(): void
    {
        $geminiCalls = 0;
        $openAi = $this->fakeClient(fn () => 'OPENAI-RESPONSE');
        $gemini = $this->fakeClient(function () use (&$geminiCalls) {
            $geminiCalls++;

            return 'G';
        });

        $this->assertSame('OPENAI-RESPONSE', $this->chat($this->service($openAi, $gemini)));
        $this->assertSame(0, $geminiCalls);
    }

    public function test_errors_gemini_cannot_fix_are_not_masked(): void
    {
        $badRequest = new ErrorException(
            ['message' => 'maximum context length exceeded', 'type' => 'invalid_request_error', 'code' => 'context_length_exceeded'],
            new Response(400)
        );
        $openAi = $this->fakeClient(fn () => throw $badRequest);
        $gemini = $this->fakeClient(fn () => 'G');

        $this->expectException(ErrorException::class);
        $this->chat($this->service($openAi, $gemini));
    }

    public function test_without_a_gemini_key_the_original_error_is_raised(): void
    {
        $openAi = $this->fakeClient(fn () => throw $this->quotaError());

        $this->expectException(RateLimitException::class);
        $this->chat($this->service($openAi, null));
    }

    public function test_server_errors_also_fall_back(): void
    {
        $serverError = new ErrorException(['message' => 'The server had an error', 'type' => 'server_error', 'code' => null], new Response(503));
        $openAi = $this->fakeClient(fn () => throw $serverError);
        $gemini = $this->fakeClient(fn () => 'G');

        $this->assertSame('G', $this->chat($this->service($openAi, $gemini)));
    }
}
