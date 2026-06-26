@varbase_media @admin
Feature: Varbase Media - administration pages
  As a site administrator
  I want the Varbase Media pages to be reachable with all media types available

  Scenario: The Varbase Media pages are reachable for the administrator
    Given I am a logged in user with the "Webmaster" user
    When I open the administration page "/admin/content/media"
    Then I should see "Add media"
    And I should not see "Page not found"
    When I open the administration page "/admin/content/media/bulk-upload/media_bulk_upload"
    Then I should see "Multiple upload"
    And I should not see "Page not found"
    When I open the administration page "/admin/config/varbase/varbase-media"
    Then I should not see "Page not found"
    When I open the administration page "/media/add/image"
    Then I should see "Add Image"
    And I should see "Allowed types"
    When I open the administration page "/media/add/video"
    Then I should not see "Page not found"
    When I open the administration page "/media/add/audio"
    Then I should not see "Page not found"
    When I open the administration page "/media/add/remote_video"
    Then I should not see "Page not found"
    When I open the administration page "/media/add/file"
    Then I should not see "Page not found"
    When I open the administration page "/media/add/gallery"
    Then I should not see "Page not found"
