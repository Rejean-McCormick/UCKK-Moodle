@mod @mod_uckkassembly
Feature: UCKK Assembly activity workflow
  In order to deliberate and document collective decisions
  As UCKK participants
  We need to create Assemblies, propose motions, object, vote, publish decisions, and preserve contestability

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Gestionnaire | UCKK | manager1@example.com |
      | teacher1 | Mentor | UCKK | teacher1@example.com |
      | student1 | Ada | Joueuse | student1@example.com |
      | student2 | Bruno | Joueur | student2@example.com |
      | observer1 | Observatrice | UCKK | observer1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | UCKK Assembly Course | UCKKASM101 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | manager1 | UCKKASM101 | manager |
      | teacher1 | UCKKASM101 | editingteacher |
      | student1 | UCKKASM101 | student |
      | student2 | UCKKASM101 | student |
      | observer1 | UCKKASM101 | student |

  @javascript
  Scenario: A mentor creates an Assembly activity with canonical governance settings
    Given I log in as "teacher1"
    And I am on "UCKK Assembly Course" course homepage with editing mode on
    When I add a "UCKK Assembly" to section "1"
    And I set the following fields to these values:
      | Assembly name | Assembly of Savoirs — Evidence review |
      | Assembly code | UCKK-ASM-SAVOIRS-001 |
      | Assembly type | Savoirs |
      | Assembly status | Planned |
      | Visibility | Course |
      | Scope | This Assembly reviews evidence, objections, and decision legitimacy for the course. |
      | Agenda | 1. Open motions. 2. Hear objections. 3. Vote/read. 4. Publish decision. |
      | Assembly rules | Motions must be clear, objections must be documented, votes must include rationale, and decisions remain contestable. |
      | Decision method | Multiple readings |
      | Decision type | Recommendation |
      | Archive policy | Decision summary |
      | Contestability window in days | 7 |
    And I press "Save and display"
    Then I should see "Assembly of Savoirs — Evidence review"
    And I should see "Savoirs"
    And I should see "Planned"
    And I should see "Scope"
    And I should see "Agenda"
    And I should see "Assembly rules"
    And I should see "Motions"
    And I should see "Objections"
    And I should see "Voting and readings"
    And I should see "Decision"
    And I should see "Contestability"
    And I should see "Archive"

  @javascript
  Scenario: A participant proposes a motion in an open Assembly
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | open | course | Review course evidence. | Review motions and vote. | Document every motion and objection. | multiple_readings | recommendation | decision_summary |
    And I log in as "student1"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Propose motion"
    And I set the following fields to these values:
      | Motion title | Accept the evidence map for archive review |
      | Motion text | The Assembly should accept the evidence map as sufficient for archive review, subject to objections and integrity checks. |
      | Rationale | The evidence map links claims, sources, and learning outcomes clearly. |
      | Visibility | Course |
    And I press "Submit motion"
    Then I should see "Motion submitted"
    And I should see "Accept the evidence map for archive review"
    And I should see "The Assembly should accept the evidence map"
    And I should see "Course"

  @javascript
  Scenario: A participant records an objection against a motion
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | deliberation | course | Review course evidence. | Review motions and objections. | Objections must be answered before decision publication. | multiple_readings | recommendation | decision_summary |
    And the following "mod_uckkassembly > motions" exist:
      | assembly | title | motiontext | status | userid |
      | assembly001 | Accept the evidence map for archive review | The Assembly should accept the evidence map for archive review. | active | student1 |
    And I log in as "student2"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Accept the evidence map for archive review"
    And I follow "Add objection"
    And I set the following fields to these values:
      | Objection title | Source provenance is incomplete |
      | Objection text | The map should identify the source date and author for each cited proof. |
      | Requested response | Add provenance details before decision publication. |
      | Visibility | Course |
    And I press "Submit objection"
    Then I should see "Objection submitted"
    And I should see "Source provenance is incomplete"
    And I should see "Add provenance details before decision publication"

  @javascript
  Scenario: A participant submits a vote with a rationale and multiple readings
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | voting_or_reading | course | Review course evidence. | Vote and read the motion. | Votes must include rationale. | multiple_readings | recommendation | decision_summary |
    And the following "mod_uckkassembly > motions" exist:
      | assembly | title | motiontext | status | userid |
      | assembly001 | Accept the evidence map for archive review | The Assembly should accept the evidence map for archive review. | active | student1 |
    And I log in as "student1"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Accept the evidence map for archive review"
    And I follow "Vote"
    And I set the field "Support" to "1"
    And I set the field "Rationale" to "I support the motion because the evidence is sufficient if the provenance objection is answered."
    And I set the field "Raw count reading" to "1"
    And I set the field "Minority objection reading" to "1"
    And I set the field "Uncertainty notes" to "The decision should mention unresolved provenance risk."
    And I press "Submit vote"
    Then I should see "Vote submitted"
    And I should see "Support"
    And I should see "Raw count reading"
    And I should see "Minority objection reading"
    And I should see "The final decision remains subject to Assembly rules"

  @javascript
  Scenario: A mentor publishes a decision with contestability and archive summary
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | decision_draft | course | Review course evidence. | Publish decision after readings. | Decisions must preserve objections and contestability. | multiple_readings | recommendation | decision_summary |
    And the following "mod_uckkassembly > motions" exist:
      | assembly | title | motiontext | status | userid |
      | assembly001 | Accept the evidence map for archive review | The Assembly should accept the evidence map for archive review. | active | student1 |
    And I log in as "teacher1"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Publish decision"
    And I set the following fields to these values:
      | Decision title | Evidence map accepted for archive review |
      | Decision text | The Assembly recommends accepting the evidence map for archive review after adding provenance details. |
      | Rationale | The motion received support, the objection was documented, and the decision remains contestable. |
      | Evidence summary | Evidence map, objection record, vote rationale, and reading summary. |
      | Decision type | Recommendation |
      | Visibility | Course |
      | Contestability window in days | 7 |
      | Archive summary | Decision, motion, objection, readings, and final rationale should be archived. |
    And I press "Publish decision"
    Then I should see "Decision published"
    And I should see "Evidence map accepted for archive review"
    And I should see "The Assembly recommends accepting the evidence map"
    And I should see "Contestability"
    And I should see "Archive summary"

  @javascript
  Scenario: A participant contests a published Assembly decision
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | contestability_window | course | Review course evidence. | Contestability window open. | Decisions may be contested with reasons. | multiple_readings | recommendation | decision_summary |
    And the following "mod_uckkassembly > decisions" exist:
      | assembly | title | decisiontext | status | decisiontype | visibility |
      | assembly001 | Evidence map accepted for archive review | The Assembly recommends accepting the evidence map after provenance details are added. | published | recommendation | course |
    And I log in as "student2"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Evidence map accepted for archive review"
    And I follow "Contest decision"
    And I set the following fields to these values:
      | Contestation summary | The decision did not answer the provenance objection clearly enough. |
      | Contestation reason | The archive summary should list which sources remain incomplete. |
      | Requested correction | Add a correction note before final archive validation. |
    And I press "Submit contestation"
    Then I should see "Decision contested"
    And I should see "The decision did not answer the provenance objection clearly enough"
    And I should see "Add a correction note before final archive validation"

  @javascript
  Scenario: A learner cannot publish an Assembly decision
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | decision_draft | course | Review course evidence. | Publish decision after readings. | Decisions require authorised publication. | multiple_readings | recommendation | decision_summary |
    And I log in as "student1"
    When I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    Then I should not see "Publish decision"
    And I should not see "Archive decision"
    And I should see "Motions"
    And I should see "Objections"
    And I should see "Voting and readings"

  @javascript
  Scenario: Restricted integrity information is not visible to ordinary participants
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Inquisiteurs — Restricted review | inquisiteurs | paused_for_integrity | restricted_integrity | Review integrity-sensitive evidence. | Restricted integrity review. | Restricted notes require explicit capability. | facilitator_synthesis | integrity | restricted_integrity |
    And I log in as "student1"
    When I am on the "Assembly of Inquisiteurs — Restricted review" "uckkassembly activity" page
    Then I should not see "Restricted integrity notes"
    And I should not see "Inquisiteur actions"
    And I should see "You do not have permission to view restricted integrity information"

  @javascript
  Scenario: A mentor sees Assembly reports for motions, decisions, contestations, and unresolved objections
    Given the following "activities" exist:
      | activity | course | idnumber | name | assemblytype | status | visibility | scope | agenda | rules | decisionmethod | decisiontype | archivepolicy |
      | uckkassembly | UCKKASM101 | assembly001 | Assembly of Savoirs — Evidence review | savoirs | open | course | Review course evidence. | Review motions and vote. | Document every motion and objection. | multiple_readings | recommendation | decision_summary |
    And I log in as "teacher1"
    And I am on the "Assembly of Savoirs — Evidence review" "uckkassembly activity" page
    When I follow "Assembly report"
    Then I should see "Motion counts"
    And I should see "Decision counts"
    And I should see "Contestation counts"
    And I should see "Unresolved objections"
    And I should see "Archive output"