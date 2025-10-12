/**
 * @file
 * Behaviors of Youtube player in the Default OEmbed iframe.
 */

function ready(fn) {
  if (document.readyState !== 'loading') {
    fn();
  } else if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    document.attachEvent('onreadystatechange', () => {
      if (document.readyState !== 'loading') fn();
    });
  }
}

const tag = document.createElement('script');
tag.src = '//www.youtube.com/player_api';
const firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

function onPlayerReady(event) {
  event.target.playVideo();
}

ready(() => {
  const mediaIframe = document.querySelector('iframe');
  mediaIframe.setAttribute('id', 'media-oembed-iframe');

  let playerConfgured = false;

  function actionProcessor(evt) {
    // Manage Youtube video.
    if (evt.data === 'play') {
      const youtubeIframe = document.querySelector(
        'iframe[src*="youtube.com"]',
      );

      if (youtubeIframe !== undefined && youtubeIframe.src !== undefined) {
        if (!playerConfgured) {
          let youtubeURL = String(youtubeIframe.src);
          youtubeURL = youtubeURL.replace(/autoplay=0/gi, 'autoplay=1');
          youtubeURL = youtubeURL.replace(/controls=0/gi, 'controls=1');
          youtubeURL += '&enablejsapi=1';
          youtubeIframe.src = youtubeURL;
          youtubeURL = undefined;

          // eslint-disable-next-line no-new, no-undef
          new window.YT.Player(youtubeIframe.id, {
            events: {
              onReady: onPlayerReady,
            },
          });

          playerConfgured = true;
        }
      }
    }
  }

  // Setup the event listener for messaging.
  if (window.addEventListener) {
    window.addEventListener('message', actionProcessor, false);
  } else {
    window.attachEvent('onmessage', actionProcessor);
  }
});
