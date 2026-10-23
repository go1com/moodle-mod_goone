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
 * Tests the Bootstrap compatibility rules of the content browser templates.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_goone;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the Bootstrap compatibility rules of the content browser templates.
 *
 * Moodle 4.5 uses Bootstrap 4. Moodle 5.0 and later use Bootstrap 5, which
 * removed .form-row, .form-group and the default badge colour. Without the
 * compatibility rules, the filter bar shows the four filters one below the
 * other, and the content type badge is white on a light background.
 *
 * These tests make sure that the rules stay in the templates.
 *
 * @package   mod_goone
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browser_template_test extends advanced_testcase {

    /**
     * Gives the name of each template that shows the filter bar.
     *
     * @return array[] The template names.
     */
    public static function filter_template_provider(): array {
        return [
            'content browser' => ['mod_goone/browser'],
            'bulk content browser' => ['mod_goone/browser_bulk'],
        ];
    }

    /**
     * Renders a template with an empty context.
     *
     * @param string $template The template name.
     * @return string The output.
     */
    private function render(string $template): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        return $renderer->render_from_template($template, []);
    }

    /**
     * The filter form must carry the class that the compatibility rules use.
     *
     * @param string $template The template name.
     * @dataProvider filter_template_provider
     */
    public function test_filter_form_has_the_scope_class(string $template): void {
        $this->resetAfterTest();
        $output = $this->render($template);

        $this->assertStringContainsString('class="form-inline goone-filters"', $output,
            'The filter form must have the goone-filters class. The compatibility rules use it.');
    }

    /**
     * The template must make the filter row a flex row.
     *
     * Bootstrap 5 does not define .form-row. Without this rule the four filters
     * show one below the other on Moodle 5.0 and later.
     *
     * @param string $template The template name.
     * @dataProvider filter_template_provider
     */
    public function test_form_row_is_a_flex_row(string $template): void {
        $this->resetAfterTest();
        $output = $this->render($template);

        $this->assertMatchesRegularExpression(
            '/\.goone-filters\s+\.form-row\s*\{[^}]*display:\s*flex/s', $output,
            'The compatibility rules must make .form-row a flex row.');
        $this->assertMatchesRegularExpression(
            '/\.goone-filters\s+\.form-row\s*\{[^}]*flex-wrap:\s*wrap/s', $output,
            'The filter row must wrap.');
    }

    /**
     * The label must take a full line, thus it stays above its control.
     *
     * The label goes above its control only when the label and the control
     * together are wider than the column. The column width is not the same on
     * Moodle 4.5 and on Moodle 5.0 and later.
     *
     * @param string $template The template name.
     * @dataProvider filter_template_provider
     */
    public function test_filter_label_takes_a_full_line(string $template): void {
        $this->resetAfterTest();
        $output = $this->render($template);

        $this->assertMatchesRegularExpression(
            '/\.goone-filters\s+\.form-group\s*>\s*label\s*\{[^}]*flex:\s*0\s+0\s+100%/s', $output,
            'The filter label must take a full line of the flex row.');
    }

    /**
     * The content type badge must use the colour of the parent.
     *
     * Bootstrap 5 sets .badge { color: #fff }. The badge is then not visible.
     *
     * @param string $template The template name.
     * @dataProvider filter_template_provider
     */
    public function test_badge_keeps_the_parent_colour(string $template): void {
        $this->resetAfterTest();
        $output = $this->render($template);

        $this->assertMatchesRegularExpression(
            '/\.badge\.badge-black\s*\{[^}]*color:\s*inherit/s', $output,
            'The badge must use the colour of the parent, or it is not visible on Bootstrap 5.');
    }
}
