/**
 * @file
 * Behaviors for the media library enhancements.
 */

(function mediaLibraryEnhancementsWrapper($, _, Drupal) {
  Drupal.behaviors.mediaLibraryEnhancements = {
    attach: function attach() {
      // Add value attr to button.
      $(window).on('ajaxComplete', function onAjaxComplete() {
        if ($('.media-library-widget-modal button.form-submit').length > 0) {
          $('.media-library-widget-modal button.form-submit').attr(
            'value',
            'dialog-submit',
          );
        }
      });
    },
  };
})(window.jQuery, window._, window.Drupal);
