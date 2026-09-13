<?php
/**
 * Kategorieseite der Rezepte.
 *
 * Die Bausteine stammen aus inc/kategorieseite.php und stehen einzeln als
 * Shortcode zur Verfügung. Ist unter "Rezepte → Kategorie-Vorlage" eine Seite
 * gewählt, bestimmt deren Elementor-Layout das Aussehen; sonst greift die
 * eingebaute Reihenfolge.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

get_header();

$napurelon_vorlage = function_exists( 'napurelon_kategorie_vorlage_id' ) ? napurelon_kategorie_vorlage_id() : 0;

if ( $napurelon_vorlage ) {
	echo '<div class="npo-kategorieseite npo-kategorieseite--vorlage">';
	echo napurelon_kategorie_vorlage_inhalt( $napurelon_vorlage ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Seiteninhalt, bereits gefiltert.
	echo '</div>';

	get_footer();

	return;
}
?>

<div class="npo-kategorieseite">
	<?php
	echo do_shortcode( '[napurelon_kategorie_hero]' );
	?>

	<div class="npo-kategorieseite__inhalt">
		<?php
		echo do_shortcode( '[napurelon_kategorie_filter]' );
		echo do_shortcode( '[napurelon_kategorie_rezepte]' );
		echo do_shortcode( '[napurelon_kategorie_empfehlungen]' );
		?>
	</div>
</div>

<?php
get_footer();
