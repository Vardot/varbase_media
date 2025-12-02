/**
 * @file
 * Behaviors for AI Image Alt Text Button Inline.
 */

(function aiImageAltTextButtonInlineScope(Drupal, once) {
  /**
   * Attaches AI image alt text inline button behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the AI inline button behavior to the page.
   */
  Drupal.behaviors.aiImageAltTextButtonInline = {
    attach(context) {
      // Use once to ensure we only process each wrapper once.
      const buttonWrappers = once(
        'ai-button-inline',
        '.ai-alt-text-generation-wrapper',
        context,
      );

      buttonWrappers.forEach(function processWrapper(wrapper) {
        // Find the alt text input field's parent container.
        // Support all media image fields using attribute selector pattern.
        let altField = null;
        let metaItems = null;

        // Search up the DOM tree for form-managed-file.
        let currentElement = wrapper.parentElement;
        while (
          currentElement &&
          !currentElement.classList.contains('form-managed-file')
        ) {
          currentElement = currentElement.parentElement;
        }

        if (currentElement) {
          metaItems = currentElement.querySelector(
            '.form-managed-file__meta-items',
          );
          if (metaItems) {
            // Find any alt field matching the pattern for media image fields.
            altField = metaItems.querySelector(
              '[class*="field"][class*="media"][class*="image"][class*="-alt"]',
            );
          }
        }

        // Fallback: search in the entire context if not found in form-managed-file.
        if (!altField) {
          altField = context.querySelector(
            '[class*="field"][class*="media"][class*="image"][class*="-alt"]',
          );
        }

        if (!altField) {
          return;
        }

        // Get the input element.
        const input = altField.querySelector('input[type="text"]');
        if (!input) {
          return;
        }

        // Store references for layout management.
        let originalParent = wrapper.parentElement;
        let inputWrapper = null;

        /**
         * Apply inline layout for large screens (>= 1200px).
         */
        const applyInlineLayout = function applyInlineLayoutHandler() {
          // Find the input wrapper div (may not exist in media library).
          inputWrapper = altField.querySelector(
            '.help-icon__element-has-description',
          );

          if (!inputWrapper) {
            // In media library, we need to create a wrapper for the input and button.
            inputWrapper = document.createElement('div');
            inputWrapper.className = 'ai-input-button-wrapper';

            // Insert wrapper after the label but before the input.
            input.parentNode.insertBefore(inputWrapper, input);

            // Move input into the wrapper.
            inputWrapper.appendChild(input);
          }

          // Store original parent before moving.
          if (!originalParent || wrapper.parentElement !== inputWrapper) {
            originalParent = wrapper.parentElement;
          }

          // Move button into the input wrapper (after the input).
          if (wrapper.parentElement !== inputWrapper) {
            inputWrapper.appendChild(wrapper);
          }

          // Add the class to enable inline mode via CSS.
          if (metaItems) {
            metaItems.classList.add('ai-inline-mode');
          }
        };

        /**
         * Restore original layout for small screens (< 1200px).
         */
        const restoreOriginalLayout = function restoreOriginalLayoutHandler() {
          // Move button back to original parent if it exists.
          if (originalParent && wrapper.parentElement !== originalParent) {
            originalParent.appendChild(wrapper);
          }

          // Remove the inline mode class.
          if (metaItems) {
            metaItems.classList.remove('ai-inline-mode');
          }
        };

        /**
         * Handle layout based on screen width.
         */
        const handleLayout = function handleLayoutChange() {
          const isLargeScreen = window.innerWidth >= 1200;
          if (isLargeScreen) {
            applyInlineLayout();
          } else {
            restoreOriginalLayout();
          }
        };

        // Apply initial layout.
        handleLayout();

        // Listen for window resize events with debouncing.
        let resizeTimeout;
        const handleResize = function handleResizeEvent() {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(function resizeDebounce() {
            handleLayout();
          }, 150);
        };

        window.addEventListener('resize', handleResize);

        // Alt-to-Title Auto-Copy Functionality (all screen sizes).
        // Find alt input.
        const altInput = altField.querySelector('input[type="text"]');
        if (!altInput) {
          return;
        }

        // Find title field - support all media image fields.
        let titleField = null;
        if (metaItems) {
          // Search in metaItems for any title field matching the pattern.
          const titleFieldContainer = metaItems.querySelector(
            '[class*="field"][class*="media"][class*="image"][class*="-title"]',
          );
          if (titleFieldContainer) {
            titleField =
              titleFieldContainer.querySelector('input[type="text"]');
          }
        }

        // Fallback: search in the entire context.
        if (!titleField) {
          const titleFieldContainer = context.querySelector(
            '[class*="field"][class*="media"][class*="image"][class*="-title"]',
          );
          if (titleFieldContainer) {
            titleField =
              titleFieldContainer.querySelector('input[type="text"]');
          }
        }

        const aiButton = wrapper.querySelector('.ai-alt-text-generation');

        if (altInput && titleField && aiButton) {
          /**
           * Copy alt text to title (only if title is empty).
           */
          const copyAltToTitle = function copyAltToTitleHandler() {
            const altValue = altInput.value;
            const titleValue = titleField.value;

            // Only copy if alt has content AND title is empty.
            if (
              altValue &&
              altValue.trim() !== '' &&
              (!titleValue || titleValue.trim() === '')
            ) {
              titleField.value = altValue;
              // Trigger change event so Drupal knows the field was updated.
              titleField.dispatchEvent(new Event('change', { bubbles: true }));
              titleField.dispatchEvent(new Event('input', { bubbles: true }));
            }
          };

          // Listen for AI button clicks and check when AI generation completes.
          aiButton.addEventListener('click', function handleAiButtonClick() {
            // Check for alt text value change every 500ms for up to 30 seconds.
            let checkCount = 0;
            const maxChecks = 60;
            const originalValue = altInput.value;

            const checkInterval = setInterval(function checkAltTextChange() {
              checkCount += 1;
              const currentValue = altInput.value;

              // If value changed and is not empty, copy to title.
              if (
                currentValue !== originalValue &&
                currentValue.trim() !== ''
              ) {
                clearInterval(checkInterval);
                copyAltToTitle();
              }

              // Stop checking after max attempts.
              if (checkCount >= maxChecks) {
                clearInterval(checkInterval);
              }
            }, 500);
          });
        }
      });
    },
  };
})(Drupal, once);
