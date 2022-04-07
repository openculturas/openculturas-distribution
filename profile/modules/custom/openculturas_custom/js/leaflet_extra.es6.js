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
          });
          circle.setStyle({ fill: false });
          circle.addTo(lMap);
          lMap.flyToBounds(circle.getBounds());
        }
      });
    },
  };
})(jQuery, Drupal);
