<?php
/**
 * Newsletter-Anmeldung [napurelon_newsletter] und Widget "Newsletter-Anmeldung".
 *
 * Double-Opt-in: Eintrag wird erst nach Klick auf den Bestätigungslink aktiv,
 * unbestätigte Einträge werden nach 7 Tagen gelöscht. Abmelden löscht den
 * Eintrag. Schutz: Nonce, Honeypot, signierte Zeitprüfung, Limit pro IP und
 * gesamt, signierte Links (HMAC), keine Auskunft, ob eine Adresse bereits
 * eingetragen ist. Verwaltung unter "Newsletter" im Dashboard.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_NEWSLETTER_DB_VERSION', '1' );
define( 'NAPURELON_NEWSLETTER_LIMIT_IP', 5 );
define( 'NAPURELON_NEWSLETTER_LIMIT_GESAMT', 50 );
define( 'NAPURELON_NEWSLETTER_GUELTIGKEIT', 7 * DAY_IN_SECONDS );
define( 'NAPURELON_NEWSLETTER_ERNEUT_SENDEN', 10 * MINUTE_IN_SECONDS );

/**
 * Name der Tabelle.
 *
 * @return string
 */
function napurelon_newsletter_tabelle() {
	global $wpdb;

	return $wpdb->prefix . 'napurelon_newsletter';
}

/**
 * Legt die Tabelle an bzw. aktualisiert sie.
 */
function napurelon_newsletter_tabelle_anlegen() {
	if ( NAPURELON_NEWSLETTER_DB_VERSION === get_option( 'napurelon_newsletter_db_version' ) ) {
		return;
	}

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tabelle = napurelon_newsletter_tabelle();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$tabelle} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(254) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'ausstehend',
			sprache varchar(10) NOT NULL DEFAULT '',
			erstellt datetime NOT NULL,
			gesendet datetime NOT NULL,
			bestaetigt datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email(191)),
			KEY status (status)
		) {$charset};"
	);

	update_option( 'napurelon_newsletter_db_version', NAPURELON_NEWSLETTER_DB_VERSION, false );
}
add_action( 'init', 'napurelon_newsletter_tabelle_anlegen', 5 );

/**
 * Löscht unbestätigte Einträge, deren Link abgelaufen ist.
 */
function napurelon_newsletter_aufraeumen() {
	global $wpdb;

	$tabelle = napurelon_newsletter_tabelle();
	$grenze  = gmdate( 'Y-m-d H:i:s', time() - NAPURELON_NEWSLETTER_GUELTIGKEIT );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabelle} WHERE status = 'ausstehend' AND gesendet < %s", $grenze ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Liest einen Eintrag.
 *
 * @param int $id ID.
 * @return object|null
 */
function napurelon_newsletter_eintrag( $id ) {
	global $wpdb;

	$tabelle = napurelon_newsletter_tabelle();

	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabelle} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Signatur für Bestätigungs- und Abmeldelinks.
 *
 * @param object $eintrag Eintrag.
 * @param string $zweck bestaetigen oder abmelden.
 * @return string
 */
function napurelon_newsletter_token( $eintrag, $zweck ) {
	$daten = $eintrag->id . '|' . $eintrag->email . '|' . $zweck;

	if ( 'bestaetigen' === $zweck ) {
		$daten .= '|' . $eintrag->gesendet;
	}

	return hash_hmac( 'sha256', $daten, wp_salt( 'auth' ) . '|napurelon_newsletter' );
}

/**
 * Startseite in der Sprache des Eintrags.
 *
 * @param string $sprache Sprach-Slug.
 * @return string
 */
function napurelon_newsletter_startseite( $sprache = '' ) {
	if ( '' !== $sprache && function_exists( 'pll_home_url' ) ) {
		$url = pll_home_url( $sprache );
		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/' );
}

/**
 * Link zum Bestätigen bzw. Abmelden.
 *
 * @param object $eintrag Eintrag.
 * @param string $zweck bestaetigen oder abmelden.
 * @return string
 */
function napurelon_newsletter_link( $eintrag, $zweck ) {
	return add_query_arg(
		array(
			'npo_newsletter' => $zweck,
			'id'             => (int) $eintrag->id,
			't'              => napurelon_newsletter_token( $eintrag, $zweck ),
		),
		napurelon_newsletter_startseite( $eintrag->sprache )
	);
}

/**
 * Aktuelle Seitenadresse ohne Newsletter-Parameter (nur eigener Host).
 *
 * @return string
 */
function napurelon_newsletter_aktuelle_url() {
	$pfad = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$teile = wp_parse_url( home_url() );
	$basis = ( isset( $teile['scheme'] ) ? $teile['scheme'] : 'https' ) . '://' . ( isset( $teile['host'] ) ? $teile['host'] : '' )
		. ( isset( $teile['port'] ) ? ':' . (int) $teile['port'] : '' );

	if ( '' === $pfad || '/' !== $pfad[0] || 0 === strpos( $pfad, '//' ) ) {
		$pfad = '/';
	}

	return remove_query_arg( array( 'napurelon_newsletter', 'newsletter' ), $basis . $pfad );
}

/**
 * Leitet mit einer Statusmeldung weiter.
 *
 * @param string $url Ziel.
 * @param string $status Status.
 */
function napurelon_newsletter_redirect( $url, $status ) {
	wp_safe_redirect( add_query_arg( 'newsletter', $status, $url ) . '#npo-newsletter' );
	exit;
}

/**
 * Speichert bzw. liefert das Ergebnis der aktuellen Verarbeitung.
 *
 * @param array|null $setzen Zu speicherndes Ergebnis.
 * @return array{fehler:array,email:string,datenschutz:bool}
 */
function napurelon_newsletter_ergebnis( $setzen = null ) {
	static $ergebnis = array(
		'fehler'      => array(),
		'email'       => '',
		'datenschutz' => false,
	);

	if ( is_array( $setzen ) ) {
		$ergebnis = $setzen;
	}

	return $ergebnis;
}

/**
 * Signatur für den Zeitstempel des Formulars.
 *
 * @param int $zeit Zeitstempel.
 * @return string
 */
function napurelon_newsletter_zeit_signatur( $zeit ) {
	return wp_hash( $zeit . '|napurelon_newsletter' );
}

/**
 * Schlüssel für das IP-Limit; die IP selbst wird nicht gespeichert.
 *
 * @return string
 */
function napurelon_newsletter_ip_schluessel() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return 'napurelon_newsletter_' . md5( wp_hash( $ip . '|napurelon_newsletter_ip' ) );
}

/**
 * Anmeldungen der letzten Stunde unter einem Schlüssel.
 *
 * @param string $schluessel Transient-Schlüssel.
 * @return int[]
 */
function napurelon_newsletter_versuche( $schluessel ) {
	$liste = get_transient( $schluessel );
	$liste = is_array( $liste ) ? array_map( 'absint', $liste ) : array();

	return array_values(
		array_filter(
			$liste,
			function ( $zeitstempel ) {
				return $zeitstempel > time() - HOUR_IN_SECONDS;
			}
		)
	);
}

/**
 * Sendet die Bestätigungs-E-Mail.
 *
 * @param object $eintrag Eintrag.
 * @return bool
 */
function napurelon_newsletter_bestaetigung_senden( $eintrag ) {
	$seitenname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$seitenname = trim( str_replace( array( "\r", "\n" ), ' ', $seitenname ) );

	$betreff = sprintf( '[%s] %s', $seitenname, napurelon_text( 'Bitte bestätige deine Newsletter-Anmeldung' ) );
	$inhalt  = napurelon_text( 'Hallo' ) . "\n\n"
		. sprintf( napurelon_text( 'Du hast dich für den Newsletter von %s angemeldet. Bitte bestätige deine Anmeldung über diesen Link:' ), $seitenname ) . "\n\n"
		. napurelon_newsletter_link( $eintrag, 'bestaetigen' ) . "\n\n"
		. napurelon_text( 'Der Link ist 7 Tage gültig. Wenn du dich nicht angemeldet hast, ignoriere diese E-Mail einfach. Du erhältst dann keine weiteren Nachrichten.' ) . "\n";

	return wp_mail( $eintrag->email, $betreff, $inhalt, array( 'Content-Type: text/plain; charset=UTF-8' ) );
}

/**
 * Verarbeitet das abgeschickte Anmeldeformular.
 */
function napurelon_newsletter_anmeldung_verarbeiten() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD']
		|| ! isset( $_GET['napurelon_newsletter'] ) || is_admin() ) {
		return;
	}

	$ergebnis = array(
		'fehler'      => array(),
		'email'       => '',
		'datenschutz' => false,
	);
	$zurueck  = napurelon_newsletter_aktuelle_url();
	$eingaben = isset( $_POST ) && is_array( $_POST ) ? wp_unslash( $_POST ) : array();
	$nonce    = napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_nonce' );

	if ( ! wp_verify_nonce( sanitize_key( $nonce ), 'napurelon_newsletter_anmelden' ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.';
		napurelon_newsletter_ergebnis( $ergebnis );
		return;
	}

	$zeit     = absint( napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_zeit' ) );
	$zeit_sig = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_zeit_sig' ) );
	$honeypot = napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_webseite' );

	if ( '' !== $honeypot
		|| ! hash_equals( napurelon_newsletter_zeit_signatur( $zeit ), $zeit_sig )
		|| time() - $zeit < 2 ) {
		// Bots erhalten keine Rückmeldung, dass sie erkannt wurden.
		napurelon_newsletter_redirect( $zurueck, 'pruefen' );
	}

	$email       = strtolower( sanitize_email( napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_email' ) ) );
	$datenschutz = '1' === napurelon_formular_eingabe( $eingaben, 'napurelon_newsletter_datenschutz' );

	$ergebnis['email']       = $email;
	$ergebnis['datenschutz'] = $datenschutz;

	if ( '' === $email || ! is_email( $email ) || strlen( $email ) > 254 ) {
		$ergebnis['fehler']['email'] = 'Bitte gib eine gültige E-Mail-Adresse ein.';
	}

	if ( ! $datenschutz ) {
		$ergebnis['fehler']['datenschutz'] = 'Bitte bestätige die Datenschutzerklärung.';
	}

	if ( ! empty( $ergebnis['fehler'] ) ) {
		napurelon_newsletter_ergebnis( $ergebnis );
		return;
	}

	$ip_schluessel = napurelon_newsletter_ip_schluessel();
	$ip_versuche   = napurelon_newsletter_versuche( $ip_schluessel );
	$alle_versuche = napurelon_newsletter_versuche( 'napurelon_newsletter_gesamt' );

	if ( count( $ip_versuche ) >= NAPURELON_NEWSLETTER_LIMIT_IP
		|| count( $alle_versuche ) >= NAPURELON_NEWSLETTER_LIMIT_GESAMT ) {
		$ergebnis['fehler']['_formular'] = 'Zu viele Versuche. Bitte versuche es in einer Stunde erneut.';
		napurelon_newsletter_ergebnis( $ergebnis );
		return;
	}

	$ip_versuche[]   = time();
	$alle_versuche[] = time();
	set_transient( $ip_schluessel, $ip_versuche, HOUR_IN_SECONDS );
	set_transient( 'napurelon_newsletter_gesamt', $alle_versuche, HOUR_IN_SECONDS );

	napurelon_newsletter_aufraeumen();

	global $wpdb;

	$tabelle = napurelon_newsletter_tabelle();
	$jetzt   = current_time( 'mysql', true );
	$sprache = function_exists( 'pll_current_language' ) ? sanitize_key( (string) pll_current_language() ) : '';
	$eintrag = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabelle} WHERE email = %s", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $eintrag ) {
		// Bestätigte Adressen und frisch gesendete Links: dieselbe Meldung, keine neue E-Mail.
		if ( 'bestaetigt' === $eintrag->status
			|| strtotime( $eintrag->gesendet . ' UTC' ) > time() - NAPURELON_NEWSLETTER_ERNEUT_SENDEN ) {
			napurelon_newsletter_redirect( $zurueck, 'pruefen' );
		}

		$wpdb->update( $tabelle, array( 'gesendet' => $jetzt, 'sprache' => $sprache ), array( 'id' => (int) $eintrag->id ), array( '%s', '%s' ), array( '%d' ) );
	} else {
		$wpdb->insert(
			$tabelle,
			array(
				'email'    => $email,
				'status'   => 'ausstehend',
				'sprache'  => $sprache,
				'erstellt' => $jetzt,
				'gesendet' => $jetzt,
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
		$eintrag = napurelon_newsletter_eintrag( (int) $wpdb->insert_id );
	}

	$eintrag = $eintrag ? napurelon_newsletter_eintrag( (int) $eintrag->id ) : null;

	if ( ! $eintrag || ! napurelon_newsletter_bestaetigung_senden( $eintrag ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Anmeldung hat nicht geklappt. Bitte versuche es später erneut.';
		napurelon_newsletter_ergebnis( $ergebnis );
		return;
	}

	napurelon_newsletter_redirect( $zurueck, 'pruefen' );
}
add_action( 'template_redirect', 'napurelon_newsletter_anmeldung_verarbeiten' );

/**
 * Verarbeitet Bestätigungs- und Abmeldelinks.
 */
function napurelon_newsletter_link_verarbeiten() {
	if ( is_admin() || ! isset( $_GET['npo_newsletter'], $_GET['id'], $_GET['t'] )
		|| ! is_scalar( $_GET['npo_newsletter'] ) || ! is_scalar( $_GET['id'] ) || ! is_scalar( $_GET['t'] ) ) {
		return;
	}

	$zweck   = sanitize_key( wp_unslash( $_GET['npo_newsletter'] ) );
	$id      = absint( $_GET['id'] );
	$token   = sanitize_text_field( wp_unslash( $_GET['t'] ) );
	$zurueck = remove_query_arg( array( 'npo_newsletter', 'id', 't' ), napurelon_newsletter_aktuelle_url() );

	if ( ! in_array( $zweck, array( 'bestaetigen', 'abmelden' ), true ) ) {
		return;
	}

	$eintrag = $id ? napurelon_newsletter_eintrag( $id ) : null;

	if ( ! $eintrag ) {
		napurelon_newsletter_redirect( $zurueck, 'abmelden' === $zweck ? 'abgemeldet' : 'ungueltig' );
	}

	if ( ! hash_equals( napurelon_newsletter_token( $eintrag, $zweck ), $token ) ) {
		napurelon_newsletter_redirect( $zurueck, 'ungueltig' );
	}

	global $wpdb;

	$tabelle = napurelon_newsletter_tabelle();

	if ( 'abmelden' === $zweck ) {
		$wpdb->delete( $tabelle, array( 'id' => $id ), array( '%d' ) );
		napurelon_newsletter_redirect( $zurueck, 'abgemeldet' );
	}

	if ( 'bestaetigt' !== $eintrag->status ) {
		if ( strtotime( $eintrag->gesendet . ' UTC' ) < time() - NAPURELON_NEWSLETTER_GUELTIGKEIT ) {
			$wpdb->delete( $tabelle, array( 'id' => $id ), array( '%d' ) );
			napurelon_newsletter_redirect( $zurueck, 'ungueltig' );
		}

		$wpdb->update(
			$tabelle,
			array(
				'status'     => 'bestaetigt',
				'bestaetigt' => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	napurelon_newsletter_redirect( $zurueck, 'bestaetigt' );
}
add_action( 'template_redirect', 'napurelon_newsletter_link_verarbeiten', 1 );

/**
 * Entfernt die Anmeldung, wenn das zugehörige Benutzerkonto gelöscht wird.
 *
 * @param int $benutzer_id Benutzer-ID.
 */
function napurelon_newsletter_benutzer_geloescht( $benutzer_id ) {
	$benutzer = get_userdata( $benutzer_id );

	if ( ! $benutzer || '' === $benutzer->user_email ) {
		return;
	}

	global $wpdb;

	$wpdb->delete( napurelon_newsletter_tabelle(), array( 'email' => strtolower( $benutzer->user_email ) ), array( '%s' ) );
}
add_action( 'delete_user', 'napurelon_newsletter_benutzer_geloescht' );

add_action(
	'wp_enqueue_scripts',
	function () {
		$css  = '/assets/css/newsletter.css';
		$pfad = get_stylesheet_directory() . $css;

		wp_enqueue_style(
			'napurelon-newsletter',
			get_stylesheet_directory_uri() . $css,
			array( 'napurelon' ),
			file_exists( $pfad ) ? (string) filemtime( $pfad ) : '1.0.0'
		);
	}
);

/**
 * Statusmeldungen nach Weiterleitung.
 *
 * @return array<string,string>
 */
function napurelon_newsletter_meldungen() {
	return array(
		'pruefen'    => 'Fast geschafft! Bitte bestätige deine Anmeldung über den Link, den wir dir per E-Mail geschickt haben.',
		'bestaetigt' => 'Danke! Deine Newsletter-Anmeldung ist bestätigt.',
		'abgemeldet' => 'Du wurdest vom Newsletter abgemeldet.',
		'ungueltig'  => 'Dieser Link ist ungültig oder abgelaufen. Bitte melde dich erneut an.',
	);
}

/**
 * Shortcode [napurelon_newsletter].
 *
 * @return string
 */
function napurelon_newsletter_shortcode() {
	static $instanz = 0;
	++$instanz;

	$anker     = 1 === $instanz ? ' id="npo-newsletter"' : '';
	$id        = 'npo-newsletter-' . $instanz;
	$meldungen = napurelon_newsletter_meldungen();
	$status    = isset( $_GET['newsletter'] ) && is_scalar( $_GET['newsletter'] ) ? sanitize_key( wp_unslash( $_GET['newsletter'] ) ) : '';

	if ( isset( $meldungen[ $status ] ) ) {
		$klasse = 'ungueltig' === $status ? 'npo-newsletter__fehler' : 'npo-newsletter__danke';

		return '<div class="npo-newsletter"' . $anker . '><div class="' . esc_attr( $klasse ) . '" role="status"><p>'
			. esc_html( napurelon_text( $meldungen[ $status ] ) ) . '</p></div></div>';
	}

	$ergebnis = napurelon_newsletter_ergebnis();
	$fehler   = isset( $ergebnis['fehler'] ) && is_array( $ergebnis['fehler'] ) ? $ergebnis['fehler'] : array();
	$email    = isset( $ergebnis['email'] ) ? (string) $ergebnis['email'] : '';

	if ( '' === $email && empty( $fehler ) && is_user_logged_in() ) {
		$email = wp_get_current_user()->user_email;
	}

	$datenschutz     = ! empty( $ergebnis['datenschutz'] );
	$datenschutz_url = get_privacy_policy_url();
	$zeit_formular   = time();
	$aktion          = add_query_arg( 'napurelon_newsletter', '1', napurelon_newsletter_aktuelle_url() );

	ob_start();
	?>
	<div class="npo-newsletter"<?php echo $anker; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( isset( $fehler['_formular'] ) ) : ?>
			<div class="npo-newsletter__fehler" role="alert"><p><?php echo esc_html( napurelon_text( $fehler['_formular'] ) ); ?></p></div>
		<?php endif; ?>

		<form class="npo-newsletter__form" method="post" action="<?php echo esc_url( $aktion . '#npo-newsletter' ); ?>">
			<?php wp_nonce_field( 'napurelon_newsletter_anmelden', 'napurelon_newsletter_nonce', false ); ?>
			<input type="hidden" name="napurelon_newsletter_zeit" value="<?php echo esc_attr( (string) $zeit_formular ); ?>">
			<input type="hidden" name="napurelon_newsletter_zeit_sig" value="<?php echo esc_attr( napurelon_newsletter_zeit_signatur( $zeit_formular ) ); ?>">

			<div class="npo-newsletter__honeypot" aria-hidden="true">
				<label><?php echo esc_html( napurelon_text( 'Deine Website' ) ); ?>
					<input type="text" name="napurelon_newsletter_webseite" tabindex="-1" autocomplete="off">
				</label>
			</div>

			<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-email' ); ?>"><?php echo esc_html( napurelon_text( 'E-Mail' ) ); ?></label>
			<div class="npo-newsletter__zeile">
				<input type="email"
					id="<?php echo esc_attr( $id . '-email' ); ?>"
					name="napurelon_newsletter_email"
					class="npo-newsletter__feld<?php echo isset( $fehler['email'] ) ? ' npo-newsletter__feld--fehler' : ''; ?>"
					value="<?php echo esc_attr( $email ); ?>"
					placeholder="<?php echo esc_attr( napurelon_text( 'Deine E-Mail-Adresse' ) ); ?>"
					maxlength="254" autocomplete="email" required aria-required="true"
					<?php echo isset( $fehler['email'] ) ? 'aria-invalid="true" aria-describedby="' . esc_attr( $id . '-email-fehler' ) . '"' : ''; ?>>
				<button type="submit" class="npo-newsletter__senden"><?php echo esc_html( napurelon_text( 'Anmelden' ) ); ?></button>
			</div>
			<?php if ( isset( $fehler['email'] ) ) : ?>
				<p class="npo-newsletter__feldfehler" id="<?php echo esc_attr( $id . '-email-fehler' ); ?>"><?php echo esc_html( napurelon_text( $fehler['email'] ) ); ?></p>
			<?php endif; ?>

			<label class="npo-newsletter__zustimmung">
				<input type="checkbox" name="napurelon_newsletter_datenschutz" value="1" required <?php checked( $datenschutz ); ?><?php echo isset( $fehler['datenschutz'] ) ? ' aria-invalid="true"' : ''; ?>>
				<span>
					<?php echo esc_html( napurelon_text( 'Ich habe die ' ) ); ?>
					<?php if ( $datenschutz_url ) : ?>
						<a href="<?php echo esc_url( $datenschutz_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?></a>
					<?php else : ?>
						<?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?>
					<?php endif; ?>
					<?php echo esc_html( napurelon_text( ' gelesen.' ) ); ?>
					<?php echo esc_html( napurelon_text( 'Abmelden ist jederzeit möglich.' ) ); ?>
				</span>
			</label>
			<?php if ( isset( $fehler['datenschutz'] ) ) : ?>
				<p class="npo-newsletter__feldfehler"><?php echo esc_html( napurelon_text( $fehler['datenschutz'] ) ); ?></p>
			<?php endif; ?>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'napurelon_newsletter', 'napurelon_newsletter_shortcode' );

add_action(
	'widgets_init',
	function () {
		register_widget( 'NaPurelOn_Newsletter_Widget' );
	}
);

/**
 * Widget "Newsletter-Anmeldung" für die Footer-Widget-Bereiche.
 */
class NaPurelOn_Newsletter_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'napurelon_newsletter',
			'Newsletter-Anmeldung (NaPurelOn)',
			array( 'description' => 'E-Mail-Feld für die Newsletter-Anmeldung mit Bestätigungslink.' )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'] . napurelon_newsletter_shortcode() . $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		echo '<p>' . esc_html( 'Keine Einstellungen nötig.' ) . '</p>';
	}
}

/**
 * Admin-Seite "Newsletter".
 */
function napurelon_newsletter_admin_menue() {
	add_menu_page( 'Newsletter', 'Newsletter', 'manage_options', 'napurelon-newsletter', 'napurelon_newsletter_admin_seite', 'dashicons-email-alt', 71 );
}
add_action( 'admin_menu', 'napurelon_newsletter_admin_menue' );

/**
 * Löscht einen Eintrag aus der Admin-Seite.
 */
function napurelon_newsletter_admin_loeschen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'Keine Berechtigung.' ), 403 );
	}

	check_admin_referer( 'napurelon_newsletter_loeschen' );

	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( $id ) {
		global $wpdb;
		$wpdb->delete( napurelon_newsletter_tabelle(), array( 'id' => $id ), array( '%d' ) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'napurelon-newsletter', 'geloescht' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_napurelon_newsletter_loeschen', 'napurelon_newsletter_admin_loeschen' );

/**
 * Schützt CSV-Zellen vor Formel-Ausführung in Tabellenprogrammen.
 *
 * @param string $wert Wert.
 * @return string
 */
function napurelon_newsletter_csv_zelle( $wert ) {
	$wert = (string) $wert;

	return preg_match( '/^[=+\-@\t\r]/', $wert ) ? "'" . $wert : $wert;
}

/**
 * CSV-Export der bestätigten Adressen.
 */
function napurelon_newsletter_admin_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'Keine Berechtigung.' ), 403 );
	}

	check_admin_referer( 'napurelon_newsletter_export' );

	global $wpdb;

	$tabelle  = napurelon_newsletter_tabelle();
	$eintraege = $wpdb->get_results( "SELECT * FROM {$tabelle} WHERE status = 'bestaetigt' ORDER BY bestaetigt ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="newsletter-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'X-Content-Type-Options: nosniff' );

	$ausgabe = fopen( 'php://output', 'w' );
	fwrite( $ausgabe, "\xEF\xBB\xBF" );
	fputcsv( $ausgabe, array( 'E-Mail', 'Sprache', 'Bestätigt (UTC)', 'Abmeldelink' ), ';' );

	foreach ( $eintraege as $eintrag ) {
		fputcsv(
			$ausgabe,
			array(
				napurelon_newsletter_csv_zelle( $eintrag->email ),
				napurelon_newsletter_csv_zelle( $eintrag->sprache ),
				napurelon_newsletter_csv_zelle( $eintrag->bestaetigt ),
				napurelon_newsletter_csv_zelle( napurelon_newsletter_link( $eintrag, 'abmelden' ) ),
			),
			';'
		);
	}

	fclose( $ausgabe );
	exit;
}
add_action( 'admin_post_napurelon_newsletter_export', 'napurelon_newsletter_admin_export' );

/**
 * Ausgabe der Admin-Seite.
 */
function napurelon_newsletter_admin_seite() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	napurelon_newsletter_aufraeumen();

	global $wpdb;

	$tabelle     = napurelon_newsletter_tabelle();
	$pro_seite   = 50;
	$seite       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$gesamt      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabelle}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$bestaetigt  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabelle} WHERE status = 'bestaetigt'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$eintraege   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabelle} ORDER BY erstellt DESC LIMIT %d OFFSET %d", $pro_seite, ( $seite - 1 ) * $pro_seite ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$seiten      = max( 1, (int) ceil( $gesamt / $pro_seite ) );
	$zeitformat  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	?>
	<div class="wrap">
		<h1>Newsletter</h1>
		<?php if ( isset( $_GET['geloescht'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Eintrag gelöscht.</p></div>
		<?php endif; ?>
		<p>
			<?php echo esc_html( sprintf( '%d bestätigt, %d unbestätigt.', $bestaetigt, $gesamt - $bestaetigt ) ); ?>
			Unbestätigte Einträge werden nach 7 Tagen automatisch gelöscht.
			Formular einfügen mit dem Shortcode <code>[napurelon_newsletter]</code> oder dem Widget „Newsletter-Anmeldung (NaPurelOn)“.
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="napurelon_newsletter_export">
			<?php wp_nonce_field( 'napurelon_newsletter_export' ); ?>
			<?php submit_button( 'Bestätigte Adressen als CSV exportieren', 'secondary', 'submit', false ); ?>
		</form>
		<table class="widefat striped" style="margin-top:1rem">
			<thead>
				<tr><th>E-Mail</th><th>Status</th><th>Sprache</th><th>Angemeldet</th><th>Bestätigt</th><th></th></tr>
			</thead>
			<tbody>
				<?php if ( empty( $eintraege ) ) : ?>
					<tr><td colspan="6">Noch keine Anmeldungen.</td></tr>
				<?php endif; ?>
				<?php foreach ( $eintraege as $eintrag ) : ?>
					<tr>
						<td><?php echo esc_html( $eintrag->email ); ?></td>
						<td><?php echo esc_html( 'bestaetigt' === $eintrag->status ? 'Bestätigt' : 'Unbestätigt' ); ?></td>
						<td><?php echo esc_html( strtoupper( $eintrag->sprache ) ); ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $eintrag->erstellt, $zeitformat ) ); ?></td>
						<td><?php echo esc_html( $eintrag->bestaetigt ? get_date_from_gmt( $eintrag->bestaetigt, $zeitformat ) : '—' ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Eintrag wirklich löschen?');">
								<input type="hidden" name="action" value="napurelon_newsletter_loeschen">
								<input type="hidden" name="id" value="<?php echo esc_attr( (string) $eintrag->id ); ?>">
								<?php wp_nonce_field( 'napurelon_newsletter_loeschen' ); ?>
								<button type="submit" class="button-link button-link-delete">Löschen</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $seiten > 1 ) : ?>
			<p>
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $seite,
							'total'   => $seiten,
						)
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
