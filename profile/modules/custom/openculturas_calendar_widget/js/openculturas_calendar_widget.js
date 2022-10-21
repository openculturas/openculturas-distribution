((Drupal, once) => {
  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openculturas_calendar_widget = {
    attach: (context) => {
      const $messages = new Drupal.Message(document.getElementById('openculturas-calendar-widget-status'));
      let $message_id;
      once('copy-button', '.openculturas-calendar-widget-copy-button', context).forEach(($button) => {
        let $text = document.getElementById($button.dataset.sourceId);
        $button.addEventListener('click', (e) => {
          $text.select();
          $text.setSelectionRange(0, 99999);
          navigator.clipboard.writeText($text.value);
          if (typeof $message_id !== 'undefined') {
            $messages.remove($message_id);
          }
          $message_id = $messages.add(Drupal.t('Copied text'), {}, 'copy-button');
          e.preventDefault();
        });
      });
    }
  }
})(Drupal, once);
