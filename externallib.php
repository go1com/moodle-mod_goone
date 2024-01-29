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
     * @param array $type
     * @param array $topic
     * @param array $language
     * @param array $provider
     * @param string $keyword
     * @param string $sort
     * @param int $offset
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
                    new external_value(PARAM_TEXT, 'type', VALUE_REQUIRED), ''),
                'tag' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'tag', VALUE_REQUIRED), ''),
                'language' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'language', VALUE_REQUIRED), ''),
                'provider' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'provider', VALUE_REQUIRED), ''),
                'keyword' => new external_value(PARAM_TEXT, 'keyword', VALUE_REQUIRED, ''),
                'sort' => new external_value(PARAM_TEXT, 'sort', VALUE_REQUIRED, ''),
                'offset' => new external_value(PARAM_INT, 'offset', VALUE_DEFAULT, 0),
        ]);
    }
    /**
     * Describes the parameters for get_modal
     * @param string $loid
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

    /**
     * Describes the parameters for get_hits
     * @param array $type
     * @param array $topic
     * @param array $language
     * @param array $provider
     * @param string $keyword
     * @param string $sort
     * @param int $offset
     * @param bool $loadmore
     * @param int $duration
     * @return external_function_parameters
     */
    public static function bulk_goone_get_hits($type, $topic, $language, $provider, $keyword, $sort, $offset, $loadmore, $duration) {
        $result = bulk_mod_goone_get_hits($type, $topic, $language, $provider, $keyword, $sort, $offset, $loadmore, $duration);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the get_hits return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_hits_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for get_hits
     * @return external_function_parameters
     */
    public static function bulk_goone_get_hits_parameters() {
        return new external_function_parameters([
                'type' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'type', VALUE_REQUIRED), ''),
                'topic' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'topic', VALUE_REQUIRED), ''),
                'language' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'language', VALUE_REQUIRED), ''),
                'provider' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'provider', VALUE_REQUIRED), ''),
                'keyword' => new external_value(PARAM_TEXT, 'keyword', VALUE_REQUIRED, ''),
                'sort' => new external_value(PARAM_TEXT, 'sort', VALUE_REQUIRED, ''),
                'offset' => new external_value(PARAM_INT, 'offset', VALUE_DEFAULT, 0),
                'loadmore' => new external_value(PARAM_BOOL, 'loadmore', VALUE_DEFAULT, false),
                'duration' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'duration', VALUE_REQUIRED), ''),
        ]);
    }
    /**
     * Describes the parameters for get_modal
     * @param string $loid
     * @return external_function_parameters
     */
    public static function bulk_goone_get_modal($loid) {
        $result = bulk_mod_goone_modal_overview($loid);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the get_modal return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_modal_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }    

    /**
     * Describes the parameters for get_modal
     * @return external_function_parameters
     */
    public static function bulk_goone_get_modal_parameters() {
        return new external_function_parameters([
                'loid' => new external_value(PARAM_INT,
                    'loid', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Describes the parameters for get_modal
     * @return external_function_parameters
     */
    public static function bulk_goone_get_course_search_result_parameters() {
        return new external_function_parameters([
                'search' => new external_value(PARAM_TEXT,
                    'search', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Describes the parameters for get_modal
     * @param int $loid
     * @return external_function_parameters
     */
    public static function bulk_goone_get_course_search_result($search) {
        $result = bulk_mod_goone_modal_search_course_result($search);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the get_modal return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_course_search_result_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }



    public static function bulk_goone_process_course_per_item($items) {
        $result = bulk_mod_goone_process_course_per_item($items);   
        return [
            'result' => $result,
            'warnings' => [],
        ];  
    }
    /**
     * Describes the parameters for process_course_per_item
     * @return external_function_parameters
     */
    public static function bulk_goone_process_course_per_item_parameters() {
        return new external_function_parameters([
                'items' => new external_value(PARAM_TEXT,
                    'items', VALUE_DEFAULT, '')
        ]);
    }
    /**
     * Describes the process_course_per_item return value
     * @return external_single_structure
     */
    public static function bulk_goone_process_course_per_item_returns() {
            return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'String with response'),
                'warnings' => new external_warnings()
        ]);
    }

    public static function bulk_goone_process_course_single_course($items, $coursename) {
        $result = bulk_mod_goone_process_course_single_course($items, $coursename);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the parameters for process_course_single_course
     * @return external_function_parameters
     */
    public static function bulk_goone_process_course_single_course_parameters() {
        return new external_function_parameters([
            'items' => new external_value(PARAM_TEXT,
                    'items', VALUE_DEFAULT, ''), 
            'coursename'=> new external_value(PARAM_TEXT,
                'coursename', VALUE_DEFAULT, '')
        ]);
    }
    /**
     * Describes the process_course_single_course return value
     * @return external_single_structure
     */
    public static function bulk_goone_process_course_single_course_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'String with response'),
                'warnings' => new external_warnings()
        ]);
    }


    public static function bulk_goone_process_add_to_existing_course($course_section, $selecteditemsids) {
        $result = bulk_mod_goone_process_add_to_existing_course($course_section, $selecteditemsids);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the parameters for process_add_to_existing_course
     * @return external_function_parameters
     */
    public static function bulk_goone_process_add_to_existing_course_parameters() {
        return new external_function_parameters([
            'course_section' => new external_value(PARAM_TEXT,
                'course_section', VALUE_DEFAULT, ''), 
            'selecteditemsids'=> new external_value(PARAM_TEXT,
                'selecteditemsids', VALUE_DEFAULT, '')
        ]);
    }
    /**
     * Describes the process_add_to_existing_course return value
     * @return external_single_structure
     */
    public static function bulk_goone_process_add_to_existing_course_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'String with response'),
                'warnings' => new external_warnings()
        ]);
    }


}


