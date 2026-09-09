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

/**
 * Registriert CSS und JavaScript der Kartenliste.
 *
 * Eingebunden wird erst beim Rendern des Shortcodes, damit andere Seiten
 * die Dateien nicht laden.
 */
function napurelon_register_rezeptkarten_assets() {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	$css = '/assets/css/rezeptkarten.css';
	$js  = '/assets/js/rezeptkarten.js';

	wp_register_style(
		'napurelon-rezeptkarten',
		$uri . $css,
		array( 'napurelon' ),
		file_exists( $dir . $css ) ? (string) filemtime( $dir . $css ) : '1.0.0'
	);

	wp_register_script(
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

	// Steht der Shortcode im Seiteninhalt, laden die Dateien regulär im Kopf.
	$beitrag = get_post();

	if ( $beitrag instanceof WP_Post && has_shortcode( $beitrag->post_content, 'napurelon_neue_rezepte' ) ) {
		wp_enqueue_style( 'napurelon-rezeptkarten' );
		wp_enqueue_script( 'napurelon-rezeptkarten' );
	}
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
 * @param array $atts anzahl: Anzahl der Karten (1–12, Vorgabe 3).
 * @return string HTML der Liste.
 */
function napurelon_neue_rezepte_shortcode( $atts ) {
	$atts   = shortcode_atts( array( 'anzahl' => 3 ), $atts, 'napurelon_neue_rezepte' );
	$anzahl = min( 12, max( 1, absint( $atts['anzahl'] ) ) );

	$abfrage = new WP_Query(
		array(
			'post_type'           => 'rezepte',
			'post_status'         => 'publish',
			'posts_per_page'      => $anzahl,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( ! $abfrage->have_posts() ) {
		return '';
	}

	wp_enqueue_style( 'napurelon-rezeptkarten' );
	wp_enqueue_script( 'napurelon-rezeptkarten' );

	$karten = '';

	foreach ( $abfrage->posts as $beitrag ) {
		$karten .= napurelon_rezeptkarte( $beitrag->ID );
	}

	return '<ul class="npo-rezeptkarten">' . $karten . '</ul>';
}

add_shortcode( 'napurelon_neue_rezepte', 'napurelon_neue_rezepte_shortcode' );
