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
 * Render GO1 content browser and search options
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

// TODO: Move javascript from mustache to AMD.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');

$mode = required_param('mode', PARAM_TEXT);
$id = required_param('id', PARAM_INT);

require_login();
goone_check_capabilities($mode, $id);


$config = get_config('mod_goone');

if (!goone_tokentest()) {
    echo $OUTPUT->notification(get_string('connectionerror', 'goone'), 'notifyproblem');
}
$facets = goone_get_facets();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/bootstrap-multiselect.js'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/goone/css/bootstrap-multiselect.css'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/browser', $facets);
echo $OUTPUT->footer();