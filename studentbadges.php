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
 * Student badges page.
 *
 * @package   local_learningdashboard
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/learningdashboard:viewstudent', $context);

$PAGE->set_url('/local/learningdashboard/student_badges.php');
$PAGE->set_context($context);
$PAGE->set_title('Meine Badges');
$PAGE->set_heading('Meine Badges');

echo $OUTPUT->header();

$table = new \local_learningdashboard\table\badge_table('student_badges' . $USER->id);

$table->set_sql(
    "
    bi.id,
    u.id as userid,
    u.firstname,
    u.lastname,
    u.city,
    u.department,
    b.name as badgename,
    b.type,
    b.courseid,
    bi.dateissued,
    COALESCE(
        (SELECT COUNT(*)
         FROM {course_modules_completion} cmc2
         WHERE cmc2.userid = u.id
         AND cmc2.timemodified >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ), 0) AS weeklyactivities,
    COALESCE(
        (SELECT COUNT(*)
         FROM {course_modules_completion} cmc3
         WHERE cmc3.userid = u.id
         AND cmc3.timemodified >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ), 0) AS monthlyactivities
    ",
    "
    {badge_issued} bi
    JOIN {badge} b ON b.id = bi.badgeid
    JOIN {user} u ON u.id = bi.userid
    ",
    "
    u.id = :userid
    ",
    ['userid' => $USER->id]
);

$table->define_columns([
    'badgename',
    'type',
    'dateissued',
    'weeklyactivities',
    'monthlyactivities',
]);

$table->define_headers([
    'Badge',
    'Typ',
    'Erhalten am',
    get_string('weeklyactivities', 'local_learningdashboard'),
    get_string('monthlyactivities', 'local_learningdashboard'),
]);

$table->out(25, true);

echo $OUTPUT->footer();
