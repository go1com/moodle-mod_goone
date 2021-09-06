<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');
require_once($CFG->libdir.'/completionlib.php');
if ($json = json_decode(file_get_contents("php://input"), true)) {
    $data = $json;
} else {
    $data = json_decode($_POST, true);
}
// Only process if the hook has been called for the enrolment update.
if ($data['type'] == 'enrolment.update') {

    $loid = $data['data']['lo_id'];
    $gooneid = $DB->get_field('goone', 'id', ['loid' => $loid]);
    if (!$gooneid) {
        die();
    }
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'goone']);
    $cmid = $DB->get_field('course_modules', 'id', ['module' => $moduleid, 'instance' => $gooneid]);
    $cm  = get_coursemodule_from_id('goone', $cmid, 0, false, MUST_EXIST);
    $gooneuserid = $data['data']['user_id'];
    $curl = new curl();
    $serverurl = "https://api.go1.com/v2/users";
    $header = ["Authorization: Bearer ".get_config('mod_goone', 'token')];
    $curl->setHeader($header);
    $response = $curl->get($serverurl.'/'.$gooneuserid);
    $result = json_decode($response, true);
    if (!$result['email']) {
        die();
    }
    $email = $result['email'];
    // The first part of the email, i.e before the @ symbol is the userid.
    $userid = explode('@', $email)[0];
    if (!$DB->record_exists('user', ['id' => $userid])) {
        die();
    }
    $completion = new \completion_info(get_course($cm->course));
    $usercompletion = ($completion->get_data($cm, false, $userid));
    if ((!$usercompletion || $usercompletion->completionstate != COMPLETION_COMPLETE)) {
        if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
            $DB->insert_record('local_goonesync', (object) ['userid' => $userid, 'cmid' => $cmid, 'courseid' => $cm->course, 'timeadded' => time()]);
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid, true);
        }
    }

}