(function($, Drupal, drupalSettings) {
  Drupal.behaviors.locate_me = {
    attach: function (context, settings) {
      var $buttons = $('.button--locate_me', context);
      if ($buttons.length) {
        var self = this;
        const messages = new Drupal.Message();
        $buttons.click(function (event) {
          event.preventDefault();
          if ('geolocation' in navigator) {
            messages.clear();
            messages.add(Drupal.t('Locating...'));
            Drupal.behaviors.locate_me.target_id = $(this).attr('data-target-selector-id');
            navigator.geolocation.getCurrentPosition(self.success, self.error, { timeout: 5000, enableHighAccuracy: true });
          }
          else {
            messages.clear();
            messages.add(Drupal.t('Geolocation is not supported by this browser.'), {type: 'error'})
          }
          return false;
        });
      }
    },
    success: function(position) {
      const messages = new Drupal.Message();
      messages.clear();
      const latitude  = position.coords.latitude;
      const longitude = position.coords.longitude;
      var base_url = drupalSettings.path.baseUrl;
      var providers = drupalSettings.geocode_origin_autocomplete.providers.toString();
      var geocode_path = base_url + 'geocoder/api/reverse_geocode';
      $.ajax({
        url: geocode_path + '?latlng=' +  encodeURIComponent(latitude + ',' + longitude) + '&geocoder=' + providers,
        type:"GET",
        contentType:"application/json; charset=utf-8",
        dataType: "json",
      }).fail(function (jqXHR, textStatus ){
        const messages = new Drupal.Message();
        messages.clear();
        messages.add( Drupal.t('A server error has occurred.'), {type: 'error'});
      })
        .done(function( data ) {
          var $value = data[0].formatted_address;
          var $selector = '[data-drupal-selector=' + Drupal.behaviors.locate_me.target_id + ']';
          var $element = $($selector);
          if ($element.length) {
            $element.val($value);
          }
          else {
            console.error('Target element selector ' + $selector + ' not found');
          }
      });
    },
    error: function(GeolocationPositionError) {
      const messages = new Drupal.Message();
      messages.clear();
      messages.add(Drupal.t('Locating not possible.'), {type: 'error'});
      console.warn(`ERROR(${GeolocationPositionError.code}): ${GeolocationPositionError.message}`);
    }
  }

})(jQuery, Drupal, drupalSettings);
