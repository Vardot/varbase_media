/**
 * @file
 * Behaviors for the media library enhancements.
 */

(function ($, Drupal) {
  Drupal.behaviors.mediaLibraryEnhancements = {
    attach() {
      // Add value attr to button.
      $(window).on('ajaxComplete', function () {
        if ($('.media-library-widget-modal button.form-submit').length > 0) {
          $('.media-library-widget-modal button.form-submit').attr(
            'value',
            'dialog-submit',
          );
        }
      });
    },
  };
})(window.jQuery, window.Drupal);
