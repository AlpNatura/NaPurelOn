<?php
/**
 * Bild-Optimierung beim Hochladen.
 *
 * Grosse Fotos werden auf eine maximale Kantenlänge verkleinert, die
 * Kompressionsqualität wird gesenkt und JPEG-/PNG-Dateien werden – sofern der
 * Server WebP unterstützt – in WebP umgewandelt. Das Seitenverhältnis bleibt
 * dabei immer erhalten, es wird nichts beschnitten.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maximale Kantenlänge des Originalbildes in Pixeln.
 */
define( 'NAPURELON_BILD_MAXBREITE', 2000 );

/**
 * Kompressionsqualität für JPEG und WebP (0–100).
 */
define( 'NAPURELON_BILD_QUALITAET', 80 );

/**
 * Schwelle, ab der WordPress ein verkleinertes "-scaled"-Original anlegt.
 *
 * @return int Maximale Kantenlänge.
 */
function napurelon_bild_schwelle() {
	return NAPURELON_BILD_MAXBREITE;
}
add_filter( 'big_image_size_threshold', 'napurelon_bild_schwelle' );

/**
 * Einheitliche Qualität für alle vom Editor erzeugten Bilder.
 *
 * @return int Qualität zwischen 0 und 100.
 */
function napurelon_bild_qualitaet() {
	return NAPURELON_BILD_QUALITAET;
}
add_filter( 'wp_editor_set_quality', 'napurelon_bild_qualitaet' );
add_filter( 'jpeg_quality', 'napurelon_bild_qualitaet' );

/**
 * Wandelt hochgeladene JPEG- und PNG-Dateien nach WebP um.
 *
 * Läuft direkt nach dem Upload, also bevor WordPress die Zwischengrössen
 * erzeugt – dadurch entstehen auch alle Thumbnails als WebP.
 *
 * @param array $upload Upload-Daten mit den Schlüsseln file, url und type.
 * @return array Angepasste Upload-Daten.
 */
function napurelon_upload_zu_webp( $upload ) {
	if ( empty( $upload['type'] ) || ! in_array( $upload['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
		return $upload;
	}

	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $upload;
	}

	$editor = wp_get_image_editor( $upload['file'] );

	if ( is_wp_error( $editor ) ) {
		return $upload;
	}

	$editor->set_quality( NAPURELON_BILD_QUALITAET );

	// Verkleinern ohne Beschnitt: die längere Kante bestimmt die Grösse.
	$editor->resize( NAPURELON_BILD_MAXBREITE, NAPURELON_BILD_MAXBREITE, false );

	$ziel      = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $upload['file'] );
	$gespeichert = $editor->save( $ziel, 'image/webp' );

	if ( is_wp_error( $gespeichert ) || empty( $gespeichert['path'] ) ) {
		return $upload;
	}

	// Original entfernen, damit keine doppelten Dateien liegen bleiben.
	wp_delete_file( $upload['file'] );

	$upload['file'] = $gespeichert['path'];
	$upload['url']  = str_replace( wp_basename( $upload['url'] ), wp_basename( $gespeichert['path'] ), $upload['url'] );
	$upload['type'] = 'image/webp';

	return $upload;
}
add_filter( 'wp_handle_upload', 'napurelon_upload_zu_webp' );
