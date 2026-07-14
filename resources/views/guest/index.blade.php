@extends('layouts.app')
@section('content')
<style>
.file-upload-area {
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.file-upload-area.border-primary {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
}

.file-preview-item {
    transition: all 0.2s ease;
}

.file-preview-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

.bulk-actions-bar {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Lead Status Badge Styles */
.badge-orange { 
    background-color: #fd7e14; 
    color: white;
    font-weight: 600;
}
.badge-purple { 
    background-color: #6f42c1; 
    color: white;
    font-weight: 600;
}
.badge-teal { 
    background-color: #20c997; 
    color: white;
    font-weight: 600;
}
.badge-indigo { 
    background-color: #6610f2; 
    color: white;
    font-weight: 600;
}

/* International Telephone Input Styles */
.iti { width: 100%; }

/* Form Validation Feedback Improvements */
.valid-feedback,
.invalid-feedback {
    display: none;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.valid-feedback.d-block,
.invalid-feedback.d-block {
    display: block !important;
}

.valid-feedback {
    color: #059669;
}

.invalid-feedback {
    color: #dc2626;
}

.valid-feedback i,
.invalid-feedback i {
    margin-right: 0.25rem;
}

.form-control.is-valid {
    border-color: #10b981;
    background-image: none;
}

.form-control.is-invalid {
    border-color: #ef4444;
    background-image: none;
}

.form-control.is-valid:focus {
    border-color: #059669;
    box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
}

.form-control.is-invalid:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
}

/* Hide default Bootstrap validation icons */
.was-validated .form-control:valid,
.form-control.is-valid {
    background-image: none;
    padding-right: 0.75rem;
}

.was-validated .form-control:invalid,
.form-control.is-invalid {
    background-image: none;
    padding-right: 0.75rem;
}

/* International Tel Input Integration */
.iti__selected-flag {
    padding: 0 8px 0 12px;
}

.iti__country-list {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    max-height: 200px;
}

/* Modal Form Improvements */
.modal-content .form-group {
    margin-bottom: 1.5rem;
}

.modal-content .form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.modal-content .form-control {
    border-radius: 6px;
    border: 2px solid #e5e7eb;
    padding: 0.625rem 0.875rem;
    transition: all 0.2s ease;
}

.modal-content .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-content .form-text {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.375rem;
}

/* Dark Mode Styles */
.dark-mode .container-fluid {
    background-color: #1a1f2e !important;
    color: #e2e8f0 !important;
}

.dark-mode .card {
    background-color: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .card-body {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .card-header {
    background-color: #4a5568 !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode .table {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .table thead th {
    background-color: #4a5568 !important;
    border-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .table tbody td {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(odd) {
    background-color: #1e293b !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(odd) td {
    background-color: #1e293b !important;
    color: #f7fafc !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(even) td {
    background-color: #2d3748 !important;
    color: #f7fafc !important;
}

.dark-mode .table-hover tbody tr:hover {
    background-color: rgba(99, 179, 237, 0.15) !important;
}

.dark-mode .table-hover tbody tr:hover td {
    background-color: rgba(99, 179, 237, 0.15) !important;
    color: #ffffff !important;
}

/* Dark mode DataTables support */
.dark-mode .dataTable tbody tr,
.dark-mode .dataTable tbody tr.even,
.dark-mode table.dataTable tbody tr {
    background-color: #2d3748 !important;
}

.dark-mode .dataTable tbody tr.odd,
.dark-mode table.dataTable tbody tr.odd {
    background-color: #1e293b !important;
}

.dark-mode .dataTable tbody td,
.dark-mode table.dataTable tbody td {
    background-color: transparent !important;
    color: #f7fafc !important;
    border-color: #4a5568 !important;
}

.dark-mode .dataTable tbody tr:hover,
.dark-mode table.dataTable tbody tr:hover {
    background-color: rgba(99, 179, 237, 0.2) !important;
}

.dark-mode .dataTable tbody tr:hover td,
.dark-mode table.dataTable tbody tr:hover td {
    color: #ffffff !important;
}

.dark-mode .dataTable thead th,
.dark-mode table.dataTable thead th {
    background-color: #4a5568 !important;
    color: #f7fafc !important;
    border-color: #4a5568 !important;
}

.dark-mode .table-standard,
.dark-mode table.table-standard {
    background-color: #2d3748 !important;
    color: #f7fafc !important;
}

.dark-mode .table-standard tbody tr,
.dark-mode table.table-standard tbody tr {
    background-color: #2d3748 !important;
    color: #f7fafc !important;
}

.dark-mode .table-standard tbody tr.odd {
    background-color: #1e293b !important;
}

.dark-mode .table-standard tbody td,
.dark-mode table.table-standard tbody td {
    color: #f7fafc !important;
    background-color: transparent !important;
}

.dark-mode .form-control {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #e2e8f0 !important;
}

.dark-mode .form-control:focus {
    background-color: #4a5568 !important;
    border-color: #63b3ed !important;
    box-shadow: 0 0 0 0.2rem rgba(99, 179, 237, 0.25) !important;
    color: #e2e8f0 !important;
}

.dark-mode .form-select {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #e2e8f0 !important;
}

.dark-mode .form-select:focus {
    background-color: #4a5568 !important;
    border-color: #63b3ed !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-content {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-header {
    background-color: #4a5568 !important;
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode .modal-footer {
    background-color: #4a5568 !important;
    border-top: 1px solid #4a5568 !important;
}

.dark-mode .modal-body {
    background-color: #2d3748 !important;
}

.dark-mode .alert {
    border-color: #4a5568 !important;
}

.dark-mode .alert-info {
    background-color: rgba(99, 179, 237, 0.1) !important;
    border-color: #63b3ed !important;
    color: #bee3f8 !important;
}

.dark-mode .alert-success {
    background-color: rgba(72, 187, 120, 0.1) !important;
    border-color: #48bb78 !important;
    color: #c6f6d5 !important;
}

.dark-mode .alert-warning {
    background-color: rgba(237, 137, 54, 0.1) !important;
    border-color: #ed8936 !important;
    color: #faf089 !important;
}

.dark-mode .alert-danger {
    background-color: rgba(245, 101, 101, 0.1) !important;
    border-color: #f56565 !important;
    color: #fed7d7 !important;
}

.dark-mode .breadcrumb {
    background-color: #4a5568 !important;
}

.dark-mode .breadcrumb-item a {
    color: #63b3ed !important;
}

.dark-mode .breadcrumb-item.active {
    color: #e2e8f0 !important;
}

.dark-mode .nav-tabs .nav-link {
    background-color: #4a5568 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .nav-tabs .nav-link.active {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .nav-tabs {
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode .badge-light {
    background-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .text-muted {
    color: #a0aec0 !important;
}

.dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode h6 {
    color: #f7fafc !important;
}

.dark-mode p, .dark-mode span, .dark-mode div {
    color: #e2e8f0 !important;
}

/* Dark Mode Validation Feedback */
.dark-mode .valid-feedback {
    color: #34d399 !important;
}

.dark-mode .invalid-feedback {
    color: #f87171 !important;
}

.dark-mode .form-control.is-valid {
    border-color: #10b981 !important;
}

.dark-mode .form-control.is-invalid {
    border-color: #ef4444 !important;
}

.dark-mode .modal-content .form-label {
    color: #f7fafc !important;
}

.dark-mode .modal-content label,
.dark-mode .modal-content .col-form-label {
    color: #f7fafc !important;
}

.dark-mode .modal-content .form-text {
    color: #a0aec0 !important;
}

.dark-mode .page-title-box {
    color: #e2e8f0 !important;
}

.dark-mode .file-upload-area {
    background-color: #4a5568 !important;
    border-color: #718096 !important;
    color: #e2e8f0 !important;
}

.dark-mode .file-upload-area:hover {
    background-color: #2d3748 !important;
    border-color: #63b3ed !important;
}

.dark-mode .file-preview-item {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
}

.dark-mode .custom-control-label {
    color: #e2e8f0 !important;
}

.dark-mode .custom-control-label::before {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
}

.dark-mode .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #63b3ed !important;
    border-color: #63b3ed !important;
}

.dark-mode .btn-outline-success {
    color: #34d399 !important;
    border-color: #34d399 !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-success:hover {
    background-color: #10b981 !important;
    border-color: #34d399 !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(52, 211, 153, 0.4) !important;
}

.dark-mode .btn-success {
    background-color: #10b981 !important;
    border-color: #34d399 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-success:hover {
    background-color: #059669 !important;
    border-color: #10b981 !important;
    box-shadow: 0 0 12px rgba(16, 185, 129, 0.5) !important;
}

.dark-mode .btn-primary {
    background-color: #3b82f6 !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-primary:hover {
    background-color: #2563eb !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.5) !important;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa !important;
    border-color: #60a5fa !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-primary:hover {
    background-color: #3b82f6 !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(96, 165, 250, 0.4) !important;
}

.dark-mode .btn-outline-info {
    color: #60a5fa !important;
    border-color: #60a5fa !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-info:hover {
    background-color: #3b82f6 !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(96, 165, 250, 0.4) !important;
}

.dark-mode .btn-info {
    background-color: #3b82f6 !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-info:hover {
    background-color: #2563eb !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.5) !important;
}

.dark-mode .btn-warning {
    background-color: #f59e0b !important;
    border-color: #fbbf24 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-warning:hover {
    background-color: #d97706 !important;
    border-color: #f59e0b !important;
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.5) !important;
}

.dark-mode .btn-danger {
    background-color: #ef4444 !important;
    border-color: #f87171 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-danger:hover {
    background-color: #dc2626 !important;
    border-color: #ef4444 !important;
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.5) !important;
}

.dark-mode .btn-secondary {
    background-color: #6b7280 !important;
    border-color: #9ca3af !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .btn-secondary:hover {
    background-color: #4b5563 !important;
    border-color: #6b7280 !important;
    box-shadow: 0 0 12px rgba(107, 114, 128, 0.5) !important;
}

.dark-mode .btn-outline-secondary {
    color: #9ca3af !important;
    border-color: #9ca3af !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-secondary:hover {
    background-color: #6b7280 !important;
    border-color: #9ca3af !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(156, 163, 175, 0.4) !important;
}

.dark-mode .btn-outline-warning {
    color: #fbbf24 !important;
    border-color: #fbbf24 !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-warning:hover {
    background-color: #f59e0b !important;
    border-color: #fbbf24 !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(251, 191, 36, 0.4) !important;
}

.dark-mode .btn-outline-danger {
    color: #f87171 !important;
    border-color: #f87171 !important;
    background-color: transparent !important;
    font-weight: 600 !important;
}

.dark-mode .btn-outline-danger:hover {
    background-color: #ef4444 !important;
    border-color: #f87171 !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(248, 113, 113, 0.4) !important;
}

.dark-mode .close {
    color: #e2e8f0 !important;
    opacity: 1 !important;
}

.dark-mode .close:hover {
    color: #f7fafc !important;
}

.dark-mode .collapse .card-body {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
}

.dark-mode .border {
    border-color: #4a5568 !important;
}

.dark-mode .border-bottom {
    border-bottom-color: #4a5568 !important;
}

.dark-mode .bg-light {
    background-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .bg-secondary {
    background-color: #718096 !important;
    color: #f7fafc !important;
}

.dark-mode .text-dark {
    color: #e2e8f0 !important;
}

.dark-mode .text-secondary {
    color: #a0aec0 !important;
}

.dark-mode .form-text {
    color: #a0aec0 !important;
}

.dark-mode small {
    color: #a0aec0 !important;
}

.dark-mode .planner-modal-bx .modal-content {
    background-color: #2d3748 !important;
}

/* ========== MODAL CONSISTENCY FIXES ========== */
/* Ensure modal has consistent size and styling in both light and dark modes */
.planner-modal-bx .modal-dialog {
    max-width: 600px !important;
    width: 100% !important;
}

.planner-modal-bx .modal-content {
    border-radius: 12px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
}

.planner-modal-bx .modal-header {
    border-radius: 12px 12px 0 0 !important;
    padding: 20px 24px !important;
}

.planner-modal-bx .modal-title {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    color: #1e293b !important;
}

.planner-modal-bx .modal-body {
    padding: 24px !important;
}

.planner-modal-bx .modal-footer {
    padding: 16px 24px !important;
    border-radius: 0 0 12px 12px !important;
}

/* Light mode modal styling - Match Dark Mode Design */
body:not(.dark-mode) .planner-modal-bx .modal-content {
    background-color: #ffffff !important;
    border: none !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: none !important;
    color: white !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-footer {
    background-color: #f8fafc !important;
    border-top: 1px solid #e5e7eb !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-title {
    color: #ffffff !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-header .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
    text-shadow: none !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-header .close:hover {
    opacity: 1 !important;
}

body:not(.dark-mode) .planner-modal-bx .modal-body {
    background-color: #ffffff !important;
    color: #1e293b !important;
}

body:not(.dark-mode) .planner-modal-bx label {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.dark-mode) .planner-modal-bx .form-text {
    color: #6b7280 !important;
}

/* Dark mode modal styling */
.dark-mode .planner-modal-bx .modal-title {
    color: #f7fafc !important;
}

.dark-mode .planner-modal-bx .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: none !important;
}

.dark-mode .planner-modal-bx .modal-header .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
    text-shadow: none !important;
}

.dark-mode .planner-modal-bx .modal-header .close:hover {
    opacity: 1 !important;
}

.dark-mode .planner-modal-bx .modal-footer {
    background-color: #374151 !important;
    border-top: 1px solid #4b5563 !important;
}

.dark-mode .planner-modal-bx .modal-body {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .planner-modal-bx label {
    color: #f7fafc !important;
}

/* ========== ALL MODALS - CONSISTENT GRADIENT STYLING ========== */
/* Apply gradient header to all modals, not just planner-modal-bx */

/* Base modal styling for all modals */
.modal-dialog {
    max-width: 600px !important;
}

.modal-content {
    border-radius: 12px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
    border: none !important;
}

.modal-header {
    border-radius: 12px 12px 0 0 !important;
    padding: 20px 24px !important;
}

.modal-body {
    padding: 24px !important;
}

.modal-footer {
    padding: 16px 24px !important;
    border-radius: 0 0 12px 12px !important;
}

/* Light Mode - All Modals with Gradient Header */
body:not(.dark-mode) .modal-content {
    background-color: #ffffff !important;
}

body:not(.dark-mode) .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: none !important;
}

body:not(.dark-mode) .modal-header.bg-info,
body:not(.dark-mode) .modal-header.bg-primary,
body:not(.dark-mode) .modal-header.bg-success,
body:not(.dark-mode) .modal-header.bg-warning,
body:not(.dark-mode) .modal-header.bg-danger {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

body:not(.dark-mode) .modal-title {
    color: #ffffff !important;
    font-weight: 600 !important;
}

body:not(.dark-mode) .modal-header .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
    text-shadow: none !important;
}

body:not(.dark-mode) .modal-header .close:hover {
    opacity: 1 !important;
}

body:not(.dark-mode) .modal-header.text-white,
body:not(.dark-mode) .modal-header .text-white {
    color: #ffffff !important;
}

body:not(.dark-mode) .modal-body {
    background-color: #ffffff !important;
    color: #1e293b !important;
}

body:not(.dark-mode) .modal-footer {
    background-color: #f8fafc !important;
    border-top: 1px solid #e5e7eb !important;
}

/* Dark Mode - All Modals with Gradient Header */
.dark-mode .modal-content {
    background-color: #2d3748 !important;
}

.dark-mode .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: none !important;
}

.dark-mode .modal-header.bg-info,
.dark-mode .modal-header.bg-primary,
.dark-mode .modal-header.bg-success,
.dark-mode .modal-header.bg-warning,
.dark-mode .modal-header.bg-danger {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.dark-mode .modal-title {
    color: #ffffff !important;
    font-weight: 600 !important;
}

.dark-mode .modal-header .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
    text-shadow: none !important;
}

.dark-mode .modal-header .close:hover {
    opacity: 1 !important;
}

.dark-mode .modal-body {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-footer {
    background-color: #374151 !important;
    border-top: 1px solid #4b5563 !important;
}

.dark-mode .modal label {
    color: #f7fafc !important;
}

/* Modal dialog sizes */
.modal-dialog.modal-lg {
    max-width: 800px !important;
}

.modal-dialog.modal-xl {
    max-width: 1140px !important;
}

.modal-dialog.modal-sm {
    max-width: 400px !important;
}

/* Cards inside modals - Light Mode */
body:not(.dark-mode) .modal-body .card {
    background-color: #f8fafc !important;
    border: 1px solid #e5e7eb !important;
}

body:not(.dark-mode) .modal-body .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: #ffffff !important;
    border-bottom: none !important;
}

body:not(.dark-mode) .modal-body .card-header.bg-primary,
body:not(.dark-mode) .modal-body .card-header.bg-info,
body:not(.dark-mode) .modal-body .card-header.bg-success {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

body:not(.dark-mode) .modal-body .card-header h6 {
    color: #ffffff !important;
}

body:not(.dark-mode) .modal-body .card-body {
    background-color: #ffffff !important;
    color: #1e293b !important;
}

body:not(.dark-mode) .modal-body label {
    color: #374151 !important;
}

body:not(.dark-mode) .modal-body .form-text {
    color: #6b7280 !important;
}

/* Cards inside modals - Dark Mode */
.dark-mode .modal-body .card {
    background-color: #374151 !important;
    border: 1px solid #4b5563 !important;
}

.dark-mode .modal-body .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: #ffffff !important;
    border-bottom: none !important;
}

.dark-mode .modal-body .card-header.bg-primary,
.dark-mode .modal-body .card-header.bg-info,
.dark-mode .modal-body .card-header.bg-success,
.dark-mode .modal-body .card-header.text-white {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.dark-mode .modal-body .card-header h6 {
    color: #ffffff !important;
}

.dark-mode .modal-body .card-body {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .modal-body .table {
    color: #e2e8f0 !important;
}

.dark-mode .modal-body .table td,
.dark-mode .modal-body .table th {
    color: #e2e8f0 !important;
    border-color: #4b5563 !important;
}

/* Modal Buttons Styling */
body:not(.dark-mode) .modal-footer .btn-secondary {
    background-color: #e5e7eb !important;
    border-color: #e5e7eb !important;
    color: #374151 !important;
}

body:not(.dark-mode) .modal-footer .btn-secondary:hover {
    background-color: #d1d5db !important;
    border-color: #d1d5db !important;
}

body:not(.dark-mode) .modal-footer .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border: none !important;
    color: #ffffff !important;
}

body:not(.dark-mode) .modal-footer .btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}

body:not(.dark-mode) .modal-footer .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: #ffffff !important;
}

body:not(.dark-mode) .modal-footer .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
}

.dark-mode .modal-footer .btn-secondary {
    background-color: #4a5568 !important;
    border-color: #4a5568 !important;
    color: #f7fafc !important;
}

.dark-mode .modal-footer .btn-secondary:hover {
    background-color: #374151 !important;
    border-color: #374151 !important;
}

.dark-mode .modal-footer .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode .modal-footer .btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}

.dark-mode .modal-footer .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: #ffffff !important;
}

.dark-mode .modal-footer .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
}

/* Alert messages inside modals */
body:not(.dark-mode) .modal-body .alert {
    border-radius: 8px !important;
}

body:not(.dark-mode) .modal-body .alert-info {
    background-color: #dbeafe !important;
    border-color: #3b82f6 !important;
    color: #1e40af !important;
}

body:not(.dark-mode) .modal-body .alert-success {
    background-color: #d1fae5 !important;
    border-color: #10b981 !important;
    color: #065f46 !important;
}

body:not(.dark-mode) .modal-body .alert-warning {
    background-color: #fef3c7 !important;
    border-color: #f59e0b !important;
    color: #92400e !important;
}

body:not(.dark-mode) .modal-body .alert-danger {
    background-color: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #991b1b !important;
}

.dark-mode .modal-body .alert {
    border-radius: 8px !important;
}

.dark-mode .modal-body .alert-info {
    background-color: rgba(59, 130, 246, 0.15) !important;
    border-color: #3b82f6 !important;
    color: #93c5fd !important;
}

.dark-mode .modal-body .alert-success {
    background-color: rgba(16, 185, 129, 0.15) !important;
    border-color: #10b981 !important;
    color: #6ee7b7 !important;
}

.dark-mode .modal-body .alert-warning {
    background-color: rgba(245, 158, 11, 0.15) !important;
    border-color: #f59e0b !important;
    color: #fcd34d !important;
}

.dark-mode .modal-body .alert-danger {
    background-color: rgba(239, 68, 68, 0.15) !important;
    border-color: #ef4444 !important;
    color: #fca5a5 !important;
}

.dark-mode .table-responsive {
    border: 1px solid #4a5568 !important;
}

.dark-mode .bulk-actions-bar {
    background-color: rgba(99, 179, 237, 0.1) !important;
    border-color: #63b3ed !important;
    color: #bee3f8 !important;
}

.dark-mode .header-title {
    color: #f7fafc !important;
}

.dark-mode .badge-primary {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #60a5fa !important;
}

/* Dark mode badge variants - High contrast for visibility */
.dark-mode .badge-success {
    background-color: #10b981 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #34d399 !important;
}

.dark-mode .badge-warning {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #fbbf24 !important;
}

.dark-mode .badge-danger {
    background-color: #ef4444 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #f87171 !important;
}

.dark-mode .badge-info {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #60a5fa !important;
}

.dark-mode .badge-secondary {
    background-color: #6b7280 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #9ca3af !important;
}

.dark-mode .badge-dark {
    background-color: #1f2937 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #374151 !important;
}

.dark-mode .badge-orange {
    background-color: #f97316 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #fb923c !important;
}

.dark-mode .badge-purple {
    background-color: #a855f7 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #c084fc !important;
}

.dark-mode .badge-teal {
    background-color: #14b8a6 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #2dd4bf !important;
}

.dark-mode .badge-indigo {
    background-color: #6366f1 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: 1px solid #818cf8 !important;
}

.dark-mode .tab-content {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode .tab-pane {
    background-color: #2d3748 !important;
}

.dark-mode .card-body.py-3 {
    background-color: #2d3748 !important;
}

.dark-mode .input-group-text {
    background-color: #4a5568 !important;
    border-color: #718096 !important;
    color: #e2e8f0 !important;
}

.dark-mode .input-group .form-control:not(:last-child) {
    border-right: 0;
}

/* Dark mode - Conversation Summary & Timeline */
.dark-mode .conversation-summary-content .bg-light {
    background-color: #1e293b !important;
    color: #e2e8f0 !important;
}

.dark-mode .conversation-summary-content .text-muted,
.dark-mode .conversation-summary-content small.text-muted {
    color: #cbd5e0 !important;
    font-weight: 500 !important;
}

.dark-mode .conversation-summary-content .font-weight-bold {
    color: #f7fafc !important;
    font-weight: 700 !important;
}

.dark-mode .conversation-summary-content .text-primary {
    color: #60a5fa !important;
}

.dark-mode .conversation-summary-content .text-info {
    color: #38bdf8 !important;
}

.dark-mode .conversation-summary-content .text-success {
    color: #34d399 !important;
}

.dark-mode .conversation-summary-content .text-warning {
    color: #fbbf24 !important;
}

.dark-mode .conversation-summary-content .border-success {
    border-color: #10b981 !important;
}

.dark-mode .conversation-summary-content .timeline-content {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
    color: #e2e8f0 !important;
}

.dark-mode .conversation-summary-content .timeline-content strong {
    color: #f7fafc !important;
    font-weight: 700 !important;
}

.dark-mode .conversation-summary-content .timeline-content p {
    color: #cbd5e0 !important;
}

.dark-mode .conversation-summary-content .timeline-item:not(:last-child)::before {
    background-color: #475569 !important;
}

.dark-mode .conversation-summary-content .timeline-marker {
    background-color: #64748b !important;
    border: 2px solid #334155 !important;
}

.dark-mode .conversation-summary-content .timeline-item.latest .timeline-marker {
    background-color: #10b981 !important;
    border-color: #34d399 !important;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5) !important;
}

.dark-mode .card-header.bg-primary {
    background-color: #1e40af !important;
}

.dark-mode .table-borderless td,
.dark-mode .table-borderless th {
    color: #e2e8f0 !important;
    border: none !important;
}

.dark-mode .table-borderless td strong {
    color: #cbd5e0 !important;
    font-weight: 600 !important;
}

.dark-mode #contact-messages,
.dark-mode #conversation-summary {
    color: #e2e8f0 !important;
}

.dark-mode #contact-messages .text-center,
.dark-mode #conversation-summary .text-center {
    color: #cbd5e0 !important;
}

.dark-mode .input-group .form-control:not(:first-child) {
    border-left: 0;
}

/* Conversation Bubble Styles */
.conversations-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.message-item {
    display: flex;
    width: 100%;
    margin-bottom: 15px !important;
}

.message-bubble {
    display: flex;
    flex-direction: column;
    word-wrap: break-word;
    border-radius: 18px !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
    animation: fadeInMessage 0.3s ease-in;
}

.message-bubble.ml-auto {
    align-self: flex-end;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    color: white !important;
}

.message-bubble.mr-auto {
    align-self: flex-start;
    background-color: #ffffff !important;
    border: 1px solid #e9ecef;
    color: #212529 !important;
}

.message-bubble .message-content p {
    margin: 0 !important;
    line-height: 1.4;
}

.message-bubble small {
    opacity: 0.8;
    font-size: 0.75rem;
    margin-top: 5px;
}

.message-bubble.ml-auto small {
    color: rgba(255,255,255,0.8) !important;
}

.message-bubble.mr-auto small {
    color: #6c757d !important;
}

@keyframes fadeInMessage {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dark-mode .conversations-list {
    background-color: #1a202c !important;
}

.dark-mode .message-bubble.mr-auto {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.dark-mode .message-bubble.mr-auto small {
    color: #a0aec0 !important;
}

#load-more-btn {
    transition: all 0.3s ease;
}

#load-more-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}
</style>
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('customers.breadcrumb_home') }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('customers.breadcrumb_category') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('customers.breadcrumb_customers') }}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">{{ __('customers.list.title') }} <span class="badge badge-primary" id="total-contacts">{{ $total_guests ?? 0 }}</span></h4>
                    <p class="text-muted mb-3">{{ __('customers.list.subtitle') }}</p>
                    
                    <!-- Bulk Actions Bar -->
                    <div id="bulk-actions-bar" class="alert alert-primary" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-check-circle mr-2"></i>
                                <span id="selected-count">0</span> {{ __('customers.bulk.selected', ['count' => '<span id="selected-count">0</span>']) }}
                            </div>
                            <div>
                                <button type="button" class="btn btn-success btn-sm mr-2" id="bulk-send-message">
                                    <i class="mdi mdi-message-text mr-1"></i>{{ __('customers.actions.send_message') }}
                                </button>
                                <button type="button" class="btn btn-danger btn-sm mr-2" id="bulk-delete">
                                    <i class="mdi mdi-delete mr-1"></i>{{ __('customers.actions.delete_selected') }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-selection">
                                    <i class="mdi mdi-close mr-1"></i>{{ __('customers.actions.clear_selection') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Handoff Management Tabs -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px;">
                                <div class="card-body py-3">
                                    <h5 class="text-white mb-3"><i class="mdi mdi-account-supervisor-circle mr-2"></i>{{ __('customers.handoff.title') }}</h5>
                                    
                                    <!-- Status Filter Tabs -->
                                    <ul class="nav nav-pills nav-fill" id="handoff-tabs" style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 5px;">
                                        <li class="nav-item">
                                            <a class="nav-link active text-white" data-status="all" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-view-dashboard mr-1"></i>{{ __('customers.handoff.all') }}
                                                <span class="badge badge-light ml-2">{{ $total_guests ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="ai" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-robot mr-1"></i>{{ __('customers.handoff.ai_handling') }}
                                                <span class="badge badge-light ml-2">{{ $handoff_stats['ai_handled'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="pending_handoff" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-clock-outline mr-1"></i>{{ __('customers.handoff.pending_handoff') }}
                                                <span class="badge badge-warning ml-2">{{ $handoff_stats['pending_handoff'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="handed_off" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-account-check mr-1"></i>{{ __('customers.handoff.handed_off') }}
                                                <span class="badge badge-info ml-2">{{ $handoff_stats['handed_off'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="completed" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-check-circle mr-1"></i>{{ __('customers.handoff.completed') }}
                                                <span class="badge badge-success ml-2">{{ $handoff_stats['completed'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="urgent" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-alert mr-1"></i>{{ __('customers.handoff.urgent') }}
                                                <span class="badge badge-danger ml-2">{{ $handoff_stats['urgent_cases'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p>  
                        <button type="button" class="btn btn-outline-success" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#myModal" onclick="$('#edit_guest').val(''); $('#ProfileStep5').attr('action', '<?= url('guest/store') ?>');">
                          <i class="mdi mdi-account-plus" style="font-size: 1.2em; margin-right: 6px;"></i>
                          {{ __('customers.actions.add_new') }}  
                        </button>
                        <script>
                        // Set modal title when adding new contact
                        $('button[data-target="#myModal"]').on('click', function() {
                            // Check if this is the add button (action contains 'store')
                            var action = $(this).attr('onclick');
                            if (action && (action.includes('store') || action.includes("val('')"))) {
                                $('#contactModalTitle').text('{{ __('customers.modals.add_title') }}');
                                // Clear form fields
                                $('#edit_guest_name').val('');
                                $('#edit_guest_phone').val('');
                                $('#edit_guest_email').val('');
                                $('#edit_lead_status').val('');
                                initializeProductSelect();
                                $('#edit_product_ids').val(null).trigger('change');
                                $('#edit_guest').val('');
                                $('#edit-form-status').html('');
                                
                                // Reset phone input if it exists
                                if (window.editPhoneInput) {
                                    editPhoneInput.setNumber('');
                                }
                            }
                        });
                        </script>
                        <a href="#" class="btn btn-outline-success ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#myUploadModal" title="{{ __('customers.actions.upload_excel') }}">
                            <i class="mdi mdi-file-excel-box" style="font-size: 1.2em; margin-right: 6px;"></i>
                            {{ __('customers.actions.upload_excel') }}
                        </a>

                        <button type="button" class="btn btn-outline-primary ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#whatsappSyncModal">
                            <i class="mdi mdi-whatsapp" style="font-size: 1.2em; margin-right: 6px;"></i>
                            {{ __('customers.actions.sync_whatsapp') }}
                        </button>

                        <button type="button" class="btn btn-outline-info ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#googleSyncModal">
                            <i class="mdi mdi-google" style="font-size: 1.2em; margin-right: 6px; color: #4285f4;"></i>
                            {{ __('customers.actions.sync_google') }}
                        </button>

                        <div class="d-inline-flex align-items-center ml-2 mt-2 mt-md-0" style="min-width: 220px; vertical-align: middle;">
                            <label for="product-filter" class="mb-0 mr-2 text-muted" style="white-space: nowrap;">Product</label>
                            <select id="product-filter" class="form-control">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- WhatsApp Sync Modal -->
                        <div class="modal fade planner-modal-bx" id="whatsappSyncModal" tabindex="-1" role="dialog" aria-labelledby="whatsappSyncModalLabel" aria-hidden="true" style="display: none;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content start-here">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                                        <h5 class="modal-title mt-0" id="whatsappSyncModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">{{ __('customers.whatsapp_sync.title') }}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>{{ __('customers.whatsapp_sync.description') }}</p>
                                        <div id="whatsapp-connection-status" class="mb-3"></div>
                                        <div id="whatsapp-sync-status" class="mb-2"></div>
                                        <button type="button" class="btn btn-success" id="startWhatsappSync">
                                            <i class="mdi mdi-whatsapp"></i> {{ __('customers.whatsapp_sync.start_sync') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <script type="text/javascript">
                            // Check WhatsApp instance status when modal opens
                            $('#whatsappSyncModal').on('shown.bs.modal', function() {
                                $('#whatsapp-connection-status').html('<span class="text-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>Checking WhatsApp connection...</span>');
                                $('#whatsapp-sync-status').html(''); // Clear previous sync messages
                                
                                $.ajax({
                                    url: '{{ route("guest.whatsappInstanceStatus") }}',
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    success: function(response) {
                                        console.log('WhatsApp instance status:', response);
                                        
                                        if (response.success && response.is_connected) {
                                            $('#whatsapp-connection-status').html(
                                                '<div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">' +
                                                '<i class="mdi mdi-check-circle mr-2"></i>' +
                                                '<strong>Connected:</strong> ' + (response.instance_name || response.phone_number || 'WhatsApp Instance') +
                                                '<br><small class="text-muted">Status: ' + (response.connect_status || response.status) + '</small>' +
                                                '</div>'
                                            );
                                        } else if (response.has_instance && !response.is_connected) {
                                            $('#whatsapp-connection-status').html(
                                                '<div class="alert alert-warning" style="padding: 10px; margin-bottom: 15px;">' +
                                                '<i class="mdi mdi-alert-circle mr-2"></i>' +
                                                '<strong>Not Connected</strong><br>' +
                                                response.message +
                                                '</div>'
                                            );
                                        } else {
                                            $('#whatsapp-connection-status').html(
                                                '<div class="alert alert-danger" style="padding: 10px; margin-bottom: 15px;">' +
                                                '<i class="mdi mdi-alert mr-2"></i>' +
                                                '<strong>No Instance Found</strong><br>' +
                                                response.message +
                                                '</div>'
                                            );
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error checking instance status:', xhr.responseJSON);
                                        $('#whatsapp-connection-status').html(
                                            '<div class="alert alert-danger" style="padding: 10px; margin-bottom: 15px;">' +
                                            '<i class="mdi mdi-alert mr-2"></i>' +
                                            'Failed to check connection status: ' + (xhr.responseJSON ? xhr.responseJSON.message : error) +
                                            '</div>'
                                        );
                                    }
                                });
                            });
                        </script>
                        
                        <!-- Google Contacts Sync Modal -->
                        <div class="modal fade planner-modal-bx" id="googleSyncModal" tabindex="-1" role="dialog" aria-labelledby="googleSyncModalLabel" aria-hidden="true" style="display: none;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content start-here">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                                        <h5 class="modal-title mt-0" id="googleSyncModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">
                                            <i class="mdi mdi-google mr-2"></i>{{ __('customers.google_sync.title') }}
                                        </h5>
                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-3">
                                            <i class="mdi mdi-google" style="font-size: 4rem; color: #4285f4;"></i>
                                        </div>
                                        <p class="text-center">{{ __('customers.google_sync.description') }}</p>
                                        <p class="text-muted small text-center">{{ __('customers.google_sync.secure_process') }}</p>
                                        
                                        <div id="google-sync-status" class="mb-3"></div>
                                        
                                        <div class="text-center">
                                            <button type="button" class="btn btn-primary btn-lg" id="startGoogleAuth" style="background: #4285f4; border-color: #4285f4; padding: 12px 30px; border-radius: 25px;">
                                                <i class="mdi mdi-google mr-2"></i> {{ __('customers.google_sync.sign_in_button') }}
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="mdi mdi-information"></i>
                                                {{ __('customers.google_sync.benefits_title') }}:
                                                <ul class="mt-2 mb-0">
                                                    <li>{{ __('customers.google_sync.benefits.secure_oauth') }}</li>
                                                    <li>{{ __('customers.google_sync.benefits.read_only') }}</li>
                                                    <li>{{ __('customers.google_sync.benefits.no_passwords') }}</li>
                                                    <li>{{ __('customers.google_sync.benefits.auto_dedupe') }}</li>
                                                </ul>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <script type="text/javascript">
                            // Google API Configuration
                            const GOOGLE_CLIENT_ID = '{{ config("services.google.client_id", "YOUR_GOOGLE_CLIENT_ID") }}';
                            const GOOGLE_API_KEY = '{{ config("services.google.api_key", "YOUR_GOOGLE_API_KEY") }}';
                            const DISCOVERY_DOC = 'https://people.googleapis.com/$discovery/rest?version=v1';
                            const SCOPES = 'https://www.googleapis.com/auth/contacts.readonly';
                            
                            let tokenClient;
                            let gapi_inited = false;
                            let gsi_inited = false;
                            
                            // Initialize Google API
                            function initializeGoogleAPI() {
                                if (typeof gapi !== 'undefined' && !gapi_inited) {
                                    gapi.load('client', initializeGapiClient);
                                }
                                if (typeof google !== 'undefined' && !gsi_inited) {
                                    initializeGsiClient();
                                }
                            }
                            
                            async function initializeGapiClient() {
                                try {
                                    await gapi.client.init({
                                        apiKey: GOOGLE_API_KEY,
                                        discoveryDocs: [DISCOVERY_DOC],
                                    });
                                    gapi_inited = true;
                                    console.log('Google API client initialized');
                                } catch (error) {
                                    console.error('Error initializing Google API:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">Error initializing Google API</div>');
                                }
                            }
                            
                            function initializeGsiClient() {
                                try {
                                    tokenClient = google.accounts.oauth2.initTokenClient({
                                        client_id: GOOGLE_CLIENT_ID,
                                        scope: SCOPES,
                                        callback: handleAuthResponse,
                                    });
                                    gsi_inited = true;
                                    console.log('Google Sign-In client initialized');
                                } catch (error) {
                                    console.error('Error initializing Google Sign-In:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">Error initializing Google Sign-In</div>');
                                }
                            }
                            
                            // Handle Google Auth button click
                            $('#startGoogleAuth').on('click', function() {
                                $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{ __("customers.google_sync.initializing") }}</div>');
                                
                                if (!gapi_inited || !gsi_inited) {
                                    initializeGoogleAPI();
                                    setTimeout(() => {
                                        if (gapi_inited && gsi_inited) {
                                            requestGoogleAuth();
                                        } else {
                                            $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.init_failed") }}</div>');
                                        }
                                    }, 2000);
                                } else {
                                    requestGoogleAuth();
                                }
                            });
                            
                            function requestGoogleAuth() {
                                try {
                                    if (gapi.client.getToken() === null) {
                                        tokenClient.requestAccessToken({prompt: 'consent'});
                                    } else {
                                        tokenClient.requestAccessToken({prompt: ''});
                                    }
                                } catch (error) {
                                    console.error('Error requesting Google auth:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.auth_start_failed") }}</div>');
                                }
                            }
                            
                            // Handle authentication response
                            async function handleAuthResponse(resp) {
                                if (resp.error !== undefined) {
                                    console.error('Google Auth Error:', resp.error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.auth_failed") }}: ' + resp.error + '</div>');
                                    return;
                                }
                                
                                $('#google-sync-status').html('<div class="alert alert-success"><i class="mdi mdi-check mr-2"></i>{{ __("customers.google_sync.auth_success") }}</div>');
                                
                                try {
                                    await fetchGoogleContacts();
                                } catch (error) {
                                    console.error('Error fetching contacts:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.fetch_failed") }}</div>');
                                }
                            }
                            
                            // Fetch Google Contacts
                            async function fetchGoogleContacts() {
                                try {
                                    $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{ __("customers.google_sync.fetching") }}</div>');
                                    
                                    const response = await gapi.client.people.people.connections.list({
                                        resourceName: 'people/me',
                                        personFields: 'names,phoneNumbers,emailAddresses',
                                        pageSize: 1000
                                    });
                                    
                                    const contacts = response.result.connections || [];
                                    console.log('Google contacts fetched:', contacts.length);
                                    
                                    if (contacts.length > 0) {
                                        processGoogleContacts(contacts);
                                    } else {
                                        $('#google-sync-status').html('<div class="alert alert-warning">{{ __("customers.google_sync.no_contacts") }}</div>');
                                    }
                                    
                                } catch (error) {
                                    console.error('Error fetching Google contacts:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.fetch_failed") }}: ' + error.message + '</div>');
                                }
                            }
                            
                            // Process and import Google contacts
                            function processGoogleContacts(contacts) {
                                $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{ __("customers.google_sync.processing") }}</div>');
                                
                                const processedContacts = contacts.map(contact => {
                                    const name = contact.names && contact.names.length > 0 
                                        ? contact.names[0].displayName || contact.names[0].givenName + ' ' + (contact.names[0].familyName || '')
                                        : 'Unknown Contact';
                                    
                                    const phones = contact.phoneNumbers || [];
                                    const emails = contact.emailAddresses || [];
                                    
                                    return {
                                        name: name.trim(),
                                        phones: phones.map(p => p.value),
                                        emails: emails.map(e => e.value),
                                        primaryPhone: phones.length > 0 ? phones[0].value : null,
                                        primaryEmail: emails.length > 0 ? emails[0].value : null
                                    };
                                }).filter(contact => contact.primaryPhone); // Only contacts with phone numbers
                                
                                console.log('Processed contacts:', processedContacts.length);
                                
                                if (processedContacts.length > 0) {
                                    importGoogleContacts(processedContacts);
                                } else {
                                    $('#google-sync-status').html('<div class="alert alert-warning">{{ __("customers.google_sync.no_phone_contacts") }}</div>');
                                }
                            }
                            
                            // Import contacts to backend
                            function importGoogleContacts(contacts) {
                                $.ajax({
                                    url: '<?= url("guest/importGoogleContacts") ?>',
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Content-Type': 'application/json'
                                    },
                                    data: JSON.stringify({
                                        contacts: contacts
                                    }),
                                    success: function(response) {
                                        console.log('Google contacts import response:', response);
                                        
                                        if (response.success) {
                                            $('#google-sync-status').html(
                                                '<div class="alert alert-success">' +
                                                '<i class="mdi mdi-check-circle mr-2"></i>' +
                                                '{{ __("customers.google_sync.import_success") }}: ' + 
                                                (response.imported_count || 0) + ' {{ __("customers.whatsapp_sync.contacts_imported") }}' +
                                                '</div>'
                                            );
                                            
                                            // Reload page after 3 seconds
                                            setTimeout(function() {
                                                location.reload();
                                            }, 3000);
                                        } else {
                                            $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.import_failed") }}: ' + (response.message || 'Unknown error') + '</div>');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Google contacts import failed:', error);
                                        $('#google-sync-status').html('<div class="alert alert-danger">{{ __("customers.google_sync.import_failed") }}: Import request failed</div>');
                                    }
                                });
                            }
                            
                            // Initialize when modal is shown
                            $('#googleSyncModal').on('shown.bs.modal', function() {
                                if (typeof gapi === 'undefined' || typeof google === 'undefined') {
                                    $('#google-sync-status').html('<div class="alert alert-warning">{{ __("customers.google_sync.loading_apis") }}</div>');
                                    loadGoogleAPIs();
                                }
                            });
                            
                            // Load Google APIs dynamically
                            function loadGoogleAPIs() {
                                if (typeof gapi === 'undefined') {
                                    const gapiScript = document.createElement('script');
                                    gapiScript.src = 'https://apis.google.com/js/api.js';
                                    gapiScript.onload = () => {
                                        console.log('Google API script loaded');
                                        gapi.load('client', initializeGapiClient);
                                    };
                                    document.head.appendChild(gapiScript);
                                }
                                
                                if (typeof google === 'undefined') {
                                    const gsiScript = document.createElement('script');
                                    gsiScript.src = 'https://accounts.google.com/gsi/client';
                                    gsiScript.onload = () => {
                                        console.log('Google Sign-In script loaded');
                                        initializeGsiClient();
                                    };
                                    document.head.appendChild(gsiScript);
                                }
                            }
                        </script>
                        
                        <script type="text/javascript">
                            $('#startWhatsappSync').on('click', function () {
                                console.log('Start WhatsApp Sync button clicked');
                                $('#whatsapp-sync-status').html('<span class="text-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{ __("customers.whatsapp_sync.syncing") }}</span>');
                                
                                // Check WhatsApp instance status dynamically via AJAX
                                $.ajax({
                                    url: '{{ route("guest.whatsappInstanceStatus") }}',
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    success: function(response) {
                                        console.log('Button click - Instance status response:', response);
                                        
                                        if (response.success && response.is_connected) {
                                            // Instance is ready, proceed with sync
                                            console.log('Instance is connected, starting sync with instance ID:', response.instance_id);
                                            syncContactsFromWAAPI(response.instance_id);
                                        } else if (response.has_instance && !response.is_connected) {
                                            // Instance exists but not connected
                                            console.warn('Instance exists but not connected:', response);
                                            $('#whatsapp-sync-status').html(
                                                '<div class="alert alert-warning" style="padding: 10px;">' +
                                                '<i class="mdi mdi-alert-circle mr-2"></i>' +
                                                response.message +
                                                '</div>'
                                            );
                                        } else {
                                            // No instance found
                                            console.error('No instance found:', response);
                                            $('#whatsapp-sync-status').html(
                                                '<div class="alert alert-danger" style="padding: 10px;">' +
                                                '<i class="mdi mdi-alert mr-2"></i>' +
                                                response.message +
                                                '</div>'
                                            );
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error checking instance status:', {xhr: xhr, status: status, error: error, response: xhr.responseJSON});
                                        $('#whatsapp-sync-status').html(
                                            '<div class="alert alert-danger" style="padding: 10px;">' +
                                            '<i class="mdi mdi-alert mr-2"></i>' +
                                            'Failed to check WhatsApp instance status. Please try again.' +
                                            (xhr.responseJSON && xhr.responseJSON.message ? '<br>' + xhr.responseJSON.message : '') +
                                            '</div>'
                                        );
                                    }
                                });
                            });
                            
                            function syncContactsFromWAAPI(instanceId) {
                                console.log('Syncing contacts from WASender for instance:', instanceId);
                                
                                // Use the backend route to sync contacts from WASender API
                                $.ajax({
                                    url: '<?= url("guest/syncWhatsappContacts") ?>',
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Content-Type': 'application/json'
                                    },
                                    success: function (data) {
                                        console.log('WASender contacts sync response:', data);
                                        
                                        if (data.success && data.contacts && data.contacts.length > 0) {
                                            // Process and save contacts to backend
                                            $.ajax({
                                                url: '<?= url("guest/importWhatsappContacts") ?>',
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Content-Type': 'application/json'
                                                },
                                                data: JSON.stringify({
                                                    contacts: data.contacts,
                                                    instance_id: instanceId
                                                }),
                                                success: function(response) {
                                                    console.log('Import response:', response);
                                                    
                                                    if (response.success) {
                                                        $('#whatsapp-sync-status').html('<span class="text-success">{{ __("customers.whatsapp_sync.contacts_synced_successfully") }}: ' + (response.imported_count || 0) + ' {{ __("customers.whatsapp_sync.contacts_imported") }}</span>');
                                                        
                                                        // Reload page after 2 seconds to show new contacts
                                                        setTimeout(function() {
                                                            location.reload();
                                                        }, 2000);
                                                    } else {
                                                        $('#whatsapp-sync-status').html('<span class="text-danger">{{ __("customers.whatsapp_sync.failed_to_import") }}: ' + (response.message || 'Unknown error') + '</span>');
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    console.error('Import failed:', error);
                                                    $('#whatsapp-sync-status').html('<span class="text-danger">{{ __("customers.whatsapp_sync.failed_to_import") }}: Import request failed</span>');
                                                }
                                            });
                                        } else {
                                            $('#whatsapp-sync-status').html('<span class="text-warning">{{ __("customers.whatsapp_sync.no_contacts_found") }}</span>');
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        console.error('WASender contacts request failed:', {
                                            status: xhr.status,
                                            statusText: xhr.statusText,
                                            responseText: xhr.responseText,
                                            error: error
                                        });
                                        
                                        let errorMessage = '{{ __("customers.whatsapp_sync.error") }}';
                                        if (xhr.status === 401) {
                                            errorMessage = '{{ __("customers.whatsapp_sync.auth_failed") }}';
                                        } else if (xhr.status === 404) {
                                            errorMessage = '{{ __("customers.whatsapp_sync.instance_not_found") }}';
                                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                            errorMessage = xhr.responseJSON.message;
                                        }
                                        
                                        $('#whatsapp-sync-status').html('<span class="text-danger">' + errorMessage + '</span>');
                                    }
                                });
                            }
                        </script>
                    </p>
                    <br/>
                    @php
                        // Define status labels globally for reuse in both table and modal
                        $statusLabels = [
                            'NEW' => 'New Lead',
                            'OUTREACHED' => 'Outreached',
                            'REPLIED' => 'Replied',
                            'ENGAGED' => 'Engaged',
                            'QUALIFIED' => 'Qualified',
                            'PITCHED' => 'Pitched',
                            'DEMO_SCHEDULED' => 'Demo Scheduled',
                            'PROPOSAL_SENT' => 'Proposal Sent',
                            'NEGOTIATING' => 'Negotiating',
                            'CLOSED' => 'Closed Won',
                            'LOST' => 'Closed Lost',
                            'HANDED_OFF' => 'Handed Off',
                            'DO_NOT_CONTACT' => 'Do Not Contact',
                            'NEEDS_ATTENTION' => 'Needs Attention',
                            'CONVERTED' => 'Converted',
                            'CHURNED' => 'Churned'
                        ];
                    @endphp
                    <div class="table-responsive">
                        <table class="table-standard contacts-datatable" id="datatable-buttons">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="select-all">
                                            <label class="custom-control-label" for="select-all"></label>
                                        </div>
                                    </th>
                                    <th>#</th>
                                    <th>{{ __('customers.table.name') }}</th>
                                    <th>{{ __('customers.table.phone') }}</th>
                                    <!--<th>{{ __('customers.table.email') }} </th>-->
                                    <th>{{ __('customers.table.created_at') }}</th>
                                    <th>{{ __('customers.table.lead_status') }}</th>
                                    <th>Products</th>
                                    <th>{{ __('customers.table.handoff_status') }}</th>
                                    <th>{{ __('customers.table.priority') }}</th>
                                    <th>{{ __('customers.table.assigned_agent') }}</th>
                                    <th name="buttons">{{ __('customers.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows are populated via server-side AJAX DataTables (GET /guest/data) -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <!--<th>Email </th>-->
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th name="buttons"></th>
                                </tr>
                        </table>
                        
                        <!-- Pagination Links -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted">
                                </p>
                            </div>
                            <div>
                               
                            </div>
                        </div>
                        
                    </div>

                </div><!--end card-body-->
            </div><!--end card-->
        </div> <!-- end col -->
    </div> <!-- end row -->
    
    <!-- Lead Status Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title mb-4">
                        <i class="mdi mdi-chart-bar"></i> {{ __('customers.summary.title') }}
                    </h4>
                    
                    <div class="row">
                        <!-- New Leads -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #17a2b8; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.new') }}</p>
                                            <h3 class="mb-0" style="color: #17a2b8;">{{ $lead_status_stats['NEW'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-account-plus" style="font-size: 2.5rem; color: #17a2b8; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Outreached -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #6f42c1; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.outreached') }}</p>
                                            <h3 class="mb-0" style="color: #6f42c1;">{{ $lead_status_stats['OUTREACHED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-send" style="font-size: 2.5rem; color: #6f42c1; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Replied -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #20c997; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.replied') }}</p>
                                            <h3 class="mb-0" style="color: #20c997;">{{ $lead_status_stats['REPLIED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-reply" style="font-size: 2.5rem; color: #20c997; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Engaged -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #28a745; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.engaged') }}</p>
                                            <h3 class="mb-0" style="color: #28a745;">{{ $lead_status_stats['ENGAGED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-account-check" style="font-size: 2.5rem; color: #28a745; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Qualified -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #007bff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.qualified') }}</p>
                                            <h3 class="mb-0" style="color: #007bff;">{{ $lead_status_stats['QUALIFIED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-star" style="font-size: 2.5rem; color: #007bff; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pitched -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #fd7e14; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.pitched') }}</p>
                                            <h3 class="mb-0" style="color: #fd7e14;">{{ $lead_status_stats['PITCHED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-presentation" style="font-size: 2.5rem; color: #fd7e14; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Demo Scheduled -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #6610f2; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.demo_scheduled') }}</p>
                                            <h3 class="mb-0" style="color: #6610f2;">{{ $lead_status_stats['DEMO_SCHEDULED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-calendar-check" style="font-size: 2.5rem; color: #6610f2; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Proposal Sent -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #e83e8c; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.proposal') }}</p>
                                            <h3 class="mb-0" style="color: #e83e8c;">{{ $lead_status_stats['PROPOSAL_SENT'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-file-document" style="font-size: 2.5rem; color: #e83e8c; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Negotiating -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #ffc107; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.negotiating') }}</p>
                                            <h3 class="mb-0" style="color: #ffc107;">{{ $lead_status_stats['NEGOTIATING'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-handshake" style="font-size: 2.5rem; color: #ffc107; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Closed (Won) -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #28a745; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: linear-gradient(135deg, #f8f9fa 0%, #e9f7ef 100%);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.won') }}</p>
                                            <h3 class="mb-0" style="color: #28a745;">{{ $lead_status_stats['CLOSED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-check-circle" style="font-size: 2.5rem; color: #28a745; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lost -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #dc3545; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.lost') }}</p>
                                            <h3 class="mb-0" style="color: #dc3545;">{{ $lead_status_stats['LOST'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-close-circle" style="font-size: 2.5rem; color: #dc3545; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Handed Off -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #17a2b8; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.handed_off') }}</p>
                                            <h3 class="mb-0" style="color: #17a2b8;">{{ $lead_status_stats['HANDED_OFF'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-account-switch" style="font-size: 2.5rem; color: #17a2b8; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Do Not Contact -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #6c757d; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.do_not_contact') }}</p>
                                            <h3 class="mb-0" style="color: #6c757d;">{{ $lead_status_stats['DO_NOT_CONTACT'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-cancel" style="font-size: 2.5rem; color: #6c757d; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Churned -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card" style="border-left: 4px solid #dc3545; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ __('customers.lead_status.churned') }}</p>
                                            <h3 class="mb-0" style="color: #dc3545;">{{ $lead_status_stats['CHURNED'] ?? 0 }}</h3>
                                        </div>
                                        <div>
                                            <i class="mdi mdi-account-remove" style="font-size: 2.5rem; color: #dc3545; opacity: 0.3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Summary -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0" style="color: white;"><i class="mdi mdi-information"></i> {{ __('customers.summary.total_contacts') }}</h5>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" style="color: white;">{{ $total_guests ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>

<div class="modal fade planner-modal-bx" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" onsubmit="return handleEditFormSubmission();">

            <div class="modal-content" style="border-radius: 12px !important; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important; border: none !important;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                    <h5 class="modal-title mt-0" id="contactModalTitle" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">{{ __('customers.modals.edit_title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body" style="padding: 24px !important;">

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{ __('customers.fields.name') }}</label>
                        <input type="text" 
                               name="guest_name" 
                               id="edit_guest_name" 
                               class="form-control" 
                               placeholder="{{ __('customers.placeholders.name') }}" 
                               pattern="^[a-zA-Z\s\-']+$"
                               title="Only letters, spaces, hyphens and apostrophes allowed"
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s\-']/g, '')"
                               required="">
                    </div>

                    <div class="form-group">
                        <label for="edit_guest_phone" class="col-form-label text-right">{{ __('customers.fields.phone') }}</label>
                        <input type="tel" 
                               name="guest_phone"
                               id="edit_guest_phone"
                               class="form-control phone-validation"
                               placeholder="{{ __('customers.placeholders.phone') }}"
                               pattern="^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$"
                               title="Phone format: +1234567890 or (123) 456-7890"
                               required="">
                        <small class="form-text text-muted">
                            {{ __('customers.messages.phone_format') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="edit_guest_email" class="col-form-label text-right">Email (Optional)</label>
                        <input type="email"
                               name="guest_email"
                               id="edit_guest_email"
                               class="form-control"
                               placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                        <label for="edit_lead_status" class="col-form-label text-right">{{ __('customers.fields.lead_status') }}</label>
                        <select class="form-control" name="lead_status" id="edit_lead_status">
                            <option value="">{{ __('customers.placeholders.lead_status') }}</option>
                            <option value="NEW">{{ __('customers.lead_status.new') }}</option>
                            <option value="OUTREACHED">{{ __('customers.lead_status.outreached') }}</option>
                            <option value="REPLIED">{{ __('customers.lead_status.replied') }}</option>
                            <option value="ENGAGED">{{ __('customers.lead_status.engaged') }}</option>
                            <option value="QUALIFIED">{{ __('customers.lead_status.qualified') }}</option>
                            <option value="PITCHED">{{ __('customers.lead_status.pitched') }}</option>
                            <option value="DEMO_SCHEDULED">{{ __('customers.lead_status.demo_scheduled') }}</option>
                            <option value="PROPOSAL_SENT">{{ __('customers.lead_status.proposal') }}</option>
                            <option value="NEGOTIATING">{{ __('customers.lead_status.negotiating') }}</option>
                            <option value="CLOSED">{{ __('customers.lead_status.won') }}</option>
                            <option value="LOST">{{ __('customers.lead_status.lost') }}</option>
                            <option value="HANDED_OFF">{{ __('customers.handoff.handed_off') }}</option>
                            <option value="DO_NOT_CONTACT">{{ __('customers.lead_status.do_not_contact') }}</option>
                            <option value="NEEDS_ATTENTION">{{ __('customers.lead_status.hot') }}</option>
                            <option value="CONVERTED">{{ __('customers.lead_status.won') }}</option>
                            <option value="CHURNED">{{ __('customers.lead_status.churned') }}</option>
                        </select>
                        <small class="form-text text-muted">
                            {{ __('customers.messages.lead_status_help') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="edit_product_ids" class="col-form-label text-right">Products</label>
                        <select class="form-control" name="product_ids[]" id="edit_product_ids" multiple="multiple">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Select one or more products for this contact's lead.
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e5e7eb !important; border-radius: 0 0 12px 12px !important; padding: 16px 24px !important;">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_guest" value="" name="id"/>
                <div id="edit-form-status" class="w-100 mb-2"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('customers.modals.cancel') }}</button>
                <button type="button" id="edit-submit-btn" class="btn btn-success" onclick="handleEditFormSubmission()" data-toggle="tooltip" data-placement="top">{{ __('customers.modals.save') }}</button>
            </div>
        </form>


    </div>
</div>

<div class="modal fade planner-modal-bx" id="myUploadModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" enctype="multipart/form-data" action="<?= url('guest/uploadGuest') ?>" method="POST">

            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                    <h5 class="modal-title mt-0" id="exampleModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">{{ __('customers.upload.title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center">
                        <span class="mr-2">{{ __('customers.upload.sample_file_info') }}</span>
                        <a href="<?= url('storage/uploads/sample.xlsx') ?>" class="btn btn-primary btn-sm font-weight-bold" style="margin-left:10px;">
                            <i class="mdi mdi-download" style="margin-right:5px;"></i>{{ __('customers.upload.download_sample') }}
                        </a>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class="col-form-label text-right">{{ __('customers.upload.select_file') }}</label>
                        <input type="file" name="file" id="edit_guest_name" class="form-control" accept=".xls,.csv,.xlsx,.vcf" placeholder="File Upload" required="">
                        <small class="form-text text-muted">
                            {{ __('customers.upload.supported_formats') }}: .xls, .xlsx, .csv, .vcf
                        </small>
                    </div>
                    <div class="form-group">
                        <a href="#" class="badge badge-info" data-toggle="collapse" data-target="#vcfInstructions" aria-expanded="false" aria-controls="vcfInstructions">
                            <i class="mdi mdi-information-outline"></i> {{ __('customers.upload.vcf_help') }}
                        </a>
                        <div class="collapse mt-2" id="vcfInstructions">
                            <div class="card card-body">
                                <strong>{{ __('customers.upload.vcf_instructions') }}</strong>
                                <ol class="mb-2">
                                    <li>{{ __('customers.upload.vcf_step_1') }}</li>
                                    <li>{{ __('customers.upload.vcf_step_2') }}</li>
                                    <li>{{ __('customers.upload.vcf_step_3') }}</li>
                                    <li>{{ __('customers.upload.vcf_step_4') }}</li>
                                    <li>{{ __('customers.upload.vcf_step_5') }}</li>
                                    <li>{{ __('customers.upload.vcf_step_6') }}</li>
                                </ol>
                                <small class="text-muted">
                                    {{ __('customers.upload.vcf_note') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_guest" value="" name="id"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('customers.modals.close') }}</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">{{ __('customers.modals.save') }}</button>
            </div>
        </form>


    </div>
</div>

<!-- Contact View Modal -->
<div class="modal fade" id="contactViewModal" tabindex="-1" role="dialog" aria-labelledby="contactViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                <h5 class="modal-title" id="contactViewModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">
                    <i class="mdi mdi-account-circle mr-2"></i>{{ __('customers.summary.contact_details') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Conversation Summary Section -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="mdi mdi-chat-processing mr-2"></i>{{ __('customers.summary.conversation_summary') }}</h6>
                            </div>
                            <div class="card-body">
                                <div id="conversation-summary">
                                    <div class="text-center text-muted">
                                        <i class="mdi mdi-loading mdi-spin"></i> {{__('loading_conversation_summary')}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="mdi mdi-account mr-2"></i>{{__('contact_information')}}</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>{{__('name')}}:</strong></td>
                                        <td id="view-contact-name"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{__('phone')}}:</strong></td>
                                        <td id="view-contact-phone"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{__('email')}}:</strong></td>
                                        <td id="view-contact-email"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{__('group')}}:</strong></td>
                                        <td id="view-contact-group"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{__('date_added')}}:</strong></td>
                                        <td id="view-contact-date"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="mdi mdi-message-text mr-2"></i>{{__('recent_messages')}}</h6>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <div id="contact-messages">
                                    <div class="text-center text-muted">
                                        <i class="mdi mdi-loading mdi-spin"></i> {{__('loading_messages')}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="sendMessageFromView()">
                    <i class="mdi mdi-message-text mr-2"></i>{{__('send_message')}}
                </button>
                <button type="button" class="btn btn-warning" onclick="editFromView()">
                    <i class="mdi mdi-pencil mr-2"></i>{{__('edit_contact')}}
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('close')}}</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                <h5 class="modal-title" id="sendMessageModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">
                    <i class="mdi mdi-message-text mr-2"></i>{{__('send_message')}}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="messageForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="message-recipients">{{__('recipients')}}:</label>
                        <div id="message-recipients" class="border rounded p-2 bg-light">
                            <!-- Recipients will be populated here -->
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message-content">{{__('message')}}:</label>
                        <textarea class="form-control" id="message-content" name="message" rows="5" placeholder="{{__('enter_your_message_here')}}"></textarea>
                        <small class="form-text text-muted">
                            <span id="char-count">0</span>/1000 {{__('characters')}}
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="message-product-id">Product to engage:</label>
                        <select class="form-control" id="message-product-id" name="product_id" required>
                            <option value="">Select product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Only contacts associated with this product will receive the message.</small>
                    </div>

                    <div class="form-group" id="product-eligibility-wrapper" style="display: none;">
                        <div class="alert alert-info mb-0" id="product-eligibility-info"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>{{__('attachments')}} ({{__('optional')}}):</label>
                        <div class="file-upload-area border rounded p-3" style="border-style: dashed !important; border-color: #dee2e6;">
                            <div class="text-center">
                                <i class="mdi mdi-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-2">{{__('drag_drop_files_or_click_to_browse')}}</p>
                                <input type="file" id="message-attachments" name="attachments[]" multiple class="d-none" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="$('#message-attachments').click()">
                                    <i class="mdi mdi-attachment mr-1"></i>{{__('choose_files')}}
                                </button>
                            </div>
                            <div id="file-preview" class="mt-3" style="display: none;">
                                <small class="text-muted mb-2 d-block">{{__('selected_files')}}:</small>
                                <div class="row" id="file-list"></div>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="mdi mdi-information mr-1"></i>{{__('supported_formats')}}: {{__('images_videos_audio_documents_max_16mb')}}
                        </small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="schedule-message">
                            <label class="custom-control-label" for="schedule-message">{{__('schedule_message')}}</label>
                        </div>
                    </div>
                    <div class="form-group" id="schedule-datetime" style="display: none;">
                        <label for="schedule-date">{{__('schedule_date_time')}}:</label>
                        <input type="datetime-local" class="form-control" id="schedule-date" name="schedule_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="message-status" class="mr-auto"></div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('cancel')}}</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-send mr-2"></i>{{__('send_message')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                <h5 class="modal-title" id="deleteConfirmModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">
                    <i class="mdi mdi-delete mr-2"></i>{{__('confirm_delete')}}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="mdi mdi-alert-circle text-danger" style="font-size: 3rem;"></i>
                    <h6 class="mt-3" id="delete-message">{{__('are_you_sure_you_want_to_delete')}}</h6>
                    <div id="delete-contact-info" class="mt-2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('cancel')}}</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="mdi mdi-delete mr-2"></i>{{__('yes_delete')}}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Contact management variables
    let selectedContacts = [];
    let currentContactId = null;
    let deleteAction = 'single'; // 'single' or 'bulk'
    let contactsToDelete = [];
    
    // Subscription limits
    const currentContactCount = {{ $total_guests ?? 0 }};
    const maxContacts = {{ $max_contacts ?? 10 }};
    const subscriptionPlan = '{{ $subscription_plan ?? 'trial' }}';
    
    // Message pagination variables
    let currentContactMessages = [];
    let messageOffset = 0;
    const messagesPerPage = 3;

    // Check contact limit before adding new contact
    function checkContactLimitBeforeAdd() {
        if (currentContactCount >= maxContacts) {
            // Show upgrade modal from checkpayment.blade.php
            const planNames = {
                'trial': 'Trial',
                'starter': 'Starter',
                'pro': 'Pro',
                'premium': 'Premium'
            };
            
            const limitMessage = `You've reached your contact limit (${maxContacts} contacts) for the ${planNames[subscriptionPlan]} plan. Upgrade to add more contacts.`;
            
            if (window.pricingControls) {
                window.pricingControls.showModal('Add Contacts', limitMessage, false);
            } else if (typeof window.showUpgradeModal === 'function') {
                window.showUpgradeModal('Add Contacts', limitMessage, false);
            } else {
                alert(limitMessage + ' Please visit Settings to upgrade your plan.');
            }
            return false;
        }
        
        // Limit not reached, open add contact modal
        $('#ProfileStep5').attr('action', '<?= url('guest/store/null') ?>');
        $('#myModal').modal('show');
    }
    
    // Initialize contact management features
    $(document).ready(function() {
        initializeContactSelection();
        initializeMessageForm();
        initializeEditFormValidation();
        save_category();
    });
    
    // Initialize edit form validation
    function initializeEditFormValidation() {
        // Clear any existing event handlers to prevent duplicates
        $('#edit_guest_name, #edit_guest_phone, #edit_guest_email, #edit_pledge, #edit_lead_status, #edit_product_ids').off('input blur change');
        
        // Real-time validation as user types
        $('#edit_guest_name').on('input blur', function() {
            if ($(this).val().trim()) {
                validateEditField('edit_guest_name');
            }
        });
        
        $('#edit_guest_phone').on('input blur', function() {
            if ($(this).val().trim()) {
                validateEditField('edit_guest_phone');
            }
        });

        $('#edit_guest_email').on('input blur', function() {
            if ($(this).val().trim()) {
                validateEditField('edit_guest_email');
            }
        });
        
        $('#edit_pledge').on('input blur', function() {
            if ($(this).val()) {
                validateEditField('edit_pledge');
            }
        });
        
        $('#edit_lead_status').on('change', function() {
            validateEditField('edit_lead_status');
        });

        $('#edit_product_ids').on('change', function() {
            validateEditField('edit_product_ids');
        });

        initializeProductSelect();
    }

    function initializeProductSelect() {
        if (!$.fn.select2) {
            return;
        }

        if ($('#edit_product_ids').hasClass('select2-hidden-accessible')) {
            $('#edit_product_ids').select2('destroy');
        }

        $('#edit_product_ids').select2({
            width: '100%',
            placeholder: 'Select products',
            dropdownParent: $('#myModal')
        });
    }
    
    function validateEditField(fieldId) {
        const field = $('#' + fieldId);
        const rawValue = field.val();
        const value = Array.isArray(rawValue) ? rawValue : (rawValue || '').trim();
        let isValid = true;
        let errorMessage = '';
        
        // Clear previous validation state completely
        field.removeClass('is-invalid is-valid');
        field.next('.invalid-feedback').remove();
        field.next('.valid-feedback').remove();
        field.siblings('.invalid-feedback').remove();
        field.siblings('.valid-feedback').remove();
        
        switch(fieldId) {
            case 'edit_guest_name':
                if (!value) {
                    errorMessage = '{{__('name_is_required')}}';
                    isValid = false;
                } else if (value.length < 2) {
                    errorMessage = '{{__('name_must_be_at_least_2_characters')}}';
                    isValid = false;
                } else if (value.length > 100) {
                    errorMessage = '{{__('name_must_not_exceed_100_characters')}}';
                    isValid = false;
                } else if (!/^([a-zA-Z\s\-\'\(\)]*)$/.test(value)) {
                    errorMessage = '{{__('name_can_only_contain_letters_spaces_hyphens_apostrophes_and_parentheses')}}';
                    isValid = false;
                }
                break;
                
            case 'edit_guest_phone':
                if (!value) {
                    errorMessage = '{{__('phone_number_is_required')}}';
                    isValid = false;
                } else {
                    // Use intlTelInput validation if available
                    if (phoneInput && typeof phoneInput.isValidNumber === 'function') {
                        if (!phoneInput.isValidNumber()) {
                            errorMessage = 'Please enter a valid phone number with country code';
                            isValid = false;
                        }
                    } else {
                        // Fallback validation
                        if (value.length < 4) {
                            errorMessage = '{{__('phone_number_must_be_at_least_4_digits')}}';
                            isValid = false;
                        } else if (value.length > 30) {
                            errorMessage = '{{__('phone_number_must_not_exceed_30_digits')}}';
                            isValid = false;
                        } else if (!/^[0-9+\-\s\(\)]*$/.test(value)) {
                            errorMessage = '{{__('phone_number_can_only_contain_numbers_and_basic_formatting')}}';
                            isValid = false;
                        }
                    }
                }
                break;

            case 'edit_guest_email':
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    errorMessage = 'Please enter a valid email address';
                    isValid = false;
                }
                break;
                
            case 'edit_pledge':
                if (value && (isNaN(value) || parseFloat(value) < 0)) {
                    errorMessage = '{{__('pledge_must_be_a_positive_number')}}';
                    isValid = false;
                }
                break;
                
            case 'edit_lead_status':
                const validStatuses = ['NEW', 'OUTREACHED', 'REPLIED', 'ENGAGED', 'QUALIFIED', 'PITCHED', 'DEMO_SCHEDULED', 'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST', 'HANDED_OFF', 'DO_NOT_CONTACT','CHURNED'];
                if (!validStatuses.includes(value)) {
                    errorMessage = '{{__('please_select_a_valid_lead_status')}}';
                    isValid = false;
                }
                break;

            case 'edit_product_ids':
                if (!Array.isArray(value) || value.length === 0) {
                    errorMessage = 'Please select at least one product';
                    isValid = false;
                }
                break;
        }
        
        if (isValid) {
            field.addClass('is-valid');
            // Don't show success message for phone fields to avoid clutter
            if (fieldId !== 'edit_guest_phone') {
                field.after('<div class="valid-feedback d-block"><i class="fas fa-check-circle"></i> Looks good!</div>');
            }
        } else {
            field.addClass('is-invalid');
            if (fieldId === 'edit_product_ids') {
                field.parent().append('<div class="invalid-feedback d-block"><i class="fas fa-exclamation-circle"></i> ' + errorMessage + '</div>');
            } else {
                field.after('<div class="invalid-feedback d-block"><i class="fas fa-exclamation-circle"></i> ' + errorMessage + '</div>');
            }
        }
        
        return isValid;
    }

    // Contact Selection Functions
    function initializeContactSelection() {
        // Select All checkbox
        $('#select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.contact-checkbox').prop('checked', isChecked);
            updateSelectedContacts();
        });

        // Individual contact checkboxes
        $('.contact-checkbox').on('change', function() {
            updateSelectedContacts();
            updateSelectAllState();
        });

        // Bulk action buttons
        $('#bulk-send-message').on('click', function() {
            if (selectedContacts.length > 0) {
                openSendMessageModal(selectedContacts);
            }
        });

        $('#bulk-delete').on('click', function() {
            if (selectedContacts.length > 0) {
                confirmBulkDelete(selectedContacts);
            }
        });

        $('#clear-selection').on('click', function() {
            clearSelection();
        });
    }

    function updateSelectedContacts() {
        selectedContacts = [];
        $('.contact-checkbox:checked').each(function() {
            selectedContacts.push(parseInt($(this).val()));
        });
        
        updateBulkActionsBar();
    }

    function updateSelectAllState() {
        const totalCheckboxes = $('.contact-checkbox').length;
        const checkedCheckboxes = $('.contact-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all').prop('indeterminate', true);
        }
    }

    function updateBulkActionsBar() {
        if (selectedContacts.length > 0) {
            $('#bulk-actions-bar').show();
            $('#selected-count').text(selectedContacts.length);
        } else {
            $('#bulk-actions-bar').hide();
        }
    }

    function clearSelection() {
        $('.contact-checkbox').prop('checked', false);
        $('#select-all').prop('checked', false).prop('indeterminate', false);
        selectedContacts = [];
        updateBulkActionsBar();
    }

    // Contact View Functions
    function viewContact(contactId) {
        currentContactId = contactId;
        
        // Get contact details
        $.ajax({
            url: '{{ url("guest/getContactDetails") }}/' + contactId,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    const contact = response.contact;
                    $('#view-contact-name').text(contact.guest_name);
                    $('#view-contact-phone').text(contact.guest_phone);
                    $('#view-contact-email').text(contact.guest_email || '{{__("not_provided")}}');
                    $('#view-contact-group').text(contact.category_name || '{{__("no_group")}}');
                    $('#view-contact-date').text(new Date(contact.created_at).toLocaleDateString());
                    
                    // Load messages and conversation summary
                    loadContactMessages(contactId);
                    loadConversationSummary(contactId);
                    
                    $('#contactViewModal').modal('show');
                } else {
                    alert('{{__("failed_to_load_contact_details")}}');
                }
            },
            error: function() {
                alert('{{__("error_loading_contact_details")}}');
            }
        });
    }

    function loadConversationSummary(contactId) {
        $('#conversation-summary').html('<div class="text-center text-muted"><i class="mdi mdi-loading mdi-spin"></i> {{__('loading_conversation_summary')}}</div>');
        
        $.ajax({
            url: '{{ url('guest/getConversationSummary') }}/' + contactId,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    displayConversationSummary(response.summary);
                } else {
                    $('#conversation-summary').html('<div class="text-center text-muted">{{__('no_conversation_summary_available')}}</div>');
                }
            },
            error: function() {
                $('#conversation-summary').html('<div class="text-center text-danger">{{__('error_loading_conversation_summary')}}</div>');
            }
        });
    }

    function displayConversationSummary(summary) {
        if (!summary) {
            $('#conversation-summary').html('<div class="text-center text-muted">{{__('no_conversation_data_available')}}</div>');
            return;
        }

        let summaryHtml = '<div class="conversation-summary-content">';
        
        // Display conversation overview
        if (summary.overview) {
            summaryHtml += `
                <div class="mb-3">
                    <h6 class="text-primary"><i class="mdi mdi-chart-line mr-1"></i>{{__('conversation_overview')}}</h6>
                    <div class="bg-light p-3 rounded">
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted">{{__('total_messages')}}</small>
                                <div class="font-weight-bold">${summary.overview.total_messages || 0}</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">{{__('last_interaction')}}</small>
                                <div class="font-weight-bold">${summary.overview.last_interaction ? new Date(summary.overview.last_interaction).toLocaleDateString() : '{{__('never')}}'}</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">{{__('conversation_stage')}}</small>
                                <div class="font-weight-bold">${summary.overview.stage || '{{__('unknown')}}'}</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">{{__('ai_responses')}}</small>
                                <div class="font-weight-bold">${summary.overview.ai_responses || 0}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Display key topics discussed
        if (summary.key_topics && summary.key_topics.length > 0) {
            summaryHtml += `
                <div class="mb-3">
                    <h6 class="text-info"><i class="mdi mdi-tag-multiple mr-1"></i>{{__('key_topics_discussed')}}</h6>
                    <div class="d-flex flex-wrap">
            `;
            
            summary.key_topics.forEach(function(topic) {
                summaryHtml += `<span class="badge badge-info mr-1 mb-1">${topic}</span>`;
            });
            
            summaryHtml += '</div></div>';
        }
        
        // Display conversation context (AI summary)
        if (summary.ai_context) {
            summaryHtml += `
                <div class="mb-3">
                    <h6 class="text-success"><i class="mdi mdi-robot mr-1"></i>{{__('ai_conversation_context')}}</h6>
                    <div class="border-left border-success pl-3">
                        <div class="text-muted" style="white-space: pre-wrap;">${summary.ai_context}</div>
                    </div>
                </div>
            `;
        }
        
        // Display recent activity
        if (summary.recent_activity && summary.recent_activity.length > 0) {
            summaryHtml += `
                <div class="mb-3">
                    <h6 class="text-warning"><i class="mdi mdi-clock mr-1"></i>{{__('recent_activity')}}</h6>
                    <div class="timeline">
            `;
            
            summary.recent_activity.forEach(function(activity, index) {
                const activityDate = new Date(activity.date).toLocaleDateString();
                const activityTime = new Date(activity.date).toLocaleTimeString();
                summaryHtml += `
                    <div class="timeline-item ${index === 0 ? 'latest' : ''}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <strong>${activity.action}</strong>
                                <small class="text-muted">${activityDate} ${activityTime}</small>
                            </div>
                            ${activity.description ? '<p class="mb-0 text-muted">' + activity.description + '</p>' : ''}
                        </div>
                    </div>
                `;
            });
            
            summaryHtml += '</div></div>';
        }
        
        summaryHtml += '</div>';
        
        // Add custom styles for timeline
        summaryHtml += `
            <style>
                .timeline {
                    position: relative;
                    padding-left: 20px;
                }
                .timeline-item {
                    position: relative;
                    padding-bottom: 15px;
                }
                .timeline-item:not(:last-child)::before {
                    content: '';
                    position: absolute;
                    left: -15px;
                    top: 20px;
                    height: calc(100% - 10px);
                    width: 2px;
                    background: #dee2e6;
                }
                .timeline-marker {
                    position: absolute;
                    left: -19px;
                    top: 5px;
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background: #6c757d;
                    border: 2px solid #fff;
                }
                .timeline-item.latest .timeline-marker {
                    background: #28a745;
                    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
                }
                .timeline-content {
                    background: #f8f9fa;
                    padding: 12px 15px;
                    border-radius: 8px;
                    border-left: 3px solid #dee2e6;
                    transition: all 0.3s ease;
                }
                .timeline-content:hover {
                    transform: translateX(3px);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .timeline-content strong {
                    font-weight: 600;
                    font-size: 0.95rem;
                }
                .timeline-content p {
                    font-size: 0.9rem;
                    line-height: 1.5;
                }
            </style>
        `;
        
        $('#conversation-summary').html(summaryHtml);
    }

    function loadContactMessages(contactId) {
        $('#contact-messages').html('<div class="text-center text-muted"><i class="mdi mdi-loading mdi-spin"></i> {{__("loading_messages")}}</div>');
        messageOffset = 0; // Reset offset
        currentContactMessages = []; // Reset messages array
        
        $.ajax({
            url: '{{ url("guest/getConversations") }}/' + contactId + '?limit=' + messagesPerPage + '&offset=' + messageOffset,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.conversations && response.conversations.length > 0) {
                    currentContactMessages = response.conversations;
                    displayMessages(currentContactMessages, response.has_more || false);
                    messageOffset += messagesPerPage;
                } else {
                    $('#contact-messages').html('<div class="text-center text-muted">{{__("no_messages_found")}}</div>');
                }
            },
            error: function() {
                $('#contact-messages').html('<div class="text-center text-danger">{{__("error_loading_messages")}}</div>');
            }
        });
    }

    function displayMessages(conversations, hasMore = false) {
        if (conversations.length === 0) {
            $('#contact-messages').html('<div class="text-center text-muted">{{__("no_messages_found")}}</div>');
            return;
        }

        let messagesHtml = '<div class="conversations-list">';
        conversations.forEach(function(conversation) {
            const messageDate = new Date(conversation.timestamp || conversation.created_at).toLocaleDateString();
            const messageTime = new Date(conversation.timestamp || conversation.created_at).toLocaleTimeString();
            const isOutgoing = conversation.sender_type === 'staff' || conversation.sender_type === 'system' || conversation.sender_type === 'ai';
            const senderClass = isOutgoing ? 'outgoing' : 'incoming';
            const alignClass = isOutgoing ? 'ml-auto' : 'mr-auto';
            
            // Clean and format message content
            let messageContent = conversation.message_content || 'No content';
            // Convert HTML line breaks to proper breaks but escape other HTML
            messageContent = messageContent.replace(/<br\s*\/?>/gi, '\n').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
            
            messagesHtml += `
                <div class="message-item">
                    <div class="message-bubble ${alignClass} p-3">
                        <div class="message-content">
                            <p class="mb-1">${messageContent}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="message-meta">${conversation.sender_type || 'unknown'} • ${messageDate} ${messageTime}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        // Add load more button if there are more messages
        if (hasMore) {
            messagesHtml += `
                <div class="text-center mt-3">
                    <button id="load-more-btn" class="btn btn-outline-primary btn-sm" onclick="loadMoreMessages(currentContactId)">
                        <i class="mdi mdi-chevron-down mr-1"></i>{{__("load_more_messages")}}
                    </button>
                </div>
            `;
        }
        
        messagesHtml += '</div>';
        $('#contact-messages').html(messagesHtml);
        
        // Scroll to bottom to show most recent messages
        const conversationsList = $('.conversations-list');
        if (conversationsList.length) {
            conversationsList.scrollTop(conversationsList[0].scrollHeight);
        }
    }

    function loadMoreMessages(contactId) {
        $('#load-more-btn').html('<i class="mdi mdi-loading mdi-spin"></i> {{__("loading")}}').prop('disabled', true);
        
        $.ajax({
            url: '{{ url("guest/getConversations") }}/' + contactId + '?limit=' + messagesPerPage + '&offset=' + messageOffset,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.conversations && response.conversations.length > 0) {
                    // Append new messages to existing ones
                    currentContactMessages = currentContactMessages.concat(response.conversations);
                    displayMessages(currentContactMessages, response.has_more || false);
                    messageOffset += messagesPerPage;
                } else {
                    $('#load-more-btn').hide();
                }
            },
            error: function() {
                $('#load-more-btn').html('{{__("error_loading_more")}}').prop('disabled', false);
            }
        });
    }

    // Message Functions
    function sendMessageToContact(contactId) {
        openSendMessageModal([contactId]);
    }

    function openSendMessageModal(contactIds) {
        // Get contact details for recipients display
        const recipientNames = [];
        contactIds.forEach(function(id) {
            const name = $('#guest_name' + id).text();
            const phone = $('#guest_phone' + id).text();
            recipientNames.push(`${name} (${phone})`);
        });
        
        $('#message-recipients').html(recipientNames.map(name => 
            `<span class="badge badge-primary mr-1 mb-1">${name}</span>`
        ).join(''));
        
        // Store contact IDs for sending
        $('#messageForm').data('contactIds', contactIds);
        
        // Clear form
        $('#message-content').val('');
        $('#message-product-id').val('');
        $('#product-eligibility-wrapper').hide();
        $('#product-eligibility-info').html('');
        $('#message-attachments').val('');
        $('#file-preview').hide();
        $('#file-list').empty();
        $('#schedule-message').prop('checked', false);
        $('#schedule-datetime').hide();
        $('#message-status').html('');
        updateCharCount();
        
        $('#sendMessageModal').modal('show');
    }

    function initializeMessageForm() {
        // Character count
        $('#message-content').on('input', updateCharCount);

        $('#message-product-id').on('change', function() {
            updateProductEligibilityPreview();
        });
        
        // File upload handling
        $('#message-attachments').on('change', handleFileSelection);
        
        // Drag and drop functionality
        $('.file-upload-area').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('border-primary bg-light');
        });
        
        $('.file-upload-area').on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('border-primary bg-light');
        });
        
        $('.file-upload-area').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('border-primary bg-light');
            
            const files = e.originalEvent.dataTransfer.files;
            $('#message-attachments')[0].files = files;
            handleFileSelection();
        });
        
        // Schedule checkbox
        $('#schedule-message').on('change', function() {
            if ($(this).is(':checked')) {
                $('#schedule-datetime').show();
                // Set minimum date to current time
                const now = new Date();
                const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                $('#schedule-date').attr('min', localDateTime);
            } else {
                $('#schedule-datetime').hide();
            }
        });
        
        // Form submission
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    function getContactProductIds(contactId) {
        const productText = ($('#guest_product_ids' + contactId).text() || '').trim();
        if (!productText) {
            return [];
        }

        return productText
            .split(',')
            .map(function(value) { return parseInt(value.trim(), 10); })
            .filter(function(value) { return !isNaN(value); });
    }

    function updateProductEligibilityPreview() {
        const selectedProductId = parseInt($('#message-product-id').val(), 10);
        const contactIds = $('#messageForm').data('contactIds') || [];

        if (!selectedProductId || contactIds.length === 0) {
            $('#product-eligibility-wrapper').hide();
            $('#product-eligibility-info').html('');
            return;
        }

        let eligibleCount = 0;

        contactIds.forEach(function(contactId) {
            const productIds = getContactProductIds(contactId);
            if (productIds.includes(selectedProductId)) {
                eligibleCount++;
            }
        });

        const ineligibleCount = contactIds.length - eligibleCount;
        let infoHtml = '<strong>Delivery preview:</strong> ' + eligibleCount + ' of ' + contactIds.length + ' selected contact(s) are associated with this product.';

        if (ineligibleCount > 0) {
            infoHtml += ' <span class="text-warning">' + ineligibleCount + ' contact(s) will be skipped.</span>';
        }

        $('#product-eligibility-info').html(infoHtml);
        $('#product-eligibility-wrapper').show();
    }

    // File handling functions
    function handleFileSelection() {
        const files = $('#message-attachments')[0].files;
        if (files.length > 0) {
            displayFilePreview(files);
            $('#file-preview').show();
        } else {
            $('#file-preview').hide();
        }
    }

    function displayFilePreview(files) {
        const fileList = $('#file-list');
        fileList.empty();
        
        Array.from(files).forEach(function(file, index) {
            // Validate file size (16MB limit)
            if (file.size > 16 * 1024 * 1024) {
                alert('{{__("file_too_large")}}: ' + file.name + ' ({{__("max_16mb")}})');
                return;
            }
            
            const fileSize = formatFileSize(file.size);
            const fileType = getFileType(file.type, file.name);
            const fileIcon = getFileIcon(fileType);
            
            const filePreview = `
                <div class="col-md-6 mb-2">
                    <div class="card card-body p-2">
                        <div class="d-flex align-items-center">
                            <i class="${fileIcon} mr-2" style="font-size: 1.5rem;"></i>
                            <div class="flex-grow-1">
                                <div class="file-name text-truncate" title="${file.name}">
                                    <strong>${file.name}</strong>
                                </div>
                                <small class="text-muted">${fileType} • ${fileSize}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger ml-2" onclick="removeFile(${index})">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            fileList.append(filePreview);
        });
    }

    function removeFile(index) {
        const files = $('#message-attachments')[0].files;
        const dataTransfer = new DataTransfer();
        
        Array.from(files).forEach(function(file, i) {
            if (i !== index) {
                dataTransfer.items.add(file);
            }
        });
        
        $('#message-attachments')[0].files = dataTransfer.files;
        handleFileSelection();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileType(mimeType, fileName) {
        if (mimeType.startsWith('image/')) return '{{__("image")}}';
        if (mimeType.startsWith('video/')) return '{{__("video")}}';
        if (mimeType.startsWith('audio/')) return '{{__("audio")}}';
        if (mimeType.includes('pdf')) return 'PDF';
        if (mimeType.includes('word') || fileName.endsWith('.doc') || fileName.endsWith('.docx')) return 'Word';
        if (mimeType.includes('excel') || fileName.endsWith('.xls') || fileName.endsWith('.xlsx')) return 'Excel';
        if (mimeType.includes('powerpoint') || fileName.endsWith('.ppt') || fileName.endsWith('.pptx')) return 'PowerPoint';
        if (fileName.endsWith('.txt')) return 'Text';
        if (fileName.endsWith('.zip') || fileName.endsWith('.rar')) return 'Archive';
        return '{{__("document")}}';
    }

    function getFileIcon(fileType) {
        switch(fileType.toLowerCase()) {
            case '{{__("image")}}': return 'mdi mdi-image text-success';
            case '{{__("video")}}': return 'mdi mdi-video text-primary';
            case '{{__("audio")}}': return 'mdi mdi-music text-info';
            case 'pdf': return 'mdi mdi-file-pdf text-danger';
            case 'word': return 'mdi mdi-file-word text-primary';
            case 'excel': return 'mdi mdi-file-excel text-success';
            case 'powerpoint': return 'mdi mdi-file-powerpoint text-warning';
            case 'text': return 'mdi mdi-file-document-outline text-secondary';
            case 'archive': return 'mdi mdi-archive text-warning';
            default: return 'mdi mdi-file text-secondary';
        }
    }

    function updateCharCount() {
        const content = $('#message-content').val();
        $('#char-count').text(content.length);
        
        if (content.length > 1000) {
            $('#char-count').parent().addClass('text-danger');
        } else if (content.length > 800) {
            $('#char-count').parent().addClass('text-warning').removeClass('text-danger');
        } else {
            $('#char-count').parent().removeClass('text-warning text-danger');
        }
    }

    function sendMessage() {
        const contactIds = $('#messageForm').data('contactIds');
        const message = $('#message-content').val();
        const productId = $('#message-product-id').val();
        const scheduleDate = $('#schedule-message').is(':checked') ? $('#schedule-date').val() : null;
        const files = $('#message-attachments')[0].files;

        if (!productId) {
            alert('Please select a product before sending this message.');
            return;
        }

        updateProductEligibilityPreview();

        const eligibilityText = $('#product-eligibility-info').text() || '';
        if (eligibilityText.includes('0 of')) {
            alert('None of the selected contacts are associated with this product. Please choose another product or update contact product allocation.');
            return;
        }
        
        if (!message.trim() && files.length === 0) {
            alert('{{__("please_enter_a_message_or_select_files")}}');
            return;
        }
        
        $('#message-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__("sending_message")}}</div>');
        
        // Create FormData for file upload support
        const formData = new FormData();
        formData.append('contact_ids', JSON.stringify(contactIds));
        formData.append('message', message);
        formData.append('product_id', productId);
        if (scheduleDate) {
            formData.append('schedule_date', scheduleDate);
        }
        
        // Add files to FormData
        Array.from(files).forEach(function(file, index) {
            formData.append('attachments[]', file);
        });
        
        $.ajax({
            url: '{{ url("guest/sendMessage") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#message-status').html('<div class="alert alert-success">{{__("message_sent_successfully")}}</div>');
                    setTimeout(function() {
                        $('#sendMessageModal').modal('hide');
                        clearSelection();
                    }, 2000);
                } else {
                    $('#message-status').html('<div class="alert alert-danger">{{__("failed_to_send_message")}}: ' + (response.message || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                $('#message-status').html('<div class="alert alert-danger">{{__("error_sending_message")}}</div>');
            }
        });
    }

    // Delete Functions
    function confirmDelete(contactId) {
        currentContactId = contactId;
        deleteAction = 'single';
        contactsToDelete = [contactId];
        
        const contactName = $('#guest_name' + contactId).text();
        const contactPhone = $('#guest_phone' + contactId).text();
        
        $('#delete-message').text('{{__("are_you_sure_you_want_to_delete_this_contact")}}');
        $('#delete-contact-info').html(`
            <div class="alert alert-warning">
                <strong>${contactName}</strong><br>
                <small>${contactPhone}</small>
            </div>
        `);
        
        $('#deleteConfirmModal').modal('show');
    }

    function confirmBulkDelete(contactIds) {
        deleteAction = 'bulk';
        contactsToDelete = contactIds;
        
        $('#delete-message').text('{{__("are_you_sure_you_want_to_delete_selected_contacts")}}');
        $('#delete-contact-info').html(`
            <div class="alert alert-warning">
                <strong>${contactIds.length} {{__("contacts_will_be_deleted")}}</strong>
            </div>
        `);
        
        $('#deleteConfirmModal').modal('show');
    }

    $('#confirm-delete-btn').on('click', function() {
        if (deleteAction === 'single') {
            deleteSingleContact(currentContactId);
        } else {
            deleteBulkContacts(contactsToDelete);
        }
    });

    function deleteSingleContact(contactId) {
        $.ajax({
            url: '<?= url("guest/destroy") ?>/' + contactId,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteConfirmModal').modal('hide');
                    var $row = $('#guest_name' + contactId).closest('tr');
                    $row.css({'background-color': '#f8d7da', 'opacity': '0.6'});
                    $row.fadeOut(500, function() {
                        $('#datatable-buttons').DataTable().ajax.reload(null, false);
                    });
                    showSuccessMessage('{{__("contact_deleted_successfully")}}');
                } else {
                    alert('{{__("failed_to_delete_contact")}}');
                }
            },
            error: function() {
                alert('{{__("error_deleting_contact")}}');
            }
        });
    }

    function deleteBulkContacts(contactIds) {
        $.ajax({
            url: '<?= url("guest/bulkDelete") ?>',
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                contact_ids: contactIds
            }),
            success: function(response) {
                if (response.success) {
                    $('#deleteConfirmModal').modal('hide');
                    var remaining = contactIds.length;
                    contactIds.forEach(function(id) {
                        var $row = $('#guest_name' + id).closest('tr');
                        $row.css({'background-color': '#f8d7da', 'opacity': '0.6'});
                        $row.fadeOut(500, function() {
                            remaining--;
                            if (remaining === 0) {
                                $('#datatable-buttons').DataTable().ajax.reload(null, false);
                            }
                        });
                    });
                    clearSelection();
                    showSuccessMessage(`${response.deleted_count} {{__("contacts_deleted_successfully")}}`);
                } else {
                    alert('{{__("failed_to_delete_contacts")}}');
                }
            },
            error: function() {
                alert('{{__("error_deleting_contacts")}}');
            }
        });
    }

    // Helper Functions
    function sendMessageFromView() {
        $('#contactViewModal').modal('hide');
        sendMessageToContact(currentContactId);
    }

    function editFromView() {
        $('#contactViewModal').modal('hide');
        editGuest(currentContactId);
    }

    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle mr-2"></i>${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('.card-body').prepend(alertHtml);
        
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 3000);
    }

    // Original functions (updated)
    function editGuest(a) {
        // Set modal title for editing
        $('#contactModalTitle').text('{{__('edit_guest_details')}}');
        
        $('#edit_guest_name').val($('#guest_name' + a).text());
        
        // Store phone number for later setting after intl input is initialized
        window.currentPhoneNumber = $('#guest_phone' + a).text();
        $('#edit_guest_email').val($('#guest_email' + a).text().trim());
        
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        
        // Set lead status from hidden span
        const leadStatus = $('#guest_lead_status' + a).text().trim();
        $('#edit_lead_status').val(leadStatus);

        const productIds = ($('#guest_product_ids' + a).text().trim() || '')
            .split(',')
            .map(function(value) { return value.trim(); })
            .filter(function(value) { return value !== ''; });
        initializeProductSelect();
        $('#edit_product_ids').val(productIds).trigger('change');
        
        $('#edit_guest').val(a);
        $('#ProfileStep5').attr('action', '<?= url('guest/edit/null') ?>');
        
        // Clear previous validation messages
        clearEditValidationMessages();
        
        $('#myModal').modal('show');
    }

    // Edit Form Validation Functions
    function validateEditForm() {
        let isValid = true;
        clearEditValidationMessages();
        
        // Validate guest name
        const name = $('#edit_guest_name').val().trim();
        if (!name) {
            showEditValidationError('edit_guest_name', '{{__('name_is_required')}}');
            isValid = false;
        } else if (name.length < 2) {
            showEditValidationError('edit_guest_name', '{{__('name_must_be_at_least_2_characters')}}');
            isValid = false;
        } else if (name.length > 100) {
            showEditValidationError('edit_guest_name', '{{__('name_must_not_exceed_100_characters')}}');
            isValid = false;
        } else if (!/^([a-zA-Z\s\-\'\(\)]*)$/.test(name)) {
            showEditValidationError('edit_guest_name', '{{__('name_can_only_contain_letters_spaces_hyphens_apostrophes_and_parentheses')}}');
            isValid = false;
        }
        
        // Validate phone number
        const phone = $('#edit_guest_phone').val().trim();
        if (!phone) {
            showEditValidationError('edit_guest_phone', '{{__('phone_number_is_required')}}');
            isValid = false;
        } else if (phone.length < 4) {
            showEditValidationError('edit_guest_phone', '{{__('phone_number_must_be_at_least_4_digits')}}');
            isValid = false;
        } else if (phone.length > 30) {
            showEditValidationError('edit_guest_phone', '{{__('phone_number_must_not_exceed_30_digits')}}');
            isValid = false;
        } else if (!/^[0-9+\-\s\(\)]*$/.test(phone)) {
            showEditValidationError('edit_guest_phone', '{{__('phone_number_can_only_contain_numbers_and_basic_formatting')}}');
            isValid = false;
        }

        // Validate email (optional)
        const email = $('#edit_guest_email').val().trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showEditValidationError('edit_guest_email', 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate pledge (if present)
        const pledge = $('#edit_pledge').val();
        if (pledge && (isNaN(pledge) || pledge < 0)) {
            showEditValidationError('edit_pledge', '{{__('pledge_must_be_a_positive_number')}}');
            isValid = false;
        }
        
        // Validate lead status
        const leadStatus = $('#edit_lead_status').val();
        const validStatuses = ['NEW', 'OUTREACHED', 'REPLIED', 'ENGAGED', 'QUALIFIED', 'PITCHED', 'DEMO_SCHEDULED', 'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST', 'HANDED_OFF', 'DO_NOT_CONTACT','CHURNED'];
        if (!leadStatus || !validStatuses.includes(leadStatus)) {
            showEditValidationError('edit_lead_status', '{{__('please_select_a_valid_lead_status')}}');
            isValid = false;
        }

        const productIds = $('#edit_product_ids').val();
        if (!Array.isArray(productIds) || productIds.length === 0) {
            showEditValidationError('edit_product_ids', 'Please select at least one product');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showEditValidationError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.next('.invalid-feedback').remove();
        
        // Add new error message
        if (fieldId === 'edit_product_ids') {
            field.parent().append('<div class="invalid-feedback d-block">' + message + '</div>');
        } else {
            field.after('<div class="invalid-feedback">' + message + '</div>');
        }
    }
    
    function clearEditValidationMessages() {
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#edit-form-status').html('');
    }
    
    // Handle edit form submission
    function handleEditFormSubmission() {
        if (!validateEditForm()) {
            return false;
        }
        
        const guestId = $('#edit_guest').val();
        const isEditMode = guestId && guestId !== '';
        
        const formData = {
            guest_name: $('#edit_guest_name').val().trim(),
            guest_phone: getFullPhoneNumber(),
            guest_email: $('#edit_guest_email').val().trim(),
            lead_status: $('#edit_lead_status').val(),
            product_ids: $('#edit_product_ids').val(),
            _token: '{{ csrf_token() }}'
        };
        
        // Include pledge if it exists
        const pledge = $('#edit_pledge').val();
        if (pledge) {
            formData.guest_pledge = pledge;
        }
        
        // Determine URL and loading message based on mode
        const url = isEditMode ? '{{ url('guest/edit') }}/' + guestId : '{{ url('guest/store') }}';
        const loadingMessage = isEditMode ? '{{__('updating_contact')}}' : 'Creating contact...';
        const loadingButtonText = isEditMode ? '{{__('updating')}}' : 'Creating...';
        
        // Show loading state
        $('#edit-form-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>' + loadingMessage + '</div>');
        $('#edit-submit-btn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-2"></i>' + loadingButtonText);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#edit-form-status').html('<div class="alert alert-success"><i class="mdi mdi-check mr-2"></i>' + response.message + '</div>');
                    
                    if (isEditMode) {
                        // Update the table row with new data
                        $('#guest_name' + guestId).text(formData.guest_name);
                        $('#guest_phone' + guestId).text(formData.guest_phone);
                        $('#guest_lead_status' + guestId).text(formData.lead_status);
                        if (formData.guest_pledge) {
                            $('#guest_pledge' + guestId).text(formData.guest_pledge);
                        }
                    }
                    
                    // Refresh the page to update the table
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $('#myModal').modal('hide');
                        showSuccessMessage(response.message || (isEditMode ? '{{__('contact_updated_successfully')}}' : 'Contact created successfully'));
                    }, 1500);
                } else {
                    $('#edit-form-status').html('<div class="alert alert-danger"><i class="mdi mdi-alert mr-2"></i>' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                let errorMessage = isEditMode ? '{{__('failed_to_update_contact')}}' : 'Failed to create contact';
                let alertClass = 'alert-danger';
                let icon = 'mdi-alert';
                
                if (xhr.responseJSON) {
                    // Check for duplicate contact error
                    if (xhr.responseJSON.error_type === 'duplicate_contact') {
                        errorMessage = '<strong>Duplicate Contact Found</strong><br>' + xhr.responseJSON.message;
                        if (xhr.responseJSON.existing_contact) {
                            const contact = xhr.responseJSON.existing_contact;
                            errorMessage += '<br><br><strong>Existing Contact:</strong><br>';
                            errorMessage += 'Name: ' + contact.name + '<br>';
                            errorMessage += 'Phone: ' + contact.phone;
                            errorMessage += '<br><br><a href="#" onclick="editGuest(' + contact.id + ')" class="btn btn-sm btn-primary">Edit Existing Contact</a>';
                        }
                        alertClass = 'alert-warning';
                        icon = 'mdi-alert-circle';
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        // Handle validation errors from server
                        const errors = xhr.responseJSON.errors;
                        errorMessage = '<strong>Validation Errors:</strong><br>' + Object.values(errors).flat().join('<br>');
                    }
                }
                
                // Show specific error based on status code
                if (xhr.status === 404) {
                    errorMessage = 'Contact not found or you do not have permission to edit it.';
                    icon = 'mdi-account-remove';
                } else if (xhr.status === 409) {
                    // Conflict - duplicate contact
                    alertClass = 'alert-warning';
                    icon = 'mdi-account-multiple-check';
                } else if (xhr.status === 422) {
                    // Validation error
                    icon = 'mdi-alert-circle';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again or contact support.';
                    icon = 'mdi-server-network-off';
                }
                
                $('#edit-form-status').html('<div class="alert ' + alertClass + '"><i class="mdi ' + icon + ' mr-2"></i>' + errorMessage + '</div>');
            },
            complete: function() {
                $('#edit-submit-btn').prop('disabled', false).html('{{__('save')}}');
            }
        });
        
        return false; // Prevent default form submission
    }

    save_category = function () {
        $('#new_category').mousedown(function () {
            var val = $('#new_category_value').val();
            if ($.trim(val) == '') {
                $('#error_message').html('This field is required').addClass('alert alert-danger');
            } else {
                $.ajax({
                    type: 'POST',
                    url: "<?= url('guest/addguestcategory') ?>",
                    data: {"name": val},
                    dataType: "html",
                    success: function (data) {
                        $('#append_option').html(data);
                    }
                });
            }
        });
    }

    load_contact = function () {
        $.getJSON('https://www.google.com/m8/feeds/contacts/default/full/?access_token=' +
                authResult.access_token + "&alt=json&callback=?", function (result) {
                    console.log(JSON.stringify(result));
                });
    }
    //  $(document).ready(load_contact);
</script>

<!-- Handoff Management Modal -->
<div class="modal fade" id="handoffModal" tabindex="-1" role="dialog" aria-labelledby="handoffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-bottom: none !important; border-radius: 12px 12px 0 0 !important; padding: 20px 24px !important;">
                <h5 class="modal-title" id="handoffModalLabel" style="color: #ffffff !important; font-size: 1.25rem !important; font-weight: 600 !important;">
                    <i class="mdi mdi-account-supervisor-circle mr-2"></i>{{__('handoff_management')}}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff !important; opacity: 0.9 !important; text-shadow: none !important;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Guest Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card" style="border-left: 4px solid #667eea;">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="mdi mdi-account mr-2"></i>{{__('customer_information')}}</h6>
                                <div id="guest-info">
                                    <p class="mb-1"><strong>{{__('name')}}:</strong> <span id="modal-guest-name"></span></p>
                                    <p class="mb-1"><strong>{{__('phone')}}:</strong> <span id="modal-guest-phone"></span></p>
                                    <p class="mb-0"><strong>{{__('current_status')}}:</strong> <span id="modal-guest-status"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Handoff Actions Tabs -->
                <ul class="nav nav-tabs mb-3" id="handoffTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="request-tab" data-toggle="tab" href="#request-handoff" role="tab">
                            <i class="mdi mdi-hand-pointing-up mr-1"></i>{{__('request_handoff')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="assign-tab" data-toggle="tab" href="#assign-agent" role="tab">
                            <i class="mdi mdi-account-plus mr-1"></i>{{__('assign_agent')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="notes-tab" data-toggle="tab" href="#handoff-notes" role="tab">
                            <i class="mdi mdi-note-text mr-1"></i>{{__('notes')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="actions-tab" data-toggle="tab" href="#handoff-actions" role="tab">
                            <i class="mdi mdi-cogs mr-1"></i>{{__('actions')}}
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="handoffTabContent">
                    <!-- Request Handoff Tab -->
                    <div class="tab-pane fade show active" id="request-handoff" role="tabpanel">
                        <form id="requestHandoffForm">
                            <input type="hidden" id="request-guest-id" name="guest_id">
                            <div class="form-group">
                                <label for="handoff-reason">{{__('reason_for_handoff')}}</label>
                                <textarea class="form-control" id="handoff-reason" name="reason" rows="3" 
                                         placeholder="{{__('explain_why_handoff_needed')}}" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="priority-level">{{__('priority_level')}}</label>
                                <select class="form-control" id="priority-level" name="priority_level" required>
                                    <option value="3">{{__('low')}}</option>
                                    <option value="2">{{__('medium')}}</option>
                                    <option value="1">{{__('high')}}</option>
                                    <option value="4">{{__('urgent')}}</option>
                                    <option value="5">{{__('critical')}}</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="mdi mdi-hand-pointing-up mr-1"></i>{{__('request_handoff')}}
                            </button>
                        </form>
                    </div>

                    <!-- Assign Agent Tab -->
                    <div class="tab-pane fade" id="assign-agent" role="tabpanel">
                        <form id="assignAgentForm">
                            <input type="hidden" id="assign-guest-id" name="guest_id">
                            <div class="form-group">
                                <label for="assigned-agent">{{__('select_agent')}}</label>
                                <select class="form-control" id="assigned-agent" name="agent_id" required>
                                    <option value="">{{__('select_agent')}}</option>
                                    @foreach($available_agents ?? [] as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assignment-notes">{{__('assignment_notes')}}</label>
                                <textarea class="form-control" id="assignment-notes" name="notes" rows="3" 
                                         placeholder="{{__('optional_notes_for_agent')}}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="mdi mdi-account-check mr-1"></i>{{__('assign_agent')}}
                            </button>
                        </form>
                    </div>

                    <!-- Handoff Notes Tab -->
                    <div class="tab-pane fade" id="handoff-notes" role="tabpanel">
                        <div id="existing-notes" class="mb-3">
                            <h6>{{__('existing_notes')}}</h6>
                            <div class="border rounded p-3" style="min-height: 100px; background-color: #f8f9fa;">
                                <span id="notes-content" class="text-muted">{{__('no_notes_available')}}</span>
                            </div>
                        </div>
                        <form id="addNotesForm">
                            <input type="hidden" id="notes-guest-id" name="guest_id">
                            <div class="form-group">
                                <label for="new-notes">{{__('add_new_notes')}}</label>
                                <textarea class="form-control" id="new-notes" name="notes" rows="3" 
                                         placeholder="{{__('add_notes_about_handoff')}}" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary">
                                <i class="mdi mdi-note-plus mr-1"></i>{{__('add_notes')}}
                            </button>
                        </form>
                    </div>

                    <!-- Handoff Actions Tab -->
                    <div class="tab-pane fade" id="handoff-actions" role="tabpanel">
                        <input type="hidden" id="actions-guest-id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-success">
                                            <i class="mdi mdi-check-circle mr-2"></i>{{__('complete_handoff')}}
                                        </h6>
                                        <p class="card-text small">{{__('mark_handoff_as_completed')}}</p>
                                        <button class="btn btn-success btn-sm" onclick="completeHandoff()">
                                            <i class="mdi mdi-check mr-1"></i>{{__('complete')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-primary">
                                            <i class="mdi mdi-robot mr-2"></i>{{__('return_to_ai')}}
                                        </h6>
                                        <p class="card-text small">{{__('return_customer_to_ai_handling')}}</p>
                                        <button class="btn btn-primary btn-sm" onclick="returnToAI()">
                                            <i class="mdi mdi-robot mr-1"></i>{{__('return_to_ai')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Priority Update -->
                        <div class="card border-warning">
                            <div class="card-body">
                                <h6 class="card-title text-warning">
                                    <i class="mdi mdi-priority-high mr-2"></i>{{__('update_priority')}}
                                </h6>
                                <form id="updatePriorityForm" class="form-inline">
                                    <input type="hidden" id="priority-guest-id" name="guest_id">
                                    <select class="form-control mr-2" id="new-priority" name="priority_level" required>
                                        <option value="3">{{__('low')}}</option>
                                        <option value="2">{{__('medium')}}</option>
                                        <option value="1">{{__('high')}}</option>
                                        <option value="4">{{__('urgent')}}</option>
                                        <option value="5">{{__('critical')}}</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="mdi mdi-update mr-1"></i>{{__('update')}}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handoff Management JavaScript Functions

let currentGuestId = null;

function openHandoffModal(guestId) {
    currentGuestId = guestId;
    
    // Set guest IDs in all forms
    document.getElementById('request-guest-id').value = guestId;
    document.getElementById('assign-guest-id').value = guestId;
    document.getElementById('notes-guest-id').value = guestId;
    document.getElementById('actions-guest-id').value = guestId;
    document.getElementById('priority-guest-id').value = guestId;
    
    // Load guest information
    loadGuestInfo(guestId);
    
    // Show modal
    $('#handoffModal').modal('show');
}

function loadGuestInfo(guestId) {
    $.ajax({
        url: `{{ route('guest.getContactDetails', '') }}/${guestId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const guest = response.contact;
                document.getElementById('modal-guest-name').textContent = guest.guest_name;
                document.getElementById('modal-guest-phone').textContent = guest.guest_phone;
                // You can add more guest info display here
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading guest info:', error);
        }
    });
}

// Request Handoff Form
document.getElementById('requestHandoffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("guest.requestHandoff") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('{{__("handoff_requested_successfully")}}');
                location.reload();
            } else {
                alert('{{__("error")}}: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('{{__("error")}}: ' + error);
        }
    });
});

// Assign Agent Form
document.getElementById('assignAgentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("guest.assignAgent") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('{{__("agent_assigned_successfully")}}');
                location.reload();
            } else {
                alert('{{__("error")}}: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('{{__("error")}}: ' + error);
        }
    });
});

// Add Notes Form
document.getElementById('addNotesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("guest.addHandoffNotes") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('{{__("notes_added_successfully")}}');
                // Optionally reload notes display
                document.getElementById('new-notes').value = '';
            } else {
                alert('{{__("error")}}: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('{{__("error")}}: ' + error);
        }
    });
});

// Update Priority Form
document.getElementById('updatePriorityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("guest.updatePriority") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('{{__("priority_updated_successfully")}}');
                location.reload();
            } else {
                alert('{{__("error")}}: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('{{__("error")}}: ' + error);
        }
    });
});

function completeHandoff() {
    if (!currentGuestId) return;
    
    if (confirm('{{__("are_you_sure_complete_handoff")}}')) {
        $.ajax({
            url: '{{ route("guest.completeHandoff") }}',
            method: 'POST',
            data: {
                guest_id: currentGuestId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('{{__("handoff_completed_successfully")}}');
                    location.reload();
                } else {
                    alert('{{__("error")}}: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('{{__("error")}}: ' + error);
            }
        });
    }
}

function returnToAI() {
    if (!currentGuestId) return;
    
    if (confirm('{{__("are_you_sure_return_to_ai")}}')) {
        $.ajax({
            url: '{{ route("guest.returnToAI") }}',
            method: 'POST',
            data: {
                guest_id: currentGuestId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('{{__("returned_to_ai_successfully")}}');
                    location.reload();
                } else {
                    alert('{{__("error")}}: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('{{__("error")}}: ' + error);
            }
        });
    }
}

// Handoff Status Filter Tabs + Server-Side DataTables Initialisation
$(document).ready(function() {

    // ---------------------------------------------------------------
    // Server-side DataTables — loads all contacts via AJAX pagination
    // so there is no 1000-row limit and all contacts are accessible.
    // ---------------------------------------------------------------
    var activeHandoffFilter = 'all';
    var activeProductFilter = '';

    var contactsTable = $('#datatable-buttons').DataTable({
        processing  : true,
        serverSide  : true,
        ajax        : {
            url  : '{{ route("guest.getData") }}',
            type : 'GET',
            data : function (d) {
                d.handoff_filter = activeHandoffFilter;
                d.product_id = activeProductFilter;
            }
        },
        columns     : [
            { orderable: false },   // 0 checkbox
            { orderable: true  },   // 1 #
            { orderable: true  },   // 2 Name
            { orderable: true  },   // 3 Phone
            { orderable: true  },   // 4 Added On
            { orderable: false },   // 5 Lead Status
            { orderable: false },   // 6 Products
            { orderable: true  },   // 7 Handoff
            { orderable: true  },   // 8 Priority
            { orderable: false },   // 9 Assigned Agent
            { orderable: false },   // 10 Actions
        ],
        order       : [[1, 'desc']],
        pageLength  : 25,
        lengthMenu  : [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language    : {
            processing : '<i class="mdi mdi-loading mdi-spin mr-1"></i> Loading contacts…',
            emptyTable : 'No contacts found.',
            zeroRecords: 'No contacts match your search.',
        },
    });

    // Handoff tab clicks — pass filter param to AJAX and redraw
    const filterTabs = document.querySelectorAll('#handoff-tabs .nav-link');

    filterTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            filterTabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');

            activeHandoffFilter = this.getAttribute('data-status');
            contactsTable.ajax.reload();
        });
    });

    $('#product-filter').on('change', function() {
        activeProductFilter = $(this).val();
        contactsTable.ajax.reload();
    });

    // Add hover effects to tabs
    filterTabs.forEach(function(tab) {
        tab.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.background = 'rgba(255,255,255,0.2)';
            }
        });
        tab.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.background = '';
            }
        });
    });

    // Style the initially active tab
    var activeTab = document.querySelector('#handoff-tabs .nav-link.active');
    if (activeTab) {
        activeTab.style.background = 'rgba(255,255,255,0.3)';
    }

    // Select-all checkbox — works with current page rows
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('.contact-checkbox').forEach(function(cb) {
            cb.checked = document.getElementById('select-all').checked;
        });
        updateBulkActionsBar();
    });
});
</script>

<!-- International Telephone Input CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

<script>
// Initialize International Telephone Input on modal phone field
let phoneInput;

$(document).ready(function() {
    // Initialize phone input after modal is shown
    $('#myModal').on('shown.bs.modal', function() {
        // Clear any previous validation messages
        $('.valid-feedback, .invalid-feedback').remove();
        $('.form-control').removeClass('is-valid is-invalid');
        
        if (phoneInput) {
            phoneInput.destroy();
        }
        
        const input = document.querySelector("#edit_guest_phone");
        if (input) {
            phoneInput = window.intlTelInput(input, {
                initialCountry: "tz", // Default to Tanzania
                preferredCountries: ["tz", "ke", "ug", "rw", "bi"],
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                separateDialCode: true,
                autoPlaceholder: "polite",
                formatOnDisplay: true
            });
            
            // Set the phone number if available
            if (window.currentPhoneNumber) {
                phoneInput.setNumber(window.currentPhoneNumber);
                window.currentPhoneNumber = null; // Clear after setting
            }
        }
    });
    
    // Clean up when modal is hidden
    $('#myModal').on('hidden.bs.modal', function() {
        // Clear validation messages on close
        $('.valid-feedback, .invalid-feedback').remove();
        $('.form-control').removeClass('is-valid is-invalid');
        
        if (phoneInput) {
            phoneInput.destroy();
            phoneInput = null;
        }
    });
});

// Update form submission to get full international number
function getFullPhoneNumber() {
    if (phoneInput && phoneInput.isValidNumber()) {
        return phoneInput.getNumber();
    }
    return $('#edit_guest_phone').val();
}
</script>

@endsection