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
         
                                    <th>{{__('group')}}</th>
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
                                    <tr>
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
                                        <td>{{isset($guest->eventGuestCategory->name) ?$guest->eventGuestCategory->name:''}}</td>
                                        <td name="buttons">
                                            <a onclick="viewContact('<?= $guest->id ?>')" class="btn btn-info btn-sm" title="{{__('view_contact')}}">
                                                <i class="las la-eye"></i>
                                            </a>
                                            <a onclick="sendMessageToContact('<?= $guest->id ?>')" class="btn btn-success btn-sm" title="{{__('send_message')}}">
                                                <i class="las la-comment"></i>
                                            </a>
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
                        <label for="quantity" class="col-form-label text-right">{{__('phone')}}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select class="form-control" name="country_code" id="country_code" style="max-width: 100px;">
                                    @foreach(\App\Models\Country::orderBy('name')->get() as $country)
                                        <option value="+{{ $country->phonecode }}">
                                            +{{ $country->name }} 
                                        </option>
                                    @endforeach
                                    <!-- Add more country codes as needed -->
                                </select>
                            </div>
                            <input type="text"
                                   name="guest_phone"
                                   id="edit_guest_phone"
                                   class="form-control"
                                   placeholder="7XXXXXXXX"
                                   pattern="[0-9]{7,15}"
                                   title="Enter phone number without country code, numbers only"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   required="">
                        </div>
                        <small class="form-text text-muted">
                            {{__('enter_phone_with_country_code')}} (e.g. +255 712345678)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('user_group')}}</label>
                        <select class="form-control" name="event_guest_category_id" id="append_option">
                            <?php foreach ($guest_categories as $category) { ?>
                                <option value="<?= $category->id ?>"><?= $category->name ?></option>
                            <?php } ?>
                        </select>
                        <br/>
                        <a class="label label-default mb-2 mb-lg-0 badge badge-success" onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                           {{__('add_new_user_group')}} 
                        </a> <i class="dripicons-arrow-thin-right arrow"></i> <i class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                        <div class="collapse hide" id="collapseExample" style="">
                            <div class="card mb-0 card-body">
                                <p class="mb-0 text-muted">{{__('user_group_name')}} </p> 
                                <div class="table-responsive">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" id="new_category_value"  name="name" class="form-control" placeholder="{{__('type_name')}}">
                                            <span class="input-group-append">

                                                <button type="button" class="btn  btn-sm btn-success" id="new_category">{{__('save')}}</button>
                                            </span>

                                        </div> 
                                        <span id="error_message"></span>
                                      </div>
                                          <div class="text-muted small d-block mt-1">{{__('user_group_name_help')}} </div>

                                        <div class="alert alert-warning mt-3">
                                            <i class="mdi mdi-information-outline mr-2"></i>
                                            {{ __('You can edit or manage existing categories in the') }}
                                            <a href="{{ url('home/settings') }}" class="font-weight-bold text-primary" target="_blank">
                                                {{ __('Settings page') }}
                                            </a>.
                                        </div>
                                  
                                </div>
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
        save_category();
    });

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
                    
                    // Load messages
                    loadContactMessages(contactId);
                    
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

    function loadContactMessages(contactId) {
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
        $('#edit_guest_phone').val($('#guest_phone' + a).text());
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        $('#edit_guest').val(a);
        $('#ProfileStep5').attr('action', '<?= url('guest/edit/null') ?>');
        $('#myModal').modal('show');
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
@endsection