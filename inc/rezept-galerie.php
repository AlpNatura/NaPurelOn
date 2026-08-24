<?php
/**
 * Bildergalerie für Rezepte.
 *
 * Die Galerie wird als Liste von Anhang-IDs im Post Meta gespeichert und im
 * Editor über eine eigene Metabox mit der WordPress-Mediathek gepflegt.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta-Schlüssel der Galerie (kommaseparierte Anhang-IDs).
 */
define( 'NAPURELON_GALERIE_META_KEY', 'napurelon_galerie' );

/**
 * Registriert das Galeriefeld als Post Meta.
 */
function napurelon_register_rezept_galerie_meta() {
	register_post_meta(
		'rezepte',
		NAPURELON_GALERIE_META_KEY,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'description'       => 'Bildergalerie (Anhang-IDs)',
			'show_in_rest'      => true,
			'sanitize_callback' => 'napurelon_sanitize_galerie_ids',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

add_action( 'init', 'napurelon_register_rezept_galerie_meta' );

/**
 * Lässt nur positive Ganzzahlen als kommaseparierte Liste zu.
 *
 * @param mixed $value Rohwert.
 * @return string
 */
function napurelon_sanitize_galerie_ids( $value ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );

	return implode( ',', $ids );
}

/**
 * Liefert die Anhang-IDs der Galerie eines Rezepts.
 *
 * @param int $post_id Beitrags-ID.
 * @return array<int, int>
 */
function napurelon_get_galerie_ids( $post_id ) {
	$raw = (string) get_post_meta( $post_id, NAPURELON_GALERIE_META_KEY, true );

	return array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );
}

/**
 * Fügt die Metabox "Bildergalerie" hinzu.
 */
function napurelon_add_galerie_meta_box() {
	add_meta_box(
		'napurelon-rezept-galerie',
		'Bildergalerie',
		'napurelon_render_galerie_meta_box',
		'rezepte',
		'side',
		'default'
	);
}

add_action( 'add_meta_boxes', 'napurelon_add_galerie_meta_box' );

/**
 * Gibt die Metabox aus.
 *
 * @param WP_Post $post Aktueller Beitrag.
 */
function napurelon_render_galerie_meta_box( $post ) {
	wp_nonce_field( 'napurelon_save_galerie', 'napurelon_galerie_nonce' );

	$ids = napurelon_get_galerie_ids( $post->ID );

	echo '<div class="napurelon-galerie">';
	echo '<ul class="napurelon-galerie__vorschau">';

	foreach ( $ids as $id ) {
		$thumb = wp_get_attachment_image( $id, 'thumbnail' );

		if ( ! $thumb ) {
			continue;
		}

		printf(
			'<li data-id="%1$d">%2$s<button type="button" class="napurelon-galerie__entfernen" aria-label="Bild entfernen">&times;</button></li>',
			(int) $id,
			$thumb // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe von wp_get_attachment_image().
		);
	}

	echo '</ul>';

	printf(
		'<input type="hidden" id="napurelon_galerie" name="%1$s" value="%2$s" />',
		esc_attr( NAPURELON_GALERIE_META_KEY ),
		esc_attr( implode( ',', $ids ) )
	);

	echo '<p><button type="button" class="button napurelon-galerie__auswaehlen">Bilder auswählen</button></p>';
	echo '<p class="description">Zusätzliche Bilder zum Rezeptbild. Sie erscheinen als Galerie über dem Rezept.</p>';
	echo '</div>';
}

/**
 * Speichert die Galerie.
 *
 * @param int     $post_id Beitrags-ID.
 * @param WP_Post $post    Beitrag.
 */
function napurelon_save_galerie_meta( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'rezepte' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['napurelon_galerie_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['napurelon_galerie_nonce'] ) ), 'napurelon_save_galerie' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$value = isset( $_POST[ NAPURELON_GALERIE_META_KEY ] )
		? napurelon_sanitize_galerie_ids( wp_unslash( $_POST[ NAPURELON_GALERIE_META_KEY ] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitisierung in napurelon_sanitize_galerie_ids().
		: '';

	if ( '' === $value ) {
		delete_post_meta( $post_id, NAPURELON_GALERIE_META_KEY );
		return;
	}

	update_post_meta( $post_id, NAPURELON_GALERIE_META_KEY, $value );
}

add_action( 'save_post', 'napurelon_save_galerie_meta', 10, 2 );

/**
 * Lädt Mediathek und Skript für die Metabox.
 *
 * @param string $hook Aktuelle Adminseite.
 */
function napurelon_enqueue_galerie_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'rezepte' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	$dir  = get_stylesheet_directory();
	$file = '/assets/js/admin-galerie.js';

	wp_enqueue_script(
		'napurelon-galerie-admin',
		get_stylesheet_directory_uri() . $file,
		array( 'jquery' ),
		file_exists( $dir . $file ) ? (string) filemtime( $dir . $file ) : '1.0.0',
		true
	);

	wp_add_inline_style(
		'wp-admin',
		'.napurelon-galerie__vorschau{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0 0 10px;padding:0}'
		. '.napurelon-galerie__vorschau li{position:relative;width:72px}'
		. '.napurelon-galerie__vorschau img{width:72px;height:72px;object-fit:cover;display:block}'
		. '.napurelon-galerie__entfernen{position:absolute;top:-6px;right:-6px;width:20px;height:20px;line-height:18px;border:0;border-radius:50%;background:#b32d2e;color:#fff;cursor:pointer}'
	);
}

add_action( 'admin_enqueue_scripts', 'napurelon_enqueue_galerie_admin_assets' );
