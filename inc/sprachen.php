<?php
/**
 * Sprachanbindung an Polylang: Rezepte, Rezeptkategorien und die
 * Merkmals-Taxonomien sind übersetzbar, die festen Texte der Filterzeile
 * lassen sich unter Sprachen → Zeichenketten übersetzen.
 *
 * Ohne Polylang bleibt alles unverändert in der Ausgangssprache.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/** Name der Zeichenketten-Gruppe in Polylang. */
const NAPURELON_SPRACHGRUPPE = 'NaPurelOn';

/**
 * Inhaltstypen, die Polylang übersetzen soll.
 *
 * @return string[] Namen der Inhaltstypen.
 */
function napurelon_sprache_inhaltstypen() {
	return array( 'rezepte' );
}

/**
 * Taxonomien, die Polylang übersetzen soll.
 *
 * @return string[] Namen der Taxonomien.
 */
function napurelon_sprache_taxonomien() {
	$taxonomien = array( 'rezeptkategorie', 'rezepttag' );

	foreach ( napurelon_filter_definitionen() as $filter ) {
		if ( isset( $filter['taxonomie'] ) ) {
			$taxonomien[] = $filter['taxonomie'];
		}
	}

	return $taxonomien;
}

/**
 * Meldet die Rezepte bei Polylang als übersetzbar an.
 *
 * @param array $inhaltstypen Bisherige Liste.
 * @param bool  $einstellungen Aufruf aus der Einstellungsseite.
 * @return array Ergänzte Liste.
 */
function napurelon_sprache_inhaltstypen_anmelden( $inhaltstypen, $einstellungen ) {
	if ( $einstellungen ) {
		return $inhaltstypen;
	}

	foreach ( napurelon_sprache_inhaltstypen() as $name ) {
		$inhaltstypen[ $name ] = $name;
	}

	return $inhaltstypen;
}

add_filter( 'pll_get_post_types', 'napurelon_sprache_inhaltstypen_anmelden', 10, 2 );

/**
 * Meldet die Rezept-Taxonomien bei Polylang als übersetzbar an.
 *
 * @param array $taxonomien Bisherige Liste.
 * @param bool  $einstellungen Aufruf aus der Einstellungsseite.
 * @return array Ergänzte Liste.
 */
function napurelon_sprache_taxonomien_anmelden( $taxonomien, $einstellungen ) {
	if ( $einstellungen ) {
		return $taxonomien;
	}

	foreach ( napurelon_sprache_taxonomien() as $name ) {
		$taxonomien[ $name ] = $name;
	}

	return $taxonomien;
}

add_filter( 'pll_get_taxonomies', 'napurelon_sprache_taxonomien_anmelden', 10, 2 );

/**
 * Übersetzt einen festen Text der Oberfläche, sofern Polylang aktiv ist.
 *
 * @param string $text Ausgangstext.
 * @return string Übersetzter Text.
 */
function napurelon_text( $text ) {
	if ( function_exists( 'pll__' ) ) {
		return (string) pll__( $text );
	}

	return $text;
}

/**
 * Trägt die festen Texte der Filterzeile in Polylang ein.
 */
function napurelon_sprache_zeichenketten() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$texte = array();

	foreach ( napurelon_filter_definitionen() as $filter ) {
		$texte[] = $filter['label'];
	}

	foreach ( napurelon_filter_zeitspannen() as $spanne ) {
		$texte[] = $spanne['label'];
	}

	$texte[] = 'Kategorien';
	$texte[] = 'Rezepte filtern';
	$texte[] = 'Anwenden';
	$texte[] = 'Filter zurücksetzen';
	$texte[] = 'In dieser Kategorie nicht verwendet';
	$texte[] = 'Zu dieser Auswahl gibt es keine Rezepte.';
	$texte[] = 'In dieser Kategorie gibt es noch keine Rezepte.';

	foreach ( array_unique( $texte ) as $text ) {
		pll_register_string( $text, $text, NAPURELON_SPRACHGRUPPE );
	}
}

add_action( 'init', 'napurelon_sprache_zeichenketten', 20 );
