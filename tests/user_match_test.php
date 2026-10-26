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
 * Tests for the match of a GO1 user to a Moodle user.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');

/**
 * Tests for the match of a GO1 user to a Moodle user.
 *
 * The GO1 API is replaced with curl::mock_response(), so no test sends a request to GO1.
 *
 * @covers ::goone_get_moodle_user
 */
final class user_match_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // A valid token prevents a token request.
        set_config('token_time', time(), 'mod_goone');
    }

    /**
     * Queue the GO1 response for the user lookup.
     */
    private static function mock_go1_user(int $id, string $email, string $firstname, string $lastname): void {
        \curl::mock_response(json_encode(['id' => $id, 'email' => $email, 'first_name' => $firstname,
            'last_name' => $lastname]));
    }

    private static function mapped_userid(int $gooneuserid): ?int {
        global $DB;
        $userid = $DB->get_field('goone_usermap', 'userid', ['gooneuserid' => $gooneuserid]);
        return $userid === false ? null : (int) $userid;
    }

    public function test_name_match_maps_the_only_namesake(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertEquals($user->id, goone_get_moodle_user(999)->id);
        $this->assertSame((int) $user->id, self::mapped_userid(999));
    }

    public function test_name_match_ignores_deleted_user(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        $deleted = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        delete_user($deleted);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertEquals($user->id, goone_get_moodle_user(999)->id);
    }

    public function test_name_match_does_not_map_only_deleted_user(): void {
        $deleted = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        delete_user($deleted);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertFalse(goone_get_moodle_user(999));
        $this->assertNull(self::mapped_userid(999));
    }

    public function test_name_match_ignores_suspended_user(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner', 'suspended' => 1]);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertEquals($user->id, goone_get_moodle_user(999)->id);
    }

    public function test_name_match_does_not_map_only_suspended_user(): void {
        $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner', 'suspended' => 1]);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertFalse(goone_get_moodle_user(999));
        $this->assertNull(self::mapped_userid(999));
    }

    public function test_email_match_maps_the_only_user_with_the_email(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        self::mock_go1_user(888, 'jane@example.com', 'Other', 'Name');

        $this->assertEquals($user->id, goone_get_moodle_user(888)->id);
        $this->assertSame((int) $user->id, self::mapped_userid(888));
    }

    public function test_email_shared_by_two_users_maps_nobody(): void {
        global $DB;
        set_config('allowaccountssameemail', 1);
        $this->getDataGenerator()->create_user(['email' => 'shared@example.com', 'firstname' => 'Jane', 'lastname' => 'Learner']);
        $this->getDataGenerator()->create_user(['email' => 'shared@example.com']);
        self::mock_go1_user(888, 'shared@example.com', 'Jane', 'Learner');

        $this->assertFalse(goone_get_moodle_user(888));
        $this->assertNull(self::mapped_userid(888));
        $this->assertSame([get_string('useremailnotunique', 'mod_goone', 888)],
            array_values($DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1')));
    }

    public function test_external_user_id_email_shared_by_two_users_maps_nobody(): void {
        set_config('allowaccountssameemail', 1);
        $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        \curl::mock_response(json_encode(['id' => 888, 'email' => 'other@example.com', 'first_name' => 'Jane',
            'last_name' => 'Learner', 'custom_fields' => ['external_user_id' => 'jane@example.com']]));

        $this->assertFalse(goone_get_moodle_user(888));
        $this->assertNull(self::mapped_userid(888));
    }

    public function test_empty_go1_email_does_not_match_user_without_email(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'email', '', ['id' => $user->id]);
        self::mock_go1_user(888, '', 'Other', 'Name');

        $this->assertFalse(goone_get_moodle_user(888));
        $this->assertNull(self::mapped_userid(888));
    }

    public function test_name_match_with_two_active_namesakes_maps_nobody(): void {
        $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Learner']);
        self::mock_go1_user(999, 'unknown@example.com', 'Jane', 'Learner');

        $this->assertFalse(goone_get_moodle_user(999));
        $this->assertNull(self::mapped_userid(999));
    }
}
