@mod @mod_goone
Feature: GO1 plugin configuration
  In order to configure the GO1 plugin,
  As an admin
  I need to be able to access its settings

  Background:
    Given the following config values are set as admin:
      | linkadmincategories   |  0   |
      | mod_goone_admin_users |      |

  @javascript
  Scenario: Configure the GO1 plugin as any admin
    When I log in as "admin"
    And I navigate to "Plugins > Activity modules > GO1" in site administration
    And I should see "Retrieve GO1 credentials"
    And I should see "Client ID"
    And I should see "GO1 Oauth client ID"
    And I should see "Client secret"
    And I should see "GO1 Oauth client secret"
    And I should see "Content settings"
    And I should see "GO1 Content Browser filter"
    And I should see "Show everything"
    And I should see "Show Premium Subscription"
    And I should see "Restrict to Custom Selection"
    Then I should see "Select GO1 Content Browser filter"

  # The scenario stops at Cancel. A press of "Create or repair" sends a request to GO1 and
  # gives the webhook of the site a new secret, which a test must never do.
  @javascript
  Scenario: The webhook button asks before it acts, and stays on the settings page
    When I log in as "admin"
    And I navigate to "Plugins > Activity modules > GO1" in site administration
    And I click on "Create or repair webhook" "button"
    Then I should see "Create or repair the GO1 webhook"
    And I should see "The webhook gets a new secret"
    When I click on "Cancel" "button" in the "Create or repair the GO1 webhook" "dialogue"
    Then I should not see "The webhook gets a new secret"
    And I should see "Select GO1 Content Browser filter"