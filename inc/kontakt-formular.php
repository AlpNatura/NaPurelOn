<?php
/**
 * Kontaktformular [napurelon_kontakt_formular].
 *
 * Sendet eine Nachricht an die Admin-E-Mail-Adresse (Filter
 * `napurelon_kontakt_empfaenger`). Schutz: Nonce, Honeypot, signierte
 * Zeitprüfung, Limit pro IP und gesamt, Längenprüfung, bereinigte Header.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_KONTAKT_LIMIT_IP', 5 );
define( 'NAPURELON_KONTAKT_LIMIT_GESAMT', 50 );
define( 'NAPURELON_KONTAKT_MAX_LINKS', 3 );

/**
 * Speichert bzw. liefert das Ergebnis der aktuellen Verarbeitung.
 *
 * @param array|null $setzen Zu speicherndes Ergebnis.
 * @return array{fehler:array,werte:array,datenschutz:bool}
 */
function napurelon_kontakt_ergebnis( $setzen = null ) {
	static $ergebnis = array(
		'fehler'      => array(),
		'werte'       => array(),
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
function napurelon_kontakt_zeit_signatur( $zeit ) {
	return wp_hash( $zeit . '|napurelon_kontakt' );
}

/**
 * Schlüssel für das IP-Limit; die IP selbst wird nicht gespeichert.
 *
 * @return string
 */
function napurelon_kontakt_ip_schluessel() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return 'napurelon_kontakt_' . md5( wp_hash( $ip . '|napurelon_kontakt_ip' ) );
}

/**
 * Zählt die Einsendungen der letzten Stunde unter einem Schlüssel.
 *
 * @param string $schluessel Transient-Schlüssel.
 * @return int[] Zeitstempel.
 */
function napurelon_kontakt_einsendungen( $schluessel ) {
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
 * Entfernt Zeichen, die in einem Mail-Header Probleme machen könnten.
 *
 * @param string $text Text.
 * @return string
 */
function napurelon_kontakt_header_text( $text ) {
	return trim( str_replace( array( "\r", "\n", '"', '<', '>', ',', ';', ':', '@', '\\' ), ' ', $text ) );
}

/**
 * Leitet nach erfolgreichem Versand auf die Formularseite zurück.
 */
function napurelon_kontakt_erfolgs_redirect() {
	wp_safe_redirect( add_query_arg( 'kontakt', 'gesendet', napurelon_formular_permalink() ) . '#npo-kontakt' );
	exit;
}

/**
 * Verarbeitet das abgeschickte Kontaktformular.
 */
function napurelon_verarbeite_kontaktformular() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD']
		|| ! isset( $_GET['napurelon_kontakt'] ) || is_admin() ) {
		return;
	}

	$ergebnis = array(
		'fehler'      => array(),
		'werte'       => array(),
		'datenschutz' => false,
	);

	$eingaben = isset( $_POST ) && is_array( $_POST ) ? wp_unslash( $_POST ) : array();
	$nonce    = napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_nonce' );

	if ( ! wp_verify_nonce( sanitize_key( $nonce ), 'napurelon_kontakt_senden' ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.';
		napurelon_kontakt_ergebnis( $ergebnis );
		return;
	}

	$zeit     = absint( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_zeit' ) );
	$zeit_sig = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_zeit_sig' ) );
	$honeypot = napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_webseite' );

	if ( '' !== $honeypot
		|| ! hash_equals( napurelon_kontakt_zeit_signatur( $zeit ), $zeit_sig )
		|| time() - $zeit < 3 ) {
		// Bots erhalten keine Rückmeldung, dass sie erkannt wurden.
		napurelon_kontakt_erfolgs_redirect();
	}

	$name        = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_name' ) );
	$email       = sanitize_email( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_email' ) );
	$betreff     = sanitize_text_field( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_betreff' ) );
	$nachricht   = sanitize_textarea_field( napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_nachricht' ) );
	$datenschutz = '1' === napurelon_formular_eingabe( $eingaben, 'napurelon_kontakt_datenschutz' );

	$ergebnis['werte']       = array(
		'name'      => $name,
		'email'     => $email,
		'betreff'   => $betreff,
		'nachricht' => $nachricht,
	);
	$ergebnis['datenschutz'] = $datenschutz;

	$fehler = array();

	if ( '' === $name ) {
		$fehler['name'] = 'Bitte gib deinen Namen ein.';
	} elseif ( mb_strlen( $name ) > 100 ) {
		$fehler['name'] = 'Der Name darf höchstens 100 Zeichen lang sein.';
	}

	if ( '' === $email || ! is_email( $email ) || strlen( $email ) > 254 ) {
		$fehler['email'] = 'Bitte gib eine gültige E-Mail-Adresse ein.';
	}

	if ( mb_strlen( $betreff ) > 150 ) {
		$fehler['betreff'] = 'Der Betreff darf höchstens 150 Zeichen lang sein.';
	}

	if ( mb_strlen( $nachricht ) < 10 ) {
		$fehler['nachricht'] = 'Bitte schreibe eine Nachricht (mindestens 10 Zeichen).';
	} elseif ( mb_strlen( $nachricht ) > 5000 ) {
		$fehler['nachricht'] = 'Die Nachricht darf höchstens 5.000 Zeichen lang sein.';
	} elseif ( preg_match_all( '#https?://|www\.#i', $nachricht . ' ' . $betreff ) > NAPURELON_KONTAKT_MAX_LINKS ) {
		$fehler['nachricht'] = 'Bitte füge höchstens drei Links ein.';
	}

	if ( ! $datenschutz ) {
		$fehler['datenschutz'] = 'Bitte bestätige die Datenschutzerklärung.';
	}

	if ( ! empty( $fehler ) ) {
		$ergebnis['fehler'] = $fehler;
		napurelon_kontakt_ergebnis( $ergebnis );
		return;
	}

	$ip_schluessel     = napurelon_kontakt_ip_schluessel();
	$ip_einsendungen   = napurelon_kontakt_einsendungen( $ip_schluessel );
	$alle_einsendungen = napurelon_kontakt_einsendungen( 'napurelon_kontakt_gesamt' );

	if ( count( $ip_einsendungen ) >= NAPURELON_KONTAKT_LIMIT_IP
		|| count( $alle_einsendungen ) >= NAPURELON_KONTAKT_LIMIT_GESAMT ) {
		$ergebnis['fehler']['_formular'] = 'Es wurden zu viele Nachrichten gesendet. Bitte versuche es später erneut.';
		napurelon_kontakt_ergebnis( $ergebnis );
		return;
	}

	$empfaenger = sanitize_email( (string) apply_filters( 'napurelon_kontakt_empfaenger', get_option( 'admin_email' ) ) );
	if ( ! is_email( $empfaenger ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Nachricht konnte nicht gesendet werden. Bitte versuche es später erneut.';
		napurelon_kontakt_ergebnis( $ergebnis );
		return;
	}

	$seitenname   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$betreff_mail = sprintf(
		'[%s] Kontaktanfrage%s',
		napurelon_kontakt_header_text( $seitenname ),
		'' !== $betreff ? ': ' . napurelon_kontakt_header_text( $betreff ) : ''
	);
	$sprache      = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
	$inhalt       = "Neue Nachricht über das Kontaktformular\n\n"
		. 'Name: ' . $name . "\n"
		. 'E-Mail: ' . $email . "\n"
		. 'Betreff: ' . ( '' !== $betreff ? $betreff : '—' ) . "\n"
		. 'Seite: ' . napurelon_formular_permalink() . "\n"
		. ( '' !== $sprache ? 'Sprache: ' . $sprache . "\n" : '' )
		. "\n" . $nachricht . "\n";
	$kopfzeilen   = array(
		'Content-Type: text/plain; charset=UTF-8',
		sprintf( 'Reply-To: %s <%s>', napurelon_kontakt_header_text( $name ), $email ),
	);

	if ( ! wp_mail( $empfaenger, $betreff_mail, $inhalt, $kopfzeilen ) ) {
		$ergebnis['fehler']['_formular'] = 'Die Nachricht konnte nicht gesendet werden. Bitte versuche es später erneut.';
		napurelon_kontakt_ergebnis( $ergebnis );
		return;
	}

	$ip_einsendungen[]   = time();
	$alle_einsendungen[] = time();
	set_transient( $ip_schluessel, $ip_einsendungen, HOUR_IN_SECONDS );
	set_transient( 'napurelon_kontakt_gesamt', $alle_einsendungen, HOUR_IN_SECONDS );

	napurelon_kontakt_erfolgs_redirect();
}
add_action( 'template_redirect', 'napurelon_verarbeite_kontaktformular' );

/**
 * HTML eines Eingabefelds.
 *
 * @param string $feld Feldname ohne Präfix.
 * @param string $label Beschriftung.
 * @param string $typ text, email oder textarea.
 * @param string $wert Wert.
 * @param array  $fehler Fehlerliste.
 * @param int    $maxlength Maximale Länge.
 * @param bool   $pflicht Pflichtfeld.
 * @param string $autocomplete Autocomplete-Wert.
 * @return string
 */
function napurelon_kontakt_feld_html( $feld, $label, $typ, $wert, $fehler, $maxlength, $pflicht, $autocomplete = '' ) {
	static $instanz = 0;
	++$instanz;

	$id        = 'npo-kontakt-' . $instanz . '-' . $feld;
	$hat_fehler = isset( $fehler[ $feld ] );
	$attribute  = sprintf(
		'id="%1$s" name="%2$s" class="npo-kontaktformular__feld%3$s" maxlength="%4$d"%5$s%6$s%7$s',
		esc_attr( $id ),
		esc_attr( 'napurelon_kontakt_' . $feld ),
		$hat_fehler ? ' npo-kontaktformular__feld--fehler' : '',
		(int) $maxlength,
		$pflicht ? ' required aria-required="true"' : '',
		$hat_fehler ? ' aria-invalid="true" aria-describedby="' . esc_attr( $id . '-fehler' ) . '"' : '',
		'' !== $autocomplete ? ' autocomplete="' . esc_attr( $autocomplete ) . '"' : ''
	);

	$html  = '<div class="npo-kontaktformular__gruppe">';
	$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html( napurelon_text( $label ) );
	if ( $pflicht ) {
		$html .= ' <span class="npo-kontaktformular__stern" aria-hidden="true">*</span>';
	}
	$html .= '</label>';

	if ( 'textarea' === $typ ) {
		$html .= '<textarea ' . $attribute . ' rows="6">' . esc_textarea( $wert ) . '</textarea>';
	} else {
		$html .= '<input type="' . esc_attr( $typ ) . '" ' . $attribute . ' value="' . esc_attr( $wert ) . '">';
	}

	if ( $hat_fehler ) {
		$html .= '<p class="npo-kontaktformular__feldfehler" id="' . esc_attr( $id . '-fehler' ) . '">' . esc_html( napurelon_text( $fehler[ $feld ] ) ) . '</p>';
	}

	return $html . '</div>';
}

/**
 * Shortcode [napurelon_kontakt_formular].
 *
 * @return string
 */
function napurelon_kontaktformular_shortcode() {
	$verzeichnis = get_stylesheet_directory();
	$css         = '/assets/css/kontakt-formular.css';

	wp_enqueue_style(
		'napurelon-kontakt-formular',
		get_stylesheet_directory_uri() . $css,
		array( 'napurelon' ),
		file_exists( $verzeichnis . $css ) ? (string) filemtime( $verzeichnis . $css ) : '1.0.0'
	);

	$permalink = napurelon_formular_permalink();
	$status    = isset( $_GET['kontakt'] ) && is_scalar( $_GET['kontakt'] ) ? sanitize_key( wp_unslash( $_GET['kontakt'] ) ) : '';

	if ( 'gesendet' === $status ) {
		ob_start();
		?>
		<div class="npo-kontaktformular" id="npo-kontakt">
			<div class="npo-kontaktformular__danke" role="status">
				<p><?php echo esc_html( napurelon_text( 'Danke für deine Nachricht! Wir melden uns so bald wie möglich.' ) ); ?></p>
				<a href="<?php echo esc_url( remove_query_arg( 'kontakt', $permalink ) . '#npo-kontakt' ); ?>"><?php echo esc_html( napurelon_text( 'Weitere Nachricht senden' ) ); ?></a>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	$ergebnis = napurelon_kontakt_ergebnis();
	$fehler   = isset( $ergebnis['fehler'] ) && is_array( $ergebnis['fehler'] ) ? $ergebnis['fehler'] : array();
	$werte    = isset( $ergebnis['werte'] ) && is_array( $ergebnis['werte'] ) ? $ergebnis['werte'] : array();

	if ( empty( $werte ) && is_user_logged_in() ) {
		$benutzer = wp_get_current_user();
		$werte    = array(
			'name'  => $benutzer->display_name,
			'email' => $benutzer->user_email,
		);
	}

	$wert = function ( $schluessel ) use ( $werte ) {
		return isset( $werte[ $schluessel ] ) && is_scalar( $werte[ $schluessel ] ) ? (string) $werte[ $schluessel ] : '';
	};

	$datenschutz     = ! empty( $ergebnis['datenschutz'] );
	$datenschutz_url = get_privacy_policy_url();
	$zeit_formular   = time();

	ob_start();
	?>
	<div class="npo-kontaktformular" id="npo-kontakt">
		<?php if ( isset( $fehler['_formular'] ) ) : ?>
			<div class="npo-kontaktformular__fehler" role="alert">
				<p><?php echo esc_html( napurelon_text( $fehler['_formular'] ) ); ?></p>
			</div>
		<?php elseif ( ! empty( $fehler ) ) : ?>
			<div class="npo-kontaktformular__fehler" role="alert">
				<p><?php echo esc_html( napurelon_text( 'Bitte prüfe die markierten Felder.' ) ); ?></p>
			</div>
		<?php endif; ?>

		<form class="npo-kontaktformular__form"
			method="post"
			action="<?php echo esc_url( add_query_arg( 'napurelon_kontakt', '1', remove_query_arg( 'kontakt', $permalink ) ) . '#npo-kontakt' ); ?>">
			<?php wp_nonce_field( 'napurelon_kontakt_senden', 'napurelon_kontakt_nonce', false ); ?>
			<input type="hidden" name="napurelon_kontakt_zeit" value="<?php echo esc_attr( (string) $zeit_formular ); ?>">
			<input type="hidden" name="napurelon_kontakt_zeit_sig" value="<?php echo esc_attr( napurelon_kontakt_zeit_signatur( $zeit_formular ) ); ?>">

			<div class="npo-kontaktformular__honeypot" aria-hidden="true">
				<label><?php echo esc_html( napurelon_text( 'Deine Website' ) ); ?>
					<input type="text" name="napurelon_kontakt_webseite" tabindex="-1" autocomplete="off">
				</label>
			</div>

			<div class="npo-kontaktformular__raster">
				<?php
				echo napurelon_kontakt_feld_html( 'name', 'Name', 'text', $wert( 'name' ), $fehler, 100, true, 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo napurelon_kontakt_feld_html( 'email', 'E-Mail', 'email', $wert( 'email' ), $fehler, 254, true, 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
			<?php
			echo napurelon_kontakt_feld_html( 'betreff', 'Betreff', 'text', $wert( 'betreff' ), $fehler, 150, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo napurelon_kontakt_feld_html( 'nachricht', 'Nachricht', 'textarea', $wert( 'nachricht' ), $fehler, 5000, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

			<div class="npo-kontaktformular__gruppe">
				<label class="npo-kontaktformular__zustimmung">
					<input type="checkbox" name="napurelon_kontakt_datenschutz" value="1" required <?php checked( $datenschutz ); ?><?php echo isset( $fehler['datenschutz'] ) ? ' aria-invalid="true"' : ''; ?>>
					<span>
						<?php echo esc_html( napurelon_text( 'Ich habe die ' ) ); ?>
						<?php if ( $datenschutz_url ) : ?>
							<a href="<?php echo esc_url( $datenschutz_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( napurelon_text( 'Datenschutzerklärung' ) ); ?>
						<?php endif; ?>
						<?php echo esc_html( napurelon_text( ' gelesen.' ) ); ?>
						<span class="npo-kontaktformular__stern" aria-hidden="true">*</span>
					</span>
				</label>
				<?php if ( isset( $fehler['datenschutz'] ) ) : ?>
					<p class="npo-kontaktformular__feldfehler"><?php echo esc_html( napurelon_text( $fehler['datenschutz'] ) ); ?></p>
				<?php endif; ?>
			</div>

			<div class="npo-kontaktformular__abschluss">
				<button type="submit" class="npo-kontaktformular__senden"><?php echo esc_html( napurelon_text( 'Nachricht senden' ) ); ?></button>
				<p class="npo-kontaktformular__hilfe"><span class="npo-kontaktformular__stern" aria-hidden="true">*</span> <?php echo esc_html( napurelon_text( 'Pflichtfeld' ) ); ?></p>
			</div>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'napurelon_kontakt_formular', 'napurelon_kontaktformular_shortcode' );
