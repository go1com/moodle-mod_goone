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
 * The "Create or repair webhook" button of the settings page.
 *
 * The button asks the user to agree, then sends the request and shows the result on the
 * settings page. It does not open a different page.
 *
 * @module    mod_goone/webhook_admin
 * @copyright 2025 onward Androgogic Pty Ltd
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str', 'core/notification', 'core/config'], function(Str, Notification, Config) {

    var webhookadmin = {};

    var SELECTOR = '[data-action="goone-create-webhook"]';

    /**
     * Send the request, and show the result on the settings page.
     *
     * @param {HTMLElement} button The button that the user pressed.
     * @return {Promise} Done when the result is shown.
     */
    webhookadmin.send = function(button) {
        button.disabled = true;

        return fetch(Config.wwwroot + '/mod/goone/payload.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                create_webhook: 1,
                sesskey: Config.sesskey,
            }).toString(),
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        }).then(function(data) {
            Notification.addNotification({
                message: data.message,
                type: data.success ? 'success' : 'error',
            });
            return data;
        }).catch(function() {
            // The answer did not arrive, or it was not JSON. The webhook did not change.
            return Str.get_string('createwebhookrequesterror', 'mod_goone').then(function(message) {
                Notification.addNotification({message: message, type: 'error'});
                return null;
            });
        }).finally(function() {
            button.disabled = false;
        });
    };

    webhookadmin.init = function() {
        document.addEventListener('click', function(e) {
            var button = e.target instanceof Element ? e.target.closest(SELECTOR) : null;
            if (!button) {
                return;
            }
            e.preventDefault();

            Str.get_strings([
                {key: 'createwebhookconfirmtitle', component: 'mod_goone'},
                {key: 'createwebhookconfirmbody', component: 'mod_goone'},
                {key: 'createwebhookconfirmgo', component: 'mod_goone'},
            ]).then(function(strings) {
                // saveCancelPromise refuses the promise when the user cancels. A cancel is
                // not a fault, thus it must not go to Notification.exception.
                return Notification.saveCancelPromise(strings[0], strings[1], strings[2], {
                    triggerElement: button,
                }).then(function() {
                    return webhookadmin.send(button);
                }).catch(function() {
                    return null;
                });
            }).catch(Notification.exception);
        });
    };

    return webhookadmin;
});
