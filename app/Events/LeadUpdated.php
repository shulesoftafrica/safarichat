<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $action;
    public $changes;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(Lead $lead, string $action = 'updated', array $changes = [])
    {
        $this->lead = $lead;
        $this->action = $action;
        $this->changes = $changes;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lead.' . $this->lead->id),
            new PrivateChannel('user.' . $this->lead->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'lead.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'contact_name' => $this->lead->contact->guest_name ?? 'Unknown',
            'status' => $this->lead->status,
            'lead_score' => $this->lead->lead_score,
            'action' => $this->action,
            'changes' => $this->changes,
            'timestamp' => $this->timestamp,
            'last_interaction_at' => $this->lead->last_interaction_at
        ];
    }
}