<?php
/**
 * Importiert Beispielrezepte aus tools/beispieldaten/rezepte.json.
 *
 * Aufruf im Wurzelverzeichnis der WordPress-Installation:
 *
 *   wp eval-file wp-content/themes/astra-child/tools/beispielrezepte.php
 *
 * Optionen (als weitere Argumente):
 *   --ohne-bilder   Bilder nicht von Openverse laden.
 *   --entwurf       Rezepte als Entwurf statt veröffentlicht anlegen.
 *
 * Das Skript ist wiederholbar: bereits vorhandene Rezepte (gleicher Titel)
 * werden übersprungen, ebenso bereits angelegte Kategorien.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Dieses Skript läuft nur über WP-CLI (wp eval-file ...).\n" );
}

$napurelon_args      = isset( $args ) && is_array( $args ) ? $args : array();
$napurelon_bilder    = ! in_array( '--ohne-bilder', $napurelon_args, true );
$napurelon_status    = in_array( '--entwurf', $napurelon_args, true ) ? 'draft' : 'publish';
$napurelon_datei     = __DIR__ . '/beispieldaten/rezepte.json';

if ( ! file_exists( $napurelon_datei ) ) {
	WP_CLI::error( 'Datendatei nicht gefunden: ' . $napurelon_datei );
}

$napurelon_daten = json_decode( (string) file_get_contents( $napurelon_datei ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokale Datei.

if ( ! is_array( $napurelon_daten ) ) {
	WP_CLI::error( 'Datendatei ist kein gültiges JSON.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Legt einen Kategoriepfad an (Eltern -> Kind) und gibt die Term-ID des
 * letzten Elements zurück.
 *
 * @param string[] $pfad Namen von der obersten Ebene abwärts.
 * @return int Term-ID oder 0.
 */
function napurelon_beispiel_kategorie( array $pfad ) {
	$parent = 0;

	foreach ( $pfad as $name ) {
		$term = get_term_by( 'name', $name, 'rezeptkategorie' );

		if ( ! $term || (int) $term->parent !== $parent ) {
			$term = term_exists( $name, 'rezeptkategorie', $parent );

			if ( ! $term ) {
				$term = wp_insert_term( $name, 'rezeptkategorie', array( 'parent' => $parent ) );

				if ( is_wp_error( $term ) ) {
					WP_CLI::warning( 'Kategorie "' . $name . '": ' . $term->get_error_message() );
					return 0;
				}
			}
		}

		$parent = (int) ( is_array( $term ) ? $term['term_id'] : $term->term_id );
	}

	return $parent;
}

/**
 * Sucht bei Openverse ein frei lizenziertes Bild.
 *
 * @param string   $suche    Suchbegriff.
 * @param string[] $benutzt  Bereits verwendete Bild-URLs.
 * @return array{url:string,titel:string,urheber:string,lizenz:string,quelle:string}|null
 */
function napurelon_beispiel_bild_abfrage( $suche, array $benutzt ) {
	$url = add_query_arg(
		array(
			'q'            => $suche,
			'license_type' => 'commercial',
			'page_size'    => 20,
			'mature'       => 'false',
		),
		'https://api.openverse.org/v1/images/'
	);

	$antwort = wp_remote_get( $url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $antwort ) || 200 !== (int) wp_remote_retrieve_response_code( $antwort ) ) {
		return null;
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $antwort ), true );

	if ( empty( $body['results'] ) || ! is_array( $body['results'] ) ) {
		return null;
	}

	foreach ( $body['results'] as $treffer ) {
		$bild_url = isset( $treffer['url'] ) ? (string) $treffer['url'] : '';

		if ( '' === $bild_url || in_array( $bild_url, $benutzt, true ) ) {
			continue;
		}

		return array(
			'url'     => $bild_url,
			'titel'   => isset( $treffer['title'] ) ? (string) $treffer['title'] : $suche,
			'urheber' => isset( $treffer['creator'] ) ? (string) $treffer['creator'] : '',
			'lizenz'  => isset( $treffer['license'] ) ? strtoupper( (string) $treffer['license'] ) : '',
			'quelle'  => isset( $treffer['foreign_landing_url'] ) ? (string) $treffer['foreign_landing_url'] : '',
		);
	}

	return null;
}

/**
 * Sucht ein Bild und kürzt den Suchbegriff schrittweise, bis Treffer kommen.
 *
 * Lange Begriffe wie "citrus vinegar cleaner spray bottle" liefern bei
 * Openverse oft null Treffer; "citrus vinegar" dagegen schon.
 *
 * @param string   $suche   Suchbegriff.
 * @param string[] $benutzt Bereits verwendete Bild-URLs.
 * @return array|null
 */
function napurelon_beispiel_bild_suchen( $suche, array $benutzt ) {
	$woerter = preg_split( '/\s+/', trim( $suche ), -1, PREG_SPLIT_NO_EMPTY );

	if ( ! $woerter ) {
		return null;
	}

	while ( $woerter ) {
		$bild = napurelon_beispiel_bild_abfrage( implode( ' ', $woerter ), $benutzt );

		if ( $bild ) {
			return $bild;
		}

		array_pop( $woerter );
	}

	return null;
}

/**
 * Lädt ein passendes Bild und setzt es als Beitragsbild.
 *
 * @param int      $post_id Beitrags-ID.
 * @param string   $titel   Rezepttitel.
 * @param string   $suche   Suchbegriff.
 * @param string[] $benutzt Bereits verwendete Bild-URLs (per Referenz).
 * @return string Bildnachweis oder leerer String.
 */
function napurelon_beispiel_bild_zuweisen( $post_id, $titel, $suche, array &$benutzt ) {
	$bild = napurelon_beispiel_bild_suchen( $suche, $benutzt );

	if ( ! $bild ) {
		WP_CLI::warning( $titel . ': kein Bild gefunden für "' . $suche . '".' );
		return '';
	}

	$benutzt[] = $bild['url'];
	$anhang_id = media_sideload_image( $bild['url'], $post_id, $titel, 'id' );

	if ( is_wp_error( $anhang_id ) ) {
		WP_CLI::warning( $titel . ' (Bild): ' . $anhang_id->get_error_message() );
		return '';
	}

	set_post_thumbnail( $post_id, $anhang_id );

	return 'Bild: ' . trim( $bild['titel'] . ' – ' . $bild['urheber'] . ' (' . $bild['lizenz'] . ') ' . $bild['quelle'] );
}

/**
 * Wandelt die Schritte in Editor-Inhalt (Absätze) um.
 *
 * @param string[] $schritte Zubereitungsschritte.
 * @return string
 */
function napurelon_beispiel_inhalt( array $schritte ) {
	$bloecke = array();

	foreach ( $schritte as $schritt ) {
		$bloecke[] = "<!-- wp:paragraph -->\n<p>" . esc_html( $schritt ) . "</p>\n<!-- /wp:paragraph -->";
	}

	return implode( "\n\n", $bloecke );
}

$napurelon_benutzte_bilder = array();
$napurelon_angelegt        = 0;
$napurelon_uebersprungen   = 0;
$napurelon_nachgeruestet   = 0;

foreach ( $napurelon_daten as $rezept ) {
	if ( empty( $rezept['titel'] ) || empty( $rezept['kategorie'] ) ) {
		continue;
	}

	$titel = (string) $rezept['titel'];

	$vorhanden = get_posts(
		array(
			'post_type'        => 'rezepte',
			'post_status'      => 'any',
			'title'            => $titel,
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	if ( $vorhanden ) {
		$post_id = (int) $vorhanden[0];

		// Fehlt dem bereits vorhandenen Rezept nur das Bild, wird es nachgeholt.
		if ( $napurelon_bilder && ! empty( $rezept['bildsuche'] ) && ! has_post_thumbnail( $post_id ) ) {
			$nachweis = napurelon_beispiel_bild_zuweisen( $post_id, $titel, (string) $rezept['bildsuche'], $napurelon_benutzte_bilder );

			if ( '' !== $nachweis ) {
				update_post_meta(
					$post_id,
					'napurelon_quellen',
					trim( (string) get_post_meta( $post_id, 'napurelon_quellen', true ) . "\n" . $nachweis )
				);

				WP_CLI::log( 'Bild ergänzt: ' . $titel );
				++$napurelon_nachgeruestet;
				continue;
			}
		}

		WP_CLI::log( 'Übersprungen (existiert bereits): ' . $titel );
		++$napurelon_uebersprungen;
		continue;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'rezepte',
			'post_status'  => $napurelon_status,
			'post_title'   => $titel,
			'post_content' => napurelon_beispiel_inhalt( isset( $rezept['schritte'] ) ? (array) $rezept['schritte'] : array() ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( $titel . ': ' . $post_id->get_error_message() );
		continue;
	}

	$term_id = napurelon_beispiel_kategorie( (array) $rezept['kategorie'] );

	if ( $term_id ) {
		wp_set_object_terms( $post_id, array( $term_id ), 'rezeptkategorie' );
	}

	if ( ! empty( $rezept['tags'] ) ) {
		wp_set_object_terms( $post_id, (array) $rezept['tags'], 'rezepttag' );
	}

	$quellen = isset( $rezept['felder']['napurelon_quellen'] ) ? (string) $rezept['felder']['napurelon_quellen'] : '';

	if ( $napurelon_bilder && ! empty( $rezept['bildsuche'] ) ) {
		$nachweis = napurelon_beispiel_bild_zuweisen( $post_id, $titel, (string) $rezept['bildsuche'], $napurelon_benutzte_bilder );

		if ( '' !== $nachweis ) {
			$quellen = trim( $quellen . "\n" . $nachweis );
		}
	}

	$felder = isset( $rezept['felder'] ) ? (array) $rezept['felder'] : array();

	$felder['napurelon_quellen'] = $quellen;

	foreach ( $felder as $key => $wert ) {
		update_post_meta( $post_id, $key, $wert );
	}

	WP_CLI::log( 'Angelegt: ' . $titel );
	++$napurelon_angelegt;
}

WP_CLI::success(
	sprintf(
		'%d Rezepte angelegt, %d Bilder nachgetragen, %d übersprungen.',
		$napurelon_angelegt,
		$napurelon_nachgeruestet,
		$napurelon_uebersprungen
	)
);
