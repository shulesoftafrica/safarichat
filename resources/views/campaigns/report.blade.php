@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .report-container {
        font-family: 'Inter', sans-serif;
        background: var(--gray-50);
        min-height: 100vh;
        padding: 20px;
    }
    
    .report-header {
        background: var(--primary-color);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
    }
    
    .report-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .report-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .back-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .metric-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 16px;
    }
    
    .metric-icon.sent { background: #dbeafe; color: #1e40af; }
    .metric-icon.delivered { background: #d1fae5; color: #065f46; }
    .metric-icon.read { background: #e0e7ff; color: #4f46e5; }
    .metric-icon.replied { background: #fef3c7; color: #92400e; }
    .metric-icon.failed { background: #fee2e2; color: #991b1b; }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #374151;
        margin-bottom: 4px;
    }
    
    .metric-label {
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .metric-percentage {
        color: #10b981;
        font-size: 0.875rem;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
    }
    
    .chart-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 20px;
    }
    
    .messages-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .messages-table thead th {
        background: #f8fafb;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: #6b7280;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .messages-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
    }
    
    .messages-table tbody td {
        padding: 16px;
        vertical-align: middle;
    }
    
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .status-dot.sent { background: #3b82f6; }
    .status-dot.delivered { background: #10b981; }
    .status-dot.read { background: #6366f1; }
    .status-dot.failed { background: #ef4444; }
    
    .action-link {
        color: #667eea;
        font-weight: 500;
        text-decoration: none;
    }
    
    .action-link:hover {
        text-decoration: underline;
    }
    
    .sentiment-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .sentiment-badge.positive {
        background: #d1fae5;
        color: #065f46;
    }
    
    .sentiment-badge.neutral {
        background: #e5e7eb;
        color: #374151;
    }
    
    .sentiment-badge.negative {
        background: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="report-container">
    <!-- Header -->
    <div class="report-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="report-title">
                    {{ $campaign->campaign_name }}
                </div>
                <div class="report-subtitle">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ __('campaigns.report.created') }} {{ $campaign->created_at->format('M d, Y') }} {{ __('campaigns.report.at') }} {{ $campaign->created_at->format('g:i A') }}
                    @if($campaign->completed_at)
                    &nbsp;•&nbsp; {{ __('campaigns.report.completed') }} {{ $campaign->completed_at->format('M d, Y') }}
                    @endif
                </div>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('campaigns.index') }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('campaigns.report.back_to_campaigns') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Metrics Overview -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon sent">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="metric-value">{{ $campaign->sent_count }}</div>
            <div class="metric-label">{{ __('campaigns.report.messages_sent') }}</div>
            @if($campaign->total_recipients > 0)
            <div class="metric-percentage">
                {{ number_format(($campaign->sent_count / $campaign->total_recipients) * 100, 1) }}% {{ __('campaigns.report.of_total') }}
            </div>
            @endif
        </div>

        <div class="metric-card">
            <div class="metric-icon delivered">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="metric-value">{{ $analytics ? $analytics->total_delivered : 0 }}</div>
            <div class="metric-label">{{ __('campaigns.report.delivered') }}</div>
            @if($analytics && $analytics->delivery_rate > 0)
            <div class="metric-percentage">
                {{ number_format($analytics->delivery_rate, 1) }}% {{ __('campaigns.report.delivery_rate') }}
            </div>
            @endif
        </div>

        <div class="metric-card">
            <div class="metric-icon read">
                <i class="fas fa-eye"></i>
            </div>
            <div class="metric-value">{{ $analytics ? $analytics->total_read : 0 }}</div>
            <div class="metric-label">{{ __('campaigns.report.read') }}</div>
            @if($analytics && $analytics->read_rate > 0)
            <div class="metric-percentage">
                {{ number_format($analytics->read_rate, 1) }}% {{ __('campaigns.report.read_rate') }}
            </div>
            @endif
        </div>

        <div class="metric-card">
            <div class="metric-icon replied">
                <i class="fas fa-reply"></i>
            </div>
            <div class="metric-value">{{ $analytics ? $analytics->total_replied : 0 }}</div>
            <div class="metric-label">{{ __('campaigns.report.replied') }}</div>
            @if($analytics && $analytics->reply_rate > 0)
            <div class="metric-percentage">
                {{ number_format($analytics->reply_rate, 1) }}% {{ __('campaigns.report.reply_rate') }}
            </div>
            @endif
        </div>

        <div class="metric-card">
            <div class="metric-icon failed">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="metric-value">{{ $campaign->failed_count }}</div>
            <div class="metric-label">{{ __('campaigns.report.failed') }}</div>
            @if($campaign->total_recipients > 0)
            <div class="metric-percentage" style="color: #ef4444;">
                {{ number_format(($campaign->failed_count / $campaign->total_recipients) * 100, 1) }}% {{ __('campaigns.report.failure_rate') }}
            </div>
            @endif
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: #fef3c7; color: #92400e;">
                <i class="fas fa-coins"></i>
            </div>
            <div class="metric-value">{{ $totalCost }}</div>
            <div class="metric-label">{{ __('campaigns.report.credits_spent') }}</div>
            @if($campaign->total_recipients > 0)
            <div class="metric-percentage" style="color: #6b7280;">
                {{ number_format($totalCost / $campaign->total_recipients, 1) }} {{ __('campaigns.report.per_message') }}
            </div>
            @endif
        </div>
    </div>

    <!-- Sentiment Analysis -->
    @if($analytics && ($sentimentBreakdown['positive'] + $sentimentBreakdown['neutral'] + $sentimentBreakdown['negative']) > 0)
    <div class="chart-card">
        <h3 class="chart-title">
            <i class="fas fa-smile mr-2"></i>
            {{ __('campaigns.report.reply_sentiment_analysis') }}
        </h3>
        <div class="row">
            <div class="col-md-4">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 2.5rem; color: #10b981;">😊</div>
                    <div style="font-size: 2rem; font-weight: 700; color: #065f46; margin-top: 12px;">
                        {{ $sentimentBreakdown['positive'] }}
                    </div>
                    <div style="color: #6b7280; font-size: 0.875rem; font-weight: 500;">
                        {{ __('campaigns.report.positive_replies') }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 2.5rem; color: #6b7280;">😐</div>
                    <div style="font-size: 2rem; font-weight: 700; color: #374151; margin-top: 12px;">
                        {{ $sentimentBreakdown['neutral'] }}
                    </div>
                    <div style="color: #6b7280; font-size: 0.875rem; font-weight: 500;">
                        {{ __('campaigns.report.neutral_replies') }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 2.5rem; color: #ef4444;">😟</div>
                    <div style="font-size: 2rem; font-weight: 700; color: #991b1b; margin-top: 12px;">
                        {{ $sentimentBreakdown['negative'] }}
                    </div>
                    <div style="color: #6b7280; font-size: 0.875rem; font-weight: 500;">
                        {{ __('campaigns.report.negative_replies') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Original Message -->
    <div class="chart-card">
        <h3 class="chart-title">
            <i class="fas fa-envelope mr-2"></i>
            {{ __('campaigns.report.original_message') }}
        </h3>
        <div style="background: #f9fafb; border-left: 4px solid #667eea; padding: 20px; border-radius: 8px;">
            <div style="white-space: pre-wrap; color: #374151; font-size: 1rem; line-height: 1.6;">{{ $campaign->original_message }}</div>
        </div>
    </div>

    <!-- Message Details Table -->
    <div class="chart-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="chart-title mb-0">
                <i class="fas fa-list mr-2"></i>
                {{ __('campaigns.report.message_recipients') }} ({{ $messages->count() }})
            </h3>
            <div>
                <select class="filter-select" id="messageStatusFilter" onchange="filterMessages()" style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 8px 16px;">
                    <option value="">{{ __('campaigns.report.all_statuses') }}</option>
                    <option value="sent">{{ __('campaigns.status.sending') }}</option>
                    <option value="delivered">{{ __('campaigns.report.delivered') }}</option>
                    <option value="read">{{ __('campaigns.report.read') }}</option>
                    <option value="failed">{{ __('campaigns.report.failed') }}</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="messages-table">
                <thead>
                    <tr>
                        <th>{{ __('campaigns.report.contact') }}</th>
                        <th>{{ __('campaigns.report.phone') }}</th>
                        <th>{{ __('campaigns.report.status') }}</th>
                        <th>{{ __('campaigns.report.sent_at') }}</th>
                        <th>{{ __('campaigns.report.delivered_at') }}</th>
                        <th>{{ __('campaigns.report.read_at') }}</th>
                        <th>{{ __('campaigns.report.reply') }}</th>
                        <th>{{ __('campaigns.report.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                    <tr data-status="{{ $message->outgoingMessage->status ?? $message->status }}">
                        <td>
                            <strong>{{ $message->contact_name ?? __('campaigns.report.unknown') }}</strong>
                        </td>
                        <td>
                            <span style="color: #6b7280;">{{ $message->phone_number }}</span>
                        </td>
                        <td>
                            @if($message->outgoingMessage)
                            <span class="status-dot {{ $message->outgoingMessage->status }}"></span>
                            <span style="text-transform: capitalize;">{{ $message->outgoingMessage->status }}</span>
                            @else
                            <span class="status-dot {{ $message->status }}"></span>
                            <span style="text-transform: capitalize;">{{ $message->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($message->outgoingMessage && $message->outgoingMessage->sent_at)
                            <span style="color: #374151;">{{ $message->outgoingMessage->sent_at->format('M d, g:i A') }}</span>
                            @else
                            <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($message->outgoingMessage && $message->outgoingMessage->delivered_at)
                            <span style="color: #374151;">{{ $message->outgoingMessage->delivered_at->format('M d, g:i A') }}</span>
                            @else
                            <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($message->outgoingMessage && $message->outgoingMessage->read_at)
                            <span style="color: #374151;">{{ $message->outgoingMessage->read_at->format('M d, g:i A') }}</span>
                            @else
                            <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($message->outgoingMessage && $message->outgoingMessage->reply_received)
                            <span class="sentiment-badge positive" title="{{ $message->outgoingMessage->reply_message }}">
                                <i class="fas fa-comment-dots"></i>
                                {{ __('campaigns.report.replied_badge') }}
                            </span>
                            @else
                            <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($message->contact_id)
                            <a href="{{ url('guest/view/' . $message->contact_id) }}" class="action-link" title="{{ __('campaigns.report.view_contact') }}">
                                <i class="fas fa-user"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 16px;"></i>
                            <div>{{ __('campaigns.report.no_messages_found') }}</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Campaign Actions -->
    <div class="chart-card">
        <h3 class="chart-title">
            <i class="fas fa-cog mr-2"></i>
            {{ __('campaigns.report.campaign_actions') }}
        </h3>
        <div class="row">
            <div class="col-md-4">
                <form action="{{ route('campaigns.clone', $campaign->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block" style="border-radius: 12px; padding: 14px;">
                        <i class="fas fa-copy mr-2"></i>
                        {{ __('campaigns.report.clone_campaign') }}
                    </button>
                </form>
            </div>
            @if($campaign->status === 'sending' || $campaign->status === 'scheduled')
            <div class="col-md-4">
                <form action="{{ route('campaigns.pause', $campaign->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary btn-block" style="border-radius: 12px; padding: 14px;">
                        <i class="fas fa-pause mr-2"></i>
                        {{ __('campaigns.report.pause_campaign') }}
                    </button>
                </form>
            </div>
            @elseif($campaign->status === 'paused')
            <div class="col-md-4">
                <form action="{{ route('campaigns.resume', $campaign->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary btn-block" style="border-radius: 12px; padding: 14px;">
                        <i class="fas fa-play mr-2"></i>
                        {{ __('campaigns.report.resume_campaign') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    function filterMessages() {
        const filterValue = document.getElementById('messageStatusFilter').value;
        const rows = document.querySelectorAll('.messages-table tbody tr');
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (filterValue === '' || status === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

@endsection
