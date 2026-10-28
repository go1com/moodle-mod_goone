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
 * Backup and restore tests.
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
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Backup and restore tests class.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_restore_test extends advanced_testcase {

    public function test_duplicate_restores_goone_instance() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $goone = $this->getDataGenerator()->create_module('goone', ['course' => $course->id, 'loid' => '12345']);

        $newcm = $this->duplicate_activity($course, $goone->cmid);

        $new = $DB->get_record('goone', ['id' => $newcm->instance], '*', MUST_EXIST);
        $this->assertEquals('12345', $new->loid);

        // The backup does not carry the launch token. This agrees with view.php, which
        // gets a new token from GO1 when the activity has none. A launch token belongs
        // to one activity and does not stay valid, thus a copy must not keep it.
        $this->assertEmpty($new->token,
            'A copy of an activity must not keep the launch token of the source activity.');
    }

    public function test_duplicate_keeps_intro_files_and_links() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $goone = $this->getDataGenerator()->create_module('goone', ['course' => $course->id]);
        $link = $CFG->wwwroot . '/mod/goone/view.php?id=' . $goone->cmid;
        $DB->update_record('goone', [
            'id' => $goone->id,
            'intro' => '<p>Intro <a href="' . $link . '">self</a></p>',
            'introformat' => FORMAT_HTML,
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($goone->cmid)->id,
            'component' => 'mod_goone',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'image.png',
        ], 'image');

        $newcm = $this->duplicate_activity($course, $goone->cmid);

        $new = $DB->get_record('goone', ['id' => $newcm->instance], '*', MUST_EXIST);
        $newlink = $CFG->wwwroot . '/mod/goone/view.php?id=' . $newcm->id;
        $this->assertEquals('<p>Intro <a href="' . $newlink . '">self</a></p>', $new->intro);
        $this->assertEquals(FORMAT_HTML, $new->introformat);
        $this->assertTrue(get_file_storage()->file_exists(
            \context_module::instance($newcm->id)->id, 'mod_goone', 'intro', 0, '/', 'image.png'));
    }

    /**
     * Makes a copy of an activity.
     *
     * Moodle 5.2 made duplicate_module() deprecated and gives
     * core_courseformat\local\cmactions::duplicate() in its place. The cmactions
     * class is present on Moodle 4.5 and later, but the duplicate() method is only
     * present on Moodle 5.2 and later. The test thus looks for the method, not for
     * the class.
     *
     * @param \stdClass $course The course.
     * @param int $cmid The course module id of the activity to copy.
     * @return object The new course module.
     */
    private function duplicate_activity(\stdClass $course, int $cmid) {
        // Test the method on the class name, and not on an object. The class is
        // present on Moodle 4.5, but its constructor there does not accept a course.
        if (method_exists('\core_courseformat\local\cmactions', 'duplicate')) {
            $cmactions = new \core_courseformat\local\cmactions($course);
            return $cmactions->duplicate($cmid);
        }
        return duplicate_module($course, get_fast_modinfo($course)->get_cm($cmid));
    }
}
