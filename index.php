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
 * Index page for the Learning Dashboard.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_url('/local/learningdashboard/index.php');
$PAGE->set_context($context);
$PAGE->set_title('Learning Dashboard');
$PAGE->set_heading('Learning Dashboard');

$cards = [];

if (is_siteadmin()) {
    $cards[] = [
    'title' => 'Admin View',
    'url' => (new moodle_url('/local/learningdashboard/admin.php'))->out(),
    'icon' => 'fa-cog',
    ];
}
if (has_capability('local/learningdashboard:viewtrainer', $context)) {
    $cards[] = [
        'title' => 'Trainer View',
        'url' => (new moodle_url('/local/learningdashboard/trainer.php'))->out(),
        'icon' => 'fa-chalkboard-teacher',
    ];

    $cards[] = [
        'title' => 'Rehacoach View',
        'url' => (new moodle_url('/local/learningdashboard/rehacoach.php'))->out(),
        'icon' => 'fa-user-md',
    ];

    $cards[] = [
        'title' => 'Trainer Grading',
        'url' => (new moodle_url('/local/learningdashboard/trainer_grading.php'))->out(),
        'icon' => 'fa-clipboard-check',
    ];
}

if (has_capability('local/learningdashboard:viewstudent', $context)) {
    $cards[] = [
        'title' => 'Student View',
        'url' => (new moodle_url('/local/learningdashboard/student.php'))->out(),
        'icon' => 'fa-user-graduate',
    ];

    $cards[] = [
        'title' => 'Student Badges',
        'url' => (new moodle_url('/local/learningdashboard/studentbadges.php'))->out(),
        'icon' => 'fa-award',
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_learningdashboard/dashboard',
    ['cards' => $cards]
);

echo $OUTPUT->footer();
