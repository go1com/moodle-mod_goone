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

require_once(__DIR__ . '/upgradelib.php');

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

        upgrade_mod_savepoint(true, 2020082900, 'goone');
    }

    if( $oldversion < 2022102000) {

        // Define table goone_webhook_logs to be created.
        $table = new xmldb_table('goone_webhook_logs');

        // Adding fields to table goone_webhook_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('message_info', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goone_webhook_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for goone_webhook_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2022102000, 'goone');
    }

    if( $oldversion < 2024112000) {
        // Rename log table for consistency.
        $table = new xmldb_table('mod_goone_webhook_logs');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'goone_webhook_logs');
        }

        // Add user mapping table (performance).
        $table = new xmldb_table('goone_usermap');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('gooneuserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        // Add primary key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Add indexes.
        $table->add_index('ix_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('ix_gooneuserid', XMLDB_INDEX_NOTUNIQUE, array('gooneuserid'));
        // Add table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2024112000, 'goone');
    }

    if( $oldversion < 2025070100) {
        $DB->execute("DELETE FROM {goone_usermap} WHERE userid IS NULL");
        upgrade_mod_savepoint(true, 2025070100, 'goone');
    }

    if ($oldversion < 2026101501) {
        // Make the goone_usermap indexes unique, as install.xml defines them.
        mod_goone_upgrade_usermap_unique_indexes();
        upgrade_mod_savepoint(true, 2026101501, 'goone');
    }

    if ($oldversion < 2026102602) {
        // Add the user of a log row, so that the privacy provider can find the rows of a user.
        $table = new xmldb_table('goone_webhook_logs');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2026102602, 'goone');
    }

    return true;
}
