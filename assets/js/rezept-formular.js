(function () {
	'use strict';

	function initialisiereFormular(formular) {
		var hauptkategorie = formular.querySelector('[name="napurelon_hauptkategorie"]');
		var unterkategorie = formular.querySelector('[name="napurelon_unterkategorie"]');
		var zutaten = formular.querySelector('[name="napurelon_zutaten"]');
		var zutatenGruppe = zutaten ? zutaten.closest('[data-pflicht-kategorien]') : null;
		var dateihinweis = formular.querySelector('.npo-rezeptformular__dateihinweis');
		var senden = formular.querySelector('.npo-rezeptformular__senden');

		function aktualisiereKategorie() {
			var auswahl = hauptkategorie ? hauptkategorie.value : '';

			if (unterkategorie) {
				Array.prototype.forEach.call(unterkategorie.options, function (option, index) {
					var sichtbar = index === 0 || (auswahl !== '' && option.getAttribute('data-eltern') === auswahl);
					option.hidden = !sichtbar;
					option.disabled = !sichtbar;
				});

				var aktuelleOption = unterkategorie.options[unterkategorie.selectedIndex];
				if (aktuelleOption && aktuelleOption.disabled) {
					unterkategorie.value = '';
				}
			}

			Array.prototype.forEach.call(formular.querySelectorAll('[data-kategorien]'), function (gruppe) {
				var ids = (gruppe.getAttribute('data-kategorien') || '').split(/\s+/);
				var sichtbar = auswahl !== '' && ids.indexOf(auswahl) !== -1;
				gruppe.hidden = !sichtbar;
				Array.prototype.forEach.call(gruppe.querySelectorAll('input, select, textarea, button'), function (feld) {
					feld.disabled = !sichtbar;
				});
			});

			if (zutaten && zutatenGruppe) {
				var pflichtIds = (zutatenGruppe.getAttribute('data-pflicht-kategorien') || '').split(/\s+/);
				var pflicht = auswahl !== '' && pflichtIds.indexOf(auswahl) !== -1;
				zutaten.required = pflicht;
				Array.prototype.forEach.call(zutatenGruppe.querySelectorAll('[data-pflichtmarkierung]'), function (markierung) {
					markierung.hidden = !pflicht;
				});
			}
		}

		function pruefeDateien() {
			if (!dateihinweis) {
				return;
			}

			dateihinweis.textContent = '';
			var hauptbild = formular.querySelector('[name="napurelon_hauptbild"]');
			var galerie = formular.querySelector('[name="napurelon_galerie[]"]');
			var dateien = [];
			var galeriedateien = galerie && galerie.files ? Array.prototype.slice.call(galerie.files) : [];

			if (galeriedateien.length > 5) {
				dateihinweis.textContent = formular.getAttribute('data-dateianzahl') || '';
				return;
			}

			if (hauptbild && hauptbild.files) {
				dateien = dateien.concat(Array.prototype.slice.call(hauptbild.files));
			}
			dateien = dateien.concat(galeriedateien);

			for (var index = 0; index < dateien.length; index += 1) {
				if (dateien[index].size > 5 * 1024 * 1024) {
					dateihinweis.textContent = formular.getAttribute('data-dateigroesse') || '';
					return;
				}
				if (['image/jpeg', 'image/png', 'image/webp'].indexOf(dateien[index].type) === -1) {
					dateihinweis.textContent = formular.getAttribute('data-dateiformat') || '';
					return;
				}
			}
		}

		if (hauptkategorie) {
			hauptkategorie.addEventListener('change', aktualisiereKategorie);
		}
		var hauptbild = formular.querySelector('[name="napurelon_hauptbild"]');
		var galerie = formular.querySelector('[name="napurelon_galerie[]"]');
		if (hauptbild) {
			hauptbild.addEventListener('change', pruefeDateien);
		}
		if (galerie) {
			galerie.addEventListener('change', pruefeDateien);
		}
		formular.addEventListener('submit', function () {
			if (!senden) {
				return;
			}
			senden.disabled = true;
			senden.textContent = formular.getAttribute('data-sendetext') || senden.textContent;
		});

		aktualisiereKategorie();
	}

	function initialisiere() {
		Array.prototype.forEach.call(document.querySelectorAll('.npo-rezeptformular__form'), initialisiereFormular);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialisiere);
	} else {
		initialisiere();
	}
}());
