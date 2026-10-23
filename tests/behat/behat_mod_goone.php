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
 * Steps for the GO1 activity that work on all supported Moodle versions.
 *
 * @package   mod_goone
 * @category  test
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file is always included from Behat.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Steps for the GO1 activity that work on all supported Moodle versions.
 *
 * Moodle 5.1 changed the activity chooser and the GO1 content browser opens a
 * second window. These steps hide both differences, thus one feature file runs
 * on Moodle 4.5, 5.0, 5.1, 5.2 and 5.3.
 *
 * @package   mod_goone
 * @category  test
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_goone extends behat_base {

    /**
     * Adds a GO1 content item from the open activity chooser.
     *
     * Moodle 4.5 and 5.0 open the activity form when you select the item.
     * Moodle 5.1 and later only select the item, and keep the chooser open. You
     * must then press Add. This step presses Add only when the button is there,
     * thus the same step works on all versions.
     *
     * @Given /^I add a GO1 content item from the activity chooser$/
     */
    public function i_add_a_go1_content_item_from_the_activity_chooser() {
        $option = "//*[@data-region='chooser-option-container']"
            . "//a[@data-action='add-chooser-option' and contains(@title, 'GO1')]";
        $this->execute('behat_general::i_click_on', [$option, 'xpath_element']);

        // Give the browser time to open the activity form, or to show the chooser
        // with the item selected.
        $this->getSession()->wait(2000);

        // Use the data-action of the button. Other hidden buttons on the page also
        // show the text "Add", for example the one of the message drawer.
        $add = "//button[@data-action = 'add-selected-chooser-option']";
        $button = $this->getSession()->getPage()->find('xpath', $add);
        if ($button && $button->isVisible()) {
            $this->execute('behat_general::i_click_on', [$add, 'xpath_element']);
        }
    }

    /**
     * Changes to the window of the GO1 content browser.
     *
     * The content browser opens in a second window. The core step "I switch to a
     * second window" does not wait for that window, thus it can stop with
     * "Undefined array key 1". This step waits for the window first.
     *
     * @Given /^I switch to the GO1 content browser window$/
     */
    public function i_switch_to_the_go1_content_browser_window() {
        $this->spin(
            function($context) {
                return count($context->getSession()->getWindowNames()) > 1;
            },
            [],
            self::get_extended_timeout(),
            new ExpectationException('The GO1 content browser window did not open.', $this->getSession())
        );

        $names = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow($names[1]);
    }
}
