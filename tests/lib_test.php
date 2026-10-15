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
 * Unit tests for (some of) mod/goone/lib.php.
 *
 * @package    mod_goone
 * @category   test
 * @copyright  2020 eCreators
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

namespace mod_goone;

use advanced_testcase;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');
/**
 * PHPUnit data generator testcase.
 *
 * @copyright  2020 eCreators
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */
class lib_test extends advanced_testcase {

    public function test_goone_supports() {
        $this->assertTrue(goone_supports(FEATURE_COMPLETION_TRACKS_VIEWS) == true);
        $this->assertTrue(goone_supports(FEATURE_COMPLETION_HAS_RULES) == true);
        $this->assertTrue(goone_supports(FEATURE_BACKUP_MOODLE2) == true);
        $this->assertEquals(MOD_PURPOSE_CONTENT, goone_supports(FEATURE_MOD_PURPOSE));
    }

    public function test_goone_convert_hours_mins() {
        $this->assertTrue(goone_convert_hours_mins("62") == "01:02");
        $this->assertTrue(empty(goone_convert_hours_mins("0")));

    }

    public function test_goone_set_completion() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $cm = $this->getDataGenerator()->create_module('goone', array('course' => $course->id));
        $student = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $mid = $DB->get_record('modules', array('name' => 'goone'), '*', MUST_EXIST);
        $cm = $DB->get_record('course_modules', array('module' => $mid->id, 'instance' => $cm->id), '*', MUST_EXIST);
        $completion = goone_set_completion($cm, $student->id, '', "completed");
        $this->assertTrue($completion);

    }

    public function test_goone_check_capabilities() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $goone = $this->getDataGenerator()->create_module('goone', array('course' => $course->id));
        // Check for expected failure.
        try {
            goone_check_capabilities('add', $course->id);
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('required_capability_exception', $e);
            $this->assertSame('nopermissions', $e->errorcode);
        }
        // Set as admin to gain capabilities.
        $this->setAdminUser();
        $this->assertTrue(goone_check_capabilities('add', $course->id));
        $this->assertTrue(goone_check_capabilities('update', $goone->id));
    }

    public function test_goone_get_lang() {
        // Test known languages.
        $this->assertTrue(goone_get_lang("en") == "English");
        $this->assertTrue(goone_get_lang("en-us") == "English (United States)");
        // Test unknown language.
        $this->assertTrue(goone_get_lang("xy") == "xy");
        // Test no language.
        $this->assertTrue(goone_get_lang() == get_string('unknownlanguage', 'mod_goone'));
    }

    public function test_goone_extract_scorm_token() {
        $this->resetAfterTest();
        $loid = "9999";
        $tempdir = make_temp_directory('goone/');
        $filename = "go1scorm.zip";
        $filepath = __DIR__ . '/fixtures/' . $filename;
        // Expected result.
        $token = "iamatestingtoken12345";

        $this->assertTrue(goone_extract_scorm_token($loid, $tempdir, $filename, $filepath) == $token);

    }


    public function test_goone_clean_hits() {
        $data = "<strong>Course Overview</strong>";
        $result = "Course Overview ";
        $this->assertTrue(goone_clean_hits($data) == $result);
    }

    public function test_goone_inject_datamodel() {
        $match = "/mod/goone/datamodel.php";
        // Check datamodel path has been injected into scorm_12.js.
        $this->assertTrue(!empty(strpos(goone_inject_datamodel(), $match)));
    }

    public function test_goone_session_state() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $cm = $this->getDataGenerator()->create_module('goone', array('course' => $course->id));
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $mid = $DB->get_record('modules', array('name' => 'goone'), '*', MUST_EXIST);
        $cm = $DB->get_record('course_modules', array('module' => $mid->id, 'instance' => $cm->id), '*', MUST_EXIST);
        // No completion record.
        $sstate = goone_session_state($cm->id, $cm->id);
        $this->assertTrue($sstate->cmistate == 'normal');
    }

    public function test_goone_signup_url() {
        global $CFG;
        $this->resetAfterTest(true);

        $partnerid = "12345";
        set_config('partnerid', $partnerid, 'mod_goone');
        $this->assertTrue(goone_signup_url() == 'https://auth.GO1.com/oauth/authorize?client_id=Moodle&'.
            'response_type=code&redirect=false&redirect_uri='.$CFG->wwwroot.
            '&new_client=Moodle&partner_portal_id='.$partnerid);
    }

    public function test_goone_get_coursemodule_info() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $goonegen = $this->getDataGenerator()->get_plugin_generator('mod_goone');
        $goone = $goonegen->create_instance([
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionsubmit' => 1,
        ]);
        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, true, MUST_EXIST);
        $info = goone_get_coursemodule_info($cm);
        $this->assertEquals($info->name, $goone->name);
        $this->assertEquals(1, $info->customdata['customcompletionrules']['completionsubmit']);
    }

    public function test_payload_request_marks_unviewed_activity_viewed_and_complete() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $goone = $this->getDataGenerator()->create_module('goone', [
            'course' => $course->id,
            'loid' => '12345',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED,
            'completionsubmit' => 1,
        ]);
        // Map the GO1 user to the student, so that no GO1 user lookup occurs.
        $DB->insert_record('goone_usermap', ['userid' => $student->id, 'gooneuserid' => 777, 'timecreated' => time()]);
        // A valid token prevents a token request.
        set_config('token_time', time(), 'mod_goone');
        // The GO1 enrolment that the webhook refers to.
        \curl::mock_response(json_encode(['id' => 1, 'pass' => 1, 'user_id' => 777]));

        mod_goone_api_payload_process_post_request(['data' => ['id' => 1, 'lo_id' => '12345']], '{}');

        $this->assertTrue($DB->record_exists('course_modules_viewed',
            ['coursemoduleid' => $goone->cmid, 'userid' => $student->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $DB->get_field('course_modules_completion', 'completionstate',
            ['coursemoduleid' => $goone->cmid, 'userid' => $student->id]));
    }
}
