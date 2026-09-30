<?php
/**
 * Auswahl einer mit Elementor gebauten Seite als Vorlage für alle
 * Kategorieseiten.
 *
 * Die gewählte Seite wird auf jeder Rezeptkategorie ausgegeben; die
 * Shortcodes darin beziehen ihre Inhalte aus dem gerade aufgerufenen
 * Begriff. Ohne Auswahl greift die eingebaute Reihenfolge der Bausteine.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

const NAPURELON_KAT_VORLAGE_OPTION = 'napurelon_kategorie_vorlage';

/**
 * Registriert die Einstellung und die Einstellungsseite.
 */
function napurelon_kategorie_vorlage_registrieren() {
	register_setting(
		'napurelon_kategorie_vorlage',
		NAPURELON_KAT_VORLAGE_OPTION,
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'napurelon_kategorie_vorlage_pruefen',
			'default'           => 0,
			'show_in_rest'      => false,
		)
	);
}

add_action( 'admin_init', 'napurelon_kategorie_vorlage_registrieren' );

/**
 * Nimmt nur die ID einer vorhandenen Seite an.
 *
 * @param mixed $wert Eingabe aus dem Formular.
 * @return int Seiten-ID oder 0.
 */
function napurelon_kategorie_vorlage_pruefen( $wert ) {
	$seiten_id = absint( $wert );

	if ( ! $seiten_id || 'page' !== get_post_type( $seiten_id ) ) {
		return 0;
	}

	return $seiten_id;
}

/**
 * Hängt die Einstellungsseite unter das Menü "Rezepte".
 */
function napurelon_kategorie_vorlage_menue() {
	add_submenu_page(
		'edit.php?post_type=rezepte',
		'Kategorie-Vorlage',
		'Kategorie-Vorlage',
		'manage_options',
		'napurelon-kategorie-vorlage',
		'napurelon_kategorie_vorlage_seite'
	);
}

add_action( 'admin_menu', 'napurelon_kategorie_vorlage_menue' );

/**
 * Gibt das Formular der Einstellungsseite aus.
 */
function napurelon_kategorie_vorlage_seite() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$aktuell = (int) get_option( NAPURELON_KAT_VORLAGE_OPTION, 0 );
	?>
	<div class="wrap">
		<h1>Kategorie-Vorlage</h1>
		<p>
			Die gewählte Seite bestimmt das Layout aller Rezeptkategorien. Baue sie
			mit Elementor und setze die Bausteine als Shortcode ein:
			<code>[napurelon_kategorie_hero]</code>, <code>[napurelon_kategorie_nav]</code>,
			<code>[napurelon_kategorie_filter]</code>, <code>[napurelon_kategorie_rezepte]</code>
			und <code>[napurelon_kategorie_empfehlungen]</code>. Ohne Auswahl bleibt die
			eingebaute Reihenfolge bestehen.
		</p>
		<p>
			Für einen selbst gebauten Hero gibt es die Einzelteile
			<code>[napurelon_kategorie_titel]</code> und <code>[napurelon_kategorie_text]</code>;
			das Bild der Kategorie erscheint als Hintergrund, sobald ein Container die
			CSS-Klasse <code>npo-kat-bild</code> trägt. Overlay, Höhe und Abstände bleiben
			dann in Elementor einstellbar.
		</p>
		<form action="options.php" method="post">
			<?php settings_fields( 'napurelon_kategorie_vorlage' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="napurelon-kategorie-vorlage">Vorlagenseite</label>
					</th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => NAPURELON_KAT_VORLAGE_OPTION,
								'id'                => 'napurelon-kategorie-vorlage',
								'selected'          => $aktuell,
								'show_option_none'  => '— keine (eingebautes Layout) —',
								'option_none_value' => 0,
							)
						);
						?>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Liefert die Vorlagenseite, sofern sie veröffentlicht ist.
 *
 * @return int Seiten-ID oder 0.
 */
function napurelon_kategorie_vorlage_id() {
	$seiten_id = (int) get_option( NAPURELON_KAT_VORLAGE_OPTION, 0 );

	if ( ! $seiten_id || 'publish' !== get_post_status( $seiten_id ) ) {
		return 0;
	}

	return $seiten_id;
}

/**
 * Baut den Inhalt der Vorlagenseite.
 *
 * Mit Elementor gebaute Seiten werden über den Elementor-Renderer ausgegeben,
 * damit deren Styles mitgeladen werden; sonst greift der übliche Inhaltsfilter.
 *
 * @param int $seiten_id Seiten-ID.
 * @return string HTML der Seite.
 */
function napurelon_kategorie_vorlage_inhalt( $seiten_id ) {
	if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {
		$elementor = \Elementor\Plugin::$instance;
		$dokument  = $elementor->documents->get( $seiten_id );

		if ( $dokument && $dokument->is_built_with_elementor() ) {
			return (string) $elementor->frontend->get_builder_content_for_display( $seiten_id );
		}
	}

	$seite = get_post( $seiten_id );

	if ( ! $seite ) {
		return '';
	}

	return (string) apply_filters( 'the_content', $seite->post_content );
}

/**
 * Wird die aktuelle Kategorieseite über die Vorlagenseite ausgegeben?
 *
 * @return bool True auf Rezeptkategorien mit gewählter Vorlage.
 */
function napurelon_kategorie_vorlage_aktiv() {
	return ! is_admin() && is_tax( 'rezeptkategorie' ) && napurelon_kategorie_vorlage_id() > 0;
}

/**
 * Astra: Kategorieseiten mit Vorlage ohne Seitenleiste.
 *
 * @param string $layout Layout laut Astra.
 * @return string Layout.
 */
function napurelon_kategorie_vorlage_seitenleiste( $layout ) {
	return napurelon_kategorie_vorlage_aktiv() ? 'no-sidebar' : $layout;
}

add_filter( 'astra_page_layout', 'napurelon_kategorie_vorlage_seitenleiste' );

/**
 * Astra: Kategorieseiten mit Vorlage über die volle Breite, damit die
 * Breiteneinstellungen der Elementor-Container greifen.
 *
 * @param string $layout Inhaltslayout laut Astra.
 * @return string Inhaltslayout.
 */
function napurelon_kategorie_vorlage_inhaltslayout( $layout ) {
	return napurelon_kategorie_vorlage_aktiv() ? 'page-builder' : $layout;
}

add_filter( 'astra_get_content_layout', 'napurelon_kategorie_vorlage_inhaltslayout' );

/**
 * Markiert Kategorieseiten mit Vorlage für das Stylesheet.
 *
 * @param string[] $klassen Body-Klassen.
 * @return string[] Body-Klassen.
 */
function napurelon_kategorie_vorlage_body_klasse( $klassen ) {
	if ( napurelon_kategorie_vorlage_aktiv() ) {
		$klassen[] = 'npo-kategorie-vorlage';
	}

	return $klassen;
}

add_filter( 'body_class', 'napurelon_kategorie_vorlage_body_klasse' );
