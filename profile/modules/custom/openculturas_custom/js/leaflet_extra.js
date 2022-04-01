(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.leaflet_extra = {
    attach: function (context, settings) {
      // React on geofieldMapInit event.
      $(document).on('leafletMapInit', function (e, settings, lMap, mapid) {
        if (settings.settings.center && settings.settings.center.lat && settings.settings.center.lon) {
          var latlng = L.latLng(settings.settings.center.lat, settings.settings.center.lon);
          //var markers = Drupal.Leaflet[mapid].markers;
          var circle = L.circle(latlng, {radius: settings.settings.radius * 1000}).addTo(lMap);
          circle.fillOpacity(0.2);
          lMap.fitBounds(circle.getBounds());
          lMap.flyToBounds(circle.getBounds());
          // lMap.setView(latlng);
        }

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
