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

$PAGE->set_url('/local/learningdashboard/trainer.php');
$PAGE->set_context($context);
$PAGE->set_title('Trainer Dashboard');
$PAGE->set_heading('Trainer Dashboard');

echo $OUTPUT->header();

$table = new \local_learningdashboard\table\trainer_progress_table('reha_coach_progress');

$standardfilter = new standardfilter('name', get_string('fullname'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('city', get_string('city'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('coursename', get_string('course'));
$table->add_filter($standardfilter);

/*
 * Headers
 */
$table->define_headers([
    get_string('name'),
    get_string('course'),
    get_string('city'),
    get_string('rehacoach', 'local_learningdashboard'),
    get_string('progress'),
    get_string('geschaeftsabschluesse', 'local_learningdashboard'),
    get_string('lernzielkontrollen', 'local_learningdashboard'),
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
]);

/*
 * SQL
 */

$fields = "m.*";

$from = "(
    SELECT
        CONCAT(u.id, '-', c.id) AS rowid,
        u.id,
        CONCAT(u.firstname, ' ', u.lastname) AS name,
        u.city,
        u.department,
        u.timemodified,
        c.id AS courseid,
        c.fullname AS coursename,
        '' as gpoints,
            COALESCE(
                ROUND(
                    100 * COUNT(DISTINCT CASE WHEN cmc.completionstate = 1 THEN cm.id END)
                    / NULLIF(COUNT(DISTINCT cm.id), 0),
                2),
            0) AS progress
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    LEFT JOIN {course_modules} cm
        ON cm.course = c.id
        AND cm.completion > 0
        AND cm.visible = 1
    LEFT JOIN {course_modules_completion} cmc
        ON cmc.coursemoduleid = cm.id
        AND cmc.userid = u.id
    WHERE
        u.deleted = 0
        AND u.department = :department
        And u.timemodified = :time
    GROUP BY
        u.id,
        u.firstname,
        u.lastname,
        u.city,
        u.department,
        c.id,
        c.fullname
) m";

$where = "1=1";

$params = [
    'department' => $USER->department,
    'time' => time(),
];

// Apply course filtering if configured.
[$coursefiltersql, $coursefilterparams] = local_learningdashboard_get_course_filter_sql('c');
if (!empty($coursefiltersql)) {
    // We need to modify the FROM clause to include course filtering within the subquery.
    $from = "(
        SELECT
            CONCAT(u.id, '-', c.id) AS rowid,
            u.id,
            CONCAT(u.firstname, ' ', u.lastname) AS name,
            u.city,
            u.timemodified,
            u.department,
            c.id AS courseid,
            c.fullname AS coursename,
            '' as gpoints,
            COALESCE(
                ROUND(
                    100 * COUNT(DISTINCT CASE WHEN cmc.completionstate = 1 THEN cm.id END)
                    / NULLIF(COUNT(DISTINCT cm.id), 0),
                2),
            0) AS progress
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        LEFT JOIN {course_modules} cm
            ON cm.course = c.id
            AND cm.completion > 0
            AND cm.visible = 1
        LEFT JOIN {course_modules_completion} cmc
            ON cmc.coursemoduleid = cm.id
            AND cmc.userid = u.id
        WHERE
            u.deleted = 0
            AND u.department = :department
            AND u.timemodified < :time
            AND " . $coursefiltersql . "
        GROUP BY
            u.id,
            u.firstname,
            u.lastname,
            u.city,
            u.department,
            c.id,
            c.fullname
    ) m";
    $params = array_merge($params, $coursefilterparams);
}

$table->set_filter_sql($fields, $from, $where, '', $params);

$table->sortable(true, 'name', SORT_ASC);

$table->define_sortablecolumns([
    'name',
    'coursename',
    'city',
    'department',
    'userprogress',
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
