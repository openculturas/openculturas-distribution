(function ($, Drupal) {

  'use strict';

  /**
   * Opening / Closing logic for the main menu inside of offcanvas menu.
   */
  Drupal.behaviors.mainMenu = {
    attach: function (context, settings) {
      $('.menu-item--toggle', context).once('init-main-menu').click(this.toggleMenuItem);
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
    toggleMenuItem: function(e= null) {
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
} (jQuery, Drupal));

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

