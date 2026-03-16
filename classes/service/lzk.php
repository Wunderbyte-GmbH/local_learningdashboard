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
     * Load learning goal control points data from the database.
     *
     * This method retrieves all quiz grades where the quiz name starts with 'Q:'
     * and caches them for efficient access. Only executes once, subsequent calls return early.
     *
     * @return void
     */
    private function load() {
        global $DB;

        if ($this->cache !== null) {
            return;
        }

        $sql = "
            SELECT
                u.id AS userid,
                gi.courseid,
                q.name,
                ROUND(COALESCE(gg.finalgrade, 0), 2) AS finalgrade,
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
            LEFT JOIN {grade_grades} gg
                ON gg.itemid = gi.id
                AND gg.userid = u.id
            WHERE q.name LIKE 'Quiz%'
        ";

        $rs = $DB->get_recordset_sql($sql);

        $this->cache = [];

        foreach ($rs as $r) {
            $this->cache[$r->userid][$r->courseid][] = [
                'name' => $r->name,
                'points' => $r->finalgrade,
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
