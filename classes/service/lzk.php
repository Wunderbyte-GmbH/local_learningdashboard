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
 * The lzk (learning goal control) service class.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\service;

/**
 * Service class for handling learning goal control (LZK) points in courses.
 *
 * This class provides functionality to retrieve and cache learning goal control points
 * from quiz grades for users across courses.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lzk {
    /**
     * @var ?lzk Singleton instance of the lzk service.
     */
    private static $instance = null;

    /**
     * @var ?array Cache for learning goal control points data indexed by userid and courseid.
     */
    private $cache = null;

    /**
     * Get or create a singleton instance of the lzk service.
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
     * Load learning goal control points data from the database.
     *
     * This method retrieves all quiz grades matching the configured name pattern
     * and caches them for efficient access. Only executes once, subsequent calls return early.
     *
     * @return void
     */
    private function load() {
        global $DB;

        if ($this->cache !== null) {
            return;
        }

        $namepattern = $this->get_safe_like_pattern('lzknamepattern', 'Quiz%');
        $params = [
            'lzkpattern1' => $namepattern,
            'lzkpattern3' => $namepattern,
        ];

        $sql = "
            SELECT
                u.id AS userid,
                gi.courseid,
                q.name,
                ROUND(COALESCE(gg.finalgrade, 0), 2) AS finalgrade,
                gi.grademax,
                ROUND(
                    CASE WHEN gi.grademax > 0
                        THEN (COALESCE(gg.finalgrade, 0) / gi.grademax) * 100
                        ELSE 0
                    END
                , 1) AS percentage,
                gg.timemodified

            FROM {quiz} q
            JOIN {grade_items} gi
                ON gi.iteminstance = q.id
                AND gi.itemmodule = 'quiz'
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
            WHERE " . $DB->sql_like('q.name', ':lzkpattern1', false, false) . "
                AND gg.finalgrade IS NOT NULL
        ";

        // Only include HVP results if the hvp table exists.
        $pluginman = \core_plugin_manager::instance();
        if ($DB->get_manager()->table_exists('hvp') && $DB->get_manager()->table_exists('hvp_xapi_results')) {
            $sql .= "
            UNION ALL
            SELECT
            gg.userid,
            gi.courseid,
            gi.itemname AS name,
            ROUND(gg.finalgrade, 2) AS finalgrade,
            gi.grademax,
            ROUND(
                CASE WHEN gi.grademax > 0
                    THEN (gg.finalgrade / gi.grademax) * 100
                    ELSE 0
                END
            , 1) AS percentage,
            gg.timemodified

            FROM {grade_items} gi
            JOIN {grade_grades} gg ON gg.itemid = gi.id

            WHERE gi.itemmodule = 'hvp'
            AND " . $DB->sql_like('gi.itemname', ':lzkpattern2', false, false) . "
            AND gg.finalgrade IS NOT NULL
            ";
            $params['lzkpattern2'] = $namepattern;
        }

        // Include core H5P activity (mod_h5pactivity) results from attempts directly.
        $sql .= "
            UNION ALL

            SELECT
                haa.userid,
                ha.course AS courseid,
                ha.name,
                ROUND(COALESCE(
                    CASE WHEN MAX(haa.maxscore) > 0
                        THEN MAX(haa.rawscore) / MAX(haa.maxscore) * 100
                        ELSE MAX(haa.rawscore)
                    END
                , 0), 2) AS finalgrade,
                MAX(haa.maxscore) AS grademax,
                ROUND(
                    CASE WHEN MAX(haa.maxscore) > 0
                        THEN (MAX(haa.rawscore) / MAX(haa.maxscore)) * 100
                        ELSE 0
                    END
                , 1) AS percentage,
                MAX(haa.timemodified) AS timemodified

            FROM {h5pactivity} ha
            JOIN {h5pactivity_attempts} haa ON haa.h5pactivityid = ha.id
            JOIN {user} u ON u.id = haa.userid
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ha.course
            WHERE " . $DB->sql_like('ha.name', ':lzkpattern3', false, false) . "
                AND haa.rawscore IS NOT NULL
            GROUP BY haa.userid, ha.course, ha.name, ha.id
        ";

        $rs = $DB->get_recordset_sql($sql, $params);

        $this->cache = [];

        foreach ($rs as $r) {
            $this->cache[$r->userid][$r->courseid][] = [
                'name' => $r->name,
                'points' => $r->finalgrade,
                'maxpoints' => $r->grademax,
                'percentage' => $r->percentage,
                'timemodified' => $r->timemodified > 0 ? '(' . userdate($r->timemodified) . ')' : '',
            ];
        }

        $rs->close();
    }

    /**
     * Get learning goal control points for a specific user in a specific course.
     *
     * @param int $userid The ID of the user.
     * @param int $courseid The ID of the course.
     *
     * @return array An array of points where each element contains 'name', 'points' and 'timemodified' keys.
     *               Returns an empty array if no points are found for the user/course combination.
     */
    public function get_user_course_points(int $userid, int $courseid): array {
        $this->load();
        return $this->cache[$userid][$courseid] ?? [];
    }
}
