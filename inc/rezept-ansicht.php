<?php
/**
 * Hilfsfunktionen und Interaktionen für die Rezept-Ansicht im Frontend.
 *
 * Enthält die Icons der Aktionsleiste (Likes, Merken, Drucken, Teilen),
 * die Aufbereitung der Zutatenliste sowie den AJAX-Endpunkt für die Likes.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta-Schlüssel, unter dem die Anzahl der Likes gespeichert wird.
 */
define( 'NAPURELON_LIKES_META_KEY', 'napurelon_likes' );

/**
 * Lädt CSS und JavaScript nur auf Rezeptseiten.
 */
function napurelon_enqueue_rezept_assets() {
	if ( ! is_singular( 'rezepte' ) ) {
		return;
	}

	$dir     = get_stylesheet_directory();
	$uri     = get_stylesheet_directory_uri();
	$css     = '/assets/css/rezept.css';
	$js      = '/assets/js/rezept.js';
	$css_ver = file_exists( $dir . $css ) ? (string) filemtime( $dir . $css ) : '1.0.0';
	$js_ver  = file_exists( $dir . $js ) ? (string) filemtime( $dir . $js ) : '1.0.0';

	wp_enqueue_style( 'napurelon-rezept', $uri . $css, array( 'napurelon' ), $css_ver );
	wp_enqueue_script( 'napurelon-rezept', $uri . $js, array(), $js_ver, true );

	wp_localize_script(
		'napurelon-rezept',
		'napurelonRezept',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'napurelon_rezept_like' ),
			'postId'  => get_queried_object_id(),
			'i18n'    => array(
				'saved'    => 'Gemerkt',
				'remember' => 'Merken',
				'copied'   => 'Link kopiert',
			),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'napurelon_enqueue_rezept_assets' );

/**
 * Sperrfrist zwischen zwei Likes desselben Besuchers für dasselbe Rezept.
 */
define( 'NAPURELON_LIKE_SPERRE', 12 * HOUR_IN_SECONDS );

/**
 * Bildet einen Besucher auf eine anonyme Kennung ab.
 *
 * Es wird nur ein Hash gespeichert, keine IP-Adresse im Klartext.
 *
 * @param int $post_id Beitrags-ID.
 * @return string Transient-Schlüssel.
 */
function napurelon_like_sperrschluessel( $post_id ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return 'napurelon_like_' . md5( $post_id . '|' . $ip . '|' . wp_salt( 'nonce' ) );
}

/**
 * Erhöht den Like-Zähler eines Rezepts.
 *
 * Der Endpunkt ist bewusst auch für Gäste erreichbar. Serverseitig wird pro
 * Besucher und Rezept nur eine Stimme innerhalb der Sperrfrist gezählt, damit
 * der Zähler nicht durch wiederholte Aufrufe manipuliert werden kann.
 */
function napurelon_ajax_rezept_like() {
	check_ajax_referer( 'napurelon_rezept_like', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;

	if ( ! $post instanceof WP_Post || 'rezepte' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_send_json_error( array( 'message' => 'Unbekanntes Rezept.' ), 400 );
	}

	$sperre = napurelon_like_sperrschluessel( $post_id );

	if ( get_transient( $sperre ) ) {
		wp_send_json_success(
			array(
				'likes'    => (int) get_post_meta( $post_id, NAPURELON_LIKES_META_KEY, true ),
				'gezaehlt' => false,
			)
		);
	}

	set_transient( $sperre, 1, NAPURELON_LIKE_SPERRE );

	$likes = (int) get_post_meta( $post_id, NAPURELON_LIKES_META_KEY, true ) + 1;
	update_post_meta( $post_id, NAPURELON_LIKES_META_KEY, $likes );

	wp_send_json_success(
		array(
			'likes'    => $likes,
			'gezaehlt' => true,
		)
	);
}

add_action( 'wp_ajax_napurelon_rezept_like', 'napurelon_ajax_rezept_like' );
add_action( 'wp_ajax_nopriv_napurelon_rezept_like', 'napurelon_ajax_rezept_like' );

/**
 * Registriert den Like-Zähler als Post Meta.
 */
function napurelon_register_rezept_like_meta() {
	register_post_meta(
		'rezepte',
		NAPURELON_LIKES_META_KEY,
		array(
			'type'          => 'integer',
			'single'        => true,
			'default'       => 0,
			'description'   => 'Anzahl der Likes',
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

add_action( 'init', 'napurelon_register_rezept_like_meta' );

/**
 * Liefert ein Inline-SVG-Icon für die Aktionsleiste.
 *
 * @param string $name herz | merken | drucken | teilen | zeit.
 * @return string SVG-Markup oder leerer String.
 */
function napurelon_rezept_icon( $name ) {
	$attrs = 'class="napurelon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';

	$icons = array(
		// Herz für die Likes.
		'herz'    => '<path d="M20.8 8.6c0 4.7-8.8 10.2-8.8 10.2S3.2 13.3 3.2 8.6a4.4 4.4 0 0 1 8.8-1 4.4 4.4 0 0 1 8.8 1z"/>',
		// Kleines Heft zum Merken.
		'merken'  => '<path d="M6.5 3.5h11a1 1 0 0 1 1 1v16l-6.5-3-6.5 3v-16a1 1 0 0 1 1-1z"/>',
		// Drucker.
		'drucken' => '<path d="M7 9V3.5h10V9"/><path d="M7 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6.5H7z"/>',
		// Geschwungener Pfeil zum Teilen.
		'teilen'  => '<path d="M14 5l6 5-6 5v-3.2C9.6 11.8 6.5 13 4.5 16.5 4.7 11 8 7.8 14 7.4z"/>',
		// Uhr für die Zeitangaben.
		'zeit'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		// Info für Menge, Haltbarkeit usw.
		'info'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5"/><path d="M12 7.8h.01"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg ' . $attrs . '>' . $icons[ $name ] . '</svg>';
}

/**
 * Zerlegt das Zutatenfeld in Gruppen.
 *
 * Zeilen, die mit ":" enden, gelten als Zwischenüberschrift ("Teig:"),
 * alle folgenden Zeilen als Zutaten dieser Gruppe.
 *
 * @param string $raw Inhalt des Feldes "Zutaten".
 * @return array<int, array{title: string, items: array<int, string>}>
 */
function napurelon_parse_zutaten( $raw ) {
	$groups  = array();
	$current = array(
		'title' => '',
		'items' => array(),
	);

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		if ( ':' === substr( $line, -1 ) ) {
			if ( ! empty( $current['items'] ) || '' !== $current['title'] ) {
				$groups[] = $current;
			}

			$current = array(
				'title' => rtrim( $line, ':' ),
				'items' => array(),
			);
			continue;
		}

		$current['items'][] = $line;
	}

	if ( ! empty( $current['items'] ) || '' !== $current['title'] ) {
		$groups[] = $current;
	}

	return $groups;
}

/**
 * Gibt einen Meta-Wert eines Rezepts zurück.
 *
 * @param int    $post_id Beitrags-ID.
 * @param string $key     Meta-Schlüssel.
 * @return string
 */
function napurelon_rezept_meta( $post_id, $key ) {
	return trim( (string) get_post_meta( $post_id, $key, true ) );
}

/**
 * Wandelt Minuten in eine lesbare Angabe um ("1 Std. 15 Min.").
 *
 * @param int $minutes Minuten.
 * @return string
 */
function napurelon_format_minuten( $minutes ) {
	$minutes = absint( $minutes );

	if ( ! $minutes ) {
		return '';
	}

	$hours = (int) floor( $minutes / 60 );
	$rest  = $minutes % 60;

	if ( $hours && $rest ) {
		return sprintf( '%d Std. %d Min.', $hours, $rest );
	}

	if ( $hours ) {
		return sprintf( '%d Std.', $hours );
	}

	return sprintf( '%d Min.', $rest );
}
