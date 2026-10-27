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

    /**
     * Gives the address of a page of this plugin, for "I am on the ... page".
     *
     * @param string $page The name of the page.
     * @return moodle_url The address.
     * @throws Exception When the name is not known.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'bulk content browser':
                return new moodle_url('/mod/goone/bulk.php');
            default:
                throw new Exception("mod_goone does not know the page '$page'.");
        }
    }

    /**
     * Gives the xpath of the group that holds one filter of the content browser.
     *
     * The multiselect library puts the <select> in a span, and puts its own group
     * AFTER the select, inside that span. The group is thus a brother of the select
     * and not a parent of it. Every part of a filter is found from the id of the
     * select, which does not change.
     *
     * @param string $select The id of the select: type, tag, language, provider, topic
     *                       or duration.
     * @return string The xpath of the group.
     */
    protected function goone_filter_group(string $select): string {
        return "//select[@id='" . $select . "']"
            . "/following-sibling::div[contains(@class,'btn-group')][1]";
    }

    /**
     * Presses the button of one filter of the GO1 content browser.
     *
     * @When /^I open the GO1 filter "([^"]*)"$/
     * @param string $select The id of the select.
     */
    public function i_open_the_go1_filter(string $select) {
        $button = $this->goone_filter_group($select) . "//button[contains(@class,'multiselect')]";
        $this->execute('behat_general::i_click_on', [$button, 'xpath_element']);
        $this->getSession()->wait(1000);
    }

    /**
     * Checks that the menu of one filter is open.
     *
     * This is the check for the fault where the menu never opens, because the button
     * carries the attribute of an older Bootstrap than the one the theme loads.
     *
     * @Then /^the GO1 filter "([^"]*)" is open$/
     * @param string $select The id of the select.
     */
    public function the_go1_filter_is_open(string $select) {
        $menu = $this->goone_filter_group($select)
            . "//div[contains(@class,'multiselect-container')]";
        $node = $this->getSession()->getPage()->find('xpath', $menu);

        // Bootstrap puts the class "show" on the menu that it opens. Ask for that
        // class as well as for the look of the menu. A menu that is only visible,
        // without the class, is a menu that Bootstrap never opened and that some
        // other style left on the page.
        $state = $this->evaluate_script(
            "(function () { var e = document.querySelector('#" . $select . "')"
            . ".parentNode.querySelector('.multiselect-container');"
            . " if (!e) { return 'no menu'; }"
            . " return e.className + ' | display=' + getComputedStyle(e).display; })()");

        if (!$node || !$node->isVisible() || strpos((string)$state, 'show') === false) {
            throw new ExpectationException(
                "The menu of the GO1 filter '$select' did not open. The menu is: $state",
                $this->getSession());
        }
    }

    /**
     * Checks that the words of a filter start at the left.
     *
     * The library lines up its button with a Bootstrap class that belongs to one
     * version only: text-left on Bootstrap 4 and text-start on Bootstrap 5. Both
     * carry !important. The plugin switches that option off and sets the alignment
     * in its own style, thus every version reads the same way. This step holds
     * that result.
     *
     * @Then /^the GO1 filter "([^"]*)" is lined up at the left$/
     * @param string $select The id of the select.
     */
    public function the_go1_filter_is_lined_up_at_the_left(string $select) {
        $found = $this->evaluate_script(
            '(function () {'
            . ' var w = document.querySelector("#' . $select . '").parentNode;'
            . ' var b = w.querySelector("button.multiselect");'
            . ' var m = w.querySelector(".multiselect-container");'
            . ' var o = w.querySelector(".multiselect-option");'
            . ' return ["button=" + (b ? getComputedStyle(b).textAlign : "none"),'
            . ' "menu=" + (m ? getComputedStyle(m).textAlign : "none"),'
            . ' "option=" + (o ? getComputedStyle(o).textAlign : "none")].join(" "); })()');

        if (strpos((string)$found, 'center') !== false || strpos((string)$found, 'right') !== false) {
            throw new ExpectationException(
                "The GO1 filter '$select' does not start at the left: $found",
                $this->getSession());
        }
    }

    /**
     * Presses one option in the open menu of a filter.
     *
     * @When /^I choose the GO1 filter option "([^"]*)" of "([^"]*)"$/
     * @param string $option The words of the option.
     * @param string $select The id of the select.
     */
    public function i_choose_the_go1_filter_option(string $option, string $select) {
        $item = $this->goone_filter_group($select)
            . "//button[contains(@class,'multiselect-option')][contains(normalize-space(.), '"
            . $option . "')]";
        $this->execute('behat_general::i_click_on', [$item, 'xpath_element']);
        $this->getSession()->wait(2000);
    }

    /**
     * Checks what the hidden select of a filter holds.
     *
     * The value of the select is what the plugin sends to GO1, thus this checks that
     * the press reached the form and not only the look of the menu.
     *
     * @Then /^the GO1 filter "([^"]*)" holds "([^"]*)"$/
     * @param string $select The id of the select.
     * @param string $expected The value, or the word "nothing".
     */
    public function the_go1_filter_holds(string $select, string $expected) {
        // Read every chosen option. The value of a select that takes more than one
        // option gives only the FIRST chosen option, thus it hides the others.
        // evaluate_script takes one expression, thus no statement may be used here.
        $value = $this->evaluate_script(
            "document.getElementById('" . $select . "') ? Array.prototype.map.call("
            . "document.getElementById('" . $select . "').selectedOptions, function (o) {"
            . " return o.value; }).join(',') : ''");
        $held = (string)$value;
        if ($expected === 'nothing') {
            if ($held !== '') {
                throw new ExpectationException(
                    "The GO1 filter '$select' holds '$held', and nothing was expected.",
                    $this->getSession());
            }
            return;
        }
        if (strpos($held, $expected) === false) {
            throw new ExpectationException(
                "The GO1 filter '$select' holds '$held', and '$expected' was expected.",
                $this->getSession());
        }
    }

    /**
     * Presses the control that clears one filter.
     *
     * @When /^I clear the GO1 filter "([^"]*)"$/
     * @param string $select The id of the select.
     */
    public function i_clear_the_go1_filter(string $select) {
        $reset = $this->goone_filter_group($select) . "//*[contains(@class,'multiselect-reset')]"
            . "//button | " . $this->goone_filter_group($select)
            . "//button[contains(@class,'multiselect-reset')]";
        $this->execute('behat_general::i_click_on', [$reset, 'xpath_element']);
        $this->getSession()->wait(2000);
    }
}
