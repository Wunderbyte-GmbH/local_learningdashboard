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
 * Settings for the Learning Dashboard plugin.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a new settings page in the Site Administration > Local Plugins menu.
    $settings = new admin_settingpage(
        'local_learningdashboard_settings',
        new lang_string('settings', 'local_learningdashboard')
    );

    // Add page to navigation only if permissions granted.
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        // Header for course filtering section.
        $settings->add(new admin_setting_heading(
            'local_learningdashboard_course_filtering',
            new lang_string('coursefilteringheading', 'local_learningdashboard'),
            new lang_string('coursefilteringheading_desc', 'local_learningdashboard')
        ));

        // Setting: Include only specific courses.
        // If empty, all courses will be included.
        // Build course options array from course objects.
        $courses = get_courses();
        $courseoptions = [];
        foreach ($courses as $course) {
            if ($course->id != SITEID) {
                // Skip the site course.
                $courseoptions[$course->id] = format_string($course->fullname);
            }
        }

        $settings->add(new admin_setting_configmultiselect(
            'local_learningdashboard/includedcourses',
            new lang_string('includedcourses', 'local_learningdashboard'),
            new lang_string('includedcourses_desc', 'local_learningdashboard'),
            [],
            $courseoptions
        ));

        // Header for display options section.
        $settings->add(new admin_setting_heading(
            'local_learningdashboard_display_options',
            new lang_string('displayoptionsheading', 'local_learningdashboard'),
            new lang_string('displayoptionsheading_desc', 'local_learningdashboard')
        ));

        // Setting: Show gamification points (Geschäftsabschlüsse).
        $settings->add(new admin_setting_configcheckbox(
            'local_learningdashboard/showgpoints',
            new lang_string('showgpoints', 'local_learningdashboard'),
            new lang_string('showgpoints_desc', 'local_learningdashboard'),
            1
        ));

        // Setting: Show learning goal control points (Lernzielkontrollen).
        $settings->add(new admin_setting_configcheckbox(
            'local_learningdashboard/showlzk',
            new lang_string('showlzk', 'local_learningdashboard'),
            new lang_string('showlzk_desc', 'local_learningdashboard'),
            1
        ));

        // Header for activity name matching patterns.
        $settings->add(new admin_setting_heading(
            'local_learningdashboard_name_patterns',
            new lang_string('namepatternsheading', 'local_learningdashboard'),
            new lang_string('namepatternsheading_desc', 'local_learningdashboard')
        ));

        // Setting: Pattern for LZK/quiz names.
        $settings->add(new admin_setting_configtext(
            'local_learningdashboard/lzknamepattern',
            new lang_string('lzknamepattern', 'local_learningdashboard'),
            new lang_string('lzknamepattern_desc', 'local_learningdashboard'),
            'Quiz%',
            PARAM_TEXT
        ));

        // Setting: Pattern for Kompetenznachweis / gpoints names.
        $settings->add(new admin_setting_configtext(
            'local_learningdashboard/gpointsnamepattern',
            new lang_string('gpointsnamepattern', 'local_learningdashboard'),
            new lang_string('gpointsnamepattern_desc', 'local_learningdashboard'),
            'Kompetenzcheck%',
            PARAM_TEXT
        ));
    }
}
