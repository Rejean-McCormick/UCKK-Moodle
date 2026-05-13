@tool @tool_uckkseed @uckk
Feature: UCKK seed tool
  In order to install and maintain the canonical UCKK-Moodle distribution
  As a site administrator
  I need to validate, dry-run, seed, export, and safely reset UCKK seed data

  Background:
    Given I log in as "admin"

  @javascript
  Scenario: View the UCKK seed tool dashboard
    When I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    Then I should see "UCKK seed tool"
    And I should see "Seed UCKK distribution"
    And I should see "Validate UCKK distribution"
    And I should see "Export preset"
    And I should see "Reset UCKK seeded content"

  @javascript
  Scenario: Validate the UCKK distribution without modifying records
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Validate UCKK distribution"
    Then I should see "Validate UCKK distribution"
    And I should see "Check the current UCKK installation and seed data without modifying records."
    When I press "Run validation"
    Then I should see "Validation report"
    And I should not see "Coding error detected"

  @javascript
  Scenario: Open the seed form and run a dry-run seed
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Seed UCKK distribution"
    Then I should see "Seed UCKK distribution"
    And I should see "Create or update the canonical UCKK campus seed data using idempotent seed operations."
    When I set the field "Mode" to "Dry run"
    And I press "Seed UCKK distribution"
    Then I should see "Seed summary"
    And I should see "dry"
    And I should not see "Coding error detected"

  @javascript
  Scenario: Export the badges preset
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Export preset"
    Then I should see "Export preset"
    And I should see "Export one canonical UCKK seed preset using the shared preset schema."
    When I set the field "Preset" to "Badges"
    And I press "Export preset"
    Then I should see "Seed summary"
    And I should see "badges"
    And I should not see "Coding error detected"

  @javascript
  Scenario: Reset page requires explicit confirmation before destructive apply mode
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Reset UCKK seeded content"
    Then I should see "Reset UCKK seeded content"
    And I should see "Reset only content explicitly created by the UCKK seed tool."
    When I set the field "Mode" to "Apply"
    And I press "Reset UCKK seeded content"
    Then I should see "Confirmation is required before applying this reset."

  @javascript
  Scenario: Reset can be previewed with rollback plan
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Reset UCKK seeded content"
    Then I should see "Reset UCKK seeded content"
    When I set the field "Mode" to "Rollback plan"
    And I press "Reset UCKK seeded content"
    Then I should see "Seed summary"
    And I should see "rollback"
    And I should not see "Coding error detected"

  @javascript
  Scenario: Recent seed runs are visible on the dashboard after validation
    Given I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    When I follow "Validate UCKK distribution"
    And I press "Run validation"
    Then I should see "Validation report"
    When I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    Then I should see "Recent seed runs"
    And I should see "validate"

  @javascript
  Scenario: The seed tool keeps ownership boundaries visible
    When I navigate to "Plugins > Admin tools > UCKK seed tool" in site administration
    Then I should see "The UCKK seed tool installs, validates, exports, and safely resets the canonical UCKK campus distribution."
    And I should see "Ownership of workflows remains with the relevant UCKK plugins."