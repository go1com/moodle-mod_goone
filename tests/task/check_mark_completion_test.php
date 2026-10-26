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
 * Tests for the scheduled task that gets the GO1 completions.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone\task;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Tests for the scheduled task that gets the GO1 completions.
 *
 * The task is the backup for a GO1 webhook that does not arrive. The GO1 API is
 * replaced with curl::mock_response(), so no test sends a request to GO1.
 *
 * @covers \mod_goone\task\check_mark_completion
 */
final class check_mark_completion_test extends advanced_testcase {

    /** @var \stdClass */
    private $course;

    /** @var string The output of the last task run. */
    private $output = '';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // A valid token prevents a token request.
        set_config('token_time', time(), 'mod_goone');
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
    }

    /**
     * Make a GO1 activity that the learner completes when the learning object is finished.
     */
    private function make_activity(string $loid): \stdClass {
        return $this->getDataGenerator()->create_module('goone', [
            'course' => $this->course->id,
            'loid' => $loid,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionsubmit' => 1,
        ]);
    }

    /**
     * Make an enrolled learner, mapped to a GO1 user.
     */
    private function make_learner(int $gooneuserid, string $email): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_and_enrol($this->course, 'student', ['email' => $email]);
        $DB->insert_record('goone_usermap', ['userid' => $user->id, 'gooneuserid' => $gooneuserid, 'timecreated' => time()]);
        return $user;
    }

    /**
     * Queue GO1 API responses in the order that the task requests them.
     */
    private static function mock_api(array ...$responses): void {
        foreach (array_reverse($responses) as $response) {
            \curl::mock_response(json_encode($response));
        }
    }

    /**
     * Run the task, and return its status.
     */
    private function run_task(): bool {
        ob_start();
        $status = (new check_mark_completion())->execute();
        $this->output = ob_get_clean();
        return $status;
    }

    private function state(\stdClass $goone, int $userid): ?int {
        global $DB;
        $state = $DB->get_field('course_modules_completion', 'completionstate',
            ['coursemoduleid' => $goone->cmid, 'userid' => $userid]);
        return $state === false ? null : (int) $state;
    }

    public function test_completed_enrolment_completes_the_activity(): void {
        $goone = $this->make_activity('111');
        $learner = $this->make_learner(555, 'learner@example.com');
        self::mock_api(['total' => 1, 'hits' => [['id' => 1, 'lo_id' => 111, 'user_id' => 555, 'status' => 'completed']]]);

        $this->assertTrue($this->run_task());

        $this->assertSame(COMPLETION_COMPLETE, $this->state($goone, (int) $learner->id));
    }

    public function test_enrolment_that_is_not_completed_is_skipped(): void {
        $goone = $this->make_activity('111');
        $learner = $this->make_learner(555, 'learner@example.com');
        self::mock_api(['total' => 1, 'hits' => [['id' => 1, 'lo_id' => 111, 'user_id' => 555, 'status' => 'in-progress']]]);

        $this->assertTrue($this->run_task());

        $this->assertNull($this->state($goone, (int) $learner->id));
    }

    public function test_all_pages_of_enrolments_are_processed(): void {
        $goone = $this->make_activity('111');
        $first = $this->make_learner(555, 'first@example.com');
        $last = $this->make_learner(556, 'last@example.com');
        // A full first page, then a second page with the 51st enrolment.
        $page1 = array_fill(0, check_mark_completion::PAGESIZE,
            ['id' => 1, 'lo_id' => 111, 'user_id' => 555, 'status' => 'completed']);
        $page2 = [['id' => 2, 'lo_id' => 111, 'user_id' => 556, 'status' => 'completed']];
        self::mock_api(['total' => 51, 'hits' => $page1], ['total' => 51, 'hits' => $page2]);

        $this->assertTrue($this->run_task());

        $this->assertSame(COMPLETION_COMPLETE, $this->state($goone, (int) $first->id));
        $this->assertSame(COMPLETION_COMPLETE, $this->state($goone, (int) $last->id));
    }

    public function test_go1_failure_makes_the_task_fail(): void {
        $this->make_activity('111');
        // An empty body: a timeout, a connection error or an HTTP 5xx error page.
        \curl::mock_response('');

        $this->assertFalse($this->run_task());
    }

    public function test_error_for_one_enrolment_does_not_stop_the_task(): void {
        // Activity A has an enrolment with a GO1 user ID that is not a number. The user map
        // cannot store it, so the database gives an error.
        $goonea = $this->make_activity('111');
        $this->getDataGenerator()->create_and_enrol($this->course, 'student', ['email' => 'jane@example.com']);
        // Activity B has a normal completed enrolment.
        $gooneb = $this->make_activity('222');
        $learner = $this->make_learner(555, 'learner@example.com');
        self::mock_api(
            ['total' => 1, 'hits' => [['id' => 1, 'lo_id' => 111, 'user_id' => 'not-a-number', 'status' => 'completed']]],
            ['id' => 'not-a-number', 'email' => 'jane@example.com', 'first_name' => 'Jane', 'last_name' => 'Learner'],
            ['total' => 1, 'hits' => [['id' => 2, 'lo_id' => 222, 'user_id' => 555, 'status' => 'completed']]],
        );

        $status = $this->run_task();

        $this->assertSame(COMPLETION_COMPLETE, $this->state($gooneb, (int) $learner->id));
        $this->assertFalse($status);
        $this->assertStringContainsString('Error: lo_id 111 GO1 user not-a-number: ', $this->output);
        $this->assertLessThan($gooneb->id, $goonea->id);
    }

    public function test_activity_without_course_module_does_not_stop_the_task(): void {
        global $DB;
        $goonea = $this->make_activity('111');
        $DB->delete_records('course_modules', ['id' => $goonea->cmid]);
        $gooneb = $this->make_activity('222');
        $learner = $this->make_learner(555, 'learner@example.com');
        // The task sends no request for activity A, so the only response is for activity B.
        self::mock_api(['total' => 1, 'hits' => [['id' => 2, 'lo_id' => 222, 'user_id' => 555, 'status' => 'completed']]]);

        $this->run_task();

        $this->assertSame(COMPLETION_COMPLETE, $this->state($gooneb, (int) $learner->id));
    }
}
