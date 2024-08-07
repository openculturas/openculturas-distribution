/* eslint-disable */
/**
 * OpenCulturas Map Module JavaScript
 */
(function (Drupal, drupalSettings) {

  /**
   * OpenCulturasMapEntry
   * This Object is used to group the OpenCulturasMapMarker, OpenCulturasMapResult and OpenCulturasMapEntryData objects together.
   */
  Drupal.OpenCulturasMapEntry = class {
    constructor() {
      this._init();
    }

    _init() {
      this._id;
      /** @type {Drupal.OpenCulturasMapEntryData} */
      this._data;
      /** @type {Drupal.OpenCulturasMapMarker} */
      this._marker;
      /** @type {Drupal.OpenCulturasMapResult} */
      this._result;
    }

    _validate() {
      if(!this._id || typeof this._id !== "number") {
        throw "Invalid ID for Entry"
      }
      if(!this._data || !(this._data instanceof Drupal.OpenCulturasMapEntryData)) {
        throw "Invalid Data for Entry";
      }
      if(!this._marker || !(this._marker instanceof Drupal.OpenCulturasMapMarker)) {
        throw "Invalid Marker for Entry";
      }
      if(!this._result || !(this._result instanceof Drupal.OpenCulturasMapResult)) {
        throw "Invalid Result for Entry";
      }
    }

    get id() {
      return this._id;
    }

    set id(id) {
      if(!id || typeof id !== "number") {
        throw "Invalid id given, expected number"
      }
      this._id = id;
    }

    /** @type {Drupal.OpenCulturasMapEntryData} */
    get data(){
      this._validate();
      return this._data;
    }

    set data(data) {
      if(!data || !(data instanceof Drupal.OpenCulturasMapEntryData)) {
        throw "Invalid data given, expected Drupal.OpenCulturasMapEntryData";
      }
      this._data = data;
    }

    /** @type {Drupal.OpenCulturasMapMarker} */
    get marker(){
      this._validate();
      return this._marker;
    }

    set marker(marker) {
      if(!marker || !(marker instanceof Drupal.OpenCulturasMapMarker)) {
        throw "Invalid marker given, expected Drupal.OpenCulturasMapMarker";
      }
      this._marker = marker;
    }

    /** @type {Drupal.OpenCulturasMapResult} */
    get result(){
      this._validate();
      return this._result;
    }

    set result(result) {
      if(!result || !(result instanceof Drupal.OpenCulturasMapResult)) {
        throw "Invalid result given, expected Drupal.OpenCulturasMapResult";
      }
      this._result = result;
    }

    static factory(id, data, marker, result) {
      const entry = new this();
      entry.id = id;
      entry.data = data;
      entry.marker = marker;
      entry.result = result;
      return entry;
    }
  }

  /**
   * OpenCulturasMapEntryCollection
   * Collection object that extends a JS Map object.
   * It is used to group multiple OpenCulturasMapEntry.
   * Used by the OpenCulturasMapLeaflet object to render.
   */
  Drupal.OpenCulturasMapEntryCollection = class extends Map {
    toArray() {
      return this.array;
    }

    withSort(sortBy) {
      return new Drupal.OpenCulturasMapEntryCollection([...this.array].sort(function(a,b) {
        a = a[1];
        b = b[1];
        return a[sortBy] - b[sortBy];
      }));
    }

    findId(id) {
      const entry = this.array.find(function(entry) {
        return entry[1].id == id;
      })

      return entry || false;
    }

    findMarkerId(markerId) {
      const entry = this.array.find(function(entry) {
        return entry[1].data.marker_id == markerId;
      })

      return entry || false;
    }

    get array() {
      return Array.from(this);
    }

    set array(array) {
      throw "cannot set array!";
    }

    get hash() {
      return this._makeHash();
    }

    set hash(hash) {
      throw "cannot set hash!";
    }

    _makeHash() {
      let hash = 0;
      const keys = Array.from(this.keys());
      const proximities = Array.from(this.values()).reduce((acc, cur) => {
        return Math.round(acc + cur.data.proximity);
      }, 0) || 0;
      const str = keys.length + "|" + (keys.join('+') || 0) + '|';
      for (let i = 0, len = str.length; i < len; i++) {
          let chr = str.charCodeAt(i);
          hash = (hash << 5) - hash + chr;
          hash |= 0;
      }
      return hash;
    }
  }

  /**
   * OpenCulturasMapPopup
   * Creates the Popup used for OpenCulturasMapMarker.
   */
  Drupal.OpenCulturasMapPopup = class {
    constructor() {
      this._init();
    }

    _init() {
      this._innerHTML;
      this._showTitle = true;
    }

    get innerHTML() {
      return this._innerHTML;
    }

    set innerHTML(innerHTML) {
      this._innerHTML = innerHTML;
    }

    withoutTitle() {
      this._showTitle = false;
      return this;
    }

    get node() {
      const template = document.createElement('template');
      template.innerHTML = this.innerHTML;
      const childNodes = template.content.children;
      if(childNodes) {
        return childNodes[0];
      }
    }

    get html() {
      if(!this.node) {
        return false;
      }
      return this.node.outerHTML;
    }

    set node(node) {
      throw "node cannot be set";
    }

    set html(html) {
      throw "html cannot be set"
    }

    static factory(innerHTML) {
      const popup = new this();
      popup.innerHTML = innerHTML;
      return popup;
    }
  }

  /**
   * OpenCulturasMapEntryData
   * Stores data that can be accessed via a OpenCulturasMapEntry.
   */
  Drupal.OpenCulturasMapEntryData = class {

    constructor() {
      this._init();
    }

    _init() {
      this._proximity;
      this._marker_id;
      this._result_id;
      this._entry_id;
    }

    get proximity() {
      return this._proximity;
    }

    get entry_id() {
      return this._entry_id;
    }

    set entry_id(entryId) {
      if(entryId === undefined || typeof entryId !== "number") {
        throw "Invalid entryId given, expected number"
      }
      this._entry_id = entryId;
    }

    get marker_id() {
      return this._marker_id;
    }

    set marker_id(markerId) {
      if(markerId === undefined || typeof markerId !== "number") {
        throw "Invalid markerId given, expected number"
      }
      this._marker_id = markerId;
    }

    get result_id() {
      return this._result_id;
    }

    set result_id(resultId) {
      if(resultId === undefined || typeof resultId !== "number") {
        throw "Invalid resultId given, expected number"
      }
      this._result_id = resultId;
    }

    set proximity(proximity) {
      if(proximity === undefined || typeof proximity !== "number") {
        throw "Invalid proximity given, expected number"
      }
      this._proximity = proximity;
    }

    static factory(
      proximity,
      entry_id,
      marker_id,
      result_id
    ) {
        const data = new this();
        data.proximity = proximity;
        data.entry_id = entry_id;
        data.marker_id = marker_id;
        data.result_id = result_id;
        return data;
    }

  }

  /**
   * OpenCulturasMapMarker
   * Creates the Marker that can be placed on the map.
   */
  Drupal.OpenCulturasMapMarker = class {

    constructor() {
      this._init();
    }

    _init() {
      this._id;
      this._title;
      this._lat;
      this._lng;
      this._data;
      this._popup_html;
      this._upcoming_dates;

      this._settings = new Drupal.OpenCulturasMapSettings();
      this._icon = L.icon({
        iconUrl: this._settings.get('marker_icon_path'),
        iconSize: [this._settings.get('marker_icon_width'), this._settings.get('marker_icon_height')], // size of the icon
        iconAnchor: [parseInt(this._settings.get('marker_anchor_width')), parseInt(this._settings.get('marker_anchor_height'))], // point of the icon which will correspond to marker's location
        popupAnchor: [parseInt(this._settings.get('marker_anchor_popup_width')), parseInt(this._settings.get('marker_anchor_popup_height'))]  // point from which the popup should open relative to the iconAnchor[1, -15]
      })
    }

    get id() {
      return this._id;
    }

    set id(id) {
      if(!id || typeof id !== "number") {
        throw "id needs to be numeric"
      }

      this._id = id;
    }

    get title() {
      return this._title;
    }

    set title(title) {
      if(!title || typeof title !== "string") {
        throw "title needs to be a string"
      }

      this._title = title;
    }

    get lat() {
      return this._lat;
    }

    set lat(lat) {
      if(!lat || typeof lat !== "number" || Number.isInteger(lat) || !Number.isFinite(lat)) {
        throw "lat needs to be a floating number"
      }
      this._lat = lat;
    }

    get lng() {
      return this._lng;
    }

    set lng(lng) {
      if(!lng || typeof lng !== "number" || Number.isInteger(lng) || !Number.isFinite(lng)) {
        throw "lng needs to be a floating number"
      }
      this._lng = lng;
    }

    get popup_html() {
      return this._popup_html;
    }

    set popup_html(popup_html) {
      this._popup_html = popup_html;
    }

    get upcoming_dates() {
      return this._upcoming_dates;
    }

    set upcoming_dates(upcoming_dates) {
      this._upcoming_dates = upcoming_dates;
    }

    get popup() {
      const popup =  Drupal.OpenCulturasMapPopup.factory(this._popup_html).html;
      return popup;
    }

    set popup(popup) {
      throw "cannot set popup";
    }

    /** @type {Drupal.OpenCulturasMapEntryData} */
    get data(){
      return this._data;
    }

    set data(data) {
      if(!data || !(data instanceof Drupal.OpenCulturasMapEntryData)) {
        throw "Invalid data given, expected Drupal.OpenCulturasMapEntryData";
      }
      this._data = data;
    }

    toLMarker(mapIcon) {
      if(!mapIcon || (!mapIcon instanceof L.icon)) {
        mapIcon = this._icon;
      }

      return L.marker(
        L.latLng(this.lat, this.lng),
        {
          icon: mapIcon,
          marker: this
        }
      );
    }

    static factoryWithGeoJSON(id, title, geoJSON, data, popup_html, upcoming_dates = false) {
      let marker;
      L.geoJSON(geoJSON, {
        pointToLayer: function (feature, latlng) {
          marker = this.factory(
            parseInt(id),
            title,
            latlng.lat,
            latlng.lng,
            data,
            popup_html,
            upcoming_dates
        )
        }.bind(this)
      });
      if(marker) {
        return marker;
      }
      return false;
    }

    static factory(
      id,
      title,
      lat,
      lng,
      data,
      popup_html,
      upcoming_dates = false
    ) {
        const marker = new this();
        marker.id = id;
        marker.title = title;
        marker.lat = lat;
        marker.lng = lng;
        marker.data = data;
        marker.popup_html = popup_html;
        marker.upcoming_dates = upcoming_dates;
        return marker;
    }

  }

  /**
   * OpenCulturasMapResult
   * Creates the Result that can be placed in the result list.
   */
  Drupal.OpenCulturasMapResult = class {
    constructor() {
      this._init();
    }

    _init() {
      this._id;
      this._html;
      this._node;
      this._data;
    }

    get id() {
      return this._id;
    }

    set id(id) {
      if(!id || typeof id !== "number") {
        throw "id needs to be numeric"
      }

      this._id = id;
    }

    _htmlToNode(html) {
      const template = document.createElement('template');
      template.innerHTML = html;
      const result = template.content.children;
      return (result.length === 1) ? result[0] : result[0];
    }

    _mutateHtml(html) {
      let node;

      node = this._htmlToNode(html);
      //node = this._addProximityToNode(node);
      //node = this._addMapLinkToNode(node);

      this._html = html;
      this._node = node;
    }

    get node() {
      return this._node;
    }

    set node(node) {
      throw "node cannot be set!";
    }

    get html() {
      return this._html;
    }

    set html(html) {
      if(!html || typeof html !== "string") {
        throw "html needs to be a string";
      }
      this._mutateHtml(html);
    }

    get data() {
      return this._data;
    }

    set data(data) {
      this._data = data;
    }

    static factory(id, html, data) {
      const result = new this();
        result.id = id;
        result.data = data;
        result.html = html;

      return result;
    }

  }

  /**
   * OpenCulturasMapMarkerCluster
   * Abstraction layer to cluster multiple OpenCulturasMapMarker together.
   */
  Drupal.OpenCulturasMapMarkerCluster = class {
    constructor() {
      this._init();
    }

    _init() {
      this._settings = new Drupal.OpenCulturasMapSettings();
      this._icon = {
        iconSize: [this._settings.get('marker_icon_width'), this._settings.get('marker_icon_height')], // size of the icon
        iconAnchor: [this._settings.get('marker_anchor_width'), this._settings.get('marker_anchor_height')], // point of the icon which will correspond to marker's location
        popupAnchor: [this._settings.get('marker_anchor_popup_width'), this._settings.get('marker_anchor_popup_height')]  // point from which the popup should open relative to the iconAnchor[1, -15]
      };

      this._group = L.markerClusterGroup({
        spiderfyOnMaxZoom: false,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderLegPolylineOptions: { weight: 0, color: '#fff', opacity: 0 },
        maxClusterRadius: 30,
        iconCreateFunction: function(cluster) {
          return new L.DivIcon({...this._icon, html: `<div class="custom-cluster-icon"><img src="${this._settings.get('marker_icon_path')}"/><span>${cluster.getChildCount()}</span></div>`})
        }.bind(this)
      });
    }

    get group() {
      return this._group;
    }
  }


  /**
   * OpenCulturasMapHTTPClient
   * Client that requests and parses data from Drupal REST Exports.
   */
  Drupal.OpenCulturasMapHTTPClient = class {
    constructor(endpoint = false) {
      this._init();

      if(endpoint && typeof endpoint === "string") {
        this._endpoint = endpoint;
      }
    }

    _init() {
      this._uri = "/";
      this._endpoint = "";
      this._format = "json";
      this._queryParams = {};
    }

    get uri() {
      return this._uri;
    }

    set uri(uri) {
      if(typeof uri !== "string") {
        throw "uri needs to be a string";
      }
      this._uri = uri;
    }

    get endpoint() {
      return this._endpoint;
    }

    set endpoint(endpoint) {
      if(typeof endpoint !== "string") {
        throw "endpoint needs to be a string";
      }
      this._endpoint = endpoint;
    }

    get format() {
      return this._format;
    }

    set format(format) {
      if(typeof format !== "string") {
        throw "format needs to be a string";
      }

      if(!['json', 'xml', 'csv'].includes(format)) {
        throw "format needs to be json|xml|csv";
      }

      this._format = format;
    }

    addToQuery(param, value) {
      if(typeof param !== "string") {
        throw "param needs to be a string";
      }
      if(typeof value !== "string") {
        throw "value needs to be a string";
      }

      this._queryParams[param] = value;
      return this;
    }

    removeFromQuery(param) {
      if(typeof param !== "string") {
        throw "param needs to be a string";
      }
      if(this._queryParams[param]) {
        delete this._queryParams[param];
      }
    }

    resetQuery() {
      this._queryParams = {};
      return this;
    }

    get queryParams() {
      const queryParams = this._queryParams;
      if(!queryParams['_format']) {
        queryParams['_format'] = this.format;
      }

      return Object.keys(queryParams).map((key) => [key, queryParams[key]].join("=")).join("&");
    }

    _validate() {
      if(!this._uri || typeof this._uri !== "string") {
        throw "cannot request, invalid uri";
      }

      if(!this._endpoint || typeof this._endpoint !== "string") {
        throw "cannot request, invalid endpoint";
      }

      if(!this.queryParams || typeof this.queryParams !== "string") {
        throw "cannot request, invalid queryParams";
      }

      return true;
    }

    _buildFullUri() {
      if(drupalSettings.path.currentLanguage && !this._endpoint.includes(drupalSettings.path.currentLanguage)) {
        this._endpoint = `${drupalSettings.path.currentLanguage}/${this._endpoint}`
      }

      this._fullUri = `${this._uri}${this._endpoint}?${this.queryParams}`;
      return this._fullUri;
    }

    async asyncResponse() {
      this._validate();
      this._buildFullUri();
      if(this._fullUri !== this._lastFullUri) {
        this._lastFullUri = this._fullUri;
        this._lastResponse = fetch(this._fullUri);
      }

      return this._lastResponse;
    }

    _request() {
      this._validate();
      let response;

      this._buildFullUri();
      const request = new XMLHttpRequest();

      if(this._fullUri !== this._lastFullUri) {
        request.open("GET", this._fullUri, false);
        request.send(null);
        if (request.status !== 200) {
          throw "request failed: invalid response code, expected 200";
        }
        if(!request.response) {
          throw "request failed: invalid response";
        }

        this._lastFullUri = this._fullUri;
        this._lastResponse = request.response;
      }

      response = this._lastResponse;

      if(this.format === "json") {
        response = JSON.parse(response);
      }

      return response;
    }

    get response() {
      return this._request();
    }

    static parseGeoJson(geoJsonString) {
      if(geoJsonString && typeof geoJsonString === 'string') {
        return JSON.parse(geoJsonString);
      }

      return false;
    }
  }

  Drupal.OpenCulturasMapUpcomingDates = class {
    constructor() {
      this._dates = new Set();
      this._settings = new Drupal.OpenCulturasMapSettings();
    }

    addDate(date) {
      if (typeof date === "string" && date.match(/datetime="([^"]*)"/)?.[1] ) {
        this._dates.add(date);
      }
    }

    sortDates() {

      if(this._dates.size < 1) {
        return;
      }

      this._dates = new Set(Array.from(this._dates).sort((a, b) => {
        const aDate = new Date(a.match(/datetime="([^"]*)"/)?.[1]);
        const bDate = new Date(b.match(/datetime="([^"]*)"/)?.[1]);

        if(!aDate || !bDate) {
          return 1;
        }

        return aDate - bDate;
      }));
    }

    parseHtml(html) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const datesNode = doc.querySelector('.upcoming--dates');
      if (datesNode) {
        const dates = datesNode.innerHTML.split('<br>');
        dates.forEach(date => this.addDate(date));
      }
    }

    injectIntoHtml(html) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const datesNode = doc.querySelector('.upcoming--dates');
      if (datesNode) {
        this.sortDates();
        datesNode.innerHTML = Array.from(this._dates).slice(0, this._settings.get('delta_limit') ?? 3).join('<br>');
      }
      return doc.documentElement.outerHTML;
    }
  }

  /**
   * OpenCulturasMapLocationsClient
   * Extends OpenCulturasMapHTTPClient to parse data from a location view endpoint.
   */
  Drupal.OpenCulturasMapLocationsClient = class extends Drupal.OpenCulturasMapHTTPClient  {
    constructor() {
      super('rest/oc_map_locations');
      this._collection = new Drupal.OpenCulturasMapEntryCollection();
    }

    async asyncMapEntries() {
      return super.asyncResponse()
            .then(response => {
              if(response.status !== 200) {
                throw new Error("Invalid Response");
              }
              if(this.format === "json") {
                return response.json();
              }
              throw new Error("Invalid Format");
            })
            .then(clientResponse => {
              return this.mapEntries(clientResponse);
            })
            .catch(error => {
              console.error(error)
            });
    }

    mapEntries(clientResponse) {
      for(const location of clientResponse) {
        let entry;
        try {
          const data = Drupal.OpenCulturasMapEntryData.factory(
            parseFloat(location.field_address_proximity),
            parseInt(location.field_id),
            parseInt((location.field_marker_id) ? location.field_marker_id : location.field_id),
            parseInt((location.field_result_id) ? location.field_result_id : location.field_id)
          );

          const marker = Drupal.OpenCulturasMapMarker.factoryWithGeoJSON(
            parseInt((location.field_marker_id) ? location.field_marker_id : location.field_id),
            (location.field_popup_title) ? location.field_popup_title : Drupal.t('Entry'),
            Drupal.OpenCulturasMapHTTPClient.parseGeoJson(location.field_gps),
            data,
            location.field_popup_html
          );

          entry = Drupal.OpenCulturasMapEntry.factory(
            parseInt(location.field_id),
            data,
            marker,
            Drupal.OpenCulturasMapResult.factory(
              parseInt((location.field_result_id) ? location.field_result_id : location.field_id),
              location.field_result_html,
              data
            )
          );

          this._collection.set(location.field_id, entry);
        } catch(error) {
          console.debug("Skipping Location[id: " + location.field_id + "]: " + error);
          console.trace(error);
          continue;
        }

      }
      return this._collection;
    }
  }

  /**
   * OpenCulturasMapDatesClient
   * Extends OpenCulturasMapHTTPClient to parse data from a dates view endpoint.
   */
  Drupal.OpenCulturasMapDatesClient = class extends Drupal.OpenCulturasMapHTTPClient  {
    constructor() {
      super('rest/oc_map_dates');
      this._collection = new Drupal.OpenCulturasMapEntryCollection();
      this._upcomingDates = new Map();
    }

    async asyncMapEntries() {
      return super.asyncResponse()
        .then(response => {
          if(response.status !== 200) {
            throw new Error("Invalid Response");
          }
          if(this.format === "json") {
            return response.json();
          }
          throw new Error("Invalid Format");
        })
        .then(clientResponse => {
          return this.mapEntries(clientResponse);
        })
        .catch(error => {
          console.error(error)
        });
    }

    mapEntries(clientResponse) {
      for(const location of clientResponse) {
        try {
          const data = this.createEntryData(location);
          let marker = this.findExistingMarker(location) || this.createMarker(location, data);
          if(!marker) {
            continue;
          }

          marker.popup_html = this.updatePopupHtml(marker, location);
          const entry = this.createEntry(location, data, marker);

          this._collection.set(location.field_id, entry);
        } catch(error) {
          console.debug(`Skipping Date[id: ${location.field_id}]: ${error}`);
          console.trace(error);
        }
      }
      return this._collection;
    }

    createEntryData(location) {
      return Drupal.OpenCulturasMapEntryData.factory(
        parseFloat(location.field_address_proximity),
        parseInt(location.field_id),
        parseInt(location.field_marker_id || location.field_id),
        parseInt(location.field_result_id || location.field_id),
      );
    }

    findExistingMarker(location) {
      const existingMarkerEntry = this._collection.findMarkerId(location.field_marker_id)[1];
      if(existingMarkerEntry) {
        return existingMarkerEntry.marker;
      }
      return null;
    }

    createMarker(location, data) {
      return Drupal.OpenCulturasMapMarker.factoryWithGeoJSON(
        parseInt(location.field_marker_id || location.field_id),
        location.field_popup_title || Drupal.t('Entry'),
        Drupal.OpenCulturasMapHTTPClient.parseGeoJson(location.field_gps),
        data,
        location.field_popup_html
      );
    }

    updatePopupHtml(marker, location) {
      if(!this._upcomingDates
        .has(marker._id)
      ) {
        this._upcomingDates.set(
          marker._id,
          new Drupal.OpenCulturasMapUpcomingDates()
        )
      }

      this._upcomingDates.get(marker._id).parseHtml(location.field_popup_html);
      return this._upcomingDates.get(marker._id).injectIntoHtml(marker.popup_html);
    }

    createEntry(location, data, marker) {
      return Drupal.OpenCulturasMapEntry.factory(
        parseInt(location.field_id),
        data,
        marker,
        Drupal.OpenCulturasMapResult.factory(
          parseInt(location.field_result_id || location.field_id),
          location.field_result_html,
          data
        )
      );
    }
  }
  /**
   * OpenCulturasMapPager
   * Provides a pager to accurately paginate a OpenCulturasMapEntryCollection.
   */
  Drupal.OpenCulturasMapPager = class {
    constructor() {
      this.selectNode = false;
      this._perPage = 1;
      this.pages = 1;
      this.page = 1;
    }

    withSelect(node) {
      if(!node.nodeName || node.nodeName !== "SELECT") {
        return;
      }
      this.selectNode = node;
      return this;
    }

    withSettings(settings) {
      if(!settings.items_per_page) {
        return this;
      }
      this.settings = settings;

      if(this.settings.items_per_page) {
        this.perPage = this.settings.items_per_page;
      }

      return this;
    }

    get perPage() {
      if(this.selectNode && this.selectNode.nodeName === "SELECT") {
        const newValue = parseInt(this.selectNode.value);
        if(newValue > 0) {
          this.perPage = newValue;
          return newValue;
        }
      }

      return this._perPage;
    }

    set perPage(perPage) {
      this._perPage = perPage;
    }

    _buildPagerWrapper() {
      const pagerWrapper = document.createElement('div');
      pagerWrapper.classList.add('results-pager');
      return pagerWrapper;
    }

    _buildPagerNode() {
      const pagerNode = document.createElement('div');
      pagerNode.setAttribute('role', 'navigation');
      pagerNode.setAttribute('aria-label', Drupal.t('Pagination'));
      pagerNode.classList.add("pager");

      return pagerNode;
    }

    _buildPagerItemsNode() {
      const pagerItemsNode = document.createElement('ul');
      pagerItemsNode.classList.add('pager__items');

      return pagerItemsNode;
    }

    _buildPagerItemNode(pageNo, text) {
      const pagerItemNode = document.createElement('li');
      pagerItemNode.classList.add('pager__item');

      if(this.page === pageNo) {
        pagerItemNode.classList.add('is-active');
      }

      const pagerItemPageNode = document.createElement('a');
      pagerItemPageNode.setAttribute('data-page', pageNo);
      pagerItemPageNode.setAttribute('aria-label', Drupal.t('Go to page @number', {'@number': pageNo}));
      pagerItemPageNode.setAttribute('role', 'button')
      pagerItemPageNode.setAttribute('href', '#')

      const pagerItemPageTextNode = document.createElement('span');
      pagerItemPageTextNode.innerText = text;

      pagerItemPageNode.appendChild(pagerItemPageTextNode);
      pagerItemNode.appendChild(pagerItemPageNode);

      return pagerItemNode;
    }

    get() {
      if(this.page > this.pages) {
        this.page = this.pages;
      }

      const pagerItems = this._buildPagerItemsNode();

      if(this.page > 1) {
        pagerItems.appendChild(this._buildPagerItemNode(1, (this.settings && this.settings.tags.first) ? `${Drupal.t(this.settings.tags.first)}` : `« ${Drupal.t("First")}`));
        pagerItems.appendChild(this._buildPagerItemNode(this.page - 1, (this.settings && this.settings.tags.previous) ? `${Drupal.t(this.settings.tags.previous)}` : "‹‹"));
      }

      for (let i = 1; i < this.pages+1; i++) {
        pagerItems.appendChild(this._buildPagerItemNode(i, i));
      }

      if(this.page !== this.pages) {
        pagerItems.appendChild(this._buildPagerItemNode(this.page + 1, (this.settings && this.settings.tags.next) ? `${Drupal.t(this.settings.tags.next)}` : "››"));
        pagerItems.appendChild(this._buildPagerItemNode(this.pages, (this.settings && this.settings.tags.last) ? `${Drupal.t(this.settings.tags.last)}` : `${Drupal.t("Last")} »`));
      }

      const pager = this._buildPagerNode();
      pager.appendChild(pagerItems);

      return pager;
    }
  }

  /**
   * OpenCulturasMapSettings
   * Abstraction layer to collect options (global and per block) from drupalSettings.
   */
  Drupal.OpenCulturasMapSettings = class {
    constructor(blockIdentifier = null) {
      this._init(blockIdentifier);
    }

    _init(blockIdentifier = null) {

      this.scopes = ["global"];

      if(blockIdentifier) {
        this.scopes.push("block");
      }

      for(const scope of this.scopes) {
        if(drupalSettings.openCulturasMap[scope]) {
          this[scope] = drupalSettings.openCulturasMap[scope];
        } else {
          console.warn("No OpenCulturasMap Settings for scope: " + scope);
        }
      }

      if(blockIdentifier && this.block[blockIdentifier]) {
        this.block = this.block[blockIdentifier];
      }
    }

    get(key) {
      let value;
      for(const scope of this.scopes) {
        const scopedValue = key.split(".").reduce((r, k) => r?.[k], this[scope]);
        if(scopedValue) {
          value = scopedValue;
        }
      }
      return value ?? false;
    }
  }

  /**
   * OpenCulturasMapLeaflet
   * Leaflet connection layer.
   */
  Drupal.OpenCulturasMapLeaflet = class {
    constructor(wrapperElement) {

      this.reloadInteraction = false;
      this.wrapperElement = wrapperElement;
      this.blockElement = wrapperElement.closest('.block');
      this.mapElement = wrapperElement.querySelector('.openculturas-map--leaflet');
      this.resultsElement = wrapperElement.querySelector('.openculturas-map--results-list');
      this.reloadElement = wrapperElement.querySelector('.openculturas-map--reload');
      this.pagerElement = wrapperElement.querySelector('.openculturas-map--results-pager');
      this.counterElement = wrapperElement.querySelector('.openculturas-map--results-counter');
      this.perPageElement = wrapperElement.querySelector('.openculturas-map--results-picker select');

      this.settings = new Drupal.OpenCulturasMapSettings(this.wrapperElement.dataset.identifier ?? null);
      this.minZoom = Math.min(this.settings.get('start_zoom_position') ?? 4, 4);
      this.maxZoom = 19;

      this.radiusBase = parseFloat(this.settings.get('radius_base')) ?? 0.0430;
      this.radius = [
        this.radiusBase*524288,
        this.radiusBase*262144,
        this.radiusBase*131072,
        this.radiusBase*65536,
        this.radiusBase*32768,
        this.radiusBase*16384,
        this.radiusBase*8192,
        this.radiusBase*4096,
        this.radiusBase*2048,
        this.radiusBase*1024,
        this.radiusBase*512,
        this.radiusBase*256,
        this.radiusBase*128,
        this.radiusBase*64,
        this.radiusBase*32,
        this.radiusBase*16,
        this.radiusBase*8,
        this.radiusBase*4,
        this.radiusBase*2,
        this.radiusBase*1
      ];

      this.zoom = {
        map: this.settings.get('start_zoom_position'),
        origin: this.settings.get('start_zoom_position')
      }

      this.coords = {
        map: {
          lat: this.settings.get('start_lat_position'),
          lng: this.settings.get('start_lng_position'),
        },
        origin: {
          lat: this.settings.get('start_lat_position'),
          lng: this.settings.get('start_lng_position'),
        },
      }

      this._init();
    }

    set entryCollection(entryCollection) {
      if(!(entryCollection instanceof Drupal.OpenCulturasMapEntryCollection)) {
        throw "invalid entryCollection given, expected Drupal.OpenCulturasMapEntryCollection";
      }
      this._entryCollection = entryCollection;
    }

    withEntryCollection(entryCollection) {
      this.entryCollection = entryCollection;
      return this;
    }

    _clearResults() {
      if(this.resultsElement) {
        this.resultsElement.replaceChildren();
      }
    }

    _renderPager() {
      this.pagerElement.replaceChildren();
      if(this.pager.pages > 1) {
        this.pagerElement.appendChild(this.pager.get());
      }
    }

    _renderPagedResults(collection = null) {
      if(collection === null) {
        collection = this.pager.collection;
      } else {
        this.pager.collection = collection;
        this.pager.page = 1;
      }

      if(this.settings.get('pager.expose')) {
        this.pager.pages = Math.ceil(collection.size / this.pager.perPage);
        this.limit = (this.pager && this.pager.pages > 1) ? this.pager.perPage : 0;
        this.offset = (this.pager && this.pager.pages > 1) ? this.pager.perPage*(this.pager.page-1) : (this.settings.get('pager').offset ?? 0);
        if (this.perPageElement) {
          this.perPageElement.parentNode.style.display = 'block';
        }
        this._renderPager();
      } else {
        this.limit = 0;
        this.offset = 0;
        if (this.perPageElement) {
          this.perPageElement.parentNode.style.display = 'none';
        }
      }

      this._renderResults(collection);
      this._renderResultCounter(this.offset+1, (this.limit+this.offset === 0) ? collection.size : Math.min(this.limit+this.offset, collection.size), collection.size);
    }

    _renderResults(collection = null) {

      if(this.resultsElement) {
        this.resultsElement.replaceChildren();

        let index = 0;
        for(const [id, entry] of collection) {

          if(this.offset > 0 && index < this.offset) {
            index++;
            continue;
          }

          if(this.limit+this.offset > 0 && index >= this.limit+this.offset) {
            index++;
            continue;
          }

          const result = entry.result;
          Drupal.attachBehaviors(result.node);
          this.resultsElement.appendChild(result.node);
          index++;
        }
      }
    }

    _renderResultCounter(start, end, size) {
      const resultsCount = this._entryCollection.size;
      const counterElement = document.querySelector('.openculturas-map--results-counter');

      if(size < 1) {
        counterElement.innerText = Drupal.t("No results.");
      } else {
        counterElement.innerHTML = Drupal.t('Displaying <strong>@start – @end</strong> of <strong>@total</strong>', {'@start': start, '@end': end, '@total': size});
      }

    }

    _clearMarker() {
      this.markerCluster.group.clearLayers();
    }

    clear() {
      this.stopWaiting();
      this._clearMarker();
      this._clearResults();
      //this.mapInstance.setView(L.latLng(this.coords.origin.lat, this.coords.origin.lng), this.zoom.origin);
    }

    setView(lat, lng, zoom = false) {
      this.mapInstance.setView(
        L.latLng(
          lat,
          lng
        ),
        (zoom) ? zoom : this.zoom.origin
      )
    }

    _showRadius(namespace = 'map') {
      if(this._radialShape) {
        this.mapInstance.removeLayer(this._radialShape);
      }

      this._radialShape = L.circle(
        L.latLng(
          this.coords[namespace].lat,
          this.coords[namespace].lng,
        )
        , {radius: this.radius[this.zoom[namespace]]*1000, fillOpacity: 0.05});
        this.mapInstance.addLayer(this._radialShape);
    }

    toOrigin() {
      this.mapInstance.setView(
        L.latLng(
          this.coords.origin.lat,
          this.coords.origin.lng
        ),
        this.zoom.origin
      )
    }

    _getRenderedMarkers() {
      let renderedMarkers = {};
      this.markerCluster.group.eachLayer(function (layer) {
        if(
          layer && layer.options && layer.options.marker && layer.options.marker.data
          &&
          layer.options.marker.data.entry_id && layer.options.marker.data.marker_id
          &&
          this._entryCollection.findId(layer.options.marker.data.entry_id)
          )
          {
          renderedMarkers[layer.options.marker.data.marker_id] = layer;
        }
      }.bind(this));

      return renderedMarkers;
    }

    _renderMarker() {
      const renderedMarkers = this._getRenderedMarkers();
      const renderedMarkerIds = Object.keys(renderedMarkers);
      let addedMarkerIds = [];

      for(const [id, entry] of this._entryCollection) {
        if(
          (renderedMarkerIds && renderedMarkerIds.includes(String(entry.data.marker_id)))
          ||
          addedMarkerIds.includes(String(entry.data.marker_id))
        ) {
          continue;
        }

        addedMarkerIds.push(String(entry.data.marker_id));
        const leafletMarker = entry.marker.toLMarker();
        leafletMarker.bindPopup(entry.marker.popup, {
          maxWidth : (/Mobi/i.test(window.navigator.userAgent) ?? false) ? 300 : 400
        });

        leafletMarker.addTo(this.markerCluster.group)
        this.mapInstance.addLayer(this.markerCluster.group);
      }
    }

    waitForInteraction(bool = true) {
      this.reloadInteraction = bool;
      this.wrapperElement.dataset.reloadInteraction = bool;
    }

    stopWaiting() {
      this.reloadInteraction = false;
      this.wrapperElement.dataset.dirty = "";
      this.wrapperElement.dataset.reloadInteraction = "";
    }

    render(render = false) {
      if(!this._entryCollection || !(this._entryCollection instanceof Drupal.OpenCulturasMapEntryCollection)) {
        throw "invalid entryCollection, expected Drupal.OpenCulturasMapEntryCollection";
      }

      if(this.reloadInteraction === false) {
        render = true;
      } else {
        if(this._entryCollection.size > 0 && this._renderedEntryCollection.hash !== this._entryCollection.hash) {
          this.wrapperElement.dataset.dirty = "true";
        } else {
          this.wrapperElement.dataset.dirty = "";
        }
      }

      this._renderMarker();

      if(!render) {
        console.debug("skipping render of results");
        //this._renderPagedResults(this._renderedEntryCollection);
        return;
      }

      this._renderPagedResults(this._entryCollection);
      this._renderedEntryCollection = this._entryCollection;
    }

    _init() {
      console.debug("Initializing OpenCulturasMap Leaflet...");
      this._initMap();
      this._initControls();
      this._initPager();
      this._listen();
    }

    _initPager() {
      if(this.pagerElement) {
        this.pager = new Drupal.OpenCulturasMapPager(this.pagerElement)
                          .withSettings(this.settings.get('pager'));
        if (this.perPageElement) {
          this.pager.withSelect(this.perPageElement);
        }
      }
    }

    _initMap() {
      console.debug("    => Map...");
      this.mapInstance = L.map(this.mapElement, {
        center: [this.coords.origin.lat, this.coords.origin.lng],
        zoom: this.zoom.origin,
        gestureHandling: true,
      });
      if(this.settings.get('show_radius').includes('init')) {
        this._showRadius('origin');
      }
      console.debug("    => Tiles...");
      this.tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: this.maxZoom,
        minZoom: this.minZoom,
        noWrap: true,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(this.mapInstance);
      console.debug("    => Marker Cluster Group...");
      this.markerCluster = new Drupal.OpenCulturasMapMarkerCluster();
    }

    _initControls() {
      console.debug("    => Controls...");

      if(this.settings.get('control_geocode')) {
        L.Control.geocoder({
          position: 'topleft',
          defaultMarkGeocode: false
        }).on('markgeocode', function(event) {
          this._setCoords(event.geocode.center.lat, event.geocode.center.lng, this.zoom.map, 'map', true);
          if(this.settings.get('show_radius').includes('geocode')) {
            this._showRadius('map');
          }
        }.bind(this)).addTo(this.mapInstance);
      }
      if(this.settings.get('control_locate')) {
        L.control.locate({
          position: "topleft",
          title: Drupal.t("Locate me"),
          zoom: this.zoom.map,
        }).addTo(this.mapInstance);
      }



      if(this.settings.get('control_reset')) {
        L.control.resetView({
          position: "topleft",
          title: Drupal.t("Reset view"),
          latlng: L.latLng([this.coords.origin.lat, this.coords.origin.lng]),
          zoom: this.zoom.origin,
        }).addTo(this.mapInstance);
      }
    }


    _setCoords(lat, lng, zoom, namespace = "map", setView = false) {

      if(
        this.coords[namespace].lat === lat
        && this.coords[namespace].lng === lng
        && this.zoom[namespace] === zoom
      ) {
        return false;
      }

      const point = this.mapInstance.latLngToLayerPoint({lat, lng});

      const oldCoords = {
        latlng: {
          lat: this.coords[namespace].lat,
          lng: this.coords[namespace].lng
        },
        zoom: this.zoom[namespace]
      };
      oldCoords.point = this.mapInstance.latLngToLayerPoint(oldCoords.latlng);


      const distanceInMetres = this.mapInstance.distance(oldCoords.latlng, {lat, lng});
      const distances = {
        metres: distanceInMetres,
        kilometres: distanceInMetres / 100,
        pixels: Math.abs((point.x + point.y) - (oldCoords.point.x + oldCoords.point.y))
      }

      this.coords[namespace].lat = lat;
      this.coords[namespace].lng = lng;
      this.zoom[namespace] = zoom;
      if(setView) {
        this.mapInstance.setView([
          lat,
          lng
        ], zoom);
      }
      this.mapElement.dispatchEvent(new CustomEvent("newcoords", { "detail": {latlng: {lat, lng}, zoom, oldCoords, distances, setView} }));
    }

    _listen() {
      console.debug("    => Event Listeners...");
      this.mapInstance.on('moveend', (event) => this._onMoveEnd(event));
      this.mapInstance.on('locationfound', (event) => this._onLocationFound(event));
      this.mapInstance.on('popupopen', (event) => this._onPopupOpen(event));
      this.mapElement.addEventListener('click', (event) => this._onMapClick(event));

      this.markerCluster.group.on('clusterclick', (event) => this._onMapClusterClick(event));

      if(this.pagerElement) {
        this.pagerElement.addEventListener('click', (event) => this._onPagerChoose(event));
      }

      if(this.reloadElement) {
        this.reloadElement.addEventListener('click', (event) => this._onResultReloadClick(event));
      }

      if(this.resultsElement) {
        this.resultsElement.addEventListener('click', (event) => this._onResultsClick(event));
      }

      if(this.perPageElement) {
        this.perPageElement.addEventListener('change', (event) => this._onPerPageChange(event));
      }
    }

    _onMoveEnd(event) {
      const latlng = event.target.getCenter();
      this._setCoords(latlng.lat, latlng.lng, event.target['_zoom'], 'map');
      if((this.settings.get('show_radius').includes('move')) || this._radialShape) {
        this._showRadius('map');
      }
    }

    _onMapClick(event) {
      if(event.target.href && event.target.href.charAt(event.target.href.length - 1) === '#') {
        event.preventDefault();
      }
      const markerClusterGroupLink = event.target.closest('.marker-cluster-group-link');
      if(markerClusterGroupLink && "markerId" in markerClusterGroupLink.dataset && "clusterId" in markerClusterGroupLink.dataset) {
        this._onMarkerClusterGroupLinkClick(event, markerClusterGroupLink);
      }
    }

    _onMarkerClusterGroupLinkClick(event, markerClusterGroupLink) {
      this.mapInstance.closePopup(); //which will close all popups
        this.mapInstance.eachLayer(function(layer){     //iterate over map layer
          if (layer._leaflet_id == markerClusterGroupLink.dataset.clusterId){         // if layer is markerCluster
            layer.spiderfy(); //spiederfies our cluster
          }
        });
        this.mapInstance.eachLayer(function(layer){     //iterate over map rather than clusters
          if (layer._leaflet_id == markerClusterGroupLink.dataset.markerId){         // if layer is marker
            layer.openPopup();
          }
        });
    }

    _onLocationFound(event) {
      this._setCoords(event.latlng.lat, event.latlng.lng, event.target['_zoom'], 'map');
      this._setCoords(event.latlng.lat, event.latlng.lng, event.target['_zoom'], 'origin');

      if(this.settings.get('show_radius').includes('locate')) {
        this._showRadius('origin');
      }

      if(this._userLocationMarker) {
        this._userLocationMarker.remove();
      }

      this._userLocationMarker = L.marker(event.latlng, {
        icon: new L.DivIcon({
          html: `<div class="marker-located"></div>`
        })
      });

      this._userLocationMarker.addTo(this.mapInstance);
    }

    _onPopupOpen(event) {
      const px = this.mapInstance.project(event.target._popup._latlng); // find the pixel location on the map where the popup anchor is
      px.y -= event.target._popup._container.clientHeight/2; // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
      this.mapInstance.panTo(this.mapInstance.unproject(px),{animate: true});
      setTimeout(() => {
        this.mapInstance.panTo(this.mapInstance.unproject(px),{animate: true}); // pan to new center
      }, 200)
    }

    _onResultsClick(event) {
      const mapLinkNode = event.target.closest('.map-link');
      if(mapLinkNode) {
        this._onResultsMapLinkClick(event, mapLinkNode);
      }
    }

    _onResultsMapLinkClick(event, mapLinkElement) {
      event.preventDefault();
      const nid = mapLinkElement.dataset.markerId;
      const [id, entry] = this._renderedEntryCollection.findMarkerId(nid);


      if(!entry || !entry.marker) {
        return;
      }

      const marker = entry.marker.toLMarker();
      this.mapInstance.closePopup();
      this.mapInstance.setView(marker._latlng, this.maxZoom);
      this.mapElement.scrollIntoView();

      setTimeout(() => {
        this.mapInstance.eachLayer(function(layer){
          if(
            typeof layer.openPopup === 'function'
            && layer.options.marker
            && nid == layer.options.marker.id
            )
          {
            layer.openPopup();
            return;
          }

          if (typeof layer.spiderfy === 'function' && typeof layer.getAllChildMarkers === 'function') {
            layer.spiderfy();
            layer.getAllChildMarkers().forEach(function(childMarker) {
              if(nid == childMarker.options.marker.id && typeof childMarker.openPopup === 'function') {
                childMarker.openPopup();
              }
            });
          }
        });
      }, 800);
    }

    _onResultReloadClick(event) {
      this.stopWaiting();
      this.render();
      this.waitForInteraction();
    }

    _onPagerChoose(event) {
      event.preventDefault();
      const pagerLink = event.target.closest('a');
      if(!pagerLink) { return; }

      const newPage = pagerLink.dataset.page;
      if(!newPage) { return; }

      this.pager.page = parseInt(newPage);
      this._renderPagedResults();

      if(this.counterElement) {
        this.counterElement.scrollIntoView();
      }
    }

    _mapClusterPopup(event) {
      const popUpContent = document.createElement('div');
        const popUpList = document.createElement("ul");
        for (let index in event.layer._markers){
          const marker = event.layer._markers[index]['options']['marker'];
          const popUpListEntry = document.createElement('li');
          popUpListEntry.classList.add('marker-cluster-group-link')
          popUpListEntry.dataset.markerId =  event.layer._markers[index]._leaflet_id;
          popUpListEntry.dataset.clusterId = event.layer._leaflet_id;

          const poupListEntryAnchor = document.createElement('a');
          poupListEntryAnchor.href = '#';
          poupListEntryAnchor.text = marker.title;
          popUpListEntry.appendChild(poupListEntryAnchor);
          popUpList.append(popUpListEntry);
        }

        popUpContent.append(popUpList);
        const popup = L.popup({maxHeight: 325, minWidth: 200, maxWidth : (/Mobi/i.test(window.navigator.userAgent) ?? false) ? 300 : 400, offset: [27, -35], autoPan: false}).setLatLng([event.layer._cLatLng.lat, event.layer._cLatLng.lng]).setContent(popUpContent.innerHTML).openOn(this.mapInstance);
    }

    _onMapClusterClick(event) {
      if(event.layer._zoom == this.maxZoom){
        this._mapClusterPopup(event);
      }
    }

    _onPerPageChange(event) {
      if(this.pager && this.pager.collection) {
        this._renderPagedResults();
      }
    }

  };

}(Drupal, drupalSettings));
