(($, Drupal, once) => {
  // Add body class on scroll.
  Drupal.behaviors.scrollToTop = {
    attach(context) {
      // eslint-disable-next-line func-names -- jQuery binds `this` to the DOM element here
      $(once('scroll-class', 'body', context)).each(function () {
        const headerOffset = $('.navbar-secondary', context).outerHeight();
        const $body = $(this);
        const $window = $(window);

        $window.scroll(() => {
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
        .click(() => {
          $('html, body').animate(
            {
              scrollTop: 0,
            },
            500,
          );
        });
    },
  };
})(jQuery, Drupal, once);
