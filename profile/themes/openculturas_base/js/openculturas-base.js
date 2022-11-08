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

(function ($, Drupal) {

  'use strict';

  /**
   * Opening / Closing logic for menu toggles (e. g. offcanvas burger menu).
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

(function ($) {

  // Add body class on scroll.
  Drupal.behaviors.scrollToTop = {
    // eslint-disable-next-line no-unused-vars
    attach(context, settings) {
      $(context)
        .find('body')
        .once('scroll-class')
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

} (jQuery));
