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
 * Process completion payload set by GO1
 *
 * @package     mod_goone
 * @copyright   2022 Esteban Echavarria <esteban.echavarria@openlms.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Kirill Astashov <kirill.astashov@androgogic.com>
 */
$ispost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
// A webhook request from GO1 is a POST without the parameters of the other actions. It needs no
// session, and NO_MOODLE_COOKIES must be defined before config.php starts the session.
$iswebhook = $ispost && empty($_GET['logs_view']) && empty($_POST['logs_view'])
    && empty($_GET['create_webhook']) && empty($_POST['create_webhook']);
if ($iswebhook) {
    define('NO_MOODLE_COOKIES', true);
}

require_once("../../config.php");
require_once($CFG->dirroot."/mod/goone/lib.php");

$viewlogs = optional_param('logs_view', 0, PARAM_INT);
$createwebhook = optional_param('create_webhook', 0, PARAM_INT);

if ($iswebhook) {
    mod_goone_api_payload_process_post();
} else if ($ispost && $createwebhook == 1) {
    // The button on the settings page sends this request. It gives back JSON, not a page.
    mod_goone_create_webhook_ajax();
} else if (!$ispost) {
    if ($viewlogs == 1 && $createwebhook == 0) {
        mod_goone_view_api_payload_logs();
    } else if ($viewlogs == 0 && $createwebhook == 1) {
        // The webhook is made by a POST from the button on the settings page. A GET
        // makes no webhook: it only sends the user to the settings page.
        redirect(new moodle_url('/admin/settings.php', ['section' => 'modsettinggoone']));
    } else if ($viewlogs == 0 && $createwebhook == 0) {
        redirect($CFG->wwwroot);
    }
}
