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
 * External goone API
 *
 * @package   mod_goone
 * @copyright 2020, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/goone/lib.php");

/**
 * mod_goone functions
 */
class mod_goone_external extends external_api {

    /**
     * Describes the parameters for get_hits
     * @return external_function_parameters
     */
    public static function get_hits($type, $tag, $language, $provider, $keyword, $sort, $offset) {
        $result = goone_get_hits($type, $tag, $language, $provider, $keyword, $sort, $offset);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the get_hits return value
     * @return external_single_structure
     */
    public static function get_hits_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for get_hits
     * @return external_function_parameters
     */
    public static function get_hits_parameters() {
        return new external_function_parameters([
                'type' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'type', VALUE_OPTIONAL), ''),
                'tag' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'tag', VALUE_OPTIONAL), ''),
                'language' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'language', VALUE_OPTIONAL), ''),
                'provider' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'provider', VALUE_OPTIONAL), ''),
                'keyword' => new external_value(PARAM_TEXT, 'keyword', VALUE_OPTIONAL, ''),
                'sort' => new external_value(PARAM_TEXT, 'sort', VALUE_OPTIONAL, ''),
                'offset' => new external_value(PARAM_INT, 'offset', VALUE_DEFAULT, 0),
        ]);
    }
    /**
     * Describes the parameters for get_modal
     * @return external_function_parameters
     */
    public static function get_modal($loid) {
        $result = goone_modal_overview($loid);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the get_modal return value
     * @return external_single_structure
     */
    public static function get_modal_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for get_modal
     * @return external_function_parameters
     */
    public static function get_modal_parameters() {
        return new external_function_parameters([
                'loid' => new external_value(PARAM_INT,
                    'loid', VALUE_DEFAULT, 0)
        ]);
    }


}