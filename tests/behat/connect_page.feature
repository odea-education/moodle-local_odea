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
