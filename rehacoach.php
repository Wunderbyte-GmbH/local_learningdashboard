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
 * Trainer dashboard view page displaying trainer progress information.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use local_wunderbyte_table\filters\types\standardfilter;

require_login();

$context = context_system::instance();
require_capability('local/learningdashboard:viewtrainer', $context);

// Get optional 'my' parameter to show only current user's data.
$my = optional_param('my', false, PARAM_BOOL);

$PAGE->set_url('/local/learningdashboard/rehacoach.php');
$PAGE->set_context($context);
$PAGE->set_title('Reha Coach Dashboard');
$PAGE->set_heading('Reha Coach Dashboard');

echo $OUTPUT->header();

// Add button to show all when my=1.
if ($my) {
    echo html_writer::link(
        new moodle_url('/local/learningdashboard/rehacoach.php', ['my' => 0]),
        get_string('showall', 'local_learningdashboard'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('div');
}

// Add button to show my department when my=0.
if (!$my && !empty($USER->department)) {
    echo html_writer::link(
        new moodle_url('/local/learningdashboard/rehacoach.php', ['my' => 1]),
        get_string('showmy', 'local_learningdashboard'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('div');
}

$table = new \local_learningdashboard\table\trainer_progress_table('reha_coach_progress' .  $my . '_' . $USER->id);

$standardfilter = new standardfilter('name', get_string('fullname'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('coursename', get_string('course'));
$table->add_filter($standardfilter);

// Add department filter if user has no department assigned or if 'my' parameter is not set.
if (empty($USER->department) && !$my) {
    $standardfilter = new standardfilter('department', get_string('department'));
    $table->add_filter($standardfilter);
}

/*
 * Headers
 */
$table->define_headers([
    get_string('name'),
    get_string('course'),
    get_string('city'),
    get_string('department'),
    get_string('progress'),
    get_string('geschaeftsabschluesse', 'local_learningdashboard'),
    get_string('lernzielkontrollen', 'local_learningdashboard'),
    get_string('weeklyactivities', 'local_learningdashboard'),
    get_string('monthlyactivities', 'local_learningdashboard'),
]);

/*
 * Columns
 */
$table->define_columns([
    'name',
    'coursename',
    'city',
    'department',
    'userprogress',
    'gpoints',
    'lzk',
    'weeklyactivities',
    'monthlyactivities',
]);

/*
 * SQL
 */

$fullname = $DB->sql_fullname('u.firstname', 'u.lastname');

$fields = "m.*";

// Build WHERE clause for department filtering.
// If 'my' is true, show only current user's department (no filter needed).
// If 'my' is false and user has department, show only that department.
// If 'my' is false and user has no department, show all departments (filter available).
$departmentwhere = '';
$departmentparams = [];

if ($my && !empty($USER->department)) {
    // Show only current user's department.
    $departmentwhere = 'AND u.department = :department';
    $departmentparams = ['department' => $USER->department];
}
// If my=0, show all users (no department WHERE clause).

$from = "(
    SELECT
        CONCAT(u.id, '-', c.id) AS rowid,
        u.id,
        $fullname AS name,
        u.city,
        u.department,
        c.id AS courseid,
        c.fullname AS coursename,

        '' AS gpoints,
        '' AS lzk,
        '' AS weeklyactivities,
        '' AS monthlyactivities,

        COALESCE(
            ROUND(
                100 * COALESCE(cc.completed, 0) / NULLIF(cm.total, 0)
            , 2)
        , 0) AS userprogress

    FROM {user} u

    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid

    /* total modules per course */
    LEFT JOIN (
        SELECT
            course,
            COUNT(*) AS total
        FROM {course_modules}
        WHERE completion > 0
        AND visible = 1
        GROUP BY course
    ) cm ON cm.course = c.id

    /* completed modules per user/course */
    LEFT JOIN (
        SELECT
            cm.course,
            cmc.userid,
            COUNT(*) AS completed
        FROM {course_modules_completion} cmc
        JOIN {course_modules} cm
            ON cm.id = cmc.coursemoduleid
            AND cm.completion > 0
            AND cm.visible = 1
        WHERE cmc.completionstate = 1
        GROUP BY cm.course, cmc.userid
    ) cc ON cc.course = c.id AND cc.userid = u.id

    WHERE
        u.deleted = 0
        $departmentwhere
) m";

$where = "1=1";

$params = $departmentparams;

/*
 * Course filter
 */
[$coursefiltersql, $coursefilterparams] = local_learningdashboard_get_course_filter_sql('c');

if (!empty($coursefiltersql)) {
    $from = "(
        SELECT
            CONCAT(u.id, '-', c.id) AS rowid,
            u.id,
            $fullname AS name,
            u.city,
            u.department,
            c.id AS courseid,
            c.fullname AS coursename,

            '' AS gpoints,
            '' AS lzk,
            '' AS weeklyactivities,
            '' AS monthlyactivities,

            COALESCE(
                ROUND(
                    100 * COALESCE(cc.completed, 0) / NULLIF(cm.total, 0)
                , 2)
            , 0) AS userprogress

        FROM {user} u

        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid

        LEFT JOIN (
            SELECT
                course,
                COUNT(*) AS total
            FROM {course_modules}
            WHERE completion > 0
            AND visible = 1
            GROUP BY course
        ) cm ON cm.course = c.id

        LEFT JOIN (
            SELECT
                cm.course,
                cmc.userid,
                COUNT(*) AS completed
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm
                ON cm.id = cmc.coursemoduleid
                AND cm.completion > 0
                AND cm.visible = 1
            WHERE cmc.completionstate = 1
            GROUP BY cm.course, cmc.userid
        ) cc ON cc.course = c.id AND cc.userid = u.id

        WHERE
            u.deleted = 0
            $departmentwhere
            AND $coursefiltersql
    ) m";

    $params = array_merge($departmentparams, $coursefilterparams);
}

$table->set_filter_sql($fields, $from, $where, '', $params);

$table->sortable(true, 'name', SORT_ASC);

$table->define_sortablecolumns([
    'name',
    'coursename',
    'city',
    'department',
    'userprogress',
    'weeklyactivities',
    'monthlyactivities',
]);

$table->define_fulltextsearchcolumns([
    'name',
    'coursename',
]);

$table->showfilterontop = true;

$table->pageable(true);

$table->showcountlabel = true;
$table->showdownloadbutton = true;
$table->showreloadbutton = true;

$table->out(25, true);

echo $OUTPUT->footer();
