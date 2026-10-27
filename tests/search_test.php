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

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');

/**
 * Tests for the search of the GO1 content browser.
 *
 * The GO1 API is replaced with curl::mock_response(), thus no test sends a request
 * to GO1.
 *
 * @package   mod_goone
 * @category  test
 * @copyright 2026, Androgogic
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::goone_get_hits
 * @covers ::bulk_mod_goone_get_hits
 */
final class search_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('token', 'token', 'mod_goone');
        set_config('token_time', time(), 'mod_goone');
        set_config('filtersel', 0, 'mod_goone');
    }

    /**
     * Gives each answer from GO1 that holds no list of hits.
     *
     * @return array[] The body of the answer.
     */
    public static function bad_answer_provider(): array {
        return [
            'no body' => [''],
            'body that is not JSON' => ['<html>a gateway said no</html>'],
            'JSON without hits' => ['{"message":"Bad Request"}'],
            'hits that are not a list' => ['{"total":0,"hits":"none"}'],
        ];
    }

    /**
     * The content browser must say that the request failed.
     *
     * Before this check the code read a list that was not there. The page then
     * showed no results and no number of results, and the user was not told that
     * anything had failed.
     *
     * @dataProvider bad_answer_provider
     * @param string $answer The body that GO1 gives back.
     */
    public function test_browser_says_the_request_failed(string $answer): void {
        \curl::mock_response($answer);

        $this->expectException(\moodle_exception::class);
        goone_get_hits([], [], [], [], 'anything', 'relevance', 0);
    }

    /**
     * The bulk content browser must say the same.
     *
     * @dataProvider bad_answer_provider
     * @param string $answer The body that GO1 gives back.
     */
    public function test_bulk_browser_says_the_request_failed(string $answer): void {
        \curl::mock_response($answer);

        $this->expectException(\moodle_exception::class);
        bulk_mod_goone_get_hits([], [], [], [], 'anything', 'relevance', 0, false, []);
    }

    /**
     * Gives each value that must survive the trip to GO1.
     *
     * A space is the value that broke the bulk content browser: curl refuses an
     * address that holds a space, with "Malformed input to a URL function".
     *
     * @return array[] The value, and the form it must take in the address.
     */
    public static function unsafe_value_provider(): array {
        return [
            'space' => ['1 minute', '1%20minute'],
            'two words' => ['IT Skills', 'IT%20Skills'],
            'and sign' => ['this & that', 'this%20%26%20that'],
            'plus sign' => ['a+b', 'a%2Bb'],
            'hash' => ['c#', 'c%23'],
        ];
    }

    /**
     * Every value of a filter must be made safe for the address of the request.
     *
     * rawurlencode is the rule that both browsers follow. A value that is not made
     * safe gives an address that curl refuses, and the page then shows nothing.
     *
     * @dataProvider unsafe_value_provider
     * @param string $value The value that a user chooses.
     * @param string $expected The form that the address must hold.
     */
    public function test_a_filter_value_is_made_safe(string $value, string $expected): void {
        $this->assertSame($expected, rawurlencode($value));
        $this->assertStringNotContainsString(' ', rawurlencode($value));
    }

    /**
     * The code of both browsers must make its values safe.
     *
     * This reads the source, because the address of the request is built inside the
     * function and is not given back. It is a guard against the encoding being
     * dropped again.
     */
    public function test_both_browsers_make_their_values_safe(): void {
        global $CFG;
        $source = (string)file_get_contents($CFG->dirroot . '/mod/goone/lib.php');

        foreach (["'keyword' => rawurlencode(\$keyword)",
                  "'sort' => rawurlencode(\$sort)",
                  "implode(',', array_map('rawurlencode', \$type))",
                  "implode(',', array_map('rawurlencode', \$provider))"] as $needle) {
            $this->assertSame(2, substr_count($source, $needle),
                "Both the content browser and the bulk content browser must hold: $needle");
        }

        foreach (['"&language%5B%5D=".rawurlencode($lang)',
                  '"&tags%5B%5D=".rawurlencode($ta)',
                  '"&topics%5B%5D=".rawurlencode($ta)'] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    /**
     * The page must not make a tag safe a second time.
     *
     * The module used to put %20 in place of each space before it sent a tag. The
     * server now makes every value safe, thus the module must send the tag as the
     * user chose it. Both together would give %2520.
     */
    public function test_the_page_does_not_make_a_tag_safe_again(): void {
        global $CFG;

        foreach (['amd/src/browser.js', 'amd/build/browser.min.js'] as $file) {
            $source = (string)file_get_contents($CFG->dirroot . '/mod/goone/' . $file);
            $this->assertStringNotContainsString('%20', $source,
                "$file must send a tag as it is. The server makes it safe.");
        }
    }
}
