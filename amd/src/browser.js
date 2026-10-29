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
 * Javascript for browser.php functions.
 *
 * @module    mod_goone/browser
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 * @author    Kirill Astashov <kirill.astashov@androgogic.com>
 */
require.config({
    paths: {
        "BsMultiselect": M.cfg.wwwroot + '/mod/goone/js/bootstrap-multiselect',
    }
});
define(['jquery', 'BsMultiselect', 'core/templates', 'core/str', 'core/notification', 'core/ajax',
 'core/modal_save_cancel', 'core/modal_events'],
    function($, BsMultiselect, Templates, Str, Notification, Ajax, ModalSaveCancel, ModalEvents) {
        var browser = {};

/*
 * The multiselect library writes its button for one Bootstrap version only.
 * Version 2.0.0 writes data-bs-toggle, which Bootstrap 5 reads. Moodle 4.5 loads
 * Bootstrap 4, which reads data-toggle and leaves such a button shut.
 *
 * The button below carries BOTH attributes and the button class of both versions.
 * Each Bootstrap reads the attribute it knows and ignores the other, thus one
 * library serves Moodle 4.5 and Moodle 5.0 and later. Remove nothing here without
 * running tests/behat/content_browser_filters.feature on 4.5 AND on 5.3.
 */
// The library puts a Bootstrap class on the button to line up its words:
// text-center by default, and text-start for 'left'. Both text-start and
// text-left belong to ONE Bootstrap version only, and both carry !important,
// thus neither can be corrected with a style rule. The option is switched off
// with false, which adds no class at all, and the style rule in the template
// lines the words up at the left in every version.
var GOONE_BUTTON = '<button type="button"'
    + ' class="multiselect dropdown-toggle custom-select form-select"'
    + ' data-toggle="dropdown" data-bs-toggle="dropdown">'
    + '<span class="multiselect-selected-text"></span></button>';

        browser.offSet = 0;
        browser.initial = 0;
        browser.contextid = 0;

        /**
         * Reads the selected values of a multiple select.
         *
         * jQuery gives null when the user selects nothing, and when the select has
         * no options. The second case occurs when the GO1 API does not answer. A
         * call to .map() on null stops the page with a JavaScript error.
         *
         * @param {String} selector The selector of the select element.
         * @return {Array} The selected values, or an empty array.
         */
        browser.selected = function(selector) {
            return $(selector).val() || [];
        };
        browser.init = function(contextid) {
            browser.contextid = contextid;
            // The multiselect filters need the text of their reset option, thus start when the string and the page are ready.
            Promise.all([Str.getString('clear', 'core'), $.ready]).then(function(strings) {
                browser.cleartext = strings[0];
                $('#sort').change(function() {
                    browser.changeFilter();
                });
                $('#provider').multiselect({templates: {button: GOONE_BUTTON}, buttonTextAlignment: false,
                    inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#tag').multiselect({templates: {button: GOONE_BUTTON}, buttonTextAlignment: false,inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#language').multiselect({templates: {button: GOONE_BUTTON}, buttonTextAlignment: false,inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#type').multiselect({templates: {button: GOONE_BUTTON}, buttonTextAlignment: false,inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('.multiselect-reset').on('click', function() {
                        browser.changeFilter();
                });
                var fixedTags = browser.selected("#tag");
                browser.loadResults(browser.selected("#type"), fixedTags, browser.selected("#language"),
                    browser.selected("#provider"),
                    $("#keyword").val(), $("#sort").val(), browser.offSet);
                return;
            }).catch(Notification.exception);

            $(document).on('click', '.lomodal', function() {
                var loname = $(this).data('name');
                var loid = $(this).data('id');

                   return ModalSaveCancel.create({
                        title: loname,
                        large: true
                    }).then(function(modal) {
                        Ajax.call([{
                            methodname: 'mod_goone_get_modal',
                            args: {loid: loid, contextid: browser.contextid},
                            done: function(data) {
                                modal.setBody(Templates.render('mod_goone/modal', JSON.parse(data.result)));
                            },
                            fail: Notification.exception
                        }]);
                        modal.setSaveButtonText(Str.get_string('submit', 'core'));
                        modal.getRoot().on(ModalEvents.save, function() {
                            try {
                                window.opener.document.getElementById('id_loid').value = loid;
                                window.opener.document.getElementById('id_loname').value = loname;
                            } catch (err) {
                                // The opener window is not available, thus there is nothing to update.
                            }
                            window.close();
                            return false;
                        });
                        modal.show();
                        return modal;
                    }).catch(Notification.exception);
            });
            $("#load-more").click(function() {
                browser.offSet = browser.offSet + 20;
                var fixedTags = browser.selected("#tag");
                browser.loadResults(browser.selected("#type"), fixedTags, browser.selected("#language"),
                    browser.selected("#provider"),
                    $("#keyword").val(), $("#sort").val(), browser.offSet);
            });
            $('#keyword').keypress(function(e) {
                if (e.which == 13) {
                    $('#keyword').submit();
                    browser.changeFilter();
                }
            });
        };

        browser.changeFilter = function() {
            if (browser.initial == 0) {
                $("#sort").val("relevance");
                browser.initial = 1;
            }
            browser.offSet = 0;
            $("#goone-browser-results").html("");
            var fixedTags = browser.selected("#tag");
            browser.loadResults(browser.selected("#type"), fixedTags, browser.selected("#language"),
                    browser.selected("#provider"),
                $("#keyword").val(), $("#sort").val(), browser.offSet);
        };

        browser.loadResults = function(type, tag, language, provider, keyword, sort, offSet) {

            $("#spinny").show();
            Ajax.call([{
                methodname: 'mod_goone_get_hits',
                args: {type: type, tag: tag, language: language, provider: provider, keyword: keyword, sort: sort,
                    offset: offSet, contextid: browser.contextid},
                done: function(data) {
                    $("#goone-browser-results").append(data.result);
                    $("#spinny").hide();
                    $("#latest-go1-result h2").html($(".go1-result").last().html());
                    var total = parseInt($("#total-hits").text(), 10);

                    if (total <= 20 || offSet >= total - 20) {
                        $("#load-more-div").hide();
                        $("#end-of-results").show();
                    } else {
                        $("#load-more-div").show();
                        $("#end-of-results").hide();
                    }
                    var fixedTags = browser.selected("#tag");
                    if (type + '' != browser.selected("#type") + '' || tag + '' != fixedTags + ''
                        || language + '' != browser.selected("#language") + ''
                        || provider + '' != browser.selected("#provider") + '') {
                            browser.changeFilter();
                    }
                },
                fail: Notification.exception
            }]);
        };

        return browser;
    });
