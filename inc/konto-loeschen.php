<?php
/**
 * „Konto löschen“ unter Mein Konto → Kontodetails.
 *
 * Bestätigung per Passwort und Checkbox. Nicht veröffentlichte Rezepte werden
 * gelöscht, veröffentlichte Rezepte gehen ohne Namen an einen Administrator
 * über, Kommentare werden anonymisiert.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

define( 'NAPURELON_LOESCHEN_FEHLVERSUCHE', 5 );

/**
 * Darf dieses Konto sich selbst über das Frontend löschen?
 *
 * @param WP_User $benutzer Benutzer.
 * @return bool
 */
function napurelon_loeschen_erlaubt( WP_User $benutzer ) {
	return ! user_can( $benutzer, 'edit_others_posts' ) && ! user_can( $benutzer, 'manage_options' );
}

/**
 * Administrator, der veröffentlichte Rezepte übernimmt.
 *
 * @param int $ausser Benutzer-ID, die nicht in Frage kommt.
 * @return int 0, wenn keiner gefunden wurde.
 */
function napurelon_loeschen_nachfolger( $ausser ) {
	$admins = get_users(
		array(
			'role'    => 'administrator',
			'exclude' => array( (int) $ausser ),
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => 1,
			'fields'  => 'ID',
		)
	);
	$id     = empty( $admins ) ? 0 : (int) $admins[0];
	$id     = (int) apply_filters( 'napurelon_konto_loeschen_nachfolger', $id, (int) $ausser );

	return ( $id > 0 && $id !== (int) $ausser && get_userdata( $id ) ) ? $id : 0;
}

/**
 * URL der Kontodetails.
 *
 * @return string
 */
function napurelon_loeschen_kontodetails_url() {
	$konto = napurelon_konto_seite_url();

	return '' !== $konto ? wc_get_endpoint_url( 'edit-account', '', $konto ) : home_url( '/' );
}

/**
 * Bricht mit einer Fehlermeldung ab und leitet zu den Kontodetails zurück.
 *
 * @param string $text Meldung.
 */
function napurelon_loeschen_fehler( $text ) {
	wc_add_notice( napurelon_text( $text ), 'error' );
	wp_safe_redirect( napurelon_loeschen_kontodetails_url() . '#npo-konto-loeschen' );
	exit;
}

/**
 * Verarbeitet die Löschanfrage.
 */
function napurelon_loeschen_verarbeiten() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD']
		|| empty( $_POST['napurelon_konto_loeschen_aktion'] ) || is_admin() || ! function_exists( 'wc_add_notice' ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$benutzer = wp_get_current_user();
	$id       = (int) $benutzer->ID;
	$nonce    = isset( $_POST['napurelon_konto_loeschen_nonce'] ) ? sanitize_key( wp_unslash( $_POST['napurelon_konto_loeschen_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'napurelon_konto_loeschen_' . $id ) ) {
		napurelon_loeschen_fehler( 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.' );
	}

	if ( ! napurelon_loeschen_erlaubt( $benutzer ) ) {
		napurelon_loeschen_fehler( 'Konten mit Verwaltungsrechten können hier nicht gelöscht werden.' );
	}

	$versuche = get_user_meta( $id, '_napurelon_loeschen_fehlversuche', true );
	$versuche = is_array( $versuche ) ? array_map( 'absint', $versuche ) : array();
	$versuche = array_values(
		array_filter(
			$versuche,
			function ( $zeitstempel ) {
				return $zeitstempel > time() - HOUR_IN_SECONDS;
			}
		)
	);

	if ( count( $versuche ) >= NAPURELON_LOESCHEN_FEHLVERSUCHE ) {
		napurelon_loeschen_fehler( 'Zu viele Versuche. Bitte versuche es in einer Stunde erneut.' );
	}

	// Passwörter werden nicht bereinigt, sondern nur verglichen.
	$passwort = isset( $_POST['napurelon_konto_loeschen_passwort'] ) && is_string( $_POST['napurelon_konto_loeschen_passwort'] ) ? wp_unslash( $_POST['napurelon_konto_loeschen_passwort'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $passwort || ! wp_check_password( $passwort, $benutzer->user_pass, $id ) ) {
		$versuche[] = time();
		update_user_meta( $id, '_napurelon_loeschen_fehlversuche', $versuche );
		napurelon_loeschen_fehler( 'Das Passwort ist nicht korrekt.' );
	}

	if ( empty( $_POST['napurelon_konto_loeschen_bestaetigung'] ) ) {
		napurelon_loeschen_fehler( 'Bitte bestätige, dass du dein Konto endgültig löschen möchtest.' );
	}

	$nachfolger = napurelon_loeschen_nachfolger( $id );
	if ( 0 === $nachfolger ) {
		napurelon_loeschen_fehler( 'Dein Konto konnte nicht gelöscht werden. Bitte kontaktiere uns.' );
	}

	$email = $benutzer->user_email;
	$name  = $benutzer->display_name;

	$offene_rezepte = get_posts(
		array(
			'post_type'      => 'rezepte',
			'author'         => $id,
			'post_status'    => array( 'pending', 'draft', 'future', 'private', 'trash', 'auto-draft' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'lang'           => '',
		)
	);
	foreach ( $offene_rezepte as $rezept_id ) {
		$anhaenge = get_children(
			array(
				'post_parent' => $rezept_id,
				'post_type'   => 'attachment',
				'fields'      => 'ids',
			)
		);
		foreach ( $anhaenge as $anhang_id ) {
			if ( (int) get_post_field( 'post_author', $anhang_id ) === $id ) {
				wp_delete_attachment( $anhang_id, true );
			}
		}
		wp_delete_post( $rezept_id, true );
	}

	$kommentare = get_comments(
		array(
			'user_id' => $id,
			'status'  => 'all',
			'fields'  => 'ids',
		)
	);
	foreach ( $kommentare as $kommentar_id ) {
		wp_update_comment(
			array(
				'comment_ID'           => (int) $kommentar_id,
				'comment_author'       => 'Anonym',
				'comment_author_email' => '',
				'comment_author_url'   => '',
				'comment_author_IP'    => '',
				'comment_agent'        => '',
				'user_id'              => 0,
			)
		);
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';

	WP_Session_Tokens::get_instance( $id )->destroy_all();

	if ( ! wp_delete_user( $id, $nachfolger ) ) {
		napurelon_loeschen_fehler( 'Dein Konto konnte nicht gelöscht werden. Bitte kontaktiere uns.' );
	}

	wp_clear_auth_cookie();
	wp_set_current_user( 0 );

	$seitenname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	wp_mail(
		$email,
		'[' . $seitenname . '] Dein Konto wurde gelöscht',
		"Hallo " . $name . ",\n\ndein Konto bei " . $seitenname . " wurde auf deinen Wunsch gelöscht.\n\nFalls du das nicht warst, antworte bitte auf diese E-Mail."
	);
	wp_mail(
		get_option( 'admin_email' ),
		'[' . $seitenname . '] Ein Benutzerkonto wurde gelöscht',
		"Ein Benutzer hat sein Konto über Mein Konto gelöscht.\nNicht veröffentlichte Rezepte: " . count( $offene_rezepte ) . " gelöscht.\nVeröffentlichte Rezepte wurden dem Administrator (ID " . $nachfolger . ') übertragen.'
	);

	$ziel = napurelon_konto_seite_url();
	wp_safe_redirect( add_query_arg( 'konto', 'geloescht', '' !== $ziel ? $ziel : home_url( '/' ) ) );
	exit;
}
add_action( 'template_redirect', 'napurelon_loeschen_verarbeiten', 5 );

/**
 * Bereich „Konto löschen“ unter dem Formular der Kontodetails.
 */
function napurelon_loeschen_formular() {
	$benutzer = wp_get_current_user();
	if ( ! $benutzer->exists() || ! napurelon_loeschen_erlaubt( $benutzer ) ) {
		return;
	}
	?>
	<section class="npo-konto-loeschen" id="npo-konto-loeschen">
		<h3><?php echo esc_html( napurelon_text( 'Konto löschen' ) ); ?></h3>
		<p><?php echo esc_html( napurelon_text( 'Wenn du dein Konto löschst:' ) ); ?></p>
		<ul>
			<li><?php echo esc_html( napurelon_text( 'werden dein Konto und deine Kontodaten (Name, E-Mail-Adresse, Passwort) endgültig gelöscht,' ) ); ?></li>
			<li><?php echo esc_html( napurelon_text( 'werden deine noch nicht veröffentlichten Rezepte gelöscht,' ) ); ?></li>
			<li><?php echo esc_html( napurelon_text( 'bleiben deine veröffentlichten Rezepte auf NaPurelOn, jedoch ohne deinen Namen,' ) ); ?></li>
			<li><?php echo esc_html( napurelon_text( 'werden deine Kommentare anonymisiert.' ) ); ?></li>
		</ul>
		<p><strong><?php echo esc_html( napurelon_text( 'Das Löschen kann nicht rückgängig gemacht werden.' ) ); ?></strong></p>

		<details class="npo-konto-loeschen__details">
			<summary><?php echo esc_html( napurelon_text( 'Ich möchte mein Konto löschen' ) ); ?></summary>
			<form method="post" class="npo-konto-loeschen__form" action="<?php echo esc_url( napurelon_loeschen_kontodetails_url() ); ?>">
				<?php wp_nonce_field( 'napurelon_konto_loeschen_' . $benutzer->ID, 'napurelon_konto_loeschen_nonce' ); ?>
				<input type="hidden" name="napurelon_konto_loeschen_aktion" value="1">
				<p class="woocommerce-form-row form-row">
					<label for="npo-konto-loeschen-passwort"><?php echo esc_html( napurelon_text( 'Aktuelles Passwort zur Bestätigung' ) ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input type="password" class="woocommerce-Input input-text" id="npo-konto-loeschen-passwort" name="napurelon_konto_loeschen_passwort" autocomplete="current-password" required>
				</p>
				<p class="form-row npo-konto-loeschen__bestaetigung">
					<label>
						<input type="checkbox" name="napurelon_konto_loeschen_bestaetigung" value="1" required>
						<span><?php echo esc_html( napurelon_text( 'Ich habe verstanden, dass mein Konto endgültig gelöscht wird.' ) ); ?></span>
					</label>
				</p>
				<p>
					<button type="submit" class="button npo-konto-loeschen__senden"><?php echo esc_html( napurelon_text( 'Konto endgültig löschen' ) ); ?></button>
				</p>
			</form>
		</details>
	</section>
	<?php
}
add_action( 'woocommerce_after_edit_account_form', 'napurelon_loeschen_formular' );

/**
 * Bestätigung nach dem Löschen über dem Anmeldeformular.
 */
function napurelon_loeschen_bestaetigung() {
	if ( isset( $_GET['konto'] ) && 'geloescht' === sanitize_key( wp_unslash( $_GET['konto'] ) ) && ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="woocommerce-message" role="status">' . esc_html( napurelon_text( 'Dein Konto wurde gelöscht.' ) ) . '</div>';
	}
}
add_action( 'woocommerce_before_customer_login_form', 'napurelon_loeschen_bestaetigung' );
