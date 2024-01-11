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
 * Class for backup
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_goone_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the assign activity
     * @return void
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $goone = new backup_nested_element('goone', array('id'),
                                            array('name',
                                                  'loid',
                                                  'loname',
                                                  'token',
                                                  'completionsubmit',
                                                  'popup',
                                                  'timecreated',
                                                  'timemodified'));

        $goonecompletions = new backup_nested_element('goonecompletions');

        $goonecompletion = new backup_nested_element('goone_completion', array('id'),
                                            array('gooneid',
                                                  'userid',
                                                  'location',
                                                  'completed'));
                // Build the tree.
                $goone->add_child($goonecompletions);
                $goonecompletions->add_child($goonecompletion);

        // Define sources.
        $goone->set_source_table('goone', array('id' => backup::VAR_ACTIVITYID));

         // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $goonecompletion->set_source_table('goone_completion', array('gooneid' => '../../id'));
        }

        // Define id annotations.
        $goonecompletion->annotate_ids('user', 'userid');

        // Define file annotations.
        // (none).

        // Return the root element (goone), wrapped into standard activity structure.
        return $this->prepare_activity_structure($goone);
    }

}

