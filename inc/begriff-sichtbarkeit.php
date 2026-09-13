<?php
/**
 * Sichtbarkeit einzelner Begriffe: Kategorien und Merkmale lassen sich im
 * Backend anlegen und pflegen, ohne dass sie auf der Website erscheinen.
 *
 * Ein verborgener Begriff verschwindet aus Navigation, Filterzeile,
 * Unterkategorien und Empfehlungen; seine Kategorieseite antwortet mit 404.
 * Angemeldete Redaktion sieht die Seite weiterhin.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/** Begriffsfeld: 1 = nicht veröffentlicht. */
const NAPURELON_BEGRIFF_VERBORGEN = 'napurelon_begriff_verborgen';

/**
 * Taxonomien, für die sich die Sichtbarkeit einstellen lässt.
 *
 * @return string[] Namen der Taxonomien.
 */
function napurelon_sichtbarkeit_taxonomien() {
	$taxonomien = array( 'rezeptkategorie', 'rezepttag' );

	foreach ( napurelon_filter_definitionen() as $filter ) {
		if ( isset( $filter['taxonomie'] ) ) {
			$taxonomien[] = $filter['taxonomie'];
		}
	}

	return $taxonomien;
}

/**
 * Meldet, ob ein Begriff auf der Website verborgen ist.
 *
 * @param WP_Term|int $begriff Begriff oder Begriffs-ID.
 * @return bool Wahr, wenn der Begriff nicht veröffentlicht ist.
 */
function napurelon_begriff_verborgen( $begriff ) {
	$term_id = ( $begriff instanceof WP_Term ) ? $begriff->term_id : absint( $begriff );

	if ( ! $term_id ) {
		return false;
	}

	return '1' === (string) get_term_meta( $term_id, NAPURELON_BEGRIFF_VERBORGEN, true );
}

/**
 * Liefert die verborgenen Begriffe einer Taxonomie.
 *
 * @param string $taxonomie Taxonomie.
 * @return int[] Begriffs-IDs.
 */
function napurelon_verborgene_begriffe( $taxonomie ) {
	static $zwischenspeicher = array();
	static $laeuft           = false;

	if ( isset( $zwischenspeicher[ $taxonomie ] ) ) {
		return $zwischenspeicher[ $taxonomie ];
	}

	if ( $laeuft ) {
		return array();
	}

	$laeuft   = true;
	$begriffe = get_terms(
		array(
			'taxonomy'   => $taxonomie,
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_key'   => NAPURELON_BEGRIFF_VERBORGEN, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Begriffsfeld, kleine Datenmenge.
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Begriffsfeld, kleine Datenmenge.
		)
	);
	$laeuft   = false;

	$zwischenspeicher[ $taxonomie ] = is_array( $begriffe ) ? array_map( 'absint', $begriffe ) : array();

	return $zwischenspeicher[ $taxonomie ];
}

/**
 * Nimmt verborgene Begriffe aus den Abfragen der Website heraus.
 *
 * Im Backend und in der REST-Schnittstelle bleibt alles sichtbar, damit sich
 * die Begriffe weiter pflegen und Rezepten zuordnen lassen.
 *
 * @param array $argumente Abfrageargumente.
 * @param array $taxonomien Abgefragte Taxonomien.
 * @return array Ergänzte Argumente.
 */
function napurelon_sichtbarkeit_begriffe_filtern( $argumente, $taxonomien ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $argumente;
	}

	$taxonomien = array_intersect( (array) $taxonomien, napurelon_sichtbarkeit_taxonomien() );

	if ( empty( $taxonomien ) ) {
		return $argumente;
	}

	$verborgen = array();

	foreach ( $taxonomien as $taxonomie ) {
		$verborgen = array_merge( $verborgen, napurelon_verborgene_begriffe( $taxonomie ) );
	}

	if ( empty( $verborgen ) ) {
		return $argumente;
	}

	$bisher = isset( $argumente['exclude'] ) ? wp_parse_id_list( $argumente['exclude'] ) : array();

	$argumente['exclude'] = array_values( array_unique( array_merge( $bisher, $verborgen ) ) );

	return $argumente;
}

add_filter( 'get_terms_args', 'napurelon_sichtbarkeit_begriffe_filtern', 10, 2 );

/**
 * Entfernt verborgene Begriffe aus den Begriffen eines Beitrags.
 *
 * @param WP_Term[]|WP_Error $begriffe  Begriffe des Beitrags.
 * @param int                $post_id   Beitrags-ID.
 * @param string             $taxonomie Taxonomie.
 * @return WP_Term[]|WP_Error Gefilterte Begriffe.
 */
function napurelon_sichtbarkeit_beitragsbegriffe( $begriffe, $post_id, $taxonomie ) {
	if ( is_admin() || is_wp_error( $begriffe ) || ! is_array( $begriffe ) ) {
		return $begriffe;
	}

	if ( ! in_array( $taxonomie, napurelon_sichtbarkeit_taxonomien(), true ) ) {
		return $begriffe;
	}

	$sichtbar = array_filter(
		$begriffe,
		static function ( $begriff ) {
			return ! napurelon_begriff_verborgen( $begriff );
		}
	);

	return array_values( $sichtbar );
}

add_filter( 'get_the_terms', 'napurelon_sichtbarkeit_beitragsbegriffe', 10, 3 );

/**
 * Beantwortet die Seite eines verborgenen Begriffs mit 404.
 *
 * Wer Beiträge bearbeiten darf, sieht die Seite zur Kontrolle weiterhin.
 */
function napurelon_sichtbarkeit_seite_sperren() {
	if ( ! is_tax( napurelon_sichtbarkeit_taxonomien() ) || current_user_can( 'edit_posts' ) ) {
		return;
	}

	$begriff = get_queried_object();

	if ( ! $begriff instanceof WP_Term || ! napurelon_begriff_verborgen( $begriff ) ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

add_action( 'template_redirect', 'napurelon_sichtbarkeit_seite_sperren' );

/**
 * Gibt das Feld im Formular "Neuer Begriff" aus.
 */
function napurelon_sichtbarkeit_feld_neu() {
	wp_nonce_field( 'napurelon_begriff_sichtbarkeit', 'napurelon_begriff_sichtbarkeit_nonce' );
	?>
	<div class="form-field">
		<label>
			<input type="checkbox" name="napurelon_begriff_verborgen" value="1">
			Nicht veröffentlichen
		</label>
		<p class="description">Der Begriff bleibt im Backend nutzbar, erscheint aber nicht auf der Website.</p>
	</div>
	<?php
}

/**
 * Gibt das Feld im Formular "Begriff bearbeiten" aus.
 *
 * @param WP_Term $begriff Begriff.
 */
function napurelon_sichtbarkeit_feld_bearbeiten( $begriff ) {
	wp_nonce_field( 'napurelon_begriff_sichtbarkeit', 'napurelon_begriff_sichtbarkeit_nonce' );
	?>
	<tr class="form-field">
		<th scope="row">Sichtbarkeit</th>
		<td>
			<label>
				<input type="checkbox" name="napurelon_begriff_verborgen" value="1" <?php checked( napurelon_begriff_verborgen( $begriff ) ); ?>>
				Nicht veröffentlichen
			</label>
			<p class="description">Der Begriff bleibt im Backend nutzbar, erscheint aber nicht auf der Website.</p>
		</td>
	</tr>
	<?php
}

/**
 * Speichert die Sichtbarkeit eines Begriffs.
 *
 * @param int $term_id Begriffs-ID.
 */
function napurelon_sichtbarkeit_speichern( $term_id ) {
	if ( ! isset( $_POST['napurelon_begriff_sichtbarkeit_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['napurelon_begriff_sichtbarkeit_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'napurelon_begriff_sichtbarkeit' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( isset( $_POST['napurelon_begriff_verborgen'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['napurelon_begriff_verborgen'] ) ) ) {
		update_term_meta( $term_id, NAPURELON_BEGRIFF_VERBORGEN, '1' );
	} else {
		delete_term_meta( $term_id, NAPURELON_BEGRIFF_VERBORGEN );
	}
}

/**
 * Ergänzt die Begriffsliste um eine Spalte zur Sichtbarkeit.
 *
 * @param array $spalten Bisherige Spalten.
 * @return array Ergänzte Spalten.
 */
function napurelon_sichtbarkeit_spalte( $spalten ) {
	$spalten['napurelon_sichtbarkeit'] = 'Sichtbarkeit';

	return $spalten;
}

/**
 * Füllt die Spalte zur Sichtbarkeit.
 *
 * @param string $inhalt  Bisheriger Inhalt.
 * @param string $spalte  Spaltenname.
 * @param int    $term_id Begriffs-ID.
 * @return string Inhalt der Spalte.
 */
function napurelon_sichtbarkeit_spalte_inhalt( $inhalt, $spalte, $term_id ) {
	if ( 'napurelon_sichtbarkeit' !== $spalte ) {
		return $inhalt;
	}

	return napurelon_begriff_verborgen( $term_id ) ? 'Nicht veröffentlicht' : 'Veröffentlicht';
}

/**
 * Hängt Feld, Speicherung und Spalte an alle betroffenen Taxonomien.
 */
function napurelon_sichtbarkeit_einhaengen() {
	foreach ( napurelon_sichtbarkeit_taxonomien() as $taxonomie ) {
		add_action( $taxonomie . '_add_form_fields', 'napurelon_sichtbarkeit_feld_neu' );
		add_action( $taxonomie . '_edit_form_fields', 'napurelon_sichtbarkeit_feld_bearbeiten' );
		add_action( 'created_' . $taxonomie, 'napurelon_sichtbarkeit_speichern' );
		add_action( 'edited_' . $taxonomie, 'napurelon_sichtbarkeit_speichern' );
		add_filter( 'manage_edit-' . $taxonomie . '_columns', 'napurelon_sichtbarkeit_spalte' );
		add_filter( 'manage_' . $taxonomie . '_custom_column', 'napurelon_sichtbarkeit_spalte_inhalt', 10, 3 );
	}
}

add_action( 'init', 'napurelon_sichtbarkeit_einhaengen', 20 );
