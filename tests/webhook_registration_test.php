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
 * Tests for the registration of the GO1 webhook of the site.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/goone/lib.php');

/**
 * Tests for the registration of the GO1 webhook of the site.
 *
 * The responses have the format of https://developers.go1.com/api/rest/2022-07-01/webhooks/.
 * The GO1 API is replaced with curl::mock_response(), so no test sends a request to GO1.
 *
 * @covers ::mod_goone_check_webhook
 * @covers ::mod_goone_create_webhook
 */
final class webhook_registration_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // A valid token prevents a token request.
        set_config('token', 'token', 'mod_goone');
        set_config('token_time', time(), 'mod_goone');
        set_config('goone_api_secret', 'old secret', 'mod_goone');
    }

    /**
     * Queue GO1 API responses in the order that the code requests them.
     */
    private static function mock_api(string ...$responses): void {
        foreach (array_reverse($responses) as $response) {
            \curl::mock_response($response);
        }
    }

    /**
     * Return the number of queued GO1 responses that no request has used.
     */
    private static function unused_mock_responses(): int {
        $prop = new \ReflectionProperty(\curl::class, 'mockresponses');
        $prop->setAccessible(true);
        $count = count($prop->getValue());
        $prop->setValue(null, []);
        return $count;
    }

    private static function webhook_list(string ...$urls): string {
        $hits = array_map(fn($url) => ['id' => 'id-' . md5($url), 'url' => $url, 'secret_key' => 'their secret',
            'event_types' => ['enrollment.complete'], 'status' => 'active'], $urls);
        return json_encode(['total' => count($hits), 'hits' => $hits]);
    }

    private static function created_response(): string {
        return json_encode(['id' => 'new-id', 'url' => 'https://www.example.com/mod/goone/payload.php',
            'secret_key' => 'the new secret', 'event_types' => ['enrollment.complete'], 'status' => 'active']);
    }

    private static function logs(): string {
        global $DB;
        return implode("\n", $DB->get_fieldset_select('goone_webhook_logs', 'message_info', '1 = 1'));
    }

    public function test_check_finds_the_webhook_of_this_site(): void {
        global $CFG;
        self::mock_api(self::webhook_list('https://other.example.com/mod/goone/payload.php',
            $CFG->wwwroot . '/mod/goone/payload.php'));

        $webhook = mod_goone_check_webhook();

        $this->assertSame($CFG->wwwroot . '/mod/goone/payload.php', $webhook['url']);
    }

    public function test_check_ignores_the_webhook_of_another_site(): void {
        self::mock_api(self::webhook_list('https://other.example.com/mod/goone/payload.php'));

        $this->assertNull(mod_goone_check_webhook());
    }

    public function test_webhook_is_created_when_only_another_site_has_one(): void {
        self::mock_api(self::webhook_list('https://other.example.com/mod/goone/payload.php'), self::created_response());

        $this->assertSame('webhooknewcreatedgood', mod_goone_create_webhook());

        $this->assertNotSame('old secret', get_config('mod_goone', 'goone_api_secret'));
        $this->assertSame(0, self::unused_mock_responses());
    }

    public function test_log_of_the_new_webhook_has_no_secret(): void {
        self::mock_api(self::webhook_list(), self::created_response());

        mod_goone_create_webhook();

        $this->assertStringContainsString('new-id', self::logs());
        $this->assertStringNotContainsString('the new secret', self::logs());
        $this->assertStringNotContainsString(get_config('mod_goone', 'goone_api_secret'), self::logs());
    }

    public function test_existing_webhook_of_this_site_is_not_created_again(): void {
        global $CFG;
        self::mock_api(self::webhook_list($CFG->wwwroot . '/mod/goone/payload.php'), self::created_response());

        $this->assertSame('webhookcreated', mod_goone_create_webhook());

        $this->assertSame('old secret', get_config('mod_goone', 'goone_api_secret'));
        $this->assertSame(1, self::unused_mock_responses(), 'The code sent a create request');
    }

    public function test_failed_list_request_creates_no_webhook(): void {
        // An empty body: a timeout, a connection error or an HTTP 5xx error page.
        self::mock_api('', self::created_response());

        $this->assertSame('webhookcurlerror', mod_goone_create_webhook());

        $this->assertSame('old secret', get_config('mod_goone', 'goone_api_secret'));
        $this->assertSame(1, self::unused_mock_responses(), 'The code sent a create request');
    }

    public function test_rejected_create_request_keeps_the_secret(): void {
        self::mock_api(self::webhook_list(), json_encode(['message' => 'Forbidden']));

        $this->assertSame('webhookcurlerror', mod_goone_create_webhook());

        $this->assertSame('old secret', get_config('mod_goone', 'goone_api_secret'));
        $this->assertStringContainsString('Forbidden', self::logs());
    }

    public function test_no_token_creates_no_webhook(): void {
        set_config('token', '', 'mod_goone');
        self::mock_api(self::webhook_list(), self::created_response());

        $this->assertSame('tokennotdefined', mod_goone_create_webhook());

        $this->assertSame('old secret', get_config('mod_goone', 'goone_api_secret'));
        $this->assertSame(1, self::unused_mock_responses(), 'The code sent a create request');
    }
}
