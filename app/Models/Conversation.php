<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'ai_sales_agent_id', 'product_id', 'message', 'message_type', 'sender_type',
        'message_content', 'conversation_state', 'ai_metadata', 'followup_attempt_at', 
        'followup_scheduled_at', 'followup_sent', 'followup_message',
        'context_data', 'is_active', 'sentiment_score', 'language_detected',
        // New RAG fields
        'rag_sources', 'rag_enhanced', 'customer_message', 'ai_response',
        'sentiment', 'confidence_score', 'tokens_used', 'state', 'summary',
        'ai_actions', 'conversation_context',
        // New queue processing fields
        'status', 'priority', 'processing_started_at', 'processing_timeout_at',
        'retry_count', 'completed_at', 'last_ai_response',
        // New billing fields
        'input_tokens', 'output_tokens', 'ai_model', 'cost_in_credits'
    ];

    protected $casts = [
        'ai_metadata' => 'array',
        'context_data' => 'array',
        'followup_attempt_at' => 'datetime',
        'followup_scheduled_at' => 'datetime',
        'followup_sent' => 'boolean',
        'sentiment_score' => 'decimal:2',
        'is_active' => 'boolean',
        // RAG field casts
        'rag_sources' => 'array',
        'rag_enhanced' => 'integer', // Temporarily changed from boolean to integer
        'confidence_score' => 'decimal:4',
        'tokens_used' => 'integer',
        'ai_actions' => 'array',
        'conversation_context' => 'array',
        // Queue processing field casts
        'priority' => 'integer',
        'processing_started_at' => 'datetime',
        'processing_timeout_at' => 'datetime',
        'retry_count' => 'integer',
        'completed_at' => 'datetime',
        // New billing field casts
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_in_credits' => 'decimal:3'
    ];

    // Message type constants
    const TYPE_CUSTOMER = 'CUSTOMER';
    const TYPE_AI_AGENT = 'AI_AGENT';
    const TYPE_HUMAN_AGENT = 'HUMAN_AGENT';

    // Token to credit conversion constants
    const TOKENS_PER_CREDIT = 3.846;
    const COST_PER_TOKEN_INPUT = 0.0015; // Example: $0.0015 per 1K input tokens
    const COST_PER_TOKEN_OUTPUT = 0.002; // Example: $0.002 per 1K output tokens

    // Conversation state constants
    const STATE_INTRO = 'INTRO';
    const STATE_PITCH = 'PITCH';
    const STATE_DEMO = 'DEMO';
    const STATE_NEGOTIATION = 'NEGOTIATION';
    const STATE_CLOSING = 'CLOSING';
    const STATE_CLOSED = 'CLOSED';
    const STATE_OBJECTION_HANDLING = 'OBJECTION_HANDLING';
    const STATE_FOLLOW_UP = 'FOLLOW_UP';
    
    // Status constants for conversation processing
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_ACTIVE = 'active'; // Additional status for active conversations

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function aiSalesAgent()
    {
        return $this->belongsTo(AiSalesAgent::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get related conversation messages
     * For now, this returns the conversation itself as a collection
     * since each Conversation record represents a single message
     */
    public function messages()
    {
        // Return other conversations in the same lead conversation thread
        return $this->where('lead_id', $this->lead_id)
                   ->orderBy('created_at');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLead($query, $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeCustomerMessages($query)
    {
        return $query->where('message_type', self::TYPE_CUSTOMER);
    }

    public function scopeAiMessages($query)
    {
        return $query->where('message_type', self::TYPE_AI_AGENT);
    }

    public function scopePendingFollowup($query)
    {
        return $query->whereNotNull('followup_attempt_at')
                    ->where('followup_attempt_at', '<=', Carbon::now())
                    ->where('is_active', true);
    }

    public function scopeByState($query, $state)
    {
        return $query->where('conversation_state', $state);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>', Carbon::now()->subDays($days));
    }

    // Business Logic Methods
    public function scheduleFollowup($followupTime = null, $message = null)
    {
        // Accept either hours (int) or Carbon instance
        if (is_numeric($followupTime)) {
            $followupTime = Carbon::now()->addHours($followupTime);
        } elseif ($followupTime === null) {
            $followupTime = Carbon::now()->addHours(24);
        }
        
        $this->update([
            'followup_scheduled_at' => $followupTime,
            'followup_sent' => false
        ]);

        return $this;
    }

    public function clearFollowup()
    {
        $this->update(['followup_attempt_at' => null]);
        return $this;
    }

    public function updateState($newState, $context = [])
    {
        $contextData = array_merge($this->context_data ?? [], $context);
        
        $this->update([
            'conversation_state' => $newState,
            'context_data' => $contextData
        ]);

        return $this;
    }

    public function addAiMetadata($metadata)
    {
        $currentMetadata = $this->ai_metadata ?? [];
        $updatedMetadata = array_merge($currentMetadata, $metadata);
        
        $this->update(['ai_metadata' => $updatedMetadata]);
        return $this;
    }

    public function detectSentiment($message = null)
    {
        $text = $message ?: $this->message_content;
        
        // Simple sentiment analysis
        $positiveWords = ['great', 'excellent', 'good', 'amazing', 'perfect', 'love', 'interested', 'yes'];
        $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'no', 'not interested', 'expensive', 'too much'];
        
        $text = strtolower($text);
        $positiveScore = 0;
        $negativeScore = 0;
        
        foreach ($positiveWords as $word) {
            $positiveScore += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeScore += substr_count($text, $word);
        }
        
        $sentiment = 0;
        if ($positiveScore > $negativeScore) {
            $sentiment = min(1.0, $positiveScore / 5);
        } elseif ($negativeScore > $positiveScore) {
            $sentiment = max(-1.0, -$negativeScore / 5);
        }
        
        $this->update(['sentiment_score' => $sentiment]);
        return $sentiment;
    }

    public function getFormattedTimestamp()
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    public function getMessageTypeIcon()
    {
        return match($this->message_type) {
            self::TYPE_CUSTOMER => '👤',
            self::TYPE_AI_AGENT => '🤖',
            self::TYPE_HUMAN_AGENT => '👨‍💼',
            default => '💬'
        };
    }

    public function getStateColor()
    {
        return match($this->conversation_state) {
            self::STATE_INTRO => 'info',
            self::STATE_PITCH => 'warning',
            self::STATE_DEMO => 'primary',
            self::STATE_NEGOTIATION => 'secondary',
            self::STATE_CLOSING => 'success',
            self::STATE_CLOSED => 'success',
            self::STATE_OBJECTION_HANDLING => 'danger',
            self::STATE_FOLLOW_UP => 'light',
            default => 'secondary'
        };
    }

    public function getSentimentIcon()
    {
        if ($this->sentiment_score === null) return '😐';
        
        if ($this->sentiment_score > 0.5) return '😊';
        if ($this->sentiment_score > 0.1) return '🙂';
        if ($this->sentiment_score < -0.5) return '😞';
        if ($this->sentiment_score < -0.1) return '😕';
        
        return '😐';
    }

    public function getConversationSummary($maxLength = 100)
    {
        $content = strip_tags($this->message_content);
        return strlen($content) > $maxLength ? 
               substr($content, 0, $maxLength) . '...' : 
               $content;
    }

    // Calculated billing properties
    public function getCreditsDeductedAttribute()
    {
        $totalTokens = ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
        return $totalTokens > 0 ? (int) ceil($totalTokens / self::TOKENS_PER_CREDIT) : 0;
    }

    public function getBillingStatusAttribute()
    {
        if (!$this->input_tokens && !$this->output_tokens) {
            return 'no_tokens';
        }
        
        return ($this->cost_in_credits > 0) ? 'processed' : 'pending';
    }

    public function getTotalTokensAttribute()
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }

    public function calculateCostInCredits()
    {
        $inputCost = ($this->input_tokens ?? 0) * (self::COST_PER_TOKEN_INPUT / 1000);
        $outputCost = ($this->output_tokens ?? 0) * (self::COST_PER_TOKEN_OUTPUT / 1000);
        return round($inputCost + $outputCost, 3);
    }

    // Scope for billing queries
    public function scopeWithTokens($query)
    {
        return $query->where(function($q) {
            $q->where('input_tokens', '>', 0)
              ->orWhere('output_tokens', '>', 0);
        });
    }

    public function scopeHighTokenUsage($query, $threshold = 1000)
    {
        return $query->whereRaw('(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) > ?', [$threshold]);
    }
}