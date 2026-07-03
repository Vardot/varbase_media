@varbase_media @content
Feature: Varbase Media - add media form
  As a site administrator
  I want the media add form to be reachable with Varbase Media enabled

  Scenario: The media add-media overview form is reachable
    Given I am a logged in user with the "Webmaster" user
    When I open the administration page "/media/add"
    Then I should not see "Page not found"

  Scenario: The Image media add form is reachable
    Given I am a logged in user with the "Webmaster" user
    When I open the administration page "/media/add/image"
    Then I should not see "Page not found"
