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
 * Grading overview table class for learning dashboard.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningdashboard\table;

use html_writer;
use moodle_url;
use stdClass;

/**
 * Table class for displaying grading overview information.
 *
 * This table extends base_learningdashboard_table to provide custom column formatting
 * for grading information.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_overview_table extends base_learningdashboard_table {
    /**
     * Format the grade link column for display with a link to grading page.
     *
     * @param stdClass $values The row data object.
     * @return string HTML link to the grading page.
     */
    public function col_gradelink(stdClass $values): string {
        $url = new moodle_url(
            '/mod/assign/view.php',
            [
                'id' => $values->cmid,
                'action' => 'grader',
                'userid' => $values->userid,
            ]
        );

        return html_writer::link(
            $url,
            'Bewerten',
            ['class' => 'btn btn-sm btn-primary']
        );
    }
}
