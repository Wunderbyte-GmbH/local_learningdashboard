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
 * Base learning dashboard table class.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\table;

use local_wunderbyte_table\wunderbyte_table;
use html_writer;
use stdClass;

/**
 * Base class for all learning dashboard tables.
 *
 * Provides common functionality and column formatting methods used across
 * all learning dashboard table implementations.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_learningdashboard_table extends wunderbyte_table {
    /**
     * @var string component where cache definition is to be found.
     */
    public $cachecomponent = 'local_learningdashboard';

    /**
     * @var string name of the cache definition in the above defined component.
     */
    public $rawcachename = 'cachedrawdata';

    /**
     * @var string name of the cache definition in the above defined component.
     */
    public $renderedcachename = 'cachedfulltable';

    /**
     * Format the fullname column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted full name.
     */
    public function col_fullname($values): string {
        return fullname($values);
    }


    /**
     * Format the fullname column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted full name.
     */
    public function col_coursename($values): string {
        $url = new \moodle_url('/course/view.php', ['id' => $values->courseid]);
        return html_writer::link($url, format_string($values->coursename));
    }

    /**
     * Format the progress column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted progress bar as HTML.
     */
    public function col_userprogress(stdClass $values): string {
        $percent = round($values->userprogress, 1);

        $bar = html_writer::div('', 'progress-bar', [
            'role' => 'progressbar',
            'style' => 'width:' . $percent . '%;',
            'aria-valuenow' => $percent,
            'aria-valuemin' => 0,
            'aria-valuemax' => 100,
        ]);

        return html_writer::div($bar, 'progress') .
               html_writer::div($percent . '%', 'small mt-1');
    }

    /**
     * Format the week difference column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted week difference as HTML.
     */
    public function col_weekdiff(stdClass $values): string {
        $diff = round($values->weekdiff, 1);

        $class = $diff < 0 ? 'text-danger' : 'text-success';

        return html_writer::tag('span', $diff . '%', ['class' => $class]);
    }
    /**
     * Format the gpoints (gamification points) column for display.
     *
     * @param stdClass $row The row data object containing userid and courseid.
     * @return string The formatted gamification points as HTML.
     */
    public function col_gpoints($row) {
        $service = \local_learningdashboard\service\gpoints::instance();

        $points = $service->get_user_course_points(
            $row->id,
            $row->courseid
        );

        $out = [];

        foreach ($points as $p) {
            $out[] = $p['name'] . ' - ' . (int)round($p['points']);
        }

        return implode('<br>', $out);
    }

    /**
     * Format the lzk (learning goal control) column for display.
     *
     * @param stdClass $row The row data object containing userid and courseid.
     * @return string The formatted learning goal control points as HTML.
     */
    public function col_lzk($row) {
        $service = \local_learningdashboard\service\lzk::instance();

        $points = $service->get_user_course_points(
            $row->id,
            $row->courseid
        );

        $out = [];

        foreach ($points as $p) {
            $out[] = $p['name'] . ' – ' . $p['points'] . ' ' . $p['timemodified'];
        }

        return implode('<br>', $out);
    }

    /**
     * Format the weekly activities column for display.
     *
     * @param stdClass $row The row data object containing userid.
     * @return string The number of weekly activities.
     */
    public function col_weeklyactivities($row) {
        $service = \local_learningdashboard\service\activities::instance();
        return $service->get_weekly_activities($row->id, $row->courseid);
    }

    /**
     * Format the monthly activities column for display.
     *
     * @param stdClass $row The row data object containing userid.
     * @return string The number of monthly activities.
     */
    public function col_monthlyactivities($row) {
        $service = \local_learningdashboard\service\activities::instance();
        return $service->get_monthly_activities($row->id, $row->courseid);
    }

    /**
     * Format the submitted at date column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted date.
     */
    public function col_submittedat(stdClass $values): string {
        return userdate($values->submittedat);
    }

    /**
     * Format the attempt number column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted attempt number (1-based).
     */
    public function col_attemptnumber(stdClass $values): string {
        return (int)$values->attemptnumber + 1;
    }

    /**
     * Format the date issued column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted date.
     */
    public function col_dateissued(stdClass $values): string {
        return userdate($values->dateissued);
    }

    /**
     * Format the badge type column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The badge type (Site Badge or Course Badge).
     */
    public function col_type(stdClass $values): string {
        return $values->type == 1 ? 'Site Badge' : 'Course Badge';
    }

    /**
     * Format the badge name column for display with bold formatting.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted badge name in bold.
     */
    public function col_badgename(stdClass $values): string {
        return html_writer::tag('strong', $values->badgename);
    }
}
