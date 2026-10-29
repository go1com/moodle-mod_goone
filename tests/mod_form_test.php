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
 * Activity form tests.
 *
 * @package   mod_goone
 * @category  test
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/goone/mod_form.php');

/**
 * Activity form tests class.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_form_test extends advanced_testcase {

    public function test_browser_button_opens_add_url_for_new_activity() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        // The form reads the course sections through $COURSE, which modedit.php sets with require_login().
        $PAGE->set_course($course);
        [, , , , $data] = prepare_new_moduleinfo_data($course, 'goone', 0);

        $form = new \mod_goone_mod_form($data, 0, null, $course);

        $expected = (new \moodle_url('/mod/goone/browser.php', ['mode' => 'add', 'id' => $course->id]))->out(false);
        $this->assertStringContainsString($expected, $this->get_browser_button_onclick($form));
    }

    public function test_browser_button_opens_update_url_for_existing_activity() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        // The form reads the course sections through $COURSE, which modedit.php sets with require_login().
        $PAGE->set_course($course);
        $goone = $this->getDataGenerator()->create_module('goone', ['course' => $course->id]);
        $cm = get_coursemodule_from_id('goone', $goone->cmid, 0, false, MUST_EXIST);
        [$cm, , , $data, $cw] = get_moduleinfo_data($cm, $course);

        $form = new \mod_goone_mod_form($data, $cw->section, $cm, $course);

        $expected = (new \moodle_url('/mod/goone/browser.php', ['mode' => 'update', 'id' => $goone->id]))->out(false);
        $this->assertStringContainsString($expected, $this->get_browser_button_onclick($form));
    }

    /**
     * Get the onclick attribute of the button that opens the GO1 content browser.
     *
     * @param \mod_goone_mod_form $form
     * @return string
     */
    protected function get_browser_button_onclick(\mod_goone_mod_form $form): string {
        $mform = (new \ReflectionProperty(\moodleform::class, '_form'))->getValue($form);
        return (string)$mform->getElement(get_string('contentbrowser', 'goone'))->getAttribute('onclick');
    }
}
