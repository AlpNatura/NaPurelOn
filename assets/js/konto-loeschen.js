/**
 * Konto löschen: Bestätigung → Umfrage → Passwort, Schritt für Schritt.
 * Ohne JavaScript sind alle Schritte gleichzeitig sichtbar.
 */
( function () {
	'use strict';

	var box = document.getElementById( 'npo-konto-loeschen' );
	if ( ! box ) {
		return;
	}

	var details = box.querySelector( '.npo-konto-loeschen__details' );
	var form = box.querySelector( '.npo-konto-loeschen__form' );
	var schritte = Array.prototype.slice.call( form.querySelectorAll( '[data-schritt]' ) );
	var sonstiges = form.querySelector( '.npo-konto-loeschen__sonstiges' );
	var aktuell = 0;

	box.classList.add( 'npo-js' );

	function zeige( index, fokus ) {
		aktuell = Math.max( 0, Math.min( index, schritte.length - 1 ) );
		schritte.forEach( function ( schritt, n ) {
			schritt.hidden = n !== aktuell;
		} );
		if ( fokus ) {
			var ziel = schritte[ aktuell ].querySelector( 'input:not([type="hidden"]), button' );
			if ( ziel ) {
				ziel.focus();
			}
		}
	}

	function gueltig( schritt ) {
		var felder = schritt.querySelectorAll( 'input, textarea' );
		for ( var i = 0; i < felder.length; i++ ) {
			if ( ! felder[ i ].checkValidity() ) {
				felder[ i ].reportValidity();
				return false;
			}
		}
		return true;
	}

	function sonstigesAnzeigen() {
		var gewaehlt = form.querySelector( 'input[name="napurelon_konto_loeschen_grund"]:checked' );
		sonstiges.hidden = ! gewaehlt || 'sonstiges' !== gewaehlt.value;
	}

	form.addEventListener( 'click', function ( event ) {
		var knopf = event.target.closest( 'button' );
		if ( ! knopf ) {
			return;
		}
		if ( knopf.hasAttribute( 'data-npo-weiter' ) ) {
			if ( gueltig( schritte[ aktuell ] ) ) {
				zeige( aktuell + 1, true );
			}
		} else if ( knopf.hasAttribute( 'data-npo-zurueck' ) ) {
			zeige( aktuell - 1, true );
		} else if ( knopf.hasAttribute( 'data-npo-abbrechen' ) ) {
			details.open = false;
			zeige( 0, false );
		}
	} );

	// Enter darf erst im letzten Schritt absenden.
	form.addEventListener( 'keydown', function ( event ) {
		if ( 'Enter' === event.key && aktuell < schritte.length - 1 && 'TEXTAREA' !== event.target.tagName && 'BUTTON' !== event.target.tagName ) {
			event.preventDefault();
			if ( gueltig( schritte[ aktuell ] ) ) {
				zeige( aktuell + 1, true );
			}
		}
	} );

	form.addEventListener( 'change', sonstigesAnzeigen );

	details.addEventListener( 'toggle', function () {
		if ( ! details.open ) {
			zeige( 0, false );
		}
	} );

	sonstigesAnzeigen();
	zeige( 0, false );
}() );
