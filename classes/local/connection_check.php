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

namespace mod_goone\local;

use core\files\curl_security_helper;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Checks the connection from this site to the GO1 hosts that the plugin uses.
 *
 * The check sends read-only requests that are the same as the requests of the plugin, and records
 * the details that the plugin pages do not show. It does not change the configuration, and it does
 * not record the client secret, the tokens or other secrets.
 *
 * The CLI script cli/check_connection.php and the page checkconnection.php show the result.
 *
 * @package   mod_goone
 * @copyright 2026 Androgogic Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */
class connection_check implements \renderable, \templatable {

    /** @var string The URL that gives a new token. */
    public const TOKEN_URL = 'https://auth.go1.com/oauth/token';

    /** @var string The URL that validates a token. */
    public const VALIDATE_URL = 'https://auth.go1.com/oauth/validate';

    /** @var string The URL of the learning objects in the GO1 API. */
    public const API_URL = 'https://api.go1.com/v2/learning-objects';

    /** @var string The URL of the webhooks in the GO1 gateway. */
    public const WEBHOOKS_URL = 'https://gateway.go1.com/webhooks';

    /** @var string The URL of a SCORM player script. The browsers of the learners load it. */
    public const PLAYER_URL = 'https://api.go1.co/scorm/assets/service.js';

    /** @var int goone_tokentest() accepts a stored token that is younger than this, in seconds, without a request to GO1. */
    private const TOKEN_MAX_AGE = 43195;

    /** @var array Each section: ['title' => string, 'lines' => [['label' => string, 'value' => string]], 'notes' => [string]]. */
    private array $sections = [];

    /** @var array Each result of a server host check: ['name' => string, 'passed' => bool]. */
    private array $results = [];

    /** @var string[] The warnings. A warning does not make the check fail. */
    private array $warnings = [];

    /**
     * Runs all the checks.
     */
    public function run(): void {
        $this->sections = [];
        $this->results = [];
        $this->warnings = [];

        $this->check_environment();
        $this->check_settings();
        $this->check_http_settings();
        $this->check_dns();
        $this->validate_stored_token();
        $token = $this->request_token();
        if ($token === '') {
            $this->add_note(get_string('check_note_notoken', 'mod_goone'));
            $this->add_result(get_string('check_result_api', 'mod_goone'), false);
            $this->add_result(get_string('check_result_webhooks', 'mod_goone'), false);
        } else {
            $this->check_api($token);
            $this->check_webhooks($token);
        }
        $this->check_player();
    }

    /**
     * Gives the sections of details.
     *
     * @return array Each section: ['title' => string, 'lines' => [['label' => string, 'value' => string]], 'notes' => [string]].
     */
    public function get_sections(): array {
        return $this->sections;
    }

    /**
     * Gives the results of the server host checks.
     *
     * @return array Each result: ['name' => string, 'passed' => bool].
     */
    public function get_results(): array {
        return $this->results;
    }

    /**
     * Gives the warnings.
     *
     * @return string[] The warnings.
     */
    public function get_warnings(): array {
        return $this->warnings;
    }

    /**
     * Tells if all the server host checks passed.
     *
     * @return bool True if all the server host checks passed.
     */
    public function passed(): bool {
        return !in_array(false, array_column($this->results, 'passed'), true);
    }

    /**
     * Gives the data for the template mod_goone/connection_check.
     *
     * @param renderer_base $output The renderer.
     * @return array The template data.
     */
    public function export_for_template(renderer_base $output): array {
        $sections = [];
        foreach ($this->sections as $section) {
            $section['notes'] = array_map(fn($note) => ['text' => $note], $section['notes']);
            $sections[] = $section;
        }
        return [
            'passed' => $this->passed(),
            'results' => $this->results,
            'warnings' => array_map(fn($warning) => ['text' => $warning], $this->warnings),
            'sections' => $sections,
        ];
    }

    /**
     * Makes a curl object for a request.
     *
     * @return \curl The curl object.
     */
    protected function new_curl(): \curl {
        return new \curl();
    }

    /**
     * Gives the IP addresses of a host.
     *
     * @param string $host The host name.
     * @return string[]|false The IP addresses, or false when the name does not resolve.
     */
    protected function resolve_host(string $host) {
        return gethostbynamel($host);
    }

    /**
     * Records the environment of this site.
     */
    private function check_environment(): void {
        global $CFG;

        $this->add_section(get_string('check_section_environment', 'mod_goone'));
        $this->add_line(get_string('check_site', 'mod_goone'), $CFG->wwwroot);
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_geteuid());
            $this->add_line(get_string('check_osuser', 'mod_goone'), $user['name'] ?? (string) posix_geteuid());
        }
        $this->add_line(get_string('check_phpversion', 'mod_goone'), PHP_VERSION);
        $curlversion = curl_version();
        $this->add_line(get_string('check_curlversion', 'mod_goone'), $curlversion['version'] . ', ' . $curlversion['ssl_version']);
    }

    /**
     * Records the plugin settings, without the values of the credentials and the token.
     */
    private function check_settings(): void {
        $config = get_config('mod_goone');
        $tokentime = (int) ($config->token_time ?? 0);

        $this->add_section(get_string('check_section_settings', 'mod_goone'));
        $credentials = [
            get_string('clientid', 'mod_goone') => (string) ($config->client_id ?? ''),
            get_string('clientsecret', 'mod_goone') => (string) ($config->client_secret ?? ''),
        ];
        foreach ($credentials as $name => $value) {
            $this->add_line($name, $this->describe_credential($value));
            // The token request is still sent, because it also tests the network path to auth.go1.com.
            if ($value === '') {
                $this->warnings[] = get_string('check_warning_notset', 'mod_goone', $name);
            }
        }
        $this->add_line(get_string('check_storedtoken', 'mod_goone'),
            get_string(empty($config->token) ? 'check_none' : 'check_set', 'mod_goone'));
        if ($tokentime) {
            $tokenage = time() - $tokentime;
            $this->add_line(get_string('check_storedtokenage', 'mod_goone'),
                format_time($tokenage) . ' (' . userdate($tokentime) . ')');
            if ($tokenage < self::TOKEN_MAX_AGE) {
                $this->add_note(get_string('check_note_youngtoken', 'mod_goone'));
            }
        } else {
            $this->add_line(get_string('check_storedtokenage', 'mod_goone'), get_string('check_none', 'mod_goone'));
        }
    }

    /**
     * Records the Moodle settings that can stop the requests.
     */
    private function check_http_settings(): void {
        global $CFG;

        $this->add_section(get_string('check_section_http', 'mod_goone'));
        $this->add_line(get_string('check_proxy', 'mod_goone'), empty($CFG->proxyhost)
            ? get_string('check_none', 'mod_goone')
            : $CFG->proxyhost . ':' . ($CFG->proxyport ?? ''));
        if (!empty($CFG->proxyhost)) {
            $this->add_line(get_string('check_proxybypass', 'mod_goone'), (string) ($CFG->proxybypass ?? ''));
        }
        $securityhelper = new curl_security_helper();
        foreach ($this->urls() as $host => $url) {
            $this->add_line(get_string('check_blocked', 'mod_goone', $host),
                get_string($securityhelper->url_is_blocked($url) ? 'check_blockedyes' : 'check_no', 'mod_goone'));
        }
    }

    /**
     * Records the IP addresses of the GO1 hosts.
     */
    private function check_dns(): void {
        $this->add_section(get_string('check_section_dns', 'mod_goone'));
        foreach (array_keys($this->urls()) as $host) {
            $addresses = $this->resolve_host($host);
            $this->add_line($host, $addresses ? implode(', ', $addresses) : get_string('check_notresolved', 'mod_goone'));
        }
    }

    /**
     * Validates the stored token, as goone_tokentest() does. A token that is not valid is only a warning,
     * because the plugin then requests a new token.
     */
    private function validate_stored_token(): void {
        $token = (string) get_config('mod_goone', 'token');
        if ($token === '') {
            return;
        }

        $this->add_section(get_string('check_section_validate', 'mod_goone', 'GET ' . self::VALIDATE_URL));
        $curl = $this->new_curl();
        $curl->setHeader(['Authorization: Bearer ' . $token]);
        $body = $curl->get(self::VALIDATE_URL);
        $this->add_response($curl, $body, true);
        if ($this->http_code($curl) != 200) {
            $this->warnings[] = get_string('check_warning_storedtoken', 'mod_goone');
        }
    }

    /**
     * Requests a new token, as goone_generatetoken() does, but does not store it.
     *
     * @return string The new token, or an empty string when GO1 does not give one.
     */
    private function request_token(): string {
        $config = get_config('mod_goone');

        $this->add_section(get_string('check_section_token', 'mod_goone', 'POST ' . self::TOKEN_URL));
        $curl = $this->new_curl();
        $body = $curl->post(self::TOKEN_URL, [
            'client_id' => $config->client_id ?? '',
            'client_secret' => $config->client_secret ?? '',
            'grant_type' => 'client_credentials',
        ]);
        $this->add_response($curl, $body, true);
        $response = json_decode((string) $body, true);
        $token = ($this->http_code($curl) == 200) ? (string) ($response['access_token'] ?? '') : '';
        $this->add_result(get_string('check_result_token', 'mod_goone'), $token !== '');
        return $token;
    }

    /**
     * Gets the learning objects from the GO1 API, as the content browsers do.
     *
     * @param string $token The token.
     */
    private function check_api(string $token): void {
        $this->add_section(get_string('check_section_api', 'mod_goone', 'GET ' . self::API_URL . '?limit=0'));
        $curl = $this->new_curl();
        $curl->setHeader(['Authorization: Bearer ' . $token]);
        $body = $curl->get(self::API_URL, ['limit' => 0]);
        $passed = ($this->http_code($curl) == 200);
        $this->add_response($curl, $body, !$passed);
        if ($passed) {
            $response = json_decode((string) $body, true);
            $this->add_line(get_string('check_total', 'mod_goone'),
                (string) ($response['total'] ?? get_string('check_unknown', 'mod_goone')));
        }
        $this->add_result(get_string('check_result_api', 'mod_goone'), $passed);
    }

    /**
     * Gets the webhooks from the GO1 gateway, as mod_goone_check_webhook() does. The webhook list can
     * contain the signing secrets of the webhooks, thus the check records only a summary of it.
     *
     * @param string $token The token.
     */
    private function check_webhooks(string $token): void {
        global $CFG;

        $this->add_section(get_string('check_section_webhooks', 'mod_goone', 'GET ' . self::WEBHOOKS_URL));
        $curl = $this->new_curl();
        $curl->setHeader([
            'Cache-Control: no-cache',
            'api-version: 2022-07-01',
            'Authorization: Bearer ' . $token,
        ]);
        $body = $curl->get(self::WEBHOOKS_URL);
        $response = json_decode((string) $body, true);
        $passed = ($this->http_code($curl) == 200) && isset($response['hits']) && is_array($response['hits']);
        $this->add_response($curl, $body, !$passed);
        if ($passed) {
            $payloadurl = $CFG->wwwroot . '/mod/goone/payload.php';
            $ours = array_filter($response['hits'], fn($webhook) => ($webhook['url'] ?? '') === $payloadurl);
            $this->add_line(get_string('check_webhookcount', 'mod_goone'), (string) count($response['hits']));
            $this->add_line(get_string('check_webhookforsite', 'mod_goone'), $ours
                ? get_string('check_yes', 'mod_goone')
                : get_string('check_nowebhook', 'mod_goone', $payloadurl));
            if (!$ours) {
                $this->warnings[] = get_string('check_warning_nowebhook', 'mod_goone');
            }
        }
        $this->add_result(get_string('check_result_webhooks', 'mod_goone'), $passed);
    }

    /**
     * Gets a SCORM player script. The browsers of the learners load these scripts, not this server,
     * thus a failure is only a warning.
     */
    private function check_player(): void {
        $this->add_section(get_string('check_section_player', 'mod_goone', 'GET ' . self::PLAYER_URL));
        $curl = $this->new_curl();
        $body = $curl->get(self::PLAYER_URL);
        $this->add_response($curl, $body, false);
        if ($this->http_code($curl) != 200) {
            $this->warnings[] = get_string('check_warning_player', 'mod_goone');
        }
    }

    /**
     * Gives the URLs that the check uses to test each GO1 host.
     *
     * @return string[] The host => the URL.
     */
    private function urls(): array {
        $urls = [];
        foreach ([self::TOKEN_URL, self::API_URL, self::WEBHOOKS_URL, self::PLAYER_URL] as $url) {
            $urls[parse_url($url, PHP_URL_HOST)] = $url;
        }
        return $urls;
    }

    /**
     * Describes a credential setting without its value.
     *
     * @param string $value The setting value.
     * @return string The description.
     */
    private function describe_credential(string $value): string {
        if ($value === '') {
            return get_string('check_notset', 'mod_goone');
        }
        $identifier = ($value === trim($value)) ? 'check_length' : 'check_lengthwhitespace';
        return get_string($identifier, 'mod_goone', strlen($value));
    }

    /**
     * Records the result of a curl request.
     *
     * @param \curl $curl The curl object after the request.
     * @param string|bool $body The response body.
     * @param bool $showbody True to record the response body without secrets. False to not record it.
     */
    private function add_response(\curl $curl, $body, bool $showbody): void {
        $info = $curl->get_info() ?? [];
        $unknown = get_string('check_unknown', 'mod_goone');
        $this->add_line(get_string('check_httpstatus', 'mod_goone'), (string) ($info['http_code'] ?? $unknown));
        $this->add_line(get_string('check_serverip', 'mod_goone'), (string) ($info['primary_ip'] ?? $unknown));
        $this->add_line(get_string('check_totaltime', 'mod_goone'), (string) ($info['total_time'] ?? $unknown));
        $this->add_line(get_string('check_curlerrno', 'mod_goone'), (string) (int) $curl->get_errno());
        $this->add_line(get_string('check_curlerror', 'mod_goone'),
            empty($curl->error) ? get_string('check_none', 'mod_goone') : (string) $curl->error);
        if (!$showbody) {
            return;
        }

        $json = json_decode((string) $body, true);
        if (is_array($json)) {
            $body = json_encode($this->remove_secrets($json));
        }
        $this->add_line(get_string('check_responsebody', 'mod_goone'), \core_text::substr((string) $body, 0, 500));
    }

    /**
     * Replaces the values of all fields that have "token" or "secret" in the name, at all levels.
     *
     * @param array $data The decoded response.
     * @return array The response without secrets.
     */
    private function remove_secrets(array $data): array {
        foreach ($data as $key => $value) {
            if (preg_match('/token|secret/i', (string) $key)) {
                $data[$key] = '[REMOVED]';
            } else if (is_array($value)) {
                $data[$key] = $this->remove_secrets($value);
            }
        }
        return $data;
    }

    /**
     * Gives the HTTP status of the last request of a curl object.
     *
     * @param \curl $curl The curl object after the request.
     * @return int The HTTP status, or 0 when there is no response.
     */
    private function http_code(\curl $curl): int {
        return (int) ($curl->get_info()['http_code'] ?? 0);
    }

    /**
     * Starts a new section.
     *
     * @param string $title The title.
     */
    private function add_section(string $title): void {
        $this->sections[] = ['title' => $title, 'lines' => [], 'notes' => []];
    }

    /**
     * Adds a line to the current section.
     *
     * @param string $label The label.
     * @param string $value The value.
     */
    private function add_line(string $label, string $value): void {
        $this->sections[array_key_last($this->sections)]['lines'][] = ['label' => $label, 'value' => $value];
    }

    /**
     * Adds a note to the current section.
     *
     * @param string $note The note.
     */
    private function add_note(string $note): void {
        $this->sections[array_key_last($this->sections)]['notes'][] = $note;
    }

    /**
     * Records the result of a server host check.
     *
     * @param string $name The name of the check.
     * @param bool $passed True if the check passed.
     */
    private function add_result(string $name, bool $passed): void {
        $this->results[] = ['name' => $name, 'passed' => $passed];
    }
}
