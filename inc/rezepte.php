<?php
/**
 * Inhaltsstruktur für Rezepte: Custom Post Type und Kategorie-Taxonomie.
 *
 * Die Registrierung liegt bewusst in einer eigenen Datei, damit sie später
 * ohne Anpassungen in ein Plugin verschoben werden kann.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version der Rewrite-Regeln. Bei jeder Änderung an Slugs oder
 * Rewrite-Optionen erhöhen, damit die Permalinks neu geschrieben werden.
 */
define( 'NAPURELON_REZEPTE_REWRITE_VERSION', '1.2.0' );

/**
 * Registriert den Custom Post Type "Rezepte".
 */
function napurelon_register_rezepte_post_type() {
    $labels = array(
        'name'                  => 'Rezepte',
        'singular_name'         => 'Rezept',
        'menu_name'             => 'Rezepte',
        'name_admin_bar'        => 'Rezept',
        'add_new'               => 'Erstellen',
        'add_new_item'          => 'Neues Rezept erstellen',
        'edit_item'             => 'Rezept bearbeiten',
        'new_item'              => 'Neues Rezept',
        'view_item'             => 'Rezept ansehen',
        'view_items'            => 'Rezepte ansehen',
        'search_items'          => 'Rezepte suchen',
        'not_found'             => 'Keine Rezepte gefunden',
        'not_found_in_trash'    => 'Keine Rezepte im Papierkorb gefunden',
        'all_items'             => 'Alle Rezepte',
        'archives'              => 'Rezept-Archiv',
        'attributes'            => 'Rezept-Attribute',
        'featured_image'        => 'Rezeptbild',
        'set_featured_image'    => 'Rezeptbild festlegen',
        'remove_featured_image' => 'Rezeptbild entfernen',
        'use_featured_image'    => 'Als Rezeptbild verwenden',
        'item_published'        => 'Rezept veröffentlicht',
        'item_updated'          => 'Rezept aktualisiert',
    );

    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true, // Gutenberg, Elementor und REST API.
        'menu_icon'    => 'dashicons-carrot',
        'menu_position'=> 5,
        'supports'     => array( 'title', 'editor', 'thumbnail', 'author', 'comments', 'revisions' ),
        'rewrite'      => array(
            'slug'       => 'rezepte',
            'with_front' => false,
        ),
        // Startvorlage: Platzhalter im Inhaltsbereich, in den die Zubereitung geschrieben wird.
        'template'     => array(
            array(
                'core/paragraph',
                array( 'placeholder' => 'Zubereitung: Schritte hier eintragen …' ),
            ),
        ),
    );

    register_post_type( 'rezepte', $args );
}

add_action( 'init', 'napurelon_register_rezepte_post_type' );

/**
 * Ersetzt den Platzhalter im Titelfeld des Rezept-Editors.
 *
 * @param string  $text Vorgabetext.
 * @param WP_Post $post Aktueller Beitrag.
 * @return string
 */
function napurelon_rezept_title_placeholder( $text, $post ) {
    return ( isset( $post->post_type ) && 'rezepte' === $post->post_type ) ? 'Rezepttitel hinzufügen' : $text;
}

add_filter( 'enter_title_here', 'napurelon_rezept_title_placeholder', 10, 2 );

/**
 * Registriert die hierarchische Taxonomie "Rezeptkategorien".
 */
function napurelon_register_rezeptkategorie_taxonomy() {
    $labels = array(
        'name'              => 'Rezeptkategorien',
        'singular_name'     => 'Rezeptkategorie',
        'menu_name'         => 'Rezeptkategorien',
        'all_items'         => 'Alle Rezeptkategorien',
        'edit_item'         => 'Rezeptkategorie bearbeiten',
        'view_item'         => 'Rezeptkategorie ansehen',
        'update_item'       => 'Rezeptkategorie aktualisieren',
        'add_new_item'      => 'Neue Rezeptkategorie hinzufügen',
        'new_item_name'     => 'Name der neuen Rezeptkategorie',
        'parent_item'       => 'Übergeordnete Rezeptkategorie',
        'parent_item_colon' => 'Übergeordnete Rezeptkategorie:',
        'search_items'      => 'Rezeptkategorien suchen',
        'not_found'         => 'Keine Rezeptkategorien gefunden',
        'back_to_items'     => 'Zurück zu den Rezeptkategorien',
    );

    $args = array(
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true, // Verhält sich wie die Standard-Kategorien.
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array(
            'slug'         => 'rezeptkategorie',
            'with_front'   => false,
            'hierarchical' => true,
        ),
    );

    register_taxonomy( 'rezeptkategorie', array( 'rezepte' ), $args );
}

add_action( 'init', 'napurelon_register_rezeptkategorie_taxonomy' );

/**
 * Schreibt die Permalinks einmalig neu, sobald sich die Rewrite-Version ändert.
 *
 * flush_rewrite_rules() ist teuer und darf nicht bei jedem Seitenaufruf laufen,
 * deshalb der Abgleich mit einer in der Datenbank gespeicherten Version.
 */
function napurelon_maybe_flush_rezepte_rewrite_rules() {
    if ( get_option( 'napurelon_rezepte_rewrite_version' ) === NAPURELON_REZEPTE_REWRITE_VERSION ) {
        return;
    }

    flush_rewrite_rules();
    update_option( 'napurelon_rezepte_rewrite_version', NAPURELON_REZEPTE_REWRITE_VERSION );
}

// Priorität 20: läuft nach der Registrierung von Post Type und Taxonomie.
add_action( 'init', 'napurelon_maybe_flush_rezepte_rewrite_rules', 20 );

/**
 * Entfernt die gespeicherte Rewrite-Version beim Themewechsel, damit die
 * Regeln beim nächsten Aktivieren sauber neu aufgebaut werden.
 */
function napurelon_reset_rezepte_rewrite_version() {
    delete_option( 'napurelon_rezepte_rewrite_version' );
    flush_rewrite_rules();
}

add_action( 'switch_theme', 'napurelon_reset_rezepte_rewrite_version' );
