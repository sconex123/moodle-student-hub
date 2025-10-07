<?php
namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

/**
 * Field transformation engine
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transformer {

    /**
     * Apply all transformations to payload
     *
     * @param array $payload The payload data
     * @return array Transformed payload
     */
    public static function apply_all_transformations($payload) {
        // Get all unique field names from payload.
        $fields = array_keys($payload);

        // Apply transformations to each field.
        foreach ($fields as $field) {
            $transforms = self::get_active_transforms($field);

            foreach ($transforms as $transform) {
                try {
                    $config = !empty($transform->transform_config) ? json_decode($transform->transform_config, true) : [];

                    // Apply transformation based on type.
                    $payload[$field] = self::apply_transformation(
                        $payload[$field],
                        $transform->transform_type,
                        $config,
                        $payload // Pass full payload for concat/conditional.
                    );

                } catch (\Exception $e) {
                    debugging("Transformation error for field $field: " . $e->getMessage(), DEBUG_DEVELOPER);
                    // Continue with next transformation on error.
                }
            }
        }

        return $payload;
    }

    /**
     * Get active transformations for a field
     *
     * @param string $fieldname Field name
     * @return array Array of transformation records
     */
    public static function get_active_transforms($fieldname) {
        global $DB;

        return $DB->get_records('local_studentmapper_transform', [
            'field_name' => $fieldname,
            'enabled' => 1,
        ], 'priority ASC');
    }

    /**
     * Apply a single transformation
     *
     * @param mixed $value The value to transform
     * @param string $type Transformation type
     * @param array $config Transformation configuration
     * @param array $fullpayload Full payload for concat/conditional
     * @return mixed Transformed value
     */
    private static function apply_transformation($value, $type, $config, $fullpayload) {
        switch ($type) {
            case 'uppercase':
                return self::apply_uppercase($value);

            case 'lowercase':
                return self::apply_lowercase($value);

            case 'date_format':
                return self::apply_date_format($value, $config);

            case 'concat':
                return self::apply_concat($fullpayload, $config);

            case 'substring':
                return self::apply_substring($value, $config);

            case 'regex':
                return self::apply_regex($value, $config);

            case 'conditional':
                return self::apply_conditional($value, $config);

            case 'trim':
                return self::apply_trim($value, $config);

            case 'default':
                return self::apply_default($value, $config);

            default:
                return $value;
        }
    }

    /**
     * Transform to uppercase
     *
     * @param mixed $value The value
     * @return string Uppercase value
     */
    private static function apply_uppercase($value) {
        if ($value === null || $value === '') {
            return $value;
        }
        return mb_strtoupper((string)$value, 'UTF-8');
    }

    /**
     * Transform to lowercase
     *
     * @param mixed $value The value
     * @return string Lowercase value
     */
    private static function apply_lowercase($value) {
        if ($value === null || $value === '') {
            return $value;
        }
        return mb_strtolower((string)$value, 'UTF-8');
    }

    /**
     * Format date/timestamp
     *
     * Config: { "from": "timestamp|Y-m-d", "to": "Y-m-d H:i:s" }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return string Formatted date
     */
    private static function apply_date_format($value, $config) {
        if ($value === null || $value === '') {
            return $value;
        }

        $from = $config['from'] ?? 'timestamp';
        $to = $config['to'] ?? 'Y-m-d H:i:s';

        try {
            if ($from === 'timestamp') {
                // Value is Unix timestamp.
                $timestamp = (int)$value;
                return date($to, $timestamp);
            } else {
                // Value is date string in specific format.
                $date = \DateTime::createFromFormat($from, $value);
                if ($date) {
                    return $date->format($to);
                }
            }
        } catch (\Exception $e) {
            debugging("Date format error: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $value;
    }

    /**
     * Concatenate multiple fields
     *
     * Config: { "fields": ["firstname", "lastname"], "separator": " " }
     *
     * @param array $fullpayload Full payload
     * @param array $config Configuration
     * @return string Concatenated value
     */
    private static function apply_concat($fullpayload, $config) {
        $fields = $config['fields'] ?? [];
        $separator = $config['separator'] ?? ' ';

        $values = [];
        foreach ($fields as $field) {
            if (isset($fullpayload[$field]) && $fullpayload[$field] !== '') {
                $values[] = $fullpayload[$field];
            }
        }

        return implode($separator, $values);
    }

    /**
     * Extract substring
     *
     * Config: { "start": 0, "length": 10 }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return string Substring
     */
    private static function apply_substring($value, $config) {
        if ($value === null || $value === '') {
            return $value;
        }

        $start = $config['start'] ?? 0;
        $length = $config['length'] ?? null;

        if ($length === null) {
            return mb_substr((string)$value, $start, null, 'UTF-8');
        }

        return mb_substr((string)$value, $start, $length, 'UTF-8');
    }

    /**
     * Regex replacement
     *
     * Config: { "pattern": "/[^a-z0-9]/i", "replacement": "_" }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return string Replaced value
     */
    private static function apply_regex($value, $config) {
        if ($value === null || $value === '') {
            return $value;
        }

        $pattern = $config['pattern'] ?? '';
        $replacement = $config['replacement'] ?? '';

        if (empty($pattern)) {
            return $value;
        }

        return preg_replace($pattern, $replacement, (string)$value);
    }

    /**
     * Conditional transformation
     *
     * Config: {
     *   "condition": "equals|contains|starts_with|ends_with",
     *   "value": "student",
     *   "true": "learner",
     *   "false": "staff"
     * }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return mixed Conditional result
     */
    private static function apply_conditional($value, $config) {
        $condition = $config['condition'] ?? 'equals';
        $comparevalue = $config['value'] ?? '';
        $truevalue = $config['true'] ?? $value;
        $falsevalue = $config['false'] ?? $value;

        $matches = false;
        $valuestr = (string)$value;

        switch ($condition) {
            case 'equals':
                $matches = ($valuestr === $comparevalue);
                break;

            case 'contains':
                $matches = (mb_strpos($valuestr, $comparevalue, 0, 'UTF-8') !== false);
                break;

            case 'starts_with':
                $matches = (mb_substr($valuestr, 0, mb_strlen($comparevalue, 'UTF-8'), 'UTF-8') === $comparevalue);
                break;

            case 'ends_with':
                $len = mb_strlen($comparevalue, 'UTF-8');
                $matches = (mb_substr($valuestr, -$len, null, 'UTF-8') === $comparevalue);
                break;

            default:
                $matches = false;
        }

        return $matches ? $truevalue : $falsevalue;
    }

    /**
     * Trim whitespace or characters
     *
     * Config: { "chars": " \t\n\r" }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return string Trimmed value
     */
    private static function apply_trim($value, $config) {
        if ($value === null || $value === '') {
            return $value;
        }

        $chars = $config['chars'] ?? " \t\n\r\0\x0B";
        return trim((string)$value, $chars);
    }

    /**
     * Provide default value if empty
     *
     * Config: { "value": "N/A" }
     *
     * @param mixed $value The value
     * @param array $config Configuration
     * @return mixed Value or default
     */
    private static function apply_default($value, $config) {
        if ($value === null || $value === '') {
            return $config['value'] ?? '';
        }
        return $value;
    }

    /**
     * Test a transformation with sample input
     *
     * @param string $type Transformation type
     * @param array $config Configuration
     * @param mixed $sampleinput Sample input value
     * @param array $samplepayload Sample full payload for concat/conditional
     * @return mixed Transformed output
     */
    public static function test_transformation($type, $config, $sampleinput, $samplepayload = []) {
        return self::apply_transformation($sampleinput, $type, $config, $samplepayload);
    }
}
