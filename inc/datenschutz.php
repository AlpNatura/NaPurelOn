<?php
/**
 * Datenschutzerklärung: Abschnitte im Admin-Bereich unter „Datenschutz“ pflegen
 * und mit [napurelon_datenschutz] ausgeben.
 *
 * Titel = Überschrift (ohne Nummer, sie wird aus der Reihenfolge erzeugt),
 * Inhalt = Text, „Reihenfolge“ = Position. Mit Polylang zeigt der Shortcode nur
 * die Abschnitte der aktuellen Sprache.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

const NAPURELON_DATENSCHUTZ_TYP            = 'napurelon_datenschutz';
const NAPURELON_DATENSCHUTZ_VORLAGE_OPTION = 'napurelon_datenschutz_vorlage_angelegt';
const NAPURELON_DATENSCHUTZ_HINWEIS        = 'napurelon_datenschutz_hinweis';

/**
 * Registriert den Inhaltstyp „Datenschutz“.
 */
function napurelon_datenschutz_registrieren() {
	register_post_type(
		NAPURELON_DATENSCHUTZ_TYP,
		array(
			'labels'              => array(
				'name'               => 'Datenschutz',
				'singular_name'      => 'Abschnitt',
				'menu_name'          => 'Datenschutz',
				'all_items'          => 'Alle Abschnitte',
				'add_new'            => 'Neuer Abschnitt',
				'add_new_item'       => 'Neuen Abschnitt hinzufügen',
				'edit_item'          => 'Abschnitt bearbeiten',
				'new_item'           => 'Neuer Abschnitt',
				'search_items'       => 'Abschnitte suchen',
				'not_found'          => 'Keine Abschnitte gefunden',
				'not_found_in_trash' => 'Keine Abschnitte im Papierkorb gefunden',
				'attributes'         => 'Reihenfolge',
				'item_published'     => 'Abschnitt veröffentlicht',
				'item_updated'       => 'Abschnitt aktualisiert',
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
			'menu_icon'           => 'dashicons-shield',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'editor', 'page-attributes', 'revisions' ),
		)
	);
}

add_action( 'init', 'napurelon_datenschutz_registrieren' );

/**
 * Admin-Liste nach „Reihenfolge“ sortieren, damit sie der Ausgabe entspricht.
 *
 * @param WP_Query $abfrage Abfrage.
 */
function napurelon_datenschutz_admin_sortierung( $abfrage ) {
	if ( ! is_admin() || ! $abfrage->is_main_query() || NAPURELON_DATENSCHUTZ_TYP !== $abfrage->get( 'post_type' ) || $abfrage->get( 'orderby' ) ) {
		return;
	}

	$abfrage->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
}

add_action( 'pre_get_posts', 'napurelon_datenschutz_admin_sortierung' );

/**
 * Spalte „Reihenfolge“ in der Admin-Liste.
 *
 * @param array $spalten Spalten.
 * @return array
 */
function napurelon_datenschutz_spalten( $spalten ) {
	$spalten['napurelon_reihenfolge'] = 'Reihenfolge';
	return $spalten;
}

add_filter( 'manage_' . NAPURELON_DATENSCHUTZ_TYP . '_posts_columns', 'napurelon_datenschutz_spalten' );

/**
 * Inhalt der Spalte „Reihenfolge“.
 *
 * @param string $spalte  Spaltenname.
 * @param int    $post_id Beitrag.
 */
function napurelon_datenschutz_spalte_inhalt( $spalte, $post_id ) {
	if ( 'napurelon_reihenfolge' === $spalte ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}

add_action( 'manage_' . NAPURELON_DATENSCHUTZ_TYP . '_posts_custom_column', 'napurelon_datenschutz_spalte_inhalt', 10, 2 );

/**
 * Shortcode [napurelon_datenschutz]: alle veröffentlichten Abschnitte der
 * aktuellen Sprache, nummeriert. Abschnitte ohne Titel erscheinen ohne
 * Überschrift und Nummer.
 *
 * @return string HTML.
 */
function napurelon_datenschutz_shortcode() {
	$args = array(
		'post_type'              => NAPURELON_DATENSCHUTZ_TYP,
		'post_status'            => 'publish',
		'posts_per_page'         => 100,
		'orderby'                => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
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

	$abschnitte = get_posts( $args );

	if ( ! $abschnitte ) {
		return '';
	}

	$pfad = get_stylesheet_directory() . '/assets/css/datenschutz.css';
	if ( file_exists( $pfad ) ) {
		wp_enqueue_style( 'napurelon-datenschutz', get_stylesheet_directory_uri() . '/assets/css/datenschutz.css', array(), (string) filemtime( $pfad ) );
	}

	$html   = '<div class="npo-datenschutz">';
	$nummer = 0;

	foreach ( $abschnitte as $abschnitt ) {
		if ( post_password_required( $abschnitt ) ) {
			continue;
		}

		$titel = get_the_title( $abschnitt );
		$html .= '<section class="npo-datenschutz__abschnitt">';

		if ( '' !== trim( $titel ) ) {
			++$nummer;
			$html .= '<h2 class="npo-datenschutz__titel">' . $nummer . '. ' . esc_html( $titel ) . '</h2>';
		}

		$html .= '<div class="npo-datenschutz__text">' . wpautop( wptexturize( wp_kses_post( $abschnitt->post_content ) ) ) . '</div>'
			. '</section>';
	}

	return $html . '</div>';
}

add_shortcode( 'napurelon_datenschutz', 'napurelon_datenschutz_shortcode' );

/**
 * Startinhalt: Abschnitte auf Deutsch und Türkisch.
 *
 * @return array[] Je [ Titel DE, Text DE, Titel TR, Text TR ].
 */
function napurelon_datenschutz_vorlage() {
	return array(
		array(
			'Verantwortlich',
			"NaPurelOn, betrieben von Ilay Alabay, Schweiz\nE-Mail: info@napurelon.ch\n\nWir bearbeiten Personendaten nach dem Schweizer Datenschutzgesetz (DSG).",
			'Sorumlu',
			"NaPurelOn, Ilay Alabay tarafından İsviçre'de işletilmektedir.\nE-posta: info@napurelon.ch\n\nKişisel verilerinizi İsviçre Veri Koruma Kanunu'na (DSG) göre işliyoruz.",
		),
		array(
			'Besuch der Website',
			"Beim Aufruf der Website speichert unser Hoster technisch notwendige Daten wie IP-Adresse, Datum und Uhrzeit, aufgerufene Seite und Browsertyp (Server-Logfiles). Diese Daten dienen dem sicheren Betrieb und werden nach kurzer Zeit gemäss den Vorgaben unseres Hosters gelöscht.\n\nWenn du ein Rezept likest, speichern wir keine IP-Adresse im Klartext, sondern nur einen verschlüsselten Wert (Hash). So verhindern wir Mehrfach-Likes. Dieser Wert wird nach 12 Stunden gelöscht.",
			'Web sitesinin ziyareti',
			"Web sitesini ziyaret ettiğinizde barındırma sağlayıcımız IP adresi, tarih ve saat, açılan sayfa ve tarayıcı türü gibi teknik olarak gerekli verileri kaydeder (sunucu günlük dosyaları). Bu veriler web sitesinin güvenli çalışması için kullanılır ve barındırma sağlayıcımızın kurallarına göre kısa süre sonra silinir.\n\nBir tarifi beğendiğinizde IP adresinizi açık metin olarak değil, yalnızca şifrelenmiş bir değer (hash) olarak kaydederiz. Böylece bir tarifin birden fazla kez beğenilmesini önleriz. Bu değer 12 saat sonra silinir.",
		),
		array(
			'Kundenkonto',
			'Für ein Konto bearbeiten wir deinen Benutzernamen, deine E-Mail-Adresse, dein Passwort (nur verschlüsselt gespeichert) und den Zeitpunkt, zu dem du diese Datenschutzerklärung bestätigt hast. Wir nutzen diese Daten nur, um dein Konto bereitzustellen.',
			'Müşteri hesabı',
			'Bir hesap için kullanıcı adınızı, e-posta adresinizi, şifrenizi (yalnızca şifrelenmiş olarak saklanır) ve bu Gizlilik Politikası\'nı onayladığınız zamanı işleriz. Bu verileri yalnızca hesabınızı sunmak için kullanırız.',
		),
		array(
			'Rezepte einreichen',
			'Wenn du ein Rezept einreichst, speichern wir den Inhalt, die hochgeladenen Bilder und die Zuordnung zu deinem Konto. Wir prüfen das Rezept vor der Veröffentlichung. Veröffentlichte Rezepte werden Teil der Inhalte von NaPurelOn und sind öffentlich sichtbar, ggf. mit deinem Anzeigenamen. Zur Bearbeitung erhalten wir eine E-Mail mit deinem Namen und deiner E-Mail-Adresse. Wenn dein Rezept online geht, informieren wir dich per E-Mail.',
			'Tarif gönderme',
			'Bir tarif gönderdiğinizde içeriği, yüklenen fotoğrafları ve tarifin hesabınızla bağlantısını kaydederiz. Tarifi yayınlamadan önce inceleriz. Yayınlanan tarifler NaPurelOn içeriğinin bir parçası olur ve herkese açık olarak, gerekirse görünen adınızla birlikte gösterilir. İnceleme için adınızı ve e-posta adresinizi içeren bir e-posta alırız. Tarifiniz yayına girdiğinde sizi e-postayla bilgilendiririz.',
		),
		array(
			'Newsletter',
			"Wenn du dich für unseren Newsletter anmeldest, speichern wir deine E-Mail-Adresse, die Sprache der Website sowie den Zeitpunkt der Anmeldung und der Bestätigung. Wir verwenden diese Angaben ausschliesslich, um dir den Newsletter zu senden.\n\nDie Anmeldung erfolgt im Double-Opt-in-Verfahren: Du erhältst eine E-Mail mit einem Bestätigungslink. Erst nach dem Klick auf diesen Link wirst du in den Verteiler aufgenommen. Bestätigst du die Anmeldung nicht innerhalb von 7 Tagen, wird deine Adresse automatisch gelöscht.\n\nZum Schutz vor Missbrauch wird deine IP-Adresse während höchstens einer Stunde in verschlüsselter (gehashter) Form zwischengespeichert. Sie wird nicht zusammen mit deiner Anmeldung gespeichert.\n\nDie Daten werden auf dem Server dieser Website gespeichert und nicht an Dritte weitergegeben.\n\nDu kannst dich jederzeit abmelden, über den Abmeldelink im Newsletter oder über unser Kontaktformular. Deine E-Mail-Adresse wird dann gelöscht. Wenn du dein Benutzerkonto löschst, wird auch eine Newsletter-Anmeldung mit derselben E-Mail-Adresse gelöscht.",
			'Bülten',
			"Bültenimize kaydolduğunuzda e-posta adresinizi, web sitesinin dilini ve kayıt ile onay zamanını kaydederiz. Bu bilgileri yalnızca size bülteni göndermek için kullanırız.\n\nKayıt, çift onay (double opt-in) yöntemiyle yapılır: Size onay bağlantısı içeren bir e-posta gönderilir. Ancak bu bağlantıya tıkladıktan sonra dağıtım listesine eklenirsiniz. Kaydınızı 7 gün içinde onaylamazsanız adresiniz otomatik olarak silinir.\n\nKötüye kullanımı önlemek için IP adresiniz en fazla bir saat boyunca şifrelenmiş (hash) olarak geçici şekilde saklanır. IP adresiniz kaydınızla birlikte saklanmaz.\n\nVeriler bu web sitesinin sunucusunda saklanır ve üçüncü kişilere aktarılmaz.\n\nBülten aboneliğinizi istediğiniz zaman bültendeki abonelikten çıkma bağlantısı veya iletişim formumuz üzerinden iptal edebilirsiniz. Bu durumda e-posta adresiniz silinir. Kullanıcı hesabınızı sildiğinizde aynı e-posta adresiyle yapılmış bülten kaydı da silinir.",
		),
		array(
			'Cookies und lokaler Speicher',
			'Wir verwenden nur technisch notwendige Cookies, z. B. für die Anmeldung, die Sprachauswahl und das Kundenkonto. Deine Likes und deine Merkliste werden nur im lokalen Speicher deines Browsers gespeichert und nicht an uns übermittelt. Wir verwenden keine Analyse- oder Werbe-Tracker.',
			'Çerezler ve yerel depolama',
			'Yalnızca oturum açma, dil seçimi ve müşteri hesabı gibi teknik olarak gerekli çerezleri kullanırız. Beğenileriniz ve kaydettiğiniz tarifler yalnızca tarayıcınızın yerel depolama alanında saklanır ve bize iletilmez. Analiz veya reklam amaçlı izleme araçları kullanmayız.',
		),
		array(
			'Weitergabe und Ausland',
			'Wir verkaufen keine Daten. Wir geben Daten nur an Dienstleister weiter, die wir für den Betrieb brauchen, insbesondere an unseren Hoster <strong>[Name, Land]</strong>. Erfolgt eine Bearbeitung ausserhalb der Schweiz, dann nur in Ländern mit angemessenem Datenschutz oder mit geeigneten Garantien (z. B. Standardvertragsklauseln).',
			'Verilerin aktarılması ve yurt dışı',
			'Verilerinizi satmayız. Verileri yalnızca web sitesinin işletimi için ihtiyaç duyduğumuz hizmet sağlayıcılara, özellikle barındırma sağlayıcımız <strong>[Ad, Ülke]</strong> firmasına aktarırız. Veriler İsviçre dışında işlenirse, bu yalnızca yeterli veri koruma düzeyine sahip ülkelerde veya uygun güvencelerle (ör. standart sözleşme maddeleri) yapılır.',
		),
		array(
			'Aufbewahrung',
			'Wir speichern Personendaten nur so lange, wie es für den jeweiligen Zweck nötig ist oder das Gesetz es verlangt. Kontodaten löschen wir, wenn du dein Konto löschen lässt. Veröffentlichte Rezepte bleiben auch danach auf NaPurelOn. Auf Wunsch entfernen wir deinen Namen, sodass das Rezept nicht mehr mit dir verbunden ist.',
			'Saklama süresi',
			'Kişisel verileri yalnızca ilgili amaç için gerekli olduğu veya kanunun öngördüğü süre boyunca saklarız. Hesabınızı sildiğinizde hesap verilerinizi sileriz. Yayınlanan tarifler bundan sonra da NaPurelOn\'da kalır. İsterseniz adınızı kaldırırız; böylece tarif artık sizinle ilişkilendirilmez.',
		),
		array(
			'Deine Rechte',
			'Du kannst jederzeit Auskunft über deine Daten verlangen und sie berichtigen oder löschen lassen. Du kannst auch einer Bearbeitung widersprechen. Schreib uns dafür an die oben genannte E-Mail-Adresse. Du hast ausserdem das Recht, dich beim Eidgenössischen Datenschutz- und Öffentlichkeitsbeauftragten (EDÖB) zu beschweren.',
			'Haklarınız',
			'Verileriniz hakkında istediğiniz zaman bilgi isteyebilir, bunları düzelttirebilir veya sildirebilirsiniz. Verilerinizin işlenmesine itiraz da edebilirsiniz. Bunun için yukarıda belirtilen e-posta adresine yazın. Ayrıca İsviçre Federal Veri Koruma ve Kamuya Açıklık Görevlisi\'ne (EDÖB) şikâyette bulunma hakkınız vardır.',
		),
		array(
			'Änderungen',
			"Wir können diese Datenschutzerklärung anpassen. Es gilt die jeweils aktuelle Fassung auf dieser Website.\n\nStand: November 2026",
			'Değişiklikler',
			"Bu Gizlilik Politikası'nı değiştirebiliriz. Her zaman bu web sitesindeki güncel sürüm geçerlidir.\n\nSon güncelleme: Kasım 2026",
		),
	);
}

/**
 * Legt einen Abschnitt in einer Sprache an.
 *
 * @param string      $titel       Überschrift.
 * @param string      $text        Text (HTML).
 * @param int         $reihenfolge Position.
 * @param string|null $sprache     Polylang-Sprache oder null.
 * @return int Beitrags-ID oder 0.
 */
function napurelon_datenschutz_abschnitt_anlegen( $titel, $text, $reihenfolge, $sprache ) {
	$id = wp_insert_post(
		array(
			'post_type'    => NAPURELON_DATENSCHUTZ_TYP,
			'post_status'  => 'publish',
			'post_title'   => $titel,
			'post_content' => $text,
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

	return (int) $id;
}

/**
 * Legt den Startinhalt einmalig an, wenn ein Administrator den Admin-Bereich
 * aufruft und noch keine Abschnitte existieren.
 */
function napurelon_datenschutz_vorlage_anlegen() {
	if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'manage_options' ) || get_option( NAPURELON_DATENSCHUTZ_VORLAGE_OPTION ) ) {
		return;
	}

	$mit_sprachen = function_exists( 'pll_languages_list' ) && function_exists( 'pll_save_post_translations' );
	$sprachen     = $mit_sprachen ? (array) pll_languages_list( array( 'fields' => 'slug' ) ) : array();

	if ( $mit_sprachen && ! in_array( 'de', $sprachen, true ) ) {
		return;
	}

	$vorhanden = get_posts(
		array(
			'post_type'      => NAPURELON_DATENSCHUTZ_TYP,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'lang'           => '',
		)
	);

	// add_option schlägt fehl, wenn eine parallele Anfrage schneller war.
	if ( ! add_option( NAPURELON_DATENSCHUTZ_VORLAGE_OPTION, 1, '', false ) || $vorhanden ) {
		return;
	}

	$de = $mit_sprachen ? 'de' : null;
	$tr = ( $mit_sprachen && in_array( 'tr', $sprachen, true ) ) ? 'tr' : null;

	$reihenfolge = 0;
	$anzahl      = 0;

	foreach ( napurelon_datenschutz_vorlage() as $eintrag ) {
		$reihenfolge += 10;

		$id_de = napurelon_datenschutz_abschnitt_anlegen( $eintrag[0], $eintrag[1], $reihenfolge, $de );
		$id_tr = $tr ? napurelon_datenschutz_abschnitt_anlegen( $eintrag[2], $eintrag[3], $reihenfolge, $tr ) : 0;

		if ( $id_de && $id_tr ) {
			pll_save_post_translations( array( 'de' => $id_de, 'tr' => $id_tr ) );
		}

		$anzahl += (int) (bool) $id_de + (int) (bool) $id_tr;
	}

	set_transient( NAPURELON_DATENSCHUTZ_HINWEIS, $anzahl, DAY_IN_SECONDS );
}

add_action( 'admin_init', 'napurelon_datenschutz_vorlage_anlegen', 40 );

/**
 * Meldet einmal, dass der Startinhalt angelegt wurde.
 */
function napurelon_datenschutz_hinweis() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$anzahl = get_transient( NAPURELON_DATENSCHUTZ_HINWEIS );

	if ( false === $anzahl ) {
		return;
	}

	delete_transient( NAPURELON_DATENSCHUTZ_HINWEIS );
	?>
	<div class="notice notice-success is-dismissible">
		<p><strong>NaPurelOn:</strong> <?php echo esc_html( sprintf( '%d Datenschutz-Abschnitte angelegt (Deutsch und Türkçe). Du findest sie links unter „Datenschutz“.', (int) $anzahl ) ); ?></p>
	</div>
	<?php
}

add_action( 'admin_notices', 'napurelon_datenschutz_hinweis' );
