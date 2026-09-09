/**
 * Freitextsuche über die Rezepte: Treffer erscheinen während der Eingabe.
 */
( function () {
	'use strict';

	var config = window.napurelonRezeptSuche || {};
	var suchen = document.querySelectorAll( '.npo-rezeptsuche' );

	Array.prototype.forEach.call( suchen, function ( suche ) {
		var feld = suche.querySelector( '.npo-rezeptsuche__feld' );
		var treffer = suche.querySelector( '[data-napurelon-suchtreffer]' );
		var status = suche.querySelector( '.npo-rezeptsuche__status' );
		var timer = null;
		var laufend = null;

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
				return;
			}

			timer = window.setTimeout( function () {
				anfrage( begriff );
			}, 300 );
		} );

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
