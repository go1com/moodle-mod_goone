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
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::goone_get_hits
 * @covers ::bulk_mod_goone_get_hits
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('goone_get_hits')]
#[\PHPUnit\Framework\Attributes\CoversFunction('bulk_mod_goone_get_hits')]
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
     * An answer without a list of hits is not usable. The browser must raise an
     * error, in place of showing an empty list and an empty number of results.
     *
     * @dataProvider bad_answer_provider
     * @param string $answer The body that GO1 gives back.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bad_answer_provider')]
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
    #[\PHPUnit\Framework\Attributes\DataProvider('bad_answer_provider')]
    public function test_bulk_browser_says_the_request_failed(string $answer): void {
        \curl::mock_response($answer);

        $this->expectException(\moodle_exception::class);
        bulk_mod_goone_get_hits([], [], [], [], 'anything', 'relevance', 0, false, []);
    }

    /**
     * GO1 can send a hit without the optional fields. Both content browsers must show it
     * without a PHP warning.
     */
    public function test_both_browsers_show_a_hit_without_optional_fields(): void {
        $answer = json_encode(['total' => 1, 'hits' => [['id' => 1, 'subscription' => ['id' => 2]]]]);

        \curl::mock_response($answer);
        $this->assertIsString(goone_get_hits([], [], [], [], '', 'relevance', 0));

        \curl::mock_response($answer);
        $this->assertIsString(bulk_mod_goone_get_hits([], [], [], [], '', 'relevance', 0, false, []));
    }

    /**
     * The details of a learning object need no PHP warning when GO1 gives an answer that is
     * not JSON.
     */
    public function test_both_modals_accept_an_answer_that_is_not_json(): void {
        \curl::mock_response('<html>a gateway said no</html>');
        $this->assertSame('{"has_items":false}', goone_modal_overview(1));

        \curl::mock_response('<html>a gateway said no</html>');
        $this->assertSame('{"has_items":false}', bulk_mod_goone_modal_overview(1));
    }

    /**
     * The facets for the filters need no PHP warning when GO1 gives an answer that is not
     * usable. An answer that is not JSON gives null. JSON without language facets is kept.
     */
    public function test_facets_accept_an_answer_that_is_not_usable(): void {
        \curl::mock_response('<html>a gateway said no</html>');
        $this->assertNull(goone_get_facets());

        \curl::mock_response('{"facets":{"tag":{"buckets":[]}}}');
        $this->assertSame(['facets' => ['tag' => ['buckets' => []]]], goone_get_facets());
    }

    /**
     * Each duration filter of the bulk content browser must work without a PHP warning.
     * The filter "60 min" has a minimum and no maximum.
     */
    public function test_bulk_browser_accepts_each_duration(): void {
        foreach (['1', '2', '3', '4'] as $duration) {
            \curl::mock_response('{"total":0,"hits":[]}');

            $this->assertIsString(bulk_mod_goone_get_hits([], [], [], [], '', 'relevance', 0, false, [$duration]));
        }
    }

    /**
     * GO1 can send a hit without a language. The bulk content browser must show
     * "Unknown language", and PHP 8.1 and later must not show a deprecation.
     */
    public function test_bulk_browser_shows_a_hit_without_language(): void {
        \curl::mock_response(json_encode(['total' => 1, 'hits' => [[
            'id' => 1,
            'title' => 'Title',
            'description' => 'Description',
            'provider' => ['name' => 'Provider'],
            'pricing' => ['price' => 0],
            'image' => 'https://example.com/image.png',
            'language' => null,
        ]]]));

        $html = bulk_mod_goone_get_hits([], [], [], [], '', 'relevance', 0, false, []);

        $this->assertStringContainsString(get_string('unknownlanguage', 'mod_goone'), $html);
    }

    /**
     * Gives each value that must survive the trip to GO1.
     *
     * curl refuses an address that holds a space, with the message "Malformed
     * input to a URL function". A space must thus never reach the address.
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
    #[\PHPUnit\Framework\Attributes\DataProvider('unsafe_value_provider')]
    public function test_a_filter_value_is_made_safe(string $value, string $expected): void {
        $this->assertSame($expected, rawurlencode($value));
        $this->assertStringNotContainsString(' ', rawurlencode($value));
    }

    /**
     * The code of both browsers must make its values safe.
     *
     * This reads the source, because the address of the request is built inside the
     * function and is not given back.
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
     * The server makes every value safe, thus the page sends each tag as the user
     * chose it. A page that also makes the tag safe gives %2520 for a space.
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
