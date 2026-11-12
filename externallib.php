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
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/goone/lib.php");

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

/**
 * The external functions of the GO1 content browser and the bulk content browser.
 */
class mod_goone_external extends external_api {

    /**
     * Gives the GO1 search results for the content browser.
     *
     * @param string[] $type The content types.
     * @param string[] $tag The tags.
     * @param string[] $language The languages.
     * @param string[] $provider The providers.
     * @param string $keyword The keyword.
     * @param string $sort The sort order.
     * @param int $offset The position of the first result.
     * @param int $contextid The context of the course or the module that the activity is added to.
     * @return array The HTML of the results in 'result', and the warnings.
     */
    public static function get_hits($type, $tag, $language, $provider, $keyword, $sort, $offset, $contextid) {
        $context = context::instance_by_id($contextid);
        self::validate_context($context);
        require_capability('mod/goone:addinstance', $context);
        // goone_get_hits() makes every value safe for the address of the request.
        // The keyword goes to it as the user wrote it.
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
                'contextid' => new external_value(PARAM_INT,
                    'Context id of the course or module the activity is added to', VALUE_REQUIRED),
        ]);
    }
    /**
     * Gives the details of a GO1 learning object for the content browser.
     *
     * @param int $loid The ID of the GO1 learning object.
     * @param int $contextid The context of the course or the module that the activity is added to.
     * @return array The learning object as JSON in 'result', and the warnings.
     */
    public static function get_modal($loid, $contextid) {
        $context = context::instance_by_id($contextid);
        self::validate_context($context);
        require_capability('mod/goone:addinstance', $context);
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
                    'loid', VALUE_DEFAULT, 0),
                'contextid' => new external_value(PARAM_INT,
                    'Context id of the course or module the activity is added to', VALUE_REQUIRED)
        ]);
    }

    /**
     * Gives the GO1 search results for the bulk content browser.
     *
     * @param string[] $type The content types.
     * @param string[] $topic The topics.
     * @param string[] $language The languages.
     * @param string[] $provider The providers.
     * @param string $keyword The keyword.
     * @param string $sort The sort order.
     * @param int $offset The position of the first result.
     * @param bool $loadmore True when the results go below the results that the page already shows.
     * @param string[] $duration The duration options.
     * @return array The HTML of the results in 'result', and the warnings.
     */
    public static function bulk_goone_get_hits($type, $topic, $language, $provider, $keyword, $sort, $offset, $loadmore, $duration) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
        $result = bulk_mod_goone_get_hits($type, $topic, $language, $provider, $keyword, $sort, $offset, $loadmore, $duration);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the bulk_goone_get_hits return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_hits_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for bulk_goone_get_hits
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
     * Gives the details of a GO1 learning object for the bulk content browser.
     *
     * @param int $loid The ID of the GO1 learning object.
     * @return array The learning object as JSON in 'result', and the warnings.
     */
    public static function bulk_goone_get_modal($loid) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
        $result = bulk_mod_goone_modal_overview($loid);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the bulk_goone_get_modal return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_modal_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }    

    /**
     * Describes the parameters for bulk_goone_get_modal
     * @return external_function_parameters
     */
    public static function bulk_goone_get_modal_parameters() {
        return new external_function_parameters([
                'loid' => new external_value(PARAM_INT,
                    'loid', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Describes the parameters for bulk_goone_get_course_search_result
     * @return external_function_parameters
     */
    public static function bulk_goone_get_course_search_result_parameters() {
        return new external_function_parameters([
                'search' => new external_value(PARAM_TEXT,
                    'search', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Finds the courses whose short name or full name holds a text, for the bulk content browser.
     *
     * @param string $search The text to find.
     * @return array The courses as JSON in 'result', and the warnings.
     */
    public static function bulk_goone_get_course_search_result($search) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
        $result = bulk_mod_goone_modal_search_course_result($search);
        return [
            'result' => $result,
            'warnings' => [],
        ];
    }

    /**
     * Describes the bulk_goone_get_course_search_result return value
     * @return external_single_structure
     */
    public static function bulk_goone_get_course_search_result_returns() {
        return new external_single_structure([
                'result' => new external_value(PARAM_RAW, 'JSON response'),
                'warnings' => new external_warnings()
        ]);
    }



    /**
     * Creates one course for each selected learning object.
     *
     * @param string $items The IDs of the selected learning objects, separated by commas.
     * @return array The HTML links to the courses in 'result', and the warnings.
     */
    public static function bulk_goone_process_course_per_item($items) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
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

    /**
     * Creates one course with an activity for each selected learning object.
     *
     * @param string $items The IDs of the selected learning objects, separated by commas.
     * @param string $coursename The name of the new course.
     * @return array The HTML links to the course in 'result', and the warnings.
     */
    public static function bulk_goone_process_course_single_course($items, $coursename) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
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


    /**
     * Adds an activity for each selected learning object to a section of an existing course.
     *
     * @param string $course_section The course ID and the section number, separated by '-'.
     * @param string $selecteditemsids The IDs of the selected learning objects, separated by commas.
     * @return array The HTML links to the course in 'result', and the warnings.
     */
    public static function bulk_goone_process_add_to_existing_course($course_section, $selecteditemsids) {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/goone:viewbulkcontent', $context);
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


