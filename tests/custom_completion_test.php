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
 * Contains unit tests for core_completion/activity_custom_completion.
 *
 * @package   mod_goone
 * @copyright Copyright (c) 2021 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;
use cm_info;
use coding_exception;
use mod_goone\completion\custom_completion;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');
/**
 * Class for unit testing mod_goone/activity_custom_completion.
 *
 * @package   mod_goone
 * @copyright Copyright (c) 2021 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_custom_completion_test extends advanced_testcase {
    /**
     * Data provider for get_state().
     *
     * @return array[]
     */
    public function get_state_provider(): array {
        return [

            'Rule available, user has submitted' => [
                'completionsubmit', COMPLETION_ENABLED, true, COMPLETION_COMPLETE, null
            ],
        ];
    }


    /**
     * Test for get_state().
     *
     * @dataProvider get_state_provider
     * @param string $rule The custom completion rule.
     * @param int $available Whether this rule is available.
     * @param bool $submitted
     * @param int|null $status Expected status.
     * @param string|null $exception Expected exception.
     */
    public function test_get_state(string $rule, int $available, ?bool $submitted, ?int $status, ?string $exception) {
        global $DB;
        if (!is_null($exception)) {
            $this->expectException($exception);
        }

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $goone = $this->create_instance($course, ['completion' => COMPLETION_TRACKING_AUTOMATIC, $rule => $available]);

        if ($submitted == true) {
            // Insert record to simulate submission
            $record = new \stdClass();
            $record->gooneid = $goone->id;
            $record->userid = $student->id;
            $record->completed = 2;
            $record->timemodified = time();
            $DB->insert_record('goone_completion', $record);
        }
        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, true, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, (int)$student->id);
        $this->assertEquals($status, $customcompletion->get_state($rule));
    }

    /**
     * Test for get_defined_custom_rules().
     */
    public function test_get_defined_custom_rules() {
        $rules = custom_completion::get_defined_custom_rules();
        $this->assertCount(1, $rules);
        $this->assertEquals('completionsubmit', reset($rules));
    }

    /**
     * Test for get_defined_custom_rule_descriptions().
     */
    public function test_get_custom_rule_descriptions() {
        $this->resetAfterTest();
        // Get defined custom rules.
        $rules = custom_completion::get_defined_custom_rules();
        // Get custom rule descriptions.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $goone = $this->create_instance($course, [
            'completionusegrade' => 1
        ]);

        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, true, MUST_EXIST);
        $cminfo = cm_info::create($cm);
        $customcompletion = new custom_completion($cminfo, 1);
        $ruledescriptions = $customcompletion->get_custom_rule_descriptions();

        // Confirm that defined rules and rule descriptions are consistent with each other.
        $this->assertEquals(count($rules), count($ruledescriptions));
        foreach ($rules as $rule) {
            $this->assertArrayHasKey($rule, $ruledescriptions);
        }
    }

    /**
     * Test for is_defined().
     */
    public function test_is_defined() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $goone = $this->create_instance($course, [
            'completionsubmit' => 1
        ]);

        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, true, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new \mod_goone\completion\custom_completion($cminfo, 1);

        // Rule is defined.
        $this->assertTrue($customcompletion->is_defined('completionsubmit'));

        // Undefined rule.
        $this->assertFalse($customcompletion->is_defined('somerandomrule'));
    }

    /**
     * Data provider for test_get_available_custom_rules().
     *
     * @return array[]
     */
    public function get_available_custom_rules_provider(): array {
        return [
            'Completion submit available' => [
                COMPLETION_ENABLED, ['completionsubmit']
            ],
            'Completion submit not available' => [
                COMPLETION_DISABLED, []
            ],
        ];
    }

    /**
     * Test for get_available_custom_rules().
     *
     * @dataProvider get_available_custom_rules_provider
     * @param int $status
     * @param array $expected
     */
    public function test_get_available_custom_rules(int $status, array $expected) {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => $status]);

        $params = [];
        if ($status == COMPLETION_ENABLED ) {
            $params = [
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionsubmit' => 1
            ];
        }

        $goone = $this->create_instance($course, $params);
        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, true, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, 1);
        $this->assertEquals($expected, $customcompletion->get_available_custom_rules());
    }

    /**
     * @param array $params Array of parameters to pass to the generator
     * @return \stdClass
     */
    protected function create_instance($course, $params = [], $options = []) {
        $params['course'] = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_goone');
        return $generator->create_instance($params, $options);
    }
}
