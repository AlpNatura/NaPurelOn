/**
 * Metabox "Bildergalerie": Bilder über die Mediathek auswählen und entfernen.
 */
( function ( $ ) {
	'use strict';

	var box = document.querySelector( '.napurelon-galerie' );

	if ( ! box ) {
		return;
	}

	var feld = box.querySelector( '#napurelon_galerie' );
	var liste = box.querySelector( '.napurelon-galerie__vorschau' );
	var rahmen = null;

	function ids() {
		return ( feld.value || '' ).split( ',' ).filter( function ( id ) {
			return id !== '';
		} );
	}

	function speichern( werte ) {
		feld.value = werte.join( ',' );
	}

	box.querySelector( '.napurelon-galerie__auswaehlen' ).addEventListener( 'click', function () {
		if ( ! rahmen ) {
			rahmen = wp.media( {
				title: 'Bilder für die Galerie',
				button: { text: 'Übernehmen' },
				library: { type: 'image' },
				multiple: 'add'
			} );

			rahmen.on( 'select', function () {
				var auswahl = rahmen.state().get( 'selection' );
				var werte = ids();

				auswahl.each( function ( anhang ) {
					var id = String( anhang.id );

					if ( werte.indexOf( id ) !== -1 ) {
						return;
					}

					werte.push( id );

					var groesse = anhang.attributes.sizes && anhang.attributes.sizes.thumbnail
						? anhang.attributes.sizes.thumbnail.url
						: anhang.attributes.url;

					var eintrag = document.createElement( 'li' );
					eintrag.setAttribute( 'data-id', id );
					eintrag.innerHTML = '<img src="" alt="" /><button type="button" class="napurelon-galerie__entfernen" aria-label="Bild entfernen">&times;</button>';
					eintrag.querySelector( 'img' ).src = groesse;
					liste.appendChild( eintrag );
				} );

				speichern( werte );
			} );
		}

		rahmen.open();
	} );

	liste.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.napurelon-galerie__entfernen' );

		if ( ! button ) {
			return;
		}

		var eintrag = button.parentNode;
		var id = eintrag.getAttribute( 'data-id' );

		speichern( ids().filter( function ( wert ) {
			return wert !== id;
		} ) );

		eintrag.remove();
	} );
}( jQuery ) );
