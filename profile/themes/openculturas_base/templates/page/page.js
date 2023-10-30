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
