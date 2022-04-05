(($, Drupal) => {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.leaflet_extra = {
    attach: () => {
      $(document).on('leafletMapInit', (e, settings, lMap) => {
        if (
          settings.settings.center &&
          settings.settings.center.lat &&
          settings.settings.center.lon
        ) {
          const latlng = L.latLng(
            settings.settings.center.lat,
            settings.settings.center.lon,
          );
          const circle = L.circle(latlng, {
            radius: parseInt(settings.settings.radius, 10) * 1000,
          }).addTo(lMap);
          circle.fillOpacity(0.2);
          lMap.fitBounds(circle.getBounds());
          lMap.flyToBounds(circle.getBounds());
          // lMap.setView(latlng);
        }
      });
    },
  };
})(jQuery, Drupal);
