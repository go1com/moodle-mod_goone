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

if ($ADMIN->fulltree) {

    require_once("$CFG->libdir/resourcelib.php");
    require_once(__DIR__.'/classes/settings/setting_statictext.php');
    require_once($CFG->dirroot.'/mod/goone/lib.php');

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    $name = 'goone/oauth_flow';
    $migratebutton = '<a class="btn btn-primary" target="_blank" href="'
        .goone_signup_url().'" role="button">'.get_string('oauth2_login', 'mod_goone').'</a>';
    $setting = new setting_statictext($name, $migratebutton);
    $settings->add($setting);

    $settings->add(new admin_setting_configtext('mod_goone/client_id',
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
}