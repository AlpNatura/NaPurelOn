/**
 * Filterleiste der Kategorieseite: Klappfelder öffnen und Auswahl sofort
 * anwenden. Ohne Skript bleibt das Formular mit dem Knopf "Anwenden" nutzbar.
 */
(function () {
	'use strict';

	var formular = document.querySelector('[data-napurelon-filter]');

	if (!formular) {
		return;
	}

	formular.classList.add('is-sofort');

	var gruppen = Array.prototype.slice.call(formular.querySelectorAll('.npo-filter__gruppe'));
	var zeigermaus = window.matchMedia ? window.matchMedia('(hover: hover)').matches : false;
	var nachlauf = null;

	function oeffnen(gruppe) {
		var knopf = gruppe.querySelector('.npo-filter__knopf');
		var panel = gruppe.querySelector('.npo-filter__panel');

		schliessen(gruppe);

		if (!knopf || !panel) {
			return;
		}

		knopf.setAttribute('aria-expanded', 'true');
		panel.hidden = false;
	}

	function schliessen(ausser) {
		gruppen.forEach(function (gruppe) {
			if (gruppe === ausser) {
				return;
			}

			var knopf = gruppe.querySelector('.npo-filter__knopf');
			var panel = gruppe.querySelector('.npo-filter__panel');

			if (knopf && panel) {
				knopf.setAttribute('aria-expanded', 'false');
				panel.hidden = true;
			}
		});
	}

	gruppen.forEach(function (gruppe) {
		var knopf = gruppe.querySelector('.npo-filter__knopf');
		var panel = gruppe.querySelector('.npo-filter__panel');

		if (!knopf || !panel) {
			return;
		}

		knopf.addEventListener('click', function () {
			if (knopf.getAttribute('aria-expanded') === 'true') {
				schliessen(null);

				return;
			}

			oeffnen(gruppe);
		});

		if (!zeigermaus) {
			return;
		}

		gruppe.addEventListener('mouseenter', function () {
			window.clearTimeout(nachlauf);
			oeffnen(gruppe);
		});

		// Kurzer Nachlauf, damit der Weg vom Knopf in die Liste nicht schliesst.
		gruppe.addEventListener('mouseleave', function () {
			window.clearTimeout(nachlauf);
			nachlauf = window.setTimeout(function () {
				schliessen(null);
			}, 200);
		});
	});

	formular.addEventListener('change', function (ereignis) {
		if (ereignis.target && 'checkbox' === ereignis.target.type) {
			formular.submit();
		}
	});

	document.addEventListener('click', function (ereignis) {
		if (!formular.contains(ereignis.target)) {
			schliessen(null);
		}
	});

	document.addEventListener('keydown', function (ereignis) {
		if ('Escape' === ereignis.key) {
			schliessen(null);
		}
	});
})();
