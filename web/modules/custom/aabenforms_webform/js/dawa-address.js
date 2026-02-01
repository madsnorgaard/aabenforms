/**
 * @file
 * DAWA Address autocomplete functionality.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Attaches DAWA autocomplete behavior to address fields.
   */
  Drupal.behaviors.dawaAddress = {
    attach: function (context, settings) {
      const apiUrl = settings.aabenforms_webform ? .dawa_api_url || 'https://api.dataforsyningen.dk/autocomplete';

      once('dawa-autocomplete', '.dawa-address-search', context).forEach(function (searchField) {
        const container = searchField.closest('.webform-composite-dawa-address');
        if (!container) { return;
        }

        const streetField = container.querySelector('.dawa-address-street');
        const postalCodeField = container.querySelector('.dawa-address-postal-code');
        const cityField = container.querySelector('.dawa-address-city');
        const idField = container.querySelector('.dawa-address-id');
        const xField = container.querySelector('.dawa-address-x');
        const yField = container.querySelector('.dawa-address-y');

        let autocompleteList = null;
        let debounceTimer = null;

        // Handle search input.
        searchField.addEventListener('input', function (e) {
          const query = e.target.value.trim();

          // Clear previous timer.
          clearTimeout(debounceTimer);

          // Hide autocomplete if query too short.
          if (query.length < 2) {
            hideAutocomplete();
            return;
          }

          // Debounce API calls (300ms).
          debounceTimer = setTimeout(function () {
            fetchAddresses(query);
          }, 300);
        });

        // Fetch addresses from DAWA API.
        function fetchAddresses(query) {
          const url = `${apiUrl}?q=${encodeURIComponent(query)}&type=adresse&fuzzy=`;

          fetch(url)
            .then(response => response.json())
            .then(data => {
              showAutocomplete(data);
            })
            .catch(error => {
              console.error('DAWA API error:', error);
            });
        }

        // Show autocomplete suggestions.
        function showAutocomplete(addresses) {
          hideAutocomplete();

          if (!addresses || addresses.length === 0) {
            return;
          }

          autocompleteList = document.createElement('ul');
          autocompleteList.className = 'dawa-autocomplete-list';
          autocompleteList.style.cssText = `
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 0;
            padding: 0;
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
          `;

          addresses.forEach(function (address) {
            const item = document.createElement('li');
            item.className = 'dawa-autocomplete-item';
            item.textContent = address.tekst;
            item.style.cssText = `
              padding: 8px 12px;
              cursor: pointer;
              border-bottom: 1px solid #eee;
            `;

            item.addEventListener('mouseenter', function () {
              item.style.backgroundColor = '#f0f0f0';
            });

            item.addEventListener('mouseleave', function () {
              item.style.backgroundColor = 'white';
            });

            item.addEventListener('click', function () {
              selectAddress(address);
            });

            autocompleteList.appendChild(item);
          });

          searchField.parentNode.style.position = 'relative';
          searchField.parentNode.appendChild(autocompleteList);
        }

        // Hide autocomplete.
        function hideAutocomplete() {
          if (autocompleteList) {
            autocompleteList.remove();
            autocompleteList = null;
          }
        }

        // Select an address.
        function selectAddress(address) {
          // Fetch full address details.
          const detailUrl = `https://api.dataforsyningen.dk / adresser / ${address.adresse.id}`;

          fetch(detailUrl)
            .then(response => response.json())
            .then(data => {
              // Populate fields.
              searchField.value = address.tekst;
              streetField.value = `${data.adgangsadresse.vejnavn} ${data.husnr}`;
              postalCodeField.value = data.adgangsadresse.postnummer.nr;
              cityField.value = data.adgangsadresse.postnummer.navn;
              idField.value = data.id;

              // Add coordinates if available.
              if (data.adgangsadresse.adgangspunkt && xField && yField) {
                xField.value = data.adgangsadresse.adgangspunkt.koordinater[0];
                yField.value = data.adgangsadresse.adgangspunkt.koordinater[1];
              }

              hideAutocomplete();

              // Mark fields as readonly.
              streetField.setAttribute('readonly', 'readonly');
              postalCodeField.setAttribute('readonly', 'readonly');
              cityField.setAttribute('readonly', 'readonly');
            })
            .catch(error => {
              console.error('Error fetching address details:', error);
            });
        }

        // Hide autocomplete when clicking outside.
        document.addEventListener('click', function (e) {
          if (!container.contains(e.target)) {
            hideAutocomplete();
          }
        });

        // Allow manual editing if search field is cleared.
        searchField.addEventListener('keyup', function (e) {
          if (e.target.value === '') {
            streetField.removeAttribute('readonly');
            postalCodeField.removeAttribute('readonly');
            cityField.removeAttribute('readonly');
            streetField.value = '';
            postalCodeField.value = '';
            cityField.value = '';
            idField.value = '';
            if (xField) { xField.value = '';
            }
            if (yField) { yField.value = '';
            }
          }
        });
      });
    }
  };

})(Drupal, once);
