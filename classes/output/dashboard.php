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

namespace local_learningdashboard\output;

use renderable;
use renderer_base;
use templatable;

/**
 * Dashboard output class for the Learning Dashboard plugin.
 *
 * @package   local_learningdashboard
 * @copyright 2025 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renderable and templatable dashboard class.
 */
class dashboard implements renderable, templatable {
    /**
     * The user ID.
     *
     * @var int
     */
    protected int $userid;

    /**
     * Whether the user is a trainer.
     *
     * @var bool
     */
    protected bool $istrainer;

    /**
     * Constructor for dashboard class.
     *
     * @param int $userid The user ID.
     * @param bool $istrainer Whether the user is a trainer.
     */
    public function __construct(int $userid, bool $istrainer = false) {
        $this->userid = $userid;
        $this->istrainer = $istrainer;
    }

    /**
     * Load dashboard data.
     *
     * @return array The dashboard data.
     */
    protected function load_data(): array {
        return [
            'userid' => $this->userid,
            'istrainer' => $this->istrainer,
        ];
    }

    /**
     * Export the dashboard data for template rendering.
     *
     * @param renderer_base $output The renderer base instance.
     * @return array The exported dashboard data.
     */
    public function export_for_template(renderer_base $output): array {
        return $this->load_data();
    }
}
