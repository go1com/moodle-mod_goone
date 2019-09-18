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
     * unused function to write setting data
     *
     * @param object $data
     * @return void
     */
    public function write_setting($data) {
        return '';
    }
    /**
     * retreive setting object for renderer
     *
     * @return bool
     */
    public function get_setting() {
        return true;
    }
    /**
     * ouput setting item html into form
     *
     * @param object $data setting
     * @param string $query null
     * @return string
     */
    public function output_html($data, $query = '') {
        return $this->text;
    }
}
