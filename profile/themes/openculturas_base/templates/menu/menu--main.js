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
