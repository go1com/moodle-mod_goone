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
 * Tests that a recorded learning object completion stays recorded.
 *
 * @package   mod_goone
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;
use completion_info;
use mod_goone\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Tests that a recorded learning object completion stays recorded.
 *
 * The GO1 player sends a progress update after it sends the completion. Before the
 * fix, that progress update changed goone_completion.completed from 2 back to 1.
 * Moodle kept its own record as complete, so nothing looked wrong. But the custom
 * completion rule reads goone_completion, so the next calculation of the completion
 * data made the activity, and then the course, incomplete again.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_persistence_test extends advanced_testcase {

    /** @var \stdClass The test course. */
    private $course;

    /** @var \stdClass The GO1 activity. */
    private $goone;

    /** @var \cm_info The course module of the GO1 activity. */
    private $cm;

    /** @var \stdClass The enrolled student. */
    private $user;

    /**
     * Makes a course with a GO1 activity that uses the "finish the learning object" rule.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['enablecompletion' => 1]);
        $this->goone = $generator->create_module('goone', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
            'completionsubmit' => 1,
        ]);
        $this->cm = get_coursemodule_from_instance('goone', $this->goone->id, $this->course->id);
        $this->user = $generator->create_user();
        $generator->enrol_user($this->user->id, $this->course->id);
    }

    /**
     * Gets the stored value of goone_completion.completed.
     *
     * @return mixed The stored value, or false if there is no record.
     */
    private function stored_completion() {
        global $DB;
        return $DB->get_field('goone_completion', 'completed',
            ['gooneid' => $this->goone->id, 'userid' => $this->user->id]);
    }

    /**
     * Gets the state that the custom completion rule reports.
     *
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    private function rule_state(): int {
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $cminfo = $modinfo->get_cm($this->cm->id);
        $customcompletion = new custom_completion($cminfo, (int)$this->user->id);
        return $customcompletion->get_state('completionsubmit');
    }

    /**
     * Gets the completion state that Moodle has stored for the activity.
     *
     * @return int The completion state.
     */
    private function module_state(): int {
        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $data = $completion->get_data($modinfo->get_cm($this->cm->id), false, (int)$this->user->id);
        return (int)$data->completionstate;
    }

    /**
     * A progress update after a completion must not remove the completion.
     */
    public function test_progress_update_does_not_remove_completion(): void {
        goone_set_completion($this->cm, $this->user->id, '', 'completed');
        $this->assertEquals(2, $this->stored_completion());
        $this->assertEquals(COMPLETION_COMPLETE, $this->rule_state());

        // The player sends the position in the learning object after it sends the completion.
        goone_set_completion($this->cm, $this->user->id, '/play/12345', 'inprogress');

        $this->assertEquals(2, $this->stored_completion(),
            'A progress update must not change the completed value from 2 to 1.');
        $this->assertEquals(COMPLETION_COMPLETE, $this->rule_state(),
            'The custom completion rule must still report the activity as complete.');
    }

    /**
     * A progress update must still record the position in the learning object.
     */
    public function test_progress_update_still_records_the_location(): void {
        global $DB;

        goone_set_completion($this->cm, $this->user->id, '', 'completed');
        goone_set_completion($this->cm, $this->user->id, '/play/12345', 'inprogress');

        $record = $DB->get_record('goone_completion',
            ['gooneid' => $this->goone->id, 'userid' => $this->user->id]);
        $this->assertEquals('/play/12345', $record->location);
        $this->assertEquals(2, $record->completed);
    }

    /**
     * A progress update before a completion keeps the in-progress value.
     */
    public function test_progress_update_before_completion_stays_in_progress(): void {
        goone_set_completion($this->cm, $this->user->id, '/play/12345', 'inprogress');

        $this->assertEquals(1, $this->stored_completion());
        $this->assertEquals(COMPLETION_INCOMPLETE, $this->rule_state());
    }

    /**
     * Moodle and the plugin must agree after a progress update.
     *
     * A progress update that arrives after a completion must leave the activity
     * complete. A new calculation of the completion data must give the same state.
     */
    public function test_completion_survives_a_recalculation(): void {
        goone_set_completion($this->cm, $this->user->id, '', 'completed');
        goone_set_completion($this->cm, $this->user->id, '/play/12345', 'inprogress');

        $this->assertEquals(COMPLETION_COMPLETE, $this->module_state(),
            'Moodle must show the activity as complete before the recalculation.');

        // Make Moodle calculate the completion state again from the plugin rules.
        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $completion->update_state($modinfo->get_cm($this->cm->id),
            COMPLETION_UNKNOWN, (int)$this->user->id);

        $this->assertEquals(COMPLETION_COMPLETE, $this->module_state(),
            'The activity must stay complete after the recalculation.');
    }
}
