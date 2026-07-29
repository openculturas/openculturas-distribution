((Drupal, once) => {
  /**
   * Re-usable clean click event: only when primary mouse key is clicked,
   * leave modifier key combinations alone (for UX and a11y)
   * @param {MouseEvent} event The click event.
   */
  function isPlainPrimaryClick(event) {
    return (
      event.button === 0 &&
      !event.ctrlKey &&
      !event.shiftKey &&
      !event.altKey &&
      !event.metaKey
    );
  }

  /**
   * Extend clickable area in teasers: full teaser
   * Conditions: wrapper article.teaser-fully-linked containing a link inside .teaser--title
   * @type {Drupal.behavior}
   */
  Drupal.behaviors.teaserFullyLinkedBehavior = {
    attach(context) {
      // Use context to filter the DOM to only the elements of interest,
      // and use once() to guarantee that our callback function processes
      // any given element one time at most, regardless of how many times
      // the behaviour itself is called (it is not sufficient in general
      // to assume an element will only ever appear in a single context).
      once(
        'teaserFullyLinkedBehavior',
        '.teaser-fully-linked',
        context,
      ).forEach((teaser) => {
        let down;
        teaser.addEventListener('mousedown', () => {
          down = Date.now();
        });
        teaser.addEventListener('mouseup', (event) => {
          if (
            event.target &&
            Date.now() - down < 200 &&
            isPlainPrimaryClick(event) &&
            !(
              event.target.parentNode.classList.contains('contextual') ||
              event.target.parentNode.classList.contains('quick-info') ||
              event.target.closest('.popover-content')
            )
          ) {
            teaser.querySelector('.teaser--title a').click();
          }
        });
      });
    },
  };

  /**
   * Extend clickable area in teasers: teaser images
   * Conditions: wrapper article.teaser-image-linked containing a link inside .teaser--title
   * @type {Drupal.behavior}
   */
  Drupal.behaviors.teaserImageLinkedBehavior = {
    attach(context) {
      // Use context to filter the DOM to only the elements of interest,
      // and use once() to guarantee that our callback function processes
      // any given element one time at most, regardless of how many times
      // the behaviour itself is called (it is not sufficient in general
      // to assume an element will only ever appear in a single context).
      once(
        'teaserImageLinkedBehavior',
        'article.teaser-image-linked',
        context,
      ).forEach((teaser) => {
        const clickableElement = teaser.querySelector(
          '.field--name-field-mood-image, .no-image, .profile--picture',
        );
        const teaserHeadlineLink = teaser.querySelector('.teaser--title a');
        if (clickableElement && teaserHeadlineLink) {
          clickableElement.addEventListener('click', (event) => {
            if (isPlainPrimaryClick(event)) {
              event.preventDefault();
              teaserHeadlineLink.click();
            }
          });
          // Unsetting CSS cursor when modifier key is pressed during hover
          if (
            clickableElement &&
            clickableElement.classList.contains('no-image')
          ) {
            const updateCursor = (event) => {
              if (
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                event.metaKey
              ) {
                clickableElement.style.cursor = 'default';
              } else {
                clickableElement.style.cursor = 'pointer';
              }
            };
            clickableElement.addEventListener('mousemove', updateCursor);
            clickableElement.addEventListener('mouseleave', () => {
              clickableElement.style.cursor = '';
            });
          }
        }
      });
    },
  };

  /**
   * @file
   * Provides a behavior to set a custom property based on an element's scroll height.
   * (Stop sticky title above .section--essentials in .oc_hero_layout_1)
   */
  Drupal.behaviors.sectionEssentialsHeightBehavior = {
    attach(context) {
      // Use 'once()' to ensure this logic runs only once per element, even if attach is called multiple times.
      // Query within the provided 'context' to target only newly added or updated elements.
      once('oc-title-bottom-stop', 'div.section--essentials', context).forEach(
        (element) => {
          // Define the function to update the custom property
          function updateOffsetHeight() {
            const absoluteHeightInPixels = element.scrollHeight;
            const rootFontSize = parseFloat(
              getComputedStyle(document.documentElement).fontSize,
            );
            const heightInRem = absoluteHeightInPixels / rootFontSize;

            // Find the closest ancestor with the class 'wrapper--content-header'
            const parentContainer = element.closest('.wrapper--content-header');

            // Set the custom property on the parent container
            if (parentContainer) {
              parentContainer.style.setProperty(
                '--oc-title-bottom-stop',
                `${heightInRem}rem`,
              );
            }
          }

          // Create a ResizeObserver to watch for changes to the element's size
          const resizeObserver = new ResizeObserver(updateOffsetHeight);

          // Observe the element
          resizeObserver.observe(element);

          // Set the initial value
          updateOffsetHeight();
        },
      );
    },
  };
})(Drupal, once);

/**
 * @file
 * Replaced Drupal cores ajax throbber(s), see: https://www.drupal.org/node/2974681
 *
 * Customization of throbber message only:
 * Drupal.theme.ajaxProgressMessage = message => '<div class="my-message">' + message + '</div>';
 */
(($, Drupal) => {
  Drupal.theme.ajaxProgressThrobber = () => {
    return `<div class="ajax-spinner ajax-spinner--inline"><span class="ajax-spinner__label">${Drupal.t(
      'Loading&hellip;',
      {},
      {
        context: 'Loading text for Drupal cores Ajax throbber (inline)',
      },
    )}</span></div>`;
  };

  Drupal.theme.ajaxProgressIndicatorFullscreen = () => {
    return `<div class="ajax-spinner ajax-spinner--fullscreen"><span class="ajax-spinner__label">${Drupal.t(
      'Loading&hellip;',
      {},
      {
        context: 'Loading text for Drupal cores Ajax throbber (fullscreen)',
      },
    )}</span></div>`;
  };
})(jQuery, Drupal);
