/**
 * Schwebender Kategoriewechsler: Knopf klappt die Kategorienliste auf.
 */
(function () {
	'use strict';

	var wechsler = Array.prototype.slice.call(document.querySelectorAll('[data-napurelon-katmenue]'));

	if (!wechsler.length) {
		return;
	}

	wechsler.forEach(function (menue) {
		var knopf = menue.querySelector('.npo-katmenue__knopf');
		var liste = menue.querySelector('.npo-katmenue__liste');

		if (!knopf || !liste) {
			return;
		}

		function schliessen() {
			knopf.setAttribute('aria-expanded', 'false');
			liste.hidden = true;
		}

		knopf.addEventListener('click', function () {
			if (knopf.getAttribute('aria-expanded') === 'true') {
				schliessen();

				return;
			}

			knopf.setAttribute('aria-expanded', 'true');
			liste.hidden = false;
		});

		document.addEventListener('click', function (ereignis) {
			if (!menue.contains(ereignis.target)) {
				schliessen();
			}
		});

		document.addEventListener('keydown', function (ereignis) {
			if ('Escape' === ereignis.key) {
				schliessen();
			}
		});
	});
})();
