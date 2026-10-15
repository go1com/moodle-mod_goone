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
 * Upgrade library tests.
 *
 * @package   mod_goone
 * @category  test
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/db/upgradelib.php');

/**
 * Upgrade library tests class.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgradelib_test extends advanced_testcase {

    public function test_upgrade_usermap_unique_indexes_removes_duplicates() {
        global $DB;

        $this->resetAfterTest();
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('goone_usermap');
        // Make the indexes the same as on a site that was upgraded from an earlier version.
        foreach (['ix_userid' => 'userid', 'ix_gooneuserid' => 'gooneuserid'] as $name => $field) {
            $index = new \xmldb_index($name, XMLDB_INDEX_NOTUNIQUE, [$field]);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
            $dbman->add_index($table, $index);
        }
        $keep1 = $DB->insert_record('goone_usermap', ['userid' => 10, 'gooneuserid' => 100, 'timecreated' => 1]);
        $DB->insert_record('goone_usermap', ['userid' => 11, 'gooneuserid' => 100, 'timecreated' => 2]);
        $keep2 = $DB->insert_record('goone_usermap', ['userid' => 12, 'gooneuserid' => 200, 'timecreated' => 3]);
        $DB->insert_record('goone_usermap', ['userid' => 12, 'gooneuserid' => 300, 'timecreated' => 4]);

        mod_goone_upgrade_usermap_unique_indexes();

        $this->assertEqualsCanonicalizing([$keep1, $keep2], array_keys($DB->get_records('goone_usermap')));
        $indexes = $DB->get_indexes('goone_usermap');
        $this->assertCount(2, $indexes);
        foreach ($indexes as $index) {
            $this->assertTrue((bool)$index['unique']);
        }
    }
}
