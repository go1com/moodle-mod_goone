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

namespace mod_goone\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/goone/lib.php');

/**
 * A schedule task for goone cron.
 *
 * @package   mod_goone
 * @copyright 2024 Esteban Echavarria (esteban.echavarria@openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_mark_completion extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_goone');
    }

    /**
     * Run assignment cron.
     */
    public function execute() {
        global $CFG, $DB;

        //Local internal completion checks.
        try {
            // Get comletions from activity table.
            $goonecompletions = $DB->get_records('goone_completion', ['completed'=>2]);

            foreach ($goonecompletions as $gcid => $gcrecord) {
                //Get moodle course module completion record.
                $module = $DB->get_record('modules', ['name'=>'goone']);
                    $gooneactivity = $DB->get_record('goone', ['id'=>$gcrecord->gooneid]);
                    $cm = $DB->get_record('course_modules', ['module'=>$module->id,
                        'course'=>$gooneactivity->course, 'instance'=>$gcrecord->instance]);
                $lmscompletion = $DB->get_record('course_modules_completion',
                    ['coursemoduleid'=>$cm->id, 'userid'=>$gcrecord->userid]);
                if (!empty($lmscompletion)) {
                    //If record completed in activity tables and not in
                    //course module completion then mark it as complete in moodle.
                    if(isset($lmscompletion->completionstate) && $lmscompletion->completionstate <> 1){
                        $lmscompletion->completionstate = 1;
                        if (isset($gcrecord->timemodified) && $gcrecord->timemodified > 0) {
                            //Set timemodified from goone completion table.
                            $lmscompletion->timemodified = $gcrecord->timemodified;
                        } else {
                            //Set timemodified as current timestamp as correct timemodified date not found.
                            $lmscompletion->timemodified = time();
                        }
                        $DB->update_record('course_modules_completion', $lmscompletion);
                        goone_set_completion($cm, $gcrecord->userid, '', "completed");
                    }

                    //Check viewed and mark it.
                    $lmsviewed = $DB->get_record('course_modules_viewed',
                    ['coursemoduleid'=>$cm->id, 'userid'=>$user->id]);
                    if (empty($lmsviewed)) {
                        $newviewed = new \stdClass();
                        if (isset($gcrecord->timemodified) && $gcrecord->timemodified > 0) {
                            //Set timemodified from goone completion table.
                            $newviewed->timecreated = $gcrecord->timemodified;
                        } else {
                            //Set timemodified as current timestamp as correct timemodified date not found.
                            $newviewed->timecreated = time();
                        }
                        $newviewed->timecreated = time();
                        $newviewed->coursemoduleid = $cm->id;
                        $newviewed->userid = $user->id;
                        $DB->insert_record('course_modules_viewed', $newviewed);
                    }

                } else {
                    //Check viewed and mark it.
                    $lmsviewed = $DB->get_record('course_modules_viewed',
                    ['coursemoduleid'=>$cm->id, 'userid'=>$gcrecord->userid]);
                    if (empty($lmsviewed)) {
                        $newviewed = new \stdClass();
                        if (isset($gcrecord->timemodified) && $gcrecord->timemodified > 0) {
                            //Set timemodified from goone completion table.
                            $lmsviewed->timecreated = $gcrecord->timemodified;
                        } else {
                            //Set timemodified as current timestamp as correct timemodified date not found.
                            $lmsviewed->timecreated = time();
                        }
                        $newviewed->timecreated = time();
                        $newviewed->coursemoduleid = $cm->id;
                        $newviewed->userid = $gcrecord->userid;
                        $DB->insert_record('course_modules_viewed', $lmsviewed);
                        goone_set_completion($cm, $gcrecord->userid, '', "completed");
                    }

                    // Insert the course completion record.
                    $module = $DB->get_record('modules', ['name'=>'goone']);
                    $gooneactivity = $DB->get_record('goone', ['id'=>$gcrecord->gooneid]);
                    $cm = $DB->get_record('course_modules', ['module'=>$module->id,
                        'course'=>$gooneactivity->course, 'instance'=>$gcrecord->instance]);
                    $newcmc = new \stdClass();
                    $newcmc->coursemoduleid = $cm->id;
                    $newcmc->userid = $gcrecord->userid;
                    $newcmc->completionstate = 1;
                    $newcmc->timemodified = $gcrecord->timemodified;
                    $DB->insert_record('course_modules_completion', $lmscompletion);
                }
            }
            $status = true;
        } catch (moodle_exception $e) {
            $status = false;
        }

        //API completion checks
        try {
            //Get enrolments for all the user in all goone lo_ids.
            $gooneactivities  = $DB->get_records('goone');
            if (empty($gooneactivities)) {
                return true;
            }
            $module  = $DB->get_record('modules', ['name'=>'goone']);
            foreach ($gooneactivities as $ids => $goone) {
                //Get enrolments completed from goone.
                $goonecompletedenrollments = bulk_mod_goone_api_custom_api_request("enrollments" , "", ['lo_id'=>$goone->loid, 'status'=>'completed', '']);
                //Get course module.
                $cm = $DB->get_record('course_modules', ['course'=>$goone->course,'module'=>$module->id,'instance'=>$goone->id]);
                foreach ($goonecompletedenrollments['hits'] as $index => $hit) {
                    //Get user details from goone.
                    $enrolleduserinfo = bulk_mod_goone_api_custom_api_request('users', $hit['user_id']);
                    if (!$user = $DB->get_record('user', array('email'=>$enrolleduserinfo['email']))) {
                        $user = $DB->get_record('user', array('firstname'=>$enrolleduserinfo['first_name'], 'lastname'=>$enrolleduserinfo['last_name']));
                        if (!$user) {
                            $user = $DB->get_record_sql('SELECT * FROM {user} WHERE CONCAT(firstname, " ", lastname) = "'.$enrolleduserinfo['first_name']." ".$enrolleduserinfo['last_name'].'"');
                            if (!$user) {
                                continue;
                            }
                        }
                    }
                    //Get course module completion record, if not found
                    //will be created if found will be updated only if completionstatus not as 1 (completed).
                    $cmc = $DB->get_record('course_modules_completion', ['coursemoduleid'=>$cm->id, 'userid'=>$user->id]);
                    if (!empty($cmc)) {
                        if (isset($cmc->completionstate) && $cmc->completionstate <> 1) {
                            $cmc->completionstate = 1;
                            $cmc->timemodified = strtotime($hits['end_date']);
                            $DB->update_record('course_modules_completion', $cmc);
                            goone_set_completion($cm, $user->id, '', "completed");
                        }
                        //Check viewed and mark it.
                        $lmsviewed = $DB->get_record('course_modules_viewed',
                        ['coursemoduleid'=>$cm->id, 'userid'=>$user->id]);
                        if (empty($lmsviewed)) {
                            $newviewed = new \stdClass();
                            $newviewed->timecreated = time();
                            $newviewed->coursemoduleid = $cm->id;
                            $newviewed->userid = $user->id;
                            $DB->insert_record('course_modules_viewed', $newviewed);
                        }
                    } else {
                        $newcmc = new \stdClass();
                        $newcmc->coursemoduleid = $cm->id;
                        $newcmc->userid = $user->id;
                        $newcmc->completionstate = 1;
                        $newcmc->timemodified = strtotime($hit['end_date']);
                        $DB->insert_record('course_modules_completion', $newcmc);
                        goone_set_completion($cm, $user->id, '', "completed");
                        //Check viewed and mark it.
                        $lmsviewed = $DB->get_record('course_modules_viewed',
                        ['coursemoduleid'=>$cm->id, 'userid'=>$user->id]);
                        if (empty($lmsviewed)) {
                            $newviewed = new \stdClass();
                            $newviewed->timecreated = time();
                            $newviewed->coursemoduleid = $cm->id;
                            $newviewed->userid = $user->id;
                            $DB->insert_record('course_modules_viewed', $newviewed);
                        }
                    }
                }
            }
        } catch (moodle_exception $e) {
            $status = false;
        }

        return $status;
    }
}
