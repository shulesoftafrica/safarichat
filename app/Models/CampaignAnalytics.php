<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'total_sent',
        'total_delivered',
        'total_read',
        'total_replied',
        'avg_response_time',
        'positive_sentiment_count',
        'neutral_sentiment_count',
        'negative_sentiment_count',
        'opt_out_count',
        'conversion_count',
        'revenue_generated',
        'credits_spent',
        'roi',
    ];

    protected $casts = [
        'total_sent' => 'integer',
        'total_delivered' => 'integer',
        'total_read' => 'integer',
        'total_replied' => 'integer',
        'avg_response_time' => 'integer',
        'positive_sentiment_count' => 'integer',
        'neutral_sentiment_count' => 'integer',
        'negative_sentiment_count' => 'integer',
        'opt_out_count' => 'integer',
        'conversion_count' => 'integer',
        'revenue_generated' => 'decimal:2',
        'credits_spent' => 'integer',
        'roi' => 'decimal:2',
    ];

    /**
     * Get the campaign that owns this analytics record
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Calculate delivery rate
     */
    public function getDeliveryRateAttribute()
    {
        if ($this->total_sent == 0) {
            return 0;
        }

        return round(($this->total_delivered / $this->total_sent) * 100, 2);
    }

    /**
     * Calculate read rate
     */
    public function getReadRateAttribute()
    {
        if ($this->total_delivered == 0) {
            return 0;
        }

        return round(($this->total_read / $this->total_delivered) * 100, 2);
    }

    /**
     * Calculate reply rate
     */
    public function getReplyRateAttribute()
    {
        if ($this->total_read == 0) {
            return 0;
        }

        return round(($this->total_replied / $this->total_read) * 100, 2);
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute()
    {
        if ($this->total_sent == 0) {
            return 0;
        }

        return round(($this->conversion_count / $this->total_sent) * 100, 2);
    }

    /**
     * Calculate average response time in human-readable format
     */
    public function getAvgResponseTimeFormattedAttribute()
    {
        $minutes = $this->avg_response_time;

        if ($minutes < 60) {
            return $minutes . ' minutes';
        } elseif ($minutes < 1440) {
            return round($minutes / 60, 1) . ' hours';
        } else {
            return round($minutes / 1440, 1) . ' days';
        }
    }

    /**
     * Calculate positive sentiment rate
     */
    public function getPositiveSentimentRateAttribute()
    {
        $total = $this->positive_sentiment_count + $this->neutral_sentiment_count + $this->negative_sentiment_count;

        if ($total == 0) {
            return 0;
        }

        return round(($this->positive_sentiment_count / $total) * 100, 2);
    }

    /**
     * Calculate negative sentiment rate
     */
    public function getNegativeSentimentRateAttribute()
    {
        $total = $this->positive_sentiment_count + $this->neutral_sentiment_count + $this->negative_sentiment_count;

        if ($total == 0) {
            return 0;
        }

        return round(($this->negative_sentiment_count / $total) * 100, 2);
    }

    /**
     * Calculate cost per conversion
     */
    public function getCostPerConversionAttribute()
    {
        if ($this->conversion_count == 0) {
            return 0;
        }

        return round($this->credits_spent / $this->conversion_count, 2);
    }

    /**
     * Calculate ROI percentage
     */
    public function calculateROI()
    {
        if ($this->credits_spent == 0) {
            return 0;
        }

        // Assuming 1 credit = $0.01 (adjust based on your pricing)
        $costInDollars = $this->credits_spent * 0.01;
        $profit = $this->revenue_generated - $costInDollars;

        return round(($profit / $costInDollars) * 100, 2);
    }

    /**
     * Increment sent count
     */
    public function incrementSent()
    {
        $this->increment('total_sent');
    }

    /**
     * Increment delivered count
     */
    public function incrementDelivered()
    {
        $this->increment('total_delivered');
    }

    /**
     * Increment read count
     */
    public function incrementRead()
    {
        $this->increment('total_read');
    }

    /**
     * Increment replied count
     */
    public function incrementReplied()
    {
        $this->increment('total_replied');
    }

    /**
     * Increment sentiment count
     */
    public function incrementSentiment($sentiment)
    {
        switch ($sentiment) {
            case 'positive':
                $this->increment('positive_sentiment_count');
                break;
            case 'neutral':
                $this->increment('neutral_sentiment_count');
                break;
            case 'negative':
                $this->increment('negative_sentiment_count');
                break;
        }
    }

    /**
     * Increment conversion count
     */
    public function incrementConversion($revenue = 0)
    {
        $this->increment('conversion_count');
        
        if ($revenue > 0) {
            $this->increment('revenue_generated', $revenue);
        }

        // Recalculate ROI
        $this->update(['roi' => $this->calculateROI()]);
    }

    /**
     * Add credits spent
     */
    public function addCreditsSpent($credits)
    {
        $this->increment('credits_spent', $credits);
        
        // Recalculate ROI
        $this->update(['roi' => $this->calculateROI()]);
    }

    /**
     * Update average response time
     */
    public function updateAvgResponseTime($newResponseTimeMinutes)
    {
        $currentTotal = $this->avg_response_time * $this->total_replied;
        $newTotal = $currentTotal + $newResponseTimeMinutes;
        $newCount = $this->total_replied + 1;

        $this->update(['avg_response_time' => round($newTotal / $newCount)]);
    }
}
