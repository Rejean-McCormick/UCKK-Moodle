@tool @tool_uckkintegrity
Feature: UCKK integrity cases
  In order to protect evidence, dignity, method and contestability
  As an Inquisiteur
  I need to open, review, correct, close and audit integrity cases

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname | email               |
      | inq1     | Inquisiteur | UCKK     | inq1@example.com    |
      | mentor1  | Mentor      | UCKK     | mentor1@example.com |
      | joueur1  | Joueur      | UCKK     | joueur1@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | inq1    | manager | System       |           |
      | mentor1 | teacher | System       |           |

  Scenario: Inquisiteur opens an integrity case
    Given I log in as "inq1"
    When I navigate to "Tools > UCKK integrity > Integrity cases" in site administration
    And I follow "Open case"
    And I set the field "Case type" to "Proof quality"
    And I set the field "Subject component" to "mod_uckkchallenge"
    And I set the field "Subject ID" to "1"
    And I set the field "Severity" to "Normal"
    And I set the field "Visibility" to "Restricted"
    And I set the field "Summary" to "The submitted proof needs an integrity review."
    And I set the field "Parties" to "joueur1"
    And I set the field "Evidence links" to "https://example.invalid/proof/1"
    And I press "Open case"
    Then I should see "Integrity case opened"
    And I should see "Proof quality"
    And I should see "mod_uckkchallenge:1"
    And I should see "The submitted proof needs an integrity review."

  Scenario: Inquisiteur reviews an integrity case
    Given I log in as "inq1"
    And I navigate to "Tools > UCKK integrity > Integrity cases" in site administration
    And I follow "Open case"
    And I set the field "Case type" to "AI misuse"
    And I set the field "Subject component" to "mod_uckkchallenge"
    And I set the field "Subject ID" to "2"
    And I set the field "Summary" to "The proof may contain unverified AI-generated claims."
    And I press "Open case"
    When I follow "Review case"
    And I set the field "Status" to "Under review"
    And I set the field "Note type" to "Observation"
    And I set the field "Body" to "The evidence must be checked against the original sources."
    And I press "Review case"
    Then I should see "Review recorded"
    And I should see "Under review"
    And I should see "The evidence must be checked against the original sources."

  Scenario: Inquisiteur requests a correction
    Given I log in as "inq1"
    And I navigate to "Tools > UCKK integrity > Integrity cases" in site administration
    And I follow "Open case"
    And I set the field "Case type" to "Archive correction"
    And I set the field "Subject component" to "mod_uckkarchive"
    And I set the field "Subject ID" to "3"
    And I set the field "Summary" to "The archive summary is incomplete."
    And I press "Open case"
    When I follow "Record decision"
    And I set the field "Status" to "Correction required"
    And I set the field "Decision text" to "The archive item can remain visible only after correction."
    And I set the field "Correction" to "Add provenance, source date, and evidence relation."
    And I set the field "Appeal path" to "Submit an appeal within the configured appeal window."
    And I press "Record decision"
    Then I should see "Decision recorded"
    And I should see "Correction required"
    And I should see "Add provenance, source date, and evidence relation."
    And I should see "Submit an appeal within the configured appeal window."

  Scenario: Inquisiteur closes a resolved integrity case
    Given I log in as "inq1"
    And I navigate to "Tools > UCKK integrity > Integrity cases" in site administration
    And I follow "Open case"
    And I set the field "Case type" to "Assessment dispute"
    And I set the field "Subject component" to "mod_uckkchallenge"
    And I set the field "Subject ID" to "4"
    And I set the field "Summary" to "The assessment decision was contested by the learner."
    And I press "Open case"
    When I follow "Review case"
    And I set the field "Status" to "Under review"
    And I set the field "Note type" to "Evidence"
    And I set the field "Body" to "The review compared the rubric, proof, and mentor feedback."
    And I press "Review case"
    And I follow "Record decision"
    And I set the field "Status" to "Resolved"
    And I set the field "Decision text" to "The assessment is confirmed with a clearer explanation."
    And I set the field "Appeal path" to "A further appeal can be submitted to the configured UCKK authority."
    And I set the field "Archive summary" to "Assessment dispute resolved after evidence review."
    And I press "Record decision"
    Then I should see "Decision recorded"
    And I should see "Resolved"
    And I should see "The assessment is confirmed with a clearer explanation."
    And I should see "Assessment dispute resolved after evidence review."

  Scenario: A participant submits an appeal on a visible case
    Given I log in as "inq1"
    And I navigate to "Tools > UCKK integrity > Integrity cases" in site administration
    And I follow "Open case"
    And I set the field "Case type" to "Challenge dispute"
    And I set the field "Subject component" to "mod_uckkchallenge"
    And I set the field "Subject ID" to "5"
    And I set the field "Summary" to "The challenge result is contested."
    And I press "Open case"
    When I follow "Submit appeal"
    And I set the field "Body" to "The decision should consider the missing evidence package."
    And I press "Submit appeal"
    Then I should see "Appeal recorded"
    And I should see "The decision should consider the missing evidence package."

  Scenario: Ordinary learner cannot access restricted integrity case list
    Given I log in as "joueur1"
    When I go to "/admin/tool/uckkintegrity/index.php"
    Then I should see "Permission"
    And I should not see "Integrity cases"

  Scenario: Mentor can open a case but cannot record a final decision
    Given I log in as "mentor1"
    When I go to "/admin/tool/uckkintegrity/case.php?action=create"
    And I set the field "Case type" to "Proof quality"
    And I set the field "Subject component" to "mod_uckkchallenge"
    And I set the field "Subject ID" to "6"
    And I set the field "Summary" to "A mentor requests an integrity review."
    And I press "Open case"
    Then I should see "Integrity case opened"
    When I follow "Record decision"
    Then I should see "Permission"