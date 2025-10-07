<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for local_studentmapper plugin
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_studentmapper_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024011000) {
        // Define table local_studentmapper_queue to be created.
        $table = new xmldb_table('local_studentmapper_queue');

        // Adding fields to table local_studentmapper_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventtype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('max_attempts', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '5');
        $table->add_field('next_retry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('last_error', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentmapper_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table local_studentmapper_queue.
        $table->add_index('status_nextretry', XMLDB_INDEX_NOTUNIQUE, ['status', 'next_retry']);
        $table->add_index('eventtype', XMLDB_INDEX_NOTUNIQUE, ['eventtype']);

        // Conditionally launch create table for local_studentmapper_queue.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_studentmapper_log to be created.
        $table = new xmldb_table('local_studentmapper_log');

        // Adding fields to table local_studentmapper_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('queueid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('eventtype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('http_code', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
        $table->add_field('success', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('execution_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentmapper_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('queueid', XMLDB_KEY_FOREIGN, ['queueid'], 'local_studentmapper_queue', ['id']);

        // Adding indexes to table local_studentmapper_log.
        $table->add_index('userid_success', XMLDB_INDEX_NOTUNIQUE, ['userid', 'success']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        $table->add_index('success', XMLDB_INDEX_NOTUNIQUE, ['success']);

        // Conditionally launch create table for local_studentmapper_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_studentmapper_transform to be created.
        $table = new xmldb_table('local_studentmapper_transform');

        // Adding fields to table local_studentmapper_transform.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('field_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('transform_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('transform_config', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('priority', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentmapper_transform.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_studentmapper_transform.
        $table->add_index('field_enabled', XMLDB_INDEX_NOTUNIQUE, ['field_name', 'enabled']);
        $table->add_index('priority', XMLDB_INDEX_NOTUNIQUE, ['priority']);

        // Conditionally launch create table for local_studentmapper_transform.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_studentmapper_webhook to be created.
        $table = new xmldb_table('local_studentmapper_webhook');

        // Adding fields to table local_studentmapper_webhook.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('request_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('signature', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('verified', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ip_address', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_field('user_agent', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentmapper_webhook.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_studentmapper_webhook.
        $table->add_index('request_id', XMLDB_INDEX_UNIQUE, ['request_id']);
        $table->add_index('verified', XMLDB_INDEX_NOTUNIQUE, ['verified']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_studentmapper_webhook.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Initialize default configuration settings.
        set_config('max_queue_attempts', 5, 'local_studentmapper');
        set_config('queue_backoff_multiplier', 2, 'local_studentmapper');
        set_config('queue_processing_limit', 100, 'local_studentmapper');
        set_config('log_retention_days', 90, 'local_studentmapper');
        set_config('webhook_retention_days', 30, 'local_studentmapper');
        set_config('webhook_enable_verification', 0, 'local_studentmapper');
        set_config('webhook_signature_header', 'X-Moodle-Signature', 'local_studentmapper');
        set_config('api_rate_limit_enabled', 0, 'local_studentmapper');
        set_config('api_rate_limit_requests', 100, 'local_studentmapper');
        set_config('api_rate_limit_window', 60, 'local_studentmapper');
        set_config('transformations_enabled', 1, 'local_studentmapper');
        set_config('api_timeout', 30, 'local_studentmapper');

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2024011000, 'local', 'studentmapper');
    }

    return true;
}
