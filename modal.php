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
 * Renders modal popup wiht GO1 course descriptions
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');
require_login();
$mode = required_param('mode', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$loid = required_param('loid', PARAM_RAW);

goone_check_capabilities($mode, $id);

$data = goone_modal_overview($loid);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/modal', $data);
echo $OUTPUT->footer();
