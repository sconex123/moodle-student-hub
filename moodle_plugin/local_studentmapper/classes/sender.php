<?php
namespace local_studentmapper;
defined('MOODLE_INTERNAL') || die();

class sender
{
    /**
     * Send user data to the external API.
     *
     * @param array $data The user data to send.
     * @return bool True on success, false on failure.
     */
    public static function send($data)
    {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = get_config('local_studentmapper', 'apiurl');
        $token = get_config('local_studentmapper', 'apitoken');

        if (empty($url)) {
            // No URL configured, cannot send.
            return false;
        }

        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            'CURLOPT_RETURNTRANSFER' => true
        ];

        $json_payload = json_encode($data);

        // Send POST request
        $response = $curl->post($url, $json_payload, $options);
        $info = $curl->get_info();

        if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
            return true;
        } else {
            // Log error
            debugging('Student Mapper API Error: ' . $info['http_code'] . ' - ' . $response, DEBUG_DEVELOPER);
            return false;
        }
    }
}
