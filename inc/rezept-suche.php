<?php
/**
 * Freitextsuche über die Rezepte.
 *
 * Shortcode [napurelon_rezept_suche] – gedacht für den Kopfbereich der mit
 * Elementor gebauten Rezepte-Seite.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_SUCHE_TREFFER', 12 );

/**
 * Bindet das JavaScript der Suche ein.
 *
 * Das Kartenstylesheet (napurelon-rezeptkarten) liefert das Raster für die
 * Trefferliste und lädt bereits im Frontend.
 */
function napurelon_enqueue_rezept_suche_assets() {
	if ( is_admin() ) {
		return;
	}

	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	$js  = '/assets/js/rezept-suche.js';

	wp_enqueue_script(
		'napurelon-rezept-suche',
		$uri . $js,
		array(),
		file_exists( $dir . $js ) ? (string) filemtime( $dir . $js ) : '1.0.0',
		true
	);

	wp_localize_script(
		'napurelon-rezept-suche',
		'napurelonRezeptSuche',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'napurelon_rezept_suche' ),
			'i18n'    => array(
				'keineTreffer' => 'Keine Rezepte gefunden.',
				'suchtGerade'  => 'Suche läuft …',
			),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'napurelon_enqueue_rezept_suche_assets' );

/**
 * Sucht veröffentlichte Rezepte und gibt die Karten als HTML zurück.
 *
 * @param string $begriff Suchbegriff.
 * @return string HTML der Trefferliste oder leerer String ohne Treffer.
 */
function napurelon_rezept_suchergebnis( $begriff ) {
	$abfrage = new WP_Query(
		array(
			'post_type'           => 'rezepte',
			'post_status'         => 'publish',
			's'                   => $begriff,
			'posts_per_page'      => NAPURELON_SUCHE_TREFFER,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( ! $abfrage->have_posts() ) {
		return '';
	}

	$karten = '';

	foreach ( $abfrage->posts as $beitrag ) {
		$karten .= napurelon_rezeptkarte( $beitrag->ID );
	}

	return '<ul class="npo-rezeptkarten">' . $karten . '</ul>';
}

/**
 * Liefert die Trefferliste für die Eingabe im Suchfeld.
 */
function napurelon_ajax_rezept_suche() {
	check_ajax_referer( 'napurelon_rezept_suche', 'nonce' );

	$begriff = isset( $_POST['begriff'] ) ? sanitize_text_field( wp_unslash( $_POST['begriff'] ) ) : '';
	$begriff = trim( mb_substr( $begriff, 0, 80 ) );

	if ( mb_strlen( $begriff ) < 2 ) {
		wp_send_json_success( array( 'html' => '' ) );
	}

	wp_send_json_success( array( 'html' => napurelon_rezept_suchergebnis( $begriff ) ) );
}

add_action( 'wp_ajax_napurelon_rezept_suche', 'napurelon_ajax_rezept_suche' );
add_action( 'wp_ajax_nopriv_napurelon_rezept_suche', 'napurelon_ajax_rezept_suche' );

/**
 * Shortcode: Suchfeld mit Trefferliste.
 *
 * Ohne JavaScript sendet das Formular an die Seite selbst; der Parameter
 * rezept_suche wird dann serverseitig ausgewertet.
 *
 * @return string HTML der Suche.
 */
function napurelon_rezept_suche_shortcode() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lesende Suche ohne Statusaenderung.
	$begriff = isset( $_GET['rezept_suche'] ) ? sanitize_text_field( wp_unslash( $_GET['rezept_suche'] ) ) : '';
	$begriff = trim( mb_substr( $begriff, 0, 80 ) );
	$treffer = ( mb_strlen( $begriff ) >= 2 ) ? napurelon_rezept_suchergebnis( $begriff ) : '';

	ob_start();
	?>
	<div class="npo-rezeptsuche">
		<form class="npo-rezeptsuche__form" role="search" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<label class="screen-reader-text" for="npo-rezeptsuche-feld">Rezept suchen</label>
			<input
				type="search"
				id="npo-rezeptsuche-feld"
				class="npo-rezeptsuche__feld"
				name="rezept_suche"
				value="<?php echo esc_attr( $begriff ); ?>"
				placeholder="Rezept suchen…"
				autocomplete="off"
			>
			<button type="submit" class="npo-rezeptsuche__button">Suchen</button>
		</form>

		<p class="npo-rezeptsuche__status" role="status" aria-live="polite"></p>

		<div class="npo-rezeptsuche__treffer" data-napurelon-suchtreffer>
			<?php
			if ( '' !== $treffer ) {
				echo $treffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karten sind bereits escaped.
			} elseif ( mb_strlen( $begriff ) >= 2 ) {
				echo '<p class="npo-rezeptsuche__leer">Keine Rezepte gefunden.</p>';
			}
			?>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_rezept_suche', 'napurelon_rezept_suche_shortcode' );
