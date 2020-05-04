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
 * Generator tests.
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generator tests class.
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */
class mod_goone_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('goone', array('course' => $course->id)));
        $goone = $this->getDataGenerator()->create_module('goone', array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('goone', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('goone', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('goone', array('id' => $goone->id)));

        $params = array('course' => $course->id, 'name' => 'GO1 Test');
        $goone = $this->getDataGenerator()->create_module('goone', $params);
        $this->assertEquals(2, $DB->count_records('goone', array('course' => $course->id)));
        $this->assertEquals('GO1 Test', $DB->get_field_select('goone', 'name', 'id = :id', array('id' => $goone->id)));

    }
}
