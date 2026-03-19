<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_contact_id', 'ai_sales_agent_id', 'user_id', 'business_id', 'name', 'phone_number', 
        'email', 'source', 'status', 'last_interaction_at', 'last_activity_at', 'last_contact_at', 'follow_up_sent_at',
        'notes', 'company_name', 'industry', 'is_churned', 'churn_date', 'churn_reason',
        'churn_notes', 'win_back_eligible_at', 'win_back_attempts', 'last_win_back_at',
        'final_price', 'deal_value', 'conversion_probability', 'lead_score',
        'assigned_agent_id', 'metadata', 'negative_sentiment_count', 'positive_sentiment_count',
        'overall_sentiment_score', 'last_reply_at', 'last_chase_at', 'chase_count'
    ];

    protected $casts = [
        'last_interaction_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'follow_up_sent_at' => 'datetime',
        'churn_date' => 'datetime',
        'win_back_eligible_at' => 'datetime',
        'last_win_back_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'last_chase_at' => 'datetime',
        'final_price' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'conversion_probability' => 'integer',
        'lead_score' => 'integer',
        'win_back_attempts' => 'integer',
        'chase_count' => 'integer',
        'negative_sentiment_count' => 'integer',
        'positive_sentiment_count' => 'integer',
        'overall_sentiment_score' => 'decimal:2',
        'is_churned' => 'boolean',
        'metadata' => 'array'
    ];

    // Mutators & Accessors
    
    /**
     * Set the phone number attribute.
     * Automatically sanitizes phone number before storage.
     *
     * @param  string  $value
     * @return void
     */
    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['phone_number'] = sanitize_phone_number($value);
    }

    /**
     * Get the phone number attribute.
     * Ensures consistent format on retrieval.
     *
     * @param  string  $value
     * @return string|null
     */
    public function getPhoneNumberAttribute($value)
    {
        return $value;
    }

    /**
     * Validate if the lead's phone number is in a valid format.
     *
     * @return bool
     */
    public function hasValidPhoneNumber()
    {
        return !empty($this->phone_number) && is_valid_phone_number($this->phone_number);
    }

    /**
     * Validate that all required relationships exist before creating a lead.
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validateRelationships(array $data)
    {
        $errors = [];
        $warnings = [];

        // Check business_id (required)
        if (empty($data['business_id'])) {
            $errors[] = 'Business ID is required';
        } elseif (!\App\Models\Business::where('id', $data['business_id'])->exists()) {
            $errors[] = 'Invalid business ID - business does not exist';
        }

        // Check user_id (required)
        if (empty($data['user_id'])) {
            $errors[] = 'User ID is required';
        } elseif (!\App\Models\User::where('id', $data['user_id'])->exists()) {
            $errors[] = 'Invalid user ID - user does not exist';
        }

        // Check ai_sales_agent_id (recommended but not required)
        if (empty($data['ai_sales_agent_id'])) {
            $warnings[] = 'AI Sales Agent ID not provided - lead will not be auto-managed';
        } elseif (!\App\Models\AiSalesAgent::where('id', $data['ai_sales_agent_id'])->exists()) {
            $errors[] = 'Invalid AI Sales Agent ID - agent does not exist';
        }

        // Check business_contact_id if provided
        if (!empty($data['business_contact_id'])) {
            if (!\App\Models\BusinessContact::where('id', $data['business_contact_id'])->exists()) {
                $errors[] = 'Invalid business contact ID - contact does not exist';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Safely create a lead with validation and error handling.
     *
     * @param array $data
     * @return array ['success' => bool, 'lead' => Lead|null, 'errors' => array, 'warnings' => array]
     */
    public static function safeCreate(array $data)
    {
        // Validate relationships first
        $validation = self::validateRelationships($data);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'lead' => null,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings']
            ];
        }

        try {
            // Sanitize phone number if present
            if (!empty($data['phone_number'])) {
                $data['phone_number'] = sanitize_phone_number($data['phone_number']);
            }

            // Set defaults
            $data['status'] = $data['status'] ?? self::STATUS_NEW;
            $data['source'] = $data['source'] ?? 'manual';
            $data['lead_score'] = $data['lead_score'] ?? 50;

            // Create the lead
            $lead = self::create($data);

            return [
                'success' => true,
                'lead' => $lead,
                'errors' => [],
                'warnings' => $validation['warnings']
            ];

        } catch (\Illuminate\Database\QueryException $e) {
            // Database constraint violation
            \Log::error('Lead creation database error', [
                'data' => $data,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            $errors = ['Database error: ' . $e->getMessage()];
            
            // Check for specific constraint violations
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errors[] = 'One or more related records (business, user, agent) do not exist';
            }
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = 'A lead with these details already exists';
            }

            return [
                'success' => false,
                'lead' => null,
                'errors' => $errors,
                'warnings' => $validation['warnings']
            ];

        } catch (\Exception $e) {
            // General error
            \Log::error('Lead creation failed', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'lead' => null,
                'errors' => ['Failed to create lead: ' . $e->getMessage()],
                'warnings' => $validation['warnings']
            ];
        }
    }

    // Status constants
    const STATUS_NEW = 'NEW';
    const STATUS_OUTREACHED = 'OUTREACHED';
    const STATUS_REPLIED = 'REPLIED';
    const STATUS_ENGAGED = 'ENGAGED';
    const STATUS_QUALIFIED = 'QUALIFIED';
    const STATUS_PITCHED = 'PITCHED';
    const STATUS_DEMO_SCHEDULED = 'DEMO_SCHEDULED';
    const STATUS_PROPOSAL_SENT = 'PROPOSAL_SENT';
    const STATUS_NEGOTIATING = 'NEGOTIATING';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_LOST = 'LOST';
    const STATUS_HANDED_OFF = 'HANDED_OFF';
    const STATUS_DO_NOT_CONTACT = 'DO_NOT_CONTACT';
    const STATUS_NEEDS_ATTENTION = 'NEEDS_ATTENTION';
    const STATUS_CONVERTED = 'CONVERTED';
    const STATUS_CHURNED = 'CHURNED';

    // Relationships
    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function aiSalesAgent()
    {
        return $this->belongsTo(AiSalesAgent::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }

    // Alias for leadProducts() - for backward compatibility
    public function products()
    {
        return $this->leadProducts();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function handoffs()
    {
        return $this->hasMany(Handoff::class);
    }
    
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_LOST, self::STATUS_DO_NOT_CONTACT]);
    }

    public function scopeChurned($query)
    {
        return $query->where('is_churned', true);
    }

    public function scopeWinBackEligible($query)
    {
        return $query->where('is_churned', true)
                    ->where('win_back_eligible_at', '<=', Carbon::now())
                    ->where('win_back_attempts', '<', 3);
    }

    public function scopeNeedsOutreach($query)
    {
        return $query->where('status', self::STATUS_NEW)
                    ->where(function($q) {
                        $q->whereNull('last_interaction_at')
                          ->orWhere('last_interaction_at', '<', Carbon::now()->subDays(30));
                    });
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('ai_sales_agent_id', $agentId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRepliedRecently($query, $days = 7)
    {
        return $query->where('status', self::STATUS_REPLIED)
                    ->where('last_interaction_at', '>', Carbon::now()->subDays($days));
    }

    // Business Logic Methods
    public function getPrimaryProduct()
    {
        return $this->leadProducts()->where('is_primary_product', true)->first()?->product;
    }

    public function getActiveProducts()
    {
        return $this->leadProducts()->where('is_active', true)->with('product')->get()->pluck('product');
    }

    public function getActiveConversation()
    {
        return $this->conversations()->where('is_active', true)->latest()->first();
    }

    public function updateProductStatus($productId, $status, $additionalData = [])
    {
        $leadProduct = $this->leadProducts()->where('product_id', $productId)->first();
        
        if ($leadProduct) {
            $updateData = array_merge(['status' => $status], $additionalData);
            $leadProduct->update($updateData);
            return $leadProduct;
        }
        
        return null;
    }

    public function addProduct($productId, $isPrimary = false, $additionalData = [])
    {
        // Set existing products as non-primary if this is primary
        if ($isPrimary) {
            $this->leadProducts()->update(['is_primary_product' => false]);
        }

        $data = array_merge([
            'product_id' => $productId,
            'is_primary_product' => $isPrimary,
            'status' => 'INTERESTED'
        ], $additionalData);

        return $this->leadProducts()->create($data);
    }

    public function markAsChurned($reason, $notes = null)
    {
        $this->update([
            'is_churned' => true,
            'churn_date' => Carbon::now(),
            'churn_reason' => $reason,
            'churn_notes' => $notes,
            'win_back_eligible_at' => Carbon::now()->addDays(30), // 30 day cooling period
            'status' => self::STATUS_LOST
        ]);

        return $this;
    }

    public function attemptWinBack()
    {
        $this->increment('win_back_attempts');
        $this->update(['last_win_back_at' => Carbon::now()]);
        
        // If max attempts reached, make ineligible for future win-backs
        if ($this->win_back_attempts >= 3) {
            $this->update(['win_back_eligible_at' => null]);
        } else {
            // Schedule next win-back attempt in 60 days
            $this->update(['win_back_eligible_at' => Carbon::now()->addDays(60)]);
        }

        return $this;
    }

    public function scheduleDemo($date, $productId = null)
    {
        $this->update([
            'status' => self::STATUS_DEMO_SCHEDULED,
            'last_interaction_at' => Carbon::now()
        ]);

        if ($productId) {
            $this->updateProductStatus($productId, 'DEMO_REQUESTED', [
                'demo_scheduled_date' => $date
            ]);
        }

        return $this;
    }

    public function getContactName()
    {
        return $this->contact ? $this->contact->guest_names : 'Unknown Contact';
    }

    public function getContactPhone()
    {
        return $this->contact ? $this->contact->guest_phone : null;
    }

    public function getContactCompany()
    {
        return $this->company_name ?: ($this->contact?->guest_company ?: 'Unknown Company');
    }

    public function calculateLeadScore()
    {
        $score = 0;
        
        // Base score for status
        $statusScores = [
            self::STATUS_NEW => 10,
            self::STATUS_OUTREACHED => 20,
            self::STATUS_REPLIED => 40,
            self::STATUS_QUALIFIED => 60,
            self::STATUS_PITCHED => 70,
            self::STATUS_DEMO_SCHEDULED => 80,
            self::STATUS_PROPOSAL_SENT => 85,
            self::STATUS_NEGOTIATING => 90,
            self::STATUS_CLOSED => 100,
            self::STATUS_LOST => 0,
            self::STATUS_DO_NOT_CONTACT => 0
        ];
        
        $score += $statusScores[$this->status] ?? 0;
        
        // Bonus for recent interaction
        if ($this->last_interaction_at && $this->last_interaction_at->gt(Carbon::now()->subDays(7))) {
            $score += 10;
        }
        
        // Bonus for multiple products
        $score += $this->leadProducts()->count() * 5;
        
        // Penalty for churned leads
        if ($this->is_churned) {
            $score = intval($score * 0.5);
        }

        $finalScore = intval(min(100, max(0, $score)));
        $this->update(['lead_score' => $finalScore]);
        return $this->lead_score;
    }

    public function isHighValue()
    {
        return $this->deal_value && $this->deal_value > 10000;
    }

    public function getDaysSinceLastInteraction()
    {
        return $this->last_interaction_at ? 
               $this->last_interaction_at->diffInDays(Carbon::now()) : 
               null;
    }

    public function getExpectedCloseDate()
    {
        $primaryProduct = $this->getPrimaryProduct();
        $salesCycleDays = $primaryProduct?->sales_cycle_days ?? 30;
        
        return $this->created_at->addDays($salesCycleDays);
    }

    /**
     * Get sentiment analysis summary
     */
    public function getSentimentAnalysis()
    {
        $totalInteractions = $this->interaction_count;
        $positiveCount = $this->positive_sentiment_count ?? 0;
        $negativeCount = $this->negative_sentiment_count ?? 0;
        $neutralCount = max(0, $totalInteractions - $positiveCount - $negativeCount);
        
        return [
            'overall_score' => $this->overall_sentiment_score ?? 0,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'neutral_count' => $neutralCount,
            'total_interactions' => $totalInteractions,
            'sentiment_label' => $this->getSentimentLabel(),
            'engagement_quality' => $this->getEngagementQuality()
        ];
    }

    /**
     * Get sentiment label based on overall score
     */
    public function getSentimentLabel()
    {
        $score = $this->overall_sentiment_score ?? 0;
        
        if ($score >= 0.3) return 'Very Positive';
        if ($score >= 0.1) return 'Positive';
        if ($score >= -0.1) return 'Neutral';
        if ($score >= -0.3) return 'Negative';
        return 'Very Negative';
    }

    /**
     * Get engagement quality assessment
     */
    public function getEngagementQuality()
    {
        $score = $this->overall_sentiment_score ?? 0;
        $interactions = $this->interaction_count;
        
        if ($interactions === 0) return 'No Data';
        
        if ($score >= 0.2 && $interactions >= 3) return 'Excellent';
        if ($score >= 0.1 && $interactions >= 2) return 'Good';
        if ($score >= -0.1) return 'Fair';
        return 'Poor';
    }

    /**
     * Check if lead needs attention based on sentiment
     */
    public function needsSentimentAttention()
    {
        return $this->overall_sentiment_score <= -0.2 || 
               $this->negative_sentiment_count >= 3;
    }
}