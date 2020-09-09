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
 * Plugin upgrade steps are defined here.
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute upgrade script.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_goone_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020082900) {
        $table = new xmldb_table('goone');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, false, null, null, null);
        $dbman->change_field_type($table, $field);
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, '0', null, null, null);
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, '2020082900', 'mod', 'goone');
    }
    return true;
}
