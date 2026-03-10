@extends('layouts.app')

@section('content')
<style>
/* Appointments Page Styling */
.page-title-box {
    padding: 1.5rem 0;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.page-title-box h4.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
}

.page-title-box .text-muted {
    color: #718096 !important;
    font-size: 1rem;
    margin-top: 0.5rem;
}

/* Enhanced Tab Styling */
.nav-tabs-custom {
    background: #ffffff;
    border-radius: 12px;
    padding: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem !important;
}

.nav-tabs-custom .nav-item {
    margin: 0 0.25rem;
}

.nav-tabs-custom .nav-link {
    border: 2px solid transparent;
    border-radius: 8px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    color: #4a5568;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    background: transparent;
    position: relative;
}

.nav-tabs-custom .nav-link i {
    font-size: 1.1rem;
    margin-right: 0.5rem;
}

.nav-tabs-custom .nav-link:hover {
    background: #f7fafc;
    color: #2d3748;
    border-color: #cbd5e0;
}

.nav-tabs-custom .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff !important;
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.nav-tabs-custom .nav-link.active i {
    color: #ffffff;
}

.nav-tabs-custom .badge {
    margin-left: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.nav-tabs-custom .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

/* Stats Cards Enhancement */
.card.shadow-sm {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s ease;
    background: #ffffff;
}

.card.shadow-sm:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
}

.card.shadow-sm .card-body {
    padding: 1.5rem;
}

.card.shadow-sm h6.text-muted {
    color: #718096 !important;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem !important;
}

.card.shadow-sm h3 {
    font-weight: 700;
    font-size: 2rem;
}

.card.shadow-sm .opacity-50 {
    opacity: 0.3 !important;
}

/* Filter Card */
.card-body label.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.card-header.bg-white {
    background: #f7fafc !important;
    border-bottom: 2px solid #e2e8f0;
    padding: 1.25rem;
}

.card-header h5 {
    font-weight: 700;
    color: #2d3748;
}

/* Table Styling */
.table-standard {
    width: 100%;
    margin-bottom: 0;
}

.table-standard thead.bg-light {
    background: #f7fafc !important;
}

.table-standard thead th {
    font-weight: 700;
    color: #2d3748;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.table-standard tbody td {
    padding: 1rem;
    vertical-align: middle;
    color: #4a5568;
    border-bottom: 1px solid #e2e8f0;
}

.table-standard tbody tr:hover {
    background: #f7fafc;
}

/* Badge Enhancements */
.badge-soft-primary {
    background: #e6f2ff;
    color: #0066cc;
    padding: 0.375rem 0.75rem;
    font-weight: 600;
    border-radius: 6px;
}

/* Dark Mode Styles */
.dark-mode .page-title-box {
    border-bottom-color: #4a5568;
}

.dark-mode .page-title-box h4.page-title {
    color: #f7fafc;
}

.dark-mode .page-title-box .text-muted {
    color: #cbd5e0 !important;
}

/* Dark Mode Tabs */
.dark-mode .nav-tabs-custom {
    background: #2d3748;
    border-color: #4a5568;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}

.dark-mode .nav-tabs-custom .nav-link {
    color: #cbd5e0;
    background: transparent;
}

.dark-mode .nav-tabs-custom .nav-link:hover {
    background: #4a5568;
    color: #f7fafc;
    border-color: #718096;
}

.dark-mode .nav-tabs-custom .nav-link.active {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    color: #ffffff !important;
    border-color: #5a67d8;
    box-shadow: 0 4px 16px rgba(90, 103, 216, 0.4);
}

.dark-mode .nav-tabs-custom .nav-link.active i {
    color: #ffffff;
}

.dark-mode .nav-tabs-custom .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

/* Override Bootstrap bg classes in dark mode */
.dark-mode .bg-white {
    background-color: #2d3748 !important;
}

.dark-mode .bg-light {
    background-color: #1a202c !important;
}

.dark-mode .card-header.bg-white,
.dark-mode .card-footer.bg-white {
    background-color: #1a202c !important;
    color: #f7fafc !important;
    border-color: #4a5568 !important;
}

.dark-mode thead.bg-light {
    background-color: #1a202c !important;
}

.dark-mode thead.bg-light th {
    color: #f7fafc !important;
}

/* Dark Mode Cards */
.dark-mode .card.shadow-sm {
    background: #2d3748 !important;
    border-color: #4a5568;
}

.dark-mode .card.shadow-sm .card-body {
    background: #2d3748 !important;
}

.dark-mode .card.shadow-sm:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.4) !important;
    border-color: #718096;
}

.dark-mode .card.shadow-sm h6.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .card.shadow-sm h3 {
    color: #ffffff !important;
}

.dark-mode .card.shadow-sm h3.text-primary {
    color: #63b3ed !important;
}

.dark-mode .card.shadow-sm h3.text-warning {
    color: #fbbf24 !important;
}

.dark-mode .card.shadow-sm h3.text-success {
    color: #48bb78 !important;
}

.dark-mode .card.shadow-sm h3.text-danger {
    color: #f56565 !important;
}

.dark-mode .card.shadow-sm .text-primary {
    color: #63b3ed !important;
}

.dark-mode .card.shadow-sm i.text-primary {
    color: #63b3ed !important;
}

.dark-mode .card.shadow-sm .text-warning {
    color: #fbbf24 !important;
}

.dark-mode .card.shadow-sm i.text-warning {
    color: #fbbf24 !important;
}

.dark-mode .card.shadow-sm .text-success {
    color: #48bb78 !important;
}

.dark-mode .card.shadow-sm i.text-success {
    color: #48bb78 !important;
}

.dark-mode .card.shadow-sm .text-danger {
    color: #f56565 !important;
}

.dark-mode .card.shadow-sm i.text-danger {
    color: #f56565 !important;
}

.dark-mode .card.shadow-sm .opacity-50 {
    opacity: 0.4 !important;
}

/* Dark Mode Filter Card */
.dark-mode .card-body label.form-label {
    color: #cbd5e0;
}

.dark-mode .form-control {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .form-control:focus {
    background: #4a5568;
    border-color: #90cdf4;
    color: #f7fafc;
    box-shadow: 0 0 0 0.2rem rgba(144, 205, 244, 0.25);
}

.dark-mode .form-control option {
    background: #2d3748;
    color: #f7fafc;
}

.dark-mode .card-header.bg-white {
    background: #1a202c !important;
    border-bottom-color: #4a5568;
}

.dark-mode .card-header h5 {
    color: #f7fafc;
}

.dark-mode .badge-primary {
    background: #5a67d8 !important;
    color: #ffffff !important;
}

.dark-mode .card {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
}

.dark-mode .card-body {
    background: #2d3748 !important;
    color: #e2e8f0;
}

.dark-mode .table-responsive {
    background-color: #2d3748 !important;
}

.dark-mode .card .table-responsive {
    background-color: transparent !important;
}

.dark-mode .table,
.dark-mode table {
    color: #e2e8f0 !important;
    background-color: transparent;
}

.dark-mode .table thead th,
.dark-mode table thead th {
    background-color: #1a202c !important;
    color: #f7fafc !important;
    border-color: #4a5568 !important;
}

.dark-mode .table tbody,
.dark-mode table tbody {
    background-color: #2d3748 !important;
}

.dark-mode .table tbody tr,
.dark-mode table tbody tr {
    background-color: #2d3748 !important;
}

.dark-mode .table tbody td,
.dark-mode table tbody td {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
    border-color: #4a5568 !important;
}

.dark-mode .table tbody tr:hover,
.dark-mode table tbody tr:hover {
    background-color: #374151 !important;
}

.dark-mode .table tbody tr:hover td,
.dark-mode table tbody tr:hover td {
    background-color: #374151 !important;
}

/* Dark Mode Text Elements */
.dark-mode .table td strong,
.dark-mode table td strong {
    color: #ffffff !important;
}

.dark-mode .table td div,
.dark-mode table td div {
    color: #f7fafc !important;
}

.dark-mode .table td .text-muted,
.dark-mode table td .text-muted,
.dark-mode .table td small.text-muted,
.dark-mode table td small.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .table-standard tbody tr:hover,
.dark-mode .table-standard tbody tr:hover td {
    background: #374151 !important;
}

/* Dark Mode Table */
.dark-mode .table-responsive {
    background: #2d3748;
    border-radius: 8px;
}

.dark-mode .table-standard {
    margin-bottom: 0;
    background: transparent;
}

.dark-mode .table-standard thead.bg-light {
    background: #1a202c !important;
}

.dark-mode .table-standard thead th {
    color: #f7fafc;
    border-bottom-color: #4a5568;
    font-weight: 700;
}

.dark-mode .table-standard tbody {
    background: #2d3748 !important;
}

.dark-mode .table-standard tbody tr {
    background: #2d3748 !important;
}

.dark-mode .table-standard tbody td {
    color: #e2e8f0 !important;
    border-bottom-color: #4a5568 !important;
    background: #2d3748 !important;
}

.dark-mode .table-standard tbody tr:hover {
    background: #374151 !important;
}

.dark-mode .table-standard tbody tr:hover td {
    color: #f7fafc !important;
    background: #374151 !important;
}

.dark-mode .table-standard tbody td strong {
    color: #ffffff !important;
    font-weight: 700;
}

.dark-mode .table-standard tbody td div {
    color: #f7fafc !important;
}

.dark-mode .table-standard tbody td .text-muted {
    color: #cbd5e0 !important;
    font-weight: 500;
}

.dark-mode .table-standard tbody td small {
    color: #cbd5e0 !important;
}

.dark-mode .table-standard tbody td small.text-muted {
    color: #cbd5e0 !important;
}

.dark-mode .badge-soft-primary {
    background: rgba(99, 179, 237, 0.25);
    color: #90cdf4;
    font-weight: 600;
    border: 1px solid rgba(99, 179, 237, 0.3);
}

.dark-mode .badge-warning {
    background: #fbbf24 !important;
    color: #1a202c !important;
    font-weight: 700;
    border: 1px solid #f59e0b;
}

.dark-mode .badge-info {
    background: #63b3ed !important;
    color: #1a202c !important;
    font-weight: 700;
    border: 1px solid #4299e1;
}

.dark-mode .badge-success {
    background: #48bb78 !important;
    color: #ffffff !important;
    font-weight: 700;
    border: 1px solid #38a169;
}

.dark-mode .badge-secondary {
    background: #718096 !important;
    color: #ffffff !important;
    font-weight: 700;
    border: 1px solid #4a5568;
}

.dark-mode .badge-danger {
    background: #f56565 !important;
    color: #ffffff !important;
    font-weight: 600;
}

/* Dark Mode Table Icons */
.dark-mode .table-standard tbody td i.fa-clock,
.dark-mode .table-standard tbody td i.far.fa-clock {
    color: #cbd5e0;
}

.dark-mode .table-standard tbody td i.fa-check-circle {
    color: #48bb78 !important;
}

.dark-mode .table-standard tbody td i.fa-exclamation-triangle {
    color: #fbbf24 !important;
}

.dark-mode .table-standard tbody td i.text-success {
    color: #48bb78 !important;
}

.dark-mode .table-standard tbody td i.text-warning {
    color: #fbbf24 !important;
}

.dark-mode .table-standard tbody td i.text-danger {
    color: #f56565 !important;
}

/* Dark Mode Buttons */
.dark-mode .btn-outline-primary {
    border-color: #5a67d8;
    color: #90cdf4;
}

.dark-mode .btn-outline-primary:hover {
    background: #5a67d8;
    color: #ffffff;
}

.dark-mode .btn-outline-success {
    border-color: #48bb78;
    color: #68d391;
}

.dark-mode .btn-outline-success:hover {
    background: #48bb78;
    color: #ffffff;
}

.dark-mode .btn-outline-danger {
    border-color: #f56565;
    color: #fc8181;
}

.dark-mode .btn-outline-danger:hover {
    background: #f56565;
    color: #ffffff;
}

/* Dark Mode Alert */
.dark-mode .alert-success {
    background: rgba(72, 187, 120, 0.15);
    border-color: #48bb78;
    color: #9ae6b4;
}

.dark-mode .alert-success i {
    color: #68d391;
}

.dark-mode .alert-danger {
    background: rgba(245, 101, 101, 0.15);
    border-color: #f56565;
    color: #fc8181;
}

.dark-mode .alert-danger i {
    color: #fc8181;
}

.dark-mode .alert .close {
    color: #f7fafc;
    opacity: 0.8;
}

.dark-mode .alert .close:hover {
    opacity: 1;
}

/* Dark Mode Empty State */
.dark-mode .text-center .text-muted {
    color: #9ca3af !important;
}

.dark-mode .text-center .fa-calendar-times {
    color: #718096 !important;
}

.dark-mode .card-footer.bg-white {
    background: #1a202c !important;
    border-top-color: #4a5568;
}

.dark-mode .pagination .page-link {
    background: #2d3748;
    border-color: #4a5568;
    color: #cbd5e0;
}

.dark-mode .pagination .page-link:hover {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .pagination .page-item.active .page-link {
    background: #5a67d8;
    border-color: #5a67d8;
    color: #ffffff;
}

.dark-mode .pagination .page-item.disabled .page-link {
    background: #1a202c;
    border-color: #4a5568;
    color: #718096;
}

/* Primary Button Styling */
.btn-primary,
a.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 8px;
    color: #ffffff !important;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover,
a.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    color: #ffffff !important;
}

.dark-mode .btn-primary,
.dark-mode a.btn-primary {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    box-shadow: 0 4px 12px rgba(90, 103, 216, 0.4);
}

.dark-mode .btn-primary:hover,
.dark-mode a.btn-primary:hover {
    box-shadow: 0 6px 16px rgba(90, 103, 216, 0.5);
}

/* Secondary Button */
.btn-secondary {
    background: #e2e8f0;
    border: 1px solid #cbd5e0;
    color: #4a5568;
    font-weight: 600;
}

.btn-secondary:hover {
    background: #cbd5e0;
    color: #2d3748;
}

.dark-mode .btn-secondary {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
}

.dark-mode .btn-secondary:hover {
    background: #718096;
    color: #ffffff;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .nav-tabs-custom {
        padding: 0.25rem;
    }
    
    .nav-tabs-custom .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    .card.shadow-sm .card-body {
        padding: 1rem;
    }
    
    .table-standard thead th,
    .table-standard tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">
                    <i class="fas fa-calendar-check text-success mr-2"></i>{{ __("appointments.page_title") }}
                </h4>
                <p class="text-muted">{{ __("appointments.page_subtitle") }}</p>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="appointmentTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="appointments-tab" href="#appointmentsContent" role="tab">
                <i class="fas fa-list mr-2"></i>{{ __("appointments.tabs.appointments") }}
                @if(isset($stats['pending']) && $stats['pending'] > 0)
                <span class="badge badge-danger ml-1">{{ $stats['pending'] }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="calendars-tab" href="{{ route('booking-calendars.index') }}" role="tab">
                <i class="fas fa-calendar-alt mr-2"></i>{{ __("appointments.tabs.booking_calendars") }}
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div id="appointmentsContent">
        @include('appointments._appointments_list')
    </div>
</div>

@include('appointments._modals')

@push('scripts')
<script>
function confirmAppointment(id) {
    const form = document.getElementById('confirmForm');
    form.action = '{{ url('/appointments') }}/' + id + '/confirm';
    $('#confirmModal').modal('show');
}

function cancelAppointment(id) {
    const form = document.getElementById('cancelForm');
    form.action = '{{ url('/appointments') }}/' + id + '/cancel';
    $('#cancelModal').modal('show');
}
</script>
@endpush
@endsection
