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
 * @copyright   2022 Esteban Echavarria <esteban.echavarria@openlms.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require.config({
    paths: {
        "BsMultiselect": M.cfg.wwwroot + '/mod/goone/js/bootstrap-multiselect',
    }
});
define(['jquery', 'BsMultiselect', 'core/templates', 'core/str', 'core/notification', 'core/ajax',
 'core/modal', 'core/modal_cancel', 'core/modal_events'],
    function($, BsMultiselect, Templates, Str, Notification, Ajax, Modal, ModalCancel, ModalEvents) {
        var browser = {};
        browser.offSet = 0;
        browser.initial = 0;
        browser.init = function() {
            // The multiselect filters need the text of their reset option, thus start when the string and the page are ready.
            Promise.all([Str.getString('clear', 'core'), $.ready]).then(function(strings) {
                browser.cleartext = strings[0];
                $('#sort').change(function() {
                    browser.changeFilter();
                });
                $('#provider').multiselect({
                    inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '11rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: M.util.get_string('providers', 'mod_goone'),
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#topic').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '11rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: M.util.get_string('topics', 'mod_goone'),
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#duration').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '11rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: M.util.get_string('duration', 'mod_goone'),
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#language').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '11rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: M.util.get_string('language', 'mod_goone'),
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('#type').multiselect({inheritClass: true,
                    enableFiltering: true,
                    enableCaseInsensitiveFiltering: true,
                    maxHeight: 350,
                    buttonWidth: '11rem',
                    includeResetOption: true,
                    resetText: browser.cleartext,
                    nonSelectedText: M.util.get_string('contenttype', 'mod_goone'),
                    onChange: function() {
                        browser.changeFilter();
                    }});
                $('.multiselect-reset').on('click', function() {
                        browser.changeFilter();
                });
                browser.loadResults($("#type").val(), $("#topic").val(), $("#language").val(), $("#provider").val(),
                    $("#keyword").val(), $("#sort").val(), browser.offSet, false, $("#duration").val());
                return;
            }).catch(Notification.exception);
            $(document).on('click', '.lomodal', function() {
                var loname = $(this).data('name');
                var loid = $(this).data('id');

                   return Modal.create({
                        title: loname,
                        large: true
                    }).then(function(modal) {
                        Ajax.call([{
                            methodname: 'bulk_mod_goone_get_modal',
                            args: {loid: loid},
                            done: function(data) {
                                modal.setBody(Templates.render('mod_goone/modal_bulk', JSON.parse(data.result)));
                            },
                            fail: Notification.exception
                        }]);
                       // Modal.setSaveButtonText(Str.get_string('submit', 'core'));
                        modal.getRoot().on(ModalEvents.save, function() {
                            try {
                                window.opener.$('#id_loid').val(loid);
                                window.opener.$('#id_loname').val(loname);
                            } catch (err) {
                                // The opener window is not available, thus there is nothing to update.
                            }
                            window.close();
                            return false;
                        });
                        modal.show();
                        return;
                    });
            });
            $("#load-more").click(function() {
                browser.offSet = browser.offSet + 20;
                browser.loadResults($("#type").val(), $("#topic").val(), $("#language").val(), $("#provider").val(),
                    $("#keyword").val(), $("#sort").val(), browser.offSet, true, $("#duration").val());
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
            browser.loadResults($("#type").val(), $("#topic").val(), $("#language").val(), $("#provider").val(),
                $("#keyword").val(), $("#sort").val(), browser.offSet, false, $("#duration").val());
        };
        browser.loadResults = function(type, topic, language, provider, keyword, sort, offSet, loadmore, duration) {
            $("#spinny").show();
            Ajax.call([{
                methodname: 'bulk_mod_goone_get_hits',
                args: {
                    type: type, topic: topic, language: language, provider: provider, keyword: keyword, sort: sort,
                    offset: offSet, loadmore: loadmore, duration: duration
                },
                done: function(data) {
                    if (loadmore === true) {
                        $('#hits-table-results tr:last').after(data.result);
                    } else {
                        $("#goone-browser-results").append(data.result);
                    }
                    $("#spinny").hide();
                    $("#latest-go1-result h4").html($(".go1-result").last().html());
                    var total = parseInt($("#total-hits").text(), 10);

                    if (total <= 20 || offSet >= total - 20) {
                        $("#load-more-div").hide();
                        $("#end-of-results").show();
                    } else {
                        $("#load-more-div").show();
                        $("#end-of-results").hide();
                    }
                    if (type + '' != $("#type").val() + '' || topic + '' != $("#topic").val() + '' || language +
                    '' != $("#language").val() + '' || provider + '' != $("#provider").val() + '') {
                            browser.changeFilter();
                    }
                },
                fail: Notification.exception
            }]);
        };
        $(document).on('click', '#addexisting', function() {
                var elementseleted = false;
                var countitemsselected = 0;
                $('#goone-browser-results div.checkbox-cell input').each(function() {
                  if ($(this).is(':checked')) {
                    elementseleted = true;
                    countitemsselected++;
                  }
                });

                if (elementseleted === false) {
                    // Message that no items selected
                    return ModalCancel.create({
                        title: M.util.get_string('searchcoursemodaltitle', 'mod_goone'),
                        large: false,
                    }).then(function(modal) {
                        modal.setBody(M.util.get_string('noitemsselected', 'mod_goone'));
                        modal.show();
                        return modal;
                    }).then(function(modal) {
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return;
                    });
                }

                if (countitemsselected >= 200) {
                    // Message limit of items reached
                    return ModalCancel.create({
                        title: M.util.get_string('searchcoursemodaltitle', 'mod_goone'),
                        large: false,
                    }).then(function(modal) {
                        modal.setBody(M.util.get_string('toomanyitems', 'mod_goone'));
                        modal.show();
                        return modal;
                    }).then(function(modal) {
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return;
                    });
                }

                var data = {
                    result: '{}',
                };
               return Modal.create({
                    title: M.util.get_string('searchcoursemodaltitle', 'mod_goone'),
                    large: true,
                }).then(function(modal) {
                    modal.setBody(Templates.render('mod_goone/modalcoursesearch_bulk', JSON.parse(data.result)));
                    modal.setFooter('<div id="searchcoursefooter"></div>');
                    modal.show();
                    return modal;
                }).then(function(modal) {
                        // Handle hidden event.
                        modal.getRoot().on(ModalEvents.hidden, function() {
                        // Destroy when hidden.
                        modal.destroy();
                    });
                    return;
                });
        });
        $(document).on('click', '#createcourses', function() {
            var selecteditems = '';
            var selecteditemsids = '';
            var dataobj = '';
            var inputdata = '';
            var countitemsselected = 0;
                $('#goone-browser-results div.checkbox-cell input').each(function() {
                  if ($(this).is(':checked')) {
                     inputdata = $(this).data();
                     selecteditems += '{"name": "' + inputdata.name + '"},';
                     selecteditemsids += inputdata.id + ',';
                     countitemsselected++;
                  }
                });
                if (countitemsselected == 0) {
                    // Message no items selected
                    return ModalCancel.create({
                        title: M.util.get_string('createcoursemodaltitle', 'mod_goone'),
                        large: false,
                    }).then(function(modal) {
                        modal.setBody(M.util.get_string('noitemsselected', 'mod_goone'));
                        modal.show();
                        return modal;
                    }).then(function(modal) {
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return;
                    });
                } else if (countitemsselected >= 200) {
                    // Message limit of items reached
                    return ModalCancel.create({
                        title: M.util.get_string('createcoursemodaltitle', 'mod_goone'),
                        large: false,
                    }).then(function(modal) {
                        modal.setBody(M.util.get_string('toomanyitems', 'mod_goone'));
                        modal.show();
                        return modal;
                    }).then(function(modal) {
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return;
                    });
                }
                selecteditems = selecteditems.replace(/,\s*$/, "");
                selecteditemsids = selecteditemsids.replace(/,\s*$/, "");
                if (selecteditems.length > 0) {
                    dataobj = '{"selected_items":[' + selecteditems + '],"has_selected_items":true,"totalelements":' +
                        countitemsselected + ',"items_ids":"' + selecteditemsids + '"}';
                } else {
                    dataobj = '{"selected_items":[],"has_selected_items":true}';
                }
                var data = {
                    result: dataobj,
                };
               return Modal.create({
                    title: M.util.get_string('createcoursemodaltitle', 'mod_goone'),
                    large: true,
                }).then(function(modal) {
                    modal.setBody(Templates.render('mod_goone/modalcreatecourses_bulk', JSON.parse(data.result)));
                    modal.setFooter('<button class="btn btn-primary" id="save_create_selected_items">' +
                        M.util.get_string('save', 'moodle') + '</button>');
                    modal.show();
                    return modal;
                }).then(function(modal) {
                        // Handle hidden event.
                        modal.getRoot().on(ModalEvents.hidden, function() {
                        // Destroy when hidden.
                        modal.destroy();
                    });
                    return;
                });
        });
        // Create single course or multiple courses
        // 1 Course per item
       // 2 Single course all items
        $(document).on('click', '#save_create_selected_items', function() {
           var optionselected = $('.form-control-goone[name="createcourse"]:checked').val();
           var selecteditemsids = $('#selected_items_ids').val();
           var coursename = $('#multi-item-course-name').val();

           $('.container.selected_items').html('<div id="spinny" class="preloader w-100 text-center" style="display:block;">' +
               '<img src="' + M.cfg.wwwroot + '/mod/goone/pix/spinner.gif"></div><br/><span style="text-align: center;">' +
               M.util.get_string('processingitems', 'mod_goone') + '</span>');
           if (optionselected == 1) {
                // 1 Per item
                Ajax.call([{
                    methodname: 'bulk_mod_goone_process_course_per_item',
                    args: {items: selecteditemsids},
                    done: function(data) {
                        $('.container.selected_items').html(data.result);
                    },
                    fail: Notification.exception
                }]);
           } else if (optionselected == 2) {
                // 2 Single course all items
                if (coursename == "") {
                    // Message that no course name
                    ModalCancel.create({
                        title: M.util.get_string('createcoursemodaltitle', 'mod_goone'),
                        large: false,
                    }).then(function(modal) {
                        modal.setBody(M.util.get_string('coursenameempty', 'mod_goone'));
                        modal.show();
                        return modal;
                    }).then(function(modal) {
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return;
                    }).catch(Notification.exception);
                    return;
                }

                Ajax.call([{
                    methodname: 'bulk_mod_goone_process_course_single_course',
                    args: {items: selecteditemsids, coursename: coursename},
                    done: function(data) {
                        $('.container.selected_items').html(data.result);
                    },
                    fail: Notification.exception
                }]);
           }
        });

        $(document).on('click', '.form-control-goone[name="createcourse"]', function() {
            if ($(this).val() == 2) {
                $(this).parent().append('<div id="multi-item-course-name-div"><br/><label>' +
                    M.util.get_string('coursename', 'mod_goone') + '</label><br>' +
                    '<input type="text" name="multi-item-course-name" id="multi-item-course-name"></div>');
            } else {
                $("#multi-item-course-name").remove();
            }
        });
        // Add activity or activities
        $(document).on('click', '#save_adding_to_existing_course', function() {
            var selecteditemsids = '';
            $('#goone-browser-results div.checkbox-cell input').each(function() {
              if ($(this).is(':checked')) {
                 var inputdata = $(this).data();
                 selecteditemsids += inputdata.id + ',';
              }
            });
           var coursesection = $(".list-group-flush input[type='radio']:checked").val();
           selecteditemsids = selecteditemsids.replace(/,\s*$/, "");
           if (coursesection !== '' && typeof (coursesection) !== 'undefined') {
                $('#resultcoursesearch').html('<div id="spinny" class="preloader w-100 text-center" style="display:block;">' +
                    '<img src="' + M.cfg.wwwroot + '/mod/goone/pix/spinner.gif"></div><br/><span style="text-align: center;">' +
                    M.util.get_string('processingitems', 'mod_goone') + '</span>');
                Ajax.call([{
                    methodname: 'bulk_mod_goone_process_add_to_existing_course',
                    args: {'course_section': coursesection, selecteditemsids: selecteditemsids},
                    done: function(data) {
                        $('#resultcoursesearch').html(data.result);
                    },
                    fail: Notification.exception
                }]);
           } else {
                // Message that no topic selected
                ModalCancel.create({
                    title: M.util.get_string('createcoursemodaltitle', 'mod_goone'),
                    large: false,
                }).then(function(modal) {
                    modal.setBody(M.util.get_string('notopicselected', 'mod_goone'));
                    modal.show();
                    return modal;
                }).then(function(modal) {
                        modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    return;
                }).catch(Notification.exception);
           }
        });
        $(document).on('click', '#coursesearchbutton', function() {
            var searchstring = $('#coursesearchinput').val();
            if (searchstring.length >= 3) {
                Ajax.call([{
                    methodname: 'bulk_mod_goone_get_course_search_result',
                    args: {search: searchstring},
                    done: function(data) {
                        Templates.render('mod_goone/searchcourseresult_bulk', JSON.parse(data.result)).then(function(html) {
                            $('#resultcoursesearch').replaceWith(html);
                            return;
                        }).catch(Notification.exception);
                        var savestring = M.util.get_string('save', 'moodle');
                        $('#searchcoursefooter').html('<button class="btn btn-primary" id="save_adding_to_existing_course">' +
                            savestring + '</button>');
                    },
                    fail: Notification.exception
                }]);
            }
        });
        return browser;
    });