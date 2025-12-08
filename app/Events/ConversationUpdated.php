<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $action;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, string $action = 'updated')
    {
        $this->conversation = $conversation;
        $this->action = $action;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lead.' . $this->conversation->lead_id),
            new PrivateChannel('user.' . $this->conversation->lead->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'lead_id' => $this->conversation->lead_id,
            'message_type' => $this->conversation->message_type,
            'conversation_state' => $this->conversation->conversation_state,
            'action' => $this->action,
            'confidence_score' => $this->conversation->confidence_score,
            'timestamp' => $this->timestamp,
            'message_preview' => substr($this->conversation->message_content, 0, 100) . '...'
        ];
    }
}