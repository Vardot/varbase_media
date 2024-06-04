/**
 * @file
 * Provides Varbase Media CKEditor 5 integration with Drimage.
 */

((Drupal) => {
  Drupal.behaviors.varbaseMediaCKEditorDrimage = {
    attach: function (context) {

      var timer;
      clearTimeout(timer);
      timer = setTimeout(Drupal.drimage_improved.init, 5, document);

      addEventListener('mouseenter', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('focus', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('click', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('mouseleave', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('mouseover', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('blur', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      addEventListener('toggle', function () {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

    }
  };

})(Drupal);
