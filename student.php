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
 * @copyright  2026
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

$standardfilter = new standardfilter('fullname', get_string('fullname'));
$table->add_filter($standardfilter);
// Headers.
$table->define_headers([
    get_string('course'),
    get_string('progress'),
     get_string('geschaeftsabschluesse', 'local_learningdashboard'),
     get_string('lernzielkontrollen', 'local_learningdashboard'),
    ]);

// Columns.
$table->define_columns([
    'fullname',
    'userprogress',
    'gpoints',
    'lzk',
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
            c.fullname,
            COALESCE(
                ROUND(
                    SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(cm.id), 0) * 100,
                2),
            0) AS progress
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
        WHERE c.visible = 1
        GROUP BY u.id, c.id, c.fullname
    ) m";

    $where = "1=1";

    $params = [
        'userid1' => $USER->id,
        'userid2' => $USER->id,
    ];

    // Apply course filtering if configured.
    [$coursefiltersql, $coursefilterparams] = local_learningdashboard_get_course_filter_sql('c');
    if (!empty($coursefiltersql)) {
        // We need to modify the WHERE clause to include course filtering within the subquery.
        $from = "(
            SELECT
                CONCAT(u.id, '-', c.id) AS rowid,
                u.id,
                c.id AS courseid,
                c.fullname,
                COALESCE(
                    ROUND(
                        SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END)
                        / NULLIF(COUNT(cm.id), 0) * 100,
                    2),
                0) AS progress
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
            WHERE c.visible = 1 AND " . $coursefiltersql . "
            GROUP BY u.id, c.id, c.fullname
        ) m";
        $params = array_merge($params, $coursefilterparams);
    }

    $table->set_filter_sql($fields, $from, $where, '', $params);

    $table->sortable(true, 'fullname', SORT_ASC);

    $table->define_sortablecolumns([
        'fullname',
        'userprogress',
        'gpoints',
        'lzk',
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
