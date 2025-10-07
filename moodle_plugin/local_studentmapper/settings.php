<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a category for Student Mapper.
    $ADMIN->add('localplugins', new admin_category('local_studentmapper_category',
        get_string('pluginname', 'local_studentmapper')));

    // Dashboard page.
    $ADMIN->add('local_studentmapper_category', new admin_externalpage(
        'local_studentmapper_dashboard',
        get_string('dashboard', 'local_studentmapper'),
        new moodle_url('/local/studentmapper/dashboard.php'),
        'local/studentmapper:viewdashboard'
    ));

    // Queue management page.
    $ADMIN->add('local_studentmapper_category', new admin_externalpage(
        'local_studentmapper_queue',
        get_string('managequeue', 'local_studentmapper'),
        new moodle_url('/local/studentmapper/manage_queue.php'),
        'local/studentmapper:managequeue'
    ));

    // Logs page.
    $ADMIN->add('local_studentmapper_category', new admin_externalpage(
        'local_studentmapper_logs',
        get_string('viewlogs', 'local_studentmapper'),
        new moodle_url('/local/studentmapper/view_logs.php'),
        'local/studentmapper:viewlogs'
    ));

    // Settings page.
    $settings = new admin_settingpage('local_studentmapper', get_string('settings'));

    if ($ADMIN->fulltree) {
        // API Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/api_settings_header',
            get_string('api_settings', 'local_studentmapper'),
            ''
        ));

        // API URL.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/apiurl',
            get_string('apiurl', 'local_studentmapper'),
            get_string('apiurl_desc', 'local_studentmapper'),
            '',
            PARAM_URL
        ));

        // API Token.
        $settings->add(new admin_setting_configpasswordunmask(
            'local_studentmapper/apitoken',
            get_string('apitoken', 'local_studentmapper'),
            get_string('apitoken_desc', 'local_studentmapper'),
            ''
        ));

        // API Timeout.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/api_timeout',
            get_string('api_timeout', 'local_studentmapper'),
            get_string('api_timeout_desc', 'local_studentmapper'),
            '30',
            PARAM_INT
        ));

        // Field Mappings.
        $settings->add(new admin_setting_configtextarea(
            'local_studentmapper/mappings',
            get_string('mappings', 'local_studentmapper'),
            get_string('mappings_desc', 'local_studentmapper'),
            "firstname:first_name\nlastname:last_name\nemail:email\nidnumber:student_id\nusername:username",
            PARAM_TEXT
        ));

        // Queue Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/queue_settings_header',
            get_string('queue_settings', 'local_studentmapper'),
            ''
        ));

        // Max Queue Attempts.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/max_queue_attempts',
            get_string('max_queue_attempts', 'local_studentmapper'),
            get_string('max_queue_attempts_desc', 'local_studentmapper'),
            '5',
            PARAM_INT
        ));

        // Queue Backoff Multiplier.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/queue_backoff_multiplier',
            get_string('queue_backoff_multiplier', 'local_studentmapper'),
            get_string('queue_backoff_multiplier_desc', 'local_studentmapper'),
            '2',
            PARAM_INT
        ));

        // Queue Processing Limit.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/queue_processing_limit',
            get_string('queue_processing_limit', 'local_studentmapper'),
            get_string('queue_processing_limit_desc', 'local_studentmapper'),
            '100',
            PARAM_INT
        ));

        // Logging Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/logging_settings_header',
            get_string('logging_settings', 'local_studentmapper'),
            ''
        ));

        // Log Retention Days.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/log_retention_days',
            get_string('log_retention_days', 'local_studentmapper'),
            get_string('log_retention_days_desc', 'local_studentmapper'),
            '90',
            PARAM_INT
        ));

        // Webhook Retention Days.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/webhook_retention_days',
            get_string('webhook_retention_days', 'local_studentmapper'),
            get_string('webhook_retention_days_desc', 'local_studentmapper'),
            '30',
            PARAM_INT
        ));

        // Webhook Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/webhook_settings_header',
            get_string('webhook_settings', 'local_studentmapper'),
            ''
        ));

        // Enable Webhook Verification.
        $settings->add(new admin_setting_configcheckbox(
            'local_studentmapper/webhook_enable_verification',
            get_string('webhook_enable_verification', 'local_studentmapper'),
            get_string('webhook_enable_verification_desc', 'local_studentmapper'),
            '0'
        ));

        // Webhook Secret.
        $settings->add(new admin_setting_configpasswordunmask(
            'local_studentmapper/webhook_secret',
            get_string('webhook_secret', 'local_studentmapper'),
            get_string('webhook_secret_desc', 'local_studentmapper'),
            ''
        ));

        // Webhook Signature Header.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/webhook_signature_header',
            get_string('webhook_signature_header', 'local_studentmapper'),
            get_string('webhook_signature_header_desc', 'local_studentmapper'),
            'X-Moodle-Signature',
            PARAM_TEXT
        ));

        // Rate Limiting Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/ratelimit_settings_header',
            get_string('ratelimit_settings', 'local_studentmapper'),
            ''
        ));

        // Enable Rate Limiting.
        $settings->add(new admin_setting_configcheckbox(
            'local_studentmapper/api_rate_limit_enabled',
            get_string('api_rate_limit_enabled', 'local_studentmapper'),
            get_string('api_rate_limit_enabled_desc', 'local_studentmapper'),
            '0'
        ));

        // Max Requests.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/api_rate_limit_requests',
            get_string('api_rate_limit_requests', 'local_studentmapper'),
            get_string('api_rate_limit_requests_desc', 'local_studentmapper'),
            '100',
            PARAM_INT
        ));

        // Rate Limit Window.
        $settings->add(new admin_setting_configtext(
            'local_studentmapper/api_rate_limit_window',
            get_string('api_rate_limit_window', 'local_studentmapper'),
            get_string('api_rate_limit_window_desc', 'local_studentmapper'),
            '60',
            PARAM_INT
        ));

        // Transformation Settings Header.
        $settings->add(new admin_setting_heading(
            'local_studentmapper/transformation_settings_header',
            get_string('transformation_settings', 'local_studentmapper'),
            ''
        ));

        // Enable Transformations.
        $settings->add(new admin_setting_configcheckbox(
            'local_studentmapper/transformations_enabled',
            get_string('transformations_enabled', 'local_studentmapper'),
            get_string('transformations_enabled_desc', 'local_studentmapper'),
            '1'
        ));
    }

    $ADMIN->add('local_studentmapper_category', $settings);
}
