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
 * Badge table class for learning dashboard.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\table;

use local_wunderbyte_table\wunderbyte_table;
use html_writer;
use stdClass;

/**
 * Table class for displaying badge information.
 *
 * This table extends wunderbyte_table to display user badges with custom column formatting
 * for badge-related information such as type, date issued, and badge names.
 */
class badge_table extends wunderbyte_table {
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
    public function col_fullname($values): string {
        return fullname($values);
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
