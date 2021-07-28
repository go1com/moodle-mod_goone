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

class sync_completion_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('synccompletiontask', 'mod_goone');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        $adminuserid = get_admin()->id;
        $sql = "SELECT gc.id, gc.userid, cm.id AS coursemoduleid, g.course, cmc.completionstate 
                  FROM {goone_completion} gc 
                  JOIN {goone} g ON g.id = gc.gooneid 
                  JOIN {course_modules} cm ON cm.instance = g.id 
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'goone' 
             LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = gc.userid 
                 WHERE gc.completed = 2 AND (cmc.completionstate IS NULL OR cmc.completionstate = 0)";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $completion = new \completion_info($record->course);
            $cm  = get_coursemodule_from_id('goone', $record->coursemoduleid, 0, false, MUST_EXIST);
            $completion->update_state($cm, COMPLETION_COMPLETE, $adminuserid, true);
        }

    }
}