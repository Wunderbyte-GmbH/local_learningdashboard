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
  * Trainer grading overview page displaying open gradings.
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

$PAGE->set_url('/local/learningdashboard/trainer_grading.php');
$PAGE->set_context($context);
$PAGE->set_title('Offene Bewertungen');
$PAGE->set_heading('Offene Bewertungen');

echo $OUTPUT->header();

$table = new \local_learningdashboard\table\grading_overview_table('grading_overviewtest' . $USER->id);

$standardfilter = new standardfilter('name', get_string('fullname'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('coursename', get_string('course'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('city', get_string('city'));
$table->add_filter($standardfilter);

$standardfilter = new standardfilter('department', get_string('department'));
$table->add_filter($standardfilter);


// $standardfilter = new standardfilter('name', get_string('course'));
// $table->add_filter($standardfilter);

/*
 * Headers
 */
$table->define_headers([
    'Name',
    'Kurs',
    'Aufgabe',
    'Abgegeben am',
    'Versuch',
    'Bewerten',
]);

/*
 * Columns
 */
$table->define_columns([
    'name',
    'coursename',
    'assignmentname',
    'submittedat',
    'attemptnumber',
    'gradelink',
]);

/*
 * SQL
 */

// Support multiple comma-separated cities for the trainer.
$usercities = array_map('trim', explode(',', $USER->city ?? ''));
$usercities = array_filter($usercities, function($c) { return $c !== ''; });
$cityplaceholders = [];
$cityparams = [];
if (!empty($usercities)) {
    foreach ($usercities as $i => $city) {
        $key = 'gradcity' . $i;
        $cityplaceholders[] = ':' . $key;
        $cityparams[$key] = $city;
    }
    $citysql = 'u.city IN (' . implode(',', $cityplaceholders) . ')';
} else {
    $citysql = '1=1';
}

$fields = "grading.*";

$innerselect = "
    SELECT
        s.id AS rowid,
        u.id AS userid,
        " . $DB->sql_fullname('u.firstname', 'u.lastname') . " AS name,
        u.city,
        u.department,
        c.fullname AS coursename,
        c.id AS courseid,
        a.name AS assignmentname,
        s.timemodified AS submittedat,
        s.attemptnumber,
        cm.id AS cmid
    FROM {assign_submission} s
    JOIN {assign} a ON a.id = s.assignment
    JOIN {modules} md ON md.name = 'assign'
    JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = md.id
    JOIN {course} c ON c.id = cm.course
    JOIN {user} u ON u.id = s.userid
    WHERE
        s.status = 'submitted'
        AND s.latest = 1
        AND u.deleted = 0
        AND s.timemodified < :time
        AND $citysql
";

$from = "($innerselect) grading";

$where = "1=1";

$params = array_merge(
    ['time' => time()],
    $cityparams
);

$table->set_filter_sql($fields, $from, $where, '', $params);

/*
 * Sorting
 */
$table->sortable(true, 'name', SORT_ASC);

$table->define_sortablecolumns([
    'name',
    'coursename',
    'assignmentname',
    'submittedat',
    'weeklyactivities',
    'monthlyactivities',
]);

/*
 * Fulltext search
 */
$table->define_fulltextsearchcolumns([
    'name',
    'coursename',
    'assignmentname',
]);
$table->showfilterontop = true;

$table->pageable(true);

$table->showcountlabel = true;
$table->showdownloadbutton = true;
$table->showreloadbutton = true;

$table->out(25, true);

echo $OUTPUT->footer();
