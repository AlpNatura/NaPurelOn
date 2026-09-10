/**
 * Bildauswahl für das Herobild einer Rezeptkategorie.
 */
( function () {
	'use strict';

	function feldZeile( knopf ) {
		return knopf.closest( 'tr' ) || knopf.closest( '.form-field' ) || document;
	}

	document.addEventListener( 'click', function ( ereignis ) {
		var wahl = ereignis.target.closest( '[data-napurelon-bildwahl]' );
		var weg = ereignis.target.closest( '[data-napurelon-bildentfernen]' );

		if ( wahl ) {
			ereignis.preventDefault();

			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			var bereich = feldZeile( wahl );
			var feld = bereich.querySelector( '[data-napurelon-bildfeld]' );
			var vorschau = bereich.querySelector( '[data-napurelon-bildvorschau]' );

			var rahmen = window.wp.media( {
				title: 'Herobild wählen',
				library: { type: 'image' },
				button: { text: 'Übernehmen' },
				multiple: false
			} );

			rahmen.on( 'select', function () {
				var anhang = rahmen.state().get( 'selection' ).first().toJSON();

				if ( feld ) {
					feld.value = anhang.id;
				}

				if ( vorschau ) {
					var quelle = anhang.sizes && anhang.sizes.medium ? anhang.sizes.medium.url : anhang.url;
					vorschau.src = quelle;
					vorschau.style.display = 'block';
				}
			} );

			rahmen.open();

			return;
		}

		if ( weg ) {
			ereignis.preventDefault();

			var zeile = feldZeile( weg );
			var eingabe = zeile.querySelector( '[data-napurelon-bildfeld]' );
			var bild = zeile.querySelector( '[data-napurelon-bildvorschau]' );

			if ( eingabe ) {
				eingabe.value = '';
			}

			if ( bild ) {
				bild.removeAttribute( 'src' );
				bild.style.display = 'none';
			}
		}
	} );
}() );
