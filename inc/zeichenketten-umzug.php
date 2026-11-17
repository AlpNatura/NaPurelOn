<?php
/**
 * Einmaliger Umzug: Türkische Übersetzungen der NaPurelOn-Zeichenketten, die
 * im Polylang-Feld „Deutsch“ statt „Türkçe“ eingetragen wurden, ins Feld
 * „Türkçe“ verschieben. Bereits ausgefüllte türkische Felder bleiben unverändert.
 *
 * Läuft einmal beim nächsten Aufruf des Admin-Bereichs durch einen Administrator.
 * Die Datei kann danach wieder entfernt werden.
 *
 * @package NaPurelOn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NAPURELON_ZK_UMZUG_OPTION  = 'napurelon_zeichenketten_umzug_tr';
const NAPURELON_ZK_UMZUG_HINWEIS = 'napurelon_zeichenketten_umzug_hinweis';

/**
 * Polylang-Sprache anhand des Kürzels.
 *
 * @param string $slug Sprachkürzel.
 * @return PLL_Language|false
 */
function napurelon_zk_umzug_sprache( $slug ) {
	$model = PLL()->model;

	if ( isset( $model->languages ) && is_object( $model->languages ) ) {
		return $model->languages->get( $slug );
	}

	return $model->get_language( $slug );
}

/**
 * Verschiebt die Übersetzungen einmalig.
 */
function napurelon_zk_umzug() {
	if ( get_option( NAPURELON_ZK_UMZUG_OPTION ) || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
		return;
	}

	$de = napurelon_zk_umzug_sprache( 'de' );
	$tr = napurelon_zk_umzug_sprache( 'tr' );

	if ( ! $de || ! $tr ) {
		return;
	}

	$mo_de = new PLL_MO();
	$mo_de->import_from_db( $de );
	$mo_tr = new PLL_MO();
	$mo_tr->import_from_db( $tr );

	$verschoben = array();

	foreach ( napurelon_sprache_texte() as $original ) {
		$text = $mo_de->translate_if_any( $original );

		if ( '' === $text || $text === $original || '' !== $mo_tr->translate_if_any( $original ) ) {
			continue;
		}

		$mo_tr->add_entry( $mo_tr->make_entry( $original, $text ) );
		$mo_de->delete_entry( $original );
		$verschoben[ $original ] = $text;
	}

	if ( $verschoben ) {
		$mo_tr->export_to_db( $tr );

		// Deutsch erst leeren, wenn die türkischen Texte nachweislich gespeichert sind.
		$kontrolle = new PLL_MO();
		$kontrolle->import_from_db( $tr );
		foreach ( $verschoben as $original => $text ) {
			if ( $kontrolle->translate_if_any( $original ) !== $text ) {
				return;
			}
		}

		$mo_de->export_to_db( $de );
	}

	update_option( NAPURELON_ZK_UMZUG_OPTION, gmdate( 'c' ), false );
	set_transient( NAPURELON_ZK_UMZUG_HINWEIS, $verschoben, DAY_IN_SECONDS );
}

add_action( 'admin_init', 'napurelon_zk_umzug', 30 );

/**
 * Zeigt einmal an, welche Übersetzungen verschoben wurden.
 */
function napurelon_zk_umzug_hinweis() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$verschoben = get_transient( NAPURELON_ZK_UMZUG_HINWEIS );

	if ( false === $verschoben ) {
		return;
	}

	delete_transient( NAPURELON_ZK_UMZUG_HINWEIS );
	?>
	<div class="notice notice-success is-dismissible">
		<p><strong>NaPurelOn:</strong> <?php echo esc_html( sprintf( '%d Übersetzungen von „Deutsch“ nach „Türkçe“ verschoben.', count( $verschoben ) ) ); ?></p>
		<?php if ( $verschoben ) : ?>
			<ul style="list-style:disc;margin-left:2em;">
				<?php foreach ( $verschoben as $original => $text ) : ?>
					<li><?php echo esc_html( $original ); ?> → <?php echo esc_html( $text ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_notices', 'napurelon_zk_umzug_hinweis' );
