(($, Drupal, once) => {
  /**
   * Opening / Closing logic for the main menu inside of offcanvas menu.
   */
  Drupal.behaviors.mainMenu = {
    attach(context) {
      $(once('init-main-menu', '.menu-item--toggle', context)).click(
        this.toggleMenuItem,
      );
      // Toggle initial states.
      // eslint-disable-next-line func-names -- jQuery binds `this` to the DOM element here
      $('.region-offcanvas-menu ul').each(function () {
        if ($(this).parent().hasClass('menu-item--active-trail')) {
          $(this).slideDown();
          $(this).parent().addClass('menu-item--open');
        } else if (!$(this).parent().hasClass('block-menu')) {
          $(this).slideUp();
        }
      });
    },
    toggleMenuItem() {
      const $menuItem = $(this).parent();
      const wasOpen = $menuItem.hasClass('menu-item--open');

      if (wasOpen) {
        $menuItem.removeClass('menu-item--open').children('ul').slideUp();
      } else {
        $menuItem
          .siblings()
          .removeClass('menu-item--open')
          .children('ul')
          .slideUp();
        $menuItem.addClass('menu-item--open').children('ul').slideDown();
      }
    },
  };
})(jQuery, Drupal, once);
