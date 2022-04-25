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
