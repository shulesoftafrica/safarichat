<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerts & Notifications Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various alerts, notifications,
    | and toast messages throughout the application.
    |
    */

    // Success Alerts
    'success' => [
        'operation_successful' => 'Operation completed successfully!',
        'contact_created' => 'Contact created successfully!',
        'contact_updated' => 'Contact updated successfully!',
        'contact_deleted' => 'Contact deleted successfully!',
        'campaign_created' => 'Campaign created successfully!',
        'campaign_updated' => 'Campaign updated successfully!',
        'campaign_sent' => 'Campaign sent successfully!',
        'campaign_scheduled' => 'Campaign scheduled successfully!',
        'product_created' => 'Product created successfully!',
        'product_updated' => 'Product updated successfully!',
        'product_deleted' => 'Product deleted successfully!',
        'agent_created' => 'Sales agent created successfully!',
        'agent_updated' => 'Sales agent updated successfully!',
        'agent_deleted' => 'Sales agent deleted successfully!',
        'appointment_created' => 'Appointment created successfully!',
        'appointment_updated' => 'Appointment updated successfully!',
        'appointment_cancelled' => 'Appointment cancelled successfully!',
        'message_sent' => 'Message sent successfully!',
        'file_uploaded' => 'File uploaded successfully!',
        'file_deleted' => 'File deleted successfully!',
        'settings_saved' => 'Settings saved successfully!',
        'password_changed' => 'Password changed successfully!',
        'profile_updated' => 'Profile updated successfully!',
        'whatsapp_connected' => 'WhatsApp connected successfully!',
        'whatsapp_disconnected' => 'WhatsApp disconnected successfully!',
        'data_imported' => 'Data imported successfully!',
        'data_exported' => 'Data exported successfully!',
    ],

    // Error Alerts
    'error' => [
        'operation_failed' => 'Operation failed. Please try again.',
        'something_went_wrong' => 'Something went wrong. Please try again.',
        'contact_not_found' => 'Contact not found.',
        'campaign_not_found' => 'Campaign not found.',
        'product_not_found' => 'Product not found.',
        'agent_not_found' => 'Sales agent not found.',
        'appointment_not_found' => 'Appointment not found.',
        'message_failed' => 'Failed to send message. Please try again.',
        'file_upload_failed' => 'File upload failed. Please try again.',
        'file_too_large' => 'File is too large. Maximum size is :size.',
        'invalid_file_type' => 'Invalid file type. Allowed types: :types.',
        'validation_error' => 'Please check the form for errors.',
        'unauthorized' => 'You are not authorized to perform this action.',
        'session_expired' => 'Your session has expired. Please log in again.',
        'network_error' => 'Network error. Please check your connection.',
        'server_error' => 'Server error. Please contact support.',
        'whatsapp_connection_failed' => 'Failed to connect to WhatsApp. Please try again.',
        'whatsapp_not_connected' => 'WhatsApp is not connected. Please connect first.',
        'insufficient_credits' => 'Insufficient credits. Please recharge.',
        'duplicate_entry' => 'This entry already exists.',
        'required_fields' => 'Please fill in all required fields.',
    ],

    // Warning Alerts
    'warning' => [
        'unsaved_changes' => 'You have unsaved changes. Are you sure you want to leave?',
        'low_credits' => 'Your credits are running low. Consider recharging.',
        'trial_ending_soon' => 'Your trial period ends in :days days.',
        'subscription_expired' => 'Your subscription has expired.',
        'whatsapp_disconnected' => 'WhatsApp is disconnected. Please reconnect.',
        'no_products_defined' => 'No products defined. Please add products first.',
        'no_contacts' => 'You have no contacts. Import or add contacts first.',
        'campaign_no_recipients' => 'No recipients selected for this campaign.',
        'confirm_delete' => 'Are you sure you want to delete this? This action cannot be undone.',
        'confirm_cancel' => 'Are you sure you want to cancel?',
        'large_file_warning' => 'Large files may take longer to upload.',
        'bulk_operation_warning' => 'This will affect :count items. Continue?',
    ],

    // Info Alerts
    'info' => [
        'welcome_back' => 'Welcome back, :name!',
        'first_time_setup' => 'Complete your setup to get started.',
        'new_feature' => 'New feature available! Check it out.',
        'update_available' => 'A new update is available.',
        'maintenance_scheduled' => 'Scheduled maintenance on :date.',
        'no_data_yet' => 'No data to display yet. Start by adding items.',
        'processing_request' => 'Processing your request...',
        'campaign_queued' => 'Campaign queued for sending.',
        'import_in_progress' => 'Import in progress. This may take a few minutes.',
        'export_ready' => 'Your export is ready for download.',
        'whatsapp_qr_scan' => 'Scan the QR code with your WhatsApp to connect.',
        'rate_limit' => 'You are sending messages too quickly. Please wait.',
    ],

    // Confirmation Messages
    'confirm' => [
        'delete_contact' => 'Are you sure you want to delete this contact?',
        'delete_contacts' => 'Are you sure you want to delete :count contacts?',
        'delete_campaign' => 'Are you sure you want to delete this campaign?',
        'delete_product' => 'Are you sure you want to delete this product?',
        'delete_agent' => 'Are you sure you want to delete this sales agent?',
        'cancel_appointment' => 'Are you sure you want to cancel this appointment?',
        'send_campaign' => 'Are you sure you want to send this campaign to :count recipients?',
        'disconnect_whatsapp' => 'Are you sure you want to disconnect WhatsApp?',
        'archive_item' => 'Are you sure you want to archive this item?',
        'restore_item' => 'Are you sure you want to restore this item?',
        'irreversible_action' => 'This action cannot be undone!',
    ],

    // Toast Notifications
    'toast' => [
        'copied' => 'Copied to clipboard!',
        'saved' => 'Saved!',
        'deleted' => 'Deleted!',
        'updated' => 'Updated!',
        'sent' => 'Sent!',
        'loading' => 'Loading...',
        'please_wait' => 'Please wait...',
        'processing' => 'Processing...',
    ],

    // Form Validation Alerts
    'validation' => [
        'required' => 'This field is required.',
        'email' => 'Please enter a valid email address.',
        'phone' => 'Please enter a valid phone number.',
        'url' => 'Please enter a valid URL.',
        'min_length' => 'Must be at least :min characters.',
        'max_length' => 'Must not exceed :max characters.',
        'numeric' => 'Please enter a number.',
        'alpha' => 'Please enter only letters.',
        'alphanumeric' => 'Please enter only letters and numbers.',
        'match_password' => 'Passwords do not match.',
        'invalid_date' => 'Please enter a valid date.',
        'future_date' => 'Date must be in the future.',
        'past_date' => 'Date must be in the past.',
    ],

    // System Notifications
    'system' => [
        'backup_complete' => 'System backup completed successfully.',
        'backup_failed' => 'System backup failed.',
        'maintenance_mode' => 'System is in maintenance mode.',
        'update_available' => 'System update available.',
        'cache_cleared' => 'Cache cleared successfully.',
        'logs_cleared' => 'Logs cleared successfully.',
    ],

];
