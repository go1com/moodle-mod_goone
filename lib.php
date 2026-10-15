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
 * GO1 plugin function library
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');
$config = get_config('mod_goone');


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $data
 * @param mod_page_mod_form $mform
 * @return int
 */
function goone_add_instance($data, $mform = null) {
    global $CFG, $DB;

    $cmid = $data->coursemodule;
    $data->timecreated = time();
    if (empty($data->token)) {
        $data->token = goone_download_scorm($data->loid);
    }
    $data->id = $DB->insert_record('goone', $data);
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    $context = context_module::instance($cmid);
    $DB->update_record('goone', $data);

    return $data->id;
}

/**
 * Downloads SCORM Zip file from GO1 API and extracts
 * token parameter.
 * @param object $loid
 * @return string
 */
function goone_download_scorm($loid) {
    global $CFG;
    // Sanitise the learning object id: it is used to build temp file paths.
    $loid = clean_param($loid, PARAM_ALPHANUMEXT);
    // Create temporary storage directory since we need to open a zip.
    $tempdir = make_temp_directory('goone/');
    $filename = $loid.'.zip';
    $tempfile = fopen($CFG->tempdir . '/goone/' . $filename, "w+");
    // Download GO1 SCORM zip file from external API.
    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects/".$loid."/scorm";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $curlopts = array(
        'file' => $tempfile,
        'followlocation' => true
        );
    $curl->download_one($serverurl, null, $curlopts);

    fclose($tempfile);
    $token = goone_extract_scorm_token($loid, $tempdir, $filename);
    fulldelete($tempdir.$filename);
    fulldelete($tempdir.$loid);
    if (!$token) {
        throw new moodle_exception('lodownloaderror', $loid);
    }
    return $token;
}

/**
 * Add a get_coursemodule_info function in case any goone type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function goone_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionsubmit';
    if (!$goone = $DB->get_record('goone', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $goone->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('goone', $goone, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $goone->completionsubmit;
    }

    return $result;
}
/**
 * Extracts token parameter from GO1 SCORM Zip.
 * @param string $loid
 * @param string $tempdir
 * @param string $filename
 * @param string $filepath
 * @return object
 */
function goone_extract_scorm_token($loid, $tempdir, $filename, $filepath = null ) {
    global $CFG;
    // Sanitise the learning object id: it is used to build temp file paths.
    $loid = clean_param($loid, PARAM_ALPHANUMEXT);
    // Open zip and extract 'config.js'.
    if (empty($filepath)) {
        $filepath = $CFG->tempdir . '/goone/' . $filename;
    }
    $tempdir = make_temp_directory('goone/');
    $packer = get_file_packer('application/zip');
    if ($packer->extract_to_pathname($filepath, $tempdir . $loid)) {
        $token = file_get_contents($tempdir . $loid . '/config.js');
        // Read token from config.js file to be stored in {goone} table.
        preg_match('/{([^}]*)}/', $token, $token);
        $token = json_decode($token[0])->token;
        return $token;
    } else {
        return false;
    }
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data
 * @param mod_page_mod_form $mform
 * @return bool
 */
function goone_update_instance($data, $mform) {
    global $CFG, $DB;

    $cmid               = $data->coursemodule;
    $data->timemodified = time();
    $data->id           = $data->instance;
    $DB->update_record('goone', $data);
    $DB->set_field('course_modules', 'instance', $data->instance, array('id' => $cmid));

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function goone_delete_instance($id) {
    global $DB;

    if (!$goone = $DB->get_record('goone', array('id' => $id))) {
        return false;
    }
    $cm = get_coursemodule_from_instance('goone', $id);
    $DB->delete_records('goone_completion', array('gooneid' => $goone->id));
    $DB->delete_records('goone', array('id' => $goone->id));

    return true;
}

/**
 * Returns an array of options for how GO1 courses can be presented
 * This is used by the participation report.
 *
 * @return array
 */
function goone_get_popup_display_array() {
    return array(0 => get_string('currentwindow', 'scorm'),
                 1 => get_string('popup', 'scorm'));
}


/**
 * Potentially unused function
 *
 * @param object $obj completion record object
 */
function notify_completion($obj) {
    global $DB, $USER;

    $goone = $DB->get_record('goone', array('id' => $obj->activityId));

    $cm  = get_coursemodule_from_id('goone', $obj->courseModuleId, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    // Update completion state.
    $completion = new completion_info($course);

    if ($completion->is_enabled($cm) && $goone->completionsubmit) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
    }
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function goone_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Generates go token and writes result to plugin config
 *
 */
function goone_generatetoken() {
    global $CFG, $DB;

    $oauthid = get_config('mod_goone', 'client_id');
    $oauthsecret = get_config('mod_goone', 'client_secret');
    $params = array (
        'client_id' => $oauthid,
        'client_secret' => $oauthsecret,
        'grant_type' => 'client_credentials'
    );

    $curl = new curl();
    $serverurl = "https://auth.GO1.com/oauth/token";
    $curloutput = @json_decode($curl->post($serverurl, $params), true);
    $curlinfo = $curl->get_info();
    if ($curlinfo['http_code'] == 200 && isset($curloutput['access_token'])) {
        set_config('token', $curloutput['access_token'], 'mod_goone');
        set_config('token_time', time(), 'mod_goone');
        return true;
    } else {
        set_config('token', '', 'mod_goone');
        set_config('token_time', 0, 'mod_goone');
        return false;
    }
}

/**
 * Generates go token and writes result to plugin config
 *
 * @return bool
 */
function goone_tokentest() {

    $config = get_config('mod_goone');
    // Check if token has expired.
    if (empty($config->token_time)) {
        return goone_generatetoken();
    } else {
        // Token expires in 12 hours.
        $token_age = time() - $config->token_time;
        $max_age = 43195; // 11:59:55
        if ($token_age < $max_age) {
            return true;
        }
    }

    if (empty($config->client_id) || empty($config->client_secret)) {
        set_config('token', '', 'mod_goone');
        return false;
    }

    $curl = new curl();
    $serverurl = "https://auth.GO1.com/oauth/validate";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $curl->get($serverurl);
    $httpcode = $curl->get_info()['http_code'];

    if ($httpcode == 200) {
        return true;
    } else {
        // Update the token, in case the credentials have changed.
        return goone_generatetoken();
    }

}

/**
 * Removes HTML tags for course descriptions retreived from GO1 API
 *
 * @param object $data
 * @return object
 */
function goone_clean_hits($data) {
    $data = preg_replace(
        '/\\\\u[0-9A-F]{4}/i', '', str_replace("\u003C", "<", str_replace("\u003E", ">", str_replace("\/", "/", $data)))
    );
    $data = html_entity_decode($data);
    $data = preg_replace('(\s*<[a-z A-Z 0-9]*>\\s*)', '', $data);
    $data = preg_replace('(\s*<\/[a-z A-Z 0-9]*>\s*)', ' ', $data);
    $data = preg_replace('(\s*<[^>]*>\s*)', '', $data);
    return $data;
}

/**
 * Outputs session state to SCORM API (using PAGE API) based on saved location and completion data
 *
 * @param int $gooneid
 * @param int $cmid
 */
function goone_session_state($gooneid, $cmid) {
    global $CFG, $DB, $PAGE, $USER;

    $def = new stdClass;
    // 0 = not started, 1 = in progress, 2 = complete.
    $completionrecord = $DB->get_record(
        'goone_completion', array('gooneid' => $gooneid, 'userid' => $USER->id), $fields = '*', $strictness = IGNORE_MISSING
    );
    $def->{(3)} = goone_scorm_def(1, '');
    $def->{(6)} = goone_scorm_def(1, '');
    $cmistate = "normal";

    if ($completionrecord && $completionrecord->completed == 1) {
        $def->{(3)} = goone_scorm_def(1, '');
        $def->{(6)} = goone_scorm_def(2, $completionrecord->location);
        $cmistate = "normal";
    }
    if ($completionrecord && $completionrecord->completed == 2) {

        $def->{(3)} = goone_scorm_def(3, '');
        $def->{(6)} = goone_scorm_def(4, '');
        $cmistate = "review";
    }

    $cmiobj = new stdClass();
    $cmiobj->{3} = '';
    $cmiobj->{6} = '';
    $cmiint = new stdClass();
    $cmiint->{3} = '';
    $cmiint->{6} = '';
    $cmistring256 = '^[\\u0000-\\uFFFF]{0,64000}$';
    $cmistring4096 = $cmistring256;

    $sstate = new stdClass();
    $sstate->def = $def;
    $sstate->cmiobj = $cmiobj;
    $sstate->cmiint = $cmiint;
    $sstate->cmistring256 = $cmistring256;
    $sstate->cmistring4096 = $cmistring4096;
    $sstate->cmistate = $cmistate;

    return($sstate);

}

/**
 * Populates SCORM API definition for function goone_session_state
 *
 * @param int $state
 * @param string $location
 */
function goone_scorm_def($state, $location) {
    global $USER;

    if (!$location) {
        $location = "";
    }
    if ($state == 1) {
        $cmicredit = "credit";
        $cmientry = "ab-initio";
        $cmimode = "normal";
        $cmilocation = "";
        $cmistatus = "";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "";
    }
    if ($state == 2) {
        $cmicredit = "credit";
        $cmientry = "resume";
        $cmimode = "normal";
        $cmilocation = $location;
        $cmistatus = "incomplete";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "suspend";
    }
    if ($state == 3) {
        $cmicredit = "no-credit";
        $cmientry = "ab-initio";
        $cmimode = "review";
        $cmilocation = "";
        $cmistatus = "";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "";
    }
    if ($state == 4) {
        $cmicredit = "no-credit";
        $cmientry = "";
        $cmimode = "review";
        $cmilocation = "";
        $cmistatus = "passed";
        $cmimax = "100";
        $cmimin = "0";
        $cmiexit = "";
    }

    $def = array();
    $def['cmi.core.student_id'] = $USER->username;
    $def['cmi.core.student_name'] = $USER->firstname.' '.$USER->lastname;
    $def['cmi.core.credit'] = $cmicredit;
    $def['cmi.core.entry'] = $cmientry;
    $def['cmi.core.lesson_mode'] = $cmimode;
    $def['cmi.launch_data'] = '';
    $def['cmi.student_data.mastery_score'] = '';
    $def['cmi.student_data.max_time_allowed'] = '';
    $def['cmi.student_data.time_limit_action'] = '';
    $def['cmi.core.total_time'] = '00:00:00';
    $def['cmi.core.lesson_location'] = $cmilocation;
    $def['cmi.core.lesson_status'] = $cmistatus;
    $def['cmi.core.score.raw'] = $cmimax;
    $def['cmi.core.score.max'] = $cmimax;
    $def['cmi.core.score.min'] = $cmimin;
    $def['cmi.core.exit'] = $cmiexit;
    $def['cmi.suspend_data'] = '';
    $def['cmi.comments'] = '';
    $def['cmi.student_preference.language'] = '';
    $def['cmi.student_preference.audio'] = '0';
    $def['cmi.student_preference.speed'] = '0';
    $def['cmi.student_preference.text'] = '0';

    return $def;
}

/**
 * Saves GO1 course completion state if enabled.
 *
 * @param object $cm
 * @param int $userid
 * @param string $location
 * @param string $type
 * @return bool
 */
function goone_set_completion($cm, $userid, $location, $type) {
    global $CFG, $DB;

    $gcomp = new stdClass();
    $gcomp->userid = $userid;
    $gcomp->gooneid = $cm->instance;
    $gcomp->position = $location;
    $gcomp->completed = ($type == "completed") ? 2 : 1;
    $gcomp->timemodified = time();
    $compstate = $DB->get_record(
        'goone_completion', array('gooneid' => $cm->instance, 'userid' => $userid), 'id,completed', $strictness = IGNORE_MISSING
    );

    if ($compstate) {
        $gcomp->id = $compstate->id;
        $DB->update_record('goone_completion', $gcomp);
    } else {
        $DB->insert_record('goone_completion', $gcomp);
    }

    if ($type == "completed") {
        $gcomp->completed = 2;
        $course = new stdClass();
        $course->id = $cm->course;
        $completion = new completion_info($course);
        $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
    }

    return true;
}

/**
 * gets scorm_12.js file form mod_scorm, modifies the datamodelurl variable, stores in cache and Retrieves it.
 * checks against current scorm version if cache needs to be rebuilt
 *
 * @return object
 */
function goone_inject_datamodel() {
    global $CFG;

    $cache = cache::make('mod_goone', 'scorm12datamodel');
    $scormversion = core_plugin_manager::instance()->get_plugin_info('mod_scorm')->versiondisk;

    if ($data = $cache->get($scormversion)) {
        return $data;
    }
        $data = file($CFG->dirroot.'/mod/scorm/datamodels/scorm_12.js');
        $data = implode("", str_replace("/mod/scorm/datamodel.php", "/mod/goone/datamodel.php", $data));
        $cache->set($scormversion, $data);
        $data = $cache->get($scormversion);
        return $data;
}

/**
 * Retrieves GO1 search results from GO1 API for Content Browser
 *
 * @param array $type
 * @param array $tag
 * @param array $language
 * @param array $provider
 * @param string $keyword
 * @param string $sort
 * @param int $offset
 * @return object
 */
function goone_get_hits($type, $tag, $language, $provider, $keyword, $sort, $offset) {
    global $PAGE, $OUTPUT;
    if (!goone_tokentest()) {
        return false;
    }

    $data = array (
        'keyword' => $keyword,
        'type' => implode(',', $type),
        'offset' => $offset,
        'sort' => $sort,
        'providers' => implode(',', $provider)
    );

    $config = get_config('mod_goone');
    $data['limit'] = 20;
    // Modifying request based on filter configuration.
    if ($config->filtersel == 1) {
        $data['subscribed'] = "true";
    }
    if ($config->filtersel == 2) {
        $data['collection'] = "default";
        $data['subscribed'] = "";
    }
    if ($config->filtersel == 0) {
        $data['subscribed'] = "";
        $data['collection'] = "";
    }

    $params = "";

    foreach ($data as $key => $value) {
        // Workaround for Moodle < 3.1.
        if ($value == 'null') {
            $value = '';
        }
        if (!$value == '') {
            $params .= $key.'='.$value.'&';
        }
    }

    $params = trim($params, '&');
    // Iterating each language/tag item due to current API limitations.
    foreach ($language as $lang) {
        // Workaround for Moodle < 3.1.
        if ($lang == 'null') {
            unset($lang);
        }
        if (isset($lang) && $lang != '') {
            $params .= "&language%5B%5D=".$lang;
        }
    }

    foreach ($tag as $ta) {
        // Workaround for Moodle < 3.1.
        if ($ta == 'null') {
            unset($ta);
        }
        if (isset($ta) && $ta != '') {
            $params .= "&tags%5B%5D=".$ta;
        }
    }
    $curl = new curl();
    // Appendeding to URL due to API limitations.
    $serverurl = "https://api.GO1.com/v2/learning-objects?facets=instance,tag,language&marketplace=all&".$params;
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $response = @json_decode($curl->get($serverurl), true);
    if ($curl->get_info()['http_code'] != 200) {
        throw new moodle_exception('go1apierror');
    }
    foreach ($response['hits'] as &$obj) {
        $obj['description'] = goone_clean_hits($obj['description']);
        $obj['pricing']['price'] = '$'.$obj['pricing']['price'];
        // Set the "Included" or "Free" flag on each result.
        if (!empty($obj['subscription']) and ($obj['subscription']['licenses'] === -1 or $obj['subscription']['licenses'] > 0)) {
            $obj['pricing']['price'] = get_string('included', 'goone');
        }
        if ($obj['pricing']['price'] === "$0") {
            $obj['pricing']['price'] = get_string('free', 'goone');
        }
        if ($obj['image'] == '') {
            $obj['image'] = "/mod/goone/pix/placeholder.png";
        }
    }
    $context = context_system::instance();
    $PAGE->set_context($context);
    return $OUTPUT->render_from_template('mod_goone/hits', $response);
}

/**
 * Retrieves GO1 search facets from GO1 API for Content Browser
 *
 * @return object
 */
function goone_get_facets() {
    global $USER;

    if (!goone_tokentest()) {
        return;
    }
    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $params = array ('facets' => 'instance,tag,language,topics',
                     'limit' => 0);
    $facets = @json_decode($curl->get($serverurl, $params), true);

    foreach ($facets['facets']['language']['buckets'] as &$obj) {
        $obj['name'] = goone_get_lang($obj['key']);
        // Compare 2 letter language string and set as default selection in fitler.
        if ($obj['key'] == substr($USER->lang, 0, 2)) {
            $obj['selected'] = "selected";
        }
    }
    return $facets;
}

/**
 * Converts ISO language code to full language name for GO1 Content Browser
 *
 * @param string $lang
 * @return string
 */
function goone_get_lang($lang = '') {
    $languages = get_string_manager()->get_list_of_languages();
    if (array_key_exists($lang, $languages)) {
        return $languages[$lang];
    }
    if (strpos($lang, '-') > 0) {
        list($langcode, $countrycode) = explode('-', $lang, 2);
        if (array_key_exists($langcode, $languages)) {
            $string = $languages[$langcode]; $countrycode = clean_param(strtoupper($countrycode), PARAM_STRINGID);
            if (get_string_manager()->string_exists($countrycode, 'core_countries')) {
                return $string . " (" . get_string($countrycode, 'core_countries') . ")";
            }
        }
    }
    if (empty($lang)) {
        return get_string('unknownlanguage', 'mod_goone');
    }
        return $lang;
}

/**
 * Retreives detailed GO1 course information for modal popup in GO1 Content Browser
 *
 * @param int $loid
 * @return object
 */
function goone_modal_overview($loid) {
    global $PAGE, $OUTPUT;

    if (!goone_tokentest()) {
        return;
    }
    $curl = new curl();
    $serverurl = "https://api.go1.com/v2/learning-objects/".$loid;
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $lodata = @json_decode($curl->get($serverurl), true);
    // Data cleanup and prettification.
    $lodata['has_items'] = !empty($lodata['items']);
    if (!empty($lodata['delivery'])) {
        foreach ($lodata['delivery'] as &$obj) {
            $obj = goone_convert_hours_mins($obj);
        }
    }
    if ($lodata['image'] == " ") {
        unset($lodata['image']);
    }

    return json_encode($lodata);
}

/**
 * Converts timecode to human readable time for GO1 course durations from GO1 API results
 *
 * @param int $time
 * @param string $format
 * @return string
 */
function goone_convert_hours_mins($time, $format = '%02d:%02d') {
    if ($time < 1) {
        return;
    }
    $hours = floor(floatval($time) / 60);
    $minutes = (floatval($time) % 60);

    return sprintf($format, $hours, $minutes);
}

/**
 * Capability check for adding or updating a goone activity
 *
 * @param string $mode
 * @param int $id
 */
function goone_check_capabilities($mode, $id) {
    $context = goone_get_browse_context($mode, $id);
    require_capability('mod/goone:addinstance', $context);
    return true;
}

/**
 * Resolve the context in which a goone activity is being added or updated.
 *
 * @param string $mode 'add' (course context) or 'update' (module context)
 * @param int $id Course id when adding, goone instance id when updating
 * @return context The course or module context used for the capability check
 */
function goone_get_browse_context($mode, $id) {
    global $DB;

    switch ($mode) {
        case 'add':
            // Adding a new activity: use the course context.
            $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
            return context_course::instance($course->id);
        case 'update':
            // Updating an existing activity: use the module context.
            $goone = $DB->get_record('goone', array('id' => $id), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance("goone", $goone->id, $goone->course);
            return context_module::instance($cm->id);
        default:
            throw new moodle_exception('invalidparam');
    }
}

/**
 * Construct GO1 signup url
 *
 */
function goone_signup_url() {
    global $CFG;

    $partnerurl = "";
    $partnerid = get_config('mod_goone', 'partnerid');
    if (!empty($partnerid)) {
        $partnerurl = "&partner_portal_id=".$partnerid;
    }
    $url = 'https://auth.GO1.com/oauth/authorize?client_id=Moodle&response_type=code&redirect=false&redirect_uri='
        .$CFG->wwwroot.'&new_client=Moodle'.$partnerurl;
    return $url;
}


/**
 * Get Moodle user by GO1 userid.
 *
 * @param int $gooneuserid
 * @return mixed stdclass|bool DB record object from {user} table or false
 */
function goone_get_moodle_user($gooneuserid) {
    global $DB;

    // API V3
    // $userinfofromgo = bulk_mod_goone_apiv3_custom_api_request('user-accounts', $gooneuserid);

    if ($row = $DB->get_record('goone_usermap', ['gooneuserid' => $gooneuserid])) {
        $user = $DB->get_record('user', ['id' => $row->userid]);
        return $user;
    }

    // API V2
    $userinfofromgo = bulk_mod_goone_api_custom_api_request('users', $gooneuserid);
    $email = $userinfofromgo['email'];
    $firstname = $userinfofromgo['first_name'];
    $lastname = $userinfofromgo['last_name'];
    $username = (isset($userinfofromgo['custom_fields']['external_user_id'])) ? $userinfofromgo['custom_fields']['external_user_id'] : '';

    if (!empty($username)) {
        $user = $DB->get_record('user', array('username' => $username));
    }

    // Try email as-is.
    if (empty($user)) {
        $user = $DB->get_record('user', array('email' => $email));
    }

    if (empty($user)) {
        $user = $DB->get_record('user', array('username' => $email));
    }

    // Try email from external_user_id.
    if (empty($user) && !empty($username)) {
        $user = $DB->get_record('user', array('email' => $username));
    }

    // Try the user part of the email as username.
    if (empty($user)) {
        $regexp = '/([^@]+)@.*/i';
        $username = preg_match($regexp, $email, $p);
        if (!empty($p[1])) {
            $user = $DB->get_record('user', array('username' => $username));
        }
    }

    // Try firstname and lastname as last resort, but proceed only if one match.
    if (empty($user)) {
        $users = $DB->get_records('user', array('firstname' => $firstname, 'lastname' => $lastname));
        if (sizeof($users) == 1) {
            reset($users);
            $user = current($users);
        }
    }

    if ($user) {
        $r = new \stdClass();
        $r->gooneuserid = $gooneuserid;
        $r->timecreated = time();
        $r->userid = $user->id;
        $DB->insert_record('goone_usermap', $r);
        return $user;
    } else {
        return false;
    }
}


// Bulk actions browser methods

/**
 * Return the max and min used based on selected duration option
 *
 * @param int $selectedduration selected duration
 * @return array with min max minutes 
 */
function bulk_mod_goone_duration_get_min_max($selectedduration) {
    /*
    1 0 - 15 min
    2 15 - 30 min
    3 30 - 60 min
    4 60 min
    */
    $la_minmax = array();
    switch ($selectedduration) {
        case '1':
            $la_minmax = array('min'=>0,'max'=>15);
            break;
        case '2':
            $la_minmax = array('min'=>15,'max'=>30);
            break;
        case '3':
            $la_minmax = array('min'=>30,'max'=>60);
            break;
        case '4':
           $la_minmax = array('min'=>60);
            break;
    }
    return $la_minmax;
}
/**
 * Retrieves GO1 search results from GO1 API for Content Browser
 *
 * @param array $type
 * @param array $topic
 * @param array $language
 * @param array $provider
 * @param string $keyword
 * @param string $sort
 * @param int $offset
 * @return object
 */
function bulk_mod_goone_get_hits($type, $topic, $language, $provider, $keyword, $sort, $offset, $loadmore, $duration) {
    global $PAGE, $OUTPUT;
    if (!goone_tokentest()) {
        return false;
    }
    $data = array (
        'keyword' => $keyword,
        'type' => implode(',', $type),
        'offset' => $offset,
        'sort' => $sort,
        'providers' => implode(',', $provider)
    );
    $config = get_config('mod_goone');
    $data['limit'] = 20;
    // Modifying request based on filter configuration.
    if ($config->filtersel == 1) {
        $data['subscribed'] = "true";
    }
    if ($config->filtersel == 2) {
        $data['collection'] = "default";
        $data['subscribed'] = "";
    }
    if ($config->filtersel == 0) {
        $data['subscribed'] = "";
        $data['collection'] = "";
    }
    $params = "";
    foreach ($data as $key => $value) {
        // Workaround for Moodle < 3.1.
        if ($value == 'null') {
            $value = '';
        }
        if (!$value == '') {
            $params .= $key.'='.$value.'&';
        }
    }
    $params = trim($params, '&');
    // Iterating each language/tag item due to current API limitations.
    foreach ($language as $lang) {
        // Workaround for Moodle < 3.1.
        if ($lang == 'null') {
            unset($lang);
        }
        if (isset($lang) && $lang != '') {
            $params .= "&language%5B%5D=".$lang;
        }
    }
    foreach ($topic as $ta) {
        // Workaround for Moodle < 3.1.
        if ($ta == 'null') {
            unset($ta);
        }
        if (isset($ta) && $ta != '') {
            $params .= "&topics%5B%5D=".$ta;
        }
    }
    $durationquery = "";
    $countdurations = 0;
    if (!empty($duration)) {
        foreach ($duration as $option) {
            $minmax = bulk_mod_goone_duration_get_min_max($option);
            if (isset($minmax['min']) && $minmax['max'] ) {
                $durationquery .= "duration%5B".$countdurations."%5D%5Bmin%5D=".$minmax['min']."&duration%5B".$countdurations."%5D%5Bmax%5D=".$minmax["max"]."&";
            } else if (isset($minmax['min']) && !isset($minmax['max'])) {
                $durationquery .= "duration%5B".$countdurations."%5D%5Bmin%5D=".$minmax['min']."&";
            } else if (isset($minmax['max']) && !isset($minmax['min'])) {
                $durationquery .= "duration%5B".$countdurations."%5D%max%5D=".$minmax['max']."&";
            }
            $countdurations++;
        }
    }
    if (!empty($durationquery)) {
        $durationquery = rtrim($durationquery, '&');
        $params.="&".$durationquery;
    }
    $response = bulk_mod_goone_api_custom_api_request("learning-objects?facets=instance,tag,language,topics&marketplace=all&".$params);
    foreach ($response['hits'] as &$obj) {
        $obj['description'] = goone_clean_hits($obj['description']);
        $obj['title'] = str_replace('"', '', $obj['title']);
        $obj['title'] = str_replace("'", '', $obj['title']);
        $obj['title'] = goone_clean_hits($obj['title']);
        $obj['provider']['name'] = goone_clean_hits($obj['provider']['name']);
        $obj['pricing']['price'] = '$'.$obj['pricing']['price'];
        // Set the "Included" or "Free" flag on each result.
        if (!empty($obj['subscription']) and ($obj['subscription']['licenses'] === -1 or $obj['subscription']['licenses'] > 0)) {
            $obj['pricing']['price'] = get_string('included', 'mod_goone');
        }
        if ($obj['pricing']['price'] === "$0") {
            $obj['pricing']['price'] = get_string('free', 'mod_goone');
        }
        if ($obj['image'] == '') {
            $obj['image'] = "/mod/goone/pix/placeholder.png";
        }
         $obj['language'] = goone_get_lang($obj['language']);
    }
    $response['loadmore'] = $loadmore;
    $context = context_system::instance();
    $PAGE->set_context($context);
    return $OUTPUT->render_from_template('mod_goone/hits_bulk', $response);
}


/**
 * Retreives detailed GO1 course information for modal popup in GO1 Content Browser
 *
 * @param int $loid
 * @return object
 */
function bulk_mod_goone_modal_overview($loid) {
    global $PAGE, $OUTPUT;
    if (!goone_tokentest()) {
        return;
    }
    $lodata = bulk_mod_goone_get_learnig_objective_info($loid);
    // Data cleanup and prettification.
    $lodata['has_items'] = !empty($lodata['items']);
    if (!empty($lodata['delivery'])) {
        foreach ($lodata['delivery'] as &$obj) {
            $obj = goone_convert_hours_mins($obj);
        }
    }
    if ($lodata['image'] == " ") {
        unset($lodata['image']);
    }
    return json_encode($lodata);
}

/**
 * Will return object of found courses
 *
 * @param string $stringtosearch
 * @return array $result  courses found
 */
function bulk_mod_goone_modal_search_course_result($stringtosearch = '') {
    global $DB;
    $result = array();
    if (!empty($stringtosearch)) {
        $searchterm = '%' . $DB->sql_like_escape($stringtosearch) . '%';
        $shortnamelike = $DB->sql_like('shortname', ':shortnamesearch', false);
        $fullnamelike = $DB->sql_like('fullname', ':fullnamesearch', false);
        $sql = "SELECT id, fullname, shortname, idnumber
                  FROM {course}
                 WHERE id > 1
                   AND ($shortnamelike OR $fullnamelike)";
        $params = ['shortnamesearch' => $searchterm, 'fullnamesearch' => $searchterm];

        $searchresult = $DB->get_records_sql($sql, $params);

        $result['has_courses'] = !empty($searchresult);

        foreach ($searchresult as $courseid => $courseobj) {
             $coursesections = $DB->get_records('course_sections', array('course'=>$courseobj->id));
             $la_coursesections = [];
             foreach ($coursesections as $secid => $secobj) {

                if (empty($secobj->name)) {
                    $secobjname = get_string('topic').' '.$secobj->section;
                } else {
                   $secobjname = $secobj->name;
                }

                $la_coursesections[] = array('section_name'=>$secobjname, 'course_section_id'=>$courseobj->id.'-'.$secobj->section);
             }
             $courseobj->sections = $la_coursesections;
        }
        foreach ($searchresult as $key => $courseobj) {
            $la_searchresult[] = $courseobj;
        }
        $result['courses'] = $la_searchresult;
        return json_encode($result);
    }
}

/**
 * Method to create a course per seleted items
 *
 * @param string $items items selected
 * @return string return message if success or failed
 */
function bulk_mod_goone_process_course_per_item($items = '') {
    global $CFG;
    $multipleitems = strpos($items, ',');
    $countcreated = 0 ;
    $coursescreated ="";
    $htmlurls = "";
   if ($multipleitems === false) {
        $loinfo = bulk_mod_goone_get_learnig_objective_info($items);
        if ($loinfo) {
            if ($createdcourse = bulk_mod_goone_create_course($loinfo)) {
                if (bulk_mod_goone_create_assign_module($loinfo, $createdcourse)) {
                    return html_writer::tag('a', $createdcourse->fullname, 
                        array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/view.php?id=".$createdcourse->id)).' - '.
                    html_writer::tag('a',get_string('edit'), 
                        array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/edit.php?id=".$createdcourse->id));
                } else {
                    throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
                }
            } else {
                throw new moodle_exception('createprocesscoursefailed', 'bulk_mod_goone');
            }
        }
   } else {
          $la_items = explode(',', $items);
          $coursescreated = array();
          foreach ($la_items as $loid) {
            if (empty($loid)) continue;
              $loinfo = bulk_mod_goone_get_learnig_objective_info($loid);
              if ($loinfo) {
                  if ($createdcourse = bulk_mod_goone_create_course($loinfo)) {
                        if (bulk_mod_goone_create_assign_module($loinfo, $createdcourse)) {
                        } else {
                            throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
                        }      
                        $coursescreated[$createdcourse->fullname] = $createdcourse->id ;
                    } else {
                        throw new moodle_exception('createprocesscoursefailed', 'bulk_mod_goone');
                    }
              }
       }
       $htmlurls .= html_writer::start_tag('ul');
       foreach ($coursescreated as $name => $id) {
        $urlview = $CFG->wwwroot."/course/view.php?id=".$id; 
        $urledit = $CFG->wwwroot."/course/edit.php?id=".$id;
        $info = html_writer::tag('a', $name, array('target'=>'_blank', 'href'=>$urlview)).' - '.html_writer::tag('a', get_string('edit'), array('target'=>'_blank', 'href'=>$urledit));
        $htmlurls .= html_writer::tag('li', $info,[]);
       }
       $htmlurls .= html_writer::end_tag('ul');
       return $htmlurls;
    }
}
/**
 * Method to create a single course with all seleted items
 *
 * @param string $items items selected
 * @return string return message if success or failed
 */
function bulk_mod_goone_process_course_single_course($items = '', $coursename = '') {
    global $CFG;
    $multipleitems = strpos($items, ',');
   if ($multipleitems === false) {
        $loinfo = bulk_mod_goone_get_learnig_objective_info($items);
        if ($loinfo) {
            if ($createdcourse = bulk_mod_goone_create_course($loinfo, $coursename)) {
                if (bulk_mod_goone_create_assign_module($loinfo, $createdcourse)) {
                    return html_writer::tag('a',$createdcourse->fullname, 
                        array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/view.php?id=".$createdcourse->id)).' - '.
                    html_writer::tag('a',get_string('edit'), 
                        array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/view.php?id=".$createdcourse->id));
                } else {
                    throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
                }
            } else {
                throw new moodle_exception('createprocesscoursefailed', 'bulk_mod_goone');
            }       
        }
   } else {
      $la_items = explode(',', $items);
      // create course 
      if (!$createdcourse = bulk_mod_goone_create_course("", $coursename)) {
        throw new moodle_exception('createprocesscoursefailed', 'bulk_mod_goone');
      }
      foreach ($la_items as $loid) {
        if (empty($loid)) continue; 
        // add each activity to the course in first topic
        $loinfo = bulk_mod_goone_get_learnig_objective_info($loid);
        if ($loinfo) {
            if (!bulk_mod_goone_create_assign_module($loinfo, $createdcourse)) {
                throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
            }      
        }
      }
      $courseurl = html_writer::tag('a', $createdcourse->fullname, array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/view.php?id=".$createdcourse->id));
      $courseurl .= ' - '.html_writer::tag('a', get_string('edit'), array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/edit.php?id=".$createdcourse->id));
      return $courseurl;
   }
}


/**
 * Method to add the activity to existing course
 *
 * @param string $course_section selected course and section
 * @return string return course url
 */
function bulk_mod_goone_process_add_to_existing_course($course_section, $selecteditemsids) {
    global $DB, $CFG;
    $la_coursesection = explode('-', $course_section);
    $la_selecteditemsids = explode(',', $selecteditemsids);
    $multipleitems = strpos($selecteditemsids, ',');
    $course = $DB->get_record('course', array('id'=>$la_coursesection[0]));
    if ($multipleitems === false) {
        // add activity to the course in selected topic
        $loinfo = bulk_mod_goone_get_learnig_objective_info($selecteditemsids);
        if ($loinfo) {
            if (!bulk_mod_goone_create_assign_module($loinfo, $course, $la_coursesection[1])) {
                throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
            }      
        }
    } else {
        foreach ($la_selecteditemsids as $loid) {
            if (empty($loid)) continue; 
            // add each activity to the course in selected topic
            $loinfo = bulk_mod_goone_get_learnig_objective_info($loid);
            if ($loinfo) {
                if (!bulk_mod_goone_create_assign_module($loinfo, $course, $la_coursesection[1])) {
                    throw new moodle_exception('createprocessactivityfailed', 'bulk_mod_goone');
                }      
            }
      }
    }
    $urls = html_writer::tag('a', $course->fullname, array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/view.php?id=".$course->id)) ;
    $urls .= " - ".html_writer::tag('a', get_string('edit'), array('target'=>'_blank', 'href'=>$CFG->wwwroot."/course/edit.php?id=".$course->id)) ;
    return $urls; 
}

/**
 * Retrieves GO1 learning objective info
 *
 * @return object
 */
function bulk_mod_goone_get_learnig_objective_info($loid) {
    $loinfo = bulk_mod_goone_api_custom_api_request('learning-objects', $loid);
    return $loinfo;
}

/**
 * Method to create course
 *
 * @param string $loinfo Learning Object info fetch from GO1 API
 * @return object $createdcourse return created course
 */
function bulk_mod_goone_create_course($loinfo = null, $coursename = '') {
    global $DB;
    $coursedefaults = get_config('moodlecourse');
    $data = new stdClass;
    if (!empty($loinfo)) {
        // Setting
        $data->category = get_config('moodle', 'defaultrequestcategory');
        $data->shortname = $loinfo['title'];
        $data->idnumber = $loinfo['id'];
        $data->fullname = $loinfo['title'];
        $data->lang = 'en';
        $data->format = $coursedefaults->format;
        $data->summary = $loinfo['description'];
        $data->summaryformat = 1;
        $data->enablecompletion = $coursedefaults->enablecompletion;
        $data->visible = $coursedefaults->visible;
        $data->startdate = time();
        if ($coursefound = $DB->get_record('course', array('idnumber'=>$loinfo['title']))) {
            return $coursefound;
        } else if ($coursefound = $DB->get_record('course', array('shortname'=>$loinfo['title']))) {
            return $coursefound;
        } else {
            $createdcourse = create_course($data);
            $imageurl = $loinfo['image'];
            if (isset($loinfo['image']) && !empty($loinfo['image'])) {
                $context = context_course::instance($createdcourse->id);
                $filerecord = array(
                    'contextid' => $context->id,
                    'component' => 'course',
                    'filearea' => 'overviewfiles',
                    'itemid' => 0,
                    'filepath' => '/'
                );
                $fs = get_file_storage();
                $fs->create_file_from_url($filerecord, $imageurl);
            }
            return $createdcourse;
        }
    } else if (empty($loinfo)) {
        // Setting
        $data->category = get_config('moodle', 'defaultrequestcategory');
        $data->shortname = preg_replace( '/[^a-z]/i', '', strtolower($coursename));
        $data->fullname = $coursename;
        $data->format = $coursedefaults->format;
        $data->lang = 'en';
        $data->enablecompletion = $coursedefaults->enablecompletion;
        $data->visible = $coursedefaults->visible;
        $data->startdate = time();
        $createdcourse = create_course($data);
        return $createdcourse;
    }
}
/**
 * Method to add activity to course
 *
 * @param string $loinfo Learning Object info fetch from GO1 API
 * @param $course created course or course to update
 * @return object $cminfo course module info
 */
function bulk_mod_goone_create_assign_module($loinfo, $course, $topic = 0) {
    global $DB;
    $cminfo = new stdClass;
    $cminfo->name = $loinfo['title'];
    $cminfo->loid = $loinfo['id'];
    $cminfo->loname = $loinfo['title'];
    $cminfo->introeditor = array('text'=>$loinfo['description']);
    $cminfo->introformat = 1;
    $cminfo->showdescription = 0;
    $cminfo->display = 5;
    $cminfo->printheading = 1;
    $cminfo->printintro = 0;
    $cminfo->printlastmodified = 1;
    $cminfo->visible = 1;
    $cminfo->visibleoncoursepage = 1;
    $cminfo->cmidnumber = $loinfo['id'];
    $cminfo->availabilityconditionsjson ='{"op":"&","c":[],"showc":[]}';
    $cminfo->completionunlocked = 1;
    $cminfo->completion = 2;
    $cminfo->completionview = 1;
    $cminfo->completionsubmit = 1;
    $cminfo->completionexpected = 0;
    $cminfo->course = $course->id;
    $cminfo->coursemodule = 0;
    $cminfo->section = $topic;
    $goonemod = $DB->get_record('modules', array('name'=>'goone'));
    $cminfo->module = $goonemod->id;
    $cminfo->modulename = 'goone';
    $cminfo->instance = 0;
    $cminfo->add = 'goone';
    $cminfo->competency_rule = 0;
    return add_moduleinfo($cminfo, $course);
}

/**
 * Method to get info from GO1 API with different methods 
 */
function bulk_mod_goone_api_custom_api_request($endpoint_method = "" , $value_id = "", $params = null) {
    global $DB;
    // define api url call
    $curl = new curl();
    if (empty($value_id)) {
        $serverurl = "https://api.go1.com/v2/".$endpoint_method;
    } else {
        $serverurl = "https://api.go1.com/v2/".$endpoint_method."/".$value_id;
    }

    goone_tokentest();
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    if ($params) {
        $result = @json_decode($curl->get($serverurl, $params), true);
    } else {
        $result = @json_decode($curl->get($serverurl), true);
    }
    if ($curl->get_info()['http_code'] == 404) {
        $logobj = new \stdClass();
        $logobj->message_info = $endpoint_method.' '.get_string('recordrequestednotfound', 'mod_goone')."\n\n".$value_id;
        $logobj->timecreated = time();
        $DB->insert_record('goone_webhook_logs', $logobj);
    }
    return $result;
}


/**
 * Method to get info from GO1 API V3 with different methods
 *
 */
function bulk_mod_goone_apiv3_custom_api_request($endpoint, $value_id = "", $method = 'get', $params = null) {
    global $DB;
    // define api url call
    $curl = new curl();
    $serverurl = "https://gateway.go1.com/".$endpoint;
    if (!empty($value_id)) {
        $serverurl .= "/{$value_id}";
    }

    goone_tokentest();
    $header = array (
        "Authorization: Bearer ".get_config('mod_goone', 'token'),
        "Api-Version: Alpha"
    );
    $curl->setHeader($header);
    if (!empty($params)) {
        $result = @json_decode($curl->$method($serverurl, $params), true);
    } else {
        $result = @json_decode($curl->$method($serverurl), true);
    }

    if ($curl->get_info()['http_code'] == 404) {
        $logobj = new \stdClass();
        $logobj->message_info = $endpoint.' '.get_string('recordrequestednotfound', 'mod_goone')."\n\n".$value_id;
        $logobj->timecreated = time();
        $DB->insert_record('goone_webhook_logs', $logobj);
    }
    return $result;
}



/**
 * Method to generate a random string to be use a secret to sing the API calls 
 *
 * @param string $loinfo Learning Object info fetch from GO1 API
 * @param string $course created course or course to update
 * @return string $cminfo course module info
 */
function bulk_mod_goone_create_api_secret_key($n = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*(){}[]_+-';
    $randsecret = '';

    for ($i = 0; $i < $n; $i++) {
        $index = random_int(0, strlen($characters) - 1);
        $randsecret .= $characters[$index];
    }

    return $randsecret;
}


function mod_goone_check_webhook() {
    goone_tokentest();

    $curl = new curl();
    $curl->setHeader(array(
        'Cache-Control: no-cache',
        'api-version: 2022-07-01',
        'Authorization: Bearer '.get_config('mod_goone', 'token'),
    ));
    $resp = $curl->get("https://gateway.go1.com/webhooks");
    $webhook = json_decode($resp);

    if(isset($webhook->total) && $webhook->total > 0 ) {
        return array('webhook'=>true, 'data'=>$webhook);
    }else{
        return array('webhook'=>false, 'data'=>'');
    }
}

function mod_goone_view_api_payload_logs() {
    global $PAGE, $OUTPUT, $DB;
    require_login();
    $context = context_system::instance();
    require_capability('mod/goone:viewbulkcontent', $context);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/mod/goone/payload.php?logs_view=1');
    $PAGE->set_title(get_string('logs_webhook', 'mod_goone'));
    $PAGE->set_heading(get_string('logs_webhook', 'mod_goone'));
    echo $OUTPUT->header();
        $sqllogs = $DB->get_records_sql('SELECT * FROM {goone_webhook_logs} ORDER BY timecreated DESC');
        $table = new html_table();
        $table->head = array('Id', 'message_info', 'Timestamp');
        $table->data = array();
        foreach ($sqllogs as $record) {
            // Escape the stored message (XSS attack evasion).
            $table->data[] = array(
                $record->id,
                s($record->message_info),
                date("Y/m/d H:i:s", $record->timecreated),
            );
        }
        echo html_writer::table($table);
    echo $OUTPUT->footer();
}

function mod_goone_api_payload_process_post() {

    global $DB;
    $logobj = new stdClass;

    $request = file_get_contents('php://input');

    $api_secret = get_config('mod_goone', 'goone_api_secret');
    if (empty($api_secret)) {
        $logobj->message_info = get_string('signaturenotconfig', 'mod_goone')."\n\n".$request;
        $logobj->timecreated = time();
        $DB->insert_record('goone_webhook_logs', $logobj);
    }

    $la_postjson = json_decode($request, true);
    $signature = array();

    $la_signature = explode(',', $_SERVER['HTTP_GO1_SIGNATURE']);
    foreach ($la_signature as $key => $value) {
        $la_signatureitems = explode('=', $value);
        $signature[] = $la_signatureitems[1];
    }

    $timestamp = $signature[0];
    $stringposttimestamp = (string) $timestamp.".".$request;
    $calculatedSignature = hash_hmac('sha256', $stringposttimestamp, $api_secret);

    if (isset($signature[1])) {
        if (hash_equals($calculatedSignature, $signature[1])) {
            mod_goone_api_payload_process_post_request($la_postjson, $request);
        } else {
            $logobj->message_info = get_string('signaturenotmatching', 'mod_goone')."\n\n".$request;
            $logobj->timecreated = time();
            $DB->insert_record('goone_webhook_logs', $logobj);
        }
    }  else {
        $logobj->message_info = get_string('signaturenotset', 'mod_goone')."\n\n".$request;
        $logobj->timecreated = time();
        $DB->insert_record('goone_webhook_logs', $logobj);
    }
}


/**
 * Process the request after basic checks.
 * The function is split to allow for internal testing (test webhook requests omitting the signature check).
 *
 * @param array $la_postjson - Decoded JSON request
 * @param mixed $request - Original JSON request (to append in log)
 *
 * @return void
 */
function mod_goone_api_payload_process_post_request($la_postjson, $request): void {
    global $DB;

    $logobj = new stdClass;

    $enrolinfo = bulk_mod_goone_api_custom_api_request('enrollments', $la_postjson['data']['id']);
    if (empty($enrolinfo['pass'])) {
        return;
    }
    $user = goone_get_moodle_user($enrolinfo['user_id']);

    if ($user) {
        $activities = $DB->get_records('goone', array('loid' => $la_postjson['data']['lo_id']));
        $mod = $DB->get_record('modules', array('name' => 'goone'));
        if (!empty($activities)) {
            foreach ($activities as $id => $activity) {
                $cm = $DB->get_record('course_modules', array('course' => $activity->course, 'module' => $mod->id, 'instance' => $activity->id));
                $cmc = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cm->id, 'userid' => $user->id));

                // Ensure the user is enrolled in course.
                $context = \context_course::instance($activity->course);
                if (!is_enrolled($context, $user)) {
                    continue;
                }

                if (!$cmc || !in_array($cmc->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                    //Check viewed and mark it.
                    $lmsviewed = $DB->record_exists('course_modules_viewed', ['coursemoduleid' => $cm->id, 'userid' => $user->id]);
                    $result = [];
                    if (!$lmsviewed) {
                        $completion = new \completion_info(get_course($activity->course));
                        $completion->set_module_viewed($cm, $user->id);
                        $result[] = 'viewed';
                    }
                    if ($cm->completion == 2 && $activity->completionsubmit) { // "Student must finish the Learning Object to complete it"
                        goone_set_completion($cm, $user->id, '', "completed");
                        $result[] = 'completed';
                    }
                    /*
                    $resultstr = implode(',', $result);
                    $logobj->message_info = "Success. User: {$user->id}, course: {$activity->course}, cm {$cm->id}, set {$resultstr}\n\n".$request;
                    $logobj->timecreated = time();
                    $DB->insert_record('goone_webhook_logs', $logobj);
                    */
                }
            }
        } else {
            $logobj->message_info = get_string('activitynotfound', 'mod_goone')."\n\n".$request;
            $logobj->timecreated = time() ;
            $DB->insert_record('goone_webhook_logs', $logobj);
        }
    } else {
        $logobj->message_info = get_string('usernotfound', 'mod_goone')."\n\n".$request;
        $logobj->timecreated = time();
        $DB->insert_record('goone_webhook_logs', $logobj);
    }
}


function mod_goone_create_webhook_api_request() {
    global $PAGE, $OUTPUT, $DB, $CFG;

    // Creating a webhook changes site state (it regenerates the signing secret and
    // registers a GO1 webhook), so require login, capability and a valid sesskey.
    require_login();
    $context = context_system::instance();
    require_capability('mod/goone:viewbulkcontent', $context);
    require_sesskey();
    $PAGE->set_context($context);

    $logobj = new stdClass;
    // Check if webhook already created and prevent creation of multiple elements.
    $webhook = mod_goone_check_webhook();
    if($webhook['webhook'] == false) {
            goone_tokentest();
            $token = get_config('mod_goone', 'token');
            if (!empty($token)) {
                set_config('goone_api_secret', bulk_mod_goone_create_api_secret_key(), 'mod_goone');
                $curl = new curl();
                $curl->setHeader(array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$token,
                    'Api-Version: 2022-07-01',
                ));
                $result = $curl->post('https://gateway.go1.com/webhooks', '{
                                                       "name": "Open LMS webhook for course completion",
                                                       "url": "'.$CFG->wwwroot.'/mod/goone/payload.php",
                                                       "secret_key": "'.get_config('mod_goone', 'goone_api_secret').'",
                                                       "event_types": ["enrollment.complete"]
                                                        }');

                $logobj->message_info = $result;
                $logobj->timecreated = time();
                $DB->insert_record('goone_webhook_logs', $logobj);

                if ($curl->get_errno()) {
                    $logobj->message_info = 'Error:' . $curl->error;
                    $logobj->timecreated = time();
                    $DB->insert_record('goone_webhook_logs', $logobj);
                    echo $OUTPUT->header();
                        echo html_writer::tag('div', get_string('webhookcurlerror', 'mod_goone'), array('class'=>'alert alert-primary', 'role'=> 'alert'));
                    echo $OUTPUT->footer();
                    die;
                }
                echo $OUTPUT->header();
                    echo html_writer::tag('div', get_string('webhooknewcreatedgood', 'mod_goone'), array('class'=>'alert alert-primary', 'role'=> 'alert'));
                echo $OUTPUT->footer();
                die;
            } else {
                $logobj->message_info = get_string('tokennotdefined', 'mod_goone');
                $logobj->timecreated = time();
                $DB->insert_record('goone_webhook_logs', $logobj);
            }
    } else {
        $PAGE->set_pagelayout('admin');
        $PAGE->set_url('/mod/goone/payload.php?create_webhook=1');
        $PAGE->set_title(get_string('logs_webhook', 'mod_goone'));
        $PAGE->set_heading(get_string('logs_webhook', 'mod_goone'));
        echo $OUTPUT->header();
            echo html_writer::tag('div', get_string('webhookcreated', 'mod_goone'), array('class'=>'alert alert-primary', 'role'=> 'alert'));
        echo $OUTPUT->footer();
    }
}
