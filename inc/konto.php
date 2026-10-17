<?php
/**
 * Kundenkonto über WooCommerce „Mein Konto“: Reiter „Meine Rezepte“,
 * ausblendbare Shop-Bereiche und Schutz der Registrierung.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_KONTO_ENDPUNKT', 'meine-rezepte' );
define( 'NAPURELON_KONTO_MINDESTZEIT', 3 );

/**
 * URL der Seite „Mein Konto“ in der aktuellen Sprache.
 *
 * @return string Leer, wenn WooCommerce fehlt oder keine Seite gesetzt ist.
 */
function napurelon_konto_seite_url() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return '';
	}

	$seite_id = (int) wc_get_page_id( 'myaccount' );
	if ( $seite_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$uebersetzt = (int) pll_get_post( $seite_id );
		if ( $uebersetzt > 0 ) {
			$seite_id = $uebersetzt;
		}
	}

	$url = get_permalink( $seite_id );

	return $url ? (string) $url : '';
}

/**
 * Anmeldelink, der nach dem Login zur angegebenen Seite zurückführt.
 *
 * @param string $ziel Ziel nach der Anmeldung.
 * @return string
 */
function napurelon_konto_anmelde_url( $ziel ) {
	$konto = napurelon_konto_seite_url();

	if ( '' === $konto ) {
		return wp_login_url( $ziel );
	}

	return add_query_arg( 'redirect_to', rawurlencode( $ziel ), $konto );
}

/**
 * Registrierungslink oder leer, wenn keine Registrierung möglich ist.
 *
 * @param string $ziel Ziel nach der Registrierung.
 * @return string
 */
function napurelon_konto_registrier_url( $ziel ) {
	if ( '' !== napurelon_konto_seite_url() && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) {
		return napurelon_konto_anmelde_url( $ziel ) . '#customer_login';
	}

	if ( get_option( 'users_can_register' ) ) {
		return wp_registration_url();
	}

	return '';
}

/**
 * Ob Bestellungen, Downloads und Adressen im Konto angezeigt werden.
 *
 * @return bool
 */
function napurelon_konto_shopbereiche() {
	return 'yes' === get_option( 'napurelon_konto_shopbereiche', 'no' );
}

/**
 * Endpunkte, die nur bei aktivem Shop sichtbar sind.
 *
 * @return string[]
 */
function napurelon_konto_shop_endpunkte() {
	return array( 'orders', 'view-order', 'downloads', 'edit-address', 'payment-methods', 'add-payment-method' );
}

/**
 * Schalter unter WooCommerce → Einstellungen → Konten & Datenschutz.
 *
 * @param array $einstellungen Einstellungen der Kontoseite.
 * @return array
 */
function napurelon_konto_einstellungen( $einstellungen ) {
	$einstellungen[] = array(
		'title' => 'NaPurelOn',
		'type'  => 'title',
		'id'    => 'napurelon_konto_optionen',
	);
	$einstellungen[] = array(
		'title'    => 'Shop-Bereiche',
		'desc'     => 'Bestellungen, Downloads und Adressen im Kundenkonto anzeigen',
		'id'       => 'napurelon_konto_shopbereiche',
		'default'  => 'no',
		'type'     => 'checkbox',
		'autoload' => true,
	);
	$einstellungen[] = array(
		'type' => 'sectionend',
		'id'   => 'napurelon_konto_optionen',
	);

	return $einstellungen;
}
add_filter( 'woocommerce_account_settings', 'napurelon_konto_einstellungen' );

/**
 * Registriert den Endpunkt „Meine Rezepte“ bei WooCommerce.
 *
 * @param array $variablen Query-Variablen von WooCommerce.
 * @return array
 */
function napurelon_konto_query_vars( $variablen ) {
	$variablen[ NAPURELON_KONTO_ENDPUNKT ] = NAPURELON_KONTO_ENDPUNKT;

	return $variablen;
}
add_filter( 'woocommerce_get_query_vars', 'napurelon_konto_query_vars' );

/**
 * Menü des Kundenkontos: „Meine Rezepte“ vor den Kontodetails,
 * Shop-Bereiche nur bei aktivem Shop.
 *
 * @param array $eintraege Menüeinträge.
 * @return array
 */
function napurelon_konto_menue( $eintraege ) {
	if ( ! napurelon_konto_shopbereiche() ) {
		foreach ( napurelon_konto_shop_endpunkte() as $endpunkt ) {
			unset( $eintraege[ $endpunkt ] );
		}
	}

	$neu = array();
	foreach ( $eintraege as $schluessel => $titel ) {
		if ( 'edit-account' === $schluessel || 'customer-logout' === $schluessel ) {
			$neu[ NAPURELON_KONTO_ENDPUNKT ] = napurelon_text( 'Meine Rezepte' );
		}
		$neu[ $schluessel ] = $titel;
	}
	$neu[ NAPURELON_KONTO_ENDPUNKT ] = napurelon_text( 'Meine Rezepte' );

	return $neu;
}
add_filter( 'woocommerce_account_menu_items', 'napurelon_konto_menue', 20 );

/**
 * Leitet ausgeblendete Shop-Bereiche auf die Kontoübersicht um.
 */
function napurelon_konto_shopbereiche_sperren() {
	if ( napurelon_konto_shopbereiche() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	foreach ( napurelon_konto_shop_endpunkte() as $endpunkt ) {
		if ( is_wc_endpoint_url( $endpunkt ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}
}
add_action( 'template_redirect', 'napurelon_konto_shopbereiche_sperren' );

/**
 * Titel des Endpunkts „Meine Rezepte“.
 *
 * @return string
 */
function napurelon_konto_endpunkt_titel() {
	return napurelon_text( 'Meine Rezepte' );
}
add_filter( 'woocommerce_endpoint_' . NAPURELON_KONTO_ENDPUNKT . '_title', 'napurelon_konto_endpunkt_titel' );

/**
 * Merkt sich die Seite mit dem Einreichungsformular.
 *
 * @param string $ausgabe Ausgabe des Shortcodes.
 * @param string $tag     Shortcode-Name.
 * @return string
 */
function napurelon_konto_formularseite_merken( $ausgabe, $tag ) {
	if ( 'napurelon_rezept_formular' === $tag && is_page() ) {
		$seite_id = (int) get_queried_object_id();
		if ( $seite_id > 0 && (int) get_option( 'napurelon_formular_seite' ) !== $seite_id ) {
			update_option( 'napurelon_formular_seite', $seite_id, false );
		}
	}

	return $ausgabe;
}
add_filter( 'do_shortcode_tag', 'napurelon_konto_formularseite_merken', 10, 2 );

/**
 * URL des Einreichungsformulars in der aktuellen Sprache.
 *
 * @return string
 */
function napurelon_konto_formular_url() {
	$seite_id = (int) get_option( 'napurelon_formular_seite' );
	if ( $seite_id <= 0 || 'publish' !== get_post_status( $seite_id ) ) {
		return '';
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$uebersetzt = (int) pll_get_post( $seite_id );
		if ( $uebersetzt > 0 ) {
			$seite_id = $uebersetzt;
		}
	}

	return (string) get_permalink( $seite_id );
}

/**
 * Bezeichnung eines Beitragsstatus für die Einreichenden.
 *
 * @param string $status Beitragsstatus.
 * @return string
 */
function napurelon_konto_status_text( $status ) {
	$texte = array(
		'publish' => 'Veröffentlicht',
		'pending' => 'Wird geprüft',
		'future'  => 'Geplant',
		'draft'   => 'Entwurf',
		'private' => 'Privat',
	);

	return napurelon_text( isset( $texte[ $status ] ) ? $texte[ $status ] : 'Wird geprüft' );
}

/**
 * Inhalt des Reiters „Meine Rezepte“.
 */
function napurelon_konto_meine_rezepte() {
	$abfrage = new WP_Query(
		array(
			'post_type'              => 'rezepte',
			'author'                 => get_current_user_id(),
			'post_status'            => array( 'publish', 'pending', 'future', 'draft', 'private' ),
			'posts_per_page'         => 100,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'lang'                   => '',
		)
	);

	$formular = napurelon_konto_formular_url();

	if ( ! $abfrage->have_posts() ) {
		echo '<p>' . esc_html( napurelon_text( 'Du hast noch keine Rezepte eingereicht.' ) ) . '</p>';
	} else {
		?>
		<table class="shop_table shop_table_responsive npo-konto-rezepte">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( napurelon_text( 'Rezept' ) ); ?></th>
					<th scope="col"><?php echo esc_html( napurelon_text( 'Status' ) ); ?></th>
					<th scope="col"><?php echo esc_html( napurelon_text( 'Eingereicht am' ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $abfrage->posts as $rezept ) :
					$status = get_post_status( $rezept );
					?>
					<tr>
						<td data-title="<?php echo esc_attr( napurelon_text( 'Rezept' ) ); ?>">
							<?php if ( 'publish' === $status ) : ?>
								<a href="<?php echo esc_url( get_permalink( $rezept ) ); ?>"><?php echo esc_html( get_the_title( $rezept ) ); ?></a>
							<?php else : ?>
								<?php echo esc_html( get_the_title( $rezept ) ); ?>
							<?php endif; ?>
						</td>
						<td data-title="<?php echo esc_attr( napurelon_text( 'Status' ) ); ?>">
							<span class="npo-konto-status npo-konto-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( napurelon_konto_status_text( $status ) ); ?></span>
						</td>
						<td data-title="<?php echo esc_attr( napurelon_text( 'Eingereicht am' ) ); ?>">
							<?php echo esc_html( get_the_date( '', $rezept ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	if ( '' !== $formular ) {
		echo '<p><a class="button" href="' . esc_url( $formular ) . '">' . esc_html( napurelon_text( 'Rezept einreichen' ) ) . '</a></p>';
	}
}
add_action( 'woocommerce_account_' . NAPURELON_KONTO_ENDPUNKT . '_endpoint', 'napurelon_konto_meine_rezepte' );

/**
 * Kontoübersicht ohne Shop-Hinweise, solange der Shop ausgeblendet ist.
 *
 * @param string $vorlage       Pfad der Vorlage.
 * @param string $vorlagen_name Name der Vorlage.
 * @return string
 */
function napurelon_konto_dashboard_vorlage( $vorlage, $vorlagen_name ) {
	if ( 'myaccount/dashboard.php' === $vorlagen_name && ! napurelon_konto_shopbereiche() ) {
		return get_stylesheet_directory() . '/templates/konto-dashboard.php';
	}

	return $vorlage;
}
add_filter( 'wc_get_template', 'napurelon_konto_dashboard_vorlage', 10, 2 );

/**
 * Gibt das Weiterleitungsziel an Login- und Registrierungsformular weiter.
 */
function napurelon_konto_weiterleitung_feld() {
	if ( ! isset( $_GET['redirect_to'] ) || ! is_scalar( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$ziel = wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $ziel ) {
		return;
	}

	echo '<input type="hidden" name="redirect" value="' . esc_url( $ziel ) . '">';
}
add_action( 'woocommerce_login_form_end', 'napurelon_konto_weiterleitung_feld' );
add_action( 'woocommerce_register_form_end', 'napurelon_konto_weiterleitung_feld' );

/**
 * Signatur des Zeitstempels im Registrierungsformular.
 *
 * @param int $zeit Zeitstempel.
 * @return string
 */
function napurelon_konto_zeit_signatur( $zeit ) {
	return wp_hash( $zeit . '|registrierung' );
}

/**
 * Unsichtbare Spamfalle und Zeitstempel im Registrierungsformular.
 */
function napurelon_konto_spamschutz_felder() {
	$zeit = time();
	?>
	<input type="hidden" name="napurelon_zeit" value="<?php echo esc_attr( (string) $zeit ); ?>">
	<input type="hidden" name="napurelon_zeit_sig" value="<?php echo esc_attr( napurelon_konto_zeit_signatur( $zeit ) ); ?>">
	<div class="npo-konto__honeypot" aria-hidden="true">
		<label for="npo-konto-webseite"><?php echo esc_html( napurelon_text( 'Deine Website' ) ); ?></label>
		<input type="text" id="npo-konto-webseite" name="napurelon_webseite" tabindex="-1" autocomplete="off">
	</div>
	<?php
}
add_action( 'woocommerce_register_form', 'napurelon_konto_spamschutz_felder', 5 );

/**
 * Pflicht-Checkbox zur Datenschutzerklärung unter dem Datenschutztext.
 */
function napurelon_konto_datenschutz_feld() {
	$url        = get_privacy_policy_url();
	$bestaetigt = ! empty( $_POST['napurelon_datenschutz'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	?>
	<p class="form-row npo-konto__datenschutz">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-row__label-for-checkbox">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="napurelon_datenschutz" value="1" required <?php checked( $bestaetigt ); ?>>
			<span>
				<?php echo esc_html( napurelon_text( 'Ich habe die ' ) ); ?>
				<?php if ( $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?></a>
				<?php else : ?>
					<?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?>
				<?php endif; ?>
				<?php echo esc_html( napurelon_text( ' gelesen.' ) ); ?>
			</span>
			<span class="required" aria-hidden="true">*</span>
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_register_form', 'napurelon_konto_datenschutz_feld', 25 );

/**
 * Prüft Spamfalle, Mindestzeit und Datenschutz-Checkbox.
 *
 * WooCommerce hat den Nonce zu diesem Zeitpunkt bereits geprüft.
 *
 * @param WP_Error $fehler Fehlerobjekt.
 * @return WP_Error
 */
function napurelon_konto_registrierung_pruefen( $fehler ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$zeit     = isset( $_POST['napurelon_zeit'] ) ? absint( wp_unslash( $_POST['napurelon_zeit'] ) ) : 0;
	$zeit_sig = isset( $_POST['napurelon_zeit_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['napurelon_zeit_sig'] ) ) : '';
	$honeypot = isset( $_POST['napurelon_webseite'] ) ? sanitize_text_field( wp_unslash( $_POST['napurelon_webseite'] ) ) : '';
	$zustimm  = ! empty( $_POST['napurelon_datenschutz'] );
	// phpcs:enable

	if ( '' !== $honeypot
		|| ! hash_equals( napurelon_konto_zeit_signatur( $zeit ), $zeit_sig )
		|| time() - $zeit < NAPURELON_KONTO_MINDESTZEIT ) {
		$fehler->add( 'napurelon_spam', napurelon_text( 'Die Registrierung konnte nicht abgeschlossen werden. Bitte versuche es erneut.' ) );

		return $fehler;
	}

	if ( ! $zustimm ) {
		$fehler->add( 'napurelon_datenschutz', napurelon_text( 'Bitte bestätige die Datenschutzerklärung.' ) );
	}

	return $fehler;
}
add_filter( 'woocommerce_process_registration_errors', 'napurelon_konto_registrierung_pruefen' );

/**
 * Hält fest, wann die Datenschutzerklärung bestätigt wurde.
 *
 * @param int $kunde_id Neue Benutzer-ID.
 */
function napurelon_konto_datenschutz_speichern( $kunde_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce hat den Nonce geprüft, die Checkbox ist Pflicht.
	if ( ! empty( $_POST['napurelon_datenschutz'] ) ) {
		update_user_meta( $kunde_id, '_napurelon_datenschutz_bestaetigt', time() );
	}
}
add_action( 'woocommerce_created_customer', 'napurelon_konto_datenschutz_speichern' );

/**
 * Lädt die Konto-Styles nur auf der Kontoseite.
 */
function napurelon_konto_assets() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	$css = '/assets/css/konto.css';
	wp_enqueue_style(
		'napurelon-konto',
		get_stylesheet_directory_uri() . $css,
		array( 'napurelon' ),
		file_exists( get_stylesheet_directory() . $css ) ? (string) filemtime( get_stylesheet_directory() . $css ) : '1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'napurelon_konto_assets' );
