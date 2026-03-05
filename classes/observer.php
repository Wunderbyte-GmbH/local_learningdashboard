<?php
namespace local_learningdashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for the Learning Dashboard plugin.
 *
 * @package    local_learningdashboard
 * @copyright  2026 Wunderbyte GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Triggered when a user profile is updated.
     *
     * Resets caches used by the learning dashboard that depend
     * on user profile data.
     *
     * @param \core\event\user_updated $event
     * @return void
     */
    public static function user_updated(\core\event\user_updated $event): void {
        \cache_helper::purge_by_event('local_fobi_importer_admin_table_changed');
    }
}
