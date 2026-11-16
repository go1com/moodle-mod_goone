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
}
