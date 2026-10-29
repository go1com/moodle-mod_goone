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
 * Tests for the privacy provider of mod_goone.
 *
 * @package   mod_goone
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Tests for the privacy provider of mod_goone.
 *
 * @covers \mod_goone\privacy\provider
 */
final class provider_test extends provider_testcase {

    /** @var \stdClass A user with a GO1 user map and a log row. */
    private $user;
    /** @var \stdClass Another user with a GO1 user map and a log row. */
    private $other;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->user = $this->getDataGenerator()->create_user();
        $this->other = $this->getDataGenerator()->create_user();
        $this->add_data((int) $this->user->id, 777);
        $this->add_data((int) $this->other->id, 888);
    }

    private function add_data(int $userid, int $gooneuserid): void {
        global $DB;
        $DB->insert_record('goone_usermap', ['userid' => $userid, 'gooneuserid' => $gooneuserid, 'timecreated' => time()]);
        $DB->insert_record('goone_webhook_logs', ['message_info' => "Row of user $userid", 'timecreated' => time(),
            'userid' => $userid]);
    }

    private static function count_data(int $userid): int {
        global $DB;
        return $DB->count_records('goone_usermap', ['userid' => $userid])
            + $DB->count_records('goone_webhook_logs', ['userid' => $userid]);
    }

    public function test_metadata_declares_the_user_map_the_log_and_go1(): void {
        $items = provider::get_metadata(new collection('mod_goone'))->get_collection();
        $names = array_map(fn($item) => $item->get_name(), $items);

        $this->assertContains('goone_usermap', $names);
        $this->assertContains('goone_webhook_logs', $names);
        $this->assertContains('go1', $names);
    }

    public function test_user_context_is_listed_for_a_mapped_user(): void {
        $contextids = array_map('intval', provider::get_contexts_for_userid((int) $this->user->id)->get_contextids());

        $this->assertContains(\context_user::instance($this->user->id)->id, $contextids);
        $this->assertNotContains(\context_user::instance($this->other->id)->id, $contextids);
    }

    public function test_user_context_is_not_listed_without_data(): void {
        $nodata = $this->getDataGenerator()->create_user();

        $this->assertSame([], provider::get_contexts_for_userid((int) $nodata->id)->get_contextids());
    }

    public function test_users_in_user_context(): void {
        $userlist = new userlist(\context_user::instance($this->user->id), 'mod_goone');

        provider::get_users_in_context($userlist);

        $this->assertEquals([$this->user->id], $userlist->get_userids());
    }

    public function test_export_has_the_user_map_and_the_log_rows(): void {
        $context = \context_user::instance($this->user->id);

        provider::export_user_data(new approved_contextlist($this->user, 'mod_goone', [$context->id]));

        $writer = writer::with_context($context);
        $subcontext = [get_string('pluginname', 'mod_goone')];
        $this->assertEquals(777, $writer->get_data($subcontext)->gooneuserid);
        $logs = $writer->get_related_data($subcontext, 'webhook_logs');
        $this->assertSame("Row of user {$this->user->id}", $logs->rows[0]->message_info);
    }

    public function test_delete_data_for_user_deletes_only_that_user(): void {
        $context = \context_user::instance($this->user->id);

        provider::delete_data_for_user(new approved_contextlist($this->user, 'mod_goone', [$context->id]));

        $this->assertSame(0, self::count_data((int) $this->user->id));
        $this->assertSame(2, self::count_data((int) $this->other->id));
    }

    public function test_delete_data_for_users_in_user_context(): void {
        $context = \context_user::instance($this->user->id);

        provider::delete_data_for_users(new approved_userlist($context, 'mod_goone', [$this->user->id]));

        $this->assertSame(0, self::count_data((int) $this->user->id));
        $this->assertSame(2, self::count_data((int) $this->other->id));
    }

    public function test_delete_data_for_all_users_in_user_context(): void {
        provider::delete_data_for_all_users_in_context(\context_user::instance($this->user->id));

        $this->assertSame(0, self::count_data((int) $this->user->id));
        $this->assertSame(2, self::count_data((int) $this->other->id));
    }

    public function test_module_context_data_is_still_deleted(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $goone = $this->getDataGenerator()->create_module('goone', ['course' => $course->id]);
        $DB->insert_record('goone_completion', ['gooneid' => $goone->id, 'userid' => $this->user->id, 'completed' => 2,
            'timemodified' => time()]);
        $context = \context_module::instance($goone->cmid);

        provider::delete_data_for_user(new approved_contextlist($this->user, 'mod_goone', [$context->id]));

        $this->assertFalse($DB->record_exists('goone_completion', ['userid' => $this->user->id]));
        // The user context was not in the list, so the user map stays.
        $this->assertSame(2, self::count_data((int) $this->user->id));
    }
}
