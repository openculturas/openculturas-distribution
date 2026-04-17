/* global L */
((Drupal) => {
  /**
   * @type {Drupal.behavior}
   */
  Drupal.behaviors.OpenCulturasOSMIDFinder = {
    attach: (context, settings) => {
      const $mapElementId = 'osmfinder-map';
      let $mapInstance;
      const $mapHTMLContainer = document.getElementById($mapElementId);
      function initMap() {
        $mapInstance = L.map($mapElementId, {
          scrollWheelZoom: false,
          zoomControl: false,
        });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 18,
          attribution:
            'Data \u00a9 <a href="http://www.openstreetmap.org/copyright"> OpenStreetMap Contributors </a> Tiles \u00a9 Komoot',
        }).addTo($mapInstance);
        new L.Control.Zoom({ position: 'topright' }).addTo($mapInstance);
      }
      if ($mapHTMLContainer) {
        $mapHTMLContainer._leaflet_id = null;
        initMap();
        const $resultListId = settings?.osmfinder?.resultListId;
        const $resultList = settings?.osmfinder?.result;
        if ($resultList) {
          const $markerBounds = L.latLngBounds([]);
          $resultList.forEach((locationItem) => {
            if (locationItem.resultListId !== $resultListId) {
              return;
            }
            const $marker = L.marker(locationItem.geo);
            $markerBounds.extend($marker.getLatLng());
            $marker.addTo($mapInstance).bindPopup(locationItem.label);
            if (typeof $markerBounds !== 'undefined') {
              if ($markerBounds.isValid()) {
                $mapInstance.fitBounds($markerBounds);
              }
            }
          });
          $mapInstance.invalidateSize(true);
        } else if ($mapInstance) {
          $mapInstance.invalidateSize(true);
        }
      }
    },
  };
})(Drupal, jQuery, once);
