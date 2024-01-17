(function (Drupal, once) {

  /**
   * Opening / Closing logic for social media links.
   * TO DO: find a more generic solution for the open/close toggling
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.pageSocialShare = {
    attach: function (context, settings) {
      once('init-offcanvas', '#oc--social-share--button', context).forEach((button) => {
        button.addEventListener('click', this.toggleOffcanvasSocial)
      });
    },
    toggleOffcanvasSocial: function(e= null) {
      const $offcanvasMenu = document.getElementById('oc--social-share--layer');
      const $burgerButtons = document.getElementById('oc--social-share--button');
      const $wasOpen = document.body.classList.contains('socialshare-open');

      document.body.classList.toggle('socialshare-open');

      // Accessibility tweaks.
      $burgerButtons.setAttribute('aria-expanded', !($burgerButtons.getAttribute('aria-expanded') === 'true'));
      $offcanvasMenu.setAttribute('aria-hidden', !($offcanvasMenu.getAttribute('aria-hidden') === 'true'));
      if (!$wasOpen) {
        $offcanvasMenu.children.item(0).focus();
      }
    }
  };

} (Drupal, once));

(function (Drupal, once) {

    /**
     * Opening / Closing logic for attribution bubble.
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.pageAttributionBubble = { // Define Drupal Behaviour
      attributionBubbleToggleSelector: 'button.attribution--bubble--toggle', // Define Selector for Toggle Button
      attributionBubbleDialogSelector: 'dialog.attribution--bubble--dialog', // Define Selector for Dialog
  
      // Drupal base function, runs when the behaviour is attached
      attach: function (context, settings) {
  
        // Attach a click event listener to all toggle buttons once
        once('init-attribution-bubble', this.attributionBubbleToggleSelector, context).forEach((attributionBubbleToggle) => {
          attributionBubbleToggle.addEventListener('click', (event) => {
            this.attributionBubbleToggle(event);
          })
        });
  
        // Attach a click event listener to the body once
        once('init-attribution-bubble-backdrop', 'body', context).forEach((body) => {
          body.addEventListener('click', (event) => {
            this.attributionBubbleBackdrop(event);
          })
  
          // Add an event listener to the document
          document.addEventListener('keydown', (event) => {
            this.attributionBubbleKeypress(event);
          })
        });
  
      },
  
      // Open or close the dialog, runs when clicking on the toggle button
      attributionBubbleToggle: function (event) {
        // Define the wrapping div element around button and dialog
        const attributionBubbleElement = event.target.parentNode;
        // Define the toggle button element within the wrapping div
        const attributionBubbleToggleElement = attributionBubbleElement.querySelector(this.attributionBubbleToggleSelector);
        // Define the dialog element within the wrapping div
        const attributionBubbleDialogElement = attributionBubbleElement.querySelector(this.attributionBubbleDialogSelector);
  
        // Abort if the event or any of the elements was not found
        if(!event || !attributionBubbleElement || !attributionBubbleToggleElement || !attributionBubbleDialogElement) {
          return;
        }
  
        // Check if dialog is open
        if(attributionBubbleDialogElement.open) {
          // Dialog is Open => close the dialog and remove the active attribute on the button
          delete attributionBubbleToggleElement.dataset.active;
          attributionBubbleDialogElement.close();
        } else {
          // Dialog is Closed => open the dialog and add the active attribute on the button
          attributionBubbleToggleElement.dataset.active = '';
          attributionBubbleDialogElement.show();
        }
  
      },
  
      attributionBubbleBackdrop: function (event) {
        // Abort if no event was found
        if(!event) {
          return;
        }
        // Check whether event target (click) is neither in a button or dialog element
        const isBackdrop = !(event.target.closest(this.attributionBubbleToggleSelector)) && !(event.target.closest(this.attributionBubbleDialogSelector));
        if(isBackdrop) {
          // Event target (click) is neither in a button or dialog element => cleanup dialogs
          this.attributionBubbleCleanup();
        }
      },

        // Abort if no event was found or the event does not contain a keypress
        // Check whether the event key pressed is escape key
        // if the escape key is pressed, call the cleanup function
      attributionBubbleKeypress: function (event) {
        if (!event || !event.key) {
            return;
        }
        const keyName = event.key;
        if(keyName === 'Escape') {
            this.attributionBubbleCleanup();
        }
      },
  
      // Close all open dialogs, runs when clicking anywhere but the buttons or the dialogs
      attributionBubbleCleanup: function () {
        // Close all open dialogs
        document.querySelectorAll(this.attributionBubbleDialogSelector + '[open]').forEach((attributionBubbleDialogElement) => {
          attributionBubbleDialogElement.close();
        });
  
        // Remove all active attributes on the buttons
        document.querySelectorAll(this.attributionBubbleToggleSelector + '[data-active]').forEach((attributionBubbleToggleElement) => {
          delete attributionBubbleToggleElement.dataset.active;
        });
      }
    };
  
  } (Drupal, once));
/**
 * @file
 * Replaced Drupal cores ajax throbber(s), see: https://www.drupal.org/node/2974681
 *
 */
(function ($, Drupal) {
  Drupal.theme.ajaxProgressThrobber = function () {
    return "<div class=\"ajax-spinner ajax-spinner--inline\"><span class=\"ajax-spinner__label\">" + Drupal.t('Loading&nbsp;&hellip;', {}, {
      context: "Loading text for Drupal cores Ajax throbber (inline)"
    }) + "</span></div>";
  };

  Drupal.theme.ajaxProgressIndicatorFullscreen = function () {
    return "<div class=\"ajax-spinner ajax-spinner--fullscreen\"><span class=\"ajax-spinner__label\">" + Drupal.t('Loading&nbsp;&hellip;', {}, {
      context: "Loading text for Drupal cores Ajax throbber (fullscreen)"
    }) + "</span></div>";
  };
  // You can also customize only throbber message:
  // Drupal.theme.ajaxProgressMessage = message => '<div class="my-message">' + message + '</div>';
})(jQuery, Drupal);

(function (Drupal, once) {


  /**
   * Opening / Closing logic for menu toggles (e. g. offcanvas burger menu).
   */
  Drupal.behaviors.pageHeaderOffcanvasMenu = {
    attach: function (context, settings) {

      // Adding an eventlistener on the BurgerMenu-Button on click
      once('init-offcanvas', '.header__burger--buttons', context).forEach((headerBurgerButton) => {
        headerBurgerButton.addEventListener('click', (event) => {
          this.toggleOffcanvasMenu();
        })
      });

      // Adding an eventlistener on the body on click
      document.body.addEventListener('click', (event) => {
        this.menuDialogBackdrop(event);
      })
      document.addEventListener('keydown', (event) => {
        this.menuDialogKeypress(event);
      })
    },
    // Check whether event target (click) is neither in a button or dialog element
    menuDialogBackdrop: function (event) {
      // Abort if no event was found
      if(!event) {
        return;
      }

      const isBackdrop = !(event.target.closest('.header__offcanvas-menu--grid')) && !(event.target.closest('.header__burger--buttons'));

      if(isBackdrop) {
        // Event target (click) is neither in a button or dialog element => cleanup dialogs
        this.menuDialogCleanup();
      }
    },
    // Close the menu and set atrributes (aria and dialog)
    menuDialogCleanup: function () {
      const burgerButtonOpen = document.querySelector('#button-offcanvas-open');
      const burgerButtonClose = document.querySelector('#button-offcanvas-close');
      const offcanvasMenu = document.querySelector('#offcanvas_menu');

      if (!burgerButtonOpen || !burgerButtonClose || !offcanvasMenu) {
        return;
      }

      burgerButtonOpen.setAttribute('aria-expanded', 'false');
      burgerButtonClose.setAttribute('aria-expanded', 'false');
      offcanvasMenu.setAttribute('aria-hidden', 'true')
      // Close all open dialogs
      document.body.classList.remove('offcanvas-open');

      // set timeout so the menu slides out
        setTimeout(() => {
          document.querySelector('#offcanvas_menu_dialog' + '[open]')?.close();
        }, 150);
    },
    // Toggle the BurgerMenu-Button and aria-labels
    toggleOffcanvasMenu: function() {
      const offcanvasMenuDialog = document.querySelector('#offcanvas_menu_dialog');
      const burgerButtonOpen = document.querySelector('#button-offcanvas-open');
      const offcanvasMenu = document.querySelector('#offcanvas_menu');
      const burgerButtonClose = document.querySelector('#button-offcanvas-close');
      const wasOpen = document.body.classList.contains('offcanvas-open');
      const offcanvasMenuDialogOpen = offcanvasMenuDialog.hasAttribute('open');

      if (!offcanvasMenuDialogOpen) {
        burgerButtonOpen.setAttribute('aria-expanded', 'true');
        burgerButtonClose.setAttribute('aria-expanded', 'true');
        offcanvasMenu.setAttribute('aria-hidden', 'false')
        offcanvasMenuDialog.setAttribute('open', '');
        setTimeout(() => {
          document.body.classList.add('offcanvas-open');
        }, 150);
      } else {
        this.menuDialogCleanup();
      }

      // Set focus in menu when its open
      if (!wasOpen) {
        const offcanvasMenuContent = document.querySelector('.header__offcanvas-menu--content')
        offcanvasMenuContent.firstElementChild.focus();
      }
    },
    // close menu on ESC
    menuDialogKeypress: function (event) {
      if (!event || !event.key) {
          return;
      }
      const keyName = event.key;
      if(keyName === 'Escape') {
          this.menuDialogCleanup();
      }
    }
  }
} (Drupal, once));

(function ($, Drupal, once) {

  // Add body class on scroll.
  Drupal.behaviors.scrollToTop = {
    // eslint-disable-next-line no-unused-vars
    attach(context, settings) {
      $(once('scroll-class', 'body', context))
        .each(function() {
          const headerOffset = $('.navbar-secondary', context).outerHeight();
          const $body = $(this);
          const $window = $(window);

          $window.scroll(function(event) {
            const scrollPos = $window.scrollTop();
            const pxToBottom = $body.height() - (scrollPos + $window.height());

            $body.toggleClass(
              'is-scrolling-past-navbar',
              scrollPos > headerOffset,
            );
            $body.toggleClass('is-scrolling', scrollPos > $window.height() / 4);
            $body.toggleClass('is-scrolled-bottom', pxToBottom < 30);
          });
        });
      $(context)
        .find('.scroll-to-top')
        .click(function() {
          $('html, body').animate(
            {
              scrollTop: 0,
            },
            500,
          );
        });
    },
  };
}(jQuery, Drupal, once));

(function ($, Drupal, once) {

  'use strict';

  /**
   * Opening / Closing logic for the main menu inside of offcanvas menu.
   */
  Drupal.behaviors.mainMenu = {
    attach: function (context, settings) {
      $(once('init-main-menu', '.menu-item--toggle', context)).click(this.toggleMenuItem);
      // Toggle initial states.
      $('.region-offcanvas-menu ul').each(function() {
        if ($(this).parent().hasClass('menu-item--active-trail')) {
          $(this).slideDown();
          $(this).parent().addClass('menu-item--open');
        } else {
          if (!$(this).parent().hasClass('block-menu')) {
            $(this).slideUp();
          }
        }
      });
    },
    toggleMenuItem: function() {
      let $menuItem = $(this).parent();
      let $allMenuItems = $('.region-offcanvas-menu li.menu-item');
      let wasOpen = $menuItem.hasClass('menu-item--open');

      if (wasOpen) {
        $menuItem.removeClass('menu-item--open').children('ul').slideUp();
      } else {
        $menuItem.siblings().removeClass('menu-item--open').children('ul').slideUp();
        $menuItem.addClass('menu-item--open').children('ul').slideDown();
      }
    }
  };
} (jQuery, Drupal, once));
