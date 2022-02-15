(function ($, Drupal) {

  'use strict';

  /**
   * Opening / Closing logic for the offcanvas burger menu.
   */
  Drupal.behaviors.pageHeaderOffcanvasMenu = {
    attach: function (context, settings) {
      $('.header__burger--link', context).once('init-offcanvas').click(this.toggleOffcanvasMenu);
    },
    toggleOffcanvasMenu: function(e= null) {
      let $body = $('body');
      let $offcanvasMenu = $('#offcanvas_menu');
      let $burgerButtons = $('.header__burger--link');
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

  /**
   * Opening / Closing logic for the offcanvas menu items.
   */
  Drupal.behaviors.pageHeaderMainMenu = {
    attach: function (context, settings) {
      $('.menu-item--toggle', context).once('init-main-menu').click(this.toggleMainMenuItem);
    },
    toggleMainMenuItem: function(e= null) {
      let wasOpen = $(this).parent.hasClass('toggle-active');
      // @TODO: Menu toggle logic
    }
  };
} (jQuery, Drupal));

/**
 * @file
 * OpenCulturas Base behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.nodeLocation = {
    attach: function (context, settings) {

    }
  };
} (jQuery, Drupal));

