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
 * Language file
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'GO1';
$string['pluginadministration'] = 'GO1 admin';
$string['modulename'] = 'GO1 content item';
$string['modulenameplural'] = 'GO1';
$string['modulename_help'] = 'Use GO1’s library of learning resources to help create your own custom training content, direct from your Moodle instance. </br>With GO1 content you have instant access to a growing library containing thousands of learning resources, from the best content providers around the world. Connecting Moodle and GO1 enables you to search the GO1 library for the right content for your training needs and easily add it to your chosen course for a simple course delivery experience. </br>GO1 has the world’s largest marketplace of training, offering you: </br><ul><li>a huge range of various types of learning resources including documents, interactive files, videos, full-length courses, and more</li><li>a comprehensive off-the-shelf library of professional development training covering areas of leadership, HR, and communications, as well as compliance training to meet your region\'s specific needs.</li></ul>';
$string['crontask'] = 'Background processing for GO1 module';
$string['userpreferences'] = 'User preferences';
$string['clientsecret'] = 'Client secret';
$string['clientsecretdesc'] = 'GO1 Oauth client secret';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'GO1 Oauth client ID';
$string['oauth2_login'] = 'Retrieve GO1 credentials';
$string['connectionerroradmin'] = 'Failed to connect to GO1, please check your credentials.';
$string['filteroptionheading'] = 'Content settings';
$string['showallfilterdesc'] = 'Show the entire GO1 Marketplace in the Content Browser when adding an activity. This overrides the other settings.';
$string['showallfilter'] = 'Show everything';
$string['premiumfilterdesc'] = 'Show only your Premium Subscription content when adding an activity.';
$string['premiumfilter'] = 'Show Premium Subscription';
$string['collectionsfilterdesc'] = 'To restrict settings further to a pre-curated list you must login to GO1 and save a selection first. This overrides the other settings.';
$string['collectionsfilter'] = 'Restrict to Custom Selection';
$string['filtersel'] = 'GO1 Content Browser filter';
$string['filterseldesc'] = 'Select GO1 Content Browser filter</br><ul><li><b>Show Everything</b> Show the entire GO1 Marketplace in the content browser when adding an activity.</li><li><b>Show Premium Subscription</b>  Show only your Premium Subscription content when adding an activity.</li><li><b> Restrict to Custom Selection</b> To restrict settings further to a pre-curated list you must login to GO1 and save a selection first.</li></ul>';
$string['selectedloid'] = 'Selected GO1 content ID';
$string['selectedloname'] = 'Selected GO1 content name';
$string['contentbrowser'] = 'Content Browser';
$string['nonameselected'] = 'Please supply a name for this activity';
$string['opennewwin'] = 'The GO1 activity has been launched in a new window.';
$string['lobrowser'] = 'Open GO1 Content Browser';
$string['clearselection'] = 'Clear selection';
$string['completionlo'] = 'Student must finish the Learning Object to complete it';
$string['noloselected'] = 'Please use the Content Browser to select GO1 content';
$string['connectionerror'] = 'Failed to connect to GO1, please notify your site administrator.';
$string['connectionsuccess'] = 'Successfully connected to GO1!';
$string['completionwatchlesson_help'] = 'The help text';
$string['cmidincorrect'] = 'Course module ID is incorrect';
$string['cmincorrect'] = 'Course module is incorrect';
$string['courseincorrect'] = 'Course is misconfigured';
$string['reportmenu'] = 'Report';
$string['nolessonadded'] = 'No lessons added on this activity';
$string['cachedef_scorm12datamodel'] = 'SCORM_12 JS MOD';
$string['included'] = 'Included';
$string['free'] = 'Free';
$string['unknownlanguage'] = 'Uknown language';
$string['results'] = "Results";
$string['loadmore'] = "Load more";
$string['endofresults'] = "End of results.";
$string['filters'] = "Filters";
$string['keyword'] = "Keyword search";
$string['close'] = "Close";
$string['select'] = "Select";
$string['tag'] = "Tag";
$string['language'] = "Language";
$string['type'] = "Type";
$string['providers'] = "Providers";
$string['relevance'] = "Relevance";
$string['popularity'] = "Popularity";
$string['price'] = "Price";
$string['title'] = "Title";
$string['created'] = "Created";
$string['overview'] = "Overview";
$string['learning'] = "Learning";
$string['description'] = 'Description';
$string['lodownloaderror'] = 'Error downloading learning object, please check your GO1 credentials';
$string['go1apierror'] = 'The GO1 API request failed. Please notify your site administrator.';
$string['privacy:metadata:goone_completion:gooneid'] = 'The ID of the GO1 Course activity';
$string['privacy:metadata:goone_completion:userid'] = 'The ID of a user who has interacted with a GO1 course';
$string['privacy:metadata:goone_completion:location'] = 'The users last recorded position in a GO1 Course';
$string['privacy:metadata:goone_completion:completed'] = 'The users completion status of a GO1 Course';
$string['privacy:metadata:goone_completion:timemodified'] = 'The timestamp indicating when a user last recorded an interaction with the GO1 Course';
$string['privacy:metadata:goone_completion'] = 'Information about a users progress and completion recrods for a GO1 Course';
$string['partnerid'] = "Partner ID";
$string['partneriddesc'] = "For use by GO1 partners only.";
$string['partnersettingheading'] = "GO1 partner settings";
$string['goone:addinstance'] = "Add or update a GO1 conent item";
$string['goone:view'] = 'View GO1 module';
$string['completiondetail:submit'] = 'Finish the learning object';
// Bulk strings
$string['pagetitle'] = "Go1 Content";
$string['contenttype'] = "Content type";
$string['contentbank'] = 'Content bank';
$string['addexisting'] = 'Add to existing course';
$string['createcourses'] = 'Create new courses';
$string['searchcoursemodaltitle'] =  'Search courses';
$string['createcoursemodaltitle'] =  'Create course(s)';
$string['searchresulttitle'] =  'Search result:';
$string['coursesnotfound'] =  'Courses not found';
$string['createsinglecourse'] = "Single course";
$string['createcourseperitem'] = "Course per item";
$string['selecteditemsresume'] = "Selected items:";
$string['actiontotake'] = "Create selected content as:";
$string['coursesnotselected'] = "Items not selected";
$string['createprocesssucess'] = "Course and activity created";
$string['createprocessactivityfailed'] = "Error creating activity";
$string['createprocesscoursefailed'] = "Error creating course";
$string['noitemsselected'] = "No items selected";
$string['notopicselected'] = "No topic selected";
$string['provider'] = "Provider";
$string['duration'] = "Duration";
$string['topics'] = "Topics";
$string['coursename'] = "Course name:";
$string['coursenameempty'] = "Course name can't be empty";
$string['toomanyitems'] = "Too many items selected, limit (20) exceeded";
$string['processingitems'] = "Processing your selected items, please wait.<br/>Once the created/updated items are displayed, you can close this window (X).";
$string['goone:viewbulkcontent'] = "View bulk content page";
$string['connectionerror'] = "Connection error, please refresh the page again.";
$string['goone_api_secret'] = "Webhook Secret";
$string['goone_api_secretdesc'] = "Secret created to handle the webhook api calls.";
$string['logs_webhook'] = "Webhook logs";
$string['usernotfound'] = "User not found";
$string['useralreadymapped'] = 'GO1 user {$a->gooneuserid} matches Moodle user {$a->userid}, but that Moodle user is already mapped to GO1 user {$a->mappedgooneuserid}. No user was selected.';
$string['useremailnotunique'] = 'More than one Moodle user has the email of GO1 user {$a}. No user was selected.';
$string['signaturenotmatching'] = "Signature not matching";
$string['signaturenotset'] = "Signature not found in request";
$string['signaturenotconfig'] = "Signature not found in Moodle config";
$string['webhookcreated'] = "Client already have a webhook created!";
$string['payloadheading'] = "Secret key used on webhook to sign the requests";
$string['tokennotdefined'] = "Token not defined";
$string['createwebhook'] = "Create webhook";
$string['viewwebhooklogs'] = "View webhook logs";
$string['sort'] = "Sort";
$string['createitems'] = "Create activities";
$string['webhookcurlerror'] = "Webhook creation request error";
$string['webhooknewcreatedgood'] = "Webhook creted successfully";
$string['recordrequestednotfound'] = "Record requested not found";
$string['activitynotfound'] = "Activity not found";
$string['createitemsheading'] = "Create activities, bulk actions";
$string['actionbuttonsheading'] = "Action buttons";
$string['credentialssettingsheading'] = "Go1 API credentials";
$string['othersettingsheading'] = 'Other settings';
$string['resetcompletion'] = 'On course reset';
$string['resetcompletiondesc'] = 'What to do with the GO1 progress of the learners when a teacher resets a course.

This setting gives the value that the course reset form shows first. A teacher can change it for each reset.

<b>Do not reset GO1 progress</b> keeps the record of each learner. Note that a reset still deletes the completion data of Moodle. The two records are then different: the activity shows as not complete, but the GO1 record still shows the learning object as finished. A learner who opens the activity again becomes complete at once, without looking at the content.

<b>Reset GO1 progress</b> removes the GO1 record of each learner in that course. A learner must then finish the learning object again.';
$string['resetcompletionkeep'] = 'Do not reset GO1 progress';
$string['resetcompletionremove'] = 'Reset GO1 progress';
$string['resetcompletionformlabel'] = 'Reset GO1 progress';
