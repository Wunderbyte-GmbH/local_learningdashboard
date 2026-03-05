<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle hooks for local_learningdashboard
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function local_learningdashboard_render_navbar_output(\renderer_base $renderer) {
    global $CFG, $USER;

    $context = context_system::instance();

    $output = '';

    return $output;
}

/**
 * Get SQL WHERE clause for course filtering.
 *
 * Returns a WHERE clause fragment and parameters array for filtering courses
 * based on admin settings. If no courses are configured, returns empty string
 * and empty array (no filtering applied).
 *
 * @param string $coursealias The table alias for the course table (default 'c').
 * @return array [whereClause, params] SQL WHERE fragment and parameters.
 */
function local_learningdashboard_get_course_filter_sql($coursealias = 'c'): array {
    $includedcourses = get_config('local_learningdashboard', 'includedcourses');

    if (empty($includedcourses)) {
        return ['', []];
    }

    // Handle both serialized and JSON formats.
    $courseids = [];

    // Try JSON decode first.
    $decoded = json_decode($includedcourses, true);
    if (is_array($decoded)) {
        $courseids = $decoded;
    } else {
        // Try unserialize for backwards compatibility.
        $unserialized = @unserialize($includedcourses);
        if (is_array($unserialized)) {
            $courseids = $unserialized;
        } else {
            // If it's a plain string/number, treat it as a single course ID.
            $courseids = [$includedcourses];
        }
    }

    if (empty($courseids)) {
        return ['', []];
    }

    // Create named placeholders for IN clause.
    $placeholders = [];
    $params = [];
    foreach ($courseids as $index => $courseid) {
        $placeholders[] = ':filtercourse' . $index;
        $params['filtercourse' . $index] = (int)$courseid;
    }
    $whereclaused = $coursealias . '.id IN (' . implode(',', $placeholders) . ')';

    return [$whereclaused, $params];
}
