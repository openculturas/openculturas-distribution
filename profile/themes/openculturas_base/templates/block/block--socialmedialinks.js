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
