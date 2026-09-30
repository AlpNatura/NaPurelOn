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
	$texte[] = 'Mehr anzeigen';
	$texte   = array_merge(
		$texte,
		array(
			'Grunddaten',
			'Zutaten & Zubereitung',
			'Details',
			'Gut zu wissen',
			'Merkmale',
			'Tags',
			'Bilder',
			'Bestätigung',
			'Rezept einreichen',
			'Rezepttitel',
			'Zubereitungszeit (Minuten)',
			'Kochzeit (Minuten)',
			'Eine Zutat pro Zeile. Eine Zeile, die mit „:“ endet, wird zur Zwischenüberschrift (z. B. „Teig:“). Zutaten sind erforderlich, außer in „Grundlagen & Wissen“.',
			'Pflichtfeld',
			'Wird gesendet…',
			'Dein Rezept wird vor der Veröffentlichung geprüft.',
			'Bitte melde dich an, um ein Rezept einzureichen.',
			'Anmelden',
			'Registrieren',
			'Weiteres Rezept einreichen',
			'Danke! Dein Rezept wurde eingereicht und wird vor der Veröffentlichung geprüft.',
			'Hauptkategorie',
			'Unterkategorie',
			'Hauptbild',
			'Galeriebilder',
			'Deine Website',
			'— keine —',
			'Bitte wählen',
			'Zubereitung',
			'Eine Zutat pro Zeile. Eine Zeile, die mit „:“ endet, wird zur Zwischenüberschrift (z. B. „Teig:“).',
			'JPG, PNG oder WebP, höchstens 5 MB pro Bild, bis zu 5 Galeriebilder.',
			'Ich habe die Fotos selbst aufgenommen oder besitze die Rechte, sie hier veröffentlichen zu lassen.',
			'Ich habe die ',
			'Datenschutzerklärung',
			' gelesen und bin mit der Verarbeitung meiner Angaben einverstanden.',
			'Bitte bestätige die Bildrechte.',
			'Bitte bestätige die Datenschutzerklärung.',
			'Bitte gib einen Rezepttitel ein.',
			'Der Titel darf höchstens 120 Zeichen lang sein.',
			'Bitte wähle eine gültige Hauptkategorie.',
			'Bitte wähle eine gültige Unterkategorie.',
			'Bitte beschreibe die Zubereitung.',
			'Die Zubereitung darf höchstens 20.000 Zeichen lang sein.',
			'Bitte gib die Zutaten an.',
			'Dieses Feld darf höchstens 200 Zeichen enthalten.',
			'Dieses Feld darf höchstens 5.000 Zeichen enthalten.',
			'Bitte gib eine gültige HTTP- oder HTTPS-URL ein.',
			'Bitte wähle gültige Merkmale für diese Kategorie.',
			'Bitte wähle gültige Rezept-Tags.',
			'Bitte lade ein Hauptbild hoch.',
			'Bitte lade höchstens 5 Galeriebilder hoch.',
			'Die Dateien müssen JPG, PNG oder WebP sein.',
			'Ein Bild darf höchstens 5 MB groß sein.',
			'Ein Bild konnte nicht gelesen werden.',
			'Der Datei-Upload ist fehlgeschlagen.',
			'Die Dateien sind zu groß. Bitte verkleinere die Bilder.',
			'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.',
			'Du hast in der letzten Stunde bereits drei Rezepte eingereicht. Bitte versuche es später erneut.',
			'Die Bilder konnten nicht gespeichert werden.',
			'Das Rezept konnte nicht gespeichert werden.',
			'Zutaten sind erforderlich, außer in „Grundlagen & Wissen“.',
		)
	);

	foreach ( napurelon_get_rezept_fields() as $key => $field ) {
		if ( '_napurelon_interne_notiz' !== $key ) {
			$texte[] = $field['label'];
		}
	}

	foreach ( array_unique( $texte ) as $text ) {
		pll_register_string( $text, $text, NAPURELON_SPRACHGRUPPE );
	}
}

add_action( 'init', 'napurelon_sprache_zeichenketten', 20 );
