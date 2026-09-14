/**
 * "Mehr anzeigen" auf den Kategorieseiten: laedt jeweils eine weitere Zeile
 * Rezeptkarten nach. Die Zeilenlaenge folgt den Spalten des Rasters, damit
 * auf Tablet (2 Spalten) und Handy (1 Spalte) ebenfalls genau eine Zeile
 * dazukommt.
 */
( function () {
	'use strict';

	var daten = window.napurelonKatRezepte;

	if ( ! daten || ! daten.url || ! daten.nonce ) {
		return;
	}

	function spalten( raster ) {
		var vorlage = window.getComputedStyle( raster ).getPropertyValue( 'grid-template-columns' );
		var anzahl = vorlage ? vorlage.split( ' ' ).filter( Boolean ).length : 0;

		return anzahl > 0 ? anzahl : 1;
	}

	function filterFelder( abschnitt ) {
		try {
			var gelesen = JSON.parse( abschnitt.getAttribute( 'data-filter' ) || '{}' );

			return gelesen && typeof gelesen === 'object' ? gelesen : {};
		} catch ( fehler ) {
			return {};
		}
	}

	function koerper( abschnitt, menge ) {
		var werte = new URLSearchParams();

		werte.append( 'action', 'napurelon_kat_rezepte' );
		werte.append( 'nonce', daten.nonce );
		werte.append( 'kategorie', abschnitt.getAttribute( 'data-kategorie' ) || '' );
		werte.append( 'sortierung', abschnitt.getAttribute( 'data-sortierung' ) || 'neu' );
		werte.append( 'versatz', abschnitt.getAttribute( 'data-geladen' ) || '0' );
		werte.append( 'anzahl', String( menge ) );

		var filter = filterFelder( abschnitt );

		Object.keys( filter ).forEach( function ( name ) {
			var liste = Array.isArray( filter[ name ] ) ? filter[ name ] : [ filter[ name ] ];

			liste.forEach( function ( wert ) {
				werte.append( 'filter[' + name + '][]', wert );
			} );
		} );

		return werte;
	}

	function einrichten( abschnitt ) {
		var raster = abschnitt.querySelector( '.npo-katrezepte__raster' );
		var knopf = abschnitt.querySelector( '[data-npo-mehr]' );
		// Ein in Elementor gebauter Knopf mit dieser Klasse ersetzt den eigenen.
		var eigener = document.querySelector( '.npo-mehr-anzeigen' );

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
		var abschnitte = document.querySelectorAll( '[data-npo-katrezepte]' );

		Array.prototype.forEach.call( abschnitte, einrichten );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
