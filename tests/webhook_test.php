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
        // The queue of GO1 responses is static. Start with an empty queue.
        self::unused_mock_responses();
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

    /**
     * Process the event, and return the HTTP status code of the response to GO1.
     */
    private function process(array $event): int {
        $json = json_encode($event);
        return mod_goone_api_payload_process_post_request(json_decode($json, true), $json);
    }

    private function go1_completed(): ?int {
        global $DB;
        $completed = $DB->get_field('goone_completion', 'completed',
            ['gooneid' => $this->goone->id, 'userid' => $this->student->id]);
        return $completed === false ? null : (int) $completed;
    }

    /**
     * Return the number of queued GO1 responses that no request has used.
     */
    private static function unused_mock_responses(): int {
        $prop = new \ReflectionProperty(\curl::class, 'mockresponses');
        $prop->setAccessible(true);
        $count = count($prop->getValue());
        $prop->setValue(null, []);
        return $count;
    }

    /**
     * Event types that the webhook must ignore, with the data that GO1 sends for them.
     *
     * @return array[]
     */
    public static function other_event_provider(): array {
        return [
            'enrollment.create' => ['enrollment.create', []],
            'enrollment.delete' => ['enrollment.delete', []],
            'content.update' => ['content.update', ['id' => '741712', 'lo_id' => null]],
            'No event type' => ['', []],
        ];
    }

    /**
     * @dataProvider other_event_provider
     * @param string $type The event type.
     * @param array $data The event data.
     */
    public function test_other_event_type_is_ignored(string $type, array $data): void {
        global $DB;
        // A completed enrolment, so that only the event type can stop the processing.
        self::mock_enrolment();
        $event = self::event($type, $data);
        if ($data && $data['lo_id'] === null) {
            unset($event['data']['lo_id']);
        }

        $this->assertSame(200, $this->process($event));

        $this->assertNull($this->go1_completed());
        $this->assertSame(1, self::unused_mock_responses(), 'The webhook sent a request to GO1');
        $this->assertSame(0, $DB->count_records('goone_webhook_logs'));
    }

    /**
     * Event data without an enrolment ID or a learning object ID.
     *
     * @return array[]
     */
    public static function incomplete_data_provider(): array {
        return [
            'No enrolment ID' => [['id' => '']],
            'No learning object ID' => [['lo_id' => '']],
        ];
    }

    /**
     * @dataProvider incomplete_data_provider
     * @param array $data The event data.
     */
    public function test_event_without_ids_is_logged_and_ignored(array $data): void {
        global $DB;
        self::mock_enrolment();

        $this->process(self::event('enrollment.complete', $data));

        $this->assertNull($this->go1_completed());
        $this->assertSame(1, self::unused_mock_responses(), 'The webhook sent a request to GO1');
        $this->assertSame([get_string('webhookinvaliddata', 'mod_goone')],
            array_map(fn($m) => strtok($m, "\n"), array_values($DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1'))));
    }

    public function test_activity_without_course_module_does_not_stop_processing(): void {
        global $DB;
        // An activity in a second course, created first, has lost its course module.
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $orphan = $this->getDataGenerator()->create_module('goone', ['course' => $course2->id, 'loid' => '741712']);
        $DB->delete_records('course_modules', ['id' => $orphan->cmid]);
        $DB->set_field('goone', 'id', 1, ['id' => $orphan->id]);
        $this->getDataGenerator()->enrol_user($this->student->id, $course2->id, 'student');
        self::mock_enrolment();

        $this->process(self::event());

        $this->assertSame(2, $this->go1_completed());
    }

    public function test_completed_enrolment_returns_200(): void {
        self::mock_enrolment();

        $this->assertSame(200, $this->process(self::event()));

        $this->assertSame(2, $this->go1_completed());
    }

    public function test_completion_writes_a_success_log_row(): void {
        global $DB;
        self::mock_enrolment();

        $this->process(self::event());

        $log = $DB->get_record('goone_webhook_logs', []);
        $expected = get_string('webhooksuccess', 'mod_goone', (object) ['userid' => $this->student->id,
            'courseid' => $this->course->id, 'cmid' => $this->goone->cmid, 'result' => 'viewed,completed']);
        $this->assertSame($expected, strtok($log->message_info, "\n"));
        $this->assertEquals($this->student->id, $log->userid);
    }

    public function test_go1_api_failure_is_logged_and_gets_a_retry(): void {
        global $DB;
        // An empty body: a timeout, a connection error or an HTTP 5xx error page.
        \curl::mock_response('');

        // A status code that is not 2xx makes GO1 send the event again.
        $this->assertSame(503, $this->process(self::event()));

        $this->assertNull($this->go1_completed());
        $this->assertSame([get_string('webhookgo1apierror', 'mod_goone')],
            array_map(fn($m) => strtok($m, "\n"), array_values($DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1'))));
    }

    /**
     * @covers ::mod_goone_webhook_log_excerpt
     */
    public function test_log_excerpt_keeps_short_request(): void {
        $this->assertSame('{"short":"body"}', mod_goone_webhook_log_excerpt('{"short":"body"}'));
    }

    /**
     * @covers ::mod_goone_webhook_log_excerpt
     */
    public function test_log_excerpt_keeps_only_the_start_of_long_request(): void {
        // Multibyte characters, so that a cut by bytes would break a character.
        $request = str_repeat('é', 2000);

        $excerpt = mod_goone_webhook_log_excerpt($request);

        $this->assertSame(str_repeat('é', 1024) . "\n" . get_string('webhooklogtruncated', 'mod_goone', 2000), $excerpt);
    }

    /**
     * @covers ::mod_goone_view_api_payload_logs
     */
    public function test_log_page_shows_one_page_of_rows(): void {
        global $DB;
        for ($i = 1; $i <= 101; $i++) {
            $DB->insert_record('goone_webhook_logs', ['message_info' => "Row $i", 'timecreated' => $i]);
        }
        $this->setAdminUser();

        ob_start();
        mod_goone_view_api_payload_logs();
        $html = ob_get_clean();

        // The newest 100 rows are on the first page, and the oldest row is on the second page.
        $this->assertStringContainsString('Row 101', $html);
        $this->assertStringContainsString('Row 2<', $html);
        $this->assertStringNotContainsString('Row 1<', $html);
        $this->assertStringContainsString('page=1', $html);
    }

    public function test_go1_user_lookup_failure_is_logged_and_gets_a_retry(): void {
        global $DB;
        // GO1 user 999 has no map, so the webhook asks GO1 for the user. That request fails.
        \curl::mock_response('');
        self::mock_enrolment(['user_id' => 999]);

        $this->assertSame(503, $this->process(self::event()));

        $this->assertNull($this->go1_completed());
        $this->assertSame([get_string('webhookgo1usererror', 'mod_goone')],
            array_map(fn($m) => strtok($m, "\n"), array_values($DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1'))));
    }

    public function test_completed_enrolment_without_pass_completes_the_activity(): void {
        self::mock_enrolment(['pass' => false, 'status' => 'completed']);

        $this->process(self::event());

        $this->assertSame(2, $this->go1_completed());
    }

    public function test_enrolment_that_is_not_completed_does_not_complete_the_activity(): void {
        self::mock_enrolment(['pass' => true, 'status' => 'in-progress']);

        $this->assertSame(200, $this->process(self::event()));

        $this->assertNull($this->go1_completed());
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
