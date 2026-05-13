@format @format_uckk
Feature: UCKK course format
  In order to use Moodle as the pedagogical campus of the Univers-Cité King Klown
  As a teacher, student, mentor, archivist or integrity reviewer
  I need the UCKK course format to render a complete course structure with evidence, deliberation and archive sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Mentor    | UCKK     | teacher1@example.com  |
      | student1 | Joueur    | UCKK     | student1@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname  | format | numsections |
      | UCKK Tronc commun test   | UCKK-TC101 | uckk   | 9           |
      | UCKK Programme test      | UCKK-GJS01 | uckk   | 9           |
    And the following "course enrolments" exist:
      | user     | course     | role           |
      | teacher1 | UCKK-TC101 | editingteacher |
      | student1 | UCKK-TC101 | student        |
      | teacher1 | UCKK-GJS01 | editingteacher |
      | student1 | UCKK-GJS01 | student        |

  @javascript
  Scenario: A teacher can view the UCKK course format structure
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    Then I should see "UCKK Tronc commun test"
    And I should see "Orientation"
    And I should see "Concepts"
    And I should see "Matière canonique"
    And I should see "Atelier"
    And I should see "Preuves"
    And I should see "Délibération"
    And I should see "Livrable"
    And I should see "Évaluation"
    And I should see "Archive"

  Scenario: A student can view the UCKK course format structure
    Given I am on the "UCKK-TC101" "Course" page logged in as "student1"
    Then I should see "UCKK Tronc commun test"
    And I should see "Orientation"
    And I should see "Concepts"
    And I should see "Matière canonique"
    And I should see "Atelier"
    And I should see "Preuves"
    And I should see "Délibération"
    And I should see "Livrable"
    And I should see "Évaluation"
    And I should see "Archive"

  @javascript
  Scenario: A teacher can turn editing on in an UCKK course
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    When I turn editing mode on
    Then I should see "Add an activity or resource"
    And I should see "Orientation"
    And I should see "Preuves"
    And I should see "Délibération"
    And I should see "Archive"

  @javascript
  Scenario: UCKK course format keeps Moodle editing controls available
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    When I turn editing mode on
    Then "Add an activity or resource" "button" should exist
    And I should see "Orientation"
    And I should see "Concepts"
    And I should see "Archive"

  Scenario: A standard UCKK program course uses the same structural map
    Given I am on the "UCKK-GJS01" "Course" page logged in as "student1"
    Then I should see "UCKK Programme test"
    And I should see "Orientation"
    And I should see "Concepts"
    And I should see "Matière canonique"
    And I should see "Atelier"
    And I should see "Preuves"
    And I should see "Délibération"
    And I should see "Livrable"
    And I should see "Évaluation"
    And I should see "Archive"

  @javascript
  Scenario: The archive panel can be opened and closed
    Given I am on the "UCKK-TC101" "Course" page logged in as "student1"
    When I click on "Archive"
    Then I should see "Archive"
    And I should see "Preuves"
    And I should see "Délibération"

  @javascript
  Scenario: The UCKK format exposes evidence and integrity areas without replacing Moodle permissions
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    Then I should see "Preuves"
    And I should see "Archive"
    And I should see "Évaluation"
    When I turn editing mode on
    Then I should see "Add an activity or resource"

  Scenario: The UCKK course format does not hide the course from enrolled students
    Given I am on the "UCKK-TC101" "Course" page logged in as "student1"
    Then I should see "UCKK Tronc commun test"
    And I should not see "You cannot enrol yourself in this course"
    And I should not see "This course is currently unavailable to students"

  @javascript
  Scenario: UCKK course format remains compatible with the Moodle course index
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    Then I should see "Orientation"
    And I should see "Concepts"
    And I should see "Preuves"
    And I should see "Archive"
    When I turn editing mode on
    Then I should see "Add an activity or resource"

  @javascript
  Scenario: UCKK course format supports activity creation in the Orientation section
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    When I turn editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name         | UCKK orientation page |
      | Description  | Orientation material for the UCKK course |
      | Page content | This page introduces the course, its rules, evidence expectations and archive path. |
    Then I should see "UCKK orientation page"
    And I should see "Orientation"

  @javascript
  Scenario: UCKK course format supports activity creation in the Preuves section
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    When I turn editing mode on
    And I add a "Page" to section "5" and I fill the form with:
      | Name         | Evidence instructions |
      | Description  | Instructions for producing verifiable evidence |
      | Page content | Evidence must be documented, traceable and suitable for archive. |
    Then I should see "Evidence instructions"
    And I should see "Preuves"

  @javascript
  Scenario: UCKK course format supports activity creation in the Archive section
    Given I am on the "UCKK-TC101" "Course" page logged in as "teacher1"
    When I turn editing mode on
    And I add a "Page" to section "9" and I fill the form with:
      | Name         | Archive instructions |
      | Description  | Instructions for preserving final course memory |
      | Page content | The archive preserves decisions, proofs, reflections and reusable learning artefacts. |
    Then I should see "Archive instructions"
    And I should see "Archive"