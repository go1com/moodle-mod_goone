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
 * Intercepts SCORM API to write user progress in GO1 course
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');

$id = optional_param('id', '', PARAM_INT);       // Course Module ID, or
$a = optional_param('a', '', PARAM_INT);         // scorm ID
$scoid = required_param('scoid', PARAM_INT);  // sco ID
$attempt = required_param('attempt', PARAM_INT);  // attempt number.

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('goone', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (! $goone = $DB->get_record("goone", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else if (!empty($a)) {
    if (! $goone = $DB->get_record("goone", array("id" => $a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $goone->course))) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("goone", $goone->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
} else {
    print_error('missingparameter');
}

$PAGE->set_url('/mod/goone/datamodel.php', array('scoid' => $scoid, 'attempt' => $attempt, 'id' => $cm->id));

require_login($course, false, $cm);

if (confirm_sesskey() && (!empty($scoid))) {
    $result = true;
    $request = null;
    foreach (data_submitted() as $element => $value) {
        $element = str_replace('__', '.', $element);
        if ($element == 'cmi.core.lesson_location') {
            $netelement = preg_replace('/\.N(\d+)\./', "\.\$1\.", $element);
            $result = goone_set_completion($cm, $USER->id, $value, "inprogress");

        }
        if ($element == 'cmi.core.lesson_status' && $value == 'passed') {
            $netelement = preg_replace('/\.N(\d+)\./', "\.\$1\.", $element);
            $result = goone_set_completion($cm, $USER->id, '', "completed");
        }
    }
    if ($result) {
        echo "true\n0";
    } else {
        echo "true\n0";
    }
    if ($request != null) {
        echo "\n".$request;
    }
}
