<?php
/**
 * General helper functions for GUCAIR.
 */

if (!function_exists('get_assessment_level')) {
    /**
     * Convert a 1.00-5.00 average score into a readable readiness level.
     *
     * Boundaries:
     *   1.00-2.00 Beginner
     *   2.01-3.00 Developing
     *   3.01-4.00 Intermediate
     *   4.01-5.00 Advanced
     */
    function get_assessment_level(float $average): string
    {
        // Round first so values such as 2.004 do not fall between levels.
        $average = round($average, 2);

        if ($average >= 1.0 && $average <= 2.0) {
            return 'Beginner';
        }

        if ($average > 2.0 && $average <= 3.0) {
            return 'Developing';
        }

        if ($average > 3.0 && $average <= 4.0) {
            return 'Intermediate';
        }

        if ($average > 4.0 && $average <= 5.0) {
            return 'Advanced';
        }

        // Safety net for unexpected values.
        return 'Unrated';
    }
}

if (!function_exists('format_bson_date')) {
    /**
     * Turn a MongoDB UTCDateTime into a simple date for display.
     */
    function format_bson_date($value): string
    {
        if ($value instanceof MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d');
        }

        return '—';
    }
}

if (!function_exists('assessment_categories')) {
    /**
     * Shared field names and labels for the five assessment ratings.
     */
    function assessment_categories(): array
    {
        return [
            'ai_education'      => 'AI Education and Curriculum',
            'ai_research'       => 'AI Research Activities',
            'ai_infrastructure' => 'AI Infrastructure',
            'faculty_knowledge' => 'Faculty AI Knowledge',
            'ai_policy'         => 'AI Policy and Governance',
        ];
    }
}

if (!function_exists('is_safe_http_url')) {
    /**
     * Only http/https URLs should become clickable links.
     */
    function is_safe_http_url($value): bool
    {
        $value = trim((string) $value);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        return $scheme === 'http' || $scheme === 'https';
    }
}
