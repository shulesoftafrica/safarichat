<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'events_guest_id', 'ai_sales_agent_id', 'user_id', 'business_id', 'name', 'phone_number', 
        'email', 'source', 'status', 'last_interaction_at', 'last_contact_at', 'follow_up_sent_at',
        'notes', 'company_name', 'industry', 'is_churned', 'churn_date', 'churn_reason',
        'churn_notes', 'win_back_eligible_at', 'win_back_attempts', 'last_win_back_at',
        'final_price', 'deal_value', 'conversion_probability', 'lead_score',
        'assigned_agent_id', 'metadata'
    ];

    protected $casts = [
        'last_interaction_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'follow_up_sent_at' => 'datetime',
        'churn_date' => 'datetime',
        'win_back_eligible_at' => 'datetime',
        'last_win_back_at' => 'datetime',
        'final_price' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'conversion_probability' => 'integer',
        'lead_score' => 'integer',
        'win_back_attempts' => 'integer',
        'is_churned' => 'boolean',
        'metadata' => 'array'
    ];

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
        return $this->belongsTo(EventsGuest::class, 'events_guest_id');
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

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function handoffs()
    {
        return $this->hasMany(Handoff::class);
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
        return $this->contact ? $this->contact->getCleanPhone() : null;
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
            $score *= 0.5;
        }

        $this->update(['lead_score' => min(100, max(0, $score))]);
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
}