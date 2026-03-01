@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .campaigns-container {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .campaigns-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
    }
    
    .campaigns-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .campaigns-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .create-campaign-btn {
        background: white;
        color: #667eea;
        border: none;
        padding: 14px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .create-campaign-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        color: #764ba2;
        text-decoration: none;
    }
    
    .campaigns-table-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .table-header-bar {
        padding: 24px 32px;
        border-bottom: 2px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }
    
    .filter-controls {
        display: flex;
        gap: 12px;
    }
    
    .filter-select {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 0.9rem;
        color: #374151;
        background: white;
        transition: all 0.3s ease;
    }
    
    .filter-select:focus {
        border-color: #667eea;
        outline: none;
    }
    
    .campaigns-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .campaigns-table thead th {
        background: #f8fafb;
        padding: 18px 24px;
        text-align: left;
        font-weight: 600;
        color: #6b7280;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .campaigns-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }
    
    .campaigns-table tbody tr:hover {
        background: #fafbfc;
    }
    
    .campaigns-table tbody td {
        padding: 20px 24px;
        vertical-align: middle;
    }
    
    .campaign-name {
        font-weight: 600;
        color: #374151;
        font-size: 1rem;
        margin-bottom: 4px;
    }
    
    .campaign-date {
        color: #9ca3af;
        font-size: 0.875rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-badge.status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.status-sending {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-badge.status-scheduled {
        background: #e0e7ff;
        color: #4f46e5;
    }
    
    .status-badge.status-paused {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-badge.status-failed {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-badge.status-staging {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .progress-bar-container {
        width: 100%;
        height: 6px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
    }
    
    .progress-text {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .metric-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        margin-right: 8px;
    }
    
    .metric-badge.success {
        background: #d1fae5;
        color: #065f46;
    }
    
    .metric-badge.info {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .action-btn {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        margin-right: 6px;
    }
    
    .action-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .action-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .action-btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }
    
    .action-btn-secondary:hover {
        background: #d1d5db;
        color: #374151;
    }
    
    .action-btn-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .action-btn-danger:hover {
        background: #fecaca;
        color: #7f1d1d;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 12px;
    }
    
    .empty-state-text {
        color: #6b7280;
        margin-bottom: 24px;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        padding: 24px;
        gap: 8px;
    }
    
    .pagination .page-link {
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        color: #374151;
        padding: 8px 16px;
    }
    
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
    }
</style>

<div class="campaigns-container">
    <!-- Header Section -->
    <div class="campaigns-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="campaigns-title">
                    <i class="fas fa-bullhorn"></i>
                    Sales Campaigns
                </div>
                <p class="campaigns-subtitle">
                    Manage and track your WhatsApp marketing campaigns in one place
                </p>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('campaigns.create') }}" class="create-campaign-btn">
                    <i class="fas fa-plus-circle"></i>
                    Create New Campaign
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid #10b981;">
        <i class="fas fa-check-circle mr-2"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid #ef4444;">
        <i class="fas fa-exclamation-circle mr-2"></i>
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <!-- Campaigns Table -->
    <div class="campaigns-table-card">
        <div class="table-header-bar">
            <h3 class="table-title">
                <i class="fas fa-list mr-2"></i>
                Your Campaigns ({{ $campaigns->total() }})
            </h3>
            <div class="filter-controls">
                <select class="filter-select" id="statusFilter" onchange="filterCampaigns()">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="sending">Active</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="paused">Paused</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        @if($campaigns->count() > 0)
        <table class="campaigns-table">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Recipients</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Metrics</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr data-status="{{ $campaign->status }}">
                    <td>
                        <div class="campaign-name">
                            {{ $campaign->campaign_name }}
                        </div>
                        <div class="campaign-date">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            {{ $campaign->created_at->format('M d, Y') }} at {{ $campaign->created_at->format('g:i A') }}
                        </div>
                    </td>
                    <td>
                        <strong style="font-size: 1.25rem; color: #374151;">{{ $campaign->total_recipients }}</strong>
                        <br>
                        <span style="font-size: 0.875rem; color: #9ca3af;">contacts</span>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $campaign->status }}">
                            <i class="{{ $campaign->status_icon }}"></i>
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: {{ $campaign->progress_percentage }}%"></div>
                        </div>
                        <div class="progress-text">
                            {{ $campaign->sent_count + $campaign->failed_count }}/{{ $campaign->total_recipients }} sent
                            ({{ $campaign->progress_percentage }}%)
                        </div>
                    </td>
                    <td>
                        @if($campaign->analytics)
                        <span class="metric-badge success" title="Read Rate">
                            <i class="fas fa-eye"></i>
                            {{ number_format($campaign->analytics->read_rate, 1) }}%
                        </span>
                        <span class="metric-badge info" title="Reply Rate">
                            <i class="fas fa-reply"></i>
                            {{ number_format($campaign->analytics->reply_rate, 1) }}%
                        </span>
                        @else
                        <span style="color: #9ca3af; font-size: 0.875rem;">No data yet</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('campaigns.report', $campaign->id) }}" class="action-btn action-btn-primary" title="View Report">
                            <i class="fas fa-chart-line"></i> Report
                        </a>
                        
                        @if($campaign->status === 'sending' || $campaign->status === 'scheduled')
                        <form action="{{ route('campaigns.pause', $campaign->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="action-btn action-btn-secondary" title="Pause Campaign">
                                <i class="fas fa-pause"></i>
                            </button>
                        </form>
                        @elseif($campaign->status === 'paused')
                        <form action="{{ route('campaigns.resume', $campaign->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="action-btn action-btn-secondary" title="Resume Campaign">
                                <i class="fas fa-play"></i>
                            </button>
                        </form>
                        @endif
                        
                        <form action="{{ route('campaigns.clone', $campaign->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="action-btn action-btn-secondary" title="Clone Campaign">
                                <i class="fas fa-copy"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            {{ $campaigns->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3 class="empty-state-title">No Campaigns Yet</h3>
            <p class="empty-state-text">
                Create your first sales campaign to start reaching customers via WhatsApp
            </p>
            <a href="{{ route('campaigns.create') }}" class="create-campaign-btn">
                <i class="fas fa-plus-circle"></i>
                Create Your First Campaign
            </a>
        </div>
        @endif
    </div>
</div>

<script>
    function filterCampaigns() {
        const filterValue = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('.campaigns-table tbody tr');
        
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
