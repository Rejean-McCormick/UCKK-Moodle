@mod @mod_uckkchallenge
Feature: UCKK Challenge activity workflows
  In order to run Défis King Klown inside UCKK-Moodle
  As a teacher, learner, mentor, or integrity reviewer
  I need challenge creation, proof submission, review visibility, and restricted actions to follow Moodle permissions

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname    | email                         |
      | teacher1      | Mentor    | UCKK        | teacher1@example.invalid      |
      | student1      | Ada       | Joueuse     | student1@example.invalid      |
      | student2      | Blaise    | Joueur      | student2@example.invalid      |
      | manager1      | Manager   | UCKK        | manager1@example.invalid      |
    And the following "courses" exist:
      | fullname                           | shortname | category | format |
      | UCKK TC101 Cartographie des idées  | UCKK101   | 0        | topics |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | teacher1 | UCKK101 | editingteacher |
      | student1 | UCKK101 | student        |
      | student2 | UCKK101 | student        |
      | manager1 | UCKK101 | manager        |

  @javascript
  Scenario: A teacher creates a UCKK Challenge activity
    Given I am on the "UCKK101" "Course" page logged in as "teacher1"
    And I turn editing mode on
    When I add a "UCKK Challenge" to section "1"
    And I set the following fields to these values:
      | Challenge name          | Map a hidden rule |
      | Challenge introduction  | Identify a hidden rule in a social, institutional, or technical system. |
      | Challenge code          | UCKK-TC101-HIDDEN-RULE |
      | Challenge type          | System mapping |
      | Challenge status        | Open |
      | Challenge statement     | Produce a clear map of one hidden rule and explain who benefits, who is constrained, and what evidence supports the claim. |
      | Challenge context       | This challenge belongs to the common core and trains system reading. |
      | Challenge rules         | Do not target private persons. Do not fabricate evidence. Separate facts, interpretation, and symbolic framing. |
      | Corridors of action     | Observation, document analysis, interview notes, archive comparison. |
      | Ethical constraints     | No harassment, doxxing, humiliation, or confusion between fiction and fact. |
      | Evidence requirements   | Submit text evidence, a source, relation to criteria, provenance, and uncertainty notes. |
      | Evaluation criteria     | Clarity of map, quality of evidence, provenance, ethical restraint, and contestability. |
      | Visibility              | Course |
      | Archive policy          | Summary archive |
    And I press "Save and display"
    Then I should see "Map a hidden rule"
    And I should see "Identify a hidden rule"
    And I should see "Evidence requirements"

  @javascript
  Scenario: A learner views a published challenge and opens the proof submission page
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name              | intro                         | challengetype  | status | statement                         | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-HIDDEN-RULE-001  | Map a hidden rule | Identify one hidden rule.     | system_mapping | open   | Map one hidden rule with evidence. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    When I am on the "Map a hidden rule" "uckkchallenge activity" page logged in as "student1"
    Then I should see "Map a hidden rule"
    And I should see "Submit proof"
    When I follow "Submit proof"
    Then I should see "Submit challenge proof"
    And I should see "Provenance statement"
    And I should see "Relation to criteria"

  @javascript
  Scenario: A learner submits proof with provenance and uncertainty notes
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name              | intro                         | challengetype  | status | statement                         | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-HIDDEN-RULE-002  | Map a hidden rule | Identify one hidden rule.     | system_mapping | open   | Map one hidden rule with evidence. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    And I am on the "Map a hidden rule" "uckkchallenge activity" page logged in as "student1"
    When I follow "Submit proof"
    And I set the following fields to these values:
      | Proof type             | Text |
      | Submission text        | The hidden rule is that access depends on informal gatekeepers. I observed repeated referral patterns and compared them with the written process. |
      | Relation to criteria   | This proof addresses evidence quality, system mapping, and ethical restraint. |
      | Provenance statement   | Produced by the learner from observation notes and course workshop analysis. |
      | Source or author       | Ada Joueuse |
      | Visibility             | Course |
      | Uncertainty notes      | The pattern is plausible but should be checked against more cases. |
    And I press "Submit proof"
    Then I should see "Proof submitted."
    And I should see "Map a hidden rule"
    And I should see "Submitted proofs"
    And I should see "Ada Joueuse"

  @javascript
  Scenario: AI-assisted proof requires an AI collaboration log
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name                 | intro                            | challengetype  | status | statement                            | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-AI-PROOF-001     | Trace an AI-assisted map | Submit evidence with AI disclosure. | system_mapping | open   | Map a system and disclose AI assistance. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    And I am on the "Trace an AI-assisted map" "uckkchallenge activity" page logged in as "student1"
    When I follow "Submit proof"
    And I set the following fields to these values:
      | Proof type             | Text |
      | Submission text        | I used AI to summarize notes about the system map. |
      | Relation to criteria   | This proof addresses system mapping. |
      | Provenance statement   | Produced by the learner with AI assistance. |
      | Source or author       | Ada Joueuse |
      | Visibility             | Course |
      | AI-assisted evidence   | 1 |
    And I press "Submit proof"
    Then I should see "An AI collaboration log is required when the submission is marked as AI-assisted."

  @javascript
  Scenario: A mentor can see review affordances for submitted proof
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name              | intro                         | challengetype  | status | statement                         | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-HIDDEN-RULE-003  | Map a hidden rule | Identify one hidden rule.     | system_mapping | open   | Map one hidden rule with evidence. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    And I am on the "Map a hidden rule" "uckkchallenge activity" page logged in as "student1"
    And I follow "Submit proof"
    And I set the following fields to these values:
      | Proof type             | Text |
      | Submission text        | A hidden rule appears in how informal approval is required before formal submission. |
      | Relation to criteria   | This addresses the mapping and proof criteria. |
      | Provenance statement   | Learner observation and workshop reflection. |
      | Source or author       | Ada Joueuse |
      | Visibility             | Course |
      | Uncertainty notes      | More evidence could strengthen the claim. |
    And I press "Submit proof"
    When I am on the "Map a hidden rule" "uckkchallenge activity" page logged in as "teacher1"
    Then I should see "Submitted proofs"
    And I should see "Review proof"
    And I should see "Ada Joueuse"

  @javascript
  Scenario: A learner without integrity authority cannot access privileged integrity actions
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name                    | intro                    | challengetype      | status | statement                  | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-INTEGRITY-001    | Integrity-bound challenge | Review must be human.    | institutional_audit | open   | Submit contestable evidence. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    When I am on the "Integrity-bound challenge" "uckkchallenge activity" page logged in as "student1"
    Then I should not see "Validate integrity"
    And I should not see "Invalidate challenge"
    And I should not see "Inquisiteur actions"

  @javascript
  Scenario: A manager can access archive and integrity entry points
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name                    | intro                    | challengetype      | status | statement                  | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-ARCHIVE-001      | Archive-ready challenge | Challenge with archive.  | capstone           | open   | Submit archive-ready evidence. | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    When I am on the "Archive-ready challenge" "uckkchallenge activity" page logged in as "manager1"
    Then I should see "Archive challenge"
    And I should see "Integrity review"

  @javascript
  Scenario: Learners cannot see another learner restricted proof
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name                    | intro                    | challengetype  | status | statement                    | rules                  | evidencepolicy          | criteria                  | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-RESTRICTED-001   | Restricted proof challenge | Restricted proof test. | system_mapping | open   | Submit restricted evidence.   | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | course     | summary       |
    And I am on the "Restricted proof challenge" "uckkchallenge activity" page logged in as "student1"
    And I follow "Submit proof"
    And I set the following fields to these values:
      | Proof type             | Text |
      | Submission text        | This proof should be visible only to restricted review contexts. |
      | Relation to criteria   | This proof addresses the criteria. |
      | Provenance statement   | Produced by the learner. |
      | Source or author       | Ada Joueuse |
      | Visibility             | Restricted integrity |
      | Uncertainty notes      | Contains sensitive integrity material. |
    And I press "Submit proof"
    When I am on the "Restricted proof challenge" "uckkchallenge activity" page logged in as "student2"
    Then I should not see "This proof should be visible only to restricted review contexts."
    And I should not see "Contains sensitive integrity material."

  @javascript
  Scenario: Challenge pages display the non-sovereign AI and human validation boundary
    Given the following "activities" exist:
      | activity      | course  | idnumber              | name                    | intro                    | challengetype       | status | statement                    | rules                  | evidencepolicy          | criteria                  | aipolicy                                                                                                                                                               | visibility | archivepolicy |
      | uckkchallenge | UCKK101 | UCKK-AI-BOUNDARY-001  | AI boundary challenge   | AI can assist only.      | public_pedagogical  | open   | Use AI to map uncertainty.    | Keep the action ethical. | Submit evidence with provenance. | Evidence quality and clarity. | AI may assist with mapping, drafting, summarising, and uncertainty detection. AI cannot grade, validate integrity, award badges, certify competencies, or replace human review. | course     | summary       |
    When I am on the "AI boundary challenge" "uckkchallenge activity" page logged in as "student1"
    Then I should see "AI may assist"
    And I should see "AI cannot grade"
    And I should see "human review"