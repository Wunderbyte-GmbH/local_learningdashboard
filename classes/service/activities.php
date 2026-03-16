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
 * The activities service class.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\service;

/**
 * Service class for handling user activities tracking.
 *
 * This class provides functionality to retrieve and cache activity data
 * from course module completions for users across courses, including
 * weekly and monthly activity counts.
 *
 * @package     local_learningdashboard
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities {
    /**
     * @var ?activities Singleton instance of the activities service.
     */
    private static $instance = null;

    /**
     * @var ?array Cache for activities data indexed by userid and courseid.
     */
    private $cache = null;

    /**
     * Get or create a singleton instance of the activities service.
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
     * Load activities data from the database.
     *
     * This method retrieves all course module completions for all users
     * and caches them for efficient access. Only executes once, subsequent
     * calls return early.
     *
     * @return void
     */
private function load() {
    global $DB;

    if ($this->cache !== null) {
        return;
    }

    $this->cache = [];

    $weekago = time() - 7 * 24 * 60 * 60;
    $monthago = time() - 30 * 24 * 60 * 60;

    $sql = "
        SELECT
            u.id AS userid,
            c.id AS courseid,
            COUNT(DISTINCT cm.id) AS totalactivities,
            SUM(CASE WHEN cmc.timemodified >= :weekago THEN 1 ELSE 0 END) AS weeklyactivities,
            SUM(CASE WHEN cmc.timemodified >= :monthago THEN 1 ELSE 0 END) AS monthlyactivities,
            MAX(cmc.timemodified) AS lastactivity
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        LEFT JOIN {course_modules} cm
            ON cm.course = c.id
        LEFT JOIN {course_modules_completion} cmc
            ON cmc.coursemoduleid = cm.id
            AND cmc.userid = u.id
        WHERE u.deleted = 0
        GROUP BY u.id, c.id
    ";

    $params = [
        'weekago' => $weekago,
        'monthago' => $monthago
    ];

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $record) {
        $key = $record->userid . '-' . $record->courseid;
        $this->cache[$key] = $record;
    }

    $rs->close();
}
    /**
     * Get weekly activity count for a specific user and course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return int The number of activities completed in the past week.
     */
    public function get_weekly_activities($userid, $courseid) {
        $this->load();
        $key = $userid . '-' . $courseid;
        return isset($this->cache[$key]) ? (int)$this->cache[$key]->weeklyactivities : 0;
    }

    /**
     * Get monthly activity count for a specific user and course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return int The number of activities completed in the past month.
     */
    public function get_monthly_activities($userid, $courseid) {
        $this->load();
        $key = $userid . '-' . $courseid;
        return isset($this->cache[$key]) ? (int)$this->cache[$key]->monthlyactivities : 0;
    }

    /**
     * Get total activity count for a specific user and course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return int The total number of activities completed.
     */
    public function get_total_activities($userid, $courseid) {
        $this->load();
        $key = $userid . '-' . $courseid;
        return isset($this->cache[$key]) ? (int)$this->cache[$key]->totalactivities : 0;
    }

    /**
     * Get last activity timestamp for a specific user and course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return int|null The timestamp of the last activity or null if none.
     */
    public function get_last_activity($userid, $courseid) {
        $this->load();
        $key = $userid . '-' . $courseid;
        return isset($this->cache[$key]) ? $this->cache[$key]->lastactivity : null;
    }

    /**
     * Get all activities data for a specific user across all courses.
     *
     * @param int $userid The user ID.
     * @return array Array of activity records indexed by courseid.
     */
    public function get_user_activities($userid) {
        $this->load();
        $useractivities = [];
        foreach ($this->cache as $key => $record) {
            if ($record->userid == $userid) {
                $useractivities[$record->courseid] = $record;
            }
        }
        return $useractivities;
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clear_cache() {
        $this->cache = null;
    }

    /**
     * Reset the singleton instance.
     *
     * @return void
     */
    public static function reset_instance() {
        self::$instance = null;
    }
}
