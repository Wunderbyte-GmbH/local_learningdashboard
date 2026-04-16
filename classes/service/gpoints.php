<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The gpoints service class.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\service;

/**
 * Service class for handling gamification points in courses.
 *
 * This class provides functionality to retrieve and cache gamification points
 * from assignment grades for users across courses.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gpoints {
    /**
     * @var ?gpoints Singleton instance of the gpoints service.
     */
    private static $instance = null;

    /**
     * @var ?array Cache for gamification points data indexed by userid and courseid.
     */
    private $cache = null;

    /**
     * Get or create a singleton instance of the gpoints service.
     *
     * @return self The singleton instance.
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return a safe configured SQL LIKE pattern.
     *
     * @param string $settingname The config key.
     * @param string $default The fallback pattern.
     * @return string
     */
    private function get_safe_like_pattern(string $settingname, string $default): string {
        $pattern = trim((string)get_config('local_learningdashboard', $settingname));
        $pattern = clean_param($pattern, PARAM_TEXT);

        if ($pattern === '' || preg_match('/[\'";`\\]/', $pattern)) {
            $pattern = $default;
        }

        if (strpos($pattern, '%') === false && strpos($pattern, '_') === false) {
            $pattern .= '%';
        }

        return $pattern;
    }

    /**
     * Load gamification points data from the database.
     *
     * This method retrieves all assignment grades matching the configured
     * competency-check name pattern and caches them for efficient access.
     * Only executes once, subsequent calls return early.
     *
     * @return void
     */
    private function load() {
        global $DB;

        if ($this->cache !== null) {
            return;
        }

        $namepattern = $this->get_safe_like_pattern('gpointsnamepattern', 'Kompetenzcheck%');

        $sql = "
SELECT
    u.id AS userid,
    gi.courseid,
    a.name,
    COALESCE(gg.finalgrade, 0) AS finalgrade
FROM {assign} a
JOIN {grade_items} gi
    ON gi.iteminstance = a.id
    AND gi.itemmodule = 'assign'
JOIN {course} c
    ON c.id = gi.courseid
JOIN {enrol} e
    ON e.courseid = c.id
JOIN {user_enrolments} ue
    ON ue.enrolid = e.id
JOIN {user} u
    ON u.id = ue.userid
JOIN {grade_grades} gg
    ON gg.itemid = gi.id
    AND gg.userid = u.id
WHERE " . $DB->sql_like('a.name', ':gpointsnamepattern', false, false) . "
    AND gg.finalgrade IS NOT NULL
        ";

        $rs = $DB->get_recordset_sql($sql, ['gpointsnamepattern' => $namepattern]);

        $this->cache = [];

        foreach ($rs as $r) {
            $this->cache[$r->userid][$r->courseid][] = [
                'name' => $r->name,
                'points' => $r->finalgrade,
            ];
        }

        $rs->close();
    }

    /**
     * Get gamification points for a specific user in a specific course.
     *
     * @param int $userid The ID of the user.
     * @param int $courseid The ID of the course.
     *
     * @return array An array of points where each element contains 'name' and 'points' keys.
     *               Returns an empty array if no points are found for the user/course combination.
     */
    public function get_user_course_points(int $userid, int $courseid): array {
        $this->load();
        return $this->cache[$userid][$courseid] ?? [];
    }
}
