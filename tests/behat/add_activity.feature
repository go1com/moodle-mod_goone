@mod @mod_goone
Feature: Add a GO1 activity

  @javascript
  Scenario: Add a GO1 activity to a course
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course1  | c1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |
      | student1 | c1     | student        |
    And the following config values are set as admin:
      | theme | snap |
    When I log in as "teacher1"
    And I am on "Course1" course homepage
    And I open the activity chooser
    And I follow "Add a new GO1 content item"
    And I set the following fields to these values:
      | Name | Activity1 |
    And I click on "Open GO1 Content Browser" "button"
    And I switch to the browser tab opened by the app
    And I switch to the main window