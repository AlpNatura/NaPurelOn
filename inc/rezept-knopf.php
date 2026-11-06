<?php
/**
 * Knopf „Rezept hinzufügen“ [napurelon_rezept_knopf], z. B. im Astra-Header (HTML-Element).
 *
 * Angemeldete gehen direkt zum Einreichungsformular, alle anderen zuerst zur
 * Anmeldung und danach zurück zum Formular.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ausgabe des Knopfs.
 *
 * @return string
 */
function napurelon_rezept_knopf_shortcode() {
	if ( ! function_exists( 'napurelon_konto_formular_url' ) ) {
		return '';
	}

	$formular = napurelon_konto_formular_url();
	if ( '' === $formular ) {
		return '';
	}

	$ziel    = is_user_logged_in() ? $formular : napurelon_konto_anmelde_url( $formular );
	$aktiv   = is_page() && (int) get_queried_object_id() === (int) url_to_postid( $formular );
	$text    = napurelon_text( 'Rezept hinzufügen' );
	$klassen = 'npo-rezeptknopf' . ( $aktiv ? ' npo-rezeptknopf--aktiv' : '' );

	ob_start();
	?>
	<a class="<?php echo esc_attr( $klassen ); ?>" href="<?php echo esc_url( $ziel ); ?>" title="<?php echo esc_attr( $text ); ?>"<?php echo $aktiv ? ' aria-current="page"' : ''; ?>>
		<svg class="npo-rezeptknopf__symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
		<span class="npo-rezeptknopf__text"><?php echo esc_html( $text ); ?></span>
	</a>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'napurelon_rezept_knopf', 'napurelon_rezept_knopf_shortcode' );

add_action(
	'wp_enqueue_scripts',
	function () {
		$css  = '/assets/css/rezept-knopf.css';
		$pfad = get_stylesheet_directory() . $css;

		wp_enqueue_style(
			'napurelon-rezept-knopf',
			get_stylesheet_directory_uri() . $css,
			array( 'napurelon' ),
			file_exists( $pfad ) ? (string) filemtime( $pfad ) : '1.0.0'
		);
	}
);
