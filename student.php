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
 * Student dashboard view page.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
use local_learningdashboard\table\student_progress_table;

use local_wunderbyte_table\filters\types\standardfilter;

require_login();

$context = context_system::instance();
require_capability('local/learningdashboard:viewstudent', $context);

$PAGE->set_url('/local/learningdashboard/student.php');
$PAGE->set_context($context);
$PAGE->set_title('Student Dashboard');
$PAGE->set_heading('Student Dashboard');

echo $OUTPUT->header();

$table = new student_progress_table('student_progress' . $USER->id);

$fullname = $DB->sql_fullname('u.firstname', 'u.lastname');

$weekago = time() - 7 * 24 * 60 * 60;
$monthago = time() - 30 * 24 * 60 * 60;

// Headers.
$table->define_headers([
    get_string('course'),
    get_string('progress'),
     get_string('geschaeftsabschluesse', 'local_learningdashboard'),
     get_string('lernzielkontrollen', 'local_learningdashboard'),
     get_string('weeklyactivities', 'local_learningdashboard'),
     get_string('monthlyactivities', 'local_learningdashboard'),
     get_string('lastactive', 'local_learningdashboard'),
    ]);

// Columns.
$table->define_columns([
    'coursename',
    'userprogress',
    'gpoints',
    'lzk',
    'weeklyactivities',
    'monthlyactivities',
    'lastactive',
    ]);

    /*
     * Define SQL like in egbooking_table
     */

    $fields = "m.*";

    $from = "(
        SELECT
            CONCAT(u.id, '-', c.id) AS rowid,
            u.id,
            c.id AS courseid,
            c.fullname as coursename,
            '' AS gpoints,
            '' AS lzk,
            COALESCE(act.weeklyactivities, 0) AS weeklyactivities,
            COALESCE(act.monthlyactivities, 0) AS monthlyactivities,
            act.lastactive,
            COALESCE(
                ROUND(
                    SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(cm.id), 0) * 100,
                2),
            0) AS userprogress
        FROM {course} c
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue
            ON ue.enrolid = e.id
            AND ue.userid = :userid1
        JOIN {user} u
            ON u.id = ue.userid
        LEFT JOIN {course_modules} cm
            ON cm.course = c.id
            AND cm.completion > 0
            AND cm.visible = 1
        LEFT JOIN {course_modules_completion} cmc
            ON cmc.coursemoduleid = cm.id
            AND cmc.userid = :userid2
        LEFT JOIN (
            SELECT
                cmc.userid,
                cm.course,
                SUM(CASE WHEN cmc.timemodified >= :weekago1 THEN 1 ELSE 0 END) AS weeklyactivities,
                SUM(CASE WHEN cmc.timemodified >= :monthago1 THEN 1 ELSE 0 END) AS monthlyactivities,
                MAX(cmc.timemodified) AS lastactive
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
            GROUP BY cmc.userid, cm.course
        ) act ON act.course = c.id AND act.userid = u.id
        WHERE c.visible = 1
        GROUP BY u.id, c.id, c.fullname, act.weeklyactivities, act.monthlyactivities, act.lastactive
    ) m";

    $where = "1=1";

    $params = [
        'userid1' => $USER->id,
        'userid2' => $USER->id,
        'weekago1' => $weekago,
        'monthago1' => $monthago,
    ];

    /*
    * Course filter
    */
    [$coursefiltersql, $coursefilterparams] = local_learningdashboard_get_course_filter_sql('c');

    if (!empty($coursefiltersql)) {
        $from = "(
            SELECT
                CONCAT(u.id, '-', c.id) AS rowid,
                u.id,
                c.id AS courseid,
                c.fullname as coursename,
                '' AS gpoints,
                '' AS lzk,
                COALESCE(act.weeklyactivities, 0) AS weeklyactivities,
                COALESCE(act.monthlyactivities, 0) AS monthlyactivities,
                act.lastactive,
                ROUND(
                    (SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) * 100.0)
                    / NULLIF(COUNT(cm.id), 0),
                2) AS userprogress
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue
                ON ue.enrolid = e.id
                AND ue.userid = :userid1
            JOIN {user} u
                ON u.id = ue.userid
            LEFT JOIN {course_modules} cm
                ON cm.course = c.id
                AND cm.completion > 0
                AND cm.visible = 1
            LEFT JOIN {course_modules_completion} cmc
                ON cmc.coursemoduleid = cm.id
                AND cmc.userid = :userid2
            LEFT JOIN (
                SELECT
                    cmc.userid,
                    cm.course,
                    SUM(CASE WHEN cmc.timemodified >= :weekago2 THEN 1 ELSE 0 END) AS weeklyactivities,
                    SUM(CASE WHEN cmc.timemodified >= :monthago2 THEN 1 ELSE 0 END) AS monthlyactivities,
                    MAX(cmc.timemodified) AS lastactive
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                GROUP BY cmc.userid, cm.course
            ) act ON act.course = c.id AND act.userid = u.id
            WHERE c.visible = 1 AND " . $coursefiltersql . "
            GROUP BY u.id, c.id, c.fullname, act.weeklyactivities, act.monthlyactivities, act.lastactive
        ) m";
        $params = array_merge($params, $coursefilterparams, [
            'weekago2' => $weekago,
            'monthago2' => $monthago,
        ]);
    }

    $table->set_filter_sql($fields, $from, $where, '', $params);

    $table->sortable(true, 'coursename', SORT_ASC);

    $table->define_sortablecolumns([
        'coursename',
        'userprogress',
        'gpoints',
        'lzk',
        'weeklyactivities',
        'monthlyactivities',
        'lastactive',
    ]);

    $table->define_fulltextsearchcolumns([
        'fullname',
    ]);

    $table->pageable(true);

    $table->showcountlabel = true;
    $table->showdownloadbutton = true;
    $table->showreloadbutton = true;
    $table->out(25, true);

    echo $OUTPUT->footer();
