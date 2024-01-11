@mod @mod_goone
Feature: GO1 plugin configuration
  In order to configure the GO1 plugin,
  As an admin
  I need to be able to access its settings

  Background:
    Given the following config values are set as admin:
      | theme                 | snap |
      | linkadmincategories   |  0   |
      | mod_goone_admin_users |      |

  @javascript
  Scenario: Configure the GO1 plugin as any admin
    When I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Activity modules" node
    And I should see "GO1"
    And I follow "GO1"
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