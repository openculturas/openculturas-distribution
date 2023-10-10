(function (Drupal, $, drupalSettings) {

  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for localized datepicker for de.
   */
  Drupal.behaviors.datepicker_i18n_de = {
    attach: function (context, settings) {
      $.datepicker.regional.de = {
        closeText: "Schließen",
        prevText: "Zurück",
        nextText: "Vor",
        currentText: "Heute",
        monthNames: [ "Januar", "Februar", "März", "April", "Mai", "Juni",
          "Juli", "August", "September", "Oktober", "November", "Dezember" ],
        monthNamesShort: [ "Jan", "Feb", "Mär", "Apr", "Mai", "Jun",
          "Jul", "Aug", "Sep", "Okt", "Nov", "Dez" ],
        dayNames: [ "Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag" ],
        dayNamesShort: [ "So", "Mo", "Di", "Mi", "Do", "Fr", "Sa" ],
        dayNamesMin: [ "So", "Mo", "Di", "Mi", "Do", "Fr", "Sa" ],
        weekHeader: "KW",
        dateFormat: "dd.mm.yy",
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ""
      };
      if (drupalSettings.path.currentLanguage === 'de') {
        $.datepicker.setDefaults( $.datepicker.regional.de);
      }
    },
  };

} (Drupal, jQuery, drupalSettings));
