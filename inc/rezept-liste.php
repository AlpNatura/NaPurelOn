<?php
/**
 * Kartenliste der zuletzt veröffentlichten Rezepte.
 *
 * Shortcode [napurelon_neue_rezepte] – gedacht für den Abschnitt
 * "Neu hinzugefügte Rezepte" auf der mit Elementor gebauten Rezepte-Seite.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

const NAPURELON_NEUE_JS      = '/assets/js/neuerezepte.js';
const NAPURELON_NEUE_AKTION  = 'napurelon_neue_rezepte';
const NAPURELON_NEUE_ANZAHL  = 3;
const NAPURELON_NEUE_SCHRITT = 3;

/**
 * Bindet CSS und JavaScript der Kartenliste ein.
 *
 * Der Shortcode steckt bei Elementor-Seiten in _elementor_data statt im
 * Beitragsinhalt, deshalb laedt das Stylesheet im Frontend generell mit.
 */
function napurelon_register_rezeptkarten_assets() {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	$css = '/assets/css/rezeptkarten.css';
	$js  = '/assets/js/rezeptkarten.js';

	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'napurelon-rezeptkarten',
		$uri . $css,
		array( 'napurelon' ),
		file_exists( $dir . $css ) ? (string) filemtime( $dir . $css ) : '1.0.0'
	);

	wp_enqueue_script(
		'napurelon-rezeptkarten',
		$uri . $js,
		array(),
		file_exists( $dir . $js ) ? (string) filemtime( $dir . $js ) : '1.0.0',
		true
	);

	wp_localize_script(
		'napurelon-rezeptkarten',
		'napurelonRezeptkarten',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'napurelon_rezept_like' ),
		)
	);

	wp_enqueue_script(
		'napurelon-neue-rezepte',
		$uri . NAPURELON_NEUE_JS,
		array(),
		file_exists( $dir . NAPURELON_NEUE_JS ) ? (string) filemtime( $dir . NAPURELON_NEUE_JS ) : '1.0.0',
		true
	);

	wp_localize_script(
		'napurelon-neue-rezepte',
		'napurelonNeueRezepte',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( NAPURELON_NEUE_AKTION ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'napurelon_register_rezeptkarten_assets' );

/**
 * Baut die Karte eines Rezepts.
 *
 * @param int $post_id Beitrags-ID.
 * @return string HTML der Karte.
 */
function napurelon_rezeptkarte( $post_id ) {
	$kategorien = get_the_terms( $post_id, 'rezeptkategorie' );
	$kategorie  = ( is_array( $kategorien ) && isset( $kategorien[0] ) ) ? $kategorien[0]->name : '';
	$untertitel = function_exists( 'napurelon_rezept_meta' ) ? napurelon_rezept_meta( $post_id, 'napurelon_untertitel' ) : '';
	$likes      = (int) get_post_meta( $post_id, NAPURELON_LIKES_META_KEY, true );
	$bild       = get_the_post_thumbnail(
		$post_id,
		'medium_large',
		array(
			'class'    => 'npo-rezeptkarte__bild',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'alt'      => '',
		)
	);

	ob_start();
	?>
	<li class="npo-rezeptkarte">
		<div class="npo-rezeptkarte__medien">
			<?php if ( $bild ) : ?>
				<?php echo $bild; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_post_thumbnail() liefert sicheres Markup. ?>
			<?php else : ?>
				<span class="npo-rezeptkarte__platzhalter" aria-hidden="true"></span>
			<?php endif; ?>
		</div>

		<div class="npo-rezeptkarte__inhalt">
			<?php if ( '' !== $kategorie ) : ?>
				<span class="npo-rezeptkarte__kategorie"><?php echo esc_html( $kategorie ); ?></span>
			<?php endif; ?>

			<h3 class="npo-rezeptkarte__titel">
				<a class="npo-rezeptkarte__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php echo esc_html( get_the_title( $post_id ) ); ?>
				</a>
			</h3>

			<?php if ( '' !== $untertitel ) : ?>
				<p class="npo-rezeptkarte__untertitel"><?php echo esc_html( $untertitel ); ?></p>
			<?php endif; ?>

			<button
				type="button"
				class="npo-rezeptkarte__like"
				data-napurelon-karte-like
				data-post-id="<?php echo esc_attr( $post_id ); ?>"
				aria-label="<?php echo esc_attr( 'Rezept „' . get_the_title( $post_id ) . '“ liken' ); ?>"
			>
				<?php echo napurelon_rezept_icon( 'herz' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Statisches Inline-SVG. ?>
				<span data-napurelon-karte-like-count><?php echo esc_html( (string) $likes ); ?></span>
			</button>
		</div>
	</li>
	<?php

	return (string) ob_get_clean();
}

/**
 * Shortcode: zeigt die zuletzt veröffentlichten Rezepte als Karten.
 *
 * @param array $atts anzahl: Anzahl der Karten (1–12, Vorgabe 3),
 *                    schritt: wie viele Karten "Mehr anzeigen" nachlädt.
 * @return string HTML der Liste.
 */
function napurelon_neue_rezepte_shortcode( $atts ) {
	$atts    = shortcode_atts(
		array(
			'anzahl'  => NAPURELON_NEUE_ANZAHL,
			'schritt' => NAPURELON_NEUE_SCHRITT,
		),
		$atts,
		'napurelon_neue_rezepte'
	);
	$anzahl  = min( 12, max( 1, absint( $atts['anzahl'] ) ) );
	$schritt = min( 12, max( 1, absint( $atts['schritt'] ) ) );

	$abfrage = new WP_Query( napurelon_neue_rezepte_abfrage( $anzahl, 0 ) );

	if ( ! $abfrage->have_posts() ) {
		wp_reset_postdata();

		return '';
	}

	$karten = '';

	foreach ( $abfrage->posts as $beitrag ) {
		$karten .= napurelon_rezeptkarte( $beitrag->ID );
	}

	$gesamt  = (int) $abfrage->found_posts;
	$geladen = count( $abfrage->posts );

	wp_reset_postdata();

	ob_start();
	?>
	<section
		class="npo-neuerezepte"
		data-npo-neuerezepte="1"
		data-schritt="<?php echo esc_attr( (string) $schritt ); ?>"
		data-geladen="<?php echo esc_attr( (string) $geladen ); ?>"
		data-gesamt="<?php echo esc_attr( (string) $gesamt ); ?>"
	>
		<ul class="npo-rezeptkarten npo-neuerezepte__raster">
			<?php echo $karten; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karten escapen selbst. ?>
		</ul>

		<?php if ( $geladen < $gesamt ) : ?>
			<div class="npo-neuerezepte__mehr">
				<button class="npo-neuerezepte__mehr-knopf" type="button" data-npo-mehr-neue>
					<?php echo esc_html( function_exists( 'napurelon_text' ) ? napurelon_text( 'Mehr anzeigen' ) : 'Mehr anzeigen' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</section>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_neue_rezepte', 'napurelon_neue_rezepte_shortcode' );

/**
 * Abfrageargumente für die zuletzt veröffentlichten Rezepte.
 *
 * @param int $anzahl  Anzahl der Karten.
 * @param int $versatz Bereits geladene Karten.
 * @return array Argumente für WP_Query.
 */
function napurelon_neue_rezepte_abfrage( $anzahl, $versatz ) {
	return array(
		'post_type'           => 'rezepte',
		'post_status'         => 'publish',
		'posts_per_page'      => $anzahl,
		'offset'              => $versatz,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	);
}

/**
 * Ajax: liefert die nächsten Karten der zuletzt veröffentlichten Rezepte.
 *
 * Nur Lesezugriff auf veröffentlichte Rezepte; Versatz und Anzahl werden
 * gegen feste Grenzen geprüft.
 */
function napurelon_neue_rezepte_ajax() {
	check_ajax_referer( NAPURELON_NEUE_AKTION, 'nonce' );

	$versatz = isset( $_POST['versatz'] ) ? absint( wp_unslash( $_POST['versatz'] ) ) : 0;
	$anzahl  = isset( $_POST['anzahl'] ) ? absint( wp_unslash( $_POST['anzahl'] ) ) : NAPURELON_NEUE_SCHRITT;
	$anzahl  = min( 12, max( 1, $anzahl ) );

	$abfrage = new WP_Query( napurelon_neue_rezepte_abfrage( $anzahl, $versatz ) );
	$karten  = '';

	foreach ( $abfrage->posts as $beitrag ) {
		$karten .= napurelon_rezeptkarte( $beitrag->ID );
	}

	wp_reset_postdata();

	// found_posts zählt ohne LIMIT, der Versatz ist darin also enthalten.
	$gesamt  = (int) $abfrage->found_posts;
	$geladen = $versatz + count( $abfrage->posts );

	wp_send_json_success(
		array(
			'karten'  => $karten,
			'geladen' => $geladen,
			'gesamt'  => $gesamt,
			'fertig'  => $geladen >= $gesamt,
		)
	);
}

add_action( 'wp_ajax_' . NAPURELON_NEUE_AKTION, 'napurelon_neue_rezepte_ajax' );
add_action( 'wp_ajax_nopriv_' . NAPURELON_NEUE_AKTION, 'napurelon_neue_rezepte_ajax' );
