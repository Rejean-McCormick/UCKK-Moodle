@theme @theme_uckk
Feature: UCKK theme as a thin Boost child theme
  In order to enter the Univers-Cité King Klown Moodle campus coherently
  As a visitor, authenticated user, teacher, or administrator
  I need the UCKK theme to preserve Moodle Boost behaviour while displaying UCKK public identity and institutional framing

  Background:
    Given the following config values are set as admin:
      | theme | uckk |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | player1  | Lucid     | Player   | player1@example.test |
      | teacher1 | Course    | Teacher  | teacher1@example.test |
    And the following "courses" exist:
      | fullname         | shortname | category | format |
      | UCKK Test Course | UCKK101   | 0        | topics |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | player1  | UCKK101 | student        |
      | teacher1 | UCKK101 | editingteacher |
    And I purge all Moodle caches

  @javascript
  Scenario: A visitor sees the UCKK public front page identity
    When I am on site homepage
    Then I should see "Enter the Univers-Cité King Klown"
    And I should see "UCKK Moodle Campus"
    And I should see "Understand the game. Play with lucidity. Change the rules."
    And I should see "A Moodle campus for learning the Grand Jeu social through courses, challenges, assemblies, evidence, and archives."
    And I should see "Enter the campus"
    And ".theme-uckk-frontpage" "css_element" should exist

  @javascript
  Scenario: A visitor sees the UCKK entry cards
    When I am on site homepage
    Then I should see "Learn"
    And I should see "Play"
    And I should see "Assemblies"
    And I should see "Archives"
    And I should see "Evidence-based learning"
    And I should see "A challenge must be visible, verifiable, contestable, and non-abusive."
    And I should see "Assemblies discuss, evaluate, correct, validate, contest, and orient the collective game."
    And I should see "The Archivist keeps proofs, works, decisions, corrections, contestations, rules, versions, learning records, challenge archives, and assembly reports."

  @javascript
  Scenario: A visitor sees the UCKK institutional boundary
    When I am on site homepage
    Then I should see "Institutional boundary"
    And I should see "kOA"
    And I should see "kOA is the movement."
    And I should see "UCKK"
    And I should see "UCKK is the school and learning city of the kOA movement."
    And I should see "kOA Digital Ecosystem"
    And I should see "The kOA Digital Ecosystem is the digital infrastructure."
    And I should see "King Klown"
    And I should see "King Klown is a pedagogical and narrative figure."
    And I should see "The Inquisiteur is an ethical and methodological guardrail."
    And I should see "Archives preserve memory."

  @javascript
  Scenario: A visitor sees the integrity and Inquisiteur notice
    When I am on site homepage
    Then I should see "Integrity"
    And I should see "The Inquisiteur protects facts, dignity, rules, procedure, evidence quality, non-manipulation, and collective trust."
    And I should see "The Inquisiteur is not a public court or a sovereign authority."
    And I should see "Integrity rules"
    And I should see "Every authority must be open to questioning."

  @javascript
  Scenario: A visitor sees the internal recognition notice
    When I am on site homepage
    Then I should see "Internal recognition"
    And I should see "UCKK programmes, baccalauréats, certificates, badges, and attestations are internal recognitions unless formal accreditation is obtained in the future."

  @javascript
  Scenario: A visitor sees the non-sovereign AI notice
    When I am on site homepage
    Then I should see "Governable AI"
    And I should see "AI may assist with clarification, mapping, synthesis, and drafting."
    And I should see "AI is not sovereign"
    And I should see "Human validation required"

  @javascript
  Scenario: An authenticated player sees the campus entry point
    Given I log in as "player1"
    When I am on site homepage
    Then I should see "Enter the Univers-Cité King Klown"
    And I should see "My campus"
    And I should not see "Enter the campus"
    And ".theme-uckk-frontpage" "css_element" should exist

  @javascript
  Scenario: UCKK front page remains usable on a small viewport
    Given I change window size to "small"
    When I am on site homepage
    Then I should see "Enter the Univers-Cité King Klown"
    And I should see "Learn"
    And I should see "Play"
    And I should see "Assemblies"
    And I should see "Archives"
    And ".theme-uckk-frontpage__cards" "css_element" should exist

  @javascript
  Scenario: UCKK theme exposes the expected visual regions
    When I am on site homepage
    Then ".theme-uckk-frontpage" "css_element" should exist
    And ".uckk-seal" "css_element" should exist
    And ".frontpage-actions" "css_element" should exist
    And ".uckk-entry-cards" "css_element" should exist
    And ".uckk-boundary" "css_element" should exist
    And ".uckk-integrity-notice" "css_element" should exist
    And ".uckk-ai-notice" "css_element" should exist

  @javascript
  Scenario: UCKK theme displays the governance formula
    When I am on site homepage
    Then I should see "Grand Jeu social"
    And I should see "King Klown reveals the game."
    And I should see "The Inquisiteur keeps the game honest."
    And I should see "The Assemblies make the game collective."
    And I should see "Players learn to play better."

  @javascript
  Scenario: UCKK public front page does not claim official accreditation
    When I am on site homepage
    Then I should see "Internal recognition"
    And I should not see "official university degree"
    And I should not see "state-accredited bachelor's degree"
    And I should not see "recognized university credit"

  @javascript
  Scenario: Standard course pages keep Boost-compatible Moodle behaviour
    Given I log in as "teacher1"
    When I am on the "UCKK Test Course" "Course" page
    Then I should see "UCKK Test Course"
    And "#page" "css_element" should exist
    And "#region-main" "css_element" should exist
    And I should not see "Enter the Univers-Cité King Klown"

  @javascript
  Scenario: Teacher can still use Moodle course editing controls
    Given I log in as "teacher1"
    And I am on the "UCKK Test Course" "Course" page
    When I turn editing mode on
    Then I should see "Add an activity or resource"
    And "#region-main" "css_element" should exist

  @javascript
  Scenario: Student can access a standard course page without frontpage chrome
    Given I log in as "player1"
    When I am on the "UCKK Test Course" "Course" page
    Then I should see "UCKK Test Course"
    And "#region-main" "css_element" should exist
    And I should not see "Institutional boundary"
    And I should not see "Enter the campus"

  @javascript
  Scenario: Admin pages remain usable under the UCKK theme
    Given I log in as "admin"
    When I navigate to "Appearance > Themes > Theme selector" in site administration
    Then I should see "Theme selector"
    And "#region-main" "css_element" should exist
    And I should not see "Enter the Univers-Cité King Klown"

  @javascript
  Scenario: UCKK theme supports keyboard focus on main entry actions
    When I am on site homepage
    And I press the tab key
    Then the focused element should be visible
    And I press the tab key
    Then the focused element should be visible

  @javascript
  Scenario: UCKK front page can be printed without hiding the institutional message
    When I am on site homepage
    Then I should see "Enter the Univers-Cité King Klown"
    And I should see "Institutional boundary"
    And I should see "Internal recognition"
    And I should see "Integrity"