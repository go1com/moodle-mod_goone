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
 * Checks the connection from this site to the GO1 hosts that the plugin uses.
 *
 * The script runs the same checks as the page checkconnection.php. See \mod_goone\local\connection_check.
 *
 * @package   mod_goone
 * @copyright 2026 Androgogic Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(['help' => false], ['h' => 'help']);

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)), 2);
}

if ($options['help']) {
    echo "Checks the connection from this site to the GO1 hosts that the plugin uses:

  auth.go1.com     Tokens. The admin settings page uses this host to test the credentials.
  api.go1.com      Learning objects: content browser, bulk creation, SCORM download.
  gateway.go1.com  Webhooks.
  api.go1.co       SCORM player scripts. The browsers of the learners load these scripts,
                   thus a failure from this server is only a warning.

The script uses the GO1 client ID and client secret from the plugin settings. It sends only
read-only requests. It does not change the configuration, and it does not show the client
secret, the tokens or other secrets.

Run the script as the web server user. The network access of a different user can be different.
The page 'Check connection' on the GO1 settings page runs the same checks as the web server user.

Options:
-h, --help    Show this help.

Example:
\$ sudo -u www-data /usr/bin/php mod/goone/cli/check_connection.php

Exit codes:
0 - All the checks of the server hosts passed.
1 - One or more checks of the server hosts failed.
";
    exit(0);
}

$check = new \mod_goone\local\connection_check();
$check->run();

foreach ($check->get_sections() as $section) {
    cli_writeln('');
    cli_writeln('== ' . $section['title'] . ' ==');
    foreach ($section['lines'] as $line) {
        cli_writeln(str_pad($line['label'] . ':', 30) . $line['value']);
    }
    foreach ($section['notes'] as $note) {
        cli_writeln($note);
    }
}

cli_writeln('');
cli_writeln('== ' . get_string('check_results', 'mod_goone') . ' ==');
foreach ($check->get_results() as $result) {
    cli_writeln(str_pad($result['name'] . ':', 30) . get_string($result['passed'] ? 'check_ok' : 'check_failed', 'mod_goone'));
}
foreach ($check->get_warnings() as $warning) {
    cli_writeln(get_string('warning') . ': ' . $warning);
}

if (!$check->passed()) {
    cli_writeln(get_string('check_failedsummary', 'mod_goone'));
    exit(1);
}
cli_writeln(get_string('check_passed', 'mod_goone'));
exit(0);
