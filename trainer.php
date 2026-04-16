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

$table = new \local_learningdashboard\table\trainer_progress_table('trainer_progress_' . $USER->id);

$standardfilter = new standardfilter('name', get_string('fullname'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('coursename', get_string('course'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('city', get_string('city'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('department', get_string('department'));
$table->add_filter($standardfilter);

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
    get_string('lastactive', 'local_learningdashboard'),
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
    'lastactive',
]);


/*
 * SQL
 */

$fullname = $DB->sql_fullname('u.firstname', 'u.lastname');

// Support multiple comma-separated cities for the trainer.
$usercities = array_map('trim', explode(',', $USER->city ?? ''));
$usercities = array_filter($usercities, function($c) { return $c !== ''; });
$cityplaceholders = [];
$cityparams = [];
if (!empty($usercities)) {
    foreach ($usercities as $i => $city) {
        $key = 'city' . $i;
        $cityplaceholders[] = ':' . $key;
        $cityparams[$key] = $city;
    }
    $citysql = 'u.city IN (' . implode(',', $cityplaceholders) . ')';
} else {
    $citysql = '1=1';
}

$weekago = time() - 7 * 24 * 60 * 60;
$monthago = time() - 30 * 24 * 60 * 60;

$fields = "m.*";

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
        COALESCE(act.weeklyactivities, 0) AS weeklyactivities,
        COALESCE(act.monthlyactivities, 0) AS monthlyactivities,
        act.lastactive,

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

    /* activities data */
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

    WHERE
        u.deleted = 0
        AND $citysql
) m";

$where = "1=1";

$params = array_merge($cityparams, [
    'weekago1' => $weekago,
    'monthago1' => $monthago,
]);

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
            COALESCE(act.weeklyactivities, 0) AS weeklyactivities,
            COALESCE(act.monthlyactivities, 0) AS monthlyactivities,
            act.lastactive,

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

        WHERE
            u.deleted = 0
            AND $citysql
            AND $coursefiltersql
    ) m";

    $params = array_merge($cityparams, $coursefilterparams, [
        'weekago2' => $weekago,
        'monthago2' => $monthago,
    ]);
}

$table->set_filter_sql($fields, $from, $where, '', $params);

/*
 * Sorting
 */
$table->sortable(true, 'lastactive', SORT_DESC);

$table->define_sortablecolumns([
    'name',
    'coursename',
    'city',
    'department',
    'userprogress',
    'weeklyactivities',
    'monthlyactivities',
    'lastactive',
]);

/*
 * Fulltext search
 */
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
