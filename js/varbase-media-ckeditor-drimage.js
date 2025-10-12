/**
 * @file
 * Provides Varbase Media CKEditor 5 integration with Drimage.
 */

((Drupal) => {
  Drupal.behaviors.varbaseMediaCKEditorDrimage = {
    attach() {
      let timer;
      clearTimeout(timer);
      timer = setTimeout(Drupal.drimage_improved.init, 5, document);

      window.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('focus', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('click', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('mouseleave', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('mouseover', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('blur', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('keyup', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });

      window.addEventListener('toggle', () => {
        clearTimeout(timer);
        timer = setTimeout(Drupal.drimage_improved.init, 100);
      });
    },
  };
})(Drupal);
