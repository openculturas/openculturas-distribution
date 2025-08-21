/**
 * @file
 * Toggles the 'dark' class based on the selected dark mode.
 */

((Drupal, once, window, drupalSettings) => {
  /**
   * Attaches behavior to toggle a class based on the selected dark mode.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Toggles the 'dark' class on the HTML element based on the selected dark
   *   mode and updates the 'data-dark-mode-source' attribute accordingly.
   */
  Drupal.behaviors.darkModeToggle = {
    attach(context) {
      once('darkModeToggle', 'html', context).forEach(() => {
        const $darkMode = localStorage.getItem('dark-mode');
        const $isDarkMode =
          $darkMode === 'dark' ||
          (!$darkMode &&
            window.matchMedia('(prefers-color-scheme: dark)').matches);
        /**
         * @type {NodeListOf<HTMLButtonElement>}
         */
        const $triggerButtons = context.querySelectorAll(
          '.dark-mode-toggle--trigger',
        );
        const $colorSchemeButtonSelector = '.dark-mode-toggle--selector button';
        let $currentActiveStatus = 'system';
        if ($darkMode) {
          $currentActiveStatus = $isDarkMode ? 'dark' : 'light';
        }
        /**
         * @param {NodeListOf<HTMLButtonElement>} buttons Buttons inside popover element.
         */
        const $setInitialActiveStatus = (buttons) => {
          const $button = Array.from(buttons).find((button) => {
            return button.value === $currentActiveStatus;
          });
          if ($button) {
            $button.dataset.active = 'true';
          }
        };

        /**
         * @param {string} value Value of the dark toggle.
         */
        const $setActiveButton = (value) => {
          $triggerButtons.forEach((button) => {
            button.dataset.icon = value;
          });
        };
        const $addEventListener = ($button) => {
          $button.addEventListener('click', (event) => {
            $setActiveButton(event.target.value);
            const $buttons = document.querySelectorAll(
              $colorSchemeButtonSelector,
            );
            // Reset state.
            $buttons.forEach(($currentButton) => {
              delete $currentButton.dataset.active;
            });
            delete event.target.dataset.active;
            if (event.target.value === 'light') {
              event.target.dataset.active = 'true';
              // When the user explicitly chooses the light mode.
              localStorage.setItem('dark-mode', 'light');
              document.documentElement.classList.remove('dark');
              if (drupalSettings?.gin?.darkmode_class) {
                document.documentElement.classList.remove(
                  drupalSettings.gin.darkmode_class,
                );
              }
              document.documentElement.setAttribute(
                'data-dark-mode-source',
                'user',
              );
            } else if (event.target.value === 'dark') {
              event.target.dataset.active = 'true';
              // When the user explicitly chooses the dark mode.
              localStorage.setItem('dark-mode', 'dark');
              document.documentElement.classList.add('dark');
              if (drupalSettings?.gin?.darkmode_class) {
                document.documentElement.classList.add(
                  drupalSettings.gin.darkmode_class,
                );
              }
              document.documentElement.setAttribute(
                'data-dark-mode-source',
                'user',
              );
            } else {
              event.target.dataset.active = 'true';
              // Whenever the user explicitly chooses to respect/switch back to the
              // OS preference.
              localStorage.removeItem('dark-mode');
              document.documentElement.classList.toggle(
                'dark',
                window.matchMedia('(prefers-color-scheme: dark)').matches,
              );
              if (drupalSettings?.gin?.darkmode_class) {
                document.documentElement.classList.toggle(
                  drupalSettings.gin.darkmode_class,
                  window.matchMedia('(prefers-color-scheme: dark)').matches,
                );
              }
              document.documentElement.setAttribute(
                'data-dark-mode-source',
                'system',
              );
            }
          });
        };

        $setActiveButton($currentActiveStatus);
        const $colorSchemeButtonSelectorWrapper = context.querySelectorAll(
          '.dark-mode-toggle--selector',
        );
        if ($colorSchemeButtonSelectorWrapper.length) {
          const $buttons = document.querySelectorAll(
            $colorSchemeButtonSelector,
          );
          $setInitialActiveStatus($buttons);
          once('darkModeToggle', $colorSchemeButtonSelector, context).forEach(
            ($button) => {
              $addEventListener($button);
            },
          );
        }
        window
          .matchMedia('(prefers-color-scheme: dark)')
          .addEventListener('change', (e) => {
            // When the system preference changes and the user has chosen to
            // respect the system preference.
            if (
              document.documentElement.getAttribute('data-dark-mode-source') ===
              'system'
            ) {
              document.documentElement.classList.toggle('dark', e.matches);
              if (drupalSettings?.gin?.darkmode_class) {
                document.documentElement.classList.toggle(
                  drupalSettings.gin.darkmode_class,
                  window.matchMedia('(prefers-color-scheme: dark)').matches,
                );
              }
            }
          });
      });
    },
  };
})(Drupal, once, window, drupalSettings);
