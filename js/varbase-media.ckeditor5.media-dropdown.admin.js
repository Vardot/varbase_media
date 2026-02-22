(function varbaseMediaDropdownScope(Drupal, $, once) {
  // Function to update the 'View mode' dropdown
  function updateViewModeDropdown(isImageMedia) {
    const viewModeDropdown = $('.ck-balloon-panel.ck-toolbar-container')
      .find('[data-cke-tooltip-text="View mode"]')
      .closest('.ck.ck-dropdown');
    viewModeDropdown.css('display', isImageMedia ? 'none' : 'inline-block');
  }

  // Function to update the 'Resize media image' dropdown
  function updateResizeOptionsDropdown(isImageMedia) {
    const resizeOptionsDropdown = $('.ck-balloon-panel.ck-toolbar-container')
      .find('.ck-dropdown__button.ck-resize-image-button')
      .closest('.ck.ck-dropdown');
    resizeOptionsDropdown.css(
      'display',
      isImageMedia ? 'inline-block' : 'none',
    );
  }

  // Function to check if the clicked element is a drupalMedia element.
  function checkClickedIsMedia(modelElement) {
    return modelElement ? modelElement.is('element', 'drupalMedia') : false;
  }

  // Function to update dropdowns based on media type.
  function updateDropdowns(isImageMedia) {
    const toolbarVisible = $('.ck-balloon-panel.ck-toolbar-container').hasClass(
      'ck-balloon-panel_visible',
    );
    if (toolbarVisible) {
      updateViewModeDropdown(isImageMedia);
      updateResizeOptionsDropdown(isImageMedia);
    }
  }

  // Handle click events on media elements
  function handleMediaClick(editor, domEventData) {
    const clickedElement = domEventData.target;
    const modelElement = editor.editing.mapper.toModelElement(clickedElement);
    const isMedia = checkClickedIsMedia(modelElement);
    if (isMedia) {
      const isImageMedia = !!modelElement.getAttribute('drupalMediaIsImage');
      updateDropdowns(isImageMedia);
    }
  }

  // Handle media data changes
  function handleMediaDataChange(editor) {
    const { selection } = editor.model.document;
    const selectedElement = selection.getSelectedElement();
    const isMedia = checkClickedIsMedia(selectedElement);
    if (isMedia) {
      const isImageMedia = !!selectedElement.getAttribute('drupalMediaIsImage');
      updateDropdowns(isImageMedia);
    }
  }

  // Attach behavior to a single editor instance
  function attachEditorBehavior(editor) {
    // Listen for view clicks in the editor.
    editor.editing.view.document.on('click', (event, domEventData) => {
      handleMediaClick(editor, domEventData);
    });

    // Listen for media insertions.
    editor.model.document.on('change:data', () => {
      handleMediaDataChange(editor);
    });
  }

  // Process each editable element
  function processEditableElement() {
    // Check for CKEditor 5 instances and attach behavior.
    if (Drupal.CKEditor5Instances) {
      Drupal.CKEditor5Instances.forEach(attachEditorBehavior);
    }
  }

  // Varbase Media Drupal Media toolbar dropdown visibility.
  Drupal.behaviors.varbaseMediaDropdown = {
    attach(context) {
      $(
        once(
          'varbase-media-dropdown-visibility',
          '.ck-editor__editable',
          context,
        ),
      ).each(processEditableElement);
    },
  };
})(Drupal, jQuery, once);
