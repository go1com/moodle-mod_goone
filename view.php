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
 * Display GO1 activity module
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

require_once('../../config.php');
require_once('lib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $USER;
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_12lib.php');
$newwin = false;
$cmid   = required_param('id', PARAM_INT);
$newwin = optional_param('win', '', PARAM_INT);  // Course Module ID.
if ($newwin == 1) {
    $newwin = true;
}

if (!$cm = get_coursemodule_from_id('goone', $cmid, 0, true, MUST_EXIST)) {
    throw new moodle_exception(get_string('cmidincorrect', 'goone'));
}
if (!$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
    throw new moodle_exception(get_string('courseincorrect', 'goone'));
}
if (!$goone = $DB->get_record('goone', array('id' => $cm->instance))) {
    throw new moodle_exception(get_string('cmincorrect', 'goone'));
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/goone:view', $context);

$PAGE->set_url('/mod/goone/view.php', array('id' => $cm->id));
$PAGE->set_title($goone->name);
$PAGE->requires->js_call_amd('mod_goone/viewer', 'init');

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$exiturl = course_get_url($course, $cm->sectionnum);
$strexit = get_string('exitactivity', 'scorm');
$exitlink = html_writer::link($exiturl, $strexit, array('title' => $strexit, 'class' => 'btn btn-default'));
$PAGE->set_button($exitlink);

// Handle opening in a new window if option selected.
if ($newwin == 1) {
    $PAGE->set_pagelayout('embedded');
}
$isnewwin = $DB->get_field('goone', 'popup', array('id' => $goone->id));
if ($isnewwin == 1 && $newwin == 0) {
    echo $OUTPUT->header();
    echo get_string('opennewwin', 'goone');
    $urltogo = new moodle_url('/mod/goone/view.php', array('id' => $cm->id));
    $PAGE->requires->js_call_amd('mod_goone/viewer', 'newwindow', array(($urltogo->__toString())));
    echo $OUTPUT->footer();
    return;
}

// Load all required mod_scorm fules we need now for GO1 content to load.
$PAGE->requires->js(new moodle_url('/lib/cookies.js'), true);
$PAGE->requires->js(new moodle_url('/mod/scorm/module.js'), true);
$PAGE->requires->js(new moodle_url('/mod/scorm/request.js'), true);

echo $OUTPUT->header();

// Correct order of operations is required here to ensure the SCORM API is available first.
$data = array(
    'datamodel' => goone_inject_datamodel()
);
echo $OUTPUT->render_from_template('mod_goone/datamodel', $data);

$sstate = goone_session_state($goone->id, $cmid);
if (!empty($sstate)) {
    $PAGE->requires->js_init_call(
        'M.scorm_api.init', array($sstate->def, $sstate->cmiobj, $sstate->cmiint, $sstate->cmistring256,
        $sstate->cmistring4096, false, "0", "0", $CFG->wwwroot,
        sesskey(), "6", "1", $sstate->cmistate, $cmid, "GO1", false, true, "3")
    );
}
$data = array (
    'token' => $goone->token,
    'loid' => $goone->loid,
);
echo $OUTPUT->render_from_template('mod_goone/view', $data);

echo $OUTPUT->footer();
