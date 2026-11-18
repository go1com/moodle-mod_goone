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

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/goone/lib.php');

/**
 * Tests for the settings page of mod_goone.
 *
 * @package   mod_goone
 * @category  test
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_test extends advanced_testcase {

    /**
     * An administrator can set $CFG->mod_goone_admin_users to one user name, not a list.
     * The settings page must accept this value as a list with one name.
     */
    public function test_admin_users_accepts_one_user_name(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->mod_goone_admin_users = 'admin';

        $page = admin_get_root(true, true)->locate('modsettinggoone');

        // The page holds the editable Client ID only for a user in the list.
        $this->assertTrue(isset($page->settings->mod_gooneclient_id));
    }

    /**
     * After a save, when GO1 does not give a token, the error message links to the connection check page.
     */
    public function test_connection_error_after_save_links_to_the_connection_check(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('configchange', 1, 'mod_goone');
        set_config('token_time', 0, 'mod_goone');
        // GO1 answers without a token. The mock gives HTTP status 200, thus no request goes to GO1.
        \curl::mock_response(json_encode(['error' => 'invalid_client']));

        $page = admin_get_root(true, true)->locate('modsettinggoone');
        $html = $page->settings->mod_gooneclient_id->output_html('');

        $this->assertStringContainsString(get_string('connectionerroradmin', 'mod_goone'), $html);
        $this->assertStringContainsString('/mod/goone/checkconnection.php', $html);
        $this->assertStringContainsString(get_string('connectiondetails', 'mod_goone'), $html);
    }

    /**
     * The action buttons include a link to the connection check page.
     */
    public function test_action_buttons_link_to_the_connection_check(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = admin_get_root(true, true)->locate('modsettinggoone');
        $html = $page->settings->{'mod_goonecreatewebhook_viewwebhooklogs'}->output_html('');

        $this->assertStringContainsString('/mod/goone/checkconnection.php', $html);
        $this->assertStringContainsString(get_string('checkconnection', 'mod_goone'), $html);
    }

    /**
     * Only an administrator in $CFG->mod_goone_admin_users, or any administrator when the list is empty,
     * can manage the GO1 settings.
     */
    public function test_user_in_admin_users(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->mod_goone_admin_users = [];
        $this->assertTrue(mod_goone_user_in_admin_users());

        $CFG->mod_goone_admin_users = ['someoneelse', 'admin'];
        $this->assertTrue(mod_goone_user_in_admin_users());

        $CFG->mod_goone_admin_users = 'someoneelse';
        $this->assertFalse(mod_goone_user_in_admin_users());
    }
}
