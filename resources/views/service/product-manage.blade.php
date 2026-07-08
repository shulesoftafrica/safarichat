@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Manage Product</h2>
            <p class="text-muted mb-0">
                <strong>{{ $product->name }}</strong> summary and operations metrics
            </p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Products
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Key Summary Metrics</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Prospects</div>
                        <div class="h3 mb-0">{{ $metrics['prospects'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Leads</div>
                        <div class="h3 mb-0">{{ $metrics['leads'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Qualified Leads</div>
                        <div class="h3 mb-0">{{ $metrics['qualified_leads'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Customers</div>
                        <div class="h3 mb-0">{{ $metrics['customers'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Sales Leader Snapshot</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pipeline Total</div>
                        <div class="h4 mb-0">{{ $salesLeader['pipeline_total'] }}</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Win Rate</div>
                        <div class="h4 mb-0">{{ $salesLeader['win_rate_percent'] }}%</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Qualified Rate</div>
                        <div class="h4 mb-0">{{ $salesLeader['qualified_rate_percent'] }}%</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Appt Coverage</div>
                        <div class="h4 mb-0">{{ $salesLeader['appointment_coverage_percent'] }}%</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">No-show Rate</div>
                        <div class="h4 mb-0">{{ $salesLeader['no_show_rate_percent'] }}%</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Msg to Appt</div>
                        <div class="h4 mb-0">{{ $salesLeader['message_to_appointment_percent'] }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Other Lead Metrics</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total Churned</div>
                        <div class="h4 mb-0">{{ $metrics['total_churned'] }}</div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total Lost</div>
                        <div class="h4 mb-0">{{ $metrics['total_lost'] }}</div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Do Not Contact</div>
                        <div class="h4 mb-0">{{ $metrics['do_not_contact'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Operations Metrics</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total Messages Sent</div>
                        <div class="h3 mb-0">{{ $operations['total_messages_sent'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Prospecting Messages</div>
                        <div class="h3 mb-0">{{ $operations['prospecting_messages'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Lead Messages</div>
                        <div class="h3 mb-0">{{ $operations['lead_messages'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Qualified Leads Messages</div>
                        <div class="h3 mb-0">{{ $operations['qualified_lead_messages'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">New Contacts Registered Per Month ({{ $currentYear }})</h5>
                </div>
                <div class="card-body">
                    <div id="newContactsMonthlyChart" style="height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Contacts Engagement Per Month ({{ $currentYear }})</h5>
                </div>
                <div class="card-body">
                    <div id="engagementMonthlyChart" style="height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const labels = @json($monthlyLabels);
        const monthlyNewContacts = @json($monthlyNewContacts);
        const monthlyEngagement = @json($monthlyEngagementContacts);

        Highcharts.chart('newContactsMonthlyChart', {
            chart: { type: 'column', backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: labels, crosshair: true },
            yAxis: {
                min: 0,
                title: { text: 'Contacts' },
                allowDecimals: false
            },
            legend: { enabled: false },
            series: [{
                name: 'New Contacts',
                data: monthlyNewContacts,
                color: '#4f7cff'
            }]
        });

        Highcharts.chart('engagementMonthlyChart', {
            chart: { type: 'line', backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: labels },
            yAxis: {
                min: 0,
                title: { text: 'Engaged Contacts' },
                allowDecimals: false
            },
            legend: { enabled: false },
            series: [{
                name: 'Engagement',
                data: monthlyEngagement,
                color: '#22a06b'
            }]
        });
    });
</script>
@endsection
