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
 * Upgrade helper functions.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Make the goone_usermap indexes unique, as install.xml defines them.
 *
 * Sites that were upgraded from an earlier version have non-unique indexes. The function first deletes
 * the duplicate rows and keeps the oldest row of each group. The table is a cache of the user matches,
 * thus the plugin finds a deleted match again when it is necessary.
 */
function mod_goone_upgrade_usermap_unique_indexes(): void {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('goone_usermap');

    foreach (['ix_userid' => 'userid', 'ix_gooneuserid' => 'gooneuserid'] as $name => $field) {
        $duplicateids = $DB->get_fieldset_sql(
            "SELECT m.id
               FROM {goone_usermap} m
              WHERE EXISTS (SELECT 1
                              FROM {goone_usermap} o
                             WHERE o.$field = m.$field
                                   AND o.id < m.id)"
        );
        if ($duplicateids) {
            $DB->delete_records_list('goone_usermap', 'id', $duplicateids);
        }

        // The DDL finds an index by its fields, thus this drops the index if it is unique or not.
        $index = new xmldb_index($name, XMLDB_INDEX_UNIQUE, [$field]);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $dbman->add_index($table, $index);
    }
}
