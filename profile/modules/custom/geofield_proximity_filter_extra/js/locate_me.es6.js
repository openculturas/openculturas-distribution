(($, Drupal, drupalSettings) => {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for locating the browser.
   * @prop {Drupal~behaviorAttach} success
   *   successCallback for navigator.geolocation.getCurrentPosition.
   * @prop {Drupal~behaviorAttach} error
   *   errorCallback for navigator.geolocation.getCurrentPosition.
   */
  Drupal.behaviors.locate_me = {
    attach: (context) => {
      const $buttons = $('.button--locate_me', context);
      if ($buttons.length) {
        const messages = new Drupal.Message();
        $buttons.click((event) => {
          const $button = $(event.currentTarget);
          event.preventDefault();
          if ('geolocation' in navigator) {
            messages.clear();
            messages.add(Drupal.t('Locating...'));
            Drupal.behaviors.locate_me.target_id = $button.attr(
              'data-target-selector-id',
            );
            navigator.geolocation.getCurrentPosition(
              Drupal.behaviors.locate_me.success,
              Drupal.behaviors.locate_me.error,
              {
                timeout: 5000,
                enableHighAccuracy: true,
              },
            );
          } else {
            messages.clear();
            messages.add(
              Drupal.t('Geolocation is not supported by this browser.'),
              { type: 'error' },
            );
          }
          return false;
        });
      }
    },
    success: (position) => {
      const messages = new Drupal.Message();
      messages.clear();
      const { latitude } = position.coords;
      const { longitude } = position.coords;
      const { baseUrl } = drupalSettings.path;
      const providers =
        drupalSettings.geocode_origin_autocomplete.providers.toString();
      const geocodePath = `${baseUrl}geocoder/api/reverse_geocode`;
      const latLon = encodeURIComponent(`${latitude},${longitude}`);
      $.ajax({
        url: `${geocodePath}?latlng=${latLon}&geocoder=${providers}`,
        type: 'GET',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
      })
        .fail(() => {
          // const messages = new Drupal.Message();
          messages.clear();
          messages.add(Drupal.t('A server error has occurred.'), {
            type: 'error',
          });
        })
        .done((data) => {
          const $value = data[0].formatted_address;
          const $selector = `[data-drupal-selector=${Drupal.behaviors.locate_me.target_id}]`;
          const $element = $($selector);
          if ($element.length) {
            $element.val($value);
          }
        });
    },
    error: () => {
      const messages = new Drupal.Message();
      messages.clear();
      messages.add(Drupal.t('Locating not possible.'), { type: 'error' });
    },
  };
})(jQuery, Drupal, drupalSettings);
