<?php
/**
 * Kontoübersicht ohne Shop-Hinweise (ersetzt myaccount/dashboard.php,
 * solange die Shop-Bereiche ausgeblendet sind).
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

$current_user = isset( $current_user ) ? $current_user : wp_get_current_user();
$erlaubt      = array( 'a' => array( 'href' => array() ) );
?>

<p>
	<?php
	printf(
		/* translators: 1: user display name 2: logout url */
		wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $erlaubt ),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>

<p>
	<?php
	printf(
		wp_kses( napurelon_text( 'Hier siehst du deine <a href="%1$s">eingereichten Rezepte</a> und kannst dein <a href="%2$s">Passwort und deine Kontodetails</a> bearbeiten.' ), $erlaubt ),
		esc_url( wc_get_endpoint_url( NAPURELON_KONTO_ENDPUNKT ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>

<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Hooks.
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
