<?php
/**
 * Felder je Rezeptkategorie: Herobild, Herohöhe, Kurztext und Empfehlungen.
 *
 * Die Werte steuern die Kategorieseite (siehe inc/kategorieseite.php).
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lädt Medienbibliothek und Auswahlskript auf den Kategorieseiten im Backend.
 *
 * @param string $hook Aktuelle Adminseite.
 */
function napurelon_kategorie_felder_assets( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	$taxonomie = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Kontextprüfung.

	if ( 'rezeptkategorie' !== $taxonomie || ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$dir = get_stylesheet_directory();
	$js  = '/assets/js/admin-kategoriebild.js';

	if ( ! file_exists( $dir . $js ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'napurelon-admin-kategoriebild',
		get_stylesheet_directory_uri() . $js,
		array(),
		(string) filemtime( $dir . $js ),
		true
	);
}

add_action( 'admin_enqueue_scripts', 'napurelon_kategorie_felder_assets' );

/**
 * Liefert die Hauptkategorien für die Empfehlungs-Auswahl.
 *
 * @param int $ausser Diese Begriffs-ID auslassen.
 * @return WP_Term[] Begriffe.
 */
function napurelon_kategorie_auswahlliste( $ausser = 0 ) {
	$begriffe = get_terms(
		array(
			'taxonomy'   => 'rezeptkategorie',
			'parent'     => 0,
			'hide_empty' => false,
			'exclude'    => $ausser ? array( (int) $ausser ) : array(),
		)
	);

	return is_wp_error( $begriffe ) ? array() : $begriffe;
}

/**
 * Gibt die Felder im Formular "Neue Rezeptkategorie" aus.
 */
function napurelon_kategorie_felder_neu() {
	wp_nonce_field( 'napurelon_kategorie_felder', 'napurelon_kategorie_felder_nonce' );
	?>
	<div class="form-field">
		<label for="napurelon_kat_bild_id">Herobild</label>
		<input type="hidden" id="napurelon_kat_bild_id" name="napurelon_kat_bild" value="" data-napurelon-bildfeld>
		<button type="button" class="button" data-napurelon-bildwahl>Bild wählen</button>
		<p class="description">Querformat, mindestens 1600 px breit.</p>
	</div>

	<div class="form-field">
		<label for="napurelon_kat_hoehe">Höhe des Heros (px)</label>
		<input type="number" id="napurelon_kat_hoehe" name="napurelon_kat_hoehe" value="<?php echo esc_attr( (string) NAPURELON_KAT_HOEHE_VORGABE ); ?>" min="120" max="800" step="10">
	</div>

	<div class="form-field">
		<label for="napurelon_kat_kurztext">Kurztext im Hero</label>
		<textarea id="napurelon_kat_kurztext" name="napurelon_kat_kurztext" rows="2"></textarea>
		<p class="description">Ohne Eintrag erscheint die Beschreibung der Kategorie.</p>
	</div>
	<?php
}

add_action( 'rezeptkategorie_add_form_fields', 'napurelon_kategorie_felder_neu' );

/**
 * Gibt die Felder im Formular "Rezeptkategorie bearbeiten" aus.
 *
 * @param WP_Term $begriff Begriff.
 */
function napurelon_kategorie_felder_bearbeiten( $begriff ) {
	$bild_id      = (int) get_term_meta( $begriff->term_id, NAPURELON_KAT_BILD, true );
	$hoehe        = (int) get_term_meta( $begriff->term_id, NAPURELON_KAT_HOEHE, true );
	$kurztext     = (string) get_term_meta( $begriff->term_id, NAPURELON_KAT_KURZTEXT, true );
	$empfehlungen = get_term_meta( $begriff->term_id, NAPURELON_KAT_EMPFEHLUNGEN, true );
	$empfehlungen = is_array( $empfehlungen ) ? array_map( 'absint', $empfehlungen ) : array();
	$vorschau     = $bild_id ? wp_get_attachment_image_url( $bild_id, 'medium' ) : '';
	$auswahl      = napurelon_kategorie_auswahlliste( $begriff->term_id );

	wp_nonce_field( 'napurelon_kategorie_felder', 'napurelon_kategorie_felder_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="napurelon_kat_bild_id">Herobild</label></th>
		<td>
			<img src="<?php echo esc_url( $vorschau ); ?>" alt="" style="<?php echo '' === $vorschau ? 'display:none;' : ''; ?>max-width:320px;height:auto;display:block;margin-bottom:8px;" data-napurelon-bildvorschau>
			<input type="hidden" id="napurelon_kat_bild_id" name="napurelon_kat_bild" value="<?php echo esc_attr( (string) $bild_id ); ?>" data-napurelon-bildfeld>
			<button type="button" class="button" data-napurelon-bildwahl>Bild wählen</button>
			<button type="button" class="button-link" data-napurelon-bildentfernen>Entfernen</button>
			<p class="description">Querformat, mindestens 1600 px breit.</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row"><label for="napurelon_kat_hoehe">Höhe des Heros (px)</label></th>
		<td>
			<input type="number" id="napurelon_kat_hoehe" name="napurelon_kat_hoehe" value="<?php echo esc_attr( (string) ( $hoehe ? $hoehe : NAPURELON_KAT_HOEHE_VORGABE ) ); ?>" min="120" max="800" step="10">
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row"><label for="napurelon_kat_kurztext">Kurztext im Hero</label></th>
		<td>
			<textarea id="napurelon_kat_kurztext" name="napurelon_kat_kurztext" rows="2" cols="50"><?php echo esc_textarea( $kurztext ); ?></textarea>
			<p class="description">Ohne Eintrag erscheint die Beschreibung der Kategorie.</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row">Empfehlungen am Seitenende</th>
		<td>
			<?php for ( $i = 0; $i < 3; $i++ ) : ?>
				<?php $gewaehlt = isset( $empfehlungen[ $i ] ) ? $empfehlungen[ $i ] : 0; ?>
				<select name="napurelon_kat_empfehlungen[]" style="margin-bottom:6px;display:block;">
					<option value="0">— keine —</option>
					<?php foreach ( $auswahl as $kandidat ) : ?>
						<option value="<?php echo esc_attr( (string) $kandidat->term_id ); ?>" <?php selected( $kandidat->term_id, $gewaehlt ); ?>>
							<?php echo esc_html( $kandidat->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endfor; ?>
			<p class="description">Ohne Auswahl erscheinen andere Hauptkategorien.</p>
		</td>
	</tr>
	<?php
}

add_action( 'rezeptkategorie_edit_form_fields', 'napurelon_kategorie_felder_bearbeiten' );

/**
 * Speichert die Felder einer Rezeptkategorie.
 *
 * @param int $term_id Begriffs-ID.
 */
function napurelon_kategorie_felder_speichern( $term_id ) {
	if ( ! isset( $_POST['napurelon_kategorie_felder_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['napurelon_kategorie_felder_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'napurelon_kategorie_felder' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( isset( $_POST['napurelon_kat_bild'] ) ) {
		$bild_id = absint( wp_unslash( $_POST['napurelon_kat_bild'] ) );

		if ( $bild_id && 'attachment' === get_post_type( $bild_id ) ) {
			update_term_meta( $term_id, NAPURELON_KAT_BILD, $bild_id );
		} else {
			delete_term_meta( $term_id, NAPURELON_KAT_BILD );
		}
	}

	if ( isset( $_POST['napurelon_kat_hoehe'] ) ) {
		$hoehe = absint( wp_unslash( $_POST['napurelon_kat_hoehe'] ) );
		$hoehe = ( $hoehe >= 120 && $hoehe <= 800 ) ? $hoehe : NAPURELON_KAT_HOEHE_VORGABE;
		update_term_meta( $term_id, NAPURELON_KAT_HOEHE, $hoehe );
	}

	if ( isset( $_POST['napurelon_kat_kurztext'] ) ) {
		$kurztext = sanitize_textarea_field( wp_unslash( $_POST['napurelon_kat_kurztext'] ) );

		if ( '' === $kurztext ) {
			delete_term_meta( $term_id, NAPURELON_KAT_KURZTEXT );
		} else {
			update_term_meta( $term_id, NAPURELON_KAT_KURZTEXT, $kurztext );
		}
	}

	if ( isset( $_POST['napurelon_kat_empfehlungen'] ) && is_array( $_POST['napurelon_kat_empfehlungen'] ) ) {
		$roh          = array_map( 'absint', wp_unslash( $_POST['napurelon_kat_empfehlungen'] ) );
		$empfehlungen = array();

		foreach ( $roh as $kandidat_id ) {
			$kandidat = $kandidat_id ? get_term( $kandidat_id, 'rezeptkategorie' ) : null;

			if ( $kandidat instanceof WP_Term && $kandidat->term_id !== (int) $term_id ) {
				$empfehlungen[ $kandidat->term_id ] = $kandidat->term_id;
			}
		}

		$empfehlungen = array_slice( array_values( $empfehlungen ), 0, 3 );

		if ( empty( $empfehlungen ) ) {
			delete_term_meta( $term_id, NAPURELON_KAT_EMPFEHLUNGEN );
		} else {
			update_term_meta( $term_id, NAPURELON_KAT_EMPFEHLUNGEN, $empfehlungen );
		}
	}
}

add_action( 'created_rezeptkategorie', 'napurelon_kategorie_felder_speichern' );
add_action( 'edited_rezeptkategorie', 'napurelon_kategorie_felder_speichern' );
