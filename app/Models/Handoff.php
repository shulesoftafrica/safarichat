<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Handoff extends Model
{
    use HasFactory;

    /**
     * Boot method to set up model event listeners
     */
    protected static function boot()
    {
        parent::boot();
        
        // Automatically set SLA deadline when creating handoffs
        static::creating(function ($handoff) {
            if (empty($handoff->sla_deadline)) {
                $handoff->sla_deadline = $handoff->calculateSlaDeadline();
            }
        });
    }

    protected $fillable = [
        'lead_id', 'reason_code', 'ai_summary', 'human_agent_id', 'status',
        'assigned_at', 'resolved_at', 'resolution_notes', 'customer_satisfaction',
        'context_data', 'priority_level', 'estimated_resolution_time', 'sla_deadline'
    ];

    protected $attributes = [
        'reason_code' => self::REASON_COMPLEX_QUESTION, // Default reason code
        'status' => self::STATUS_PENDING,
        'priority_level' => self::PRIORITY_MEDIUM
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'context_data' => 'array',
        'customer_satisfaction' => 'integer',
        'estimated_resolution_time' => 'integer'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_ESCALATED = 'escalated';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Reason code constants
    const REASON_COMPLEX_QUESTION = 'COMPLEX_QUESTION';
    const REASON_COMPLAINT = 'COMPLAINT';
    const REASON_LARGE_ORDER = 'LARGE_ORDER';
    const REASON_PAYMENT_ISSUE = 'PAYMENT_ISSUE';
    const REASON_ANGRY_CUSTOMER = 'ANGRY_CUSTOMER';
    const REASON_AI_ERROR = 'AI_ERROR';
    const REASON_LOW_STOCK = 'LOW_STOCK';
    const REASON_GENERAL_ESCALATION = 'GENERAL_ESCALATION';
    const REASON_CUSTOMER_REQUEST = 'CUSTOMER_REQUEST';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function humanAgent()
    {
        return $this->belongsTo(User::class, 'human_agent_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('assigned_at', '<', Carbon::now()->subHours(4))
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority_level', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('human_agent_id', $agentId);
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('reason_code', $reason);
    }

    // Business Logic Methods
    public function assignTo($agentId, $estimatedTime = null)
    {
        $this->update([
            'human_agent_id' => $agentId,
            'status' => self::STATUS_ASSIGNED,
            'assigned_at' => Carbon::now(),
            'estimated_resolution_time' => $estimatedTime
        ]);

        return $this;
    }

    public function markInProgress()
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
        return $this;
    }

    public function markResolved($resolutionNotes = null, $satisfaction = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => Carbon::now(),
            'resolution_notes' => $resolutionNotes,
            'customer_satisfaction' => $satisfaction
        ]);

        return $this;
    }

    public function escalate($newPriority = self::PRIORITY_HIGH)
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'priority_level' => $newPriority
        ]);

        return $this;
    }

    public function isOverdue()
    {
        if (!$this->assigned_at || $this->status === self::STATUS_RESOLVED) {
            return false;
        }
        
        $slaHours = match($this->priority_level) {
            self::PRIORITY_URGENT => 0.5,  // 30 minutes
            self::PRIORITY_HIGH => 2,      // 2 hours
            self::PRIORITY_MEDIUM => 4,    // 4 hours
            self::PRIORITY_LOW => 24,      // 24 hours
            default => 4
        };
        
        return $this->assigned_at->diffInHours(Carbon::now()) > $slaHours;
    }

    public function getTimeToResolution()
    {
        if (!$this->resolved_at || !$this->assigned_at) {
            return null;
        }
        
        return $this->assigned_at->diffInMinutes($this->resolved_at);
    }

    public function getElapsedTime()
    {
        if (!$this->assigned_at) {
            return $this->created_at->diffForHumans();
        }
        
        if ($this->resolved_at) {
            return $this->assigned_at->diffForHumans($this->resolved_at);
        }
        
        return $this->assigned_at->diffForHumans();
    }

    public function getPriorityColor()
    {
        return match($this->priority_level) {
            self::PRIORITY_URGENT => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => 'primary',
            self::PRIORITY_LOW => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusColor()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ASSIGNED => 'info',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_ESCALATED => 'danger',
            default => 'secondary'
        };
    }

    public function getReasonDescription()
    {
        return match($this->reason_code) {
            self::REASON_COMPLEX_QUESTION => 'Complex Technical Question',
            self::REASON_COMPLAINT => 'Customer Complaint',
            self::REASON_LARGE_ORDER => 'Large Order (Requires Approval)',
            self::REASON_PAYMENT_ISSUE => 'Payment Processing Issue',
            self::REASON_ANGRY_CUSTOMER => 'Angry/Frustrated Customer',
            self::REASON_AI_ERROR => 'AI System Error',
            self::REASON_LOW_STOCK => 'Low Stock Issue',
            self::REASON_GENERAL_ESCALATION => 'General Escalation',
            self::REASON_CUSTOMER_REQUEST => 'Customer Request for Human Agent',
            default => 'Other Reason'
        };
    }

    public function getSlaTimeRemaining()
    {
        if (!$this->assigned_at || $this->status === self::STATUS_RESOLVED) {
            return null;
        }
        
        $slaHours = match($this->priority_level) {
            self::PRIORITY_URGENT => 0.5,
            self::PRIORITY_HIGH => 2,
            self::PRIORITY_MEDIUM => 4,
            self::PRIORITY_LOW => 24,
            default => 4
        };
        
        $slaDeadline = $this->assigned_at->addHours($slaHours);
        $now = Carbon::now();
        
        if ($now->gt($slaDeadline)) {
            return 'Overdue';
        }
        
        return $now->diffForHumans($slaDeadline);
    }

    public function addContextData($key, $value)
    {
        $context = $this->context_data ?? [];
        $context[$key] = $value;
        
        $this->update(['context_data' => $context]);
        return $this;
    }

    /**
     * Calculate SLA deadline based on priority level
     */
    public function calculateSlaDeadline(): Carbon
    {
        $hours = match($this->priority_level) {
            self::PRIORITY_URGENT => 0.5,  // 30 minutes
            self::PRIORITY_HIGH => 2,      // 2 hours
            self::PRIORITY_MEDIUM => 4,    // 4 hours
            self::PRIORITY_LOW => 24,      // 24 hours
            default => 4                   // Default 4 hours
        };
        
        return Carbon::now()->addHours($hours);
    }

    /**
     * Create a handoff with proper defaults and validation
     */
    public static function createHandoff(array $data)
    {
        // Ensure required fields have defaults
        $data = array_merge([
            'reason_code' => self::REASON_GENERAL_ESCALATION,
            'status' => self::STATUS_PENDING,
            'priority_level' => self::PRIORITY_MEDIUM,
            'ai_summary' => 'Customer requested human assistance during conversation'
        ], $data);

        // Validate required fields
        if (empty($data['lead_id'])) {
            throw new \InvalidArgumentException('lead_id is required for handoff creation');
        }

        if (empty($data['reason_code'])) {
            $data['reason_code'] = self::REASON_GENERAL_ESCALATION;
        }

        return self::create($data);
    }

    /**
     * Get all available reason codes
     */
    public static function getReasonCodes()
    {
        return [
            self::REASON_COMPLEX_QUESTION,
            self::REASON_COMPLAINT,
            self::REASON_LARGE_ORDER,
            self::REASON_PAYMENT_ISSUE,
            self::REASON_ANGRY_CUSTOMER,
            self::REASON_AI_ERROR,
            self::REASON_LOW_STOCK,
            self::REASON_GENERAL_ESCALATION,
            self::REASON_CUSTOMER_REQUEST
        ];
    }
}