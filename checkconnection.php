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
 * Checks the connection from this site to the GO1 hosts, and shows the result.
 *
 * The page runs the same checks as cli/check_connection.php. Only a user who can manage the GO1
 * settings can open it.
 *
 * @package   mod_goone
 * @copyright 2026 Androgogic Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/goone/lib.php');

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);
if (!mod_goone_user_in_admin_users()) {
    throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', '');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/goone/checkconnection.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('checkconnection', 'mod_goone'));
$PAGE->set_heading(get_string('checkconnection', 'mod_goone'));

$check = new \mod_goone\local\connection_check();
$check->run();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('checkconnection', 'mod_goone'));
echo $OUTPUT->render_from_template('mod_goone/connection_check', $check->export_for_template($OUTPUT));
echo $OUTPUT->footer();
