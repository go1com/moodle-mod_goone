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
 * Privacy API
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD based on work by 2019 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

namespace mod_goone\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the goone activity module.
 *
 * @copyright  2019 eCreators PTY LTD based on work by 2019 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'goone_completion',
            [
                'gooneid' => 'privacy:metadata:goone_completion:gooneid',
                'userid' => 'privacy:metadata:goone_completion:userid',
                'location' => 'privacy:metadata:goone_completion:location',
                'completed' => 'privacy:metadata:goone_completion:completed',
                'timemodified' => 'privacy:metadata:goone_completion:timemodified',
            ],
            'privacy:metadata:goone_completion'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {goone} g ON g.id = cm.instance
            INNER JOIN {goone_completion} gm ON gm.gooneid = g.id
                 WHERE gm.userid = :userid";

        $params = [
            'modname'       => 'goone',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all choice answers.
        $sql = "SELECT gm.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {goone} g ON g.id = cm.instance
                  JOIN {goone_completion} gm ON gm.gooneid = g.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'goone',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       g.loid as loid,
                       gc.completed as completed,
                       gc.timemodified
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {goone} g ON g.id = cm.instance
            INNER JOIN {goone_completion} gc ON gc.gooneid = g.id
                 WHERE c.id {$contextsql}
                       AND gc.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'goone', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Reference to the goone activity seen in the last iteration of the loop. By comparing this with the current record, and
        // because we know the results are ordered, we know when we've moved to the answers for a new goone activity and therefore
        // when we can export the complete data for the last activity.
        $lastcmid = null;

        $goonecompletions = $DB->get_recordset_sql($sql, $params);

        foreach ($goonecompletions as $goonecompletion) {
            // If we've moved to a new completion record, then write the last completion record data
            // and reinit the completion record data array.
            if ($lastcmid != $goonecompletion->cmid) {
                if (!empty($goonedata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_goone_data_for_user($goonedata, $context, $user);
                }
                $goonedata = [
                    'loid' => [],
                    'completed' => [],
                    'timemodified' => \core_privacy\local\request\transform::datetime($goonecompletion->timemodified),
                ];
            }
            $goonedata['loid'][] = $goonecompletion->loid;
            $goonedata['completed'][] = $goonecompletion->completed;
            $lastcmid = $goonecompletion->cmid;
        }

        $goonecompletions->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($goonedata)) {
            $context = \context_module::instance($lastcmid);
            self::export_goone_data_for_user($goonedata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single goone activity, along with any generic data or area files.
     *
     * @param array $goonedata the personal data to export for the goone activity.
     * @param \context_module $context the context of the goone activity.
     * @param \stdClass $user the user record
     */
    protected static function export_goone_data_for_user(array $goonedata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the goone activity.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with goone activty data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $goonedata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('goone', $context->instanceid)) {
            $DB->delete_records('goone_completion', ['gooneid' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }
            $DB->delete_records('goone_completion', ['gooneid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('goone', $context->instanceid);

        if (!$cm) {
            // Only goone module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "gooneid = :gooneid AND userid $usersql";
        $params = ['gooneid' => $cm->instance] + $userparams;
        $DB->delete_records_select('goone_completion', $select, $params);
    }
}
