(($, Drupal) => {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.leaflet_extra = {
    attach: () => {
      const $elements = $(once('leafletDetails', 'details'));
      $elements.on('toggle', (event) => {
        const $detail = $(event.currentTarget);
        const $leaflet = $detail.find('.leaflet-container');
        if ($leaflet.length) {
          $leaflet.data('leaflet').lMap.invalidateSize();
        }
      });
      $(document).on('leafletMapInit', (e, settings, lMap) => {
        if (
          settings.settings.center &&
          settings.settings.center.lat &&
          settings.settings.center.lon &&
          settings.settings.radius
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
