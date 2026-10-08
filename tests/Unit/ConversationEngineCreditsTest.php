<?php

namespace Tests\Unit;

use App\Console\Commands\ConversationEngineCommand;
use App\Models\Conversation;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * No database: the Conversation model and the logger are mocked.
 *
 * Covers the 2026-10 production incident where ~400,000 "insufficient_ai_credits" ERROR lines were written
 * and failed conversations were retried even though retrying cannot succeed.
 */
class ConversationEngineCreditsTest extends TestCase
{
    private function engine(): ConversationEngineCommand
    {
        $engine = new ConversationEngineCommand(
            Mockery::mock(AiWhatsAppService::class),
            Mockery::mock(OpenAiService::class)
        );
        // artisan normally supplies the console output; a null one is enough here
        $engine->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));

        return $engine;
    }

    private function invoke(ConversationEngineCommand $engine, string $method, array $args = [])
    {
        $m = new ReflectionMethod($engine, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($engine, $args);
    }

    private function conversation(int $leadId, int $retryCount = 0): Conversation
    {
        $c = Mockery::mock(Conversation::class)->makePartial();
        $c->lead_id = $leadId;
        $c->retry_count = $retryCount;
        $c->id = $leadId * 10;

        return $c;
    }

    public function test_no_credits_failure_is_permanent_and_stored_in_ai_metadata_not_last_error(): void
    {
        $conversation = $this->conversation(7, 0);
        $conversation->shouldReceive('update')->once()->with(Mockery::on(function (array $a) {
            return $a['status'] === Conversation::STATUS_FAILED
                && $a['retry_count'] >= 3                                   // never picked up by retryFailedConversations()
                && ($a['ai_metadata']['last_error'] ?? null) === 'insufficient_ai_credits'
                && !array_key_exists('last_error', $a);                     // that column does not exist
        }))->andReturn(true);

        // A permanent no-credit failure must not write a per-conversation ERROR line.
        Log::shouldReceive('error')->never();

        $this->invoke($this->engine(), 'markConversationFailed', [$conversation, 'insufficient_ai_credits', false]);
    }

    public function test_ordinary_failure_is_still_retryable_and_logged_as_error(): void
    {
        $conversation = $this->conversation(8, 1);
        $conversation->shouldReceive('update')->once()->with(Mockery::on(function (array $a) {
            return $a['status'] === Conversation::STATUS_FAILED && $a['retry_count'] === 2;
        }))->andReturn(true);

        Log::shouldReceive('error')->once()->with('Conversation processing failed', Mockery::type('array'));

        $this->invoke($this->engine(), 'markConversationFailed', [$conversation, 'boom']);
    }

    public function test_many_skipped_conversations_produce_a_single_summary_line(): void
    {
        $engine = $this->engine();
        Log::shouldReceive('error')->never();
        Log::shouldReceive('warning')->once()->with(
            'Conversations skipped: insufficient AI credits',
            ['conversations' => 3, 'leads' => 2]
        );

        foreach ([[1, 'a'], [1, 'b'], [2, 'c']] as [$lead]) {
            $c = $this->conversation($lead);
            $c->shouldReceive('update')->once()->andReturn(true);
            $this->invoke($engine, 'markConversationFailed', [$c, 'insufficient_ai_credits', false]);
        }

        $this->invoke($engine, 'reportCreditSkips');
        // a second report in the same run must not log again
        $this->invoke($engine, 'reportCreditSkips');
    }

    public function test_escalation_no_longer_depends_on_missing_constant_or_columns(): void
    {
        $conversation = $this->conversation(9, 0);
        $conversation->shouldReceive('update')->once()->with(Mockery::on(function (array $a) {
            return $a['status'] === Conversation::STATUS_FAILED
                && ($a['ai_metadata']['requires_human_handoff'] ?? false) === true
                && !array_key_exists('requires_human_handoff', $a)
                && !array_key_exists('handoff_reason', $a);
        }))->andReturn(true);

        Log::shouldReceive('warning')->once();

        $this->invoke($this->engine(), 'escalateConversation', [$conversation, 'x']);
    }
}
