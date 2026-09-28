/**
 * "Mehr anzeigen" unter "Neu hinzugefuegte Rezepte": laedt jeweils eine
 * weitere Zeile Rezeptkarten nach. Die Zeilenlaenge folgt den Spalten des
 * Rasters, damit auf Tablet (2 Spalten) und Handy (1 Spalte) ebenfalls
 * genau eine Zeile dazukommt.
 */
( function () {
	'use strict';

	var daten = window.napurelonNeueRezepte;

	if ( ! daten || ! daten.url || ! daten.nonce ) {
		return;
	}

	function spalten( raster ) {
		var vorlage = window.getComputedStyle( raster ).getPropertyValue( 'grid-template-columns' );
		var anzahl = vorlage ? vorlage.split( ' ' ).filter( Boolean ).length : 0;

		return anzahl > 0 ? anzahl : 1;
	}

	function koerper( abschnitt, menge ) {
		var werte = new URLSearchParams();

		werte.append( 'action', 'napurelon_neue_rezepte' );
		werte.append( 'nonce', daten.nonce );
		werte.append( 'versatz', abschnitt.getAttribute( 'data-geladen' ) || '0' );
		werte.append( 'anzahl', String( menge ) );

		return werte;
	}

	function einrichten( abschnitt ) {
		var raster = abschnitt.querySelector( '.npo-neuerezepte__raster' );
		var knopf = abschnitt.querySelector( '[data-npo-mehr-neue]' );
		// Ein in Elementor gebauter Knopf mit dieser Klasse ersetzt den eigenen.
		var eigener = document.querySelector( '.npo-mehr-neue' );

		if ( ! raster ) {
			return;
		}

		if ( eigener ) {
			if ( knopf ) {
				knopf.parentNode.removeChild( knopf );
			}

			knopf = eigener;

			if ( Number( abschnitt.getAttribute( 'data-geladen' ) ) >= Number( abschnitt.getAttribute( 'data-gesamt' ) ) ) {
				knopf.hidden = true;

				return;
			}
		}

		if ( ! knopf ) {
			return;
		}

		knopf.addEventListener( 'click', function ( ereignis ) {
			ereignis.preventDefault();

			if ( knopf.disabled ) {
				return;
			}

			var menge = Math.max( spalten( raster ), Number( abschnitt.getAttribute( 'data-schritt' ) ) || 1 );

			knopf.disabled = true;
			knopf.classList.add( 'is-laedt' );

			window
				.fetch( daten.url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: koerper( abschnitt, menge ).toString(),
				} )
				.then( function ( antwort ) {
					return antwort.json();
				} )
				.then( function ( ergebnis ) {
					if ( ! ergebnis || ! ergebnis.success || ! ergebnis.data ) {
						throw new Error( 'Fehlerhafte Antwort' );
					}

					if ( ergebnis.data.karten ) {
						raster.insertAdjacentHTML( 'beforeend', ergebnis.data.karten );
						raster.dispatchEvent( new CustomEvent( 'napurelon:karten-geladen', { bubbles: true } ) );
					}

					abschnitt.setAttribute( 'data-geladen', String( ergebnis.data.geladen ) );
					abschnitt.setAttribute( 'data-gesamt', String( ergebnis.data.gesamt ) );

					if ( ergebnis.data.fertig ) {
						knopf.hidden = true;

						return;
					}

					knopf.disabled = false;
					knopf.classList.remove( 'is-laedt' );
				} )
				.catch( function () {
					knopf.disabled = false;
					knopf.classList.remove( 'is-laedt' );
				} );
		} );
	}

	function start() {
		var abschnitte = document.querySelectorAll( '[data-npo-neuerezepte]' );

		Array.prototype.forEach.call( abschnitte, einrichten );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
