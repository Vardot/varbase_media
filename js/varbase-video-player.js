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

        // Locally Hosted Video.
        // ---------------------------------------------------------------------
        if ($(this).closest(".field.field--type-entity-reference").find('.media--type-video video').length > 0) {
          $(this).closest(".field.field--type-entity-reference").find('.media--type-video video').get(0).play();
        }

        if ($(this).closest(".embedded-entity").find('.media--type-video video').length > 0) {
          $(this).closest(".embedded-entity").find('.media--type-video video').get(0).play();
        }
        // ---------------------------------------------------------------------

        // Remote Youtube Video.
        // ---------------------------------------------------------------------
        if ($(this).closest('.field.field--type-entity-reference').find('.media--type-remote-video iframe[src*="youtube.com"]').length > 0) {
          var closestYoutubeIframe = $(this).closest('.field.field--type-entity-reference').find('.media--type-remote-video iframe[src*="youtube.com"]');
          var closestInnerYoutubeIframe = closestYoutubeIframe.contents().find('iframe[src*="youtube.com"]');
          var youtubeURL = String(closestInnerYoutubeIframe.get(0).src); 
          youtubeURL = youtubeURL.replace(/autoplay=0/gi, "autoplay=1");
          youtubeURL = youtubeURL.replace(/controls=0/gi, "controls=1");
          closestInnerYoutubeIframe.get(0).src = youtubeURL;
          ev.preventDefault();
        }

        if ($(this).closest('.embedded-entity').find('.media--type-remote-video iframe[src*="youtube.com"]').length > 0) {
          var closestYoutubeIframe = $(this).closest('.embedded-entity').find('.media--type-remote-video iframe[src*="youtube.com"]');
          var closestInnerYoutubeIframe = closestYoutubeIframe.contents().find('iframe[src*="youtube.com"]');
          var youtubeURL = String(closestInnerYoutubeIframe.get(0).src); 
          youtubeURL = youtubeURL.replace(/autoplay=0/gi, "autoplay=1");
          youtubeURL = youtubeURL.replace(/controls=0/gi, "controls=1");
          closestInnerYoutubeIframe.get(0).src = youtubeURL;
          ev.preventDefault();
        }
        // ---------------------------------------------------------------------


        // Remote Vimeo Video.
        // ---------------------------------------------------------------------
        if ($(this).closest('.field.field--type-entity-reference').find('.media--type-remote-video iframe[src*="vimeo.com"]').length > 0) {
          var closestVimoIframe = $(this).closest('.field.field--type-entity-reference').find('.media--type-remote-video iframe[src*="vimeo.com"]').get(0);
          var closestInnerVimoIframe = closestVimoIframe.contents().find('iframe[src*="vimeo.com"]').get(0);
          var vimoPlayer = new Vimeo.Player(closestInnerVimoIframe);
          vimoPlayer.play();
        }

        if ($(this).closest('.embedded-entity').find('.media--type-remote-video iframe[src*="vimeo.com"]').length > 0) {
          var closestVimoIframe = $(this).closest('.embedded-entity').find('.media--type-remote-video iframe[src*="vimeo.com"]').get(0);
          var closestInnerVimoIframe = closestVimoIframe.contents().find('iframe[src*="vimeo.com"]').get(0);
          var vimoPlayer = new Vimeo.Player(closestInnerVimoIframe);
          vimoPlayer.play();
        }
        // ---------------------------------------------------------------------

      });
    }
  };
})(window.jQuery, window._, window.Drupal, window.drupalSettings);
