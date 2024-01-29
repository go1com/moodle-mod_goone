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
 */
require_once("../../config.php");
require_once($CFG->dirroot."/mod/goone/lib.php");
global $CFG;

$viewlogs = optional_param('logs_view', 0, PARAM_INT);
$create_webhook = optional_param('create_webhook', 0, PARAM_INT);

if($_SERVER['REQUEST_METHOD'] !== 'POST' && $viewlogs == 1 && $create_webhook == 0) {
	mod_goone_view_api_payload_logs();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $viewlogs == 0 && $create_webhook == 0 ) {
	mod_goone_api_payload_process_post();
} 
if ($viewlogs == 0 && $create_webhook == 1 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
	mod_goone_create_webhook_api_request();
}
if($_SERVER['REQUEST_METHOD'] !== 'POST' && $viewlogs == 0 && $create_webhook == 0){
	redirect($CFG->wwwroot);
}