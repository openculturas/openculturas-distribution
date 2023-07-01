((Drupal, drupalSettings) => {
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
    attach: () => {
      const messages = new Drupal.Message();
      if ('geolocation' in navigator) {
        const $selector = '[data-target-selector-id]';
        const $buttons = once('locate_me', $selector);
        if ($buttons) {
          $buttons.forEach((button) => {
            button.addEventListener('click', () => {
              if ('geolocation' in navigator) {
                messages.clear();
                messages.add(Drupal.t('Locating...'));
                Drupal.behaviors.locate_me.target_id = button.getAttribute(
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
          });
        }
      } else {
        messages.clear();
        messages.add(
          Drupal.t('Geolocation is not supported by this browser.'),
          { type: 'error' },
        );
      }
    },
    /**
     * @param {GeolocationPosition} position Position
     */
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
      fetch(`${geocodePath}?latlng=${latLon}&geocoder=${providers}`)
        .then((response) => {
          return response.json();
        })
        .then((data) => {
          const $value = data[0].formatted_address;
          const $selector = `[data-drupal-selector=${Drupal.behaviors.locate_me.target_id}]`;
          const $element = document.querySelector($selector);
          if ($element) {
            $element.value = $value;
          }
        })
        .catch(() => {
          messages.clear();
          messages.add(Drupal.t('A server error has occurred.'), {
            type: 'error',
          });
        });
    },
    error: () => {
      const messages = new Drupal.Message();
      messages.clear();
      messages.add(Drupal.t('Locating not possible.'), { type: 'error' });
    },
  };
})(Drupal, drupalSettings);
