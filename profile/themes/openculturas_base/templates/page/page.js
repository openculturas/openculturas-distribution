(function ($, Drupal) {

  'use strict';

  /**
   * Opening / Closing logic for the offcanvas burger menu.
   */
  Drupal.behaviors.pageHeaderOffcanvasMenu = {
    attach: function (context, settings) {
      $('.header__burger--buttons', context).once('init-offcanvas').click(this.toggleOffcanvasMenu);
    },
    toggleOffcanvasMenu: function(e= null) {
      let $body = $('body');
      let $offcanvasMenu = $('#offcanvas_menu');
      let $burgerButtons = $('.header__burger--buttons');
      let wasOpen = $body.hasClass('offcanvas-open');

      $body.toggleClass('offcanvas-open');

      // Accessibility tweaks.
      $burgerButtons.each(function() {
        $(this).attr('aria-expanded', !($(this).attr('aria-expanded') === 'true'));
      });
      $offcanvasMenu.attr('aria-hidden', !($offcanvasMenu.attr('aria-hidden') === 'true'));

      if (!wasOpen) {
        $('.header__offcanvas-menu--content').children().eq(0).focus();
      }
    }
  };
} (jQuery, Drupal));
