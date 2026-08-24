<?php
/**
 * Nicht hierarchische Taxonomie "Rezept-Tags" (z. B. vegetarisch, GAPS-Diät).
 *
 * Ergänzt die hierarchischen Rezeptkategorien um freie Schlagworte, die auf
 * der Rezeptseite unter den Zeitangaben ausgegeben werden.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registriert die Taxonomie "Rezept-Tags".
 */
function napurelon_register_rezepttag_taxonomy() {
	$labels = array(
		'name'                       => 'Rezept-Tags',
		'singular_name'              => 'Rezept-Tag',
		'menu_name'                  => 'Rezept-Tags',
		'all_items'                  => 'Alle Rezept-Tags',
		'edit_item'                  => 'Rezept-Tag bearbeiten',
		'view_item'                  => 'Rezept-Tag ansehen',
		'update_item'                => 'Rezept-Tag aktualisieren',
		'add_new_item'               => 'Neuen Rezept-Tag hinzufügen',
		'new_item_name'              => 'Name des neuen Rezept-Tags',
		'separate_items_with_commas' => 'Rezept-Tags mit Komma trennen',
		'add_or_remove_items'        => 'Rezept-Tags hinzufügen oder entfernen',
		'choose_from_most_used'      => 'Häufig genutzte Rezept-Tags',
		'search_items'               => 'Rezept-Tags suchen',
		'not_found'                  => 'Keine Rezept-Tags gefunden',
		'back_to_items'              => 'Zurück zu den Rezept-Tags',
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'hierarchical'      => false, // Freie Schlagworte, wie die Standard-Schlagwörter.
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array(
			'slug'       => 'rezept-tag',
			'with_front' => false,
		),
	);

	register_taxonomy( 'rezepttag', array( 'rezepte' ), $args );
}

add_action( 'init', 'napurelon_register_rezepttag_taxonomy' );
