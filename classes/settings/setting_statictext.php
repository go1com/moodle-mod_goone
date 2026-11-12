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
 * Static Text/HTML output for admin settings forms.
 *
 * @author    Guy Thomas <osdev@blackboard.com>
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_goone\settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Static Text/HTML output for admin settings forms.
 *
 * @package   mod_goone
 * @author    Guy Thomas <osdev@blackboard.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_statictext extends \admin_setting {
    /**
     * @var string $text
     */
    public $text;

    /**
     * Construct admin settings html
     *
     * @param string $name setting name
     * @param string $text setting item html
     */
    public function __construct($name, $text) {
        parent::__construct($name, '', '', null);
        $this->text = $text;
    }
    /**
     * Does not write anything, because the setting only shows text.
     *
     * @param mixed $data The submitted value.
     * @return string An empty string, which means no error.
     */
    public function write_setting($data) {
        return '';
    }
    /**
     * Gives a value, so that the admin tree does not treat the setting as a new setting.
     *
     * @return bool Always true.
     */
    public function get_setting() {
        return true;
    }
    /**
     * Output setting item html into form
     *
     * @param mixed $data The value of the setting. The function does not use it.
     * @param string $query The search query of the admin page.
     * @return string The HTML of the setting.
     */
    public function output_html($data, $query = '') {
        return $this->text;
    }
}
