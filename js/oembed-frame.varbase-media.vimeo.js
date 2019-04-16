/**
 * @file
 * Behaviors of Viemo player in the Default OEmbed iframe.
 */

function ready(fn) {
  if (document.readyState != 'loading'){
    fn();
  } else if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    document.attachEvent('onreadystatechange', function() {
      if (document.readyState != 'loading')
        fn();
    });
  }
}

var tag = document.createElement('script');
tag.src = "//player.vimeo.com/api/player.js";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

ready(function() {

  var media_iframe = document.querySelector('iframe');
  media_iframe.setAttribute('id', 'media-oembed-iframe');

  var player_confgured = false;
  var vimeo_player;

  function actionProcessor(evt) {

    // Manage Vimeo video.
    if (evt.data === "play") {
      if (!player_confgured) {
        var vimeo_iframe = document.querySelector('iframe[src*="vimeo.com"]');

        vimeo_player = new Vimeo.Player(vimeo_iframe);
        player_confgured = true;
      }

       vimeo_player.ready().then(function() {
         vimeo_player.getPaused().then(function(paused) {
          if (paused) {
            vimeo_player.play();
          }
        });
      });
    }
  }

  // Setup the event listener for messaging.
  if (window.addEventListener) {
    window.addEventListener("message", actionProcessor, false);
  }
  else {
    window.attachEvent("onmessage", actionProcessor);
  }
});