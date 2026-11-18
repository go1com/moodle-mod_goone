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

use mod_goone\local\connection_check;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Tests for the GO1 connection check.
 *
 * The tests replace curl and the DNS lookup, thus no test sends a request to GO1.
 *
 * @package   mod_goone
 * @category  test
 * @copyright 2026 Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_goone\local\connection_check
 */
#[\PHPUnit\Framework\Attributes\CoversClass(connection_check::class)]
final class connection_check_test extends \advanced_testcase {

    /** @var string The client secret in the configuration. */
    private const CLIENT_SECRET = 'client-secret-value';

    /** @var string The stored token in the configuration. */
    private const STORED_TOKEN = 'stored-token-value';

    /** @var string The token that the fake GO1 gives. */
    private const NEW_TOKEN = 'new-token-value';

    /** @var string The signing secret of the webhook that the fake GO1 gives. */
    private const WEBHOOK_SECRET = 'webhook-secret-value';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // With no blocked hosts and ports, the curl security helper does no DNS lookup.
        set_config('curlsecurityblockedhosts', '');
        set_config('curlsecurityallowedport', '');
        set_config('client_id', 'client-id-value', 'mod_goone');
        set_config('client_secret', self::CLIENT_SECRET, 'mod_goone');
        set_config('token', self::STORED_TOKEN, 'mod_goone');
        set_config('token_time', time() - 100, 'mod_goone');
    }

    /**
     * Gives the responses of a GO1 that accepts all the requests.
     *
     * @return array The URL start => [HTTP status, body].
     */
    private function good_responses(): array {
        global $CFG;
        return [
            connection_check::VALIDATE_URL => [200, '{}'],
            connection_check::TOKEN_URL => [200, json_encode(['token_type' => 'Bearer', 'access_token' => self::NEW_TOKEN])],
            connection_check::API_URL => [200, json_encode(['total' => 5, 'hits' => []])],
            connection_check::WEBHOOKS_URL => [200, json_encode(['hits' => [[
                'url' => $CFG->wwwroot . '/mod/goone/payload.php',
                'secret_key' => self::WEBHOOK_SECRET,
            ]]])],
            connection_check::PLAYER_URL => [200, 'var player = 1;'],
        ];
    }

    /**
     * Makes a connection check that uses fake responses in place of GO1.
     *
     * A URL that has no fake response fails with a curl error, as when the host is not available.
     *
     * @param array $responses The URL start => [HTTP status, body].
     * @return connection_check The check. Its property $requests records the URL and the headers of each request.
     */
    private function make_check(array $responses): connection_check {
        return new class($responses) extends connection_check {
            /** @var array The URL and the headers of each request. */
            public array $requests = [];

            /** @var array The URL start => [HTTP status, body]. */
            private array $responses;

            /**
             * Constructor.
             *
             * @param array $responses The URL start => [HTTP status, body].
             */
            public function __construct(array $responses) {
                $this->responses = $responses;
            }

            /**
             * Gives a fake response, and records the request.
             *
             * @param \curl $curl The curl object of the request.
             * @param string $url The URL of the request.
             * @return string|bool The body, or false when there is no fake response.
             */
            public function fake_request(\curl $curl, string $url) {
                $this->requests[] = ['url' => $url, 'header' => $curl->header];
                foreach ($this->responses as $start => [$httpcode, $body]) {
                    if (str_starts_with($url, $start)) {
                        $curl->info = ['http_code' => $httpcode, 'primary_ip' => '192.0.2.1', 'total_time' => 0.1];
                        $curl->errno = 0;
                        $curl->error = '';
                        return $body;
                    }
                }
                $curl->info = ['http_code' => 0];
                $curl->errno = CURLE_COULDNT_CONNECT;
                $curl->error = 'Failed to connect';
                return false;
            }

            protected function new_curl(): \curl {
                return new class($this) extends \curl {
                    /** @var object The connection check that gives the fake responses. */
                    private object $check;

                    /**
                     * Constructor.
                     *
                     * @param object $check The connection check that gives the fake responses.
                     */
                    public function __construct(object $check) {
                        parent::__construct();
                        $this->check = $check;
                    }

                    protected function request($url, $options = []) {
                        return $this->check->fake_request($this, $url);
                    }
                };
            }

            protected function resolve_host(string $host) {
                return ['192.0.2.1'];
            }
        };
    }

    /**
     * Gives all the text that the check shows.
     *
     * @param connection_check $check The check after run().
     * @return string The text.
     */
    private function all_text(connection_check $check): string {
        return json_encode([$check->get_sections(), $check->get_results(), $check->get_warnings()]);
    }

    /**
     * Gives the URLs that the check sent requests to.
     *
     * @param connection_check $check The check after run().
     * @return array The URLs.
     */
    private function requested_urls(connection_check $check): array {
        return array_column($check->requests, 'url');
    }

    public function test_all_checks_pass_when_go1_accepts_all_requests(): void {
        $check = $this->make_check($this->good_responses());

        $check->run();

        $this->assertTrue($check->passed());
        $this->assertSame([true, true, true], array_column($check->get_results(), 'passed'));
        $this->assertSame([], $check->get_warnings());
    }

    public function test_api_and_gateway_requests_use_the_new_token(): void {
        $check = $this->make_check($this->good_responses());

        $check->run();

        foreach ($check->requests as $request) {
            if (str_starts_with($request['url'], connection_check::API_URL)
                    || str_starts_with($request['url'], connection_check::WEBHOOKS_URL)) {
                $this->assertContains('Authorization: Bearer ' . self::NEW_TOKEN, $request['header']);
            }
        }
    }

    public function test_the_check_shows_no_secrets(): void {
        $check = $this->make_check($this->good_responses());

        $check->run();

        $text = $this->all_text($check);
        $this->assertStringNotContainsString(self::CLIENT_SECRET, $text);
        $this->assertStringNotContainsString(self::STORED_TOKEN, $text);
        $this->assertStringNotContainsString(self::NEW_TOKEN, $text);
        $this->assertStringNotContainsString(self::WEBHOOK_SECRET, $text);
    }

    public function test_the_check_removes_secrets_from_a_failed_response(): void {
        $responses = $this->good_responses();
        $responses[connection_check::TOKEN_URL] = [400, json_encode([
            'error' => 'invalid_request',
            'access_token' => self::NEW_TOKEN,
            'details' => ['client_secret' => self::CLIENT_SECRET],
        ])];
        $check = $this->make_check($responses);

        $check->run();

        $text = $this->all_text($check);
        $this->assertStringContainsString('invalid_request', $text);
        $this->assertStringContainsString('[REMOVED]', $text);
        $this->assertStringNotContainsString(self::NEW_TOKEN, $text);
        $this->assertStringNotContainsString(self::CLIENT_SECRET, $text);
    }

    public function test_a_failed_token_request_fails_all_server_checks_and_skips_the_api_requests(): void {
        $responses = $this->good_responses();
        $responses[connection_check::TOKEN_URL] = [401, json_encode(['error' => 'invalid_client'])];
        $check = $this->make_check($responses);

        $check->run();

        $this->assertFalse($check->passed());
        $this->assertSame([false, false, false], array_column($check->get_results(), 'passed'));
        $this->assertStringContainsString('invalid_client', $this->all_text($check));
        foreach ($this->requested_urls($check) as $url) {
            $this->assertStringStartsNotWith(connection_check::API_URL, $url);
            $this->assertStringStartsNotWith(connection_check::WEBHOOKS_URL, $url);
        }
    }

    public function test_the_check_shows_the_curl_error(): void {
        $responses = $this->good_responses();
        unset($responses[connection_check::TOKEN_URL]);
        $check = $this->make_check($responses);

        $check->run();

        $this->assertFalse($check->passed());
        $this->assertStringContainsString('Failed to connect', $this->all_text($check));
    }

    public function test_a_failed_api_request_fails_only_its_check(): void {
        $responses = $this->good_responses();
        $responses[connection_check::API_URL] = [403, json_encode(['message' => 'Forbidden'])];
        $check = $this->make_check($responses);

        $check->run();

        $this->assertFalse($check->passed());
        $this->assertSame([true, false, true], array_column($check->get_results(), 'passed'));
        $this->assertStringContainsString('Forbidden', $this->all_text($check));
    }

    public function test_a_stored_token_that_is_not_valid_is_only_a_warning(): void {
        $responses = $this->good_responses();
        $responses[connection_check::VALIDATE_URL] = [401, '{}'];
        $check = $this->make_check($responses);

        $check->run();

        $this->assertTrue($check->passed());
        $this->assertCount(1, $check->get_warnings());
    }

    public function test_a_missing_webhook_and_a_missing_player_script_are_only_warnings(): void {
        $responses = $this->good_responses();
        $responses[connection_check::WEBHOOKS_URL] = [200, json_encode(['hits' => []])];
        unset($responses[connection_check::PLAYER_URL]);
        $check = $this->make_check($responses);

        $check->run();

        $this->assertTrue($check->passed());
        $this->assertCount(2, $check->get_warnings());
    }

    public function test_empty_credentials_give_warnings_and_the_token_request_is_still_sent(): void {
        set_config('client_id', '', 'mod_goone');
        set_config('client_secret', '', 'mod_goone');
        $responses = $this->good_responses();
        $responses[connection_check::TOKEN_URL] = [401, json_encode(['error' => 'invalid_client'])];
        $check = $this->make_check($responses);

        $check->run();

        $this->assertSame([
            get_string('check_warning_notset', 'mod_goone', get_string('clientid', 'mod_goone')),
            get_string('check_warning_notset', 'mod_goone', get_string('clientsecret', 'mod_goone')),
        ], $check->get_warnings());
        $this->assertContains(connection_check::TOKEN_URL, $this->requested_urls($check));
        $this->assertFalse($check->passed());
    }

    public function test_the_check_does_not_validate_a_missing_stored_token(): void {
        set_config('token', '', 'mod_goone');
        $check = $this->make_check($this->good_responses());

        $check->run();

        $this->assertNotContains(connection_check::VALIDATE_URL, $this->requested_urls($check));
        $this->assertTrue($check->passed());
    }

    public function test_the_check_does_not_change_the_configuration(): void {
        $before = get_config('mod_goone');
        $check = $this->make_check($this->good_responses());

        $check->run();

        $this->assertEquals($before, get_config('mod_goone'));
    }
}
