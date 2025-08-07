@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .reports-container {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        padding: 20px;
    }
    
    .reports-header {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(37, 211, 102, 0.2);
    }
    
    .reports-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .reports-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 20px;
    }
    
    .period-selector {
        display: flex;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 4px;
        gap: 4px;
    }
    
    .period-btn {
        background: transparent;
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        opacity: 0.7;
    }
    
    .period-btn.active {
        background: white;
        color: #25d366;
        opacity: 1;
    }
    
    .metric-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .trend-up {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .trend-down {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .trend-neutral {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .chart-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .chart-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .engagement-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .engagement-item {
        text-align: center;
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .engagement-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .engagement-label {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .insights-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #bae6fd;
        margin-bottom: 20px;
    }
    
    .insights-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #0c4a6e;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .insight-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        padding: 12px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .insight-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .insight-content {
        flex: 1;
    }
    
    .insight-text {
        font-size: 0.9rem;
        color: #374151;
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .insight-desc {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .performance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .performance-item {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }
    
    .performance-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 1.2rem;
    }
    
    .performance-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .performance-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 8px;
    }
    
    .performance-change {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
    }
    
    .comparison-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .comparison-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .comparison-item:last-child {
        border-bottom: none;
    }
    
    .comparison-label {
        font-weight: 500;
        color: #374151;
    }
    
    .comparison-value {
        font-weight: 700;
        color: #1e293b;
    }
    
    .export-btn {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        color: white;
    }
    
    @media (max-width: 768px) {
        .reports-container {
            padding: 10px;
        }
        
        .reports-header {
            padding: 20px;
        }
        
        .reports-title {
            font-size: 1.5rem;
        }
        
        .period-selector {
            flex-wrap: wrap;
        }
        
        .engagement-metrics {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .performance-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="reports-container">
    <!-- Reports Header -->
    <div class="reports-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="reports-title">
                    <i class="fas fa-chart-line"></i>
                    WhatsApp Business Analytics
                </h1>
                <p class="reports-subtitle">Track your customer engagement and business growth through WhatsApp messaging</p>
                
                <div class="period-selector">
                    <button class="period-btn" onclick="changePeriod('today')">Today</button>
                    <button class="period-btn active" onclick="changePeriod('week')">This Week</button>
                    <button class="period-btn" onclick="changePeriod('month')">This Month</button>
                    <button class="period-btn" onclick="changePeriod('quarter')">Quarter</button>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
        </div>
    </div>
    <!-- Key Performance Metrics -->
    <div class="row">
        <!-- Messages Sent -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #25d366;">
                <div class="metric-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="metric-value">{{ number_format($whatsapp_sent) }}</div>
                <div class="metric-label">WhatsApp Messages Sent</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> 
                    @if($messages_sent_week > 0)
                        {{ $messages_sent_week }} this week
                    @else
                        Total messages
                    @endif
                </span>
            </div>
        </div>

        <!-- Customer Responses -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #3b82f6;">
                <div class="metric-icon" style="background: #dbeafe; color: #2563eb;">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="metric-value">{{ number_format($whatsapp_received) }}</div>
                <div class="metric-label">Customer Responses</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> 
                    {{ $response_rate }}% response rate
                </span>
            </div>
        </div>

        <!-- Active Conversations -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #8b5cf6;">
                <div class="metric-icon" style="background: #ede9fe; color: #7c3aed;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-value">{{ number_format($active_conversations) }}</div>
                <div class="metric-label">Active Conversations</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Last 30 days
                </span>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card" style="--card-color: #f59e0b;">
                <div class="metric-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-value">
                    @if($whatsapp_sent > 0)
                        {{ number_format(($successful_messages / max($whatsapp_sent, 1)) * 100, 1) }}%
                    @else
                        0%
                    @endif
                </div>
                <div class="metric-label">Message Success Rate</div>
                <span class="metric-trend trend-up">
                    <i class="fas fa-arrow-up"></i> Delivery success
                </span>
            </div>
        </div>
    </div>

    <!-- Business Impact Insights -->
    <div class="row">
        <div class="col-lg-8">
            <div class="insights-card">
                <h3 class="insights-title">
                    <i class="fas fa-lightbulb"></i>
                    Business Impact Insights
                </h3>
                
                <div class="insight-item">
                    <div class="insight-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-text">
                            @if($active_conversations > 0)
                                {{ $active_conversations }} active customer conversations this month
                            @else
                                Ready to start engaging customers via WhatsApp
                            @endif
                        </div>
                        <div class="insight-desc">
                            @if($response_rate > 50)
                                Excellent response rate of {{ $response_rate }}% shows customers love WhatsApp communication
                            @elseif($response_rate > 25)
                                Good {{ $response_rate }}% response rate - customers are engaging with your messages
                            @else
                                WhatsApp typically gets 10x better response rates than email marketing
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="insight-item">
                    <div class="insight-icon" style="background: #dbeafe; color: #2563eb;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-text">
                            @if($messages_sent_today > 0)
                                {{ $messages_sent_today }} messages sent today
                            @else
                                Ready to send instant messages to customers
                            @endif
                        </div>
                        <div class="insight-desc">WhatsApp messages are typically read within 3 minutes vs 6+ hours for email</div>
                    </div>
                </div>
                
                <div class="insight-item">
                    <div class="insight-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-text">
                            Estimated messaging cost: TSh {{ number_format($total_messaging_cost) }}
                        </div>
                        <div class="insight-desc">
                            @if($roi_percentage > 100)
                                Excellent ROI of {{ $roi_percentage }}%! WhatsApp is generating strong returns
                            @elseif($roi_percentage > 0)
                                Positive ROI of {{ $roi_percentage }}% - your WhatsApp investment is paying off
                            @else
                                WhatsApp typically costs 75% less than traditional advertising per customer reached
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="insight-item">
                    <div class="insight-icon" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-text">
                            {{ number_format($total_contacts) }} total contacts ready for messaging
                        </div>
                        <div class="insight-desc">
                            @if($contacts_messaged > 0)
                                You've reached {{ number_format($contacts_messaged) }} unique customers via WhatsApp
                            @else
                                Start engaging your contacts to build stronger customer relationships
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="comparison-card">
                <h3 class="chart-title">
                    <i class="fas fa-balance-scale"></i>
                    WhatsApp vs Traditional Channels
                </h3>
                
                <div class="comparison-item">
                    <span class="comparison-label">Read Rate</span>
                    <span class="comparison-value" style="color: #16a34a;">98% vs 20%</span>
                </div>
                
                <div class="comparison-item">
                    <span class="comparison-label">Response Rate</span>
                    <span class="comparison-value" style="color: #16a34a;">{{ $response_rate }}% vs 2%</span>
                </div>
                
                <div class="comparison-item">
                    <span class="comparison-label">Cost per Message</span>
                    <span class="comparison-value" style="color: #16a34a;">TSh 50 vs TSh 200</span>
                </div>
                
                <div class="comparison-item">
                    <span class="comparison-label">Delivery Speed</span>
                    <span class="comparison-value" style="color: #16a34a;">Instant vs 24hrs</span>
                </div>
                
                <div class="comparison-item">
                    <span class="comparison-label">Customer Preference</span>
                    <span class="comparison-value" style="color: #16a34a;">{{ number_format($customer_satisfaction, 1) }}/5 vs 2.8/5</span>
                </div>
                
                @if($roi_percentage > 0)
                <div style="background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 8px; margin-top: 16px; text-align: center;">
                    <strong>ROI: {{ $roi_percentage }}%</strong><br>
                    <small>Your WhatsApp investment is generating excellent returns!</small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance Metrics Grid -->
    <div class="row">
        <div class="col-lg-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-area"></i>
                        Customer Engagement Performance
                    </h3>
                </div>
                
                <div class="performance-grid">
                    <div class="performance-item">
                        <div class="performance-icon" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="performance-value">
                            @if($whatsapp_sent > 0)
                                {{ number_format(($successful_messages / max($whatsapp_sent, 1)) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="performance-label">Message Success Rate</div>
                        <span class="performance-change trend-up">Delivery success</span>
                    </div>
                    
                    <div class="performance-item">
                        <div class="performance-icon" style="background: #dbeafe; color: #2563eb;">
                            <i class="fas fa-reply"></i>
                        </div>
                        <div class="performance-value">{{ $response_rate }}%</div>
                        <div class="performance-label">Customer Response Rate</div>
                        <span class="performance-change trend-up">
                            @if($response_rate > 50)
                                Excellent!
                            @elseif($response_rate > 25)
                                Good
                            @else
                                Growing
                            @endif
                        </span>
                    </div>
                    
                    <div class="performance-item">
                        <div class="performance-icon" style="background: #ede9fe; color: #7c3aed;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="performance-value">{{ number_format($auto_replies_sent) }}</div>
                        <div class="performance-label">Auto-Replies Sent</div>
                        <span class="performance-change trend-up">Automated</span>
                    </div>
                    
                    <div class="performance-item">
                        <div class="performance-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="performance-value">{{ number_format($contacts_messaged) }}</div>
                        <div class="performance-label">Customers Reached</div>
                        <span class="performance-change trend-up">
                            @if($total_contacts > 0)
                                {{ number_format(($contacts_messaged / $total_contacts) * 100, 1) }}% of total
                            @else
                                Ready to start
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="engagement-metrics">
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($whatsapp_sent + $sms_sent) }}</div>
                        <div class="engagement-label">Total Messages</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($successful_messages) }}</div>
                        <div class="engagement-label">Successfully Delivered</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($whatsapp_received) }}</div>
                        <div class="engagement-label">Customer Responses</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($estimated_leads) }}</div>
                        <div class="engagement-label">Estimated Leads</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($connected_instances) }}</div>
                        <div class="engagement-label">Active WhatsApp Instances</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($text_messages) }}</div>
                        <div class="engagement-label">Text Messages</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">{{ number_format($media_messages) }}</div>
                        <div class="engagement-label">Media Messages</div>
                    </div>
                    <div class="engagement-item">
                        <div class="engagement-value">TSh {{ number_format($total_messaging_cost) }}</div>
                        <div class="engagement-label">Total Messaging Cost</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Trends Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Message Engagement Trends
                    </h3>
                </div>
                
                <div id="engagementChart" style="height: 400px;">
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
                                    tickWidth: 0,
                                    labels: {
                                        style: {
                                            color: '#64748b',
                                            fontSize: '12px'
                                        }
                                    }
                                },
                                yAxis: {
                                    title: {
                                        text: 'Messages',
                                        style: {
                                            color: '#64748b'
                                        }
                                    },
                                    gridLineWidth: 1,
                                    gridLineColor: '#f1f5f9',
                                    labels: {
                                        style: {
                                            color: '#64748b'
                                        }
                                    }
                                },
                                legend: {
                                    enabled: true,
                                    align: 'center',
                                    verticalAlign: 'bottom',
                                    itemStyle: {
                                        color: '#374151'
                                    }
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
                                colors: ['#25d366', '#3b82f6', '#8b5cf6', '#f59e0b'],
                                series: [{
                                    name: 'WhatsApp Messages',
                                    data: [
                                        @if(!empty($reports))
                                            @foreach ($reports as $value)
                                                @if(isset($value->month_name))
                                                    ['{{ strtoupper($value->month_name) }}', {{ $value->count }}],
                                                @else
                                                    ['{{ $value->month }}/{{ $value->year }}', {{ $value->count }}],
                                                @endif
                                            @endforeach
                                        @else
                                            ['No Data', 0]
                                        @endif
                                    ]
                                }, {
                                    name: 'Customer Responses',
                                    data: [
                                        @if(!empty($reports))
                                            @foreach ($reports as $value)
                                                @if(isset($value->month_name))
                                                    ['{{ strtoupper($value->month_name) }}', {{ ceil($value->count * ($response_rate / 100)) }}],
                                                @else
                                                    ['{{ $value->month }}/{{ $value->year }}', {{ ceil($value->count * ($response_rate / 100)) }}],
                                                @endif
                                            @endforeach
                                        @else
                                            ['No Data', 0]
                                        @endif
                                    ]
                                }],
                                tooltip: {
                                    shared: true,
                                    valueSuffix: ' messages',
                                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                    borderColor: '#e2e8f0',
                                    borderRadius: 8,
                                    style: {
                                        color: '#374151'
                                    }
                                }
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Recommendations -->
    <div class="row">
        <div class="col-lg-12">
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-rocket"></i>
                    Growth Recommendations
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                            <h4 style="color: #1e293b; margin-bottom: 12px;">📈 Immediate Actions</h4>
                            
                            @if($whatsapp_sent == 0)
                                <p>• Start sending WhatsApp messages to your {{ number_format($total_contacts) }} contacts</p>
                                <p>• Set up automated welcome messages for new customers</p>
                            @elseif($response_rate < 25)
                                <p>• Improve message content to increase your {{ $response_rate }}% response rate</p>
                                <p>• Send more personalized messages using customer names</p>
                            @else
                                <p>• Your {{ $response_rate }}% response rate is excellent! Keep engaging</p>
                                <p>• Consider expanding to more customer segments</p>
                            @endif
                            
                            @if($auto_replies_sent == 0)
                                <p>• Set up auto-replies to handle customer inquiries 24/7</p>
                            @endif
                            
                            @if($media_messages == 0)
                                <p>• Try sending images and documents for better engagement</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                            <h4 style="color: #0c4a6e; margin-bottom: 12px;">💡 Growth Opportunities</h4>
                            
                            @if($roi_percentage > 100)
                                <p>• Your excellent {{ $roi_percentage }}% ROI shows WhatsApp is very profitable</p>
                                <p>• Consider increasing your messaging budget for more growth</p>
                            @elseif($roi_percentage > 0)
                                <p>• Your {{ $roi_percentage }}% ROI is positive - scale up messaging</p>
                            @else
                                <p>• Start measuring conversions to track your ROI</p>
                            @endif
                            
                            @if($active_conversations > 0)
                                <p>• {{ number_format($active_conversations) }} customers are engaged - nurture these relationships</p>
                            @endif
                            
                            <p>• WhatsApp customers typically spend 3x more than email customers</p>
                            <p>• Consider offering exclusive WhatsApp-only promotions</p>
                        </div>
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #dcfce7 0%, #ecfdf5 100%); padding: 20px; border-radius: 12px; text-align: center;">
                    <h4 style="color: #16a34a; margin-bottom: 8px;">🎯 Your WhatsApp Success Score</h4>
                    @php
                        $success_score = 0;
                        if($whatsapp_sent > 0) $success_score += 20;
                        if($response_rate > 25) $success_score += 20;
                        if($active_conversations > 0) $success_score += 20;
                        if($auto_replies_sent > 0) $success_score += 15;
                        if($media_messages > 0) $success_score += 10;
                        if($connected_instances > 0) $success_score += 15;
                    @endphp
                    <div style="font-size: 2rem; font-weight: 700; color: #16a34a; margin-bottom: 8px;">
                        {{ $success_score }}/100
                    </div>
                    <p style="color: #16a34a; margin-bottom: 0;">
                        @if($success_score >= 80)
                            Excellent! You're maximizing WhatsApp's potential
                        @elseif($success_score >= 60)
                            Great progress! A few more optimizations will boost your results
                        @elseif($success_score >= 40)
                            Good start! Focus on engagement and automation
                        @else
                            Ready to unlock WhatsApp's full potential for your business
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include required scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<script>
// Period selector functionality
function changePeriod(period) {
    document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show loading state
    const metrics = document.querySelectorAll('.metric-value');
    metrics.forEach(metric => {
        metric.style.opacity = '0.5';
    });
    
    // In real implementation, this would make an AJAX call to fetch filtered data
    setTimeout(() => {
        metrics.forEach(metric => {
            metric.style.opacity = '1';
        });
        console.log('Data refreshed for period:', period);
    }, 500);
}

// Export functionality
function exportReport() {
    // Generate comprehensive business report summary
    const reportData = {
        messages_sent: {{ $whatsapp_sent }},
        responses_received: {{ $whatsapp_received }},
        response_rate: {{ $response_rate }},
        active_conversations: {{ $active_conversations }},
        total_cost: {{ $total_messaging_cost }},
        estimated_revenue: {{ $estimated_total_revenue }},
        roi_percentage: {{ $roi_percentage }},
        success_score: {{ $success_score ?? 0 }}
    };
    
    // Create downloadable content
    const reportContent = `
WhatsApp Business Analytics Report
Generated: ${new Date().toLocaleDateString()}

PERFORMANCE SUMMARY:
• Messages Sent: ${reportData.messages_sent.toLocaleString()}
• Customer Responses: ${reportData.responses_received.toLocaleString()}
• Response Rate: ${reportData.response_rate}%
• Active Conversations: ${reportData.active_conversations.toLocaleString()}

BUSINESS IMPACT:
• Total Messaging Cost: TSh ${reportData.total_cost.toLocaleString()}
• Estimated Revenue: TSh ${reportData.estimated_revenue.toLocaleString()}
• ROI: ${reportData.roi_percentage}%
• Success Score: ${reportData.success_score}/100

RECOMMENDATIONS:
${reportData.response_rate < 25 ? '• Focus on improving message content and personalization' : '• Excellent response rate - consider scaling up'}
${reportData.roi_percentage > 100 ? '• Strong ROI - increase messaging budget for growth' : '• Track conversions to measure ROI better'}
• WhatsApp provides 10x better engagement than email
• Continue building customer relationships through consistent messaging

Report generated by SafariChat WhatsApp Business Platform
`;

    // Create and download file
    const blob = new Blob([reportContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `whatsapp-business-report-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    // Show success message
    alert('✅ Report exported successfully!\n\nYour comprehensive WhatsApp business analytics report has been downloaded.');
}

// Auto-refresh data every 2 minutes for real-time updates
setInterval(function() {
    // In real implementation, fetch latest data via AJAX
    const indicators = document.querySelectorAll('.metric-trend');
    indicators.forEach(indicator => {
        indicator.style.opacity = '0.7';
        setTimeout(() => {
            indicator.style.opacity = '1';
        }, 1000);
    });
    console.log('Analytics data refreshed...');
}, 120000);

// Initialize dashboard interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth animations to metric cards
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add click handlers for interactive elements
    const performanceItems = document.querySelectorAll('.performance-item');
    performanceItems.forEach(item => {
        item.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    console.log('WhatsApp Business Analytics Dashboard initialized successfully');
    
    // Show welcome message for first-time users
    @if($whatsapp_sent == 0)
        setTimeout(() => {
            if(confirm('Welcome to SafariChat WhatsApp Business Analytics!\n\nWould you like to start sending your first messages to engage customers?')) {
                window.location.href = "{{url('/guest')}}";
            }
        }, 2000);
    @endif
});

// Utility functions for data formatting
function formatCurrency(amount) {
    return 'TSh ' + new Intl.NumberFormat().format(amount);
}

function formatPercentage(value) {
    return value.toFixed(1) + '%';
}

// Success celebration for high performers
@if($response_rate > 50 && $whatsapp_sent > 100)
    setTimeout(() => {
        const celebration = document.createElement('div');
        celebration.innerHTML = '🎉 Congratulations! Your WhatsApp engagement is exceptional!';
        celebration.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dcfce7; color: #16a34a; padding: 15px 20px; border-radius: 8px; font-weight: 600; z-index: 1000; animation: slideIn 0.5s ease;';
        document.body.appendChild(celebration);
        setTimeout(() => {
            celebration.remove();
        }, 5000);
    }, 3000);
@endif
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

@endsection