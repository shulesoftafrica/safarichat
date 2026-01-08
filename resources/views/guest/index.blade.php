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
.badge-orange { background-color: #fd7e14; }
.badge-purple { background-color: #6f42c1; }
.badge-teal { background-color: #20c997; }
.badge-indigo { background-color: #6610f2; }

/* International Telephone Input Styles */
.iti { width: 100%; }

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
    color: #e2e8f0 !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.05) !important;
}

.dark-mode .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.075) !important;
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
    color: #48bb78 !important;
    border-color: #48bb78 !important;
}

.dark-mode .btn-outline-success:hover {
    background-color: #48bb78 !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-primary {
    color: #63b3ed !important;
    border-color: #63b3ed !important;
}

.dark-mode .btn-outline-primary:hover {
    background-color: #63b3ed !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-info {
    color: #63b3ed !important;
    border-color: #63b3ed !important;
}

.dark-mode .btn-outline-info:hover {
    background-color: #63b3ed !important;
    color: #ffffff !important;
}

.dark-mode .btn-outline-secondary {
    color: #a0aec0 !important;
    border-color: #a0aec0 !important;
}

.dark-mode .btn-outline-secondary:hover {
    background-color: #a0aec0 !important;
    color: #1a1f2e !important;
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
    background-color: #63b3ed !important;
    color: #1a1f2e !important;
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

.dark-mode .input-group .form-control:not(:first-child) {
    border-left: 0;
}
</style>
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{__('home')}}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{__('category')}}</a></li>
                        <li class="breadcrumb-item active">{{__('guests')}}</li>
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

                    <h4 class="mt-0 header-title">{{__('list_of_guests')}} <span class="badge badge-primary" id="total-contacts">{{ $total_guests ?? 0 }}</span></h4>
                    <p class="text-muted mb-3">{{__('manage_list_of_guests')}}</p>
                    
                    <!-- Bulk Actions Bar -->
                    <div id="bulk-actions-bar" class="alert alert-primary" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-check-circle mr-2"></i>
                                <span id="selected-count">0</span> {{__('contacts_selected')}}
                            </div>
                            <div>
                                <button type="button" class="btn btn-success btn-sm mr-2" id="bulk-send-message">
                                    <i class="mdi mdi-message-text mr-1"></i>{{__('send_message')}}
                                </button>
                                <button type="button" class="btn btn-danger btn-sm mr-2" id="bulk-delete">
                                    <i class="mdi mdi-delete mr-1"></i>{{__('delete_selected')}}
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-selection">
                                    <i class="mdi mdi-close mr-1"></i>{{__('clear_selection')}}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Handoff Management Tabs -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px;">
                                <div class="card-body py-3">
                                    <h5 class="text-white mb-3"><i class="mdi mdi-account-supervisor-circle mr-2"></i>{{__('handoff_management')}}</h5>
                                    
                                    <!-- Status Filter Tabs -->
                                    <ul class="nav nav-pills nav-fill" id="handoff-tabs" style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 5px;">
                                        <li class="nav-item">
                                            <a class="nav-link active text-white" data-status="all" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-view-dashboard mr-1"></i>{{__('all')}}
                                                <span class="badge badge-light ml-2">{{ $total_guests ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="ai" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-robot mr-1"></i>{{__('ai_handling')}}
                                                <span class="badge badge-light ml-2">{{ $handoff_stats['ai_handled'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="pending_handoff" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-clock-outline mr-1"></i>{{__('pending_handoff')}}
                                                <span class="badge badge-warning ml-2">{{ $handoff_stats['pending_handoff'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="handed_off" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-account-check mr-1"></i>{{__('handed_off')}}
                                                <span class="badge badge-info ml-2">{{ $handoff_stats['handed_off'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="completed" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-check-circle mr-1"></i>{{__('completed')}}
                                                <span class="badge badge-success ml-2">{{ $handoff_stats['completed'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" data-status="urgent" href="#" style="border-radius: 8px; transition: all 0.3s ease;">
                                                <i class="mdi mdi-alert mr-1"></i>{{__('urgent')}}
                                                <span class="badge badge-danger ml-2">{{ $handoff_stats['urgent_cases'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p>  
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#myModal" onclick=" $('#ProfileStep5').attr('action', '<?= url('guest/store/null') ?>');">
                          {{__('add_new_guest')}}  
                        </button>
                        <a href="#" class="btn btn-outline-success ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#myUploadModal" title="{{__('upload_excel')}}">
                            <i class="mdi mdi-file-excel-box" style="font-size: 1.2em; margin-right: 6px;"></i>
                            {{__('upload_excel')}}
                        </a>

                        <button type="button" class="btn btn-outline-primary ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#whatsappSyncModal">
                            <i class="mdi mdi-whatsapp" style="font-size: 1.2em; margin-right: 6px;"></i>
                            {{__('sync_from_whatsapp')}}
                        </button>

                        <button type="button" class="btn btn-outline-info ml-2" style="display: inline-flex; align-items: center;" data-toggle="modal" data-target="#googleSyncModal">
                            <i class="mdi mdi-google" style="font-size: 1.2em; margin-right: 6px; color: #4285f4;"></i>
                            {{__('sync_from_google')}}
                        </button>

                        <!-- WhatsApp Sync Modal -->
                        <div class="modal fade planner-modal-bx" id="whatsappSyncModal" tabindex="-1" role="dialog" aria-labelledby="whatsappSyncModalLabel" aria-hidden="true" style="display: none;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content start-here">
                                    <div class="modal-header">
                                        <h5 class="modal-title mt-0" id="whatsappSyncModalLabel">{{__('sync_whatsapp_contacts')}}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>{{__('connect_whatsapp_to_import_contacts')}}</p>
                                        <div id="whatsapp-sync-status" class="mb-2"></div>
                                        <button type="button" class="btn btn-success" id="startWhatsappSync">
                                            <i class="mdi mdi-whatsapp"></i> {{__('start_sync')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Google Contacts Sync Modal -->
                        <div class="modal fade planner-modal-bx" id="googleSyncModal" tabindex="-1" role="dialog" aria-labelledby="googleSyncModalLabel" aria-hidden="true" style="display: none;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content start-here">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); color: white;">
                                        <h5 class="modal-title mt-0" id="googleSyncModalLabel">
                                            <i class="mdi mdi-google mr-2"></i>{{__('sync_google_contacts')}}
                                        </h5>
                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-3">
                                            <i class="mdi mdi-google" style="font-size: 4rem; color: #4285f4;"></i>
                                        </div>
                                        <p class="text-center">{{__('sync_contacts_from_google_account')}}</p>
                                        <p class="text-muted small text-center">{{__('secure_google_oauth_process')}}</p>
                                        
                                        <div id="google-sync-status" class="mb-3"></div>
                                        
                                        <div class="text-center">
                                            <button type="button" class="btn btn-primary btn-lg" id="startGoogleAuth" style="background: #4285f4; border-color: #4285f4; padding: 12px 30px; border-radius: 25px;">
                                                <i class="mdi mdi-google mr-2"></i> {{__('sign_in_with_google')}}
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="mdi mdi-information"></i>
                                                {{__('google_contacts_sync_info')}}:
                                                <ul class="mt-2 mb-0">
                                                    <li>{{__('secure_oauth_authentication')}}</li>
                                                    <li>{{__('read_only_access_to_contacts')}}</li>
                                                    <li>{{__('no_passwords_stored')}}</li>
                                                    <li>{{__('automatic_duplicate_prevention')}}</li>
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
                                $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__("initializing_google_auth")}}</div>');
                                
                                if (!gapi_inited || !gsi_inited) {
                                    initializeGoogleAPI();
                                    setTimeout(() => {
                                        if (gapi_inited && gsi_inited) {
                                            requestGoogleAuth();
                                        } else {
                                            $('#google-sync-status').html('<div class="alert alert-danger">{{__("failed_to_initialize_google_api")}}</div>');
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
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{__("failed_to_start_google_auth")}}</div>');
                                }
                            }
                            
                            // Handle authentication response
                            async function handleAuthResponse(resp) {
                                if (resp.error !== undefined) {
                                    console.error('Google Auth Error:', resp.error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{__("google_auth_failed")}}: ' + resp.error + '</div>');
                                    return;
                                }
                                
                                $('#google-sync-status').html('<div class="alert alert-success"><i class="mdi mdi-check mr-2"></i>{{__("google_auth_successful_fetching_contacts")}}</div>');
                                
                                try {
                                    await fetchGoogleContacts();
                                } catch (error) {
                                    console.error('Error fetching contacts:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{__("failed_to_fetch_google_contacts")}}</div>');
                                }
                            }
                            
                            // Fetch Google Contacts
                            async function fetchGoogleContacts() {
                                try {
                                    $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__("fetching_contacts_from_google")}}</div>');
                                    
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
                                        $('#google-sync-status').html('<div class="alert alert-warning">{{__("no_contacts_found_in_google_account")}}</div>');
                                    }
                                    
                                } catch (error) {
                                    console.error('Error fetching Google contacts:', error);
                                    $('#google-sync-status').html('<div class="alert alert-danger">{{__("error_fetching_google_contacts")}}: ' + error.message + '</div>');
                                }
                            }
                            
                            // Process and import Google contacts
                            function processGoogleContacts(contacts) {
                                $('#google-sync-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__("processing_contacts_for_import")}}</div>');
                                
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
                                    $('#google-sync-status').html('<div class="alert alert-warning">{{__("no_contacts_with_phone_numbers_found")}}</div>');
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
                                                '{{__("google_contacts_imported_successfully")}}: ' + 
                                                (response.imported_count || 0) + ' {{__("contacts_imported")}}' +
                                                '</div>'
                                            );
                                            
                                            // Reload page after 3 seconds
                                            setTimeout(function() {
                                                location.reload();
                                            }, 3000);
                                        } else {
                                            $('#google-sync-status').html('<div class="alert alert-danger">{{__("failed_to_import_google_contacts")}}: ' + (response.message || 'Unknown error') + '</div>');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Google contacts import failed:', error);
                                        $('#google-sync-status').html('<div class="alert alert-danger">{{__("failed_to_import_google_contacts")}}: Import request failed</div>');
                                    }
                                });
                            }
                            
                            // Initialize when modal is shown
                            $('#googleSyncModal').on('shown.bs.modal', function() {
                                if (typeof gapi === 'undefined' || typeof google === 'undefined') {
                                    $('#google-sync-status').html('<div class="alert alert-warning">{{__("loading_google_apis")}}</div>');
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
                                $('#whatsapp-sync-status').html('<span class="text-info">{{__("syncing_contacts_please_wait")}}</span>');
                                
                                // Get user's WhatsApp instance directly from backend
                                @php
                                    $whatsappInstance = Auth::user()->whatsappInstance();
                                   
                                @endphp
                                
                                @if($whatsappInstance)
                                    // User has a WhatsApp instance, proceed with sync
                                    var instanceId = '{{ $whatsappInstance->instance_id }}';
                                    var connectStatus = '{{ $whatsappInstance->connect_status }}';
                                    
                                    if (connectStatus === 'ready') {
                                        syncContactsFromWAAPI(instanceId);
                                    } else {
                                        $('#whatsapp-sync-status').html('<span class="text-warning">{{__("whatsapp_instance_not_connected_please_connect_first")}}</span>');
                                    }
                                @else
                                    // No WhatsApp instance found
                                    $('#whatsapp-sync-status').html('<span class="text-danger">{{__("no_whatsapp_instance_found_please_setup_first")}}</span>');
                                @endif
                            });
                            
                            function syncContactsFromWAAPI(instanceId) {
                                console.log('Syncing contacts for instance:', instanceId);
                                
                                $.ajax({
                                    url: 'https://waapi.app/api/v1/instances/' + instanceId + '/client/action/get-contacts',
                                    method: 'POST',
                                    headers: {
                                        'Authorization': 'Bearer {{ config("app.waapi_token", "ftXEQe1S8hncxJVzHRrc3JqB9eHqUmG6WIctlMPy8435fd42") }}',
                                        'Content-Type': 'application/json'
                                    },
                                    data: JSON.stringify({}),
                                    success: function (data) {
                                        console.log('Contacts sync response:', data);
                                        
                                        if (data.data && data.data.length > 0) {
                                            // Process and save contacts to backend
                                            $.ajax({
                                                url: '<?= url("guest/importWhatsappContacts") ?>',
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Content-Type': 'application/json'
                                                },
                                                data: JSON.stringify({
                                                    contacts: data.data,
                                                    instance_id: instanceId
                                                }),
                                                success: function(response) {
                                                    console.log('Import response:', response);
                                                    
                                                    if (response.success) {
                                                        $('#whatsapp-sync-status').html('<span class="text-success">{{__("contacts_synced_successfully")}}: ' + (response.imported_count || 0) + ' contacts imported</span>');
                                                        
                                                        // Reload page after 2 seconds to show new contacts
                                                        setTimeout(function() {
                                                            location.reload();
                                                        }, 2000);
                                                    } else {
                                                        $('#whatsapp-sync-status').html('<span class="text-danger">{{__("failed_to_import_contacts")}}: ' + (response.message || 'Unknown error') + '</span>');
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    console.error('Import failed:', error);
                                                    $('#whatsapp-sync-status').html('<span class="text-danger">{{__("failed_to_import_contacts")}}: Import request failed</span>');
                                                }
                                            });
                                        } else {
                                            $('#whatsapp-sync-status').html('<span class="text-warning">{{__("no_contacts_found_in_whatsapp")}}</span>');
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        console.error('WAAPI contacts request failed:', {
                                            status: xhr.status,
                                            statusText: xhr.statusText,
                                            responseText: xhr.responseText,
                                            error: error
                                        });
                                        
                                        let errorMessage = '{{__("failed_to_sync_contacts")}}';
                                        if (xhr.status === 401) {
                                            errorMessage = '{{__("authentication_failed_check_waapi_token")}}';
                                        } else if (xhr.status === 404) {
                                            errorMessage = '{{__("instance_not_found_or_not_connected")}}';
                                        } else if (xhr.status === 405) {
                                            errorMessage = '{{__("method_not_allowed_api_endpoint_issue")}}';
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
                        <table class="table table-bordered dataTable" id="datatable-buttons">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="select-all">
                                            <label class="custom-control-label" for="select-all"></label>
                                        </div>
                                    </th>
                                    <th>#</th>
                                    <th>{{__('name')}}</th>
                                    <th>{{__('phone')}}</th>
                                    <!--<th>{{__('email')}} </th>-->
                                    <th>{{__('date')}}</th>
                                    <th>{{__('lead_status')}}</th>
                                    <th>{{__('handoff_status')}}</th>
                                    <th>{{__('priority')}}</th>
                                    <th>{{__('assigned_agent')}}</th>
                                    <th name="buttons">{{__('action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                              
                                $total_pledge = 0;
                                $i = 1;
                                foreach ($guests as $guest) {
                                    $total_pledge += $guest->guest_pledge;
                                    ?>
                                    <tr data-handoff-status="{{ $guest->handoff_status ?? 'ai' }}" data-priority="{{ $guest->priority_level ?? 3 }}">
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input contact-checkbox" id="checkbox-<?= $guest->id ?>" value="<?= $guest->id ?>">
                                                <label class="custom-control-label" for="checkbox-<?= $guest->id ?>"></label>
                                            </div>
                                        </td>
                                        <td>{{$i}}</td>
                                        <td><span id="guest_name<?= $guest->id ?>">{{$guest->guest_name}}</span></td>
                                        <td><span id="guest_phone<?= $guest->id ?>">{{$guest->guest_phone}}</span></td>
                                        <!--<td>{{$guest->guest_email}}</td>-->
                                        <td>{{date('d M Y',strtotime($guest->created_at))}}</td>
                                        <td>
                                            @php
                                                $leadStatus = $guest->lead ? $guest->lead->status : 'NEW';
                                                $statusColors = [
                                                    'NEW' => 'secondary',
                                                    'OUTREACHED' => 'info',
                                                    'REPLIED' => 'primary',
                                                    'ENGAGED' => 'success',
                                                    'QUALIFIED' => 'warning',
                                                    'PITCHED' => 'orange',
                                                    'DEMO_SCHEDULED' => 'purple',
                                                    'PROPOSAL_SENT' => 'teal',
                                                    'NEGOTIATING' => 'indigo',
                                                    'CLOSED' => 'success',
                                                    'LOST' => 'danger',
                                                    'HANDED_OFF' => 'info',
                                                    'DO_NOT_CONTACT' => 'dark',
                                                    'NEEDS_ATTENTION' => 'warning',
                                                    'CONVERTED' => 'success',
                                                    'CHURNED' => 'danger'
                                                ];
                                                $statusIcons = [
                                                    'NEW' => 'account-plus',
                                                    'OUTREACHED' => 'send',
                                                    'REPLIED' => 'reply',
                                                    'ENGAGED' => 'account-heart',
                                                    'QUALIFIED' => 'account-check',
                                                    'PITCHED' => 'presentation',
                                                    'DEMO_SCHEDULED' => 'calendar-clock',
                                                    'PROPOSAL_SENT' => 'file-document',
                                                    'NEGOTIATING' => 'handshake',
                                                    'CLOSED' => 'check-circle',
                                                    'LOST' => 'close-circle',
                                                    'HANDED_OFF' => 'account-arrow-right',
                                                    'DO_NOT_CONTACT' => 'account-cancel',
                                                    'NEEDS_ATTENTION' => 'alert',
                                                    'CONVERTED' => 'trophy',
                                                    'CHURNED' => 'account-remove'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $statusColors[$leadStatus] ?? 'secondary' }}" style="font-size: 0.8em; padding: 5px 8px; min-width: 90px; text-align: center;">
                                                <i class="mdi mdi-{{ $statusIcons[$leadStatus] ?? 'help' }} mr-1"></i>
                                                {{ $statusLabels[$leadStatus] ?? $leadStatus }}
                                            </span>
                                            <span id="guest_lead_status<?= $guest->id ?>" style="display:none;">{{ $leadStatus }}</span>
                                        </td>
                                        
                                        <!-- Handoff Status Column -->
                                        <td>
                                            @php
                                                $handoffStatus = $guest->handoff_status ?? 'ai';
                                                $statusColors = [
                                                    'ai' => 'primary',
                                                    'pending_handoff' => 'warning',
                                                    'handed_off' => 'info',
                                                    'completed' => 'success'
                                                ];
                                                $statusIcons = [
                                                    'ai' => 'robot',
                                                    'pending_handoff' => 'clock-outline',
                                                    'handed_off' => 'account-check',
                                                    'completed' => 'check-circle'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $statusColors[$handoffStatus] ?? 'secondary' }}" style="font-size: 0.85em; padding: 6px 10px;">
                                                <i class="mdi mdi-{{ $statusIcons[$handoffStatus] ?? 'help' }} mr-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $handoffStatus)) }}
                                            </span>
                                        </td>
                                        
                                        <!-- Priority Column -->
                                        <td>
                                            @php
                                                $priority = $guest->priority_level ?? 3;
                                                $priorityLabels = [1 => 'High', 2 => 'Medium', 3 => 'Low', 4 => 'Urgent', 5 => 'Critical'];
                                                $priorityColors = [1 => 'warning', 2 => 'info', 3 => 'secondary', 4 => 'danger', 5 => 'dark'];
                                            @endphp
                                            <span class="badge badge-{{ $priorityColors[$priority] ?? 'secondary' }}" style="font-size: 0.75em;">
                                                {{ $priorityLabels[$priority] ?? 'Unknown' }}
                                            </span>
                                        </td>
                                        
                                        <!-- Assigned Agent Column -->
                                        <td>
                                            @if($guest->assignedAgent)
                                                <span class="text-success">
                                                    <i class="mdi mdi-account-check mr-1"></i>
                                                    {{ $guest->assignedAgent->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    <i class="mdi mdi-account-off mr-1"></i>
                                                    {{__('unassigned')}}
                                                </span>
                                            @endif
                                        </td>
                                        
                                        <td name="buttons">
                                            <a onclick="viewContact('<?= $guest->id ?>')" class="btn btn-info btn-sm" title="{{__('view_contact')}}">
                                                <i class="las la-eye"></i>
                                            </a>
                                            <a onclick="sendMessageToContact('<?= $guest->id ?>')" class="btn btn-success btn-sm" title="{{__('send_message')}}">
                                                <i class="las la-comment"></i>
                                            </a>
                                            <!-- Handoff Management Button -->
                                            <button onclick="openHandoffModal('<?= $guest->id ?>')" class="btn btn-primary btn-sm" title="{{__('manage_handoff')}}">
                                                <i class="mdi mdi-account-supervisor"></i>
                                            </button>
                                            <a onclick="editGuest('<?= $guest->id ?>')" data-toggle="modal" href="#myModal" class="btn btn-warning btn-sm" title="{{__('edit')}}">
                                                <i class="las la-pen"></i>
                                            </a>
                                            <a onclick="confirmDelete('<?= $guest->id ?>')" class="btn btn-danger btn-sm" title="{{__('delete')}}">
                                                <i class="las la-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                                ?>
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


</div>

<div class="modal fade planner-modal-bx" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="<?= url('guest/store') ?>" method="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{__('edit_guest_details')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('guest_name')}}</label>
                        <input type="text" 
                               name="guest_name" 
                               id="edit_guest_name" 
                               class="form-control" 
                               placeholder="Name" 
                               pattern="^[a-zA-Z\s\-']+$"
                               title="Only letters, spaces, hyphens and apostrophes allowed"
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s\-']/g, '')"
                               required="">
                    </div>

                    <div class="form-group">
                        <label for="edit_guest_phone" class="col-form-label text-right">{{__('phone')}}</label>
                        <input type="tel" 
                               name="guest_phone"
                               id="edit_guest_phone"
                               class="form-control"
                               placeholder="Enter phone number"
                               required="">
                        <small class="form-text text-muted">
                            {{__('enter_phone_with_country_code')}} (e.g. +255 712345678)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="edit_lead_status" class="col-form-label text-right">{{__('lead_status')}}</label>
                        <select class="form-control" name="lead_status" id="edit_lead_status">
                            <option value="">{{__('select_lead_status')}}</option>
                            <option value="NEW">{{__('New')}}</option>
                            <option value="OUTREACHED">{{__('Outreached')}}</option>
                            <option value="REPLIED">{{__('Replied')}}</option>
                            <option value="ENGAGED">{{__('Engaged')}}</option>
                            <option value="QUALIFIED">{{__('Qualified')}}</option>
                            <option value="PITCHED">{{__('Pitched')}}</option>
                            <option value="DEMO_SCHEDULED">{{__('Demo Scheduled')}}</option>
                            <option value="PROPOSAL_SENT">{{__('Proposal Sent')}}</option>
                            <option value="NEGOTIATING">{{__('Negotiating')}}</option>
                            <option value="CLOSED">{{__('Closed')}}</option>
                            <option value="LOST">{{__('Lost')}}</option>
                            <option value="HANDED_OFF">{{__('Handed Off')}}</option>
                            <option value="DO_NOT_CONTACT">{{__('Do Not Contact')}}</option>
                            <option value="NEEDS_ATTENTION">{{__('Needs Attention')}}</option>
                            <option value="CONVERTED">{{__('Converted')}}</option>
                            <option value="CHURNED">{{__('Churned')}}</option>
                        </select>
                        <small class="form-text text-muted">
                            {{__('select_appropriate_lead_status_based_on_current_conversation_stage')}}
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_guest" value="" name="id"/>
                <div id="edit-form-status" class="w-100 mb-2"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('close')}}</button>
                <button type="button" id="edit-submit-btn" class="btn btn-success" onclick="handleEditFormSubmission()" data-toggle="tooltip" data-placement="top">{{__('update_contact')}}</button>
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
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{__('upload_guest_details')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center">
                        <span class="mr-2">{{__('download_sample_file')}}</span>
                        <a href="<?= url('storage/uploads/sample.xlsx') ?>" class="btn btn-primary btn-sm font-weight-bold" style="margin-left:10px;">
                            <i class="mdi mdi-download" style="margin-right:5px;"></i>{{__('sample_excel_file')}}
                        </a>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class="col-form-label text-right">{{__('click_to_upload_excel_or_vcf')}}</label>
                        <input type="file" name="file" id="edit_guest_name" class="form-control" accept=".xls,.csv,.xlsx,.vcf" placeholder="File Upload" required="">
                        <small class="form-text text-muted">
                            {{__('supported_formats')}}: .xls, .xlsx, .csv, .vcf
                        </small>
                    </div>
                    <div class="form-group">
                        <a href="#" class="badge badge-info" data-toggle="collapse" data-target="#vcfInstructions" aria-expanded="false" aria-controls="vcfInstructions">
                            <i class="mdi mdi-information-outline"></i> {{__('how_to_export_vcf_from_phone')}}
                        </a>
                        <div class="collapse mt-2" id="vcfInstructions">
                            <div class="card card-body">
                                <strong>{{__('step_by_step_vcf_export_instructions')}}</strong>
                                <ol class="mb-2">
                                    <li>{{__('open_contacts_app_on_your_phone')}}</li>
                                    <li>{{__('go_to_settings_or_manage_contacts')}}</li>
                                    <li>{{__('find_export_option_and_choose_export_to_vcf_file')}}</li>
                                    <li>{{__('save_vcf_file_to_your_phone_storage')}}</li>
                                    <li>{{__('transfer_vcf_file_to_your_computer_if_needed')}}</li>
                                    <li>{{__('click_browse_and_select_vcf_file_to_upload')}}</li>
                                </ol>
                                <small class="text-muted">
                                    {{__('note_vcf_export_steps_may_vary_by_phone_brand')}}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_guest" value="" name="id"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('close')}}</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">{{__('save')}}</button>
            </div>
        </form>


    </div>
</div>

<!-- Contact View Modal -->
<div class="modal fade" id="contactViewModal" tabindex="-1" role="dialog" aria-labelledby="contactViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="contactViewModalLabel">
                    <i class="mdi mdi-account-circle mr-2"></i>{{__('contact_details')}}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Conversation Summary Section -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="mdi mdi-chat-processing mr-2"></i>{{__('conversation_summary')}}</h6>
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
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="sendMessageModalLabel">
                    <i class="mdi mdi-message-text mr-2"></i>{{__('send_message')}}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="mdi mdi-delete mr-2"></i>{{__('confirm_delete')}}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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

    // Initialize contact management features
    $(document).ready(function() {
        initializeContactSelection();
        initializeMessageForm();
        initializeEditFormValidation();
        save_category();
    });
    
    // Initialize edit form validation
    function initializeEditFormValidation() {
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
        
        $('#edit_pledge').on('input blur', function() {
            if ($(this).val()) {
                validateEditField('edit_pledge');
            }
        });
        
        $('#edit_lead_status').on('change', function() {
            validateEditField('edit_lead_status');
        });
    }
    
    function validateEditField(fieldId) {
        const field = $('#' + fieldId);
        const value = field.val().trim();
        let isValid = true;
        let errorMessage = '';
        
        // Clear previous validation state
        field.removeClass('is-invalid is-valid');
        field.next('.invalid-feedback').remove();
        
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
                } else if (value.length < 4) {
                    errorMessage = '{{__('phone_number_must_be_at_least_4_digits')}}';
                    isValid = false;
                } else if (value.length > 30) {
                    errorMessage = '{{__('phone_number_must_not_exceed_30_digits')}}';
                    isValid = false;
                } else if (!/^[0-9+\-\s\(\)]*$/.test(value)) {
                    errorMessage = '{{__('phone_number_can_only_contain_numbers_and_basic_formatting')}}';
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
        }
        
        if (isValid) {
            field.addClass('is-valid');
        } else {
            field.addClass('is-invalid');
            field.after('<div class="invalid-feedback">' + errorMessage + '</div>');
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
            url: '<?= url("guest/getContactDetails") ?>/' + contactId,
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
            url: '<?= url('guest/getConversationSummary') ?>/' + contactId,
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
                }
                .timeline-item.latest .timeline-marker {
                    background: #28a745;
                }
                .timeline-content {
                    background: #f8f9fa;
                    padding: 10px;
                    border-radius: 5px;
                }
            </style>
        `;
        
        $('#conversation-summary').html(summaryHtml);
    }
        $('#contact-messages').html('<div class="text-center text-muted"><i class="mdi mdi-loading mdi-spin"></i> {{__("loading_messages")}}</div>');
        
        $.ajax({
            url: '<?= url("guest/getContactMessages") ?>/' + contactId,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    displayMessages(response.messages);
                } else {
                    $('#contact-messages').html('<div class="text-center text-muted">{{__("no_messages_found")}}</div>');
                }
            },
            error: function() {
                $('#contact-messages').html('<div class="text-center text-danger">{{__("error_loading_messages")}}</div>');
            }
        });
    }

    function displayMessages(messages) {
        if (messages.length === 0) {
            $('#contact-messages').html('<div class="text-center text-muted">{{__("no_messages_found")}}</div>');
            return;
        }

        let messagesHtml = '';
        messages.forEach(function(message) {
            const messageDate = new Date(message.created_at).toLocaleDateString();
            const messageTime = new Date(message.created_at).toLocaleTimeString();
            const statusClass = message.status === 'sent' ? 'success' : 
                               message.status === 'delivered' ? 'info' : 
                               message.status === 'failed' ? 'danger' : 'warning';
            
            messagesHtml += `
                <div class="message-item border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="message-content">
                            <p class="mb-1">${message.message}</p>
                            <small class="text-muted">${messageDate} ${messageTime}</small>
                        </div>
                        <span class="badge badge-${statusClass}">${message.status}</span>
                    </div>
                </div>
            `;
        });
        
        $('#contact-messages').html(messagesHtml);
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
        const scheduleDate = $('#schedule-message').is(':checked') ? $('#schedule-date').val() : null;
        const files = $('#message-attachments')[0].files;
        
        if (!message.trim() && files.length === 0) {
            alert('{{__("please_enter_a_message_or_select_files")}}');
            return;
        }
        
        $('#message-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__("sending_message")}}</div>');
        
        // Create FormData for file upload support
        const formData = new FormData();
        formData.append('contact_ids', JSON.stringify(contactIds));
        formData.append('message', message);
        if (scheduleDate) {
            formData.append('schedule_date', scheduleDate);
        }
        
        // Add files to FormData
        Array.from(files).forEach(function(file, index) {
            formData.append('attachments[]', file);
        });
        
        $.ajax({
            url: '<?= url("guest/sendMessage") ?>',
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
                    $(`#contact-${contactId}`).closest('tr').fadeOut(function() {
                        $(this).remove();
                        updateSelectedContacts();
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
                    contactIds.forEach(function(id) {
                        $(`#contact-${id}`).closest('tr').fadeOut(function() {
                            $(this).remove();
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
        $('#edit_guest_name').val($('#guest_name' + a).text());
        
        // Store phone number for later setting after intl input is initialized
        window.currentPhoneNumber = $('#guest_phone' + a).text();
        
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        
        // Set lead status from hidden span
        const leadStatus = $('#guest_lead_status' + a).text().trim();
        $('#edit_lead_status').val(leadStatus);
        
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
        
        return isValid;
    }
    
    function showEditValidationError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.next('.invalid-feedback').remove();
        
        // Add new error message
        field.after('<div class="invalid-feedback">' + message + '</div>');
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
        const formData = {
            guest_name: $('#edit_guest_name').val().trim(),
            guest_phone: getFullPhoneNumber(),
            lead_status: $('#edit_lead_status').val(),
            _token: '{{ csrf_token() }}'
        };
        
        // Include pledge if it exists
        const pledge = $('#edit_pledge').val();
        if (pledge) {
            formData.guest_pledge = pledge;
        }
        
        // Show loading state
        $('#edit-form-status').html('<div class="alert alert-info"><i class="mdi mdi-loading mdi-spin mr-2"></i>{{__('updating_contact')}}</div>');
        $('#edit-submit-btn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-2"></i>{{__('updating')}}');
        
        $.ajax({
            url: '{{ url('guest/edit') }}/' + guestId,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#edit-form-status').html('<div class="alert alert-success"><i class="mdi mdi-check mr-2"></i>' + response.message + '</div>');
                    
                    // Update the table row with new data
                    $('#guest_name' + guestId).text(formData.guest_name);
                    $('#guest_phone' + guestId).text(formData.guest_phone);
                    $('#guest_lead_status' + guestId).text(formData.lead_status);
                    if (formData.guest_pledge) {
                        $('#guest_pledge' + guestId).text(formData.guest_pledge);
                    }
                    
                    // Refresh the page to update the lead status badge with proper styling
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $('#myModal').modal('hide');
                        showSuccessMessage(response.message || '{{__('contact_updated_successfully')}}');
                    }, 1500);
                } else {
                    $('#edit-form-status').html('<div class="alert alert-danger"><i class="mdi mdi-alert mr-2"></i>' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{__('failed_to_update_contact')}}';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Handle validation errors from server (fallback)
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                
                $('#edit-form-status').html('<div class="alert alert-danger"><i class="mdi mdi-alert mr-2"></i>' + errorMessage + '</div>');
            },
            complete: function() {
                $('#edit-submit-btn').prop('disabled', false).html('{{__('update_contact')}}');
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
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="handoffModalLabel">
                    <i class="mdi mdi-account-supervisor-circle mr-2"></i>{{__('handoff_management')}}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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

// Handoff Status Filter Tabs
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('#handoff-tabs .nav-link');
    const tableRows = document.querySelectorAll('#datatable-buttons tbody tr');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            const filterStatus = this.getAttribute('data-status');
            
            tableRows.forEach(row => {
                const rowStatus = row.getAttribute('data-handoff-status');
                const rowPriority = parseInt(row.getAttribute('data-priority'));
                
                if (filterStatus === 'all') {
                    row.style.display = '';
                } else if (filterStatus === 'urgent') {
                    row.style.display = (rowPriority >= 4) ? '' : 'none';
                } else {
                    row.style.display = (rowStatus === filterStatus) ? '' : 'none';
                }
            });
        });
    });
    
    // Add hover effects to tabs
    filterTabs.forEach(tab => {
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
    
    // Style active tab
    const activeTab = document.querySelector('#handoff-tabs .nav-link.active');
    if (activeTab) {
        activeTab.style.background = 'rgba(255,255,255,0.3)';
    }
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
        if (phoneInput) {
            phoneInput.destroy();
        }
        
        const input = document.querySelector("#edit_guest_phone");
        phoneInput = window.intlTelInput(input, {
            initialCountry: "tz", // Default to Tanzania
            preferredCountries: ["tz", "ke", "ug", "rw", "bi"],
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
        });
        
        // Set the phone number if available
        if (window.currentPhoneNumber) {
            phoneInput.setNumber(window.currentPhoneNumber);
            window.currentPhoneNumber = null; // Clear after setting
        }
    });
    
    // Clean up when modal is hidden
    $('#myModal').on('hidden.bs.modal', function() {
        if (phoneInput) {
            phoneInput.destroy();
            phoneInput = null;
        }
    });
});

// Update form submission to get full international number
function getFullPhoneNumber() {
    if (phoneInput) {
        return phoneInput.getNumber();
    }
    return $('#edit_guest_phone').val();
}
</script>

@endsection