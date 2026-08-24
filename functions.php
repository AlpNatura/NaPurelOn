<?php
// Inhaltsstruktur: Custom Post Type "Rezepte" inkl. Taxonomie.
require_once get_stylesheet_directory() . '/inc/rezepte.php';
require_once get_stylesheet_directory() . '/inc/rezept-felder.php';
require_once get_stylesheet_directory() . '/inc/rezept-tags.php';
require_once get_stylesheet_directory() . '/inc/rezept-duplikate.php';
require_once get_stylesheet_directory() . '/inc/rezept-galerie.php';
require_once get_stylesheet_directory() . '/inc/rezept-ansicht.php';

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'astra-parent', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'napurelon', get_stylesheet_uri(), array( 'astra-parent' ), '1.0.0' );
} );

/**
 * Inline SVG flags, so the switcher does not depend on Polylang's flag images
 * (those 404 on some installs) or on any file inside the theme.
 */
function napurelon_flag_svg( $slug ) {
    $flags = array(
        'de' => '<svg class="napurelon-flag" viewBox="0 0 5 3" role="presentation" focusable="false"><rect width="5" height="3" fill="#000"/><rect y="1" width="5" height="2" fill="#D00"/><rect y="2" width="5" height="1" fill="#FFCE00"/></svg>',
        'ch' => '<svg class="napurelon-flag" viewBox="0 0 32 32" role="presentation" focusable="false"><rect width="32" height="32" fill="#D52B1E"/><rect x="13" y="6" width="6" height="20" fill="#fff"/><rect x="6" y="13" width="20" height="6" fill="#fff"/></svg>',
        'tr' => '<svg class="napurelon-flag" viewBox="0 0 1200 800" role="presentation" focusable="false"><rect width="1200" height="800" fill="#E30A17"/><circle cx="425" cy="400" r="200" fill="#fff"/><circle cx="475" cy="400" r="160" fill="#E30A17"/><polygon fill="#fff" points="860,400 790.9,422.5 790.9,495.1 748.2,436.3 679.1,458.8 721.8,400 679.1,341.2 748.2,363.7 790.9,304.9 790.9,377.5"/></svg>',
    );

    $slug = strtolower( $slug );

    return isset( $flags[ $slug ] ) ? $flags[ $slug ] : '';
}

/**
 * Flag-only language switcher backed by Polylang.
 *
 * Usage: [napurelon_language_switcher] or the "Dil Değiştirici (Bayraklar)" widget,
 * or automatically appended to the primary menu (see below).
 */
function napurelon_language_switcher() {
    if ( ! function_exists( 'pll_the_languages' ) ) {
        return '';
    }

    $languages = pll_the_languages(
        array(
            'raw'                    => 1,
            'hide_if_empty'          => 0,
            'hide_current'           => 0,
            'display_names_as'       => 'name',
            'hide_if_no_translation' => 0,
        )
    );

    if ( empty( $languages ) || ! is_array( $languages ) ) {
        return '';
    }

    $items = '';

    foreach ( $languages as $language ) {
        $classes = array( 'napurelon-lang-switcher__item' );

        if ( ! empty( $language['current_lang'] ) ) {
            $classes[] = 'is-current';
        }

        $flag = ! empty( $language['flag_code'] ) ? napurelon_flag_svg( $language['flag_code'] ) : '';

        if ( '' === $flag ) {
            $flag = napurelon_flag_svg( $language['slug'] );
        }

        if ( '' === $flag ) {
            $flag = '<span class="napurelon-lang-switcher__code">' . esc_html( strtoupper( $language['slug'] ) ) . '</span>';
        }

        $items .= sprintf(
            '<li class="%1$s"><a href="%2$s" lang="%3$s" hreflang="%3$s" title="%4$s" aria-label="%4$s"%5$s>%6$s</a></li>',
            esc_attr( implode( ' ', $classes ) ),
            esc_url( $language['url'] ),
            esc_attr( $language['locale'] ),
            esc_attr( $language['name'] ),
            ! empty( $language['current_lang'] ) ? ' aria-current="true"' : '',
            $flag
        );
    }

    return sprintf(
        '<ul class="napurelon-lang-switcher" role="list">%s</ul>',
        $items
    );
}

add_shortcode( 'napurelon_language_switcher', 'napurelon_language_switcher' );

/**
 * Append the switcher to the Astra primary menu so the flags show up in the header
 * without any theme option or page builder change.
 *
 * Disable with: add_filter( 'napurelon_language_switcher_in_menu', '__return_false' );
 */
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }

    if ( ! apply_filters( 'napurelon_language_switcher_in_menu', true ) ) {
        return $items;
    }

    $switcher = napurelon_language_switcher();

    if ( '' === $switcher ) {
        return $items;
    }

    return $items . sprintf(
        '<li class="menu-item napurelon-lang-switcher-menu-item">%s</li>',
        $switcher
    );
}, 10, 2 );

add_action( 'widgets_init', function () {
    register_widget( 'NaPurelOn_Language_Switcher_Widget' );
} );

class NaPurelOn_Language_Switcher_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'napurelon_language_switcher',
            __( 'Dil Değiştirici (Bayraklar)', 'napurelon' ),
            array( 'description' => __( 'Polylang dillerini bayrak olarak gösterir.', 'napurelon' ) )
        );
    }

    public function widget( $args, $instance ) {
        $switcher = napurelon_language_switcher();

        if ( '' === $switcher ) {
            return;
        }

        echo $args['before_widget'] . $switcher . $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function form( $instance ) {
        echo '<p>' . esc_html__( 'Ayar gerekmez.', 'napurelon' ) . '</p>';
    }
}
