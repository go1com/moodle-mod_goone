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
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */
require.config({
    paths: {
        "BsMultiselect": M.cfg.wwwroot + '/mod/goone/js/bootstrap-multiselect',
    }
});
define(['jquery', 'BsMultiselect', 'core/templates', 'core/str', 'core/notification', 'core/ajax',
 'core/modal_factory', 'core/modal_events'],
    function($, BsMultiselect, Templates, Str, Notification, Ajax, ModalFactory, ModalEvents) {
        var browser = {};
        browser.offSet = 0;
        browser.initial = 0;
        Str.get_string('expand', 'core', browser.cleartext);
        browser.init = function() {
            $(document).ready(function () {
                $('#sort').change(function() {
                    browser.changeFilter();
                });
                $('#provider').multiselect({
                    inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering:true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#tag').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering:true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#language').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering:true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#type').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering:true,
                    maxHeight: 350,
                    buttonWidth: '15rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: "Any",
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('.multiselect-reset').on('click',function () {
                        browser.changeFilter();
                });
                browser.loadResults($("#type").val(),$("#tag").val(),$("#language").val(),$("#provider").val(),
                    $("#keyword").val(),$("#sort").val(),browser.offSet);
            });

            $(document).on('click', '#lomodal', function() {
                var loname = $(this).data('name');
                var loid = $(this).data('id');

                   return ModalFactory.create({
                        title: loname,
                        type: ModalFactory.types.SAVE_CANCEL,
                        large: true
                    }).done(function(modal) {
                        Ajax.call([{
                            methodname: 'mod_goone_get_modal',
                            args: {loid: loid},
                            done: function(data) {
                                modal.setBody(Templates.render('mod_goone/modal', JSON.parse(data.result)));
                            },
                            fail: Notification.exception
                        }]);
                        modal.setSaveButtonText(Str.get_string('submit', 'core'));
                        modal.getRoot().on(ModalEvents.save, function() {
                            try {
                                window.opener.$('#id_loid').val(loid);
                                window.opener.$('#id_loname').val(loname);
                            }
                            catch (err) {}
                            window.close();
                            return false;
                        });
                        modal.show();
                    });
            });
            $("#load-more").click(function() {
                browser.offSet = browser.offSet + 20;
                browser.loadResults($("#type").val(),$("#tag").val(),$("#language").val(),$("#provider").val(),
                    $("#keyword").val(),$("#sort").val(),browser.offSet);
            });
            $('#keyword').keypress(function (e) {
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
            browser.loadResults($("#type").val(),$("#tag").val(),$("#language").val(),$("#provider").val(),
                $("#keyword").val(),$("#sort").val(),browser.offSet);
        };

        browser.loadResults = function(type, tag, language, provider, keyword, sort, offSet) {

            $("#spinny").show();
            Ajax.call([{
                methodname: 'mod_goone_get_hits',
                args: {type: type, tag: tag, language: language, provider: provider, keyword: keyword, sort: sort, offset: offSet},
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
                    if(type + '' != $("#type").val() + '' || tag + '' != $("#tag").val() + '' || language +
                    '' != $("#language").val() + '' || provider + '' != $("#provider").val() + '') {
                            browser.changeFilter();
                    }
                },
                fail: Notification.exception
            }]);
        };

        return browser;
    });
