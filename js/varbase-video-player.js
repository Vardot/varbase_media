/**
 * @file
 * Behaviors Varbase Video Player general scripts for videos with cover images.
 */

(function varbaseVideoPlayer($, Drupal) {
  Drupal.behaviors.varbaseVideoPlayer = {
    attach() {
      $('.js-video-player-icon').on('click', function onVideoIconClick() {
        $(this).fadeOut(500);

        // Locally Hosted Video.
        // Use descendant selector (not direct child >) to work with Drupal's
        // standard field template wrapper divs.
        const $video = $(this)
          .closest('.media')
          .find('.varbase-video-player video');

        if ($video.length > 0) {
          $video.get(0).play();
        }

        // Remote Video (YouTube, Vimeo, etc.) via Drupal oembed iframe.
        // The iframe src is the Drupal oembed route (/media/oembed?url=...).
        // The oembed-frame JS inside the iframe listens for postMessage('play').
        const $iframe = $(this)
          .closest('.media')
          .find('.varbase-video-player iframe');

        if ($iframe.length > 0) {
          $iframe.get(0).contentWindow.postMessage('play', '*');
        }
      });
    },
  };
})(window.jQuery, window.Drupal);
