/**
 * @file
 * Behaviors Varbase Video Player general scripts.
 */

(function ($, _, Drupal, drupalSettings) {
  "use strict";

    Drupal.behaviors.varbaseVideoPlayer = {
      attach: function (context, settings) {

        $('.js-video-player-icon').on("click", function(ev) {
        $(this).fadeOut(500);

        // Locally hosted Video.
        if ($(this).closest(".field.field--type-entity-reference").find('.media--type-video video').length > 0) {
          $(this).closest(".field.field--type-entity-reference").find('.media--type-video video').get(0).play();
        }

        // Youtube.
        if ($(this).closest('.field.field--type-entity-reference').find('.media--type-video-embed iframe[src*="youtube.com"]').length > 0) {
          var closestYoutubeIframe = $(this).closest('.field.field--type-entity-reference').find('.media--type-video-embed iframe[src*="youtube.com"]');
          var youtubeURL = String(closestYoutubeIframe.get(0).src); 
          youtubeURL = youtubeURL.replace(/autoplay=0/gi, "autoplay=1");
          youtubeURL = youtubeURL.replace(/controls=0/gi, "controls=1");
          closestYoutubeIframe.get(0).src = youtubeURL;
          ev.preventDefault();
        }
        
        // Vimeo.
        if ($(this).closest('.field.field--type-entity-reference').find('iframe[src*="vimeo.com"]').length > 0) {
          var closestVimoIframe = $(this).closest('.field.field--type-entity-reference').find('iframe[src*="vimeo.com"]').get(0);
          var vimoPlayer = new Vimeo.Player(closestVimoIframe);
          vimoPlayer.play();
        }

      });
    }
  };
})(window.jQuery, window._, window.Drupal, window.drupalSettings);
