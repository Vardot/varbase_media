/**
 * @file
 * Behaviors Varbase Video Player general scripts for videos with cover images.
 */

(function ($, Drupal) {
  Drupal.behaviors.varbaseVideoPlayer = {
    attach() {
      $('.js-video-player-icon').on('click', function () {
        $(this).fadeOut(500);

        // Locally Hosted Video.
        if (
          $(this).closest('.media').find('.varbase-video-player > video')
            .length > 0
        ) {
          $(this)
            .closest('.media')
            .find('.varbase-video-player > video')
            .get(0)
            .play();
        }

        // Remote Youtube Video.
        if (
          $(this)
            .closest('.media')
            .find('.varbase-video-player > iframe[src*="youtube.com"]').length >
          0
        ) {
          const closestYoutubeIframe = $(this)
            .closest('.media')
            .find('.varbase-video-player > iframe[src*="youtube.com"]')
            .get(0).contentWindow;
          closestYoutubeIframe.postMessage('play', '*');
        }

        // Remote Vimeo Video.
        if (
          $(this)
            .closest('.media')
            .find('.varbase-video-player > iframe[src*="vimeo.com"]').length > 0
        ) {
          const closestVimeoIframe = $(this)
            .closest('.media')
            .find('.varbase-video-player > iframe[src*="vimeo.com"]')
            .get(0).contentWindow;
          closestVimeoIframe.postMessage('play', '*');
        }
      });
    },
  };
})(window.jQuery, window.Drupal);
