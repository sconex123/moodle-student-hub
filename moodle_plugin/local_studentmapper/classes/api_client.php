<?php
namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

/**
 * Enhanced API client for sending data to external API
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_client {

    /** @var array Rate limiting tracking */
    private static $requesttimestamps = [];

    /**
     * Send user data to the external API
     *
     * @param array $data The user data to send
     * @return array Response array with keys: success, http_code, headers, body, error, execution_time
     */
    public static function send($data) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $starttime = microtime(true);

        $url = get_config('local_studentmapper', 'apiurl');
        $token = get_config('local_studentmapper', 'apitoken');

        if (empty($url)) {
            return [
                'success' => false,
                'http_code' => null,
                'headers' => [],
                'body' => null,
                'error' => 'No API URL configured',
                'execution_time' => 0,
            ];
        }

        // Check rate limiting.
        $ratelimiterror = self::check_rate_limit();
        if ($ratelimiterror) {
            return [
                'success' => false,
                'http_code' => 429,
                'headers' => [],
                'body' => null,
                'error' => $ratelimiterror,
                'execution_time' => 0,
            ];
        }

        // Generate webhook signature if enabled.
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $webhooksignature = self::generate_webhook_signature($data);
        if ($webhooksignature) {
            $signatureheader = get_config('local_studentmapper', 'webhook_signature_header') ?: 'X-Moodle-Signature';
            $headers[] = $signatureheader . ': ' . $webhooksignature;
        }

        $timeout = get_config('local_studentmapper', 'api_timeout') ?: 30;

        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => $timeout,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ];

        $jsonpayload = json_encode($data);

        try {
            // Send POST request.
            $response = $curl->post($url, $jsonpayload, $options);
            $info = $curl->get_info();
            $executiontime = round((microtime(true) - $starttime) * 1000); // Convert to milliseconds.

            $httpcode = $info['http_code'];
            $success = ($httpcode >= 200 && $httpcode < 300);

            // Track request for rate limiting.
            self::$requesttimestamps[] = time();

            $result = [
                'success' => $success,
                'http_code' => $httpcode,
                'headers' => $info,
                'body' => $response,
                'error' => null,
                'execution_time' => $executiontime,
            ];

            if (!$success) {
                $result['error'] = self::get_error_message($httpcode, $response);
            }

            return $result;

        } catch (\Exception $e) {
            $executiontime = round((microtime(true) - $starttime) * 1000);
            return [
                'success' => false,
                'http_code' => null,
                'headers' => [],
                'body' => null,
                'error' => 'Exception: ' . $e->getMessage(),
                'execution_time' => $executiontime,
            ];
        }
    }

    /**
     * Generate webhook signature for request verification
     *
     * @param array $payload The payload to sign
     * @return string|null HMAC signature or null if disabled
     */
    private static function generate_webhook_signature($payload) {
        $enabled = get_config('local_studentmapper', 'webhook_enable_verification');
        if (!$enabled) {
            return null;
        }

        $secret = get_config('local_studentmapper', 'webhook_secret');
        if (empty($secret)) {
            return null;
        }

        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Check rate limiting constraints
     *
     * @return string|null Error message if rate limit exceeded, null otherwise
     */
    private static function check_rate_limit() {
        $enabled = get_config('local_studentmapper', 'api_rate_limit_enabled');
        if (!$enabled) {
            return null;
        }

        $limit = get_config('local_studentmapper', 'api_rate_limit_requests') ?: 100;
        $window = get_config('local_studentmapper', 'api_rate_limit_window') ?: 60;

        // Clean old timestamps outside the window.
        $cutoff = time() - $window;
        self::$requesttimestamps = array_filter(
            self::$requesttimestamps,
            function($ts) use ($cutoff) {
                return $ts > $cutoff;
            }
        );

        if (count(self::$requesttimestamps) >= $limit) {
            return "Rate limit exceeded: $limit requests per $window seconds";
        }

        return null;
    }

    /**
     * Get human-readable error message based on HTTP code
     *
     * @param int $httpcode HTTP status code
     * @param string|null $response Response body
     * @return string Error message
     */
    private static function get_error_message($httpcode, $response) {
        $messages = [
            400 => 'Bad Request: Invalid data sent to API',
            401 => 'Unauthorized: Invalid or missing API token',
            403 => 'Forbidden: API token does not have permission',
            404 => 'Not Found: API endpoint does not exist',
            408 => 'Request Timeout: API did not respond in time',
            429 => 'Too Many Requests: Rate limit exceeded',
            500 => 'Internal Server Error: API encountered an error',
            502 => 'Bad Gateway: API server is unavailable',
            503 => 'Service Unavailable: API is temporarily down',
            504 => 'Gateway Timeout: API took too long to respond',
        ];

        $errormessage = isset($messages[$httpcode]) ? $messages[$httpcode] : "HTTP Error $httpcode";

        // Append response body if available (truncated).
        if (!empty($response)) {
            $truncated = mb_substr($response, 0, 500);
            if (mb_strlen($response) > 500) {
                $truncated .= '... (truncated)';
            }
            $errormessage .= " - Response: $truncated";
        }

        return $errormessage;
    }

    /**
     * Test API connection with current configuration
     *
     * @return array Test result with success status and message
     */
    public static function test_connection() {
        $testpayload = [
            'test' => true,
            'timestamp' => time(),
            'message' => 'Moodle Student Mapper connection test',
        ];

        $result = self::send($testpayload);

        return [
            'success' => $result['success'],
            'message' => $result['success']
                ? "Connection successful (HTTP {$result['http_code']}, {$result['execution_time']}ms)"
                : "Connection failed: {$result['error']}",
            'details' => $result,
        ];
    }
}
