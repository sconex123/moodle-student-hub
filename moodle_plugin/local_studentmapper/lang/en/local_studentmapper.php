<?php
// Plugin.
$string['pluginname'] = 'Student Mapper';
$string['settings'] = 'Student Mapper Settings';

// Basic settings.
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The endpoint URL to send student data to. Should be a POST endpoint.';
$string['apitoken'] = 'API Token';
$string['apitoken_desc'] = 'Authorization token to be sent in the header (Authorization: Bearer <token>).';
$string['mappings'] = 'Field Mappings';
$string['mappings_desc'] = 'Map Moodle fields to External fields. Format: moodle_field:external_field. One per line. Example: firstname:first_name';

// Queue settings.
$string['queue_settings'] = 'Queue Configuration';
$string['max_queue_attempts'] = 'Max Retry Attempts';
$string['max_queue_attempts_desc'] = 'Maximum number of retry attempts for failed syncs (default: 5)';
$string['queue_backoff_multiplier'] = 'Backoff Multiplier';
$string['queue_backoff_multiplier_desc'] = 'Exponential backoff multiplier for retry delays (default: 2)';
$string['queue_processing_limit'] = 'Queue Processing Limit';
$string['queue_processing_limit_desc'] = 'Maximum number of queue items to process per scheduled task run (default: 100)';

// Logging settings.
$string['logging_settings'] = 'Logging Configuration';
$string['log_retention_days'] = 'Log Retention Days';
$string['log_retention_days_desc'] = 'Number of days to retain sync logs before cleanup (default: 90)';
$string['webhook_retention_days'] = 'Webhook Log Retention Days';
$string['webhook_retention_days_desc'] = 'Number of days to retain webhook logs before cleanup (default: 30)';

// Webhook settings.
$string['webhook_settings'] = 'Webhook Security';
$string['webhook_enable_verification'] = 'Enable Signature Verification';
$string['webhook_enable_verification_desc'] = 'Enable HMAC-SHA256 signature verification for requests';
$string['webhook_secret'] = 'Webhook Secret';
$string['webhook_secret_desc'] = 'Secret key for HMAC signature generation (minimum 32 characters)';
$string['webhook_signature_header'] = 'Signature Header Name';
$string['webhook_signature_header_desc'] = 'HTTP header name for signature (default: X-Moodle-Signature)';

// Rate limiting settings.
$string['ratelimit_settings'] = 'Rate Limiting';
$string['api_rate_limit_enabled'] = 'Enable Rate Limiting';
$string['api_rate_limit_enabled_desc'] = 'Limit API requests to prevent overload';
$string['api_rate_limit_requests'] = 'Max Requests';
$string['api_rate_limit_requests_desc'] = 'Maximum requests per window (default: 100)';
$string['api_rate_limit_window'] = 'Window Duration (seconds)';
$string['api_rate_limit_window_desc'] = 'Time window for rate limiting in seconds (default: 60)';

// API settings.
$string['api_settings'] = 'API Configuration';
$string['api_timeout'] = 'API Timeout';
$string['api_timeout_desc'] = 'Request timeout in seconds (default: 30)';

// Transformation settings.
$string['transformation_settings'] = 'Field Transformations';
$string['transformations_enabled'] = 'Enable Transformations';
$string['transformations_enabled_desc'] = 'Enable field transformation rules';

// Scheduled tasks.
$string['task_process_queue'] = 'Process Student Mapper Queue';
$string['task_cleanup_logs'] = 'Cleanup Student Mapper Logs';

// Queue statuses.
$string['status_pending'] = 'Pending';
$string['status_processing'] = 'Processing';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';

// Event types.
$string['event_user_created'] = 'User Created';
$string['event_user_updated'] = 'User Updated';
$string['event_manual'] = 'Manual Sync';

// General messages.
$string['sync_success'] = 'Sync successful';
$string['sync_failed'] = 'Sync failed';
$string['queued'] = 'Added to queue';

// Capabilities.
$string['studentmapper:viewdashboard'] = 'View Student Mapper dashboard';
$string['studentmapper:managequeue'] = 'Manage Student Mapper queue';
$string['studentmapper:manualsync'] = 'Manually trigger syncs';
$string['studentmapper:managetransforms'] = 'Manage transformation rules';
$string['studentmapper:viewlogs'] = 'View sync logs';

// Transformations.
$string['transformations'] = 'Field Transformations';
$string['transformations_desc'] = 'Manage field transformation rules';
$string['transformation_add'] = 'Add Transformation';
$string['transformation_edit'] = 'Edit Transformation';
$string['transformation_delete'] = 'Delete Transformation';
$string['transformation_delete_confirm'] = 'Are you sure you want to delete this transformation?';
$string['transformation_field'] = 'Field Name';
$string['transformation_field_help'] = 'The external field name to transform (e.g., first_name)';
$string['transformation_type'] = 'Transformation Type';
$string['transformation_config'] = 'Configuration (JSON)';
$string['transformation_config_help'] = 'JSON configuration for the transformation';
$string['transformation_priority'] = 'Priority';
$string['transformation_priority_help'] = 'Order of execution (lower numbers run first)';
$string['transformation_enabled'] = 'Enabled';
$string['transformation_test'] = 'Test Transformation';
$string['transformation_test_input'] = 'Test Input';
$string['transformation_test_output'] = 'Test Output';
$string['transformation_test_run'] = 'Run Test';

// Transformation types.
$string['transform_uppercase'] = 'Uppercase';
$string['transform_lowercase'] = 'Lowercase';
$string['transform_date_format'] = 'Date Format';
$string['transform_concat'] = 'Concatenate Fields';
$string['transform_substring'] = 'Substring';
$string['transform_regex'] = 'Regex Replace';
$string['transform_conditional'] = 'Conditional';
$string['transform_trim'] = 'Trim';
$string['transform_default'] = 'Default Value';

// Transformation examples.
$string['transform_uppercase_example'] = 'No config needed';
$string['transform_lowercase_example'] = 'No config needed';
$string['transform_date_format_example'] = '{"from": "timestamp", "to": "Y-m-d H:i:s"}';
$string['transform_concat_example'] = '{"fields": ["firstname", "lastname"], "separator": " "}';
$string['transform_substring_example'] = '{"start": 0, "length": 10}';
$string['transform_regex_example'] = '{"pattern": "/[^a-z0-9]/i", "replacement": "_"}';
$string['transform_conditional_example'] = '{"condition": "equals", "value": "student", "true": "learner", "false": "staff"}';
$string['transform_trim_example'] = '{"chars": " \\t\\n\\r"}';
$string['transform_default_example'] = '{"value": "N/A"}';

// Transformation messages.
$string['transformation_saved'] = 'Transformation saved successfully';
$string['transformation_deleted'] = 'Transformation deleted successfully';
$string['transformation_error'] = 'Error saving transformation';
$string['no_transformations'] = 'No transformations configured';
$string['manage_transformations'] = 'Manage Transformations';

// Dashboard and UI.
$string['dashboard'] = 'Dashboard';
$string['managequeue'] = 'Manage Queue';
$string['viewlogs'] = 'View Logs';
$string['logdetails'] = 'Log Details';
$string['queueitemdetails'] = 'Queue Item Details';
$string['totalsyncs'] = 'Total Syncs';
$string['successrate'] = 'Success Rate';
$string['queuesize'] = 'Queue Size';
$string['failedsyncs'] = 'Failed Syncs';
$string['recentlogs'] = 'Recent Sync Logs';
$string['queueitems'] = 'Queue Items';
$string['nologs'] = 'No sync logs found';
$string['noqueueitems'] = 'No queue items found';
$string['viewalllogs'] = 'View All Logs';
$string['daterange'] = 'Date Range';
$string['last24hours'] = 'Last 24 Hours';
$string['last7days'] = 'Last 7 Days';
$string['last30days'] = 'Last 30 Days';
$string['last90days'] = 'Last 90 Days';
$string['alltime'] = 'All Time';
$string['lastupdated'] = 'Last Updated';
$string['ready'] = 'Ready';
$string['retry'] = 'Retry';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';
$string['confirmdeleteall'] = 'Are you sure you want to delete all failed items?';
$string['queueitemretried'] = 'Queue item has been retried';
$string['queueitemdeleted'] = 'Queue item has been deleted';
$string['allitemsretried'] = '{$a} failed items have been retried';
$string['allitemsdeleted'] = '{$a} failed items have been deleted';
$string['retryall'] = 'Retry All Failed';
$string['filters'] = 'Filters';
$string['clearfilters'] = 'Clear Filters';
$string['allusers'] = 'All Users';

// Manual sync form.
$string['manualsync'] = 'Manual Sync';
$string['synctype'] = 'Sync Type';
$string['synctype_single'] = 'Single User';
$string['synctype_bulk'] = 'Bulk Users';
$string['synctype_cohort'] = 'Cohort';
$string['synctype_role'] = 'Role';
$string['singleusersync'] = 'Single User Sync';
$string['bulkusersync'] = 'Bulk User Sync';
$string['cohortsync'] = 'Cohort Sync';
$string['rolesync'] = 'Role Sync';
$string['identifiertype'] = 'Identifier Type';
$string['identifier'] = 'Identifier Value';
$string['userids'] = 'User IDs';
$string['userids_help'] = 'Enter user IDs separated by commas or newlines';
$string['addtoqueue'] = 'Add to Queue';
$string['addtoqueue_desc'] = 'Add syncs to queue instead of executing immediately';
$string['forcesync'] = 'Force Sync';
$string['forcesync_desc'] = 'Force sync even if recently synced';
$string['limit'] = 'Limit';
$string['limit_help'] = 'Maximum number of users to sync (1-10000)';
$string['sync'] = 'Sync';
$string['usernotfound'] = 'User not found';
$string['nouserstosync'] = 'No users found to sync';
$string['usersqueuedsuccess'] = '{$a} users added to queue successfully';
$string['syncresult'] = 'Sync complete: {$a->success} successful, {$a->failed} failed';
$string['syncerror'] = 'Sync error';
$string['resyncuser'] = 'Re-sync User';
$string['invaliduserids'] = 'Invalid user IDs format';
$string['invalidlimit'] = 'Limit must be between 1 and 10000';

// Log details.
$string['generalinformation'] = 'General Information';
$string['timestamp'] = 'Timestamp';
$string['userid'] = 'User ID';
$string['eventtype'] = 'Event Type';
$string['httpcode'] = 'HTTP Code';
$string['executiontime'] = 'Execution Time';
$string['errormessage'] = 'Error Message';
$string['payload'] = 'Payload';
$string['response'] = 'Response';
$string['nopayload'] = 'No payload data available';
$string['viewdetails'] = 'View Details';
$string['queueid'] = 'Queue ID';
$string['lasterror'] = 'Last Error';
$string['attempts'] = 'Attempts';
$string['nextretry'] = 'Next Retry';
$string['timecreated'] = 'Created';
$string['timemodified'] = 'Modified';
$string['failed'] = 'Failed';
$string['options'] = 'Options';

// Event types.
$string['eventtype_user_created'] = 'User Created';
$string['eventtype_user_updated'] = 'User Updated';
$string['eventtype_manual'] = 'Manual Sync';

// CLI help text.
$string['cli_sync_user_help'] = 'Sync a single user to external system';
$string['cli_sync_users_help'] = 'Sync multiple users to external system';
$string['cli_process_queue_help'] = 'Manually process the Student Mapper queue';
$string['cli_cleanup_help'] = 'Cleanup old Student Mapper logs';

// Configuration capability.
$string['studentmapper:configure'] = 'Configure Student Mapper settings';

// Privacy (GDPR).
$string['privacy:metadata:log'] = 'Sync logs containing user sync attempts and results';
$string['privacy:metadata:log:userid'] = 'The ID of the user being synced';
$string['privacy:metadata:log:eventtype'] = 'Type of event that triggered the sync';
$string['privacy:metadata:log:payload'] = 'User data sent to external system';
$string['privacy:metadata:log:response'] = 'Response received from external system';
$string['privacy:metadata:log:http_code'] = 'HTTP status code from external system';
$string['privacy:metadata:log:success'] = 'Whether the sync was successful';
$string['privacy:metadata:log:error_message'] = 'Error message if sync failed';
$string['privacy:metadata:log:execution_time'] = 'Time taken to execute the sync';
$string['privacy:metadata:log:timecreated'] = 'When the sync attempt was made';

$string['privacy:metadata:queue'] = 'Queue of pending and failed sync attempts';
$string['privacy:metadata:queue:userid'] = 'The ID of the user to be synced';
$string['privacy:metadata:queue:payload'] = 'User data to be sent to external system';
$string['privacy:metadata:queue:eventtype'] = 'Type of event that triggered the sync';
$string['privacy:metadata:queue:attempts'] = 'Number of sync attempts made';
$string['privacy:metadata:queue:status'] = 'Current status of the queue item';
$string['privacy:metadata:queue:last_error'] = 'Last error message from failed sync';
$string['privacy:metadata:queue:timecreated'] = 'When the queue item was created';
$string['privacy:metadata:queue:timemodified'] = 'When the queue item was last modified';

$string['privacy:metadata:external'] = 'User data sent to external system for synchronization';
$string['privacy:metadata:external:userid'] = 'User ID from Moodle';
$string['privacy:metadata:external:userdata'] = 'User profile data based on configured field mappings';

$string['privacy:path:synclogs'] = 'Synchronization Logs';
$string['privacy:path:queue'] = 'Sync Queue';
