@local @local_uckk @uckk @mediatheque
Feature: Public Médiathèque page
  In order to explore public UCKK media responsibly
  As a visitor
  I need the public Médiathèque page to expose a stable public explorer shell

  @javascript
  Scenario: A visitor can open the public Médiathèque page directly
    When I am on "/local/uckk/mediatheque.php"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Explorateur Médiathèque"
    And I should see "Rechercher dans la Médiathèque"
    And I should see "Type de média"
    And I should see "Appliquer les filtres"

  @javascript
  Scenario: A visitor can open the public Médiathèque page from the public navigation
    When I am on "/local/uckk/index.php"
    And I follow "Médiathèque"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Explorateur Médiathèque"
    And I should see "Rechercher dans la Médiathèque"

  @javascript
  Scenario: A visitor sees the public empty state when no public media matches
    When I am on "/local/uckk/mediatheque.php?q=aucun-resultat-public-uckk"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Aucun média public ne correspond aux filtres."

  @javascript
  Scenario: A visitor can submit a public search without authentication
    When I am on "/local/uckk/mediatheque.php"
    And I wait until the page is ready
    And I set the field "Rechercher dans la Médiathèque" to "archive"
    And I press "Rechercher"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Explorateur Médiathèque"

  @javascript
  Scenario: A visitor can apply public media filters without authentication
    When I am on "/local/uckk/mediatheque.php"
    And I wait until the page is ready
    And I set the field "Type de média" to "Image"
    And I press "Appliquer les filtres"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Explorateur Médiathèque"

  @javascript
  Scenario: A visitor can reset public media filters
    When I am on "/local/uckk/mediatheque.php?q=archive&mediatype=image"
    And I wait until the page is ready
    And I press "Réinitialiser"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should see "Explorateur Médiathèque"
    And I should see "Rechercher dans la Médiathèque"

  @javascript
  Scenario: The public Médiathèque page does not expose internal archive controls
    When I am on "/local/uckk/mediatheque.php"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should not see "Ajouter un média"
    And I should not see "Modifier le média"
    And I should not see "Télécharger l’original"
    And I should not see "Exporter"
    And I should not see "Supprimer"

  @javascript
  Scenario: The public Médiathèque page does not expose private implementation fields
    When I am on "/local/uckk/mediatheque.php"
    And I wait until the page is ready
    Then I should see "Médiathèque UCKK"
    And I should not see "provenancehash"
    And I should not see "integritycaseid"
    And I should not see "sourceobjectid"
    And I should not see "internalnote"
    And I should not see "culturalprotocolnote"