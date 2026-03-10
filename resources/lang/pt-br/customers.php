<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer & Contacts Language Lines (Portuguese - Brazil)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the customer/contact management module
    | TODO: Professional Portuguese (Brazil) translation required
    |
    */

    // Page Title & Navigation
    'page_title' => 'Customers',
    'page_subtitle' => 'Manage List of Customers',
    'breadcrumb_home' => 'Home',
    'breadcrumb_category' => 'Campaign',
    'breadcrumb_customers' => 'People',

    // List View
    'list' => [
        'title' => 'List of Customers',
        'subtitle' => 'Manage List of Customers',
        'total_contacts' => 'Total Contacts',
        'no_contacts' => 'No customers found',
        'showing' => 'Showing :from to :to of :total customers',
    ],

    // Actions
    'actions' => [
        'add_new' => 'Add Customer Contact',
        'upload_excel' => 'Upload Excel/CSV File',
        'sync_whatsapp' => 'Sync from WhatsApp',
        'sync_google' => 'Sync from Google',
        'export' => 'Export Customers',
        'import' => 'Import Customers',
        'send_message' => 'Send Message',
        'delete_selected' => 'Delete Selected',
        'clear_selection' => 'Clear Selection',
        'bulk_actions' => 'Bulk Actions',
        'filter' => 'Filter',
        'search' => 'Search customers...',
        'refresh' => 'Refresh',
    ],

    // Bulk Actions
    'bulk' => [
        'selected' => ':count contacts selected',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'action_required' => 'Please select an action',
        'confirm_delete' => 'Are you sure you want to delete :count contacts?',
        'delete_success' => ':count contacts deleted successfully',
        'delete_error' => 'Failed to delete contacts',
        'send_message_to' => 'Send message to :count contacts',
    ],

    // Form Fields
    'fields' => [
        'name' => 'Name',
        'phone' => 'Phone Number',
        'email' => 'Email',
        'address' => 'Address',
        'city' => 'City',
        'country' => 'Country',
        'type' => 'Type',
        'status' => 'Status',
        'tags' => 'Tags',
        'notes' => 'Notes',
        'created_at' => 'Date Added',
        'updated_at' => 'Last Updated',
        'lead_status' => 'Lead Status',
        'handoff_status' => 'Handoff Status',
        'assigned_to' => 'Assigned To',
        'source' => 'Source',
        'last_contact' => 'Last Contact',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'Enter customer name',
        'phone' => 'Enter phone number',
        'email' => 'Enter email address',
        'address' => 'Enter address',
        'city' => 'Enter city',
        'country' => 'Select country',
        'notes' => 'Add notes or comments...',
        'search' => 'Search by name, phone or email...',
        'tags' => 'Add tags separated by commas',
    ],

    // Handoff Management
    'handoff' => [
        'title' => 'Handoff Management',
        'all' => 'All',
        'ai_handling' => 'AI Handling',
        'pending_handoff' => 'Pending Handoff',
        'handed_off' => 'Handed Off',
        'completed' => 'Completed',
        'urgent' => 'Urgent',
        'status_ai' => 'AI',
        'status_human' => 'Human',
        'status_pending' => 'Pending',
        'assign_to_human' => 'Assign to Human',
        'return_to_ai' => 'Return to AI',
    ],

    // Lead Status
    'lead_status' => [
        'new' => 'New Lead',
        'contacted' => 'Contacted',
        'outreached' => 'Outreached',
        'replied' => 'Replied',
        'engaged' => 'Engaged',
        'qualified' => 'Qualified',
        'pitched' => 'Pitched',
        'demo_scheduled' => 'Demo Scheduled',
        'proposal' => 'Proposal Sent',
        'negotiating' => 'Negotiating',
        'won' => 'Closed (Won)',
        'lost' => 'Lost',
        'handed_off' => 'Handed Off',
        'do_not_contact' => 'Do Not Contact',
        'churned' => 'Churned',
        'cold' => 'Cold Lead',
        'hot' => 'Hot Lead',
        'warm' => 'Warm Lead',
    ],

    // Summary Section
    'summary' => [
        'title' => 'Lead Status Summary',
        'total_contacts' => 'Total Contacts',
        'contact_details' => 'Contact Details',
        'conversation_summary' => 'Conversation Summary',
    ],

    // Table Headers
    'table' => [
        'select' => 'Select',
        'name' => 'Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'status' => 'Status',
        'lead_status' => 'Lead Status',
        'handoff_status' => 'Handoff',
        'last_message' => 'Last Message',
        'created_at' => 'Added On',
        'actions' => 'Actions',
        'no_data' => 'No customers found',
        'loading' => 'Loading...',
    ],

    // Filters
    'filters' => [
        'all' => 'All Customers',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'new' => 'New This Month',
        'recent' => 'Recently Added',
        'unread' => 'Unread Messages',
        'favorites' => 'Favorites',
        'by_status' => 'By Status',
        'by_source' => 'By Source',
        'by_date' => 'By Date Range',
        'clear_filters' => 'Clear Filters',
    ],

    // Upload
    'upload' => [
        'title' => 'Upload Customers Details',
        'subtitle' => 'Import customers from Excel or CSV file',
        'download_sample' => 'Download Sample File',
        'sample_excel' => 'Sample Excel File',
        'click_to_upload' => 'Click here to upload excel file',
        'drag_drop' => 'Drag and drop your file here or click to browse',
        'supported_formats' => 'Supported formats: Excel (.xlsx, .xls) and CSV (.csv)',
        'max_file_size' => 'Maximum file size: 10MB',
        'uploading' => 'Uploading...',
        'processing' => 'Processing file...',
        'success' => ':count customers imported successfully',
        'partial_success' => ':success imported, :failed failed',
        'error' => 'Failed to import customers',
        'invalid_file' => 'Invalid file format',
        'file_too_large' => 'File is too large',
        'required_columns' => 'Required columns: Name, Phone',
        'optional_columns' => 'Optional columns: Email, Address, City, Notes',
    ],

    // WhatsApp Sync
    'whatsapp_sync' => [
        'title' => 'Sync WhatsApp Contacts',
        'subtitle' => 'Connect your WhatsApp to import contacts',
        'description' => 'Import all your WhatsApp contacts automatically',
        'start_sync' => 'Start Sync',
        'syncing' => 'Syncing contacts, please wait...',
        'success' => ':count contacts synced from WhatsApp',
        'error' => 'Failed to sync WhatsApp contacts',
        'no_instance' => 'No WhatsApp instance connected',
        'connect_first' => 'Please connect WhatsApp first',
        'select_instance' => 'Select WhatsApp Instance',
        'sync_now' => 'Sync Now',
        'last_sync' => 'Last synced: :date',
        'never_synced' => 'Never synced',
        'instance_not_connected' => 'WhatsApp instance not connected. Please connect first.',
        'no_instance_found' => 'No WhatsApp instance found. Please set up first.',
        'contacts_synced_successfully' => 'Contacts synced successfully',
        'contacts_imported' => 'contacts imported',
        'failed_to_import' => 'Failed to import contacts',
        'no_contacts_found' => 'No contacts found in WhatsApp',
        'auth_failed' => 'Authentication failed. Check WAAPI token.',
        'instance_not_found' => 'Instance not found or not connected',
        'method_not_allowed' => 'Method not allowed. API endpoint issue.',
    ],

    // Google Sync
    'google_sync' => [
        'title' => 'Sync Google Contacts',
        'subtitle' => 'Import contacts from your Google account',
        'description' => 'Sync contacts from your Google account',
        'secure_process' => 'We use secure OAuth 2.0 authentication process',
        'sign_in' => 'Sign in with Google',
        'sign_in_button' => 'Sign in with Google',
        'connecting' => 'Connecting to Google...',
        'syncing' => 'Syncing contacts...',
        'success' => ':count contacts synced from Google',
        'error' => 'Failed to sync Google contacts',
        'auth_required' => 'Google authentication required',
        'secure_oauth' => 'We use secure OAuth process',
        'info' => 'Google Contacts Sync Info',
        'benefits_title' => 'What you should know',
        'benefits' => [
            'secure_oauth' => 'Secure OAuth 2.0 authentication',
            'read_only' => 'Read-only access to your contacts',
            'no_passwords' => 'We never store your password',
            'auto_dedupe' => 'Automatic duplicate prevention',
        ],
        'initializing' => 'Initializing Google authentication...',
        'init_failed' => 'Failed to initialize Google API',
        'auth_start_failed' => 'Failed to start Google authentication',
        'auth_failed' => 'Google authentication failed',
        'auth_success' => 'Google authentication successful! Fetching contacts...',
        'fetching' => 'Fetching contacts from Google...',
        'fetch_failed' => 'Failed to fetch Google contacts',
        'processing' => 'Processing contacts for import...',
        'no_contacts' => 'No contacts found in Google account',
        'no_phone_contacts' => 'No contacts with phone numbers found',
        'import_success' => 'Google contacts imported successfully',
        'import_failed' => 'Failed to import Google contacts',
        'loading_apis' => 'Loading Google APIs...',
    ],

    // Modals
    'modals' => [
        'add_title' => 'Add Customer Contact',
        'edit_title' => 'Edit Customer Details',
        'delete_title' => 'Delete Customer',
        'delete_message' => 'Are you sure you want to delete this customer?',
        'delete_confirm' => 'Yes, Delete',
        'delete_cancel' => 'Cancel',
        'view_title' => 'Customer Details',
        'close' => 'Close',
        'save' => 'Save',
        'save_changes' => 'Save Changes',
        'cancel' => 'Cancel',
    ],

    // Messages
    'messages' => [
        'created' => 'Customer added successfully',
        'updated' => 'Customer updated successfully',
        'deleted' => 'Customer deleted successfully',
        'delete_error' => 'Failed to delete customer',
        'not_found' => 'Customer not found',
        'duplicate_phone' => 'A customer with this phone number already exists',
        'invalid_phone' => 'Invalid phone number format',
        'required_fields' => 'Please fill all required fields',
        'no_selection' => 'Please select at least one customer',
    ],

    // Status Labels
    'status_labels' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'blocked' => 'Blocked',
        'pending' => 'Pending',
        'verified' => 'Verified',
        'unverified' => 'Unverified',
    ],

    // Sort Options
    'sort' => [
        'newest' => 'Newest First',
        'oldest' => 'Oldest First',
        'name_asc' => 'Name (A-Z)',
        'name_desc' => 'Name (Z-A)',
        'recent_activity' => 'Recent Activity',
        'most_messages' => 'Most Messages',
    ],

    // Export
    'export' => [
        'title' => 'Export Customers',
        'format' => 'Select Format',
        'excel' => 'Excel (.xlsx)',
        'csv' => 'CSV (.csv)',
        'pdf' => 'PDF (.pdf)',
        'all_contacts' => 'All Contacts',
        'selected_contacts' => 'Selected Contacts',
        'filtered_contacts' => 'Filtered Contacts',
        'exporting' => 'Exporting...',
        'success' => 'Customers exported successfully',
        'error' => 'Failed to export customers',
    ],

    // Empty States
    'empty' => [
        'title' => 'No Customers Yet',
        'subtitle' => 'Start by adding your first customer',
        'action' => 'Add Customer',
        'import_action' => 'Or import from file',
    ],

];
