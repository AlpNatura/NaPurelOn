/**
 * Likes in der Kartenliste der neuen Rezepte.
 *
 * Nutzt denselben Endpunkt wie die Rezept-Einzelansicht
 * (Aktion "napurelon_rezept_like"); gezählt wird serverseitig.
 */
( function () {
	'use strict';

	var config = window.napurelonRezeptkarten || {};
	var buttons = document.querySelectorAll( '[data-napurelon-karte-like]' );

	function storage() {
		try {
			return window.localStorage;
		} catch ( e ) {
			return null;
		}
	}

	var store = storage();

	Array.prototype.forEach.call( buttons, function ( button ) {
		var postId = button.getAttribute( 'data-post-id' );
		var key = 'napurelon_like_' + postId;

		if ( store && store.getItem( key ) ) {
			button.classList.add( 'is-active' );
		}

		button.addEventListener( 'click', function ( event ) {
			// Der Klick darf den Kartenlink nicht auslösen.
			event.preventDefault();
			event.stopPropagation();

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
	} );
}() );
