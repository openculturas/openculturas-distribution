((Drupal, once) => {
  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openculturas_calendar_widget = {
    attach: (context) => {
      const $messagesWrapper = document.getElementById(
        'openculturas-calendar-widget-messages',
      );
      const $fallBack = document.querySelector(
        '[data-drupal-messages-fallback]',
      );
      if ($messagesWrapper === null && $fallBack === null) {
        return;
      }
      const $messages = new Drupal.Message($messagesWrapper);
      let $messageId;
      once(
        'copy-button',
        '.openculturas-calendar-widget-copy-button',
        context,
      ).forEach(($button) => {
        const $text = document.getElementById($button.dataset.sourceId);
        $button.addEventListener('click', (e) => {
          $text.select();
          $text.setSelectionRange(0, 99999);
          navigator.clipboard.writeText($text.value);
          if (typeof $messageId !== 'undefined') {
            $messages.remove($messageId);
          }
          $messageId = $messages.add(
            Drupal.t('Copied text'),
            {},
            'copy-button',
          );
          e.preventDefault();
        });
      });
    },
  };
})(Drupal, once);
