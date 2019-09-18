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
 * Retrieves and renders GO1 search result items
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');
require_login();

$config = get_config('mod_goone');

$mode = required_param('mode', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$keyword = optional_param('keyword', '', PARAM_TEXT);
$provider = optional_param('provider', '', PARAM_TEXT);
$language = optional_param('language', '', PARAM_TEXT);
$tag = optional_param('tag', '', PARAM_TEXT);
$price = optional_param('price', '', PARAM_TEXT);
$type = optional_param('type', '', PARAM_TEXT);
$sub = optional_param('subscribed', '', PARAM_TEXT);
$sort = optional_param('sort', '', PARAM_TEXT);
$offset = optional_param('offset', '', PARAM_INT);

goone_check_capabilities($mode, $id);

$params = array (
    'keyword' => $keyword,
    'price%5Bmax%5D' => $price,
    'type' => $type,
    'offset' => $offset,
    'sort' => $sort,
    'providers' => $provider);

$response = goone_get_hits($params, $language, $tag);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
echo $OUTPUT->render_from_template('mod_goone/hits', $response);

