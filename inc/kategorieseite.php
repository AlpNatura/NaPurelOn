<?php
/**
 * Dynamische Kategorieseite für die Taxonomie "rezeptkategorie".
 *
 * Die Seite besteht aus Bausteinen, die einzeln als Shortcode verfügbar sind:
 * [napurelon_kategorie_hero], [napurelon_kategorie_nav],
 * [napurelon_unterkategorien], [napurelon_kategorie_rezepte] und
 * [napurelon_kategorie_empfehlungen]. Die Vorlage taxonomy-rezeptkategorie.php
 * setzt sie in der gewünschten Reihenfolge zusammen.
 *
 * Bild, Höhe des Heros, Kurztext und die drei Empfehlungen werden je Begriff
 * unter "Rezeptkategorien" gepflegt.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

const NAPURELON_KAT_BILD          = 'napurelon_kat_bild';
const NAPURELON_KAT_HOEHE         = 'napurelon_kat_hoehe';
const NAPURELON_KAT_KURZTEXT      = 'napurelon_kat_kurztext';
const NAPURELON_KAT_EMPFEHLUNGEN  = 'napurelon_kat_empfehlungen';
const NAPURELON_KAT_HOEHE_VORGABE = 280;
const NAPURELON_KAT_REZEPTE       = 12;

/**
 * Bindet das Stylesheet der Kategorieseite ein.
 */
function napurelon_kategorieseite_assets() {
	if ( is_admin() ) {
		return;
	}

	$dir = get_stylesheet_directory();
	$css = '/assets/css/kategorieseite.css';

	if ( ! file_exists( $dir . $css ) ) {
		return;
	}

	wp_enqueue_style(
		'napurelon-kategorieseite',
		get_stylesheet_directory_uri() . $css,
		array( 'napurelon' ),
		(string) filemtime( $dir . $css )
	);
}

add_action( 'wp_enqueue_scripts', 'napurelon_kategorieseite_assets' );

/**
 * Die sechs Hauptkategorien in ihrer redaktionellen Reihenfolge,
 * jeweils mit dem Kurztext, der ohne eigene Pflege im Hero erscheint.
 *
 * @return array<string,string> Name des Begriffs => Kurztext.
 */
function napurelon_kategorie_hauptnamen() {
	return array(
		'Lebensmittel'             => 'Natürliche Rezepte für den Alltag.',
		'Getränke'                 => 'Wohltuende Getränke aus natürlichen Zutaten.',
		'Naturkosmetik'            => 'Pflege aus Pflanzen und Ölen.',
		'Seifen & Reinigung'       => 'Natürlich sauber – ohne Zusätze.',
		'Startkulturen & Fermente' => 'Lebendige Kulturen selbst vorbereiten.',
		'Grundlagen & Wissen'      => 'Verstehen statt nur nachmachen.',
	);
}

/**
 * Liefert die vorhandenen Hauptkategorien als Begriffe.
 *
 * Es werden ausschliesslich die sechs festgelegten Kategorien beruecksichtigt,
 * damit weitere Begriffe auf oberster Ebene die Navigation nicht aufblaehen.
 *
 * @param int[] $ausschluss Begriffs-IDs, die uebersprungen werden.
 * @return WP_Term[] Begriffe in redaktioneller Reihenfolge.
 */
function napurelon_kategorie_hauptkategorien( array $ausschluss = array() ) {
	$begriffe = array();

	foreach ( array_keys( napurelon_kategorie_hauptnamen() ) as $name ) {
		$begriff = get_term_by( 'name', $name, 'rezeptkategorie' );

		if ( ! $begriff instanceof WP_Term ) {
			$begriff = get_term_by( 'slug', sanitize_title( $name ), 'rezeptkategorie' );
		}

		if ( ! $begriff instanceof WP_Term || in_array( $begriff->term_id, $ausschluss, true ) ) {
			continue;
		}

		$begriffe[] = $begriff;
	}

	return $begriffe;
}

/**
 * Haelt die Seitengroesse der Hauptabfrage gleich gross wie das Raster,
 * damit "Seite 2" der Kategorieseite nicht auf 404 laeuft.
 *
 * @param WP_Query $abfrage Abfrage.
 */
function napurelon_kategorie_seitengroesse( $abfrage ) {
	if ( is_admin() || ! $abfrage->is_main_query() || ! $abfrage->is_tax( 'rezeptkategorie' ) ) {
		return;
	}

	$abfrage->set( 'posts_per_page', NAPURELON_KAT_REZEPTE );
}

add_action( 'pre_get_posts', 'napurelon_kategorie_seitengroesse' );

/**
 * Liefert den Begriff, auf den sich die Bausteine beziehen.
 *
 * @param array $atts Shortcode-Attribute, optional "kategorie" (Slug oder ID).
 * @return WP_Term|null Begriff oder null.
 */
function napurelon_kategorie_begriff( $atts = array() ) {
	$wunsch = isset( $atts['kategorie'] ) ? trim( (string) $atts['kategorie'] ) : '';

	if ( '' !== $wunsch ) {
		$begriff = is_numeric( $wunsch )
			? get_term( absint( $wunsch ), 'rezeptkategorie' )
			: get_term_by( 'slug', sanitize_title( $wunsch ), 'rezeptkategorie' );

		return ( $begriff instanceof WP_Term ) ? $begriff : null;
	}

	if ( is_tax( 'rezeptkategorie' ) ) {
		$begriff = get_queried_object();

		return ( $begriff instanceof WP_Term ) ? $begriff : null;
	}

	if ( napurelon_kategorie_ist_vorlage() ) {
		$haupt = napurelon_kategorie_hauptkategorien();

		return isset( $haupt[0] ) ? $haupt[0] : null;
	}

	return null;
}

/**
 * Prueft, ob gerade die Vorlagenseite selbst angezeigt oder bearbeitet wird.
 *
 * Dort zeigen die Bausteine die erste Hauptkategorie, damit sich das Layout
 * in Elementor mit echten Inhalten aufbauen laesst.
 *
 * @return bool Wahr auf der Vorlagenseite.
 */
function napurelon_kategorie_ist_vorlage() {
	if ( ! function_exists( 'napurelon_kategorie_vorlage_id' ) ) {
		return false;
	}

	$vorlage = napurelon_kategorie_vorlage_id();

	return $vorlage > 0 && get_queried_object_id() === $vorlage;
}

/**
 * Liest den Kurztext einer Kategorie, ersatzweise die Beschreibung.
 *
 * @param WP_Term $begriff Begriff.
 * @return string Kurztext.
 */
function napurelon_kategorie_kurztext( WP_Term $begriff ) {
	$kurztext = (string) get_term_meta( $begriff->term_id, NAPURELON_KAT_KURZTEXT, true );

	if ( '' === $kurztext ) {
		$kurztext = wp_strip_all_tags( (string) $begriff->description );
	}

	if ( '' === $kurztext ) {
		$vorgaben = napurelon_kategorie_hauptnamen();
		$kurztext = isset( $vorgaben[ $begriff->name ] ) ? $vorgaben[ $begriff->name ] : '';
	}

	return $kurztext;
}

/**
 * Shortcode: nur der Name der Kategorie.
 *
 * Fuer einen selbst gebauten Hero, in dem Ueberschrift, Text und Hintergrund
 * getrennt gestaltet werden.
 *
 * @param array $atts kategorie: Slug oder ID, tag: HTML-Element (Vorgabe h1).
 * @return string HTML der Ueberschrift.
 */
function napurelon_kategorie_titel_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '', 'tag' => 'h1' ), $atts, 'napurelon_kategorie_titel' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$erlaubt = array( 'h1', 'h2', 'h3', 'h4', 'p', 'span', 'div' );
	$tag     = in_array( strtolower( $atts['tag'] ), $erlaubt, true ) ? strtolower( $atts['tag'] ) : 'h1';

	return sprintf(
		'<%1$s class="npo-kattitel">%2$s</%1$s>',
		$tag,
		esc_html( $begriff->name )
	);
}

add_shortcode( 'napurelon_kategorie_titel', 'napurelon_kategorie_titel_shortcode' );

/**
 * Shortcode: nur der Kurztext der Kategorie.
 *
 * @param array $atts kategorie: Slug oder ID, tag: HTML-Element (Vorgabe p).
 * @return string HTML des Kurztexts.
 */
function napurelon_kategorie_text_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '', 'tag' => 'p' ), $atts, 'napurelon_kategorie_text' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$kurztext = napurelon_kategorie_kurztext( $begriff );

	if ( '' === $kurztext ) {
		return '';
	}

	$erlaubt = array( 'p', 'span', 'div', 'h2', 'h3' );
	$tag     = in_array( strtolower( $atts['tag'] ), $erlaubt, true ) ? strtolower( $atts['tag'] ) : 'p';

	return sprintf(
		'<%1$s class="npo-kattext">%2$s</%1$s>',
		$tag,
		esc_html( $kurztext )
	);
}

add_shortcode( 'napurelon_kategorie_text', 'napurelon_kategorie_text_shortcode' );

/**
 * Setzt das Kategoriebild als Hintergrund fuer eigene Bausteine.
 *
 * Ein Element mit der Klasse "npo-kat-bild" (etwa ein Elementor-Container)
 * bekommt das Bild der aufgerufenen Kategorie als Hintergrund; Overlay,
 * Hoehe und alles Weitere bleiben in Elementor einstellbar. "!important"
 * ist noetig, weil Elementor eigene Hintergrundregeln mit hoher
 * Spezifitaet ausgibt.
 */
function napurelon_kategorie_bild_stil() {
	$begriff = napurelon_kategorie_begriff();

	if ( ! $begriff ) {
		return;
	}

	$bild_id = (int) get_term_meta( $begriff->term_id, NAPURELON_KAT_BILD, true );
	$bild    = $bild_id ? wp_get_attachment_image_url( $bild_id, 'full' ) : '';

	if ( '' === $bild ) {
		return;
	}

	printf(
		'<style id="napurelon-kategoriebild">.npo-kat-bild{background-image:url(\'%s\') !important;background-size:cover;background-position:center center;}</style>',
		esc_url( $bild )
	);
}

add_action( 'wp_head', 'napurelon_kategorie_bild_stil' );

/**
 * Shortcode: Hero mit Bild, Kategoriename und Kurztext.
 *
 * @param array $atts kategorie: Slug oder ID, hoehe: Höhe in Pixeln.
 * @return string HTML des Heros.
 */
function napurelon_kategorie_hero_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '', 'hoehe' => '' ), $atts, 'napurelon_kategorie_hero' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$hoehe = '' !== $atts['hoehe']
		? absint( $atts['hoehe'] )
		: (int) get_term_meta( $begriff->term_id, NAPURELON_KAT_HOEHE, true );

	if ( $hoehe < 120 || $hoehe > 800 ) {
		$hoehe = NAPURELON_KAT_HOEHE_VORGABE;
	}

	$bild_id = (int) get_term_meta( $begriff->term_id, NAPURELON_KAT_BILD, true );
	$bild    = $bild_id ? wp_get_attachment_image_url( $bild_id, 'full' ) : '';

	$stil = sprintf( 'min-height:%dpx;', $hoehe );

	if ( '' !== $bild ) {
		$stil .= sprintf( "background-image:url('%s');", esc_url( $bild ) );
	}

	$kurztext = napurelon_kategorie_kurztext( $begriff );

	ob_start();
	?>
	<section class="npo-kathero<?php echo '' === $bild ? ' npo-kathero--ohne-bild' : ''; ?>" style="<?php echo esc_attr( $stil ); ?>">
		<div class="npo-kathero__inhalt">
			<h1 class="npo-kathero__titel"><?php echo esc_html( $begriff->name ); ?></h1>
			<?php if ( '' !== $kurztext ) : ?>
				<p class="npo-kathero__text"><?php echo esc_html( $kurztext ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_kategorie_hero', 'napurelon_kategorie_hero_shortcode' );

/**
 * Shortcode: schmale Navigation über alle Hauptkategorien.
 *
 * @param array $atts kategorie: aktiver Begriff (Slug oder ID).
 * @return string HTML der Navigation.
 */
function napurelon_kategorie_nav_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '' ), $atts, 'napurelon_kategorie_nav' );
	$aktiv    = napurelon_kategorie_begriff( $atts );
	$begriffe = napurelon_kategorie_hauptkategorien();

	if ( empty( $begriffe ) ) {
		return '';
	}

	$aktiv_pfad = array();

	if ( $aktiv ) {
		$aktiv_pfad   = get_ancestors( $aktiv->term_id, 'rezeptkategorie' );
		$aktiv_pfad[] = $aktiv->term_id;
	}

	ob_start();
	?>
	<nav class="npo-katnav" aria-label="Alle Kategorien">
		<ul class="npo-katnav__liste">
			<?php foreach ( $begriffe as $begriff ) : ?>
				<?php $ist_aktiv = in_array( $begriff->term_id, $aktiv_pfad, true ); ?>
				<li class="npo-katnav__eintrag">
					<a
						class="npo-katnav__link<?php echo $ist_aktiv ? ' is-aktiv' : ''; ?>"
						href="<?php echo esc_url( (string) get_term_link( $begriff ) ); ?>"
						<?php echo $ist_aktiv ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $begriff->name ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_kategorie_nav', 'napurelon_kategorie_nav_shortcode' );

/**
 * Shortcode: Unterkategorien der aktuellen Kategorie als Chips.
 *
 * Ohne Unterkategorien bleibt der Bereich leer.
 *
 * @param array $atts kategorie: Slug oder ID.
 * @return string HTML der Unterkategorien.
 */
function napurelon_unterkategorien_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '' ), $atts, 'napurelon_unterkategorien' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$eltern = $begriff->parent ? $begriff->parent : $begriff->term_id;

	$kinder = get_terms(
		array(
			'taxonomy'   => 'rezeptkategorie',
			'parent'     => $eltern,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $kinder ) || empty( $kinder ) ) {
		return '';
	}

	ob_start();
	?>
	<nav class="npo-unterkat" aria-label="Unterkategorien">
		<ul class="npo-unterkat__liste">
			<?php foreach ( $kinder as $kind ) : ?>
				<?php $ist_aktiv = ( $kind->term_id === $begriff->term_id ); ?>
				<li class="npo-unterkat__eintrag">
					<a
						class="npo-unterkat__chip<?php echo $ist_aktiv ? ' is-aktiv' : ''; ?>"
						href="<?php echo esc_url( (string) get_term_link( $kind ) ); ?>"
						<?php echo $ist_aktiv ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $kind->name ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_unterkategorien', 'napurelon_unterkategorien_shortcode' );

/**
 * Liefert die erlaubten Sortierungen der Kategorieseite.
 *
 * @return array<string,array{label:string,orderby:string,order:string,meta_key?:string}> Sortierungen.
 */
function napurelon_kategorie_sortierungen() {
	return array(
		'neu'      => array(
			'label'   => 'Neueste zuerst',
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		'alt'      => array(
			'label'   => 'Älteste zuerst',
			'orderby' => 'date',
			'order'   => 'ASC',
		),
		'beliebt'  => array(
			'label'    => 'Beliebteste',
			'orderby'  => 'meta_value_num',
			'order'    => 'DESC',
			'meta_key' => NAPURELON_LIKES_META_KEY,
		),
		'titel'    => array(
			'label'   => 'Titel A–Z',
			'orderby' => 'title',
			'order'   => 'ASC',
		),
	);
}

/**
 * Shortcode: Überschrift, Anzahl, Sortierung und Rezeptraster einer Kategorie.
 *
 * @param array $atts kategorie: Slug oder ID, anzahl: Rezepte pro Seite.
 * @return string HTML des Abschnitts.
 */
function napurelon_kategorie_rezepte_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '', 'anzahl' => NAPURELON_KAT_REZEPTE ), $atts, 'napurelon_kategorie_rezepte' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$anzahl        = min( 48, max( 1, absint( $atts['anzahl'] ) ) );
	$sortierungen  = napurelon_kategorie_sortierungen();
	$sortierung    = isset( $_GET['sortierung'] ) ? sanitize_key( wp_unslash( $_GET['sortierung'] ) ) : 'neu'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige, keine Datenänderung.
	$sortierung    = isset( $sortierungen[ $sortierung ] ) ? $sortierung : 'neu';
	$einstellung   = $sortierungen[ $sortierung ];
	$seite         = max( 1, (int) get_query_var( 'paged' ) );

	$argumente = array(
		'post_type'      => 'rezepte',
		'post_status'    => 'publish',
		'posts_per_page' => $anzahl,
		'paged'          => $seite,
		'orderby'        => $einstellung['orderby'],
		'order'          => $einstellung['order'],
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy'         => 'rezeptkategorie',
				'field'            => 'term_id',
				'terms'            => $begriff->term_id,
				'include_children' => true,
			),
		),
	);

	if ( isset( $einstellung['meta_key'] ) ) {
		$argumente['meta_key'] = $einstellung['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}

	$abfrage = new WP_Query( $argumente );
	$gesamt  = (int) $abfrage->found_posts;

	ob_start();
	?>
	<section class="npo-katrezepte">
		<div class="npo-katrezepte__kopf">
			<h2 class="npo-katrezepte__titel"><?php echo esc_html( $begriff->name . ' Rezepte' ); ?></h2>
			<p class="npo-katrezepte__anzahl"><?php echo esc_html( sprintf( '%d %s', $gesamt, 1 === $gesamt ? 'Rezept' : 'Rezepte' ) ); ?></p>

			<form class="npo-katrezepte__sortierung" method="get" action="<?php echo esc_url( (string) get_term_link( $begriff ) ); ?>">
				<label class="npo-katrezepte__label" for="npo-sortierung">Sortieren</label>
				<select class="npo-katrezepte__auswahl" id="npo-sortierung" name="sortierung">
					<?php foreach ( $sortierungen as $schluessel => $eintrag ) : ?>
						<option value="<?php echo esc_attr( $schluessel ); ?>" <?php selected( $schluessel, $sortierung ); ?>>
							<?php echo esc_html( $eintrag['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button class="npo-katrezepte__senden" type="submit">Anwenden</button>
			</form>
		</div>

		<?php if ( $abfrage->have_posts() ) : ?>
			<ul class="npo-rezeptkarten">
				<?php foreach ( $abfrage->posts as $beitrag ) : ?>
					<?php echo napurelon_rezeptkarte( $beitrag->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karte escaped selbst. ?>
				<?php endforeach; ?>
			</ul>

			<?php
			$blaettern = paginate_links(
				array(
					'total'     => (int) $abfrage->max_num_pages,
					'current'   => $seite,
					'add_args'  => array( 'sortierung' => $sortierung ),
					'prev_text' => 'Zurück',
					'next_text' => 'Weiter',
					'type'      => 'plain',
				)
			);
			?>

			<?php if ( $blaettern ) : ?>
				<nav class="npo-katrezepte__blaettern" aria-label="Weitere Rezepte">
					<?php echo wp_kses_post( $blaettern ); ?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<p class="npo-katrezepte__leer">In dieser Kategorie gibt es noch keine Rezepte.</p>
		<?php endif; ?>
	</section>
	<?php

	wp_reset_postdata();

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_kategorie_rezepte', 'napurelon_kategorie_rezepte_shortcode' );

/**
 * Liest die redaktionell gepflegten Empfehlungen einer Kategorie.
 *
 * Ohne Pflege stehen die nächsten Hauptkategorien in der vorhandenen
 * Reihenfolge zur Verfügung.
 *
 * @param WP_Term $begriff Aktuelle Kategorie.
 * @return WP_Term[] Bis zu drei Begriffe.
 */
function napurelon_kategorie_empfehlungen( WP_Term $begriff ) {
	$gespeichert = get_term_meta( $begriff->term_id, NAPURELON_KAT_EMPFEHLUNGEN, true );
	$empfehlungen = array();

	if ( is_array( $gespeichert ) ) {
		foreach ( $gespeichert as $term_id ) {
			$kandidat = get_term( absint( $term_id ), 'rezeptkategorie' );

			if ( $kandidat instanceof WP_Term && $kandidat->term_id !== $begriff->term_id ) {
				$empfehlungen[ $kandidat->term_id ] = $kandidat;
			}
		}
	}

	if ( count( $empfehlungen ) >= 3 ) {
		return array_slice( array_values( $empfehlungen ), 0, 3 );
	}

	$ausschluss = array_merge(
		array( $begriff->term_id ),
		get_ancestors( $begriff->term_id, 'rezeptkategorie' ),
		array_keys( $empfehlungen )
	);

	$weitere = array_slice(
		napurelon_kategorie_hauptkategorien( array_map( 'absint', $ausschluss ) ),
		0,
		3 - count( $empfehlungen )
	);

	foreach ( $weitere as $kandidat ) {
		$empfehlungen[ $kandidat->term_id ] = $kandidat;
	}

	return array_slice( array_values( $empfehlungen ), 0, 3 );
}

/**
 * Shortcode: drei Kategoriekarten als Weiterlesen-Empfehlung.
 *
 * @param array $atts kategorie: Slug oder ID.
 * @return string HTML des Abschnitts.
 */
function napurelon_kategorie_empfehlungen_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'kategorie' => '' ), $atts, 'napurelon_kategorie_empfehlungen' );
	$begriff = napurelon_kategorie_begriff( $atts );

	if ( ! $begriff ) {
		return '';
	}

	$empfehlungen = napurelon_kategorie_empfehlungen( $begriff );

	if ( empty( $empfehlungen ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="npo-katempfehlung">
		<h2 class="npo-katempfehlung__titel">Vielleicht interessiert dich auch …</h2>

		<ul class="npo-katempfehlung__liste">
			<?php foreach ( $empfehlungen as $empfehlung ) : ?>
				<?php
				$bild_id = (int) get_term_meta( $empfehlung->term_id, NAPURELON_KAT_BILD, true );
				$bild    = $bild_id ? wp_get_attachment_image_url( $bild_id, 'medium_large' ) : '';
				$stil    = '' !== $bild ? sprintf( "background-image:url('%s');", esc_url( $bild ) ) : '';
				?>
				<li class="npo-katempfehlung__eintrag">
					<a class="npo-katempfehlung__karte" href="<?php echo esc_url( (string) get_term_link( $empfehlung ) ); ?>" style="<?php echo esc_attr( $stil ); ?>">
						<span class="npo-katempfehlung__name"><?php echo esc_html( $empfehlung->name ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php

	return (string) ob_get_clean();
}

add_shortcode( 'napurelon_kategorie_empfehlungen', 'napurelon_kategorie_empfehlungen_shortcode' );
