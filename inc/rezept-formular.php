<?php
/**
 * Frontendformular für Rezept-Einreichungen.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_FORMULAR_LIMIT', 3 );
define( 'NAPURELON_FORMULAR_BILD_MAX', 5 * MB_IN_BYTES );

/**
 * Profile der Hauptkategorien für die Einreichung.
 *
 * @return array<string,array{felder:string[],zutaten_pflicht:bool}>
 */
function napurelon_formular_profile() {
	return array(
		'Lebensmittel'              => array(
			'felder'           => array(
				'napurelon_zubereitungszeit',
				'napurelon_kochzeit',
				'napurelon_portionen',
				'napurelon_haltbarkeit',
				'napurelon_ausbacken',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => true,
		),
		'Getränke'                  => array(
			'felder'           => array(
				'napurelon_zubereitungszeit',
				'napurelon_kochzeit',
				'napurelon_portionen',
				'napurelon_menge',
				'napurelon_haltbarkeit',
				'napurelon_anwendung',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => true,
		),
		'Naturkosmetik'             => array(
			'felder'           => array(
				'napurelon_zubereitungszeit',
				'napurelon_menge',
				'napurelon_haltbarkeit',
				'napurelon_anwendung',
				'napurelon_einsatzgebiete',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => true,
		),
		'Seifen & Reinigung'        => array(
			'felder'           => array(
				'napurelon_zubereitungszeit',
				'napurelon_menge',
				'napurelon_haltbarkeit',
				'napurelon_anwendung',
				'napurelon_einsatzgebiete',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => true,
		),
		'Startkulturen & Fermente'  => array(
			'felder'           => array(
				'napurelon_zubereitungszeit',
				'napurelon_menge',
				'napurelon_haltbarkeit',
				'napurelon_anwendung',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => true,
		),
		'Grundlagen & Wissen'       => array(
			'felder'           => array(
				'napurelon_anwendung',
				'napurelon_einsatzgebiete',
				'napurelon_vorteile',
				'napurelon_nachteile',
				'napurelon_hinweise',
			),
			'zutaten_pflicht' => false,
		),
	);
}

/**
 * Liefert das passende Profil eines Hauptkategorienbegriffs.
 *
 * @param WP_Term $hauptkategorie Hauptkategorie.
 * @return array{felder:string[],zutaten_pflicht:bool} Profil.
 */
function napurelon_formular_profile_fuer_term( WP_Term $hauptkategorie ) {
	$profile = napurelon_formular_profile();
	$name    = $hauptkategorie->name;

	if ( ! isset( $profile[ $name ] )
		&& function_exists( 'pll_get_term' )
		&& function_exists( 'pll_default_language' ) ) {
		$standard_id = pll_get_term( $hauptkategorie->term_id, pll_default_language() );
		$standard    = $standard_id ? get_term( $standard_id, 'rezeptkategorie' ) : null;

		if ( $standard instanceof WP_Term ) {
			$name = $standard->name;
		}
	}

	if ( isset( $profile[ $name ] ) ) {
		return $profile[ $name ];
	}

	$alle_felder = array();
	foreach ( $profile as $eintrag ) {
		$alle_felder = array_merge( $alle_felder, $eintrag['felder'] );
	}

	return array(
		'felder'           => array_values( array_unique( $alle_felder ) ),
		'zutaten_pflicht' => true,
	);
}

/**
 * Wandelt Formularfelder in die von einer Kategorie erlaubten Meta-Felder um.
 *
 * @return string[]
 */
function napurelon_formular_immer_sichtbare_meta_felder() {
	return array(
		'napurelon_untertitel',
		'napurelon_einleitung',
		'napurelon_zutaten',
		'napurelon_tipps',
		'napurelon_quellen',
		'napurelon_video_url',
	);
}

/**
 * Prüft, ob das Formular gerade einen verarbeiteten Request enthält.
 *
 * @param array|null $setzen Zu speicherndes Ergebnis.
 * @return array Ergebnis mit Fehlern, Werten und Auswahlen.
 */
function napurelon_rezeptformular_ergebnis( $setzen = null ) {
	static $ergebnis = array(
		'fehler'    => array(),
		'werte'     => array(),
		'merkmale'  => array(),
		'tags'      => array(),
		'bildrechte'=> false,
		'datenschutz' => false,
	);

	if ( is_array( $setzen ) ) {
		$ergebnis = $setzen;
	}

	return $ergebnis;
}

/**
 * Ergänzt einen feldbezogenen Fehler.
 *
 * @param array  $fehler Fehlerliste.
 * @param string $feld Formularfeld.
 * @param string $text Fehlermeldung.
 */
function napurelon_formular_fehler_hinzufuegen( &$fehler, $feld, $text ) {
	if ( ! isset( $fehler[ $feld ] ) ) {
		$fehler[ $feld ] = $text;
	}
}

/**
 * Liest einen skalaren Formularwert.
 *
 * @param array  $eingaben Formularwerte.
 * @param string $schluessel Formularschlüssel.
 * @return string
 */
function napurelon_formular_eingabe( $eingaben, $schluessel ) {
	if ( ! isset( $eingaben[ $schluessel ] ) || ! is_scalar( $eingaben[ $schluessel ] ) ) {
		return '';
	}

	return (string) $eingaben[ $schluessel ];
}

/**
 * Liefert die Permalink-Adresse für Rückleitungen.
 *
 * @return string
 */
function napurelon_formular_permalink() {
	$permalink = get_permalink( get_queried_object_id() );

	return $permalink ? $permalink : home_url( '/' );
}

/**
 * Leitet nach erfolgreicher Einreichung zurück zur Formularseite.
 */
function napurelon_formular_erfolgs_redirect() {
	wp_safe_redirect( add_query_arg( 'rezept', 'eingereicht', napurelon_formular_permalink() ) );
	exit;
}

/**
 * Normalisiert ein einzelnes Upload-Feld.
 *
 * @param array $datei Upload-Feld aus $_FILES.
 * @return array|null
 */
function napurelon_formular_datei_normalisieren( $datei ) {
	if ( ! is_array( $datei ) ) {
		return null;
	}

	foreach ( array( 'name', 'tmp_name', 'error', 'size' ) as $schluessel ) {
		if ( ! isset( $datei[ $schluessel ] ) || ! is_scalar( $datei[ $schluessel ] ) ) {
			return null;
		}
	}

	return array(
		'name'     => (string) $datei['name'],
		'tmp_name' => (string) $datei['tmp_name'],
		'error'    => absint( $datei['error'] ),
		'size'     => absint( $datei['size'] ),
	);
}

/**
 * Prüft eine hochgeladene Bilddatei ausschließlich anhand der Serverdatei.
 *
 * @param array|null $datei Normalisierte Datei.
 * @param array      $mimes Erlaubte MIME-Zuordnungen.
 * @return array|false Geprüfte Datei oder false.
 */
function napurelon_formular_bild_pruefen( $datei, $mimes ) {
	if ( ! is_array( $datei ) || UPLOAD_ERR_OK !== $datei['error'] || ! is_uploaded_file( $datei['tmp_name'] ) ) {
		return false;
	}

	if ( $datei['size'] > NAPURELON_FORMULAR_BILD_MAX ) {
		return false;
	}

	$gepruefter_typ = wp_check_filetype_and_ext( $datei['tmp_name'], $datei['name'], $mimes );
	if ( empty( $gepruefter_typ['type'] )
		|| ! in_array( $gepruefter_typ['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return false;
	}

	if ( ! wp_getimagesize( $datei['tmp_name'] ) ) {
		return false;
	}

	$datei['name'] = sanitize_file_name( $datei['name'] );

	return $datei;
}

/**
 * Verarbeitet die abgesicherte POST-Übermittlung des Formulars.
 */
function napurelon_verarbeite_rezeptformular() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD']
		|| ! isset( $_GET['napurelon_formular'] ) || is_admin() ) {
		return;
	}

	$ergebnis = array(
		'fehler'      => array(),
		'werte'       => array(),
		'merkmale'    => array(),
		'tags'        => array(),
		'bildrechte'  => false,
		'datenschutz' => false,
	);
	napurelon_rezeptformular_ergebnis( $ergebnis );

	if ( empty( $_POST ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Dateien sind zu groß. Bitte verkleinere die Bilder.';
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	if ( ! is_user_logged_in() ) {
		$ergebnis['fehler']['_formular'] = 'Bitte melde dich an, um ein Rezept einzureichen.';
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	$eingaben = isset( $_POST ) && is_array( $_POST ) ? wp_unslash( $_POST ) : array();
	$nonce    = napurelon_formular_eingabe( $eingaben, 'napurelon_rezept_nonce' );

	if ( ! wp_verify_nonce( sanitize_key( $nonce ), 'napurelon_rezept_einreichen' ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.';
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	$zeit       = absint( napurelon_formular_eingabe( $eingaben, 'napurelon_zeit' ) );
	$zeit_sig   = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_zeit_sig' ) );
	$signatur   = wp_hash( $zeit . '|' . get_current_user_id() );
	$honeypot   = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_webseite' ) );

	if ( '' !== $honeypot || ! hash_equals( $signatur, $zeit_sig ) || time() - $zeit < 3 ) {
		napurelon_formular_erfolgs_redirect();
	}

	$user_id       = get_current_user_id();
	$einreichungen = get_user_meta( $user_id, '_napurelon_einreichungen', true );
	$einreichungen = is_array( $einreichungen ) ? array_map( 'absint', $einreichungen ) : array();
	$einreichungen = array_values(
		array_filter(
			$einreichungen,
			function ( $zeitstempel ) {
				return $zeitstempel > time() - HOUR_IN_SECONDS;
			}
		)
	);

	if ( ! current_user_can( 'edit_others_posts' ) && count( $einreichungen ) >= NAPURELON_FORMULAR_LIMIT ) {
		$ergebnis['fehler']['_formular'] = 'Du hast in der letzten Stunde bereits drei Rezepte eingereicht. Bitte versuche es später erneut.';
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	$fehler = array();
	$werte  = array();

	$titel         = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_titel' ) );
	$titel_laenge  = mb_strlen( $titel );
	$werte['napurelon_titel'] = mb_substr( $titel, 0, 120 );

	if ( '' === trim( $titel ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_titel', 'Bitte gib einen Rezepttitel ein.' );
	} elseif ( $titel_laenge > 120 ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_titel', 'Der Titel darf höchstens 120 Zeichen lang sein.' );
	}

	$haupt_id         = absint( napurelon_formular_eingabe( $eingaben, 'napurelon_hauptkategorie' ) );
	$hauptkategorien  = napurelon_kategorie_hauptkategorien();
	$hauptkategorie   = null;

	foreach ( $hauptkategorien as $begriff ) {
		if ( $haupt_id === (int) $begriff->term_id ) {
			$hauptkategorie = $begriff;
			break;
		}
	}

	$werte['napurelon_hauptkategorie'] = $haupt_id;
	if ( ! $hauptkategorie ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptkategorie', 'Bitte wähle eine gültige Hauptkategorie.' );
	}

	$unter_raw = trim( napurelon_formular_eingabe( $eingaben, 'napurelon_unterkategorie' ) );
	$unter_id  = absint( $unter_raw );
	$werte['napurelon_unterkategorie'] = $unter_id;
	$unterkategorie = null;

	if ( '' !== $unter_raw ) {
		$unterkategorie = get_term( $unter_id, 'rezeptkategorie' );
		$vorfahren      = $unterkategorie instanceof WP_Term ? get_ancestors( $unter_id, 'rezeptkategorie' ) : array();

		if ( ! $hauptkategorie || ! $unterkategorie instanceof WP_Term
			|| ! in_array( $haupt_id, array_map( 'absint', $vorfahren ), true )
			|| napurelon_begriff_verborgen( $unterkategorie ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_unterkategorie', 'Bitte wähle eine gültige Unterkategorie.' );
			$unterkategorie = null;
			$werte['napurelon_unterkategorie'] = 0;
		}
	}

	$profil = $hauptkategorie
		? napurelon_formular_profile_fuer_term( $hauptkategorie )
		: array(
			'felder'           => array(),
			'zutaten_pflicht' => true,
		);

	$zubereitung = sanitize_textarea_field( napurelon_formular_eingabe( $eingaben, 'napurelon_zubereitung' ) );
	$werte['napurelon_zubereitung'] = mb_substr( $zubereitung, 0, 20000 );
	if ( '' === trim( $zubereitung ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_zubereitung', 'Bitte beschreibe die Zubereitung.' );
	} elseif ( mb_strlen( $zubereitung ) > 20000 ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_zubereitung', 'Die Zubereitung darf höchstens 20.000 Zeichen lang sein.' );
	}

	$meta_whitelist = array(
		'napurelon_untertitel',
		'napurelon_einleitung',
		'napurelon_zutaten',
		'napurelon_ausbacken',
		'napurelon_zubereitungszeit',
		'napurelon_kochzeit',
		'napurelon_portionen',
		'napurelon_menge',
		'napurelon_haltbarkeit',
		'napurelon_anwendung',
		'napurelon_einsatzgebiete',
		'napurelon_tipps',
		'napurelon_vorteile',
		'napurelon_nachteile',
		'napurelon_hinweise',
		'napurelon_quellen',
		'napurelon_video_url',
	);
	$erlaubte_meta = array_unique(
		array_merge(
			napurelon_formular_immer_sichtbare_meta_felder(),
			$profil['felder']
		)
	);
	$felddefinitionen = napurelon_get_rezept_fields();

	foreach ( $meta_whitelist as $schluessel ) {
		if ( ! in_array( $schluessel, $erlaubte_meta, true ) ) {
			continue;
		}

		$rohwert = napurelon_formular_eingabe( $eingaben, $schluessel );
		$typ     = $felddefinitionen[ $schluessel ]['type'];
		if ( '' === trim( $rohwert ) ) {
			$werte[ $schluessel ] = '';
			continue;
		}
		$sanitizer = 'napurelon_sanitize_rezept_meta_' . $typ;
		$wert      = call_user_func( $sanitizer, $rohwert );

		if ( 'number' === $typ ) {
			$wert = min( 10000, max( 0, (int) $wert ) );
		} elseif ( 'url' === $typ ) {
			$wert = (string) $wert;
			if ( '' !== $rohwert && ( '' === $wert || ! in_array( strtolower( (string) wp_parse_url( $wert, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) ) {
				napurelon_formular_fehler_hinzufuegen( $fehler, $schluessel, 'Bitte gib eine gültige HTTP- oder HTTPS-URL ein.' );
			}
		} else {
			$maximum = 'text' === $typ ? 200 : 5000;
			if ( mb_strlen( $wert ) > $maximum ) {
				napurelon_formular_fehler_hinzufuegen(
					$fehler,
					$schluessel,
					'text' === $typ ? 'Dieses Feld darf höchstens 200 Zeichen enthalten.' : 'Dieses Feld darf höchstens 5.000 Zeichen enthalten.'
				);
				$wert = mb_substr( $wert, 0, $maximum );
			}
		}

		$werte[ $schluessel ] = $wert;
	}

	if ( $profil['zutaten_pflicht'] && ( ! isset( $werte['napurelon_zutaten'] ) || '' === trim( $werte['napurelon_zutaten'] ) ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_zutaten', 'Bitte gib die Zutaten an.' );
	}

	$filter_definitionen = napurelon_filter_definitionen();
	$merkmale_roh        = isset( $eingaben['napurelon_merkmale'] ) && is_array( $eingaben['napurelon_merkmale'] )
		? $eingaben['napurelon_merkmale']
		: array();
	if ( array_key_exists( 'napurelon_merkmale', $eingaben ) && ! is_array( $eingaben['napurelon_merkmale'] ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_merkmale', 'Bitte wähle gültige Merkmale für diese Kategorie.' );
	}
	$merkmale_roh = wp_unslash( $merkmale_roh );
	$aktive_filter = $hauptkategorie ? napurelon_kategorie_filter_aktiv( $hauptkategorie ) : array();
	$taxonomie_zu_schluessel = array();

	foreach ( $filter_definitionen as $filter_schluessel => $definition ) {
		if ( isset( $definition['taxonomie'] ) ) {
			$taxonomie_zu_schluessel[ $definition['taxonomie'] ] = $filter_schluessel;
		}
	}

	foreach ( $merkmale_roh as $taxonomie => $term_ids ) {
		if ( ! is_string( $taxonomie ) || ! isset( $taxonomie_zu_schluessel[ $taxonomie ] )
			|| ! in_array( $taxonomie_zu_schluessel[ $taxonomie ], $aktive_filter, true ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_merkmale', 'Bitte wähle gültige Merkmale für diese Kategorie.' );
			continue;
		}

		if ( ! is_array( $term_ids ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_merkmale', 'Bitte wähle gültige Merkmale für diese Kategorie.' );
			continue;
		}

		$gueltige_ids = array();
		foreach ( $term_ids as $term_id_roh ) {
			if ( ! is_scalar( $term_id_roh ) ) {
				napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_merkmale', 'Bitte wähle gültige Merkmale für diese Kategorie.' );
				continue;
			}
			$term_id = absint( $term_id_roh );
			$term    = $term_id ? get_term( $term_id, $taxonomie ) : null;

			if ( ! $term instanceof WP_Term || napurelon_begriff_verborgen( $term ) ) {
				napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_merkmale', 'Bitte wähle gültige Merkmale für diese Kategorie.' );
				continue;
			}

			$gueltige_ids[] = $term_id;
		}

		$ergebnis['merkmale'][ $taxonomie ] = array_values( array_unique( $gueltige_ids ) );
	}

	$tags_roh = isset( $eingaben['napurelon_tags'] ) ? $eingaben['napurelon_tags'] : array();
	$tags_roh = wp_unslash( $tags_roh );
	if ( ! is_array( $tags_roh ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_tags', 'Bitte wähle gültige Rezept-Tags.' );
		$tags_roh = array();
	}

	foreach ( $tags_roh as $term_id_roh ) {
		if ( ! is_scalar( $term_id_roh ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_tags', 'Bitte wähle gültige Rezept-Tags.' );
			continue;
		}
		$term_id = absint( $term_id_roh );
		$term    = $term_id ? get_term( $term_id, 'rezepttag' ) : null;

		if ( ! $term instanceof WP_Term || napurelon_begriff_verborgen( $term ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_tags', 'Bitte wähle gültige Rezept-Tags.' );
			continue;
		}

		$ergebnis['tags'][] = $term_id;
	}
	$ergebnis['tags'] = array_values( array_unique( $ergebnis['tags'] ) );

	$ergebnis['bildrechte']  = '1' === napurelon_formular_eingabe( $eingaben, 'napurelon_bildrechte' );
	$ergebnis['datenschutz'] = '1' === napurelon_formular_eingabe( $eingaben, 'napurelon_datenschutz' );
	if ( ! $ergebnis['bildrechte'] ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_bildrechte', 'Bitte bestätige die Bildrechte.' );
	}
	if ( ! $ergebnis['datenschutz'] ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_datenschutz', 'Bitte bestätige die Datenschutzerklärung.' );
	}

	$mimes = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);
	$hochgeladene_dateien = isset( $_FILES ) && is_array( $_FILES ) ? wp_unslash( $_FILES ) : array();
	$hauptbild_roh        = isset( $hochgeladene_dateien['napurelon_hauptbild'] )
		? napurelon_formular_datei_normalisieren( $hochgeladene_dateien['napurelon_hauptbild'] )
		: null;
	$hauptbild = false;

	if ( ! $hauptbild_roh || UPLOAD_ERR_NO_FILE === $hauptbild_roh['error'] ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptbild', 'Bitte lade ein Hauptbild hoch.' );
	} elseif ( UPLOAD_ERR_OK !== $hauptbild_roh['error'] ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptbild', 'Der Datei-Upload ist fehlgeschlagen.' );
	} elseif ( $hauptbild_roh['size'] > NAPURELON_FORMULAR_BILD_MAX ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptbild', 'Ein Bild darf höchstens 5 MB groß sein.' );
	} elseif ( ! is_uploaded_file( $hauptbild_roh['tmp_name'] ) ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptbild', 'Der Datei-Upload ist fehlgeschlagen.' );
	} else {
		$hauptbild = napurelon_formular_bild_pruefen( $hauptbild_roh, $mimes );
		if ( ! $hauptbild ) {
			$gepruefter_typ = wp_check_filetype_and_ext( $hauptbild_roh['tmp_name'], $hauptbild_roh['name'], $mimes );
			$fehlermeldung  = ! empty( $gepruefter_typ['type'] )
				&& in_array( $gepruefter_typ['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true )
				? 'Ein Bild konnte nicht gelesen werden.'
				: 'Die Dateien müssen JPG, PNG oder WebP sein.';
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_hauptbild', $fehlermeldung );
		}
	}

	$galerie_roh = isset( $hochgeladene_dateien['napurelon_galerie'] ) ? $hochgeladene_dateien['napurelon_galerie'] : null;
	$galerie      = array();
	$galerie_fehler = false;
	if ( null !== $galerie_roh ) {
		if ( ! is_array( $galerie_roh ) || ! isset( $galerie_roh['name'], $galerie_roh['tmp_name'], $galerie_roh['error'], $galerie_roh['size'] )
			|| ! is_array( $galerie_roh['name'] ) || ! is_array( $galerie_roh['tmp_name'] )
			|| ! is_array( $galerie_roh['error'] ) || ! is_array( $galerie_roh['size'] ) ) {
			$galerie_fehler = true;
		} else {
			foreach ( $galerie_roh['name'] as $index => $name ) {
				$fehlercode = isset( $galerie_roh['error'][ $index ] ) ? absint( $galerie_roh['error'][ $index ] ) : UPLOAD_ERR_NO_FILE;
				if ( UPLOAD_ERR_NO_FILE === $fehlercode ) {
					continue;
				}

				if ( ! isset( $galerie_roh['tmp_name'][ $index ], $galerie_roh['size'][ $index ] )
					|| ! is_scalar( $name ) || ! is_scalar( $galerie_roh['tmp_name'][ $index ] )
					|| ! is_scalar( $galerie_roh['size'][ $index ] ) ) {
					$galerie_fehler = true;
					continue;
				}

				$galerie[] = array(
					'name'     => (string) $name,
					'tmp_name' => (string) $galerie_roh['tmp_name'][ $index ],
					'error'    => $fehlercode,
					'size'     => absint( $galerie_roh['size'][ $index ] ),
				);
			}
		}
	}

	if ( count( $galerie ) > 5 ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', 'Bitte lade höchstens 5 Galeriebilder hoch.' );
	}

	$gepruefte_galerie = array();
	foreach ( $galerie as $datei ) {
		if ( UPLOAD_ERR_OK !== $datei['error'] ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', 'Der Datei-Upload ist fehlgeschlagen.' );
			continue;
		}
		if ( $datei['size'] > NAPURELON_FORMULAR_BILD_MAX ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', 'Ein Bild darf höchstens 5 MB groß sein.' );
			continue;
		}
		if ( ! is_uploaded_file( $datei['tmp_name'] ) ) {
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', 'Der Datei-Upload ist fehlgeschlagen.' );
			continue;
		}

		$geprueft = napurelon_formular_bild_pruefen( $datei, $mimes );
		if ( ! $geprueft ) {
			$gepruefter_typ = wp_check_filetype_and_ext( $datei['tmp_name'], $datei['name'], $mimes );
			$fehlermeldung  = ! empty( $gepruefter_typ['type'] )
				&& in_array( $gepruefter_typ['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true )
				? 'Ein Bild konnte nicht gelesen werden.'
				: 'Die Dateien müssen JPG, PNG oder WebP sein.';
			napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', $fehlermeldung );
			continue;
		}
		$gepruefte_galerie[] = $geprueft;
	}
	if ( $galerie_fehler ) {
		napurelon_formular_fehler_hinzufuegen( $fehler, 'napurelon_galerie', 'Der Datei-Upload ist fehlgeschlagen.' );
	}

	$ergebnis['fehler'] = $fehler;
	$ergebnis['werte']  = $werte;
	napurelon_rezeptformular_ergebnis( $ergebnis );

	if ( ! empty( $fehler ) ) {
		return;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'rezepte',
			'post_status'  => 'pending',
			'post_author'  => $user_id,
			'post_title'   => $werte['napurelon_titel'],
			'post_content' => wpautop( $zubereitung ),
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		$ergebnis['fehler'] = array( '_formular' => 'Das Rezept konnte nicht gespeichert werden.' );
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	$kategorie_id = $unterkategorie instanceof WP_Term ? $unterkategorie->term_id : $haupt_id;
	$term_result  = wp_set_object_terms( $post_id, array( (int) $kategorie_id ), 'rezeptkategorie' );
	if ( is_wp_error( $term_result ) ) {
		wp_delete_post( $post_id, true );
		$ergebnis['fehler'] = array( '_formular' => 'Das Rezept konnte nicht gespeichert werden.' );
		napurelon_rezeptformular_ergebnis( $ergebnis );
		return;
	}

	foreach ( $ergebnis['merkmale'] as $taxonomie => $term_ids ) {
		$term_result = wp_set_object_terms( $post_id, $term_ids, $taxonomie );
		if ( is_wp_error( $term_result ) ) {
			wp_delete_post( $post_id, true );
			$ergebnis['fehler'] = array( '_formular' => 'Das Rezept konnte nicht gespeichert werden.' );
			napurelon_rezeptformular_ergebnis( $ergebnis );
			return;
		}
	}

	if ( ! empty( $ergebnis['tags'] ) ) {
		$term_result = wp_set_object_terms( $post_id, $ergebnis['tags'], 'rezepttag' );
		if ( is_wp_error( $term_result ) ) {
			wp_delete_post( $post_id, true );
			$ergebnis['fehler'] = array( '_formular' => 'Das Rezept konnte nicht gespeichert werden.' );
			napurelon_rezeptformular_ergebnis( $ergebnis );
			return;
		}
	}

	foreach ( $erlaubte_meta as $schluessel ) {
		if ( ! isset( $werte[ $schluessel ] ) || '' === (string) $werte[ $schluessel ] ) {
			continue;
		}
		update_post_meta( $post_id, $schluessel, $werte[ $schluessel ] );
	}
	update_post_meta( $post_id, '_napurelon_eingereicht', 1 );

	if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) {
		pll_set_post_language( $post_id, pll_current_language() );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$anhang_ids = array();
	foreach ( array_merge( array( $hauptbild ), $gepruefte_galerie ) as $datei ) {
		$anhang_id = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( $datei['name'] ),
				'tmp_name' => $datei['tmp_name'],
				'error'    => 0,
				'size'     => $datei['size'],
			),
			$post_id,
			null,
			array(
				'test_form' => false,
				'mimes'     => $mimes,
			)
		);

		if ( ! is_int( $anhang_id ) ) {
			foreach ( $anhang_ids as $erstellt_id ) {
				wp_delete_attachment( $erstellt_id, true );
			}
			wp_delete_post( $post_id, true );
			$ergebnis['fehler'] = array( 'napurelon_hauptbild' => 'Die Bilder konnten nicht gespeichert werden.' );
			napurelon_rezeptformular_ergebnis( $ergebnis );
			return;
		}

		$autor_result = wp_update_post(
			array(
				'ID'          => $anhang_id,
				'post_author' => $user_id,
			),
			true
		);
		if ( is_wp_error( $autor_result ) ) {
			$anhang_ids[] = $anhang_id;
			foreach ( $anhang_ids as $erstellt_id ) {
				wp_delete_attachment( $erstellt_id, true );
			}
			wp_delete_post( $post_id, true );
			$ergebnis['fehler'] = array( 'napurelon_hauptbild' => 'Die Bilder konnten nicht gespeichert werden.' );
			napurelon_rezeptformular_ergebnis( $ergebnis );
			return;
		}
		$anhang_ids[] = $anhang_id;
	}

	set_post_thumbnail( $post_id, $anhang_ids[0] );
	$galerie_ids = array_slice( $anhang_ids, 1 );
	if ( ! empty( $galerie_ids ) ) {
		update_post_meta( $post_id, NAPURELON_GALERIE_META_KEY, napurelon_sanitize_galerie_ids( implode( ',', $galerie_ids ) ) );
	}

	$einreichungen[] = time();
	update_user_meta( $user_id, '_napurelon_einreichungen', $einreichungen );

	$benutzer       = get_userdata( $user_id );
	$kategoriename  = $hauptkategorie->name;
	$seitenname     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$betreff        = '[' . $seitenname . '] Neues Rezept eingereicht: ' . $werte['napurelon_titel'];
	$bearbeitungsurl = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	$nachricht      = "Titel: " . $werte['napurelon_titel'] . "\nKategorie: " . $kategoriename . "\nEingereicht von: " . $benutzer->display_name . ' (' . $benutzer->user_email . ")\n\n" . $bearbeitungsurl;
	wp_mail( get_option( 'admin_email' ), $betreff, $nachricht );

	napurelon_formular_erfolgs_redirect();
}

add_action( 'template_redirect', 'napurelon_verarbeite_rezeptformular' );

/**
 * Meldet dem Einreicher die Freigabe eines Rezepts.
 *
 * @param string  $neuer_status Neuer Status.
 * @param string  $alter_status Alter Status.
 * @param WP_Post $post         Geänderter Beitrag.
 */
function napurelon_formular_freigabe_melden( $neuer_status, $alter_status, $post ) {
	if ( 'publish' !== $neuer_status || 'publish' === $alter_status
		|| ! $post instanceof WP_Post || 'rezepte' !== $post->post_type
		|| ! get_post_meta( $post->ID, '_napurelon_eingereicht', true )
		|| get_post_meta( $post->ID, '_napurelon_freigabe_gemeldet', true ) ) {
		return;
	}

	$autor = get_userdata( $post->post_author );
	if ( ! $autor || ! $autor->user_email ) {
		return;
	}

	$nachricht = "Hallo " . $autor->display_name . ",\n\ndein Rezept „" . $post->post_title . "“ wurde freigegeben:\n" . get_permalink( $post->ID ) . "\n\nVielen Dank!";
	wp_mail( $autor->user_email, 'Dein Rezept ist online: ' . $post->post_title, $nachricht );
	update_post_meta( $post->ID, '_napurelon_freigabe_gemeldet', 1 );
}

add_action( 'transition_post_status', 'napurelon_formular_freigabe_melden', 10, 3 );

/**
 * Rendert ein beschriftetes und abgesichertes Formulareingabefeld.
 *
 * @param string $name        Eingabename.
 * @param string $label       Beschriftung.
 * @param string $type        Eingabetyp.
 * @param mixed  $value       Vorbelegter Wert.
 * @param int    $instance    Formularinstanz.
 * @param array  $ergebnis    Verarbeitetes Ergebnis.
 * @param bool   $required    Pflichtfeld.
 * @param string $hilfe       Hilfetext.
 * @param array  $daten       data-Attribute ohne Präfix.
 * @param int    $maxlength   Maximale Textlänge.
 * @param string $zusatzklasse Zusätzliche CSS-Klasse.
 * @param bool   $dynamisch_pflichtig Pflichtfeldstatus wird per JavaScript gesetzt.
 * @return string
 */
function napurelon_formular_feld_html( $name, $label, $type, $value, $instance, $ergebnis, $required = false, $hilfe = '', $daten = array(), $maxlength = 0, $zusatzklasse = '', $dynamisch_pflichtig = false ) {
	$id       = 'npo-rezeptformular-' . $instance . '-' . sanitize_html_class( $name );
	$fehler   = isset( $ergebnis['fehler'][ $name ] );
	$klasse   = 'npo-rezeptformular__feld';
	if ( $fehler ) {
		$klasse .= ' npo-rezeptformular__feld--fehler';
	}
	if ( '' !== $zusatzklasse ) {
		$klasse .= ' ' . $zusatzklasse;
	}
	$pflicht  = $required && ! $dynamisch_pflichtig ? ' required' : '';
	$aria     = $fehler ? ' aria-invalid="true"' : '';
	$beschreibt = '' !== $hilfe ? ' aria-describedby="' . esc_attr( $id . '-hilfe' ) . '"' : '';
	$daten_html = '';
	foreach ( $daten as $schluessel => $wert ) {
		$daten_html .= ' data-' . esc_attr( $schluessel ) . '="' . esc_attr( implode( ' ', array_map( 'strval', (array) $wert ) ) ) . '"';
	}
	$ausgabe  = '<div class="npo-rezeptformular__gruppe"' . $daten_html . '>';
	$ausgabe .= '<label for="' . esc_attr( $id ) . '">' . esc_html( napurelon_text( $label ) );
	if ( $required ) {
		$ausgabe .= ' <span class="npo-rezeptformular__stern" data-pflichtmarkierung aria-hidden="true">*</span><span class="screen-reader-text" data-pflichtmarkierung> ' . esc_html( napurelon_text( 'Pflichtfeld' ) ) . '</span>';
	}
	$ausgabe .= '</label>';

	if ( 'textarea' === $type ) {
		$ausgabe .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $klasse ) . '" rows="5"' . $pflicht . $aria . $beschreibt . '>' . esc_textarea( (string) $value ) . '</textarea>';
	} else {
		$attribute = '';
		if ( 'number' === $type ) {
			$attribute = ' min="0" max="10000" step="1"';
		}
		if ( $maxlength > 0 ) {
			$attribute .= ' maxlength="' . (int) $maxlength . '"';
		}
		$ausgabe .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="' . esc_attr( $klasse ) . '"' . $attribute . $pflicht . $aria . $beschreibt . '>';
	}

	if ( '' !== $hilfe ) {
		$ausgabe .= '<p class="npo-rezeptformular__hilfe" id="' . esc_attr( $id . '-hilfe' ) . '">' . esc_html( napurelon_text( $hilfe ) ) . '</p>';
	}
	$ausgabe .= '</div>';

	return $ausgabe;
}

/**
 * Liefert Hauptkategorien, zu denen ein Profilfeld gehört.
 *
 * @param string $feld Meta-Schlüssel.
 * @param WP_Term[] $hauptkategorien Hauptkategorien.
 * @return int[]
 */
function napurelon_formular_feld_kategorien( $feld, $hauptkategorien ) {
	$ids = array();
	foreach ( $hauptkategorien as $hauptkategorie ) {
		$profil = napurelon_formular_profile_fuer_term( $hauptkategorie );
		if ( in_array( $feld, $profil['felder'], true ) ) {
			$ids[] = (int) $hauptkategorie->term_id;
		}
	}

	return $ids;
}

/**
 * Liefert Kategorien, in denen Zutaten Pflicht sind.
 *
 * @param WP_Term[] $hauptkategorien Hauptkategorien.
 * @return int[]
 */
function napurelon_formular_zutaten_pflicht_kategorien( $hauptkategorien ) {
	$ids = array();
	foreach ( $hauptkategorien as $hauptkategorie ) {
		$profil = napurelon_formular_profile_fuer_term( $hauptkategorie );
		if ( $profil['zutaten_pflicht'] ) {
			$ids[] = (int) $hauptkategorie->term_id;
		}
	}

	return $ids;
}

/**
 * Zeigt das Einreichungsformular oder seinen Status an.
 *
 * @return string Formularausgabe.
 */
function napurelon_rezeptformular_shortcode() {
	$verzeichnis = get_stylesheet_directory();
	$uri          = get_stylesheet_directory_uri();
	$css          = '/assets/css/rezept-formular.css';
	$js           = '/assets/js/rezept-formular.js';

	wp_enqueue_style(
		'napurelon-rezept-formular',
		$uri . $css,
		array( 'napurelon' ),
		file_exists( $verzeichnis . $css ) ? (string) filemtime( $verzeichnis . $css ) : '1.0.0'
	);
	wp_enqueue_script(
		'napurelon-rezept-formular',
		$uri . $js,
		array(),
		file_exists( $verzeichnis . $js ) ? (string) filemtime( $verzeichnis . $js ) : '1.0.0',
		true
	);

	$permalink = napurelon_formular_permalink();
	if ( ! is_user_logged_in() ) {
		ob_start();
		?>
		<div class="npo-rezeptformular__hinweis">
			<p><?php echo esc_html( napurelon_text( 'Bitte melde dich an, um ein Rezept einzureichen.' ) ); ?></p>
			<p><a href="<?php echo esc_url( wp_login_url( $permalink ) ); ?>"><?php echo esc_html( napurelon_text( 'Anmelden' ) ); ?></a>
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				| <a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php echo esc_html( napurelon_text( 'Registrieren' ) ); ?></a>
			<?php endif; ?>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	$ergebnis = napurelon_rezeptformular_ergebnis();
	$seite    = isset( $_GET['rezept'] ) && is_scalar( $_GET['rezept'] ) ? sanitize_key( wp_unslash( $_GET['rezept'] ) ) : '';
	if ( 'eingereicht' === $seite ) {
		ob_start();
		?>
		<div class="npo-rezeptformular__danke" role="status">
			<p><?php echo esc_html( napurelon_text( 'Danke! Dein Rezept wurde eingereicht und wird vor der Veröffentlichung geprüft.' ) ); ?></p>
			<a href="<?php echo esc_url( remove_query_arg( 'rezept', $permalink ) ); ?>"><?php echo esc_html( napurelon_text( 'Weiteres Rezept einreichen' ) ); ?></a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	static $instanz = 0;
	++$instanz;
	$werte            = isset( $ergebnis['werte'] ) && is_array( $ergebnis['werte'] ) ? $ergebnis['werte'] : array();
	$fehler           = isset( $ergebnis['fehler'] ) && is_array( $ergebnis['fehler'] ) ? $ergebnis['fehler'] : array();
	$hauptkategorien  = napurelon_kategorie_hauptkategorien();
	$zutaten_kategorien = napurelon_formular_zutaten_pflicht_kategorien( $hauptkategorien );
	$main_id          = isset( $werte['napurelon_hauptkategorie'] ) ? absint( $werte['napurelon_hauptkategorie'] ) : 0;
	$unter_id         = isset( $werte['napurelon_unterkategorie'] ) ? absint( $werte['napurelon_unterkategorie'] ) : 0;
	$fields           = napurelon_get_rezept_fields();
	$gruppen          = array();

	foreach ( $hauptkategorien as $hauptkategorie ) {
		$kinder = get_terms(
			array(
				'taxonomy'   => 'rezeptkategorie',
				'parent'     => $hauptkategorie->term_id,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $kinder ) ) {
			continue;
		}
		foreach ( $kinder as $kind ) {
			if ( ! napurelon_begriff_verborgen( $kind ) ) {
				$gruppen[] = array(
					'term'   => $kind,
					'eltern' => (int) $hauptkategorie->term_id,
				);
			}
		}
	}

	$merkmale_auswahl = isset( $ergebnis['merkmale'] ) && is_array( $ergebnis['merkmale'] ) ? $ergebnis['merkmale'] : array();
	$tags_auswahl     = isset( $ergebnis['tags'] ) && is_array( $ergebnis['tags'] ) ? $ergebnis['tags'] : array();
	$bildrechte       = ! empty( $ergebnis['bildrechte'] );
	$datenschutz      = ! empty( $ergebnis['datenschutz'] );
	$zeit_formular    = time();

	ob_start();
	?>
	<div class="npo-rezeptformular">
		<?php if ( ! empty( $fehler ) ) : ?>
			<div class="npo-rezeptformular__fehler" role="alert">
				<ul>
					<?php foreach ( $fehler as $meldung ) : ?>
						<li><?php echo esc_html( napurelon_text( $meldung ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="npo-rezeptformular__form"
			method="post"
			enctype="multipart/form-data"
			action="<?php echo esc_url( add_query_arg( 'napurelon_formular', '1', $permalink ) ); ?>"
			data-dateiformat="<?php echo esc_attr( napurelon_text( 'Die Dateien müssen JPG, PNG oder WebP sein.' ) ); ?>"
			data-dateigroesse="<?php echo esc_attr( napurelon_text( 'Ein Bild darf höchstens 5 MB groß sein.' ) ); ?>"
			data-dateianzahl="<?php echo esc_attr( napurelon_text( 'Bitte lade höchstens 5 Galeriebilder hoch.' ) ); ?>"
			data-sendetext="<?php echo esc_attr( napurelon_text( 'Wird gesendet…' ) ); ?>">
			<?php wp_nonce_field( 'napurelon_rezept_einreichen', 'napurelon_rezept_nonce' ); ?>
			<input type="hidden" name="napurelon_zeit" value="<?php echo esc_attr( (string) $zeit_formular ); ?>">
			<input type="hidden" name="napurelon_zeit_sig" value="<?php echo esc_attr( wp_hash( $zeit_formular . '|' . get_current_user_id() ) ); ?>">

			<div class="npo-rezeptformular__honeypot" aria-hidden="true">
				<label for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-webseite' ); ?>"><?php echo esc_html( napurelon_text( 'Deine Website' ) ); ?></label>
				<input type="text" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-webseite' ); ?>" name="napurelon_webseite" tabindex="-1" autocomplete="off">
			</div>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Grunddaten' ) ); ?></h2>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_titel',
					'Rezepttitel',
					'text',
					isset( $werte['napurelon_titel'] ) ? $werte['napurelon_titel'] : '',
					$instanz,
					$ergebnis,
					true,
					'',
					array(),
					120
				);
				?>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_untertitel',
					$fields['napurelon_untertitel']['label'],
					'text',
					isset( $werte['napurelon_untertitel'] ) ? $werte['napurelon_untertitel'] : '',
					$instanz,
					$ergebnis,
					false,
					'',
					array(),
					200
				);
				?>
				<?php
				$haupt_error = isset( $fehler['napurelon_hauptkategorie'] );
				$haupt_id_html = 'npo-rezeptformular-' . $instanz . '-napurelon-hauptkategorie';
				?>
				<div class="npo-rezeptformular__gruppe">
					<label for="<?php echo esc_attr( $haupt_id_html ); ?>"><?php echo esc_html( napurelon_text( 'Hauptkategorie' ) ); ?> <span class="npo-rezeptformular__stern" aria-hidden="true">*</span><span class="screen-reader-text"><?php echo esc_html( napurelon_text( 'Pflichtfeld' ) ); ?></span></label>
					<select id="<?php echo esc_attr( $haupt_id_html ); ?>" name="napurelon_hauptkategorie" class="npo-rezeptformular__feld<?php echo $haupt_error ? ' npo-rezeptformular__feld--fehler' : ''; ?>" required<?php echo $haupt_error ? ' aria-invalid="true"' : ''; ?>>
						<option value=""><?php echo esc_html( napurelon_text( 'Bitte wählen' ) ); ?></option>
						<?php foreach ( $hauptkategorien as $hauptkategorie ) : ?>
							<option value="<?php echo esc_attr( (string) $hauptkategorie->term_id ); ?>" <?php selected( $main_id, $hauptkategorie->term_id ); ?>><?php echo esc_html( napurelon_text( $hauptkategorie->name ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php
				$unter_error = isset( $fehler['napurelon_unterkategorie'] );
				$unter_id_html = 'npo-rezeptformular-' . $instanz . '-napurelon-unterkategorie';
				?>
				<div class="npo-rezeptformular__gruppe">
					<label for="<?php echo esc_attr( $unter_id_html ); ?>"><?php echo esc_html( napurelon_text( 'Unterkategorie' ) ); ?></label>
					<select id="<?php echo esc_attr( $unter_id_html ); ?>" name="napurelon_unterkategorie" class="npo-rezeptformular__feld<?php echo $unter_error ? ' npo-rezeptformular__feld--fehler' : ''; ?>"<?php echo $unter_error ? ' aria-invalid="true"' : ''; ?>>
						<option value=""><?php echo esc_html( napurelon_text( '— keine —' ) ); ?></option>
						<?php foreach ( $gruppen as $gruppe ) : ?>
							<option value="<?php echo esc_attr( (string) $gruppe['term']->term_id ); ?>" data-eltern="<?php echo esc_attr( (string) $gruppe['eltern'] ); ?>" <?php selected( $unter_id, $gruppe['term']->term_id ); ?>><?php echo esc_html( napurelon_text( $gruppe['term']->name ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_einleitung',
					$fields['napurelon_einleitung']['label'],
					'textarea',
					isset( $werte['napurelon_einleitung'] ) ? $werte['napurelon_einleitung'] : '',
					$instanz,
					$ergebnis
				);
				?>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Zutaten & Zubereitung' ) ); ?></h2>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_zutaten',
					$fields['napurelon_zutaten']['label'],
					'textarea',
					isset( $werte['napurelon_zutaten'] ) ? $werte['napurelon_zutaten'] : '',
					$instanz,
					$ergebnis,
					true,
					'Eine Zutat pro Zeile. Eine Zeile, die mit „:“ endet, wird zur Zwischenüberschrift (z. B. „Teig:“). Zutaten sind erforderlich, außer in „Grundlagen & Wissen“.',
					array(
						'pflicht-kategorien' => $zutaten_kategorien,
					),
					0,
					'',
					true
				);
				?>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_zubereitung',
					'Zubereitung',
					'textarea',
					isset( $werte['napurelon_zubereitung'] ) ? $werte['napurelon_zubereitung'] : '',
					$instanz,
					$ergebnis,
					true,
					'',
					array(),
					20000
				);
				?>
				<?php
				echo napurelon_formular_feld_html(
					'napurelon_ausbacken',
					$fields['napurelon_ausbacken']['label'],
					'textarea',
					isset( $werte['napurelon_ausbacken'] ) ? $werte['napurelon_ausbacken'] : '',
					$instanz,
					$ergebnis,
					false,
					'',
					array(
						'kategorien' => napurelon_formular_feld_kategorien( 'napurelon_ausbacken', $hauptkategorien ),
					)
				);
				?>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Details' ) ); ?></h2>
				<div class="npo-rezeptformular__raster">
					<?php
					foreach ( array( 'napurelon_zubereitungszeit', 'napurelon_kochzeit', 'napurelon_portionen', 'napurelon_menge', 'napurelon_haltbarkeit' ) as $schluessel ) {
						$feld = $fields[ $schluessel ];
						$label = $feld['label'];
						if ( in_array( $schluessel, array( 'napurelon_zubereitungszeit', 'napurelon_kochzeit' ), true ) ) {
							$label .= ' (Minuten)';
						}
						echo napurelon_formular_feld_html(
							$schluessel,
							$label,
							$feld['type'],
							isset( $werte[ $schluessel ] ) ? $werte[ $schluessel ] : '',
							$instanz,
							$ergebnis,
							false,
							'',
							array(
								'kategorien' => napurelon_formular_feld_kategorien( $schluessel, $hauptkategorien ),
							),
							0,
							'npo-rezeptformular__kurzfeld'
						);
					}
					?>
				</div>
				<?php
				foreach ( array( 'napurelon_anwendung', 'napurelon_einsatzgebiete' ) as $schluessel ) {
					$feld = $fields[ $schluessel ];
					echo napurelon_formular_feld_html(
						$schluessel,
						$feld['label'],
						$feld['type'],
						isset( $werte[ $schluessel ] ) ? $werte[ $schluessel ] : '',
						$instanz,
						$ergebnis,
						false,
						'',
						array(
							'kategorien' => napurelon_formular_feld_kategorien( $schluessel, $hauptkategorien ),
						)
					);
				}
				?>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Gut zu wissen' ) ); ?></h2>
				<?php
				foreach ( array( 'napurelon_tipps', 'napurelon_vorteile', 'napurelon_nachteile', 'napurelon_hinweise', 'napurelon_quellen', 'napurelon_video_url' ) as $schluessel ) {
					$feld  = $fields[ $schluessel ];
					$daten = in_array( $schluessel, napurelon_formular_immer_sichtbare_meta_felder(), true )
						? array()
						: array(
							'kategorien' => napurelon_formular_feld_kategorien( $schluessel, $hauptkategorien ),
						);
					echo napurelon_formular_feld_html(
						$schluessel,
						$feld['label'],
						$feld['type'],
						isset( $werte[ $schluessel ] ) ? $werte[ $schluessel ] : '',
						$instanz,
						$ergebnis,
						false,
						'',
						$daten
					);
				}
				?>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Merkmale' ) ); ?></h2>
				<?php foreach ( napurelon_filter_definitionen() as $filter_schluessel => $filter ) : ?>
					<?php if ( empty( $filter['taxonomie'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php
					$taxonomie = $filter['taxonomie'];
					$begriffe  = napurelon_filter_taxonomie_begriffe( $taxonomie );
					$begriffe  = array_values(
						array_filter(
							$begriffe,
							function ( $begriff ) {
								return ! napurelon_begriff_verborgen( $begriff );
							}
						)
					);
					if ( empty( $begriffe ) ) {
						continue;
					}
					$kategorien = array();
					foreach ( $hauptkategorien as $hauptkategorie ) {
						if ( in_array( $filter_schluessel, napurelon_kategorie_filter_aktiv( $hauptkategorie ), true ) ) {
							$kategorien[] = (int) $hauptkategorie->term_id;
						}
					}
					$merkmale_error = isset( $fehler['napurelon_merkmale'] );
					$fieldset_id = 'npo-rezeptformular-' . $instanz . '-' . sanitize_html_class( $taxonomie );
					?>
					<fieldset class="npo-rezeptformular__merkmalgruppe<?php echo $merkmale_error ? ' npo-rezeptformular__feld--fehler' : ''; ?>" data-kategorien="<?php echo esc_attr( implode( ' ', $kategorien ) ); ?>">
						<legend><?php echo esc_html( napurelon_text( $filter['label'] ) ); ?></legend>
						<div class="npo-rezeptformular__checkboxen">
							<?php foreach ( $begriffe as $begriff ) : ?>
								<?php $checked = isset( $merkmale_auswahl[ $taxonomie ] ) && in_array( (int) $begriff->term_id, array_map( 'absint', $merkmale_auswahl[ $taxonomie ] ), true ); ?>
								<label for="<?php echo esc_attr( $fieldset_id . '-' . $begriff->term_id ); ?>" class="npo-rezeptformular__pille">
									<input type="checkbox" id="<?php echo esc_attr( $fieldset_id . '-' . $begriff->term_id ); ?>" name="<?php echo esc_attr( 'napurelon_merkmale[' . $taxonomie . '][]' ); ?>" value="<?php echo esc_attr( (string) $begriff->term_id ); ?>" <?php checked( $checked ); ?><?php echo $merkmale_error ? ' aria-invalid="true"' : ''; ?>>
									<?php echo esc_html( napurelon_text( $begriff->name ) ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endforeach; ?>

				<?php
				$tags = get_terms(
					array(
						'taxonomy'   => 'rezepttag',
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $tags ) ) {
					$tags = array_values(
						array_filter(
							$tags,
							function ( $tag ) {
								return ! napurelon_begriff_verborgen( $tag );
							}
						)
					);
				}
				$tags_error = isset( $fehler['napurelon_tags'] );
				?>
				<?php if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) : ?>
					<fieldset class="npo-rezeptformular__merkmalgruppe<?php echo $tags_error ? ' npo-rezeptformular__feld--fehler' : ''; ?>">
						<legend><?php echo esc_html( napurelon_text( 'Tags' ) ); ?></legend>
						<div class="npo-rezeptformular__checkboxen">
							<?php foreach ( $tags as $tag ) : ?>
								<?php $checked = in_array( (int) $tag->term_id, array_map( 'absint', $tags_auswahl ), true ); ?>
								<label for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-tag-' . $tag->term_id ); ?>" class="npo-rezeptformular__pille">
									<input type="checkbox" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-tag-' . $tag->term_id ); ?>" name="napurelon_tags[]" value="<?php echo esc_attr( (string) $tag->term_id ); ?>" <?php checked( $checked ); ?><?php echo $tags_error ? ' aria-invalid="true"' : ''; ?>>
									<?php echo esc_html( napurelon_text( $tag->name ) ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endif; ?>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Bilder' ) ); ?></h2>
				<div class="npo-rezeptformular__gruppe">
					<label for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-hauptbild' ); ?>"><?php echo esc_html( napurelon_text( 'Hauptbild' ) ); ?> <span class="npo-rezeptformular__stern" aria-hidden="true">*</span><span class="screen-reader-text"><?php echo esc_html( napurelon_text( 'Pflichtfeld' ) ); ?></span></label>
					<input type="file" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-hauptbild' ); ?>" name="napurelon_hauptbild" class="npo-rezeptformular__feld<?php echo isset( $fehler['napurelon_hauptbild'] ) ? ' npo-rezeptformular__feld--fehler' : ''; ?>" accept="image/jpeg,image/png,image/webp" required aria-describedby="<?php echo esc_attr( 'npo-rezeptformular-dateihilfe-' . $instanz ); ?>"<?php echo isset( $fehler['napurelon_hauptbild'] ) ? ' aria-invalid="true"' : ''; ?>>
				</div>
				<div class="npo-rezeptformular__gruppe">
					<label for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-galerie' ); ?>"><?php echo esc_html( napurelon_text( 'Galeriebilder' ) ); ?></label>
					<input type="file" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-galerie' ); ?>" name="napurelon_galerie[]" class="npo-rezeptformular__feld<?php echo isset( $fehler['napurelon_galerie'] ) ? ' npo-rezeptformular__feld--fehler' : ''; ?>" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="<?php echo esc_attr( 'npo-rezeptformular-dateihilfe-' . $instanz ); ?>"<?php echo isset( $fehler['napurelon_galerie'] ) ? ' aria-invalid="true"' : ''; ?>>
				</div>
				<p class="npo-rezeptformular__hilfe" id="<?php echo esc_attr( 'npo-rezeptformular-dateihilfe-' . $instanz ); ?>"><?php echo esc_html( napurelon_text( 'JPG, PNG oder WebP, höchstens 5 MB pro Bild, bis zu 5 Galeriebilder.' ) ); ?></p>
				<p class="npo-rezeptformular__dateihinweis" aria-live="polite" id="<?php echo esc_attr( 'npo-rezeptformular-dateihinweis-' . $instanz ); ?>"></p>
			</section>

			<section class="npo-rezeptformular__abschnitt">
				<h2><?php echo esc_html( napurelon_text( 'Bestätigung' ) ); ?></h2>
				<div class="npo-rezeptformular__gruppe">
					<label class="npo-rezeptformular__zustimmung" for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-bildrechte' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-bildrechte' ); ?>" name="napurelon_bildrechte" value="1" required <?php checked( $bildrechte ); ?><?php echo isset( $fehler['napurelon_bildrechte'] ) ? ' class="npo-rezeptformular__feld--fehler" aria-invalid="true"' : ''; ?>>
						<?php echo esc_html( napurelon_text( 'Ich habe die Fotos selbst aufgenommen oder besitze die Rechte, sie hier veröffentlichen zu lassen.' ) ); ?>
						<span class="npo-rezeptformular__stern" aria-hidden="true">*</span><span class="screen-reader-text"><?php echo esc_html( napurelon_text( 'Pflichtfeld' ) ); ?></span>
					</label>
				</div>
				<div class="npo-rezeptformular__gruppe">
					<label class="npo-rezeptformular__zustimmung" for="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-datenschutz' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( 'npo-rezeptformular-' . $instanz . '-napurelon-datenschutz' ); ?>" name="napurelon_datenschutz" value="1" required <?php checked( $datenschutz ); ?><?php echo isset( $fehler['napurelon_datenschutz'] ) ? ' class="npo-rezeptformular__feld--fehler" aria-invalid="true"' : ''; ?>>
						<?php echo esc_html( napurelon_text( 'Ich habe die ' ) ); ?>
						<?php if ( get_privacy_policy_url() ) : ?>
							<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?>
						<?php endif; ?>
						<?php echo esc_html( napurelon_text( ' gelesen und bin mit der Verarbeitung meiner Angaben einverstanden.' ) ); ?>
						<span class="npo-rezeptformular__stern" aria-hidden="true">*</span><span class="screen-reader-text"><?php echo esc_html( napurelon_text( 'Pflichtfeld' ) ); ?></span>
					</label>
				</div>
			</section>

			<div class="npo-rezeptformular__abschluss">
				<button type="submit" class="npo-rezeptformular__senden"><?php echo esc_html( napurelon_text( 'Rezept einreichen' ) ); ?></button>
				<p><?php echo esc_html( napurelon_text( 'Dein Rezept wird vor der Veröffentlichung geprüft.' ) ); ?></p>
			</div>
		</form>
	</div>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_rezept_formular', 'napurelon_rezeptformular_shortcode' );
