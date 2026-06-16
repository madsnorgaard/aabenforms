/**
 * @file
 * Binds the Klimadatastyrelsen Adressevælger widget to the dawa_address
 * composite element and populates its subfields from the selected record.
 *
 * Requests are routed through the in-app proxy (apiUrl below) so the access
 * token stays server-side. Adressevælger is the successor to DAWA, which is
 * decommissioned 17 August 2026.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Maps an Adressevælger by-id record to the composite subfield values.
   *
   * Handles both an `adresse` lookup ({ adresse: { husnummer: {...} } }) and an
   * access-address lookup ({ husnummer: {...} }), preferring structured fields and
   * falling back to the formatted "betegnelse" when a field is absent.
   */
  function normalizeAddress(record) {
    var adresse = record && record.adresse ? record.adresse : null;
    var hus = (record && record.husnummer)
      ? record.husnummer
      : (adresse && adresse.husnummer ? adresse.husnummer : null);

    var id = (adresse && adresse.id_lokalid) || (hus && hus.id_lokalid) || (record && record.id_lokalid) || '';
    var vejnavn = (hus && hus.vejnavn) || '';
    var husnr = (hus && hus.husnummertekst) || '';
    var etage = (adresse && adresse.etagebetegnelse) || '';
    var doer = (adresse && adresse.doerbetegnelse) || '';
    var postalCode = (hus && hus.postnummer && hus.postnummer.postnr) || '';
    var city = (hus && hus.postnummer && hus.postnummer.navn) || '';

    var streetParts = [[vejnavn, husnr].filter(Boolean).join(' ')];
    if (etage) { streetParts.push(etage + '.'); }
    if (doer) { streetParts.push(doer); }
    var street = streetParts.filter(Boolean).join(' ').trim();

    if (!street) {
      var betegnelse = (adresse && adresse.adressebetegnelse) || (hus && hus.adgangsadressebetegnelse) || '';
      street = postalCode
        ? betegnelse.split(new RegExp(',?\\s*' + postalCode + '\\b'))[0].trim()
        : betegnelse;
    }

    var koord = (hus && hus.adgangspunkt && hus.adgangspunkt.koordinater) || null;
    return {
      id: id,
      street: street,
      postal_code: postalCode,
      city: city,
      x: (koord && typeof koord.x === 'number') ? koord.x : '',
      y: (koord && typeof koord.y === 'number') ? koord.y : ''
    };
  }

  Drupal.behaviors.dawaAddress = {
    attach: function (context) {
      var config = (drupalSettings.aabenforms_webform && drupalSettings.aabenforms_webform.adressevaelger) || {};
      var apiUrl = config.proxyUrl || '/aabenforms/adressevaelger';

      // The widget requires a token option; the proxy injects the real one, so a
      // placeholder is sufficient here.
      var token = config.token || 'proxy';

      once('dawa-autocomplete', '.dawa-address-search', context).forEach(function (searchField) {
        var container = searchField.closest('.webform-composite-dawa-address') || searchField.closest('fieldset');
        if (!container) { return; }

        var streetField = container.querySelector('.dawa-address-street');
        var postalCodeField = container.querySelector('.dawa-address-postal-code');
        var cityField = container.querySelector('.dawa-address-city');
        var idField = container.querySelector('.dawa-address-id');
        var xField = container.querySelector('.dawa-address-x');
        var yField = container.querySelector('.dawa-address-y');

        // The widget's stylesheet targets `.autocomplete-container input`.
        if (searchField.parentNode && !searchField.parentNode.classList.contains('autocomplete-container')) {
          searchField.parentNode.classList.add('autocomplete-container');
        }

        function setReadonly(state) {
          [streetField, postalCodeField, cityField].forEach(function (field) {
            if (!field) { return; }
            if (state) {
              field.setAttribute('readonly', 'readonly');
            }
            else {
              field.removeAttribute('readonly');
            }
          });
        }

        var options = {
          apiUrl: apiUrl,
          token: token,
          select: function (record) {
            var value = normalizeAddress(record);
            if (streetField) { streetField.value = value.street; }
            if (postalCodeField) { postalCodeField.value = value.postal_code; }
            if (cityField) { cityField.value = value.city; }
            if (idField) { idField.value = value.id; }
            if (xField) { xField.value = value.x; }
            if (yField) { yField.value = value.y; }
            setReadonly(true);
          }
        };
        if (config.kommuneKode) { options.kommuneKode = config.kommuneKode; }
        if (config.adgangsadresserOnly) { options.adgangsadresserOnly = true; }

        window.adressevaelger.adressevaelger(searchField, options);

        // Allow manual editing again when the search field is cleared.
        searchField.addEventListener('input', function () {
          if (searchField.value === '') {
            setReadonly(false);
            [streetField, postalCodeField, cityField, idField, xField, yField].forEach(function (field) {
              if (field) { field.value = ''; }
            });
          }
        });
      });
    }
  };

})(Drupal, once);
