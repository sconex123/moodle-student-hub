<?php
/**
 * Webhook signature validator
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

/**
 * Webhook signature validation using HMAC-SHA256
 */
class webhook_validator {

    /**
     * Generate HMAC-SHA256 signature for payload
     *
     * @param string $payload JSON payload
     * @param string $secret Shared secret key
     * @return string HMAC signature
     */
    public static function generate_signature($payload, $secret) {
        if (empty($secret)) {
            throw new \moodle_exception('webhook_secret_empty', 'local_studentmapper');
        }

        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify HMAC-SHA256 signature
     *
     * @param string $payload JSON payload
     * @param string $signature Provided signature
     * @param string $secret Shared secret key
     * @return bool True if signature is valid
     */
    public static function verify_signature($payload, $signature, $secret) {
        if (empty($secret) || empty($signature)) {
            return false;
        }

        $expected = self::generate_signature($payload, $secret);

        // Use hash_equals to prevent timing attacks.
        return hash_equals($expected, $signature);
    }

    /**
     * Log webhook attempt for audit trail
     *
     * @param string $requestid Unique request identifier
     * @param string $signature Provided signature
     * @param string $payload Request payload
     * @param bool $verified Whether signature was verified
     * @param string $ipaddress Request IP address
     * @param string $useragent Request user agent
     * @return int Record ID
     */
    public static function log_webhook_attempt($requestid, $signature, $payload, $verified, $ipaddress = null, $useragent = null) {
        global $DB;

        $record = new \stdClass();
        $record->request_id = $requestid;
        $record->signature = $signature;
        $record->payload = $payload;
        $record->verified = $verified ? 1 : 0;
        $record->ip_address = $ipaddress ?: $_SERVER['REMOTE_ADDR'] ?? '';
        $record->user_agent = $useragent ?: $_SERVER['HTTP_USER_AGENT'] ?? '';
        $record->timecreated = time();

        return $DB->insert_record('local_studentmapper_webhook', $record);
    }

    /**
     * Check if request ID has been seen before (replay attack prevention)
     *
     * @param string $requestid Unique request identifier
     * @param int $windowhours Time window in hours (default 24)
     * @return bool True if request ID is duplicate
     */
    public static function is_duplicate_request($requestid, $windowhours = 24) {
        global $DB;

        $cutoff = time() - ($windowhours * 3600);

        return $DB->record_exists_select(
            'local_studentmapper_webhook',
            'request_id = :requestid AND timecreated > :cutoff',
            ['requestid' => $requestid, 'cutoff' => $cutoff]
        );
    }

    /**
     * Validate webhook request headers and signature
     *
     * @param array $headers Request headers
     * @param string $payload Request payload
     * @return array Result with 'valid' boolean and 'error' message
     */
    public static function validate_webhook_request($headers, $payload) {
        $config = get_config('local_studentmapper');

        // Check if webhook verification is enabled.
        if (empty($config->webhook_enable_verification)) {
            return ['valid' => true, 'error' => null];
        }

        // Get signature header name.
        $signatureheader = $config->webhook_signature_header ?? 'X-Moodle-Signature';
        $signatureheader = strtolower(str_replace('-', '_', $signatureheader));

        // Check if signature header exists.
        if (!isset($headers[$signatureheader])) {
            return [
                'valid' => false,
                'error' => 'Missing signature header: ' . $signatureheader,
            ];
        }

        $signature = $headers[$signatureheader];
        $secret = $config->webhook_secret ?? '';

        // Verify signature.
        if (!self::verify_signature($payload, $signature, $secret)) {
            return [
                'valid' => false,
                'error' => 'Invalid signature',
            ];
        }

        // Check for request ID to prevent replay attacks.
        $requestid = $headers['x_request_id'] ?? null;
        if ($requestid && self::is_duplicate_request($requestid)) {
            return [
                'valid' => false,
                'error' => 'Duplicate request ID (replay attack detected)',
            ];
        }

        // Log successful validation.
        if ($requestid) {
            self::log_webhook_attempt(
                $requestid,
                $signature,
                $payload,
                true,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get webhook statistics
     *
     * @param int $datefrom Start date (unix timestamp)
     * @param int $dateto End date (unix timestamp)
     * @return array Statistics
     */
    public static function get_webhook_statistics($datefrom = null, $dateto = null) {
        global $DB;

        $params = [];
        $where = '1=1';

        if ($datefrom) {
            $where .= ' AND timecreated >= :datefrom';
            $params['datefrom'] = $datefrom;
        }

        if ($dateto) {
            $where .= ' AND timecreated <= :dateto';
            $params['dateto'] = $dateto;
        }

        $total = $DB->count_records_select('local_studentmapper_webhook', $where, $params);
        $verified = $DB->count_records_select('local_studentmapper_webhook', $where . ' AND verified = 1', $params);
        $failed = $DB->count_records_select('local_studentmapper_webhook', $where . ' AND verified = 0', $params);

        return [
            'total' => $total,
            'verified' => $verified,
            'failed' => $failed,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Cleanup old webhook logs
     *
     * @param int $days Number of days to retain
     * @return int Number of records deleted
     */
    public static function cleanup_old_webhooks($days) {
        global $DB;

        $cutoff = time() - ($days * 86400);

        return $DB->delete_records_select('local_studentmapper_webhook', 'timecreated < ?', [$cutoff]);
    }
}
