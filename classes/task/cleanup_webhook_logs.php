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

namespace mod_goone\task;

/**
 * A scheduled task that deletes old rows of the GO1 webhook log.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_webhook_logs extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanupwebhooklogstask', 'mod_goone');
    }

    /**
     * Delete the log rows that are older than the period of the webhooklogretention setting.
     */
    public function execute() {
        global $DB;

        $retention = get_config('mod_goone', 'webhooklogretention');
        if ($retention === false) {
            $retention = 90 * DAYSECS;
        }
        // 0 keeps all rows.
        if ((int) $retention <= 0) {
            return;
        }
        $DB->delete_records_select('goone_webhook_logs', 'timecreated < ?', [time() - (int) $retention]);
    }
}
