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

namespace mod_goone;

/**
 * Tests for the language file of mod_goone.
 *
 * @package   mod_goone
 * @category  test
 * @copyright 2026 Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class lang_test extends \basic_testcase {

    /**
     * Each string identifier must occur one time only. A second definition silently replaces the first.
     */
    public function test_no_string_is_defined_twice(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/mod/goone/lang/en/goone.php');
        preg_match_all('/^\$string\[\s*([\'"])(.+?)\1\s*\]\s*=/m', $source, $matches);
        $counts = array_count_values($matches[2]);

        $this->assertNotEmpty($counts);
        $this->assertSame([], array_keys(array_filter($counts, fn($count) => $count > 1)));
    }
}
