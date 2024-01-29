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
 * Service definitions
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_goone_get_hits' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'get_hits',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Retreive hits from GO1 API',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_goone_get_modal' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'get_modal',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Retreive learning object overview from GO1 API',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_get_hits' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_get_hits',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Retreive hits from GO1 API',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_get_modal' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_get_modal',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Retreive learning object overview from GO1 API',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_get_course_search_result' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_get_course_search_result',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Retreive search form',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_process_course_per_item' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_process_course_per_item',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Process course per item',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_process_course_single_course' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_process_course_single_course',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Process items as single course',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'bulk_mod_goone_process_add_to_existing_course' => [
        'classname'     => 'mod_goone_external',
        'methodname'    => 'bulk_goone_process_add_to_existing_course',
        'classpath'     => 'mod/goone/externallib.php',
        'description'   => 'Add items to existing course',
        'type'          => 'read',
        'capabilities'  => 'mod/goone:addinstance',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
