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
 * Class for restore
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Goone restore task that provides all the settings and steps to perform one complete restore of the activity.
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_goone_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('goone', '/activity/goone');

        if ($userinfo) {
            $paths[] = new restore_path_element('goone_completion', '/activity/goone/goonecompletions/goonecompletion');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a goone restore.
     * @param object $data The data in object form
     * @return void
     */
    protected function process_goone($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        
        // Ensure intro is not null. If it is null set to a blank string to avoid DB error.
        if (!property_exists($data, 'intro')) {
            $data->intro = '';
        }

        // Insert the goone record.
        $newitemid = $DB->insert_record('goone', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }
    /**
     * Process  goone activity completions
     * @param object $data The data in object form
     * @return void
     */
    protected function process_goone_completion($data) {
        global $DB;

        $data = (object)$data;

        $data->goone_id = $this->get_new_parentid('goone');
        $data->user_id = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('goone_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }
    /**
     * Unused function
     * @return void
     */
    protected function after_execute() {
        // Add goone related files, no need to match by itemname (just internally handled context).
    }
}
