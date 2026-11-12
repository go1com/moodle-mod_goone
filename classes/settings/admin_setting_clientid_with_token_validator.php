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
 * Class to create an admin setting for the clientid, that checks if the resulting token
 * is valid, and displays a message accordingly, when the user saves config changes.
 *
 * @package   mod_goone
 * @copyright Open LMS 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Julian Tovar <julian.tovar@openlms.net>
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */

namespace mod_goone\settings;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/goone/lib.php');

/**
 * The Client ID setting. After a change of the settings, it shows if GO1 gives a valid token.
 *
 * @package   mod_goone
 * @copyright Open LMS 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_clientid_with_token_validator extends \admin_setting_configtext {

    /**
     * Writes the setting, and records that the settings changed.
     *
     * @param mixed $data The submitted value.
     * @return string An empty string, or an error message.
     */
    public function write_setting($data) {
        set_config('configchange', 1, 'mod_goone');
        return parent::write_setting($data);
    }

    /**
     * Gives the HTML of the setting. After a change of the settings, a token test result comes first.
     *
     * @param mixed $data The value of the setting.
     * @param string $query The search query of the admin page.
     * @return string The HTML of the setting.
     */
    public function output_html($data, $query='') {
        global $OUTPUT;
        $html = parent::output_html($data, $query);

        // If the user has not saved config changes, return the HTML as is.
        $configchanged = get_config('mod_goone', 'configchange');
        if (!$configchanged) {
            return $html;
        }

        // Otherwise, return the HTML with the token validation notification prepended to it.
        if (goone_tokentest()) {
            $notify = new \core\output\notification(get_string('connectionsuccess',
                'goone'), \core\output\notification::NOTIFY_SUCCESS);
        } else {
            $notify = new \core\output\notification(get_string('connectionerroradmin',
                'goone'), \core\output\notification::NOTIFY_ERROR);
        }
        set_config('configchange', 0, 'mod_goone');
        return \html_writer::div($OUTPUT->render($notify), 'form-item') . $html;
    }

}
