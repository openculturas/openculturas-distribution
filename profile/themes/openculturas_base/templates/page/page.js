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
      $('body').toggleClass('offcanvas-open');
    }
  };
} (jQuery, Drupal));
