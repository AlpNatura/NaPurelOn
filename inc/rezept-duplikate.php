<?php
/**
 * Hinweis auf Rezepte mit gleichem Titel.
 *
 * WordPress erlaubt doppelte Titel. Diese Datei blockiert nichts, sondern
 * blendet im Editor einen Hinweis samt Link auf das bestehende Rezept ein.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sucht Rezepte mit identischem Titel.
 *
 * @param string $title   Zu prüfender Titel.
 * @param int    $exclude ID des aktuellen Rezepts.
 * @return WP_Post[] Gefundene Rezepte ohne das aktuelle.
 */
function napurelon_find_rezept_duplicates( $title, $exclude = 0 ) {
    $title = trim( $title );

    if ( '' === $title ) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type'              => 'rezepte',
            'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'posts_per_page'         => 5,
            'post__not_in'           => array( (int) $exclude ),
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'title'                  => $title,
        )
    );

    return $query->posts;
}

/**
 * Zeigt im Rezept-Editor einen Hinweis, wenn der Titel bereits vergeben ist.
 */
function napurelon_rezept_duplicate_notice() {
    $screen = get_current_screen();

    if ( ! $screen || 'rezepte' !== $screen->id || 'post' !== $screen->base ) {
        return;
    }

    $post = get_post();

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $duplicates = napurelon_find_rezept_duplicates( $post->post_title, $post->ID );

    if ( empty( $duplicates ) ) {
        return;
    }

    $links = array();

    foreach ( $duplicates as $duplicate ) {
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( (string) get_edit_post_link( $duplicate->ID ) ),
            esc_html( get_the_title( $duplicate ) . ' (#' . $duplicate->ID . ')' )
        );
    }

    printf(
        '<div class="notice notice-warning"><p><strong>Achtung:</strong> Es gibt bereits ein Rezept mit diesem Titel: %s</p></div>',
        wp_kses( implode( ', ', $links ), array( 'a' => array( 'href' => array() ) ) )
    );
}

add_action( 'admin_notices', 'napurelon_rezept_duplicate_notice' );
