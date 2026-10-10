<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\IncomingMessage;
use App\Models\Lead;
use App\Services\AiWhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Production: a customer got 2-3 AI answers to a single WhatsApp message - one straight away and more 10-15
 * minutes later. The webhook replied directly, then saved the conversation with the column default status
 * ('pending'); the 5-minute ConversationEngineCommand then answered that same row again.
 *
 * Runs inside a rolled-back transaction, so the shared local database is not changed.
 */
class ConversationEngineDoubleReplyTest extends TestCase
{
    use DatabaseTransactions;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = Lead::query()->firstOrFail();
    }

    private function row(array $attrs): Conversation
    {
        // saved with forceFill/insert semantics of the model; status is set explicitly in every case
        $c = new Conversation();
        $c->forceFill(array_merge([
            'lead_id' => $this->lead->id,
            'message_content' => 'test',
            'customer_message' => 'test',
            'status' => Conversation::STATUS_PENDING,
            'sender_type' => 'customer',
            'message_type' => Conversation::TYPE_CUSTOMER,
        ], $attrs))->save();

        return $c;
    }

    public function test_webhook_saved_conversation_is_recorded_as_completed_not_pending(): void
    {
        $service = app(AiWhatsAppService::class);
        $method = new ReflectionMethod($service, 'saveConversation');
        $method->setAccessible(true);

        $message = new IncomingMessage([
            'phone_number' => '255700000001',
            'message_body' => 'How much is it?',
            'message_type' => 'text',
        ]);

        $conversation = $method->invoke(
            $service,
            $this->lead,
            $message,
            ['response' => 'It costs TZS 10,000.', 'confidence' => 0.9, 'tokens_used' => 10, 'actions' => []],
            ['sentiment' => 'neutral'],
            null,
            []
        );

        $fresh = Conversation::findOrFail($conversation->id);
        $this->assertSame(Conversation::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertFalse(
            Conversation::where('id', $fresh->id)->where('status', Conversation::STATUS_PENDING)->exists(),
            'the engine selects status=pending, so this row must never be pending'
        );
    }

    public function test_engine_only_picks_up_unanswered_customer_messages(): void
    {
        $unanswered = $this->row(['customer_message' => 'Hello']);
        $answered = $this->row(['ai_response' => 'Hi there']);
        $followUp = $this->row(['sender_type' => 'ai_agent', 'message_type' => Conversation::TYPE_AI_AGENT]);
        $manual = $this->row(['sender_type' => 'user_manual']);

        $ids = Conversation::where('status', Conversation::STATUS_PENDING)
            ->awaitingAiReply()
            ->whereIn('id', [$unanswered->id, $answered->id, $followUp->id, $manual->id])
            ->pluck('id')
            ->all();

        $this->assertSame([$unanswered->id], $ids);
    }
}
