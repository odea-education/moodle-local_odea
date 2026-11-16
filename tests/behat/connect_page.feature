@local @local_odea
Feature: Connect this site to Odea
  In order to link my Moodle site with Odea
  As an admin
  I need to see the connect page render with its form

  Background:
    Given I log in as "admin"

  Scenario: The connect page renders with the connect form
    When I navigate to "Plugins > Local plugins > Odea connection" in site administration
    Then I should see "Connect this site to Odea"
    And I should see "Your name"
    And I should see "Your work email"
    And "//input[@type='submit']" "xpath_element" should exist

  Scenario: A site waiting for approval is not reported as connected
    Given the following config values are set as admin:
      | status | pending | local_odea |
    When I navigate to "Plugins > Local plugins > Odea connection" in site administration
    Then I should see "Almost there"
    And "//input[@type='submit' and @value='Check status']" "xpath_element" should exist

  Scenario: An approved site is reported as connected
    Given the following config values are set as admin:
      | status | active | local_odea |
    When I navigate to "Plugins > Local plugins > Odea connection" in site administration
    Then I should see "Connected. Odea is active for your institution"
    And "//input[@type='submit' and @value='Reconnect']" "xpath_element" should exist
