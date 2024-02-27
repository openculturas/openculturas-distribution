/* eslint-disable */
/**
 * OpenCulturas Map Module Block JavaScript
 */
(function (Drupal, once) {

  Drupal.OpenCulturasMapBlock = class {
    constructor(mapBlockElement, callback) {
      if(!mapBlockElement) {
        throw "mapBlockElement needed!"
      }
      if(!callback && typeof callback !== "function") {
        throw "callback function needed!"
      }
      this._mapBlockElement = mapBlockElement;
      this._callback = callback;
      this._init();
    }

    _init() {
      this._mapInstance = new Drupal.OpenCulturasMapLeaflet(this._mapBlockElement);
      this._mapInstance.mapElement.addEventListener("newcoords", this._callback.bind(this));
      this._callback();
      this._reloadInteraction = false;
    }

    _initFilter() {
      if(!this._filterElement) return;
      this._filterElement.addEventListener(`submit`, function(event) {
        event.preventDefault();
        this._filter = {};
        const inputElements = this._filterElement.querySelectorAll("input[type='text'], select");
        for(const inputElement of inputElements) {
          if(inputElement && inputElement.value) {
              this._filter[inputElement.name] = inputElement.value;
          }
        }

        this._callback();
      }.bind(this));
    }

    get filterElement() {
      return this._filterElement;
    }

    set filterElement(filterElement) {
      if(!filterElement) return;
      this._filterElement = filterElement;
      this._initFilter();
    }

    withFilter(filterElement) {
      this.filterElement = filterElement;
      return this;
    }

    get mapBlockElement() {
      return this._mapBlockElement;
    }

    set mapBlockElement(mapBlockElement) {
      throw "mapBlockElement can only be set on init";
    }

    get mapInstance() {
      return this._mapInstance;
    }

    set mapInstance(mapInstance) {
      throw "mapInstance cannot be set";
    }
  }

  Drupal.behaviors.OpenCulturasMapBlock = {
    mapSelector: '.block-openculturas-map .openculturas--map',
    formValues: [],
    // Drupal base function, runs when the behaviour is attached
    attach: function (context, settings) {
      once('init-openculturas-map-block', this.mapSelector, context).forEach((openculturasMapElement) => {
        const openCulturasMapBlock = new Drupal.OpenCulturasMapBlock(openculturasMapElement, function(event) {
          const mapType = openculturasMapElement.dataset.type;
      
          const mapInstance = this.mapInstance;
          const locationsClient = new Drupal.OpenCulturasMapLocationsClient();
          const client = locationsClient;

          let fetchNew = true;
          if(!event) {
            // Without Map Move
          } else {
            if(event.detail.distances.pixels < 80 && event.detail.oldCoords.zoom === event.detail.zoom) {
              fetchNew = false;
            }
          }

          if(
            mapInstance
            && mapInstance.radius[mapInstance.zoom.map]
            && mapInstance.coords.map.lat
            && mapInstance.coords.map.lng
            && mapInstance.coords.map.lng >= -180 && mapInstance.coords.map.lng <= 180
          ) {
            client
              .addToQuery("proximity[value]", String(mapInstance.radius[mapInstance.zoom.map]))
              .addToQuery("proximity[origin][lat]", String(mapInstance.coords.map.lat))
              .addToQuery("proximity[origin][lon]", String(mapInstance.coords.map.lng))
          }

          if(this._filter) {
            for(const [filterKey, filterValue] of Object.entries(this._filter)) {
              client.addToQuery(filterKey, filterValue);
            }

            if(JSON.stringify(this._filter) === "{}") {
              if(!event) {
                this.mapInstance.clear();
                this.mapInstance.toOrigin();
              }
            } else if(JSON.stringify(this._filter) !== JSON.stringify(this._renderedFilter)) {
              this.mapInstance.clear();
              this._renderedFilter = this._filter;
            }
          }
          
          if(fetchNew) {
            openculturasMapElement.dataset.loading = true;
            openculturasMapElement.setAttribute("aria-busy", "true");
            client.asyncMapEntries()
              .then(mapEntries => {
                openculturasMapElement.dataset.loading = false;
                openculturasMapElement.setAttribute("aria-busy", "false");
                mapInstance.withEntryCollection(mapEntries).render();
                if(mapInstance.settings.get('refresh_results_on_user_interaction')) {
                  mapInstance.waitForInteraction();
                }
              })
          }
          

        }).withFilter(openculturasMapElement.querySelector('form'));
      });
    },
  }

}(Drupal, once));
