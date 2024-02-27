/**
 * https://github.com/domoritz/leaflet-locatecontrol/
 * MIT License
 * Copyright (c) 2014 Dominik Moritz
 */
(function (factory, window) {
  if (typeof define === "function" && define.amd) {
    define(["leaflet"], factory);
  } else if (typeof exports === "object") {
    module.exports = factory(require("leaflet"));
  }

  if (typeof window !== "undefined" && window.L) {
    window.L.Control.Locate = factory(L);
  }
}(function (L) {
  Locate = L.Control.extend({
    options: {
      position: "topleft",
      title: "Locate Me",
      zoom: 19,
    },

    onAdd: function(map) {
      this._map = map;
      this._located = false;

      this._container = L.DomUtil.create("div", "leaflet-control-locate leaflet-bar leaflet-control");
      this._link = L.DomUtil.create("a", "leaflet-bar-part leaflet-bar-part-single", this._container);
      this._link.title = this.options.title;
      this._link.href = "#";
      this._link.setAttribute("role", "button");
      this._icon = L.DomUtil.create("span", "leaflet-control-locate-icon", this._link);

      L.DomEvent.on(this._link, "click", this._locate, this);

      return this._container;
    },

    onRemove: function(map) {
      L.DomEvent.off(this._link, "click", this._locate, this);
    },

    _locate: function(e) {
      e.preventDefault();
      this._map.locate(
        {
          setView: true,
          maxZoom: this._map._zoom
        }
      );
    },
  });

  L.control.locate = function(options) {
    return new Locate(options);
  };

  return Locate;
}, window));
