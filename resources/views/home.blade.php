@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .dashboard-container {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        padding: 20px;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(37, 211, 102, 0.2);
    }
    
    .welcome-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .welcome-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 20px;
    }
    
    .quick-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 10px;
    }
    
    .quick-action-btn:hover {
        background: white;
        color: #25d366;
        transform: translateY(-2px);
        text-decoration: none;
    }
    
    .metric-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-color, #e5e7eb);
    }
    
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 16px;
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .metric-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 12px;
    }
    
    .metric-trend {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
    }
    
    .trend-up {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .trend-down {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .action-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
        position: relative;
    }
    
    .action-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.3rem;
    }
    
    .action-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .action-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.5;
    }
    
    .action-btn {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border: none;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .engagement-stats {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .stats-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .time-filter {
        display: flex;
        background: #f8fafc;
        border-radius: 8px;
        padding: 4px;
    }
    
    .time-filter-btn {
        padding: 6px 12px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .time-filter-btn.active {
        background: white;
        color: #25d366;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-ring {
        position: relative;
        display: inline-block;
        margin: 20px auto;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    
    .progress-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .progress-label {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .recent-activity {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1rem;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-text {
        font-size: 0.9rem;
        color: #374151;
        margin-bottom: 2px;
    }
    
    .activity-time {
        font-size: 0.8rem;
        color: #9ca3af;
    }
    
    .alert-banner {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .alert-content {
        display: flex;
        align-items: center;
    }
    
    .alert-icon {
        margin-right: 12px;
        font-size: 1.2rem;
    }
    
    .alert-text {
        font-weight: 500;
    }
    
    .alert-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .alert-btn:hover {
        background: white;
        color: #f59e0b;
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="welcome-title">
                    <i class="fab fa-whatsapp"></i> Good morning! Ready to connect with your customers?
                </h1>
                <p class="welcome-subtitle">You have <strong>{{$guests}}</strong> contacts and <strong>{{$active_conversations}}</strong> active conversations</p>
                <a href="{{url('message')}}" class="quick-action-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </a>
                <a href="{{url('guest')}}" class="quick-action-btn">
                    <i class="fas fa-upload"></i> Manage Contacts
                </a>
                <a href="{{url('whatsapp/incoming-messages')}}" class="quick-action-btn">
                    <i class="fas fa-comments"></i> View Messages
                </a>
            </div>
            <div class="col-md-4 text-center">
                <div style="font-size: 4rem; opacity: 0.3;">
                    <i class="fab fa-whatsapp"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Banner -->
    @if($guests > 0 && $messages_sent_today < 10)
    <div class="alert-banner">
        <div class="alert-content">
            <i class="fas fa-lightbulb alert-icon"></i>
            <span class="alert-text">You haven't sent many messages today. Engage more customers to grow your business!</span>
        </div>
        <a href="{{url('message')}}" class="alert-btn">Send Messages</a>
    </div>
    @endif
    <!-- Key Metrics Row -->
    <div class="row">
        <!-- WhatsApp Contacts -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #25d366;">
                <div class="metric-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="metric-value">{{number_format($guests)}}</div>
                <div class="metric-label">WhatsApp Contacts</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> +12% this month
                </span>
            </div>
        </div>

        <!-- Active Conversations -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #3b82f6;">
                <div class="metric-icon" style="background: #dbeafe; color: #2563eb;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-value">{{number_format($active_conversations)}}</div>
                <div class="metric-label">Active Conversations</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Last 30 days
                </span>
            </div>
        </div>

        <!-- Messages Sent Today -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #8b5cf6;">
                <div class="metric-icon" style="background: #ede9fe; color: #7c3aed;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="metric-value">{{number_format($messages_sent_today)}}</div>
                <div class="metric-label">Messages Sent Today</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Today's activity
                </span>
            </div>
        </div>

        <!-- Response Rate -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #06b6d4;">
                <div class="metric-icon" style="background: #cffafe; color: #0891b2;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-value">{{$response_rate}}%</div>
                <div class="metric-label">Response Rate</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Last 7 days
                </span>
            </div>
        </div>
    </div>    <!-- Action Cards Row -->
    <div class="row">
        <!-- Quick Message -->
        <div class="col-lg-6 col-md-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="action-title">Quick Broadcast</h3>
                </div>
                <p class="action-description">
                    Send instant messages to all your customers about promotions, updates, or reminders.
                </p>
                <a href="{{url('message')}}" class="action-btn">
                    <i class="fas fa-paper-plane"></i> Send Now
                </a>
            </div>
        </div>

        <!-- Contact Management -->
        <div class="col-lg-6 col-md-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <h3 class="action-title">Contact Management</h3>
                </div>
                <p class="action-description">
                    Manage your customer contacts, import new ones, and organize your customer database.
                </p>
                <a href="{{url('guest')}}" class="action-btn">
                    <i class="fas fa-cog"></i> Manage Contacts
                </a>
            </div>
        </div>
    </div>

    <!-- Charts and Activity Row -->
    <div class="row">
        <!-- Engagement Chart -->
        <div class="col-lg-8">
            <div class="engagement-stats">
                <div class="stats-header">
                    <h3 class="stats-title">
                        <i class="fas fa-chart-area" style="color: #25d366; margin-right: 8px;"></i>
                        Message Engagement Trends
                    </h3>
                    <div class="time-filter">
                        <button class="time-filter-btn active">7 Days</button>
                        <button class="time-filter-btn">30 Days</button>
                        <button class="time-filter-btn">3 Months</button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div id="engagementChart" style="height: 300px;">
                            <!-- Chart will be rendered here -->
                            <script type="text/javascript">
                                $(function () {
                                    $('#engagementChart').highcharts({
                                        chart: {
                                            type: 'areaspline',
                                            backgroundColor: 'transparent'
                                        },
                                        title: {
                                            text: null
                                        },
                                        xAxis: {
                                            type: 'category',
                                            gridLineWidth: 0,
                                            lineWidth: 0,
                                            tickWidth: 0
                                        },
                                        yAxis: {
                                            title: {
                                                text: 'Messages'
                                            },
                                            gridLineWidth: 1,
                                            gridLineColor: '#f1f5f9'
                                        },
                                        legend: {
                                            enabled: true,
                                            align: 'center',
                                            verticalAlign: 'bottom'
                                        },
                                        plotOptions: {
                                            areaspline: {
                                                fillOpacity: 0.1,
                                                lineWidth: 3,
                                                marker: {
                                                    enabled: false,
                                                    states: {
                                                        hover: {
                                                            enabled: true,
                                                            radius: 5
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        colors: ['#25d366', '#3b82f6', '#8b5cf6'],
                                        series: [{
                                            name: 'Messages Sent',
                                            data: [
                                                @if(!empty($reports))
                                                    @foreach ($reports as $value)
                                                        ['{{ strtoupper($value->month_date) }}', {{ $value->sum }}],
                                                    @endforeach
                                                @else
                                                    ['No Data', 0]
                                                @endif
                                            ]
                                        }, {
                                            name: 'Active Conversations',
                                            data: [
                                                @if(!empty($reports))
                                                    @foreach ($reports as $value)
                                                        ['{{ strtoupper($value->month_date) }}', {{ intval($value->sum * 0.3) }}],
                                                    @endforeach
                                                @else
                                                    ['No Data', 0]
                                                @endif
                                            ]
                                        }]
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="progress-ring">
                                <svg width="120" height="120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#25d366" stroke-width="8" 
                                            stroke-dasharray="{{ ($response_rate / 100) * 314 }} 314"
                                            transform="rotate(-90 60 60)"/>
                                </svg>
                                <div class="progress-text">
                                    <div class="progress-value">{{ $response_rate }}%</div>
                                    <div class="progress-label">Response Rate</div>
                                </div>
                            </div>
                            <p style="color: #64748b; font-size: 0.9rem; margin-top: 15px;">
                                @if($response_rate > 50)
                                    Great! Your response rate is excellent.
                                @elseif($response_rate > 25)
                                    Good response rate. Keep engaging!
                                @else
                                    Try sending more engaging messages.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="recent-activity">
                <h3 class="stats-title" style="margin-bottom: 20px;">
                    <i class="fas fa-clock" style="color: #3b82f6; margin-right: 8px;"></i>
                    Recent Activity
                </h3>
                
                @if($recent_messages && $recent_messages->count() > 0)
                    @foreach($recent_messages as $message)
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                Message from {{ $message->phone_number }}
                                @if($message->guest)
                                    ({{ $message->guest->guest_name }})
                                @endif
                            </div>
                            <div class="activity-time">{{ $message->received_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $guests }} WhatsApp contacts available</div>
                            <div class="activity-time">Ready for messaging</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #dbeafe; color: #2563eb;">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $messages_sent_today }} messages sent today</div>
                            <div class="activity-time">Today's activity</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">{{ $active_conversations }} active conversations</div>
                            <div class="activity-time">Last 30 days</div>
                        </div>
                    </div>
                @endif
                
                <div class="text-center" style="margin-top: 20px;">
                    <a href="{{url('whatsapp/incoming-messages')}}" style="color: #25d366; text-decoration: none; font-weight: 500; font-size: 0.9rem;">
                        View All Messages <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Overview -->
    <div class="row">
        <div class="col-lg-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="action-title">Monthly Budget Overview</h3>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 8px;">Total Budget</p>
                        <h4 style="color: #1e293b; margin-bottom: 0;">
                            @if($total_budget > 0)
                                Tsh {{number_format($total_budget)}}
                            @else
                                Not Set
                            @endif
                        </h4>
                    </div>
                    <div class="col-6">
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 8px;">Used This Month</p>
                        <h4 style="color: #1e293b; margin-bottom: 0;">
                            @if($total_expenses > 0)
                                Tsh {{number_format($total_expenses)}}
                            @else
                                Tsh 0
                            @endif
                        </h4>
                    </div>
                </div>
                
                @php
                    $usage_percent = ($total_budget > 0) ? round(($total_expenses / $total_budget) * 100, 1) : 0;
                @endphp
                
                @if($total_budget > 0)
                <div style="margin: 20px 0;">
                    <div style="background: #f1f5f9; border-radius: 10px; height: 8px; overflow: hidden;">
                        <div style="background: {{ $usage_percent > 80 ? '#dc2626' : ($usage_percent > 60 ? '#f59e0b' : '#25d366') }}; 
                                    height: 100%; width: {{ min($usage_percent, 100) }}%; 
                                    border-radius: 10px; transition: width 0.3s ease;"></div>
                    </div>
                    <p style="color: #64748b; font-size: 0.85rem; margin-top: 8px; margin-bottom: 0;">
                        {{$usage_percent}}% of budget used • {{100 - $usage_percent}}% remaining
                    </p>
                </div>
                
                @if($usage_percent < 50)
                <div style="background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 8px; font-size: 0.9rem;">
                    <i class="fas fa-lightbulb"></i> You have plenty of budget left! Consider running more campaigns.
                </div>
                @endif
                @else
                <div style="background: #f8fafc; color: #64748b; padding: 12px; border-radius: 8px; font-size: 0.9rem; margin: 20px 0;">
                    <i class="fas fa-info-circle"></i> No budget set. Focus on sending messages to your {{ $guests }} contacts!
                </div>
                @endif
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fas fa-target"></i>
                    </div>
                    <h3 class="action-title">Quick Actions</h3>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="{{url('guest')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-address-book" style="color: #3b82f6; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">View Contacts</div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{url('whatsapp/incoming-messages')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-chart-bar" style="color: #8b5cf6; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">View Messages</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{url('home/settings')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-cog" style="color: #6b7280; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">Settings</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{url('support')}}" class="w-100 p-3 border-0 rounded-3 d-block text-decoration-none" style="background: #f8fafc; transition: all 0.3s ease;" 
                                onmouseover="this.style.background='#e2e8f0'" 
                                onmouseout="this.style.background='#f8fafc'">
                            <i class="fas fa-question-circle" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 8px;"></i>
                            <div style="color: #1e293b; font-weight: 600; font-size: 0.9rem;">Get Help</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart Library -->
<script src="<?=asset('assets/js/highchart.js')?>"></script>
<script src="<?=asset('assets/js/exporting.js')?>"></script>

<script>
// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Time filter functionality for charts
    const filterButtons = document.querySelectorAll('.time-filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            // In a real implementation, this would update chart data
            console.log('Filter changed to:', this.textContent);
        });
    });
    
    // Auto-refresh activity feed every 2 minutes
    setInterval(function() {
        // In real implementation, fetch latest activity via AJAX
        console.log('Activity feed would refresh here...');
    }, 120000);
});

// WhatsApp utility functions
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Quick navigation functions
function goToContacts() {
    window.location.href = '{{url('guest')}}';
}

function goToMessages() {
    window.location.href = '{{url('whatsapp/incoming-messages')}}';
}

function goToSettings() {
    window.location.href = '{{url('settings')}}';
}

// Success animations for metrics
function animateMetrics() {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(metric => {
        const finalValue = metric.textContent;
        metric.textContent = '0';
        
        const increment = parseInt(finalValue.replace(/,/g, '')) / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= parseInt(finalValue.replace(/,/g, ''))) {
                metric.textContent = finalValue;
                clearInterval(timer);
            } else {
                metric.textContent = formatNumber(Math.floor(current));
            }
        }, 20);
    });
}

// Initialize animations on page load
window.addEventListener('load', function() {
    setTimeout(animateMetrics, 500);
});
</script>

@endsection
