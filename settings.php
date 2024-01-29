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
 * Settings file
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */
defined('MOODLE_INTERNAL') || die;

use mod_goone\settings\setting_statictext;
use mod_goone\settings\admin_setting_clientid_with_token_validator;

global $ADMIN, $USER, $CFG;
if ($ADMIN->fulltree) {

    require_once("$CFG->libdir/resourcelib.php");
    require_once(__DIR__.'/classes/settings/setting_statictext.php');
    require_once(__DIR__.'/classes/settings/admin_setting_clientid_with_token_validator.php');
    require_once($CFG->dirroot.'/mod/goone/lib.php');

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);
    // GO1 settings are available to selected admins (in $CFG->mod_goone_admin_users), or to ALL admins.
    if (empty($CFG->mod_goone_admin_users) || in_array($USER->username, $CFG->mod_goone_admin_users)) {


        $createwebhook = '<a class="btn btn-primary" target="_blank" href="'
        .$CFG->wwwroot. '/mod/goone/payload.php?create_webhook=1" role="button">' . get_string('createwebhook', 'mod_goone') . '</a>'; 
       
        $webhooklogs = '<br/><br/> <a class="btn btn-primary" target="_blank" href="'
        .$CFG->wwwroot. '/mod/goone/payload.php?logs_view=1" role="button">' . get_string('viewwebhooklogs', 'mod_goone') . '</a>';
       
        $migratebutton = '<br/><br/> <a class="btn btn-primary" target="_blank" href="'
            . goone_signup_url() . '" role="button">' . get_string('oauth2_login', 'mod_goone') . '</a>';
       
        $createitems = '<br/><br/> <a class="btn btn-primary" target="_blank" href="'
            .$CFG->wwwroot. '/mod/goone/bulk.php" role="button">' . get_string('createitems', 'mod_goone') . '</a>';

        $settings->add(new admin_setting_heading('createitems', get_string('createitemsheading', 'goone'), ''));
        $name = "goone/createitems_flow";
        $setting = new setting_statictext($name,  $createitems);
        $settings->add($setting);

        $name = "mod_goone/createwebhook_viewwebhooklogs";
        $settings->add(new admin_setting_heading('actionbuttons',get_string('actionbuttonsheading', 'goone'), ''));
        $setting = new setting_statictext($name,  $createwebhook.$migratebutton.$webhooklogs);
        $settings->add($setting);


        $name = 'goone/oauth_flow';
        $settings->add(new admin_setting_heading('credentialssettings',get_string('credentialssettingsheading', 'goone'), ''));
        $settings->add($setting);
        //$setting = new setting_statictext($name, $migratebutton);
        //$settings->add($setting);

        $settings->add(new admin_setting_clientid_with_token_validator('mod_goone/client_id',
            get_string('clientid', 'goone'), get_string('clientiddesc', 'goone'), ''));

        $settings->add(new admin_setting_configtext('mod_goone/client_secret',
            get_string('clientsecret', 'goone'), get_string('clientsecretdesc', 'goone'), ''));
        $settings->add(new admin_setting_heading('filterheading',
            get_string('filteroptionheading', 'goone'), ''));

        $settings->add(new admin_setting_configselect('mod_goone/filtersel',
            get_string('filtersel', 'goone'), get_string('filterseldesc', 'goone'), '0',
            array('0' => get_string('showallfilter', 'goone'),
                '1' => get_string('premiumfilter', 'goone'),
                '2' => get_string('collectionsfilter', 'goone')
            )
        ));

        $settings->add(new admin_setting_heading('partnersettings',
            get_string('partnersettingheading', 'goone'), ''));

        $settings->add(new admin_setting_configtext('mod_goone/partnerid',
            get_string('partnerid', 'goone'), get_string('partneriddesc', 'goone'), ''));

    } else {

        $settings->add(new admin_setting_heading('mod_goone/clientidadmin',
            get_string('clientid', 'goone'), get_config('mod_goone', 'client_id')));
        $settings->add(new admin_setting_heading('mod_goone/clientsecretadmin',
            get_string('clientsecret', 'goone'), get_config('mod_goone', 'client_secret')));

    }

    $settings->add(new admin_setting_heading('secretwebhook',
            get_string('payloadheading', 'mod_goone'), ''));

    if (empty(get_config('mod_goone', 'goone_api_secret'))) {
        $settings->add(new admin_setting_configtext('mod_goone/goone_api_secret',
            get_string('goone_api_secret', 'mod_goone'), get_string('goone_api_secretdesc', 'mod_goone'), ''));
    } else {

    $settingvalue = '<div class="form-item row" id="admin-goone_api_secret">
        <div class="form-label col-sm-3 text-sm-right">
            <label for="id_s_mod_goone_goone_api_secret">
             '.get_string('goone_api_secret', 'mod_goone').'
            </label>
            <span class="form-shortname d-block small text-muted">mod_goone | goone_api_secret</span>
        </div>
        <div class="form-setting col-sm-9">
            <div class="form-text defaultsnext">
                <p>'.get_config('mod_goone', 'goone_api_secret').'</p>
            </div>
            <div class="form-description mt-3">
                <p>'.get_string('goone_api_secretdesc', 'mod_goone').'</p>
            </div>
        </div>
    </div>';

        $setting = new setting_statictext('mod_goone/goone_api_secret', $settingvalue);
        $settings->add($setting);
    }

}
