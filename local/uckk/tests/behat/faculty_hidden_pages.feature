@local @local_uckk @uckk @faculty @faculty_hidden_pages
Feature: Hidden UCKK faculty pages
  In order to prevent unpublished faculty profiles from leaking public Atlas or Moodle data
  As a visitor
  I need hidden, draft, restricted, and invalid faculty pages to fail closed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                    |
      | player1  | Public    | Player   | player1@example.test     |
    And the following "categories" exist:
      | name                          | category | idnumber |
      | UCKK Métaphysique             | 0        | UCKK-ME  |
      | UCKK Sciences politiques      | 0        | UCKK-SP  |
      | UCKK IA gouvernable           | 0        | UCKK-IA  |
    And the following "courses" exist:
      | fullname                                      | shortname | category | format | visible |
      | ME101 — Métaphysique opératoire               | ME101     | UCKK-ME  | topics | 1       |
      | SP101 — Cartographie du pouvoir               | SP101     | UCKK-SP  | topics | 1       |
      | IA101 — Gouvernance des systèmes intelligents  | IA101     | UCKK-IA  | topics | 1       |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | player1 | ME101  | student |
      | player1 | SP101  | student |
      | player1 | IA101  | student |
    And I purge all Moodle caches

  @javascript
  Scenario: An anonymous visitor cannot open a hidden faculty page
    When I am on "/local/uckk/faculty.php?slug=metaphysique"
    And I wait until the page is ready
    Then I should not see "Voie de Métaphysique"
    And I should not see "Métaphysique opératoire"
    And I should not see "ME101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: An anonymous visitor cannot open a draft faculty page
    When I am on "/local/uckk/faculty.php?slug=sciences-politiques"
    And I wait until the page is ready
    Then I should not see "Voie des Sciences politiques"
    And I should not see "Cartographie du pouvoir"
    And I should not see "SP101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: An anonymous visitor cannot open a restricted faculty page without authentication
    When I am on "/local/uckk/faculty.php?slug=ia-gouvernable"
    And I wait until the page is ready
    Then I should not see "Voie de l’IA gouvernable"
    And I should not see "Gouvernance des systèmes intelligents"
    And I should not see "IA101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: A logged-in learner without faculty publishing rights cannot open a hidden faculty page
    Given I log in as "player1"
    When I am on "/local/uckk/faculty.php?slug=metaphysique"
    And I wait until the page is ready
    Then I should not see "Voie de Métaphysique"
    And I should not see "Métaphysique opératoire"
    And I should not see "ME101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: A logged-in learner without faculty publishing rights cannot open a draft faculty page
    Given I log in as "player1"
    When I am on "/local/uckk/faculty.php?slug=sciences-politiques"
    And I wait until the page is ready
    Then I should not see "Voie des Sciences politiques"
    And I should not see "Cartographie du pouvoir"
    And I should not see "SP101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: A hidden faculty page does not leak public Moodle course links
    When I am on "/local/uckk/faculty.php?slug=metaphysique"
    And I wait until the page is ready
    Then I should not see "Cours associés"
    And I should not see "ME101 — Métaphysique opératoire"
    And "a[href*=\"/course/view.php\"]" "css_element" should not exist

  @javascript
  Scenario: A draft faculty page does not leak Atlas projection content
    When I am on "/local/uckk/faculty.php?slug=sciences-politiques"
    And I wait until the page is ready
    Then I should not see "Programme"
    And I should not see "Cours"
    And I should not see "SP101"
    And I should not see "SP102"
    And I should not see "SP103"
    And I should not see "SP104"
    And I should not see "SP105"
    And I should not see "SP106"
    And I should not see "SP107"
    And I should not see "SP108"
    And I should not see "SP109"
    And I should not see "SP110"

  @javascript
  Scenario: A hidden faculty page does not leak private learner or badge data
    Given I log in as "player1"
    When I am on "/local/uckk/faculty.php?slug=metaphysique"
    And I wait until the page is ready
    Then I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Badge personnel obtenu"
    And I should not see "Données privées d’étudiant"

  @javascript
  Scenario: A missing slug fails closed
    When I am on "/local/uckk/faculty.php"
    And I wait until the page is ready
    Then I should not see "Voie du Grand Jeu social"
    And I should not see "GJS101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: An unknown slug fails closed
    When I am on "/local/uckk/faculty.php?slug=faculty-hidden-behat-fixture"
    And I wait until the page is ready
    Then I should not see "Voie du Grand Jeu social"
    And I should not see "Voie de Métaphysique"
    And I should not see "GJS101"
    And I should not see "ME101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: A file-path-like slug cannot load a hidden faculty file
    When I am on "/local/uckk/faculty.php?slug=../content/faculties/metaphysique.faculty"
    And I wait until the page is ready
    Then I should not see "Voie de Métaphysique"
    And I should not see "Métaphysique opératoire"
    And I should not see "ME101"
    And ".local-uckk-faculty" "css_element" should not exist

  @javascript
  Scenario: An Atlas file-path-like slug cannot load a hidden Voie file
    When I am on "/local/uckk/faculty.php?slug=../atlas/voies/voie_metaphysique"
    And I wait until the page is ready
    Then I should not see "Voie de Métaphysique"
    And I should not see "Métaphysique opératoire"
    And I should not see "ME101"
    And ".local-uckk-faculty" "css_element" should not exist