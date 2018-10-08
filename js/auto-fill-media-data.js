/**
 * @file
 * Behaviors Varbase Auto Fill Media data scripts.
 */

(function ($, _, Drupal, drupalSettings) {
  "use strict";

    Drupal.behaviors.autoFillMediaData = {
      attach: function (context, settings) {

      // Save the default value on loading the page.
      var default_name = $("input[name='name[0][value]']").val();
      var default_alt = $("input[name='field_media_image[0][alt]']").val();
      var default_title = $("input[name='field_media_image[0][title]']").val();

      // When we do change the media name.
      $("input[name='name[0][value]']").keyup(function () {
        // And the default alt was empty.
        if (default_alt == '') {
          // Then copy then value of the media name
          $("input[name='field_media_image[0][alt]']").val(this.value);
        }

        // And the default alt was empty.
        if (default_title == '') {
          // Then copy then value of the media name
          $("input[name='field_media_image[0][title]']").val(this.value);
        }

        // And reset the default value for the media name.
        default_name = $("input[name='name[0][value]']").val();
      });
      $("input[name='name[0][value]']").blur(function() {
        default_name = $("input[name='name[0][value]']").val();
        default_alt = $("input[name='field_media_image[0][alt]']").val();
        default_title = $("input[name='field_media_image[0][title]']").val();
      });


      // When we do change the field media image alt value.
      $("input[name='field_media_image[0][alt]']").keyup(function () {
        // And the default media name was empty.
        if (default_name == '') {
          // Then update the media name with the alt value.
          $("input[name='name[0][value]']").val(this.value);
        }
 
        // And the default title was empty.
        if (default_title == '') {
          // Then update the media title with the alt value.
          $("input[name='field_media_image[0][title]']").val(this.value);
        }

        // And reset the default value for the media alt.
        default_alt = $("input[name='field_media_image[0][alt]']").val();
      });
      $("input[name='field_media_image[0][alt]']").blur(function() {
        default_name = $("input[name='name[0][value]']").val();
        default_alt = $("input[name='field_media_image[0][alt]']").val();
        default_title = $("input[name='field_media_image[0][title]']").val();
      });



      // When we do change the media title.
      $("input[name='field_media_image[0][title]']").keyup(function () {
        // And the default media name was empty.
        if (default_name == '') {
          // Then update the media name with the title value.
          $("input[name='name[0][value]']").val(this.value);
        }
        
        // And the default media alt was empty.
        if (default_alt == '') {
          // Then update the media alt with the title value.
          $("input[name='field_media_image[0][alt]']").val(this.value);
        }

        // And reset the default value for the media title.
        default_title = $("input[name='field_media_image[0][title]']").val();
      });
      $("input[name='field_media_image[0][title]']").blur(function() {
        default_name = $("input[name='name[0][value]']").val();
        default_alt = $("input[name='field_media_image[0][alt]']").val();
        default_title = $("input[name='field_media_image[0][title]']").val();
      });
    }
  };
})(window.jQuery, window._, window.Drupal, window.drupalSettings);
