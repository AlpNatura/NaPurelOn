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
	$ids = napurelon_rezept_such_ids( $begriff );

	if ( empty( $ids ) ) {
		return '';
	}

	$karten = '';

	foreach ( $ids as $id ) {
		$karten .= napurelon_rezeptkarte( $id );
	}

	return '<ul class="npo-rezeptkarten">' . $karten . '</ul>';
}

/**
 * Sammelt die IDs der Treffer aus Rezepttexten und aus dem Zutatenfeld.
 *
 * Zuerst die Volltexttreffer (Titel, Einleitung, Zubereitung), danach die
 * Rezepte, deren Zutatenliste den Begriff enthält.
 *
 * @param string $begriff Suchbegriff.
 * @return int[] Beitrags-IDs, höchstens NAPURELON_SUCHE_TREFFER Stück.
 */
function napurelon_rezept_such_ids( $begriff ) {
	$grund = array(
		'post_type'           => 'rezepte',
		'post_status'         => 'publish',
		'posts_per_page'      => NAPURELON_SUCHE_TREFFER,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
	);

	$volltext = new WP_Query( array_merge( $grund, array( 's' => $begriff ) ) );

	$zutaten = new WP_Query(
		array_merge(
			$grund,
			array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query -- Suche über die Zutatenliste.
					array(
						'key'     => 'napurelon_zutaten',
						'value'   => $begriff,
						'compare' => 'LIKE',
					),
				),
			)
		)
	);

	$ids = array_unique( array_merge( $volltext->posts, $zutaten->posts ) );

	return array_slice( array_map( 'absint', $ids ), 0, NAPURELON_SUCHE_TREFFER );
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
	$begriff = napurelon_rezept_suchbegriff();
	$eigen   = napurelon_rezept_treffer_eigenstaendig();

	ob_start();
	?>
	<div class="npo-rezeptsuche">
		<form class="npo-rezeptsuche__form" role="search" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<label class="screen-reader-text" for="npo-rezeptsuche-feld">Rezepte und Zutaten durchsuchen</label>
			<input
				type="search"
				id="npo-rezeptsuche-feld"
				class="npo-rezeptsuche__feld"
				name="rezept_suche"
				value="<?php echo esc_attr( $begriff ); ?>"
				placeholder="Rezepte, Zutaten, …"
				autocomplete="off"
			>
			<button type="submit" class="npo-rezeptsuche__button">Suchen</button>
		</form>

		<p class="npo-rezeptsuche__status" role="status" aria-live="polite"></p>

		<?php if ( ! $eigen ) : ?>
			<?php echo napurelon_rezept_trefferbereich( $begriff ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karten sind bereits escaped. ?>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_rezept_suche', 'napurelon_rezept_suche_shortcode' );

/**
 * Shortcode: nur die Trefferliste, gedacht für den Inhaltsbereich der Seite.
 *
 * So kann das Suchfeld im Kopfbereich stehen, während die Treffer weiter
 * unten in einem eigenen Abschnitt erscheinen.
 *
 * @return string HTML des Trefferbereichs.
 */
function napurelon_rezept_treffer_shortcode() {
	return napurelon_rezept_trefferbereich( napurelon_rezept_suchbegriff() );
}

add_shortcode( 'napurelon_rezept_treffer', 'napurelon_rezept_treffer_shortcode' );

/**
 * Liefert den Behälter der Trefferliste inklusive serverseitiger Treffer.
 *
 * @param string $begriff Geprüfter Suchbegriff.
 * @return string HTML des Behälters.
 */
function napurelon_rezept_trefferbereich( $begriff ) {
	$treffer = ( mb_strlen( $begriff ) >= 2 ) ? napurelon_rezept_suchergebnis( $begriff ) : '';

	ob_start();
	?>
	<div class="npo-rezeptsuche__treffer" data-napurelon-suchtreffer>
		<?php
		if ( '' !== $treffer ) {
			echo $treffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karten sind bereits escaped.
		} elseif ( mb_strlen( $begriff ) >= 2 ) {
			echo '<p class="npo-rezeptsuche__leer">Keine Rezepte gefunden.</p>';
		}
		?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Liest den Suchbegriff aus der Adresszeile.
 *
 * @return string Suchbegriff, auf 80 Zeichen begrenzt.
 */
function napurelon_rezept_suchbegriff() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lesende Suche ohne Statusaenderung.
	$begriff = isset( $_GET['rezept_suche'] ) ? sanitize_text_field( wp_unslash( $_GET['rezept_suche'] ) ) : '';

	return trim( mb_substr( $begriff, 0, 80 ) );
}

/**
 * Steht die Trefferliste als eigener Shortcode auf der Seite?
 *
 * @return bool True, wenn [napurelon_rezept_treffer] im Seiteninhalt steht.
 */
function napurelon_rezept_treffer_eigenstaendig() {
	$inhalt = get_post_field( 'post_content', get_the_ID() );

	if ( is_string( $inhalt ) && has_shortcode( $inhalt, 'napurelon_rezept_treffer' ) ) {
		return true;
	}

	$elementor = get_post_meta( get_the_ID(), '_elementor_data', true );

	return is_string( $elementor ) && false !== strpos( $elementor, 'napurelon_rezept_treffer' );
}
