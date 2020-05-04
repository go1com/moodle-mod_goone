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
 * Form to add or update a GO1 activity module
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');

if (isset($data->add)) {
    $browserurl = (new moodle_url('/mod/goone/browser.php',
        array (
            'mode' => 'add',
            'id' => $course->id
        )
    ))->out(false);
} else if (isset($data->update)) {
    $browserurl = (new moodle_url('/mod/goone/browser.php',
        array (
            'mode' => 'update',
            'id' => $data->id
        )
    ))->out(false);
}
/**
 * Goone settings form.
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_goone_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE, $browserurl;

        $mform =& $this->_form;
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('nonameselected', 'goone'), 'required', null, 'client');
        $mform->addElement('button', get_string('contentbrowser', 'goone'),
            get_string('lobrowser', 'goone'), array('onclick' => "window.open('$browserurl')"));

        $mform->addElement('text', 'loid', get_string('selectedloid', 'goone'), array('size' => '16', 'readonly'));
        $mform->setType('loid', PARAM_TEXT);
        $mform->addRule('loid', get_string('noloselected', 'goone'), 'required', null, 'client');
        $mform->addElement('text', 'loname', get_string('selectedloname', 'goone'), array('size' => '64', 'readonly'));
        $mform->setType('loname', PARAM_TEXT);
        $mform->addRule('loname', get_string('noloselected', 'goone'), 'required', null, 'client');

         $mform->addElement('select', 'popup', get_string('display', 'scorm'), goone_get_popup_display_array());

        $this->standard_intro_elements(get_string('description', 'goone'));
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completionlo', 'goone'));
        // Enable this completion rule by default.
        $mform->setDefault('completionsubmit', 1);
        return array('completionsubmit');
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

}

