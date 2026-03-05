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

namespace local_learningdashboard\table;

use local_wunderbyte_table\wunderbyte_table;
use html_writer;
use stdClass;

/**
 * Trainer progress table class.
 *
 * @package local_learningdashboard
 * @copyright 2026
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trainer_progress_table extends wunderbyte_table {

    /**
     *
     * @var string component where cache defintion is to be found.
     */
    public $cachecomponent = 'local_learningdashboard';

    /**
     *
     * @var string name of the cache definition in the above defined component.
     */
    public $rawcachename = 'cachedrawdata';

    /**
     *
     * @var string name of the cache definition in the above defined component.
     */
    public $renderedcachename = 'cachedfulltable';
    
    /**
     * Format the fullname column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted full name.
     */
    public function col_fullname($values) {
        return fullname($values);
    }

    /**
     * Format the progress column for display.
     *
     * @param stdClass $values The row data object.
     * @return string The formatted progress bar as HTML.
     */
    public function col_userprogress(stdClass $values): string {
        $percent = round($values->progress, 1);

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
            $out[] = $p['name'] . ' – ' . $p['points'];
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
}
