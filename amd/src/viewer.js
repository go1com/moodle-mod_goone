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
 * Javascript for view.php to dynamically fit content to window.
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

define(['jquery'], function($) {
    var viewer = {};

    viewer.urltogo = null;

    viewer.init = function() {
        $(document).ready(function () {
            var newheight = ($(window).height() - 50);
            if (newheight < 680 || isNaN(newheight)) {
                newheight = 680;
            }
            $("#content").height(newheight);
        });

        $(window).on('resize',function(){
            var newheight = ($(window).height() - 50);
            if (newheight < 680 || isNaN(newheight)) {
                newheight = 680;
            }
            $("#content").height(newheight);
        });
    };

    viewer.newwindow = function(urltogo) {
        setTimeout(function(){
            window.open(urltogo + '&win=1');}, 1500);
    };
    return viewer;
});
