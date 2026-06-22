@local @local_uckk @uckk @faculty @faculty_admin_validation
Feature: UCKK faculty admin validation
  In order to keep public faculty pages aligned with Atlas, Faculty JSON, and Moodle mappings
  As a UCKK faculty administrator
  I need to validate Atlas Voies, Faculty profiles, and cross-file contracts from an admin page

  Background:
    Given the following "users" exist:
      | username     | firstname | lastname | email                         |
      | facultyadmin | Faculty   | Admin    | facultyadmin@example.test     |
      | player1      | Public    | Player   | player1@example.test          |
    And the following "role assigns" exist:
      | user         | role    | contextlevel | reference |
      | facultyadmin | manager | System       |           |
    And the following "categories" exist:
      | name                  | category | idnumber |
      | UCKK Grand Jeu social | 0        | UCKK-GJS |
      | UCKK Économie         | 0        | UCKK-EC  |
      | UCKK Écologie         | 0        | UCKK-ECL |
    And the following "courses" exist:
      | fullname                                  | shortname | category | format | visible |
      | GJS101 — Cartographie du Grand Jeu social | GJS101    | UCKK-GJS | topics | 1       |
      | EC101 — Cartographie économique           | EC101     | UCKK-EC  | topics | 1       |
      | ECL101 — Cartographie écologique          | ECL101    | UCKK-ECL | topics | 1       |
    And I purge all Moodle caches

  @javascript
  Scenario: Anonymous visitors cannot open the faculty validation page
    When I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    Then I should not see "Validation des profils publics de faculté"
    And I should not see "Validation Atlas"
    And I should not see "grand-jeu-social.faculty.json"
    And I should not see "voie_grand_jeu_social.json"
    And ".local-uckk-faculty-validation" "css_element" should not exist

  @javascript
  Scenario: Logged-in users without admin capability cannot open the faculty validation page
    Given I log in as "player1"
    When I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    Then I should not see "Validation des profils publics de faculté"
    And I should not see "Validation Atlas"
    And I should not see "grand-jeu-social.faculty.json"
    And I should not see "voie_grand_jeu_social.json"
    And ".local-uckk-faculty-validation" "css_element" should not exist

  @javascript
  Scenario: A faculty administrator can open the validation page
    Given I log in as "facultyadmin"
    When I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    Then I should see "Validation des profils publics de faculté"
    And I should see "Validation Faculty"
    And I should see "Validation Atlas"
    And I should see "Validation croisée"
    And ".local-uckk-faculty-validation" "css_element" should exist
    And "form[action*=\"faculty_validate.php\"]" "css_element" should exist

  @javascript
  Scenario: A faculty administrator can validate all Faculty JSON profiles
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les profils Faculty"
    And I wait until the page is ready
    Then I should see "Validation Faculty"
    And I should see "grand-jeu-social.faculty.json"
    And I should see "economie.faculty.json"
    And I should see "ecologie.faculty.json"
    And I should see "sciences-politiques.faculty.json"
    And I should see "linguistique-architecture-du-sens.faculty.json"
    And I should see "metaphysique.faculty.json"
    And I should see "ia-gouvernable.faculty.json"
    And I should see "intervention-sociale-systemes-humains.faculty.json"
    And I should see "architecture-sociotechnique.faculty.json"
    And I should see "ecosysteme-digital-koa.faculty.json"
    And I should see "schema_version"
    And I should see "UCKK-FACULTY-0.1"
    And I should not see "Coding error"
    And I should not see "Exception"
    And I should not see "Debug info"

  @javascript
  Scenario: A faculty administrator can validate all Atlas Voie JSON files
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les JSON Atlas"
    And I wait until the page is ready
    Then I should see "Validation Atlas"
    And I should see "voie_grand_jeu_social.json"
    And I should see "voie_economie.json"
    And I should see "voie_ecologie.json"
    And I should see "voie_sciences_politiques.json"
    And I should see "voie_linguistique_architecture_du_sens.json"
    And I should see "voie_metaphysique.json"
    And I should see "voie_ia_gouvernable.json"
    And I should see "voie_intervention_sociale_systemes_humains.json"
    And I should see "voie_architecture_sociotechnique.json"
    And I should see "voie_ecosysteme_digital_koa.json"
    And I should see "UCKK-ATLAS-0.2-draft"
    And I should not see "Coding error"
    And I should not see "Exception"
    And I should not see "Debug info"

  @javascript
  Scenario: Faculty validation reports required public profile contract checks
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les profils Faculty"
    And I wait until the page is ready
    Then I should see "faculty_id"
    And I should see "voie_id"
    And I should see "slug"
    And I should see "source_atlas.file"
    And I should see "moodle.category_idnumber"
    And I should see "moodle.course_prefix"
    And I should see "identity"
    And I should see "seo"
    And I should see "hero"
    And I should see "atlas_projection"
    And I should see "dynamic_blocks"
    And I should see "governance"

  @javascript
  Scenario: Atlas validation reports required Voie contract checks
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les JSON Atlas"
    And I wait until the page is ready
    Then I should see "voie_id"
    And I should see "code"
    And I should see "cours_conceptuels"
    And I should see "10 courses"
    And I should see "orders 1..10"
    And I should see "concept_maitre"
    And I should see "concept_associe"
    And I should see "artefact_maitrise"
    And I should see "criteres_passage"
    And I should see "relations_intervoies"

  @javascript
  Scenario: Cross-file validation checks Faculty, Atlas, and Moodle alignment
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les alignements"
    And I wait until the page is ready
    Then I should see "Validation croisée"
    And I should see "faculty.voie_id"
    And I should see "atlas.voie_id"
    And I should see "faculty.moodle.course_prefix"
    And I should see "atlas.code"
    And I should see "source_atlas.file"
    And I should see "faculty.slug"
    And I should see "faculty_id"
    And I should see "category_idnumber"
    And I should see "course_prefix"
    And I should not see "Coding error"
    And I should not see "Exception"
    And I should not see "Debug info"

  @javascript
  Scenario: A single Faculty profile can be validated by slug
    Given I log in as "facultyadmin"
    When I am on "/local/uckk/faculty_validate.php?type=faculty&slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Validation Faculty"
    And I should see "grand-jeu-social"
    And I should see "grand-jeu-social.faculty.json"
    And I should see "faculty_grand_jeu_social"
    And I should see "voie_grand_jeu_social"
    And I should not see "voie_economie.json"
    And I should not see "economie.faculty.json"

  @javascript
  Scenario: A single Atlas Voie can be validated by voie id
    Given I log in as "facultyadmin"
    When I am on "/local/uckk/faculty_validate.php?type=atlas&voie=voie_grand_jeu_social"
    And I wait until the page is ready
    Then I should see "Validation Atlas"
    And I should see "voie_grand_jeu_social"
    And I should see "voie_grand_jeu_social.json"
    And I should see "GJS"
    And I should not see "voie_economie.json"
    And I should not see "economie.faculty.json"

  @javascript
  Scenario: Invalid validation targets fail closed
    Given I log in as "facultyadmin"
    When I am on "/local/uckk/faculty_validate.php?type=faculty&slug=../content/faculties/grand-jeu-social.faculty"
    And I wait until the page is ready
    Then I should not see "faculty_grand_jeu_social"
    And I should not see "voie_grand_jeu_social.json"
    And I should not see "GJS101"
    And I should not see "Cartographie du Grand Jeu social"
    And ".local-uckk-faculty-validation-report" "css_element" should not exist

  @javascript
  Scenario: Validation reports do not dump complete raw JSON content
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les profils Faculty"
    And I wait until the page is ready
    Then I should see "Validation Faculty"
    And I should not see "\"cours_conceptuels\""
    And I should not see "\"concepts_associes\""
    And I should not see "\"criteres_passage\""
    And I should not see "\"dynamic_blocks\""
    And I should not see "{"
    And I should not see "}"

  @javascript
  Scenario: Validation reports expose counts and status instead of private data
    Given I log in as "facultyadmin"
    And I am on "/local/uckk/faculty_validate.php"
    And I wait until the page is ready
    When I press "Valider les alignements"
    And I wait until the page is ready
    Then I should see "Validation croisée"
    And I should see "status"
    And I should see "counts"
    And I should see "hash"
    And I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Badge personnel obtenu"
    And I should not see "Données privées d’étudiant"