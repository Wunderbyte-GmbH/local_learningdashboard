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
 * Trainer badges overview page.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/learningdashboard:viewtrainer', $context);

$PAGE->set_url('/local/learningdashboard/trainer_badges.php');
$PAGE->set_context($context);
$PAGE->set_title('Badges Übersicht');
$PAGE->set_heading('Badges Übersicht');

echo $OUTPUT->header();

$table = new \local_learningdashboard\table\badge_table('trainer_badges' . $USER->id);

$table->set_sql(
    "
    bi.id,
    u.id as userid,
    u.firstname,
    u.lastname,
    b.name as badgename,
    b.type,
    b.courseid,
    bi.dateissued
    ",
    "
    {badge_issued} bi
    JOIN {badge} b ON b.id = bi.badgeid
    JOIN {user} u ON u.id = bi.userid
    ",
    "
    u.deleted = 0
    AND u.city = :city
    ",
    ['city' => $USER->city]
);

$table->define_columns([
    'fullname',
    'badgename',
    'type',
    'dateissued',
]);

$table->define_headers([
    'Name',
    'Badge',
    'Typ',
    'Erhalten am',
]);

$table->out(25, true);

echo $OUTPUT->footer();
