(function (Drupal, once) {

  /**
   * Opening / Closing logic for social media links.
   * TO DO: find a more generic solution for the open/close toggling
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.pageSocialShare = {
    offcanvasMenuSelector: 'oc--social-share--layer',
    burgerButtonsSelector: 'oc--social-share--button',

    attach: function (context, settings) {
      once('init-offcanvas', `#${this.burgerButtonsSelector}`, context).forEach((button) => {
        button.addEventListener('click', () => {
          this.handleSocialShareClick();
        });
        document.body.addEventListener('click', (event) => {
          this.handleBodyClick(event);
        });
      });
    },
    handleSocialShareClick: function () {
      const $willOpen = !document.body.classList.contains('socialshare-open');

      this.toggleOffcanvasSocial($willOpen);
    },
    handleBodyClick: function (event) {
      // Abort if no event was found
      if (!event) {
        return;
      }

      const isBackdrop = !(event.target.closest(`#${this.offcanvasMenuSelector}`))
        && !(event.target.closest(`#${this.burgerButtonsSelector}`));

      if (isBackdrop) {
        this.toggleOffcanvasSocial(false);
      }
    },
    toggleOffcanvasSocial: function (willOpen) {
      const $offcanvasMenu = document.getElementById(this.offcanvasMenuSelector);
      const $burgerButtons = document.getElementById(this.burgerButtonsSelector);

      document.body.classList.toggle('socialshare-open', willOpen);

      // Accessibility tweaks.
      $burgerButtons.setAttribute('aria-expanded', willOpen);
      $offcanvasMenu.setAttribute('aria-hidden', !willOpen);
      if (willOpen) {
        $offcanvasMenu.children.item(0).focus();
      }
    }
  };

} (Drupal, once));
