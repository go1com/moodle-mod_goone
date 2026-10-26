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
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir.'/accesslib.php');

/**
 * A schedule task for goone cron.
 *
 * @package   mod_goone
 * @copyright 2024 Esteban Echavarria (esteban.echavarria@openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_mark_completion extends \core\task\scheduled_task {
    /** @var int The number of enrolments in one GO1 response. 50 is the maximum of the GO1 API. */
    const PAGESIZE = 50;

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

        $starttime = microtime();
        $debugging = true;
        $status = true;

        //API completion checks
        $comps = $apireqs = 0;
        //Get enrolments for all the user in all goone lo_ids.
        $gooneactivities  = $DB->get_records('goone');
        if (empty($gooneactivities)) {
            return true;
        }
        $module = $DB->get_record('modules', ['name'=>'goone']);
        foreach ($gooneactivities as $goone) {
            //Get course module.
            $cm = $DB->get_record('course_modules', ['course' => $goone->course, 'module' => $module->id, 'instance' => $goone->id]);
            if (!$cm) {
                mtrace("Skip: goone->id $goone->id has no course module");
                continue;
            }
            //Get enrolments completed from goone, one page at a time.
            if ($debugging) mtrace("Requesting: course $goone->course goone->id $goone->id lo_id $goone->loid");
            $hits = [];
            $params = ['lo_ids' => $goone->loid, 'status' => 'completed', 'limit' => self::PAGESIZE, 'offset' => 0];
            do {
                $response = bulk_mod_goone_api_custom_api_request("enrollments", "", $params);
                $apireqs++;
                if (!isset($response['hits'])) {
                    mtrace("Error: no enrolment list from GO1 for lo_id $goone->loid");
                    $status = false;
                    break;
                }
                $hits = array_merge($hits, $response['hits']);
                $params['offset'] += self::PAGESIZE;
            } while (count($response['hits']) == self::PAGESIZE && $params['offset'] < ($response['total'] ?? 0));
            $course = $DB->get_record('course', ['id' => $goone->course]);
            $completion = new \completion_info($course);
            foreach ($hits as $index => $hit) {
                // An error for one enrolment must not stop the task for the other enrolments.
                try {
                    //Get user details from goone.
                    if (($user = goone_get_moodle_user($hit['user_id'])) == false) {
                        continue;
                    }
                    $apireqs++;

                    // Ensure the user is enrolled in course.
                    $context = \context_course::instance($goone->course);
                    if (!is_enrolled($context, $user)) {
                        continue;
                    }

                    // Set CM viewed.
                    $lmsviewed = $DB->record_exists('course_modules_viewed', ['coursemoduleid' => $cm->id, 'userid' => $user->id]);
                    if (!$lmsviewed) {
                        $completion->set_module_viewed($cm, $user->id);
                    }

                    // Mark completion.
                    if ($debugging) mtrace("Loid: $goone->loid userid: $user->id course $course->id cm $cm->id goone $goone->id ", '');
                    if ($completion->is_enabled($cm) && $cm->completion == 2 && $goone->completionsubmit) {
                        if ($debugging) mtrace(" - proceed ", '');
                        // "Student must finish the Learning Object to complete it"
                        // Use the goone_completion value to make this decision. The value in
                        // course_modules_completion can show complete while goone_completion is
                        // not yet 2. If you read course_modules_completion, the task stops and
                        // the two records stay different. Moodle then makes the activity
                        // incomplete at the next calculation of the completion data.
                        $locomplete = $DB->get_field('goone_completion', 'completed',
                            ['gooneid' => $goone->id, 'userid' => $user->id]);
                        if ($locomplete != 2) {
                            goone_set_completion($cm, $user->id, '', "completed");
                            if ($debugging) mtrace("set");
                        } else {
                            if ($debugging) mtrace("skip: goone_completion.completed $locomplete");
                        }
                    } else {
                        if ($debugging) mtrace(" - skip: cm->completion $cm->completion completionsubmit $goone->completionsubmit");
                    }
                    $comps++;
                } catch (\moodle_exception $e) {
                    mtrace("Error: lo_id $goone->loid GO1 user {$hit['user_id']}: " . $e->getMessage());
                    $status = false;
                }
            }
            if ($debugging) mtrace("");
        }

        $endtime = microtime();
        $difftime = microtime_diff($starttime, $endtime);
        $starttime = $endtime;
        mtrace("API completion checks finished, {$difftime} seconds, processed {$comps} completions, {$apireqs} API requests.");

        return $status;
    }
}
