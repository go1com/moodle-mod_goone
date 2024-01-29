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
 * Render GO1 Bulk content browser and search options
 *
 * @package     mod_goone
 * @copyright   2022 Esteban Echavarria <esteban.echavarria@openlms.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');

require_login();
require_capability('mod/goone:viewbulkcontent', context_system::instance());

$config = get_config('mod_goone');
if (!goone_tokentest()) {
    echo $OUTPUT->notification(get_string('connectionerror', 'mod_goone'), 'notifyproblem');
}
$facets = goone_get_facets();
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/bulk.php');
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('contentbank', 'mod_goone'));
$PAGE->set_heading(get_string('contentbank', 'mod_goone'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/goone/css/bootstrap-multiselect.css'));
$PAGE->requires->js_call_amd('mod_goone/browser_bulk', 'init');
$PAGE->requires->strings_for_js(
    array('createcoursemodaltitle', 'searchcoursemodaltitle', 'noitemsselected', 'topics', 'language', 'type', 'providers', 'duration', 'coursename', 'coursenameempty', 'processingitems', 'toomanyitems', 'contenttype')
    , 'mod_goone');
$PAGE->requires->strings_for_js(array('create', 'save'), 'moodle');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/browser_bulk', $facets);
echo $OUTPUT->footer();
