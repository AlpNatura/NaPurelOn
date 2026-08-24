/**
 * Aktionsleiste der Rezeptseite: Likes, Merken, Drucken, Teilen.
 */
( function () {
	'use strict';

	var config = window.napurelonRezept || {};
	var postId = config.postId;
	var likeKey = 'napurelon_like_' + postId;
	var merkKey = 'napurelon_gemerkt';

	function storage() {
		try {
			return window.localStorage;
		} catch ( e ) {
			return null;
		}
	}

	function gemerkteRezepte() {
		var store = storage();

		if ( ! store ) {
			return [];
		}

		try {
			return JSON.parse( store.getItem( merkKey ) || '[]' );
		} catch ( e ) {
			return [];
		}
	}

	// Likes: eine Stimme pro Browser, gespeichert im localStorage.
	var likeButton = document.querySelector( '[data-napurelon-like]' );

	if ( likeButton ) {
		var store = storage();
		var bereitsGeliked = store && store.getItem( likeKey );

		if ( bereitsGeliked ) {
			likeButton.classList.add( 'is-active' );
		}

		likeButton.addEventListener( 'click', function () {
			if ( store && store.getItem( likeKey ) ) {
				return;
			}

			var body = new URLSearchParams();
			body.append( 'action', 'napurelon_rezept_like' );
			body.append( 'nonce', config.nonce );
			body.append( 'post_id', postId );

			fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						return;
					}

					var counter = likeButton.querySelector( '[data-napurelon-like-count]' );

					if ( counter ) {
						counter.textContent = result.data.likes;
					}

					likeButton.classList.add( 'is-active' );

					if ( store ) {
						store.setItem( likeKey, '1' );
					}
				} )
				.catch( function () {} );
		} );
	}

	// Merken: Rezept im Browser vormerken.
	var merkButton = document.querySelector( '[data-napurelon-merken]' );

	if ( merkButton ) {
		var label = merkButton.querySelector( '[data-napurelon-merken-label]' );

		var zeichneMerken = function () {
			var gemerkt = gemerkteRezepte().indexOf( postId ) !== -1;
			merkButton.classList.toggle( 'is-active', gemerkt );

			if ( label ) {
				label.textContent = gemerkt ? config.i18n.saved : config.i18n.remember;
			}
		};

		zeichneMerken();

		merkButton.addEventListener( 'click', function () {
			var store = storage();

			if ( ! store ) {
				return;
			}

			var liste = gemerkteRezepte();
			var index = liste.indexOf( postId );

			if ( index === -1 ) {
				liste.push( postId );
			} else {
				liste.splice( index, 1 );
			}

			store.setItem( merkKey, JSON.stringify( liste ) );
			zeichneMerken();
		} );
	}

	// Drucken.
	var druckButton = document.querySelector( '[data-napurelon-drucken]' );

	if ( druckButton ) {
		druckButton.addEventListener( 'click', function () {
			window.print();
		} );
	}

	// Teilen: Web Share API, sonst Link in die Zwischenablage.
	var teilenButton = document.querySelector( '[data-napurelon-teilen]' );

	if ( teilenButton ) {
		teilenButton.addEventListener( 'click', function () {
			var url = teilenButton.getAttribute( 'data-url' );
			var titel = teilenButton.getAttribute( 'data-titel' );

			if ( navigator.share ) {
				navigator.share( { title: titel, url: url } ).catch( function () {} );
				return;
			}

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( function () {
					var span = teilenButton.querySelector( 'span' );

					if ( span ) {
						span.textContent = config.i18n.copied;
					}
				} );
			}
		} );
	}
}() );
