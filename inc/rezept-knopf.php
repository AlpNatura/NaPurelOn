<?php
/**
 * Header-Knöpfe für Astra-HTML-Elemente: „Rezept hinzufügen“ [napurelon_rezept_knopf]
 * und Profilsymbol [napurelon_konto_knopf] (Astra free bietet „Konto“ mobil nicht an).
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

/**
 * Profilsymbol, das zu „Mein Konto“ führt. Angemeldete sehen beim Überfahren
 * zusätzlich das Astra-Menü „Konto-Menü“ (in Astra free sonst nur in Pro).
 *
 * @return string
 */
function napurelon_konto_knopf_shortcode() {
	if ( ! function_exists( 'napurelon_konto_seite_url' ) ) {
		return '';
	}

	$konto = napurelon_konto_seite_url();
	if ( '' === $konto ) {
		return '';
	}

	$text  = napurelon_text( is_user_logged_in() ? 'Mein Konto' : 'Anmelden' );
	$aktiv = function_exists( 'is_account_page' ) && is_account_page();

	$menue = '';
	if ( is_user_logged_in() && has_nav_menu( 'loggedin_account_menu' ) ) {
		$menue = (string) wp_nav_menu(
			array(
				'theme_location' => 'loggedin_account_menu',
				'container'      => false,
				'menu_class'     => 'npo-kontomenue__liste',
				'depth'          => 1,
				'fallback_cb'    => false,
				'echo'           => false,
			)
		);
	}

	ob_start();
	?>
	<div class="npo-kontomenue<?php echo '' !== $menue ? ' npo-kontomenue--mit-menue' : ''; ?>">
		<a class="npo-kontoknopf" href="<?php echo esc_url( $konto ); ?>" title="<?php echo esc_attr( $text ); ?>" aria-label="<?php echo esc_attr( $text ); ?>"<?php echo $aktiv ? ' aria-current="page"' : ''; ?>>
			<svg class="npo-kontoknopf__symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		</a>
		<?php if ( '' !== $menue ) : ?>
			<nav class="npo-kontomenue__panel" aria-label="<?php echo esc_attr( $text ); ?>">
				<?php echo $menue; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe von wp_nav_menu(). ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'napurelon_konto_knopf', 'napurelon_konto_knopf_shortcode' );

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
