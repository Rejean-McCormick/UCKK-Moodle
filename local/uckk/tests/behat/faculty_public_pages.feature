@local @local_uckk @uckk @faculty @faculty_public_pages
Feature: Public UCKK faculty pages
  In order to explore published UCKK faculties without exposing private Moodle data
  As a visitor or authenticated user
  I need public faculty pages to render from Faculty JSON, Atlas JSON, and public Moodle data safely

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                    |
      | player1  | Public    | Player   | player1@example.test     |
    And the following "categories" exist:
      | name                  | category | idnumber |
      | UCKK Grand Jeu social | 0        | UCKK-GJS |
    And the following "courses" exist:
      | fullname                                  | shortname | category | format | visible |
      | GJS101 — Cartographie du Grand Jeu social | GJS101    | UCKK-GJS | topics | 1       |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | player1 | GJS101 | student |
    And I purge all Moodle caches

  @javascript
  Scenario: An anonymous visitor opens a published public faculty page
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should see "Lire les systèmes, comprendre les règles du jeu, agir avec lucidité."
    And I should see "Une faculté pour lire le jeu avant d’y jouer"
    And I should see "Explorer les cours"

  @javascript
  Scenario: An anonymous visitor sees the institutional recognition notice
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Reconnaissance interne UCKK"
    And I should see "ne constitue pas un diplôme public accrédité"

  @javascript
  Scenario: An anonymous visitor sees the 10 Atlas courses projected on the faculty page
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Programme"
    And I should see "Cours"
    And I should see "GJS101"
    And I should see "GJS102"
    And I should see "GJS103"
    And I should see "GJS104"
    And I should see "GJS105"
    And I should see "GJS106"
    And I should see "GJS107"
    And I should see "GJS108"
    And I should see "GJS109"
    And I should see "GJS110"

  @javascript
  Scenario: An anonymous visitor does not see private learner progress or completion data
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Badge personnel obtenu"
    And I should not see "Données privées d’étudiant"

  @javascript
  Scenario: A logged-in user sees allowed Moodle course links
    Given I log in as "player1"
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Cours associés"
    And I should see "GJS101"
    And I should see "GJS101 — Cartographie du Grand Jeu social"
    And "a[href*=\"/course/view.php\"]" "css_element" should exist

  @javascript
  Scenario: An unknown slug does not render a faculty page
    When I am on "/local/uckk/faculty.php?slug=voie-inconnue"
    And I wait until the page is ready
    Then I should not see "Voie du Grand Jeu social"
    And I should not see "GJS101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: A file-path-like slug is not accepted as a faculty source
    When I am on "/local/uckk/faculty.php?slug=../atlas/voies/voie_grand_jeu_social"
    And I wait until the page is ready
    Then I should not see "Voie du Grand Jeu social"
    And I should not see "Cartographie du Grand Jeu social"
    And I should not see "GJS101"
    And ".local-uckk-faculty" "css_element" should not exist