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
 * Tests that a course reset removes the GO1 progress.
 *
 * @package   mod_goone
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
 * Tests that a course reset removes the GO1 progress.
 *
 * Before the fix, mod_goone did not give the reset callbacks. Moodle thus
 * listed GO1 as not supported on the reset page, and the rows in
 * goone_completion stayed after a reset. The completion rule reads those rows.
 * A learner who opened the activity again became complete at once, without
 * looking at the content.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_test extends advanced_testcase {

    /** @var \stdClass The test course. */
    private $course;

    /** @var \stdClass The GO1 activity. */
    private $goone;

    /** @var \stdClass The course module of the GO1 activity. */
    private $cm;

    /** @var \stdClass The enrolled student. */
    private $user;

    /**
     * Makes a course with a GO1 activity that one learner has completed.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['enablecompletion' => 1]);
        $this->goone = $generator->create_module('goone', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionsubmit' => 1,
        ]);
        $this->cm = get_coursemodule_from_instance('goone', $this->goone->id, $this->course->id);
        $this->user = $generator->create_user();
        $generator->enrol_user($this->user->id, $this->course->id);

        // The learner finishes the learning object and looks at the activity.
        goone_set_completion($this->cm, $this->user->id, '', 'completed');
        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $completion->set_module_viewed($modinfo->get_cm($this->cm->id), (int)$this->user->id);
    }

    /**
     * Counts the GO1 completion rows of the course.
     *
     * @return int The number of rows.
     */
    private function rows(): int {
        global $DB;
        return $DB->count_records_sql(
            'SELECT COUNT(1) FROM {goone_completion} gc
               JOIN {goone} g ON g.id = gc.gooneid
              WHERE g.course = ?', [$this->course->id]);
    }

    /**
     * Gives the state that the custom completion rule reports.
     *
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    private function rule_state(): int {
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $rule = new custom_completion($modinfo->get_cm($this->cm->id), (int)$this->user->id);
        return $rule->get_state('completionsubmit');
    }

    /**
     * Runs a course reset with the given options.
     *
     * @param array $options The options to add to the reset data.
     */
    private function reset_course(array $options): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // reset_course_userdata() reads the course from $data->id and sets
        // $data->courseid itself.
        $data = (object)array_merge(['id' => $this->course->id, 'reset_start_date' => 0], $options);
        reset_course_userdata($data);
    }

    /**
     * The plugin must give the three reset callbacks.
     *
     * Moodle calls them only when they exist. Without them it lists the module
     * as not supported on the reset page and leaves the data as it is.
     */
    public function test_the_reset_callbacks_exist(): void {
        $this->assertTrue(function_exists('goone_reset_userdata'),
            'goone_reset_userdata() must exist, or a course reset leaves the GO1 data.');
        $this->assertTrue(function_exists('goone_reset_course_form_definition'),
            'goone_reset_course_form_definition() must exist to offer the option.');
        $this->assertTrue(function_exists('goone_reset_course_form_defaults'),
            'goone_reset_course_form_defaults() must exist to set the default.');
    }

    /**
     * The GO1 option must remove the progress.
     */
    public function test_the_goone_option_removes_the_progress(): void {
        $this->assertEquals(1, $this->rows());

        $this->reset_course(['reset_goone_completion' => 1]);

        $this->assertEquals(0, $this->rows(),
            'A reset with the GO1 option must remove the rows in goone_completion.');
    }

    /**
     * The option of Moodle to delete the completion data must not act on its own.
     *
     * A site can keep the GO1 records on purpose. Only the GO1 option decides.
     */
    public function test_the_moodle_completion_option_alone_keeps_the_progress(): void {
        $this->assertEquals(1, $this->rows());

        $this->reset_course(['reset_completion' => 1]);

        $this->assertEquals(1, $this->rows(),
            'Only the GO1 option may remove the GO1 rows.');
    }

    /**
     * Gives the two values of the site setting.
     *
     * @return array[] The setting value, and the wanted default of the form option.
     */
    public static function setting_provider(): array {
        return [
            'keep the records' => ['0', 0],
            'remove the records' => ['1', 1],
        ];
    }

    /**
     * The form option must start with the value of the site setting.
     *
     * @param string $setting The value of the site setting.
     * @param int $expected The wanted default of the form option.
     * @dataProvider setting_provider
     */
    public function test_the_form_default_follows_the_setting(string $setting, int $expected): void {
        set_config('resetcompletion', $setting, 'mod_goone');

        $defaults = goone_reset_course_form_defaults($this->course);

        $this->assertArrayHasKey('reset_goone_completion', $defaults);
        $this->assertSame($expected, $defaults['reset_goone_completion'],
            'The reset form must start with the value of the "On course reset" setting.');
    }

    /**
     * The default of the site setting must keep the records.
     */
    public function test_the_setting_defaults_to_keeping_the_records(): void {
        unset_config('resetcompletion', 'mod_goone');

        $defaults = goone_reset_course_form_defaults($this->course);

        $this->assertSame(0, $defaults['reset_goone_completion'],
            'With no value set, the reset form must not offer to remove the GO1 records.');
    }

    /**
     * With the setting on "keep", a reset leaves the two records different.
     *
     * This is the result that the site chooses. Moodle forgets the completion,
     * the GO1 record stays, and the learner becomes complete again at the next
     * view. The description of the setting says so.
     */
    public function test_keeping_the_records_lets_the_learner_complete_again(): void {
        set_config('resetcompletion', '0', 'mod_goone');

        $this->reset_course(array_merge(
            ['reset_completion' => 1], goone_reset_course_form_defaults($this->course)));

        $this->assertEquals(1, $this->rows(), 'The GO1 records must stay.');
        $this->assertEquals(COMPLETION_COMPLETE, $this->rule_state(),
            'The GO1 rule still reports the learner as complete.');

        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $completion->set_module_viewed($modinfo->get_cm($this->cm->id), (int)$this->user->id);

        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $data = $completion->get_data($modinfo->get_cm($this->cm->id), false, (int)$this->user->id);
        $this->assertEquals(COMPLETION_COMPLETE, (int)$data->completionstate,
            'With the records kept, the learner becomes complete again at the next view.');
    }

    /**
     * With the setting on "remove", a reset clears the records.
     */
    public function test_removing_the_records_clears_them(): void {
        set_config('resetcompletion', '1', 'mod_goone');

        $this->reset_course(array_merge(
            ['reset_completion' => 1], goone_reset_course_form_defaults($this->course)));

        $this->assertEquals(0, $this->rows(), 'The GO1 records must go.');
        $this->assertEquals(COMPLETION_INCOMPLETE, $this->rule_state());
    }

    /**
     * A reset must not make the learner complete again.
     *
     * This is the fault that the fix removes. After the reset the learner opened
     * the activity, which satisfied the view rule. The GO1 rule still read the
     * old row, thus the activity and then the course became complete again.
     */
    public function test_the_learner_is_not_complete_after_a_reset(): void {
        $this->assertEquals(COMPLETION_COMPLETE, $this->rule_state());

        $this->reset_course(['reset_completion' => 1, 'reset_goone_completion' => 1]);

        $this->assertEquals(COMPLETION_INCOMPLETE, $this->rule_state(),
            'After a reset the GO1 rule must not report the learner as complete.');

        // The learner opens the activity again, which satisfies the view rule only.
        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $completion->set_module_viewed($modinfo->get_cm($this->cm->id), (int)$this->user->id);

        $modinfo = get_fast_modinfo($this->course, $this->user->id);
        $data = $completion->get_data($modinfo->get_cm($this->cm->id), false, (int)$this->user->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, (int)$data->completionstate,
            'Opening the activity after a reset must not complete it again.');
    }

    /**
     * A reset without either option must keep the progress.
     */
    public function test_a_reset_without_the_options_keeps_the_progress(): void {
        $this->reset_course(['reset_events' => 1]);

        $this->assertEquals(1, $this->rows(),
            'A reset that does not ask for the completion data must keep the GO1 rows.');
    }

    /**
     * The reset must only touch the course that the teacher resets.
     */
    public function test_the_reset_leaves_other_courses_alone(): void {
        $generator = $this->getDataGenerator();
        $other = $generator->create_course(['enablecompletion' => 1]);
        $othergoone = $generator->create_module('goone', [
            'course' => $other->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionsubmit' => 1,
        ]);
        $othercm = get_coursemodule_from_instance('goone', $othergoone->id, $other->id);
        goone_set_completion($othercm, $this->user->id, '', 'completed');

        $this->reset_course(['reset_goone_completion' => 1]);

        global $DB;
        $this->assertEquals(1, $DB->count_records('goone_completion', ['gooneid' => $othergoone->id]),
            'The reset of one course must not touch the GO1 data of another course.');
    }
}
