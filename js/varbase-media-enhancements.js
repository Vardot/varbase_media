/**
 * @file
 * Behaviors for the media library enhancements.
 */

(function ($, _, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.mediaLibraryEnhancements = {
    attach: function (context) {
      // Add value attr to button.
      $(window).on('ajaxComplete', function () {
        if ($('.media-library-widget-modal button.form-submit').length > 0) {
          $('.media-library-widget-modal button.form-submit').attr('value', 'dialog-submit');
        }
      });
    }
  };

})(window.jQuery, window._, window.Drupal, window.drupalSettings);
