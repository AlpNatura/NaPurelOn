<?php
/**
 * Schwebender Kategoriewechsler am rechten Rand.
 *
 * Auf jeder Kategorieseite sitzt oberhalb des Warenkorbs ein runder Knopf,
 * der die sechs Hauptkategorien als Verweise aufklappt. Als Shortcode
 * [napurelon_kategorie_menue] laesst er sich zusaetzlich frei platzieren.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bindet Stil und Skript des Kategoriewechslers ein.
 */
function napurelon_katmenue_assets() {
	if ( is_admin() ) {
		return;
	}

	$dir = get_stylesheet_directory();
	$css = '/assets/css/kategoriemenue.css';
	$js  = '/assets/js/kategoriemenue.js';

	if ( file_exists( $dir . $css ) ) {
		wp_enqueue_style(
			'napurelon-kategoriemenue',
			get_stylesheet_directory_uri() . $css,
			array(),
			(string) filemtime( $dir . $css )
		);
	}

	if ( file_exists( $dir . $js ) ) {
		wp_enqueue_script(
			'napurelon-kategoriemenue',
			get_stylesheet_directory_uri() . $js,
			array(),
			(string) filemtime( $dir . $js ),
			true
		);
	}
}

add_action( 'wp_enqueue_scripts', 'napurelon_katmenue_assets' );

/**
 * Baut den Kategoriewechsler.
 *
 * @return string HTML des Wechslers oder leer.
 */
function napurelon_katmenue_html() {
	$begriffe = napurelon_kategorie_hauptkategorien();

	if ( empty( $begriffe ) ) {
		return '';
	}

	$aktiv      = napurelon_kategorie_begriff();
	$aktiv_pfad = array();

	if ( $aktiv ) {
		$aktiv_pfad   = get_ancestors( $aktiv->term_id, 'rezeptkategorie' );
		$aktiv_pfad[] = $aktiv->term_id;
	}

	$beschriftung = napurelon_text( 'Kategorien' );

	static $nummer = 0;
	++$nummer;
	$listen_id = 'npo-katmenue-liste-' . $nummer;

	ob_start();
	?>
	<div class="npo-katmenue" data-napurelon-katmenue>
		<ul class="npo-katmenue__liste" id="<?php echo esc_attr( $listen_id ); ?>" hidden>
			<?php foreach ( $begriffe as $begriff ) : ?>
				<?php $ist_aktiv = in_array( $begriff->term_id, $aktiv_pfad, true ); ?>
				<li class="npo-katmenue__eintrag">
					<a
						class="npo-katmenue__link<?php echo $ist_aktiv ? ' is-aktiv' : ''; ?>"
						href="<?php echo esc_url( (string) get_term_link( $begriff ) ); ?>"
						<?php echo $ist_aktiv ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $begriff->name ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<button
			class="npo-katmenue__knopf"
			type="button"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $listen_id ); ?>"
			aria-label="<?php echo esc_attr( $beschriftung ); ?>"
			title="<?php echo esc_attr( $beschriftung ); ?>"
		>
			<svg class="npo-katmenue__symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
			</svg>
		</button>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Shortcode: Kategoriewechsler an beliebiger Stelle.
 *
 * @return string HTML des Wechslers.
 */
function napurelon_katmenue_shortcode() {
	return napurelon_katmenue_html();
}

add_shortcode( 'napurelon_kategorie_menue', 'napurelon_katmenue_shortcode' );

/**
 * Zeigt den Wechsler auf Kategorieseiten automatisch an.
 */
function napurelon_katmenue_fussbereich() {
	if ( ! is_tax( 'rezeptkategorie' ) && ! napurelon_kategorie_ist_vorlage() ) {
		return;
	}

	echo napurelon_katmenue_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe ist im Baustein escaped.
}

add_action( 'wp_footer', 'napurelon_katmenue_fussbereich' );
