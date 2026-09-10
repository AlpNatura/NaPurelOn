<?php
/**
 * Kategorieseite der Rezepte.
 *
 * Die Bausteine stammen aus inc/kategorieseite.php und stehen einzeln als
 * Shortcode zur Verfügung, damit sie sich auch in einer mit Elementor
 * gebauten Seite verwenden lassen.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="npo-kategorieseite">
	<?php
	echo do_shortcode( '[napurelon_kategorie_hero]' );
	echo do_shortcode( '[napurelon_kategorie_nav]' );
	?>

	<div class="npo-kategorieseite__inhalt">
		<?php
		echo do_shortcode( '[napurelon_unterkategorien]' );
		echo do_shortcode( '[napurelon_kategorie_rezepte]' );
		echo do_shortcode( '[napurelon_kategorie_empfehlungen]' );
		?>
	</div>
</div>

<?php
get_footer();
