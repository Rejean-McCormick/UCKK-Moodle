@local @local_uckk @uckk @faculty @faculty_dynamic_blocks
Feature: UCKK faculty dynamic blocks
  In order to expose live public Moodle and local_uckk data on faculty pages
  As a visitor
  I need dynamic blocks to render public content, empty states, and permission-filtered Moodle data safely

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                    |
      | teacher1 | Faculty   | Teacher  | teacher1@example.test    |
      | player1  | Public    | Player   | player1@example.test     |
    And the following "categories" exist:
      | name                  | category | idnumber |
      | UCKK Grand Jeu social | 0        | UCKK-GJS |
    And the following "courses" exist:
      | fullname                                      | shortname       | category | format | visible |
      | GJS Hub public                                | GJS-HUB         | UCKK-GJS | topics | 1       |
      | GJS101 — Cartographie du Grand Jeu social     | GJS101          | UCKK-GJS | topics | 1       |
      | GJS199 — Cours Moodle privé de coordination   | GJS199-PRIVATE  | UCKK-GJS | topics | 0       |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | teacher1 | GJS-HUB | editingteacher |
      | teacher1 | GJS101  | editingteacher |
      | player1  | GJS101  | student        |
    And the following "activities" exist:
      | activity | course  | idnumber              | name           | intro                         | visible |
      | forum    | GJS-HUB | gjs-public-annonces   | Annonces       | Annonces publiques GJS        | 1       |
      | forum    | GJS-HUB | gjs-private-annonces  | Forum privé    | Forum privé de coordination   | 0       |
    And the following "mod_forum > discussions" exist:
      | user     | forum         | name                              | message                                      |
      | teacher1 | Annonces      | Annonce publique de rentrée GJS   | Le laboratoire public ouvre cette semaine.   |
      | teacher1 | Forum privé   | Coordination interne GJS          | Cette note privée ne doit jamais apparaître. |
    And I purge all Moodle caches

  @javascript
  Scenario: An anonymous visitor sees the dynamic block shell on a published faculty page
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should see "Annonces de la faculté"
    And I should see "Cours associés"
    And "#annonces" "css_element" should exist
    And ".local-uckk-faculty-dynamic-block" "css_element" should exist

  @javascript
  Scenario: The moodle_forum provider exposes public announcement content
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Annonces de la faculté"
    And I should see "Annonce publique de rentrée GJS"
    And I should see "Le laboratoire public ouvre cette semaine."

  @javascript
  Scenario: The moodle_forum provider does not expose hidden forum content
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Annonces de la faculté"
    And I should not see "Coordination interne GJS"
    And I should not see "Cette note privée ne doit jamais apparaître."
    And I should not see "Forum privé"

  @javascript
  Scenario: The moodle_category provider exposes visible Moodle courses
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Cours associés"
    And I should see "GJS101"
    And I should see "GJS101 — Cartographie du Grand Jeu social"
    And "a[href*=\"/course/view.php\"]" "css_element" should exist

  @javascript
  Scenario: The moodle_category provider does not expose hidden Moodle courses
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Cours associés"
    And I should not see "GJS199 — Cours Moodle privé de coordination"
    And I should not see "GJS199-PRIVATE"

  @javascript
  Scenario: Dynamic blocks do not expose learner-specific Moodle data to anonymous visitors
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should not see "player1@example.test"
    And I should not see "Public Player"
    And I should not see "Participants"
    And I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Badge personnel obtenu"

  @javascript
  Scenario: Dynamic blocks do not expose learner-specific Moodle data to logged-in learners
    Given I log in as "player1"
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should see "Cours associés"
    And I should not see "teacher1@example.test"
    And I should not see "Faculty Teacher"
    And I should not see "Participants"
    And I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Données privées d’étudiant"

  @javascript
  Scenario: Empty dynamic blocks render safely when no public Moodle data exists
    When I am on "/local/uckk/faculty.php?slug=economie"
    And I wait until the page is ready
    Then I should see "Voie de l’Économie"
    And I should see "Annonces de la faculté"
    And I should see "Aucune annonce publique pour le moment."
    And ".local-uckk-faculty-dynamic-block" "css_element" should exist
    And I should not see "Coding error"
    And I should not see "Exception"
    And I should not see "Debug info"

  @javascript
  Scenario: Dynamic block links stay inside public Moodle or local_uckk targets
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Annonces de la faculté"
    And "a[href*=\"/local/uckk/faculty.php\"]" "css_element" should exist
    And "a[href*=\"/course/view.php\"]" "css_element" should exist
    And "a[href*=\"../\"]" "css_element" should not exist
    And "a[href*=\"/admin/\"]" "css_element" should not exist
    And "a[href*=\"/user/profile.php\"]" "css_element" should not exist

  @javascript
  Scenario: Dynamic block rendering fails closed for an unknown faculty slug
    When I am on "/local/uckk/faculty.php?slug=faculty-dynamic-blocks-unknown"
    And I wait until the page is ready
    Then I should not see "Annonces de la faculté"
    And I should not see "Annonce publique de rentrée GJS"
    And I should not see "Cours associés"
    And ".local-uckk-faculty-dynamic-block" "css_element" should not exist

  @javascript
  Scenario: Dynamic block rendering fails closed for file-path-like slugs
    When I am on "/local/uckk/faculty.php?slug=../content/faculties/grand-jeu-social.faculty"
    And I wait until the page is ready
    Then I should not see "Annonces de la faculté"
    And I should not see "Annonce publique de rentrée GJS"
    And I should not see "Cours associés"
    And ".local-uckk-faculty-dynamic-block" "css_element" should not exist