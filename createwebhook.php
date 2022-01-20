<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/createwebhook.php');
$PAGE->set_title(get_string('contentbrowser', 'goone'));
$PAGE->set_heading(get_string('contentbrowser', 'goone'));

echo $OUTPUT->header();
if (!goone_tokentest()) {
    echo $OUTPUT->notification(get_string('connectionerror', 'goone'), 'notifyproblem');
}

$curl = curl_init();

curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.go1.com/v2/webhooks',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
  "enrollment_create": false,
  "enrollment_delete": false,
  "enrollment_update": true,
  "lo_create": false,
  "lo_delete": false,
  "lo_update": false,
  "enabled": false,
  "secret_key": "our_little_secret",
  "url": '.$CFG->wwwroot.'/mod/goone/webhook.php",
  "user_create": false,
  "user_delete": false,
  "user_update": false,
  "content_update": false,
  "content_decommission": false
}',
        CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.get_config('mod_goone', 'token'),
                'Content-Type: application/json'
        ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
echo $OUTPUT->footer();

