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
 * Tests for the scheduled task that deletes old rows of the GO1 webhook log.
 *
 * @package   mod_goone
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone\task;

use advanced_testcase;

/**
 * Tests for the scheduled task that deletes old rows of the GO1 webhook log.
 *
 * @covers \mod_goone\task\cleanup_webhook_logs
 */
final class cleanup_webhook_logs_test extends advanced_testcase {

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $DB->insert_record('goone_webhook_logs', ['message_info' => 'old', 'timecreated' => time() - 91 * DAYSECS]);
        $DB->insert_record('goone_webhook_logs', ['message_info' => 'new', 'timecreated' => time() - 89 * DAYSECS]);
    }

    private static function messages(): array {
        global $DB;
        return array_values($DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1 ORDER BY id'));
    }

    public function test_rows_older_than_the_default_period_are_deleted(): void {
        unset_config('webhooklogretention', 'mod_goone');

        (new cleanup_webhook_logs())->execute();

        $this->assertSame(['new'], self::messages());
    }

    public function test_rows_older_than_the_configured_period_are_deleted(): void {
        set_config('webhooklogretention', 30 * DAYSECS, 'mod_goone');

        (new cleanup_webhook_logs())->execute();

        $this->assertSame([], self::messages());
    }

    public function test_zero_keeps_all_rows(): void {
        set_config('webhooklogretention', 0, 'mod_goone');

        (new cleanup_webhook_logs())->execute();

        $this->assertSame(['old', 'new'], self::messages());
    }
}
