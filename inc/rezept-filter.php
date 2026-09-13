<?php
/**
 * Globale Rezeptfilter (Menüart, Region, Zeitaufwand, Saisonalität, Anlass,
 * Temperament, Ernährungsweise) und die Filterzeile der Kategorieseite.
 *
 * Die Merkmale sind global definiert; je Kategorie wird nur festgelegt,
 * welche davon dort benutzt werden. Nicht benutzte Filter bleiben sichtbar,
 * sind aber ausgegraut und nicht anklickbar.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/** Term-Meta: in dieser Kategorie benutzte Filter. */
const NAPURELON_KAT_FILTER = 'napurelon_kat_filter';

/** Präfix der Filterparameter in der Adresszeile. */
const NAPURELON_FILTER_PREFIX = 'f_';

/**
 * Beschreibt alle Filter der Rezeptseiten.
 *
 * "taxonomie" verweist auf eine Merkmals-Taxonomie, "art" unterscheidet die
 * Sonderfälle: Unterkategorien der aktuellen Kategorie und die aus
 * Zubereitungs- und Kochzeit berechnete Zeitspanne.
 *
 * @return array<string,array{label:string,art:string,taxonomie?:string}> Filter.
 */
function napurelon_filter_definitionen() {
	return array(
		'kategorie'   => array(
			'label' => 'Kategorie',
			'art'   => 'unterkategorie',
		),
		'menueart'    => array(
			'label'     => 'Menüart',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_menueart',
		),
		'region'      => array(
			'label'     => 'Region',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_region',
		),
		'zeitaufwand' => array(
			'label' => 'Zeitaufwand',
			'art'   => 'zeit',
		),
		'saison'      => array(
			'label'     => 'Saisonalität',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_saison',
		),
		'anlass'      => array(
			'label'     => 'Anlass',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_anlass',
		),
		'temperament' => array(
			'label'     => 'Temperament',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_temperament',
		),
		'ernaehrung'  => array(
			'label'     => 'Ernährungsweise',
			'art'       => 'taxonomie',
			'taxonomie' => 'rezept_ernaehrung',
		),
	);
}

/**
 * Zeitspannen des Filters "Zeitaufwand" in Minuten.
 *
 * @return array<string,array{label:string,von:int,bis:int}> Spannen.
 */
function napurelon_filter_zeitspannen() {
	return array(
		'bis15'   => array(
			'label' => 'bis 15 Minuten',
			'von'   => 0,
			'bis'   => 15,
		),
		'bis30'   => array(
			'label' => '15 bis 30 Minuten',
			'von'   => 16,
			'bis'   => 30,
		),
		'bis60'   => array(
			'label' => '30 bis 60 Minuten',
			'von'   => 31,
			'bis'   => 60,
		),
		'ueber60' => array(
			'label' => 'über 60 Minuten',
			'von'   => 61,
			'bis'   => PHP_INT_MAX,
		),
	);
}

/**
 * Registriert die Merkmals-Taxonomien der Rezepte.
 *
 * Die Taxonomien haben keine eigenen Archivseiten (public = false), weil
 * gefiltert wird und nicht navigiert.
 */
function napurelon_register_filter_taxonomien() {
	$taxonomien = array(
		'rezept_menueart'    => array( 'Menüarten', 'Menüart' ),
		'rezept_region'      => array( 'Regionen', 'Region' ),
		'rezept_saison'      => array( 'Saisonalität', 'Saison' ),
		'rezept_anlass'      => array( 'Anlässe', 'Anlass' ),
		'rezept_temperament' => array( 'Temperament', 'Temperament' ),
		'rezept_ernaehrung'  => array( 'Ernährungsweisen', 'Ernährungsweise' ),
	);

	foreach ( $taxonomien as $name => $bezeichnung ) {
		register_taxonomy(
			$name,
			array( 'rezepte' ),
			array(
				'labels'            => array(
					'name'          => $bezeichnung[0],
					'singular_name' => $bezeichnung[1],
					'menu_name'     => $bezeichnung[0],
					'all_items'     => 'Alle Einträge',
					'edit_item'     => $bezeichnung[1] . ' bearbeiten',
					'add_new_item'  => $bezeichnung[1] . ' hinzufügen',
					'new_item_name' => 'Name',
					'search_items'  => $bezeichnung[0] . ' suchen',
					'not_found'     => 'Keine Einträge gefunden',
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				// Hierarchisch, damit der Editor eine Auswahlliste mit Kästchen zeigt.
				'hierarchical'      => true,
			)
		);
	}
}

add_action( 'init', 'napurelon_register_filter_taxonomien' );

/**
 * Liefert die in einer Kategorie benutzten Filterschlüssel.
 *
 * Ohne Pflege sind alle Filter aktiv. Der Wert wird an der Hauptkategorie
 * gepflegt und gilt auch für deren Unterkategorien.
 *
 * @param WP_Term $begriff Kategorie.
 * @return string[] Schlüssel aus napurelon_filter_definitionen().
 */
function napurelon_kategorie_filter_aktiv( WP_Term $begriff ) {
	$erlaubt = array_keys( napurelon_filter_definitionen() );
	$quelle  = $begriff->term_id;

	if ( $begriff->parent ) {
		$vorfahren = get_ancestors( $begriff->term_id, 'rezeptkategorie' );
		$quelle    = ! empty( $vorfahren ) ? (int) end( $vorfahren ) : $begriff->term_id;
	}

	$gespeichert = get_term_meta( $quelle, NAPURELON_KAT_FILTER, true );

	if ( ! is_array( $gespeichert ) ) {
		return $erlaubt;
	}

	$aktiv = array_values( array_intersect( $erlaubt, array_map( 'strval', $gespeichert ) ) );

	return $aktiv;
}

/**
 * Liest die gewählten Werte eines Filters aus der Adresszeile.
 *
 * @param string $schluessel Filterschlüssel.
 * @return string[] Bereinigte Werte.
 */
function napurelon_filter_auswahl( $schluessel ) {
	$parameter = NAPURELON_FILTER_PREFIX . $schluessel;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige, keine Datenänderung.
	if ( ! isset( $_GET[ $parameter ] ) ) {
		return array();
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige, keine Datenänderung.
	$roh = wp_unslash( $_GET[ $parameter ] );
	$roh = is_array( $roh ) ? $roh : array( $roh );

	$werte = array();

	foreach ( $roh as $wert ) {
		$wert = sanitize_title( (string) $wert );

		if ( '' !== $wert ) {
			$werte[ $wert ] = $wert;
		}
	}

	return array_values( $werte );
}

/**
 * Liest alle gültigen Filterwerte einer Kategorie.
 *
 * @param WP_Term $begriff Kategorie.
 * @return array<string,string[]> Filterschlüssel mit Werten.
 */
function napurelon_filter_auswahl_alle( WP_Term $begriff ) {
	$aktiv    = napurelon_kategorie_filter_aktiv( $begriff );
	$auswahl  = array();

	foreach ( $aktiv as $schluessel ) {
		$werte = napurelon_filter_auswahl( $schluessel );

		if ( ! empty( $werte ) ) {
			$auswahl[ $schluessel ] = $werte;
		}
	}

	return $auswahl;
}

/**
 * Liefert die IDs aller veröffentlichten Rezepte einer Kategorie.
 *
 * @param WP_Term $begriff Kategorie.
 * @return int[] Beitrags-IDs.
 */
function napurelon_filter_rezept_ids( WP_Term $begriff ) {
	static $zwischenspeicher = array();

	if ( isset( $zwischenspeicher[ $begriff->term_id ] ) ) {
		return $zwischenspeicher[ $begriff->term_id ];
	}

	$ids = get_posts(
		array(
			'post_type'        => 'rezepte',
			'post_status'      => 'publish',
			'posts_per_page'   => 500,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'rezeptkategorie',
					'field'            => 'term_id',
					'terms'            => $begriff->term_id,
					'include_children' => true,
				),
			),
		)
	);

	$zwischenspeicher[ $begriff->term_id ] = array_map( 'absint', $ids );

	return $zwischenspeicher[ $begriff->term_id ];
}

/**
 * Gesamtzeit eines Rezepts aus Zubereitungs- und Kochzeit.
 *
 * @param int $post_id Beitrags-ID.
 * @return int Minuten.
 */
function napurelon_filter_gesamtzeit( $post_id ) {
	$zubereitung = (int) get_post_meta( $post_id, 'napurelon_zubereitungszeit', true );
	$kochen      = (int) get_post_meta( $post_id, 'napurelon_kochzeit', true );

	return max( 0, $zubereitung ) + max( 0, $kochen );
}

/**
 * Zählt, wie viele Rezepte der Kategorie zu jeder Filteroption gehören.
 *
 * @param WP_Term $begriff Kategorie.
 * @return array<string,array<string,int>> Filterschlüssel, Wert, Anzahl.
 */
function napurelon_filter_anzahlen( WP_Term $begriff ) {
	$ids      = napurelon_filter_rezept_ids( $begriff );
	$anzahlen = array();

	if ( empty( $ids ) ) {
		return $anzahlen;
	}

	foreach ( napurelon_filter_definitionen() as $schluessel => $filter ) {
		$anzahlen[ $schluessel ] = array();

		if ( 'zeit' === $filter['art'] ) {
			update_meta_cache( 'post', $ids );

			foreach ( $ids as $id ) {
				$minuten = napurelon_filter_gesamtzeit( $id );

				if ( 0 === $minuten ) {
					continue;
				}

				foreach ( napurelon_filter_zeitspannen() as $spanne_id => $spanne ) {
					if ( $minuten >= $spanne['von'] && $minuten <= $spanne['bis'] ) {
						$anzahlen[ $schluessel ][ $spanne_id ] = isset( $anzahlen[ $schluessel ][ $spanne_id ] )
							? $anzahlen[ $schluessel ][ $spanne_id ] + 1
							: 1;
					}
				}
			}

			continue;
		}

		$taxonomie = 'unterkategorie' === $filter['art'] ? 'rezeptkategorie' : $filter['taxonomie'];
		// "all_with_object_id" liefert je Zuordnung eine Zeile, nur so lässt sich zählen.
		$zuordnung = wp_get_object_terms( $ids, $taxonomie, array( 'fields' => 'all_with_object_id' ) );

		if ( is_wp_error( $zuordnung ) ) {
			continue;
		}

		foreach ( $zuordnung as $eintrag ) {
			$anzahlen[ $schluessel ][ $eintrag->slug ] = isset( $anzahlen[ $schluessel ][ $eintrag->slug ] )
				? $anzahlen[ $schluessel ][ $eintrag->slug ] + 1
				: 1;
		}
	}

	return $anzahlen;
}

/**
 * Liefert die Optionen eines Filters.
 *
 * @param string  $schluessel Filterschlüssel.
 * @param WP_Term $begriff    Kategorie.
 * @return array<int,array{wert:string,label:string}> Optionen.
 */
function napurelon_filter_optionen( $schluessel, WP_Term $begriff ) {
	$definitionen = napurelon_filter_definitionen();

	if ( ! isset( $definitionen[ $schluessel ] ) ) {
		return array();
	}

	$filter   = $definitionen[ $schluessel ];
	$optionen = array();

	if ( 'zeit' === $filter['art'] ) {
		foreach ( napurelon_filter_zeitspannen() as $spanne_id => $spanne ) {
			$optionen[] = array(
				'wert'  => $spanne_id,
				'label' => $spanne['label'],
			);
		}

		return $optionen;
	}

	if ( 'unterkategorie' === $filter['art'] ) {
		$eltern = $begriff->parent ? $begriff->parent : $begriff->term_id;
		$kinder = get_terms(
			array(
				'taxonomy'   => 'rezeptkategorie',
				'parent'     => $eltern,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $kinder ) ) {
			return array();
		}

		foreach ( $kinder as $kind ) {
			$optionen[] = array(
				'wert'  => $kind->slug,
				'label' => $kind->name,
			);
		}

		return $optionen;
	}

	$begriffe = get_terms(
		array(
			'taxonomy'   => $filter['taxonomie'],
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $begriffe ) ) {
		return array();
	}

	foreach ( $begriffe as $eintrag ) {
		$optionen[] = array(
			'wert'  => $eintrag->slug,
			'label' => $eintrag->name,
		);
	}

	return $optionen;
}

/**
 * Ergänzt Abfrageargumente um die gewählten Filter.
 *
 * Der Zeitaufwand steht in zwei getrennten Feldern und lässt sich deshalb
 * nicht als meta_query abfragen; die passenden Rezepte werden ermittelt und
 * über post__in eingeschränkt.
 *
 * @param array   $argumente WP_Query-Argumente.
 * @param WP_Term $begriff   Kategorie.
 * @return array Ergänzte Argumente.
 */
function napurelon_filter_abfrage( array $argumente, WP_Term $begriff ) {
	$auswahl      = napurelon_filter_auswahl_alle( $begriff );
	$definitionen = napurelon_filter_definitionen();

	if ( empty( $auswahl ) ) {
		return $argumente;
	}

	$tax_query = isset( $argumente['tax_query'] ) ? $argumente['tax_query'] : array();

	foreach ( $auswahl as $schluessel => $werte ) {
		$filter = $definitionen[ $schluessel ];

		if ( 'taxonomie' === $filter['art'] ) {
			$tax_query[] = array(
				'taxonomy' => $filter['taxonomie'],
				'field'    => 'slug',
				'terms'    => $werte,
				'operator' => 'IN',
			);

			continue;
		}

		if ( 'unterkategorie' === $filter['art'] ) {
			$tax_query[] = array(
				'taxonomy'         => 'rezeptkategorie',
				'field'            => 'slug',
				'terms'            => $werte,
				'include_children' => true,
			);
		}
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	$argumente['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

	if ( isset( $auswahl['zeitaufwand'] ) ) {
		$spannen = napurelon_filter_zeitspannen();
		$treffer = array();

		$ids = napurelon_filter_rezept_ids( $begriff );
		update_meta_cache( 'post', $ids );

		foreach ( $ids as $id ) {
			$minuten = napurelon_filter_gesamtzeit( $id );

			if ( 0 === $minuten ) {
				continue;
			}

			foreach ( $auswahl['zeitaufwand'] as $spanne_id ) {
				if ( ! isset( $spannen[ $spanne_id ] ) ) {
					continue;
				}

				if ( $minuten >= $spannen[ $spanne_id ]['von'] && $minuten <= $spannen[ $spanne_id ]['bis'] ) {
					$treffer[] = $id;
					break;
				}
			}
		}

		// Ohne Treffer bleibt die Liste leer; 0 ist keine gültige Beitrags-ID.
		$argumente['post__in'] = empty( $treffer ) ? array( 0 ) : $treffer;
	}

	return $argumente;
}

/**
 * Lädt Stil und Skript der Filterzeile.
 */
function napurelon_filter_assets() {
	if ( is_admin() ) {
		return;
	}

	$dir = get_stylesheet_directory();
	$css = '/assets/css/rezeptfilter.css';
	$js  = '/assets/js/rezeptfilter.js';

	if ( file_exists( $dir . $css ) ) {
		wp_enqueue_style(
			'napurelon-rezeptfilter',
			get_stylesheet_directory_uri() . $css,
			array(),
			(string) filemtime( $dir . $css )
		);
	}

	if ( file_exists( $dir . $js ) ) {
		wp_enqueue_script(
			'napurelon-rezeptfilter',
			get_stylesheet_directory_uri() . $js,
			array(),
			(string) filemtime( $dir . $js ),
			true
		);
	}
}

add_action( 'wp_enqueue_scripts', 'napurelon_filter_assets' );

/**
 * Liest die gewählte Sortierung aus der Adresszeile.
 *
 * Die Filterleiste reicht sie als verstecktes Feld weiter, damit eine neue
 * Filterauswahl die Sortierung nicht zurücksetzt.
 *
 * @return string Schlüssel der Sortierung oder leer.
 */
function napurelon_filter_sortierung() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige, keine Datenänderung.
	$sortierung = isset( $_GET['sortierung'] ) ? sanitize_key( wp_unslash( $_GET['sortierung'] ) ) : '';

	if ( ! function_exists( 'napurelon_kategorie_sortierungen' ) ) {
		return '';
	}

	$erlaubt = napurelon_kategorie_sortierungen();

	return isset( $erlaubt[ $sortierung ] ) ? $sortierung : '';
}

/**
 * Liefert die gewählten Filter als Parameter für Links und Formulare.
 *
 * @param WP_Term $begriff Kategorie.
 * @return array<string,string[]> Parametername mit Werten.
 */
function napurelon_filter_parameter( WP_Term $begriff ) {
	$parameter = array();

	foreach ( napurelon_filter_auswahl_alle( $begriff ) as $schluessel => $werte ) {
		$parameter[ NAPURELON_FILTER_PREFIX . $schluessel ] = $werte;
	}

	return $parameter;
}

/**
 * Shortcode: einzeilige Filterleiste unter dem Hero.
 *
 * @param array $atts kategorie: Slug oder ID.
 * @return string HTML der Filterleiste.
 */
function napurelon_kategorie_filter_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '' ), $atts, 'napurelon_kategorie_filter' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$definitionen = napurelon_filter_definitionen();
	$aktiv        = napurelon_kategorie_filter_aktiv( $begriff );
	$auswahl      = napurelon_filter_auswahl_alle( $begriff );
	$anzahlen     = napurelon_filter_anzahlen( $begriff );
	$ziel         = (string) get_term_link( $begriff );
	$sortierung   = napurelon_filter_sortierung();
	$gewaehlt     = 0;

	foreach ( $auswahl as $werte ) {
		$gewaehlt += count( $werte );
	}

	ob_start();
	?>
	<form class="npo-filter" method="get" action="<?php echo esc_url( $ziel ); ?>" data-napurelon-filter>
		<?php if ( '' !== $sortierung ) : ?>
			<input type="hidden" name="sortierung" value="<?php echo esc_attr( $sortierung ); ?>">
		<?php endif; ?>
		<div class="npo-filter__leiste" role="group" aria-label="Rezepte filtern">
			<?php foreach ( $definitionen as $schluessel => $filter ) : ?>
				<?php
				$ist_aktiv = in_array( $schluessel, $aktiv, true );
				$optionen  = $ist_aktiv ? napurelon_filter_optionen( $schluessel, $begriff ) : array();
				$gewaehlte = isset( $auswahl[ $schluessel ] ) ? $auswahl[ $schluessel ] : array();
				$benutzbar = $ist_aktiv && ! empty( $optionen );
				$panel_id  = 'npo-filter-' . $schluessel;
				?>
				<div class="npo-filter__gruppe<?php echo $benutzbar ? '' : ' is-inaktiv'; ?>">
					<button
						class="npo-filter__knopf<?php echo empty( $gewaehlte ) ? '' : ' is-gewaehlt'; ?>"
						type="button"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						<?php echo $benutzbar ? '' : 'disabled title="In dieser Kategorie nicht verwendet"'; ?>
					>
						<span class="npo-filter__name"><?php echo esc_html( $filter['label'] ); ?></span>
						<?php if ( ! empty( $gewaehlte ) ) : ?>
							<span class="npo-filter__zaehler"><?php echo esc_html( (string) count( $gewaehlte ) ); ?></span>
						<?php endif; ?>
					</button>

					<?php if ( $benutzbar ) : ?>
						<div class="npo-filter__panel" id="<?php echo esc_attr( $panel_id ); ?>" hidden>
							<?php foreach ( $optionen as $option ) : ?>
								<?php $anzahl = isset( $anzahlen[ $schluessel ][ $option['wert'] ] ) ? (int) $anzahlen[ $schluessel ][ $option['wert'] ] : 0; ?>
								<label class="npo-filter__option">
									<input
										type="checkbox"
										name="<?php echo esc_attr( NAPURELON_FILTER_PREFIX . $schluessel ); ?>[]"
										value="<?php echo esc_attr( $option['wert'] ); ?>"
										<?php checked( in_array( $option['wert'], $gewaehlte, true ) ); ?>
									>
									<span class="npo-filter__label"><?php echo esc_html( $option['label'] ); ?></span>
									<span class="npo-filter__anzahl">(<?php echo esc_html( (string) $anzahl ); ?>)</span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="npo-filter__aktionen">
			<button class="npo-filter__senden" type="submit">Anwenden</button>
			<?php if ( $gewaehlt > 0 ) : ?>
				<a class="npo-filter__zuruecksetzen" href="<?php echo esc_url( $ziel ); ?>">Filter zurücksetzen</a>
			<?php endif; ?>
		</div>
	</form>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_kategorie_filter', 'napurelon_kategorie_filter_shortcode' );
