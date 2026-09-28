/**
 * Freitextsuche über die Rezepte: Treffer erscheinen während der Eingabe.
 */
( function () {
	'use strict';

	var config = window.napurelonRezeptSuche || {};
	var suchen = document.querySelectorAll( '.npo-rezeptsuche' );
	// Steht die Trefferliste als eigener Shortcode im Inhaltsbereich, hat sie Vorrang.
	var eigen = document.querySelector( '[data-napurelon-suchtreffer="eigen"]' );
	var zaehler = document.querySelectorAll( '[data-napurelon-suchanzahl]' );
	var bereiche = document.querySelectorAll( '.npo-suchergebnisse' );

	function anzahlText( anzahl ) {
		var i18n = config.i18n || {};

		if ( ! anzahl ) {
			return i18n.keineTreffer || '';
		}

		if ( 1 === anzahl ) {
			return i18n.einTreffer || String( anzahl );
		}

		return ( i18n.vieleTreffer || '%d' ).replace( '%d', anzahl );
	}

	function zeige( sichtbar, anzahl ) {
		Array.prototype.forEach.call( zaehler, function ( element ) {
			element.textContent = sichtbar ? anzahlText( anzahl ) : '';
		} );

		Array.prototype.forEach.call( bereiche, function ( bereich ) {
			bereich.classList.toggle( 'npo-suchergebnisse--sichtbar', sichtbar );
		} );
	}

	Array.prototype.forEach.call( suchen, function ( suche ) {
		var feld = suche.querySelector( '.npo-rezeptsuche__feld' );
		var innen = suche.querySelector( '[data-napurelon-suchtreffer]' );
		var treffer = eigen || innen;

		var status = suche.querySelector( '.npo-rezeptsuche__status' );
		var timer = null;
		var laufend = null;

		if ( eigen && innen ) {
			innen.parentNode.removeChild( innen );
		}

		if ( ! feld || ! treffer ) {
			return;
		}

		function anfrage( begriff ) {
			if ( laufend ) {
				laufend.abort();
			}

			laufend = new AbortController();
			status.textContent = config.i18n ? config.i18n.suchtGerade : '';

			var body = new URLSearchParams();
			body.append( 'action', 'napurelon_rezept_suche' );
			body.append( 'nonce', config.nonce );
			body.append( 'begriff', begriff );

			fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
				signal: laufend.signal
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						return;
					}

					status.textContent = '';
					zeige( true, result.data.anzahl || 0 );

					if ( result.data.html ) {
						treffer.innerHTML = result.data.html;
						treffer.dispatchEvent(
							new CustomEvent( 'napurelon:karten-geladen', { bubbles: true } )
						);
					} else {
						treffer.innerHTML = '<p class="npo-rezeptsuche__leer">' +
							( config.i18n ? config.i18n.keineTreffer : '' ) + '</p>';
					}
				} )
				.catch( function () {
					status.textContent = '';
				} );
		}

		feld.addEventListener( 'input', function () {
			var begriff = feld.value.trim();

			window.clearTimeout( timer );

			if ( begriff.length < 2 ) {
				if ( laufend ) {
					laufend.abort();
					laufend = null;
				}

				status.textContent = '';
				treffer.innerHTML = '';
				zeige( false, 0 );
				return;
			}

			timer = window.setTimeout( function () {
				anfrage( begriff );
			}, 300 );
		} );

		// Serverseitig gelieferte Treffer (Suche ohne JavaScript) bleiben sichtbar.
		if ( treffer.innerHTML.trim() ) {
			Array.prototype.forEach.call( bereiche, function ( bereich ) {
				bereich.classList.add( 'npo-suchergebnisse--sichtbar' );
			} );
		}

		suche.addEventListener( 'submit', function ( event ) {
			// Mit JavaScript bleibt die Seite stehen, die Treffer stehen bereits darunter.
			event.preventDefault();

			var begriff = feld.value.trim();

			if ( begriff.length >= 2 ) {
				window.clearTimeout( timer );
				anfrage( begriff );
			}
		} );
	} );
}() );
