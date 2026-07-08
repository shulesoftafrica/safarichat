<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\OutgoingMessage;
use App\Models\BusinessContact;
use App\Models\Lead;
use App\Models\Product;
use \Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display campaign list (Sales Campaigns hub page)
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->with('analytics')
            ->latest()
            ->paginate(20);

        // Calculate additional metrics for each campaign
        foreach ($campaigns as $campaign) {
            // Get progress percentage
            if ($campaign->total_recipients > 0) {
                $processed = $campaign->sent_count + $campaign->failed_count;
                $campaign->progress_percentage = round(($processed / $campaign->total_recipients) * 100, 1);
            } else {
                $campaign->progress_percentage = 0;
            }

            // Get status badge color
            $campaign->status_color = $this->getStatusColor($campaign->status);

            // Get status icon
            $campaign->status_icon = $this->getStatusIcon($campaign->status);
        }

        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show campaign creation form
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [];
     
        if (Auth::user()->whatsappInstances()->count() == 0) {
            // Create a new WhatsApp instance for the user
            \App\Models\WhatsappInstance::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'phone_number' => ltrim(Auth::user()->phone, '+'),
                'instance_name' => Auth::user()->name,
            ]);
        }
        
        // Pass Lead Statuses for recipient selection
        $data['lead_statuses'] = [
            Lead::STATUS_NEW => 'New',
            Lead::STATUS_OUTREACHED => 'Outreached',
            Lead::STATUS_REPLIED => 'Replied',
            Lead::STATUS_ENGAGED => 'Engaged',
            Lead::STATUS_QUALIFIED => 'Qualified',
            Lead::STATUS_PITCHED => 'Pitched',
            Lead::STATUS_DEMO_SCHEDULED => 'Demo Scheduled',
            Lead::STATUS_PROPOSAL_SENT => 'Proposal Sent',
            Lead::STATUS_NEGOTIATING => 'Negotiating',
            Lead::STATUS_CLOSED => 'Closed',
            Lead::STATUS_LOST => 'Lost',
            Lead::STATUS_HANDED_OFF => 'Handed Off',
            Lead::STATUS_DO_NOT_CONTACT => 'Do Not Contact',
            Lead::STATUS_NEEDS_ATTENTION => 'Needs Attention',
            Lead::STATUS_CONVERTED => 'Converted',
            Lead::STATUS_CHURNED => 'Churned'
        ];
        
        // Get contact statistics
        $data['total_contacts'] = BusinessContact::where('user_id', Auth::id())->count();
        $data['whatsapp'] = true; // WhatsApp availability check
        
        // Get user's credit balance
        $data['credit_balance'] = Auth::user()->credits ?? 0;

        $data['products'] = Product::forUser(Auth::id())
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return view('campaigns.create', $data);
    }

    /**
     * Store campaign (delegates to Message controller)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Delegate to existing Message controller for now
        // This maintains compatibility with existing flow
        $messageController = new \App\Http\Controllers\Message();
        
        return $messageController->store($request);
    }

    /**
     * Show campaign analytics report
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function report($id)
    {
        $campaign = Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('analytics')
            ->firstOrFail();

        // Get analytics data
        $analytics = $campaign->analytics;

        // Get message queue entries for this campaign
        $messages = MessageQueue::where('campaign_id', $campaign->id)
            ->with(['contact', 'outgoingMessage'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Group messages by status for the report
        $messagesByStatus = [
            'sent' => $messages->where('status', MessageQueue::STATUS_SENT)->count(),
            'delivered' => 0,
            'read' => 0,
            'replied' => 0,
            'failed' => $messages->where('status', MessageQueue::STATUS_FAILED)->count(),
            'scheduled' => $messages->where('status', MessageQueue::STATUS_SCHEDULED)->count(),
            'analyzing' => $messages->where('status', MessageQueue::STATUS_ANALYZING)->count(),
        ];

        // Count delivered/read/replied from outgoing messages
        $outgoingMessages = OutgoingMessage::where('campaign_id', $campaign->id)->get();
        $messagesByStatus['delivered'] = $outgoingMessages->where('status', 'delivered')->count() 
            + $outgoingMessages->where('status', 'read')->count();
        $messagesByStatus['read'] = $outgoingMessages->where('status', 'read')->count();
        $messagesByStatus['replied'] = $outgoingMessages->where('reply_received', true)->count();

        // Calculate timeline data (messages over time)
        $timelineData = $this->getTimelineData($campaign->id);

        // Get top performing messages (highest engagement)
        $topMessages = OutgoingMessage::where('campaign_id', $campaign->id)
            ->whereNotNull('read_at')
            ->take(5)
            ->get();

        // Calculate ROI if revenue data is available
        $totalCost = $analytics ? $analytics->credits_spent : 0;
        $roi = null; // Placeholder for future revenue tracking integration

        // Get sentiment breakdown
        $sentimentBreakdown = [
            'positive' => $analytics ? $analytics->reply_sentiment_positive : 0,
            'neutral' => $analytics ? $analytics->reply_sentiment_neutral : 0,
            'negative' => $analytics ? $analytics->reply_sentiment_negative : 0,
        ];

        return view('campaigns.report', compact(
            'campaign',
            'analytics',
            'messages',
            'messagesByStatus',
            'timelineData',
            'topMessages',
            'totalCost',
            'roi',
            'sentimentBreakdown'
        ));
    }

    /**
     * Pause a campaign
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pause($id)
    {
        $campaign = Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $campaign->update(['status' => Campaign::STATUS_PAUSED]);

        return redirect()->back()->with('success', 'Campaign paused successfully.');
    }

    /**
     * Resume a paused campaign
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function resume($id)
    {
        $campaign = Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Determine the appropriate status to resume to
        $newStatus = Campaign::STATUS_SENDING;
        if ($campaign->scheduled_count > 0) {
            $newStatus = Campaign::STATUS_SCHEDULED;
        }

        $campaign->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Campaign resumed successfully.');
    }

    /**
     * Clone a campaign
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function clone($id)
    {
        $campaign = Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Create a new campaign based on the original
        $newCampaign = Campaign::create([
            'user_id' => $campaign->user_id,
            'business_id' => $campaign->business_id,
            'campaign_name' => $campaign->campaign_name . ' (Copy)',
            'campaign_type' => $campaign->campaign_type,
            'original_message' => $campaign->original_message,
            'recipient_criteria' => $campaign->recipient_criteria,
            'total_recipients' => 0, // Will be set when campaign is sent
            'queued_count' => 0,
            'status' => Campaign::STATUS_STAGING,
            'has_attachments' => $campaign->has_attachments,
        ]);

        return redirect()->route('campaigns.create')
            ->with('success', 'Campaign cloned successfully. You can now customize and send it.')
            ->with('cloned_campaign', $newCampaign);
    }

    /**
     * Delete a campaign
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $campaign = Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Only allow deletion of completed or failed campaigns
        if (!in_array($campaign->status, [Campaign::STATUS_COMPLETED, 'failed', Campaign::STATUS_STAGING])) {
            return redirect()->back()->with('error', 'Cannot delete an active campaign. Please pause it first.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Get status color for badge display
     *
     * @param string $status
     * @return string
     */
    private function getStatusColor($status)
    {
        $colors = [
            Campaign::STATUS_STAGING => 'secondary',
            Campaign::STATUS_SCHEDULED => 'info',
            Campaign::STATUS_SENDING => 'primary',
            Campaign::STATUS_PAUSED => 'warning',
            Campaign::STATUS_COMPLETED => 'success',
            'failed' => 'danger',
        ];

        return $colors[$status] ?? 'secondary';
    }

    /**
     * Get status icon
     *
     * @param string $status
     * @return string
     */
    private function getStatusIcon($status)
    {
        $icons = [
            Campaign::STATUS_STAGING => 'fas fa-pencil-alt',
            Campaign::STATUS_SCHEDULED => 'fas fa-clock',
            Campaign::STATUS_SENDING => 'fas fa-paper-plane',
            Campaign::STATUS_PAUSED => 'fas fa-pause-circle',
            Campaign::STATUS_COMPLETED => 'fas fa-check-circle',
            'failed' => 'fas fa-exclamation-circle',
        ];

        return $icons[$status] ?? 'fas fa-circle';
    }

    /**
     * Get timeline data for campaign (messages sent over time)
     *
     * @param int $campaignId
     * @return array
     */
    private function getTimelineData($campaignId)
    {
        $sentMessages = OutgoingMessage::where('campaign_id', $campaignId)
            ->whereNotNull('sent_at')
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $sentMessages->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'data' => $sentMessages->pluck('count')->toArray(),
        ];
    }
}
