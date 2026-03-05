<?php
/**
 * Cache definitions for Learning Dashboard.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'cachedrawdata' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 200000,
        'invalidationevents' => ['local_fobi_importer_admin_table_changed'],
    ],
    'cachedfulltable' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 1,
        'invalidationevents' => ['local_fobi_importer_admin_table_changed_smallchange', 'local_fobi_importer_admin_table_changed'],
    ],
];
