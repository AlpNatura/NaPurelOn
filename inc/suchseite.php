<?php
/**
 * Suchergebnisseite (search.php): oben Rezepte als Karten, darunter Seiten & Beiträge.
 *
 * Die Hauptabfrage der Suche liefert nur Seiten und Beiträge (mit Seitenzahlen),
 * die Rezepte kommen aus napurelon_rezept_such_ids() inkl. Zutaten, Kategorien
 * und Tags.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_SUCHSEITE_REZEPTE', 24 );
define( 'NAPURELON_SUCHSEITE_SEITEN', 10 );

/**
 * Beschränkt die Hauptsuche auf Seiten und Beiträge.
 *
 * @param WP_Query $query Abfrage.
 */
function napurelon_suchseite_hauptabfrage( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$typ = $query->get( 'post_type' );
	if ( ! empty( $typ ) && 'any' !== $typ ) {
		return;
	}

	$query->set( 'post_type', array( 'page', 'post' ) );
	$query->set( 'posts_per_page', NAPURELON_SUCHSEITE_SEITEN );
}
add_action( 'pre_get_posts', 'napurelon_suchseite_hauptabfrage' );

/**
 * Suchseite ohne Seitenleiste.
 *
 * @param string $layout Astra-Layout.
 * @return string
 */
function napurelon_suchseite_layout( $layout ) {
	return is_search() ? 'no-sidebar' : $layout;
}
add_filter( 'astra_page_layout', 'napurelon_suchseite_layout' );

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_search() ) {
			return;
		}

		$css  = '/assets/css/suche.css';
		$pfad = get_stylesheet_directory() . $css;

		wp_enqueue_style(
			'napurelon-suche',
			get_stylesheet_directory_uri() . $css,
			array( 'napurelon', 'napurelon-rezeptkarten' ),
			file_exists( $pfad ) ? (string) filemtime( $pfad ) : '1.0.0'
		);
	}
);

/**
 * Bereinigter Suchbegriff der aktuellen Anfrage.
 *
 * @return string
 */
function napurelon_suchseite_begriff() {
	return trim( mb_substr( sanitize_text_field( get_search_query( false ) ), 0, 80 ) );
}

/**
 * Suchformular der Ergebnisseite.
 *
 * @param string $begriff Aktueller Suchbegriff.
 * @return string
 */
function napurelon_suchseite_formular( $begriff ) {
	ob_start();
	?>
	<form class="npo-suche__formular" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="npo-suche-feld"><?php echo esc_html( napurelon_text( 'Suchen nach:' ) ); ?></label>
		<input id="npo-suche-feld" class="npo-suche__feld" type="search" name="s" value="<?php echo esc_attr( $begriff ); ?>" placeholder="<?php echo esc_attr( napurelon_text( 'Suchen …' ) ); ?>" maxlength="80" enterkeyhint="search">
		<button class="npo-suche__senden" type="submit"><?php echo esc_html( napurelon_text( 'Suchen' ) ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}
