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
 * Tests the guards on the filter selects of the content browser.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the guards on the filter selects of the content browser.
 *
 * jQuery gives null, and not an empty array, when a multiple select has no
 * selected option. This occurs when the GO1 API does not answer, because the
 * filter bar then has no options at all. Two faults follow:
 *
 * - browser.js called .map() on that value, thus the page stopped with
 *   "Cannot read properties of undefined (reading 'map')";
 * - both modules gave that value to the external functions, which declare
 *   these parameters as arrays, thus the request failed.
 *
 * Moodle 4.5 does not give Jest, thus these tests read the files. A Jest test
 * would run only on Moodle 5.x and would need a second branch.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browser_js_test extends advanced_testcase {

    /**
     * Gives each content browser module, with the ids of its multiple selects.
     *
     * @return array[] The module name, and the select ids it reads.
     */
    public static function module_provider(): array {
        return [
            'content browser' => ['browser', ['type', 'tag', 'language', 'provider']],
            'bulk content browser' => ['browser_bulk', ['type', 'topic', 'language', 'provider', 'duration']],
        ];
    }

    /**
     * Reads a file of the plugin.
     *
     * @param string $relative The path under the directory of the plugin.
     * @return string The contents.
     */
    private function read(string $relative): string {
        global $CFG;
        $path = $CFG->dirroot . '/mod/goone/' . $relative;
        $this->assertFileExists($path);
        return (string)file_get_contents($path);
    }

    /**
     * Each module must have the function that gives an empty array in place of null.
     *
     * @param string $module The name of the AMD module.
     * @param array $selects The ids of the multiple selects.
     * @dataProvider module_provider
     */
    public function test_module_defines_the_guard(string $module, array $selects): void {
        $source = $this->read("amd/src/$module.js");

        $this->assertMatchesRegularExpression(
            '/browser\.selected\s*=\s*function\s*\([^)]*\)\s*\{\s*return\s+\$\([^)]*\)\.val\(\)\s*\|\|\s*\[\]/s',
            $source,
            "amd/src/$module.js must define browser.selected(), which gives [] in place of null.");
    }

    /**
     * No module may read a multiple select without the guard.
     *
     * @param string $module The name of the AMD module.
     * @param array $selects The ids of the multiple selects.
     * @dataProvider module_provider
     */
    public function test_no_multiple_select_is_read_without_the_guard(string $module, array $selects): void {
        $source = $this->read("amd/src/$module.js");

        foreach ($selects as $id) {
            $this->assertDoesNotMatchRegularExpression(
                '/\$\(\s*["\']#' . preg_quote($id, '/') . '["\']\s*\)\.val\(\)/',
                $source,
                "amd/src/$module.js reads #$id with \$(...).val(). Use browser.selected() instead, "
                . "or the value is null when the GO1 API does not answer.");
        }
    }

    /**
     * No module may call .map() straight after .val().
     *
     * This is the call that stopped the page.
     *
     * @param string $module The name of the AMD module.
     * @param array $selects The ids of the multiple selects.
     * @dataProvider module_provider
     */
    public function test_no_map_call_on_an_unguarded_value(string $module, array $selects): void {
        $source = $this->read("amd/src/$module.js");

        $this->assertDoesNotMatchRegularExpression(
            '/\.val\(\)\s*\.\s*map\s*\(/',
            $source,
            "amd/src/$module.js calls .map() straight after .val(). The value is null when nothing "
            . "is selected, thus the page stops with a JavaScript error.");
        $this->assertStringNotContainsString('.val().map(', $source);
    }

    /**
     * The built file must hold the guard.
     *
     * Moodle serves amd/build, and not amd/src. A change to the source without a
     * new build leaves the fault in place on the site.
     *
     * @param string $module The name of the AMD module.
     * @param array $selects The ids of the multiple selects.
     * @dataProvider module_provider
     */
    public function test_built_file_holds_the_guard(string $module, array $selects): void {
        $built = $this->read("amd/build/$module.min.js");

        $this->assertMatchesRegularExpression(
            '/\.val\(\)\s*\|\|\s*\[\]/', $built,
            "amd/build/$module.min.js does not hold the guard. Build the module again with "
            . "\"npx grunt amd --root=mod/goone\".");
        $this->assertDoesNotMatchRegularExpression(
            '/\.val\(\)\s*\.\s*map\s*\(/', $built,
            "amd/build/$module.min.js still calls .map() straight after .val().");
    }
}
