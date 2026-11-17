<?php
/**
 * Türkische Vorgaben für die NaPurelOn-Zeichenketten in Polylang.
 *
 * Trägt beim Aufruf des Admin-Bereichs durch einen Administrator die Übersetzungen
 * unten ins Feld „Türkçe“ ein, aber nur, wo es noch leer ist. Eigene Übersetzungen
 * unter Languages → Translations bleiben unverändert. Läuft erneut, sobald sich
 * die Liste ändert.
 *
 * @package NaPurelOn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NAPURELON_TR_VORGABEN_OPTION  = 'napurelon_tr_vorgaben_stand';
const NAPURELON_TR_VORGABEN_HINWEIS = 'napurelon_tr_vorgaben_hinweis';

/**
 * Deutsche Ausgangstexte => türkische Übersetzung (Anrede „siz“).
 *
 * @return array<string,string>
 */
function napurelon_tr_vorgaben() {
	return array(
		'Kategorie' => 'Kategori',
		'Menüart' => 'Yemek türü',
		'Region' => 'Bölge',
		'Zeitaufwand' => 'Süre',
		'Saison' => 'Mevsim',
		'Anlass' => 'Özel gün',
		'Temperament' => 'Mizaç',
		'Ernährungsweise' => 'Beslenme şekli',
		'Bis 15 Min.' => '15 dk.\'ya kadar',
		'15–30 Min.' => '15–30 dk.',
		'30–60 Min.' => '30–60 dk.',
		'Über 60 Min.' => '60 dk.\'dan fazla',
		'Kategorien' => 'Kategoriler',
		'Rezepte filtern' => 'Tarifleri filtrele',
		'Anwenden' => 'Uygula',
		'Filter zurücksetzen' => 'Filtreleri sıfırla',
		'In dieser Kategorie nicht verwendet' => 'Bu kategoride kullanılmıyor',
		'Zu dieser Auswahl gibt es keine Rezepte.' => 'Bu seçime uygun tarif yok.',
		'In dieser Kategorie gibt es noch keine Rezepte.' => 'Bu kategoride henüz tarif yok.',
		'Mehr anzeigen' => 'Daha fazla göster',
		'Rezepte, Zutaten, Kategorien, Tags …' => 'Tarifler, malzemeler, kategoriler, etiketler …',
		'Rezepte, Zutaten, Kategorien und Tags durchsuchen' => 'Tariflerde, malzemelerde, kategorilerde ve etiketlerde ara',
		'Suche läuft …' => 'Aranıyor …',
		'1 Rezept gefunden' => '1 tarif bulundu',
		'%d Rezepte gefunden' => '%d tarif bulundu',
		'Suchen …' => 'Ara …',
		'Suchen' => 'Ara',
		'Suchen nach:' => 'Ara:',
		'Suche absenden' => 'Aramayı gönder',
		'Suche' => 'Arama',
		'Suchergebnisse für „%s“' => '“%s” için arama sonuçları',
		'Rezepte' => 'Tarifler',
		'Seiten & Beiträge' => 'Sayfalar & Yazılar',
		'Seite' => 'Sayfa',
		'Beitrag' => 'Yazı',
		'Keine Rezepte gefunden.' => 'Tarif bulunamadı.',
		'Keine Seiten oder Beiträge gefunden.' => 'Sayfa veya yazı bulunamadı.',
		'Bitte gib mindestens zwei Zeichen ein.' => 'Lütfen en az iki karakter girin.',
		'Leider haben wir zu „%s“ nichts gefunden. Versuche es mit einem anderen Begriff, zum Beispiel einer Zutat.' => 'Maalesef “%s” için bir şey bulamadık. Başka bir kelime deneyin, örneğin bir malzeme.',
		'Untertitel' => 'Alt başlık',
		'Einleitung' => 'Giriş',
		'Zutaten' => 'Malzemeler',
		'Ausbacken' => 'Fırında pişirme',
		'Zubereitungszeit' => 'Hazırlık süresi',
		'Kochzeit' => 'Pişirme süresi',
		'Portionen' => 'Porsiyon',
		'Menge' => 'Miktar',
		'Haltbarkeit' => 'Saklama süresi',
		'Anwendung' => 'Kullanım',
		'Einsatzgebiete' => 'Kullanım alanları',
		'Tipps & Empfehlungen' => 'İpuçları & Öneriler',
		'Vorteile' => 'Avantajlar',
		'Nachteile' => 'Dezavantajlar',
		'Warnhinweise' => 'Uyarılar',
		'Quellen' => 'Kaynaklar',
		'Video-URL' => 'Video bağlantısı',
		'Zubereitung' => 'Hazırlanışı',
		'Grunddaten' => 'Temel bilgiler',
		'Zutaten & Zubereitung' => 'Malzemeler & Hazırlanışı',
		'Details' => 'Ayrıntılar',
		'Gut zu wissen' => 'Bilmekte fayda var',
		'Merkmale' => 'Özellikler',
		'Tags' => 'Etiketler',
		'Bilder' => 'Görseller',
		'Bestätigung' => 'Onay',
		'Rezept einreichen' => 'Tarif gönder',
		'Rezept hinzufügen' => 'Tarif ekle',
		'Mein Konto' => 'Hesabım',
		'Rezepttitel' => 'Tarif adı',
		'Zubereitungszeit (Minuten)' => 'Hazırlık süresi (dakika)',
		'Kochzeit (Minuten)' => 'Pişirme süresi (dakika)',
		'Eine Zutat pro Zeile. Eine Zeile, die mit „:“ endet, wird zur Zwischenüberschrift (z. B. „Teig:“). Zutaten sind erforderlich, außer in „Grundlagen & Wissen“.' => 'Her satıra bir malzeme yazın. “:” ile biten satır ara başlık olur (ör. “Hamur:”). “Temel Bilgiler” dışında malzeme girmek zorunludur.',
		'Eine Zutat pro Zeile. Eine Zeile, die mit „:“ endet, wird zur Zwischenüberschrift (z. B. „Teig:“).' => 'Her satıra bir malzeme yazın. “:” ile biten satır ara başlık olur (ör. “Hamur:”).',
		'Zutaten sind erforderlich, außer in „Grundlagen & Wissen“.' => '“Temel Bilgiler” dışında malzeme girmek zorunludur.',
		'Pflichtfeld' => 'Zorunlu alan',
		'Wird gesendet…' => 'Gönderiliyor…',
		'Dein Rezept wird vor der Veröffentlichung geprüft.' => 'Tarifiniz yayınlanmadan önce incelenir.',
		'Bitte melde dich an, um ein Rezept einzureichen.' => 'Tarif göndermek için lütfen giriş yapın.',
		'Anmelden' => 'Giriş yap',
		'Registrieren' => 'Kayıt ol',
		'Weiteres Rezept einreichen' => 'Başka bir tarif gönder',
		'Danke! Dein Rezept wurde eingereicht und wird vor der Veröffentlichung geprüft.' => 'Teşekkürler! Tarifiniz gönderildi, yayınlanmadan önce incelenecek.',
		'Hauptkategorie' => 'Ana kategori',
		'Unterkategorie' => 'Alt kategori',
		'Hauptbild' => 'Ana görsel',
		'Galeriebilder' => 'Galeri görselleri',
		'Deine Website' => 'Web siteniz',
		'— keine —' => '— yok —',
		'Bitte wählen' => 'Lütfen seçin',
		'JPG, PNG oder WebP, höchstens 5 MB pro Bild, bis zu 5 Galeriebilder.' => 'JPG, PNG veya WebP; görsel başına en fazla 5 MB, en fazla 5 galeri görseli.',
		'Ich habe die Fotos selbst aufgenommen oder besitze die Rechte, sie hier veröffentlichen zu lassen.' => 'Fotoğrafları kendim çektim veya burada yayınlatma hakkına sahibim.',
		'Ich räume NaPurelOn das unbefristete Recht ein, mein Rezept und meine Bilder zu veröffentlichen und zu bearbeiten.' => 'NaPurelOn\'a tarifimi ve görsellerimi süresiz olarak yayınlama ve düzenleme hakkı veriyorum.',
		'Datenschutzerklärung' => 'Gizlilik Politikası',
		'Ich habe die %s gelesen.' => '%s\'nı okudum.',
		'Ich habe die %s gelesen und bin mit der Verarbeitung meiner Angaben einverstanden.' => '%s\'nı okudum ve bilgilerimin işlenmesini kabul ediyorum.',
		'Bitte bestätige die Bildrechte.' => 'Lütfen görsel haklarını onaylayın.',
		'Bitte bestätige das Nutzungsrecht.' => 'Lütfen kullanım hakkını onaylayın.',
		'Bitte bestätige die Datenschutzerklärung.' => 'Lütfen Gizlilik Politikası\'nı onaylayın.',
		'Bitte gib einen Rezepttitel ein.' => 'Lütfen bir tarif adı girin.',
		'Der Titel darf höchstens 120 Zeichen lang sein.' => 'Başlık en fazla 120 karakter olabilir.',
		'Bitte wähle eine gültige Hauptkategorie.' => 'Lütfen geçerli bir ana kategori seçin.',
		'Bitte wähle eine gültige Unterkategorie.' => 'Lütfen geçerli bir alt kategori seçin.',
		'Bitte beschreibe die Zubereitung.' => 'Lütfen hazırlanışını anlatın.',
		'Die Zubereitung darf höchstens 20.000 Zeichen lang sein.' => 'Hazırlanış metni en fazla 20.000 karakter olabilir.',
		'Bitte gib die Zutaten an.' => 'Lütfen malzemeleri girin.',
		'Dieses Feld darf höchstens 200 Zeichen enthalten.' => 'Bu alan en fazla 200 karakter içerebilir.',
		'Dieses Feld darf höchstens 5.000 Zeichen enthalten.' => 'Bu alan en fazla 5.000 karakter içerebilir.',
		'Bitte gib eine gültige HTTP- oder HTTPS-URL ein.' => 'Lütfen geçerli bir HTTP veya HTTPS adresi girin.',
		'Bitte wähle gültige Merkmale für diese Kategorie.' => 'Lütfen bu kategori için geçerli özellikler seçin.',
		'Bitte wähle gültige Rezept-Tags.' => 'Lütfen geçerli tarif etiketleri seçin.',
		'Bitte lade ein Hauptbild hoch.' => 'Lütfen bir ana görsel yükleyin.',
		'Bitte lade höchstens 5 Galeriebilder hoch.' => 'Lütfen en fazla 5 galeri görseli yükleyin.',
		'Die Dateien müssen JPG, PNG oder WebP sein.' => 'Dosyalar JPG, PNG veya WebP olmalıdır.',
		'Ein Bild darf höchstens 5 MB groß sein.' => 'Bir görsel en fazla 5 MB olabilir.',
		'Ein Bild konnte nicht gelesen werden.' => 'Bir görsel okunamadı.',
		'Der Datei-Upload ist fehlgeschlagen.' => 'Dosya yüklenemedi.',
		'Die Dateien sind zu groß. Bitte verkleinere die Bilder.' => 'Dosyalar çok büyük. Lütfen görselleri küçültün.',
		'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.' => 'Oturumun süresi doldu. Lütfen sayfayı yenileyin.',
		'Du hast in der letzten Stunde bereits drei Rezepte eingereicht. Bitte versuche es später erneut.' => 'Son bir saatte zaten üç tarif gönderdiniz. Lütfen daha sonra tekrar deneyin.',
		'Die Bilder konnten nicht gespeichert werden.' => 'Görseller kaydedilemedi.',
		'Das Rezept konnte nicht gespeichert werden.' => 'Tarif kaydedilemedi.',
		'Meine Rezepte' => 'Tariflerim',
		'Du hast noch keine Rezepte eingereicht.' => 'Henüz tarif göndermediniz.',
		'Rezept' => 'Tarif',
		'Status' => 'Durum',
		'Eingereicht am' => 'Gönderim tarihi',
		'Veröffentlicht' => 'Yayında',
		'Wird geprüft' => 'İnceleniyor',
		'Geplant' => 'Planlandı',
		'Entwurf' => 'Taslak',
		'Privat' => 'Özel',
		'Hier siehst du deine <a href="%1$s">eingereichten Rezepte</a> und kannst dein <a href="%2$s">Passwort und deine Kontodetails</a> bearbeiten.' => 'Burada <a href="%1$s">gönderdiğiniz tarifleri</a> görebilir, <a href="%2$s">şifrenizi ve hesap bilgilerinizi</a> düzenleyebilirsiniz.',
		'Die Registrierung konnte nicht abgeschlossen werden. Bitte versuche es erneut.' => 'Kayıt tamamlanamadı. Lütfen tekrar deneyin.',
		'Name' => 'Ad',
		'E-Mail' => 'E-posta',
		'Betreff' => 'Konu',
		'Nachricht' => 'Mesaj',
		'Nachricht senden' => 'Mesaj gönder',
		'Bitte prüfe die markierten Felder.' => 'Lütfen işaretli alanları kontrol edin.',
		'Bitte gib deinen Namen ein.' => 'Lütfen adınızı girin.',
		'Der Name darf höchstens 100 Zeichen lang sein.' => 'Ad en fazla 100 karakter olabilir.',
		'Bitte gib eine gültige E-Mail-Adresse ein.' => 'Lütfen geçerli bir e-posta adresi girin.',
		'Der Betreff darf höchstens 150 Zeichen lang sein.' => 'Konu en fazla 150 karakter olabilir.',
		'Bitte schreibe eine Nachricht (mindestens 10 Zeichen).' => 'Lütfen bir mesaj yazın (en az 10 karakter).',
		'Die Nachricht darf höchstens 5.000 Zeichen lang sein.' => 'Mesaj en fazla 5.000 karakter olabilir.',
		'Bitte füge höchstens drei Links ein.' => 'Lütfen en fazla üç bağlantı ekleyin.',
		'Es wurden zu viele Nachrichten gesendet. Bitte versuche es später erneut.' => 'Çok fazla mesaj gönderildi. Lütfen daha sonra tekrar deneyin.',
		'Die Nachricht konnte nicht gesendet werden. Bitte versuche es später erneut.' => 'Mesaj gönderilemedi. Lütfen daha sonra tekrar deneyin.',
		'Danke für deine Nachricht! Wir melden uns so bald wie möglich.' => 'Mesajınız için teşekkürler! En kısa sürede size dönüş yapacağız.',
		'Weitere Nachricht senden' => 'Başka bir mesaj gönder',
		'Konto löschen' => 'Hesabı sil',
		'Wenn du dein Konto löschst:' => 'Hesabınızı silerseniz:',
		'werden dein Konto und deine Kontodaten (Name, E-Mail-Adresse, Passwort) endgültig gelöscht,' => 'hesabınız ve hesap bilgileriniz (ad, e-posta adresi, şifre) kalıcı olarak silinir,',
		'werden deine noch nicht veröffentlichten Rezepte gelöscht,' => 'henüz yayınlanmamış tarifleriniz silinir,',
		'bleiben deine veröffentlichten Rezepte auf NaPurelOn, jedoch ohne deinen Namen,' => 'yayınlanmış tarifleriniz NaPurelOn\'da kalır, ancak adınız kaldırılır,',
		'werden deine Kommentare anonymisiert.' => 'yorumlarınız anonim hâle getirilir.',
		'Das Löschen kann nicht rückgängig gemacht werden.' => 'Silme işlemi geri alınamaz.',
		'Ich möchte mein Konto löschen' => 'Hesabımı silmek istiyorum',
		'Aktuelles Passwort zur Bestätigung' => 'Onay için mevcut şifreniz',
		'Ich habe verstanden, dass mein Konto endgültig gelöscht wird.' => 'Hesabımın kalıcı olarak silineceğini anladım.',
		'Konto endgültig löschen' => 'Hesabı kalıcı olarak sil',
		'Dein Konto wurde gelöscht.' => 'Hesabınız silindi.',
		'Konten mit Verwaltungsrechten können hier nicht gelöscht werden.' => 'Yönetici yetkisine sahip hesaplar burada silinemez.',
		'Zu viele Versuche. Bitte versuche es in einer Stunde erneut.' => 'Çok fazla deneme yapıldı. Lütfen bir saat sonra tekrar deneyin.',
		'Das Passwort ist nicht korrekt.' => 'Şifre yanlış.',
		'Bitte bestätige, dass du dein Konto endgültig löschen möchtest.' => 'Lütfen hesabınızı kalıcı olarak silmek istediğinizi onaylayın.',
		'Dein Konto konnte nicht gelöscht werden. Bitte kontaktiere uns.' => 'Hesabınız silinemedi. Lütfen bizimle iletişime geçin.',
		'Möchtest du dein Konto wirklich löschen?' => 'Hesabınızı gerçekten silmek istiyor musunuz?',
		'Ja, weiter' => 'Evet, devam et',
		'Nein, Konto behalten' => 'Hayır, hesabımı koru',
		'Warum möchtest du dein Konto löschen?' => 'Hesabınızı neden silmek istiyorsunuz?',
		'Ich nutze NaPurelOn nicht mehr.' => 'Artık NaPurelOn\'u kullanmıyorum.',
		'Ich habe nicht die Rezepte gefunden, die ich suche.' => 'Aradığım tarifleri bulamadım.',
		'Die Website ist mir zu kompliziert.' => 'Site benim için fazla karmaşık.',
		'Ich möchte meine Daten hier nicht mehr speichern.' => 'Bilgilerimin burada saklanmasını artık istemiyorum.',
		'Ich habe ein anderes Konto.' => 'Başka bir hesabım var.',
		'Sonstiges' => 'Diğer',
		'Magst du uns mehr erzählen? (optional)' => 'Biraz daha anlatmak ister misiniz? (isteğe bağlı)',
		'Weiter' => 'Devam',
		'Zurück' => 'Geri',
		'Bitte wähle aus, warum du dein Konto löschen möchtest.' => 'Lütfen hesabınızı neden silmek istediğinizi seçin.',
		'Deine E-Mail-Adresse' => 'E-posta adresiniz',
		'Abmelden ist jederzeit möglich.' => 'Aboneliğinizi istediğiniz zaman iptal edebilirsiniz.',
		'Die Anmeldung hat nicht geklappt. Bitte versuche es später erneut.' => 'Abonelik gerçekleşmedi. Lütfen daha sonra tekrar deneyin.',
		'Bitte bestätige deine Newsletter-Anmeldung' => 'Lütfen bülten aboneliğinizi onaylayın',
		'Hallo' => 'Merhaba',
		'Du hast dich für den Newsletter von %s angemeldet. Bitte bestätige deine Anmeldung über diesen Link:' => '%s bültenine abone oldunuz. Lütfen aboneliğinizi şu bağlantıyla onaylayın:',
		'Der Link ist 7 Tage gültig. Wenn du dich nicht angemeldet hast, ignoriere diese E-Mail einfach. Du erhältst dann keine weiteren Nachrichten.' => 'Bağlantı 7 gün geçerlidir. Abone olmadıysanız bu e-postayı dikkate almayın; size başka mesaj gönderilmez.',
		'Fast geschafft! Bitte bestätige deine Anmeldung über den Link, den wir dir per E-Mail geschickt haben.' => 'Az kaldı! Lütfen size e-postayla gönderdiğimiz bağlantıyla aboneliğinizi onaylayın.',
		'Danke! Deine Newsletter-Anmeldung ist bestätigt.' => 'Teşekkürler! Bülten aboneliğiniz onaylandı.',
		'Du wurdest vom Newsletter abgemeldet.' => 'Bülten aboneliğiniz iptal edildi.',
		'Dieser Link ist ungültig oder abgelaufen. Bitte melde dich erneut an.' => 'Bu bağlantı geçersiz veya süresi dolmuş. Lütfen tekrar abone olun.',
		'Abonnieren' => 'Abone ol',
	);
}

/**
 * Trägt fehlende türkische Übersetzungen ein.
 */
function napurelon_tr_vorgaben_eintragen() {
	if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
		return;
	}

	$vorgaben = napurelon_tr_vorgaben();
	$stand    = md5( wp_json_encode( $vorgaben ) );

	if ( get_option( NAPURELON_TR_VORGABEN_OPTION ) === $stand ) {
		return;
	}

	$model = PLL()->model;
	$tr    = ( isset( $model->languages ) && is_object( $model->languages ) )
		? $model->languages->get( 'tr' )
		: $model->get_language( 'tr' );

	if ( ! $tr ) {
		return;
	}

	$mo = new PLL_MO();
	$mo->import_from_db( $tr );

	$neu = array();

	foreach ( napurelon_sprache_texte() as $original ) {
		if ( isset( $vorgaben[ $original ] ) && '' === $mo->translate_if_any( $original ) ) {
			$mo->add_entry( $mo->make_entry( $original, $vorgaben[ $original ] ) );
			$neu[] = $original;
		}
	}

	if ( $neu ) {
		$mo->export_to_db( $tr );

		$kontrolle = new PLL_MO();
		$kontrolle->import_from_db( $tr );
		foreach ( $neu as $original ) {
			if ( $kontrolle->translate_if_any( $original ) !== $vorgaben[ $original ] ) {
				return;
			}
		}
	}

	update_option( NAPURELON_TR_VORGABEN_OPTION, $stand, false );
	set_transient( NAPURELON_TR_VORGABEN_HINWEIS, count( $neu ), DAY_IN_SECONDS );
}

add_action( 'admin_init', 'napurelon_tr_vorgaben_eintragen', 30 );

/**
 * Meldet einmal, wie viele Übersetzungen eingetragen wurden.
 */
function napurelon_tr_vorgaben_hinweis() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$anzahl = get_transient( NAPURELON_TR_VORGABEN_HINWEIS );

	if ( false === $anzahl ) {
		return;
	}

	delete_transient( NAPURELON_TR_VORGABEN_HINWEIS );
	?>
	<div class="notice notice-success is-dismissible">
		<p><strong>NaPurelOn:</strong> <?php echo esc_html( sprintf( '%d türkische Übersetzungen eingetragen. Bereits vorhandene blieben unverändert.', (int) $anzahl ) ); ?></p>
	</div>
	<?php
}

add_action( 'admin_notices', 'napurelon_tr_vorgaben_hinweis' );
