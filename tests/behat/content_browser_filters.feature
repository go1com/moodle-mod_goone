@mod @mod_goone
Feature: The filters of the GO1 content browser
  In order to find content in the GO1 catalogue,
  As a teacher,
  I need the filters, the search box and the sort box to answer a mouse click.

  # The menu of each filter is drawn by the multiselect library, not by Moodle.
  # The library writes the button with the attribute of one Bootstrap version. A
  # theme that loads a different Bootstrap does not open the menu, and then no
  # filter answers a mouse click. These scenarios press the controls with a real
  # mouse click, thus they find that fault.
  #
  # Only the "Type" filter and the "Sort" box are checked, because their options
  # are in the template. The options of Providers, Language and Tag come from the
  # GO1 catalogue, which a test site does not reach. The end to end suite covers
  # those three, and covers the results that each filter brings back.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course1  | c1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |

  @javascript
  Scenario: The Type filter of the content browser answers a mouse click
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    And I turn editing mode on
    And I open the activity chooser
    And I add a GO1 content item from the activity chooser
    And I set the following fields to these values:
      | Name | Activity1 |
    And I click on "Open GO1 Content Browser" "button"
    And I switch to the GO1 content browser window
    Then the GO1 filter "type" holds "nothing"
    When I open the GO1 filter "type"
    Then the GO1 filter "type" is open
    When I choose the GO1 filter option "video" of "type"
    Then the GO1 filter "type" holds "video"
    When I choose the GO1 filter option "course" of "type"
    Then the GO1 filter "type" holds "video"
    And the GO1 filter "type" holds "course"
    When I clear the GO1 filter "type"
    Then the GO1 filter "type" holds "nothing"

  @javascript
  Scenario: The search box and the sort box of the content browser answer a mouse click
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    And I turn editing mode on
    And I open the activity chooser
    And I add a GO1 content item from the activity chooser
    And I set the following fields to these values:
      | Name | Activity1 |
    And I click on "Open GO1 Content Browser" "button"
    And I switch to the GO1 content browser window
    When I set the field "keyword" to "1 minute"
    Then the field "keyword" matches value "1 minute"
    # The first change of any filter makes the browser set the sort to Relevance,
    # thus the first choice of the user is lost. See BUG-30. The second choice is
    # kept. This scenario holds the behaviour of today, so that a change to it is
    # seen at once.
    When I set the field "sort" to "Created"
    Then the field "sort" matches value "Relevance"
    When I set the field "sort" to "Created"
    Then the field "sort" matches value "Created"

  @javascript
  Scenario: The Type filter of the bulk content browser answers a mouse click
    Given I log in as "admin"
    And I am on the "mod_goone > bulk content browser" page
    Then the GO1 filter "type" holds "nothing"
    When I open the GO1 filter "type"
    Then the GO1 filter "type" is open
    # The bulk browser writes the options of Type with a capital letter, and the
    # browser of the activity form writes them in small letters. The value that is
    # sent to GO1 is the same in both.
    When I choose the GO1 filter option "Video" of "type"
    Then the GO1 filter "type" holds "video"
    When I clear the GO1 filter "type"
    Then the GO1 filter "type" holds "nothing"
