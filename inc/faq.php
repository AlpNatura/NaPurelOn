<?php
/**
 * FAQ: Fragen und Antworten im Admin-Bereich unter „FAQ“ pflegen und mit
 * [napurelon_faq] als aufklappbare Liste ausgeben.
 *
 * Titel = Frage, Inhalt = Antwort, „Reihenfolge“ = Position. Gruppen kommen aus
 * „FAQ-Gruppen“ und erscheinen in der Reihenfolge ihrer ersten Frage. Mit Polylang
 * zeigt der Shortcode nur die Einträge der aktuellen Sprache.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

const NAPURELON_FAQ_TYP            = 'napurelon_faq';
const NAPURELON_FAQ_GRUPPE         = 'napurelon_faq_gruppe';
const NAPURELON_FAQ_VORLAGE_OPTION = 'napurelon_faq_vorlage_angelegt';
const NAPURELON_FAQ_HINWEIS        = 'napurelon_faq_hinweis';

/**
 * Registriert den Inhaltstyp „FAQ“ und die Taxonomie „FAQ-Gruppen“.
 */
function napurelon_faq_registrieren() {
	register_post_type(
		NAPURELON_FAQ_TYP,
		array(
			'labels'              => array(
				'name'               => 'FAQ',
				'singular_name'      => 'Frage',
				'menu_name'          => 'FAQ',
				'all_items'          => 'Alle Fragen',
				'add_new'            => 'Neue Frage',
				'add_new_item'       => 'Neue Frage hinzufügen',
				'edit_item'          => 'Frage bearbeiten',
				'new_item'           => 'Neue Frage',
				'search_items'       => 'Fragen suchen',
				'not_found'          => 'Keine Fragen gefunden',
				'not_found_in_trash' => 'Keine Fragen im Papierkorb gefunden',
				'attributes'         => 'Reihenfolge',
				'item_published'     => 'Frage veröffentlicht',
				'item_updated'       => 'Frage aktualisiert',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-editor-help',
			'menu_position'       => 21,
			'supports'            => array( 'title', 'editor', 'page-attributes', 'revisions' ),
		)
	);

	register_taxonomy(
		NAPURELON_FAQ_GRUPPE,
		array( NAPURELON_FAQ_TYP ),
		array(
			'labels'            => array(
				'name'          => 'FAQ-Gruppen',
				'singular_name' => 'FAQ-Gruppe',
				'menu_name'     => 'FAQ-Gruppen',
				'all_items'     => 'Alle FAQ-Gruppen',
				'edit_item'     => 'FAQ-Gruppe bearbeiten',
				'update_item'   => 'FAQ-Gruppe aktualisieren',
				'add_new_item'  => 'Neue FAQ-Gruppe hinzufügen',
				'new_item_name' => 'Name der neuen FAQ-Gruppe',
				'search_items'  => 'FAQ-Gruppen suchen',
				'not_found'     => 'Keine FAQ-Gruppen gefunden',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);
}

add_action( 'init', 'napurelon_faq_registrieren' );

/**
 * Admin-Liste nach „Reihenfolge“ sortieren, damit sie der Ausgabe entspricht.
 *
 * @param WP_Query $abfrage Abfrage.
 */
function napurelon_faq_admin_sortierung( $abfrage ) {
	if ( ! is_admin() || ! $abfrage->is_main_query() || NAPURELON_FAQ_TYP !== $abfrage->get( 'post_type' ) || $abfrage->get( 'orderby' ) ) {
		return;
	}

	$abfrage->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
}

add_action( 'pre_get_posts', 'napurelon_faq_admin_sortierung' );

/**
 * Spalte „Reihenfolge“ in der Admin-Liste.
 *
 * @param array $spalten Spalten.
 * @return array
 */
function napurelon_faq_spalten( $spalten ) {
	$spalten['napurelon_reihenfolge'] = 'Reihenfolge';
	return $spalten;
}

add_filter( 'manage_' . NAPURELON_FAQ_TYP . '_posts_columns', 'napurelon_faq_spalten' );

/**
 * Inhalt der Spalte „Reihenfolge“.
 *
 * @param string $spalte  Spaltenname.
 * @param int    $post_id Beitrag.
 */
function napurelon_faq_spalte_inhalt( $spalte, $post_id ) {
	if ( 'napurelon_reihenfolge' === $spalte ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}

add_action( 'manage_' . NAPURELON_FAQ_TYP . '_posts_custom_column', 'napurelon_faq_spalte_inhalt', 10, 2 );

/**
 * Shortcode [napurelon_faq]: alle veröffentlichten Fragen der aktuellen Sprache.
 *
 * @return string HTML.
 */
function napurelon_faq_shortcode() {
	$args = array(
		'post_type'              => NAPURELON_FAQ_TYP,
		'post_status'            => 'publish',
		'posts_per_page'         => 200,
		'orderby'                => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	);

	if ( function_exists( 'pll_current_language' ) ) {
		$sprache = pll_current_language();

		// Im Elementor-Editor gibt es keine aktuelle Sprache: dann die der Seite.
		if ( ! $sprache && function_exists( 'pll_get_post_language' ) && get_the_ID() ) {
			$sprache = pll_get_post_language( get_the_ID() );
		}

		if ( $sprache ) {
			$args['lang'] = $sprache;
		}
	}

	$fragen = get_posts( $args );

	if ( ! $fragen ) {
		return '';
	}

	$gruppen = array();

	foreach ( $fragen as $frage ) {
		$begriffe = get_the_terms( $frage, NAPURELON_FAQ_GRUPPE );
		$begriff  = ( $begriffe && ! is_wp_error( $begriffe ) ) ? $begriffe[0] : null;
		$schluessel = $begriff ? 'g' . $begriff->term_id : 'ohne';

		if ( ! isset( $gruppen[ $schluessel ] ) ) {
			$gruppen[ $schluessel ] = array(
				'name'   => $begriff ? $begriff->name : '',
				'fragen' => array(),
			);
		}

		$gruppen[ $schluessel ]['fragen'][] = $frage;
	}

	$pfad = get_stylesheet_directory() . '/assets/css/faq.css';
	if ( file_exists( $pfad ) ) {
		wp_enqueue_style( 'napurelon-faq', get_stylesheet_directory_uri() . '/assets/css/faq.css', array(), (string) filemtime( $pfad ) );
	}

	$html = '<div class="npo-faq" id="faq">';

	foreach ( $gruppen as $gruppe ) {
		$html .= '<div class="npo-faq__gruppe">';

		if ( '' !== $gruppe['name'] ) {
			$html .= '<h3 class="npo-faq__gruppentitel">' . esc_html( $gruppe['name'] ) . '</h3>';
		}

		foreach ( $gruppe['fragen'] as $frage ) {
			$html .= '<details class="npo-faq__eintrag">'
				. '<summary class="npo-faq__frage">' . esc_html( get_the_title( $frage ) ) . '</summary>'
				. '<div class="npo-faq__antwort">' . wpautop( wptexturize( wp_kses_post( $frage->post_content ) ) ) . '</div>'
				. '</details>';
		}

		$html .= '</div>';
	}

	return $html . '</div>';
}

add_shortcode( 'napurelon_faq', 'napurelon_faq_shortcode' );

/**
 * Startinhalt: Gruppen und Fragen auf Deutsch und Türkisch.
 *
 * @param string $datenschutz_de Adresse der deutschen Datenschutzerklärung oder leer.
 * @param string $datenschutz_tr Adresse der türkischen Datenschutzerklärung oder leer.
 * @return array[] Gruppen mit 'de', 'tr' und 'fragen' (je [ Frage DE, Antwort DE, Frage TR, Antwort TR ]).
 */
function napurelon_faq_vorlage( $datenschutz_de, $datenschutz_tr ) {
	$link = static function ( $url, $text ) {
		return $url ? '<a href="' . esc_url( $url ) . '">' . $text . '</a>' : $text;
	};

	return array(
		array(
			'de'     => 'Allgemein',
			'tr'     => 'Genel',
			'fragen' => array(
				array( 'Was ist NaPurelOn?', 'Eine Sammlung natürlicher Rezepte: Lebensmittel, Getränke, Naturkosmetik, Seifen & Reinigung, Startkulturen & Fermente sowie Grundlagen & Wissen.', 'NaPurelOn nedir?', 'Doğal tariflerden oluşan bir koleksiyon: gıdalar, içecekler, doğal kozmetik, sabun ve temizlik, maya ve fermentler ile temel bilgiler.' ),
				array( 'Kostet die Nutzung etwas?', 'Nein, alle Rezepte sind kostenlos.', 'Kullanımı ücretli mi?', 'Hayır, tüm tarifler ücretsizdir.' ),
				array( 'In welchen Sprachen gibt es NaPurelOn?', 'Auf Deutsch und Türkisch. Die Sprache wechselst du über die Flaggen oben.', 'NaPurelOn hangi dillerde var?', 'Almanca ve Türkçe. Dili sayfanın üst kısmındaki bayraklardan değiştirebilirsiniz.' ),
			),
		),
		array(
			'de'     => 'Rezepte einreichen',
			'tr'     => 'Tarif gönderme',
			'fragen' => array(
				array( 'Kann ich eigene Rezepte einreichen?', 'Ja. Mit einem kostenlosen Konto kannst du Rezepte über das Formular einreichen.', 'Kendi tariflerimi gönderebilir miyim?', 'Evet. Ücretsiz bir hesapla tariflerinizi form üzerinden gönderebilirsiniz.' ),
				array( 'Wann wird mein Rezept veröffentlicht?', 'Wir prüfen jedes Rezept vor der Veröffentlichung. Sobald es online ist, bekommst du eine E-Mail.', 'Tarifim ne zaman yayınlanır?', 'Her tarifi yayınlamadan önce inceliyoruz. Tarifiniz yayına girdiğinde size bir e-posta göndeririz.' ),
				array( 'Welche Bilder darf ich hochladen?', 'Nur eigene Fotos oder Fotos, an denen du die Rechte besitzt. Erlaubt sind JPG, PNG oder WebP mit höchstens 5 MB pro Bild und bis zu 5 Galeriebildern.', 'Hangi fotoğrafları yükleyebilirim?', 'Yalnızca kendi çektiğiniz veya haklarına sahip olduğunuz fotoğrafları. JPG, PNG veya WebP formatında, fotoğraf başına en fazla 5 MB ve en fazla 5 galeri fotoğrafı yükleyebilirsiniz.' ),
				array( 'Wo sehe ich meine eingereichten Rezepte?', 'In deinem Konto unter „Meine Rezepte“.', 'Gönderdiğim tarifleri nerede görebilirim?', 'Hesabınızda „Tariflerim“ bölümünde.' ),
				array( 'Kann ich mein veröffentlichtes Rezept löschen lassen?', 'Veröffentlichte Rezepte bleiben auf NaPurelOn. Auf Wunsch entfernen wir aber deinen Namen.', 'Yayınlanan tarifimi sildirebilir miyim?', 'Yayınlanan tarifler NaPurelOn\'da kalır. İsterseniz adınızı tariften kaldırırız.' ),
			),
		),
		array(
			'de'     => 'Konto & Datenschutz',
			'tr'     => 'Hesap ve gizlilik',
			'fragen' => array(
				array( 'Brauche ich ein Konto?', 'Nur zum Einreichen von Rezepten. Zum Lesen brauchst du keins.', 'Hesaba ihtiyacım var mı?', 'Sadece tarif göndermek için. Tarifleri okumak için hesap gerekmez.' ),
				array( 'Wie lösche ich mein Konto?', 'In deinem Konto unter „Kontodetails“ → „Konto löschen“.', 'Hesabımı nasıl silerim?', 'Hesabınızda „Hesap detayları“ → „Hesabı sil“ bölümünden.' ),
				array( 'Was passiert mit meinen Daten?', 'Alle Informationen findest du in unserer ' . $link( $datenschutz_de, 'Datenschutzerklärung' ) . '.', 'Verilerime ne oluyor?', 'Tüm bilgileri ' . $link( $datenschutz_tr, 'Gizlilik Politikamızda' ) . ' bulabilirsiniz.' ),
			),
		),
		array(
			'de'     => 'Shop',
			'tr'     => 'Mağaza',
			'fragen' => array(
				array( 'Gibt es einen Shop?', 'Der Shop ist demnächst verfügbar.', 'Bir mağaza var mı?', 'Mağaza yakında açılacak.' ),
			),
		),
		array(
			'de'     => 'Sicherheit & Haftung',
			'tr'     => 'Güvenlik ve sorumluluk',
			'fragen' => array(
				array( 'Ersetzen die Rezepte ärztlichen Rat?', 'Nein. Die Rezepte ersetzen keine ärztliche oder fachliche Beratung.', 'Tarifler doktor tavsiyesinin yerini tutar mı?', 'Hayır. Tarifler tıbbi veya uzman tavsiyesinin yerini tutmaz.' ),
				array( 'Was muss ich bei Allergien beachten?', 'Prüfe die Zutaten. Kosmetik solltest du vorher an einer kleinen Hautstelle testen.', 'Alerjim varsa nelere dikkat etmeliyim?', 'Malzemeleri kontrol edin. Kozmetik ürünleri kullanmadan önce cildinizin küçük bir bölgesinde deneyin.' ),
			),
		),
		array(
			'de'     => 'Kontakt',
			'tr'     => 'İletişim',
			'fragen' => array(
				array( 'Wie schnell antwortet ihr?', 'In der Regel innerhalb weniger Tage.', 'Ne kadar sürede cevap veriyorsunuz?', 'Genellikle birkaç gün içinde.' ),
			),
		),
	);
}

/**
 * Legt eine FAQ-Gruppe in einer Sprache an oder findet sie.
 *
 * @param string      $name    Name.
 * @param string|null $sprache Polylang-Sprache oder null.
 * @return int Term-ID oder 0.
 */
function napurelon_faq_gruppe_anlegen( $name, $sprache ) {
	$vorhanden = get_term_by( 'name', $name, NAPURELON_FAQ_GRUPPE );
	if ( $vorhanden ) {
		return (int) $vorhanden->term_id;
	}

	$term = wp_insert_term( $name, NAPURELON_FAQ_GRUPPE, array( 'slug' => sanitize_title( $name . ( $sprache ? '-' . $sprache : '' ) ) ) );
	if ( is_wp_error( $term ) ) {
		return 0;
	}

	if ( $sprache && function_exists( 'pll_set_term_language' ) ) {
		pll_set_term_language( (int) $term['term_id'], $sprache );
	}

	return (int) $term['term_id'];
}

/**
 * Legt eine Frage in einer Sprache an.
 *
 * @param string      $frage       Frage.
 * @param string      $antwort     Antwort (HTML).
 * @param int         $reihenfolge Position.
 * @param int         $gruppe      Term-ID.
 * @param string|null $sprache     Polylang-Sprache oder null.
 * @return int Beitrags-ID oder 0.
 */
function napurelon_faq_frage_anlegen( $frage, $antwort, $reihenfolge, $gruppe, $sprache ) {
	$id = wp_insert_post(
		array(
			'post_type'    => NAPURELON_FAQ_TYP,
			'post_status'  => 'publish',
			'post_title'   => $frage,
			'post_content' => $antwort,
			'menu_order'   => $reihenfolge,
		),
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	if ( $sprache && function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $id, $sprache );
	}

	if ( $gruppe ) {
		wp_set_object_terms( $id, array( $gruppe ), NAPURELON_FAQ_GRUPPE );
	}

	return (int) $id;
}

/**
 * Legt den Startinhalt einmalig an, wenn ein Administrator den Admin-Bereich
 * aufruft und noch keine Fragen existieren.
 */
function napurelon_faq_vorlage_anlegen() {
	if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'manage_options' ) || get_option( NAPURELON_FAQ_VORLAGE_OPTION ) ) {
		return;
	}

	$mit_sprachen = function_exists( 'pll_languages_list' ) && function_exists( 'pll_save_post_translations' );
	$sprachen     = $mit_sprachen ? (array) pll_languages_list( array( 'fields' => 'slug' ) ) : array();

	if ( $mit_sprachen && ! in_array( 'de', $sprachen, true ) ) {
		return;
	}

	$vorhanden = get_posts(
		array(
			'post_type'      => NAPURELON_FAQ_TYP,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'lang'           => '',
		)
	);

	update_option( NAPURELON_FAQ_VORLAGE_OPTION, 1, false );

	if ( $vorhanden ) {
		return;
	}

	$de = $mit_sprachen ? 'de' : null;
	$tr = ( $mit_sprachen && in_array( 'tr', $sprachen, true ) ) ? 'tr' : null;

	$seite_de       = (int) get_option( 'wp_page_for_privacy_policy' );
	$seite_tr       = ( $seite_de && $tr && function_exists( 'pll_get_post' ) ) ? (int) pll_get_post( $seite_de, 'tr' ) : 0;
	$datenschutz_de = $seite_de && 'publish' === get_post_status( $seite_de ) ? get_permalink( $seite_de ) : '';
	$datenschutz_tr = $seite_tr && 'publish' === get_post_status( $seite_tr ) ? get_permalink( $seite_tr ) : '';

	$reihenfolge = 0;
	$anzahl      = 0;

	foreach ( napurelon_faq_vorlage( $datenschutz_de, $datenschutz_tr ) as $gruppe ) {
		$gruppe_de = napurelon_faq_gruppe_anlegen( $gruppe['de'], $de );
		$gruppe_tr = $tr ? napurelon_faq_gruppe_anlegen( $gruppe['tr'], $tr ) : 0;

		if ( $gruppe_de && $gruppe_tr && function_exists( 'pll_save_term_translations' ) ) {
			pll_save_term_translations( array( 'de' => $gruppe_de, 'tr' => $gruppe_tr ) );
		}

		foreach ( $gruppe['fragen'] as $eintrag ) {
			$reihenfolge += 10;

			$id_de = napurelon_faq_frage_anlegen( $eintrag[0], $eintrag[1], $reihenfolge, $gruppe_de, $de );
			$id_tr = $tr ? napurelon_faq_frage_anlegen( $eintrag[2], $eintrag[3], $reihenfolge, $gruppe_tr, $tr ) : 0;

			if ( $id_de && $id_tr ) {
				pll_save_post_translations( array( 'de' => $id_de, 'tr' => $id_tr ) );
			}

			$anzahl += (int) (bool) $id_de + (int) (bool) $id_tr;
		}
	}

	set_transient( NAPURELON_FAQ_HINWEIS, $anzahl, DAY_IN_SECONDS );
}

add_action( 'admin_init', 'napurelon_faq_vorlage_anlegen', 40 );

/**
 * Meldet einmal, dass der Startinhalt angelegt wurde.
 */
function napurelon_faq_hinweis() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$anzahl = get_transient( NAPURELON_FAQ_HINWEIS );

	if ( false === $anzahl ) {
		return;
	}

	delete_transient( NAPURELON_FAQ_HINWEIS );
	?>
	<div class="notice notice-success is-dismissible">
		<p><strong>NaPurelOn:</strong> <?php echo esc_html( sprintf( '%d FAQ-Einträge angelegt (Deutsch und Türkçe). Du findest sie links unter „FAQ“.', (int) $anzahl ) ); ?></p>
	</div>
	<?php
}

add_action( 'admin_notices', 'napurelon_faq_hinweis' );
