/**
 * Likes in den Rezeptkarten.
 *
 * Nutzt denselben Endpunkt wie die Rezept-Einzelansicht
 * (Aktion "napurelon_rezept_like"); gezählt wird serverseitig.
 * Die Bindung liegt am Dokument, damit auch nachgeladene Karten
 * (Suchtreffer) funktionieren.
 */
( function () {
	'use strict';

	var config = window.napurelonRezeptkarten || {};

	function storage() {
		try {
			return window.localStorage;
		} catch ( e ) {
			return null;
		}
	}

	var store = storage();

	function markieren( wurzel ) {
		if ( ! store ) {
			return;
		}

		var buttons = wurzel.querySelectorAll( '[data-napurelon-karte-like]' );

		Array.prototype.forEach.call( buttons, function ( button ) {
			if ( store.getItem( 'napurelon_like_' + button.getAttribute( 'data-post-id' ) ) ) {
				button.classList.add( 'is-active' );
			}
		} );
	}

	markieren( document );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-napurelon-karte-like]' ) : null;

		if ( ! button ) {
			return;
		}

		// Der Klick darf den Kartenlink nicht auslösen.
		event.preventDefault();
		event.stopPropagation();

		var postId = button.getAttribute( 'data-post-id' );
		var key = 'napurelon_like_' + postId;

		if ( store && store.getItem( key ) ) {
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'napurelon_rezept_like' );
		body.append( 'nonce', config.nonce );
		body.append( 'post_id', postId );

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					return;
				}

				var counter = button.querySelector( '[data-napurelon-karte-like-count]' );

				if ( counter ) {
					counter.textContent = result.data.likes;
				}

				button.classList.add( 'is-active' );

				if ( store ) {
					store.setItem( key, '1' );
				}
			} )
			.catch( function () {} );
	} );

	document.addEventListener( 'napurelon:karten-geladen', function ( event ) {
		markieren( event.target || document );
	} );
}() );
