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
 * Tests for the processing of GO1 webhook events.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Tests for the processing of GO1 webhook events.
 *
 * The events have the format of https://developers.go1.com/docs/developer-tools/webhooks/events/.
 * The GO1 API is replaced with curl::mock_response(), so no test sends a request to GO1.
 *
 * @covers ::mod_goone_api_payload_process_post_request
 */
final class webhook_test extends advanced_testcase {

    /** @var \stdClass */
    private $course;
    /** @var \stdClass */
    private $student;
    /** @var \stdClass */
    private $goone;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        // A valid token prevents a token request.
        set_config('token_time', time(), 'mod_goone');
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->goone = $this->getDataGenerator()->create_module('goone', [
            'course' => $this->course->id,
            'loid' => '741712',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionsubmit' => 1,
        ]);
        // Map the GO1 user to the student, so that no GO1 user lookup occurs.
        $DB->insert_record('goone_usermap', ['userid' => $this->student->id, 'gooneuserid' => 777, 'timecreated' => time()]);
    }

    /**
     * Return a GO1 webhook event.
     */
    private static function event(string $type = 'enrollment.complete', array $data = []): array {
        return [
            'id' => 'hg4JWUDbT55B',
            'event_type' => $type,
            'webhook_version' => '3.0.0',
            'sent' => '2022-03-29T01:29:36.676Z',
            'attempt_number' => 1,
            'url' => 'https://example.com/mod/goone/payload.php',
            'webhook_id' => 'xeDFME314',
            'data' => $data + ['id' => '101916887', 'lo_id' => '741712', 'portal_id' => '11861197',
                'event_time' => '2022-03-29T01:29:36.000Z'],
        ];
    }

    /**
     * Queue the GO1 response for the enrolment lookup.
     */
    private static function mock_enrolment(array $fields = []): void {
        \curl::mock_response(json_encode($fields + ['id' => 101916887, 'lo_id' => 741712, 'user_id' => 777,
            'pass' => true, 'status' => 'completed']));
    }

    private function process(array $event): void {
        $json = json_encode($event);
        mod_goone_api_payload_process_post_request(json_decode($json, true), $json);
    }

    private function go1_completed(): ?int {
        global $DB;
        $completed = $DB->get_field('goone_completion', 'completed',
            ['gooneid' => $this->goone->id, 'userid' => $this->student->id]);
        return $completed === false ? null : (int) $completed;
    }

    public function test_completion_is_recorded_when_activity_already_shows_complete(): void {
        global $DB;
        // The activity shows complete, but goone_completion has no record, for example after a
        // manual override. The next calculation of the completion data makes the activity
        // incomplete again, unless goone_completion records the completion.
        $DB->insert_record('course_modules_completion', ['coursemoduleid' => $this->goone->cmid,
            'userid' => $this->student->id, 'completionstate' => COMPLETION_COMPLETE, 'timemodified' => time()]);
        self::mock_enrolment();

        $this->process(self::event());

        $this->assertSame(2, $this->go1_completed());
    }

    public function test_recorded_completion_is_not_written_again(): void {
        global $DB;
        self::mock_enrolment();
        $this->process(self::event());
        $before = $DB->get_records('goone_completion');
        $this->waitForSecond();

        self::mock_enrolment();
        $this->process(self::event());

        $this->assertEquals($before, $DB->get_records('goone_completion'));
    }
}
