<?php
/**
 * Event observers for the Learning Dashboard plugin.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_updated',
        'callback'    => '\local_learningdashboard\observer::user_updated',
        'priority'    => 9999,
        'internal'    => false,
    ],
];
