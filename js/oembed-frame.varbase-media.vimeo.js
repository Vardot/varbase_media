/**
 * @file
 * Behaviors of Vimeo player in the Default OEmbed iframe.
 */

function ready(fn) {
  if (document.readyState !== 'loading') {
    fn();
  } else if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    document.attachEvent('onreadystatechange', function checkReadyState() {
      if (document.readyState !== 'loading') fn();
    });
  }
}

const tag = document.createElement('script');
tag.src = '//player.vimeo.com/api/player.js';
const firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

ready(function initVimeoPlayer() {
  const mediaIframe = document.querySelector('iframe');
  mediaIframe.setAttribute('id', 'media-oembed-iframe');

  let playerConfigured = false;
  let vimeoPlayer;

  function actionProcessor(evt) {
    // Manage Vimeo video.
    if (evt.data === 'play') {
      if (!playerConfigured) {
        const vimeoIframe = document.querySelector('iframe[src*="vimeo.com"]');

        vimeoPlayer = new window.Vimeo.Player(vimeoIframe);
        playerConfigured = true;
      }

      vimeoPlayer.ready().then(function onVimeoReady() {
        vimeoPlayer.getPaused().then(function onPausedCheck(paused) {
          if (paused) {
            vimeoPlayer.play();
          }
        });
      });
    }
  }

  // Setup the event listener for messaging.
  if (window.addEventListener) {
    window.addEventListener('message', actionProcessor, false);
  } else {
    window.attachEvent('onmessage', actionProcessor);
  }
});
