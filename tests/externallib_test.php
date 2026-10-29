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
 * Tests the descriptions of the external functions.
 *
 * @package   mod_goone
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;
use core_external\external_description;
use core_external\external_function_parameters;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/externallib.php');

/**
 * Tests the descriptions of the external functions.
 *
 * The plugin uses the classes of the core_external namespace. The old names,
 * for example external_api, come from lib/externallib.php, which Moodle will
 * remove. These tests make sure that each external function still gives a
 * correct description, thus the services of the plugin stay usable.
 *
 * The tests do not call the GO1 API. They only read the descriptions.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externallib_test extends advanced_testcase {

    /**
     * Gives the name of each external function of the plugin.
     *
     * @return array[] The function names.
     */
    public static function external_function_provider(): array {
        $names = [
            'get_hits',
            'get_modal',
            'bulk_goone_get_hits',
            'bulk_goone_get_modal',
            'bulk_goone_get_course_search_result',
            'bulk_goone_process_course_per_item',
            'bulk_goone_process_course_single_course',
            'bulk_goone_process_add_to_existing_course',
        ];
        $cases = [];
        foreach ($names as $name) {
            $cases[$name] = [$name];
        }
        return $cases;
    }

    /**
     * The class must extend the external API class of the core_external namespace.
     */
    public function test_class_extends_the_namespaced_external_api(): void {
        $this->assertTrue(
            is_subclass_of('mod_goone_external', \core_external\external_api::class),
            'mod_goone_external must extend core_external\external_api.');
    }

    /**
     * Each function must describe its parameters.
     *
     * @param string $name The name of the external function.
     * @dataProvider external_function_provider
     */
    public function test_parameters_description(string $name): void {
        $method = $name . '_parameters';
        $this->assertTrue(method_exists('mod_goone_external', $method),
            "mod_goone_external::$method() is missing.");

        $description = \mod_goone_external::$method();
        $this->assertInstanceOf(external_function_parameters::class, $description,
            "mod_goone_external::$method() must give an external_function_parameters object.");
    }

    /**
     * Each function must describe what it gives back.
     *
     * @param string $name The name of the external function.
     * @dataProvider external_function_provider
     */
    public function test_returns_description(string $name): void {
        $method = $name . '_returns';
        $this->assertTrue(method_exists('mod_goone_external', $method),
            "mod_goone_external::$method() is missing.");

        $description = \mod_goone_external::$method();
        $this->assertInstanceOf(external_description::class, $description,
            "mod_goone_external::$method() must give an external_description object.");
    }

    /**
     * Every function in db/services.php must be present in the class.
     */
    public function test_services_agree_with_the_class(): void {
        global $CFG;

        $functions = [];
        include($CFG->dirroot . '/mod/goone/db/services.php');

        $this->assertNotEmpty($functions, 'db/services.php must declare functions.');
        foreach ($functions as $name => $definition) {
            $this->assertTrue(
                method_exists($definition['classname'], $definition['methodname']),
                "The service $name names a method that does not exist.");
        }
    }
}
