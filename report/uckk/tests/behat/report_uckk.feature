@report @report_uckk
Feature: UCKK institutional reports
  In order to monitor UCKK learning, governance, archive, and integrity activity
  As a Gestionnaire UCKK
  I need to view filtered institutional reports and export them when authorized

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                    |
      | manager1 | UCKK       | Manager  | manager1@example.test    |
      | joueur1  | UCKK       | Joueur   | joueur1@example.test     |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  @javascript
  Scenario: A manager views the UCKK report dashboard
    Given I log in as "manager1"
    When I navigate to "Reports > UCKK reports" in site administration
    Then I should see "Institutional report dashboard"
    And I should see "Joueur progress"
    And I should see "Cohort progress"
    And I should see "Program progress"
    And I should see "Competency matrix"
    And I should see "Badge awards"
    And I should see "Challenge status"
    And I should see "Assembly decisions"
    And I should see "Archive production"
    And I should see "Integrity cases"

  @javascript
  Scenario: A manager filters the selected report
    Given I log in as "manager1"
    And I navigate to "Reports > UCKK reports" in site administration
    When I set the field "User ID" to "2"
    And I set the field "Status" to "validated"
    And I press "Apply filters"
    Then I should see "Active filters"
    And I should see "validated"

  @javascript
  Scenario: A manager switches report families
    Given I log in as "manager1"
    And I navigate to "Reports > UCKK reports" in site administration
    When I follow "Challenge status"
    Then I should see "Challenge status"
    And I should see "Challenge type"
    When I follow "Assembly decisions"
    Then I should see "Assembly decisions"
    And I should see "Assembly type"
    When I follow "Integrity cases"
    Then I should see "Integrity cases"
    And I should see "Integrity type"

  @javascript
  Scenario: A manager can access CSV export controls
    Given I log in as "manager1"
    When I navigate to "Reports > UCKK reports" in site administration
    Then I should see "Export CSV"

  Scenario: A regular user cannot access institutional reports
    Given I log in as "joueur1"
    When I am on the "report_uckk > UCKK reports" page
    Then I should see "Sorry, but you do not currently have permissions to do that"