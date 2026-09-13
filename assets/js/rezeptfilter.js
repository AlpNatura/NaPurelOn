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
			var offen = knopf.getAttribute('aria-expanded') === 'true';

			schliessen(gruppe);
			knopf.setAttribute('aria-expanded', offen ? 'false' : 'true');
			panel.hidden = offen;
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
