<?php
/**
 * Zusatzfelder (Post Meta) für den Custom Post Type "Rezepte".
 *
 * Die Felder werden über register_post_meta() registriert (dadurch auch in der
 * REST API bzw. für Gutenberg und Elementor verfügbar) und über eine eigene
 * Metabox im Editor gepflegt.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition aller Rezeptfelder.
 *
 * type:     text | number | textarea | url
 * rest:     false = Feld bleibt privat (nicht in der REST API sichtbar).
 *
 * @return array<string, array<string, mixed>> Feldschlüssel => Konfiguration.
 */
function napurelon_get_rezept_fields() {
    return array(
        'napurelon_einleitung'        => array(
            'label'       => 'Einleitung',
            'type'        => 'textarea',
            'optional'    => true,
            'description' => 'Kurzer Einstiegstext, erscheint vor der Zubereitung.',
        ),
        'napurelon_zutaten'           => array(
            'label'       => 'Zutaten',
            'type'        => 'textarea',
            'description' => 'Eine Zutat pro Zeile. Eine Zeile, die mit ":" endet, wird als Zwischenüberschrift ausgegeben (z. B. "Teig:").',
        ),
        'napurelon_ausbacken'         => array(
            'label'       => 'Ausbacken',
            'type'        => 'textarea',
            'optional'    => true,
            'description' => 'Angaben zum Backen bzw. Erhitzen, erscheinen unter den Zutaten.',
        ),
        'napurelon_zubereitungszeit'  => array(
            'label'       => 'Zubereitungszeit',
            'type'        => 'number',
            'description' => 'Aktive Arbeitszeit in Minuten.',
        ),
        'napurelon_kochzeit'          => array(
            'label'       => 'Kochzeit',
            'type'        => 'number',
            'optional'    => true,
            'description' => 'Koch- bzw. Backzeit in Minuten.',
        ),
        'napurelon_portionen'         => array(
            'label' => 'Portionen',
            'type'  => 'number',
        ),
        'napurelon_menge'             => array(
            'label'       => 'Menge',
            'type'        => 'text',
            'description' => 'z. B. "für eine Anwendung".',
        ),
        'napurelon_haltbarkeit'       => array(
            'label'       => 'Haltbarkeit',
            'type'        => 'text',
            'description' => 'Aufbewahrungsdauer, z. B. "4 Wochen im Kühlschrank".',
        ),
        'napurelon_anwendung'         => array(
            'label'       => 'Anwendung',
            'type'        => 'textarea',
            'description' => 'Wie wird das Produkt angewendet?',
        ),
        'napurelon_einsatzgebiete'    => array(
            'label'       => 'Einsatzgebiete',
            'type'        => 'textarea',
            'description' => 'Wofür bzw. in welchen Bereichen wird es eingesetzt?',
        ),
        'napurelon_tipps'             => array(
            'label'    => 'Tipps & Empfehlungen',
            'type'     => 'textarea',
            'optional' => true,
        ),
        'napurelon_vorteile'          => array(
            'label'    => 'Vorteile',
            'type'     => 'textarea',
            'optional' => true,
        ),
        'napurelon_nachteile'         => array(
            'label'    => 'Nachteile',
            'type'     => 'textarea',
            'optional' => true,
        ),
        'napurelon_hinweise'          => array(
            'label'    => 'Warnhinweise',
            'type'     => 'textarea',
            'optional' => true,
        ),
        'napurelon_quellen'           => array(
            'label'       => 'Quellen',
            'type'        => 'textarea',
            'description' => 'Eine Quelle pro Zeile.',
        ),
        'napurelon_video_url'         => array(
            'label'    => 'Video-URL',
            'type'     => 'url',
            'optional' => true,
        ),
        '_napurelon_interne_notiz'    => array(
            'label'       => 'Interne Notiz',
            'type'        => 'textarea',
            'optional'    => true,
            'rest'        => false,
            'description' => 'Nur in der Verwaltung sichtbar, z. B. Freigabegrund.',
        ),
    );
}

/**
 * Registriert die Felder als Post Meta.
 */
function napurelon_register_rezept_meta() {
    foreach ( napurelon_get_rezept_fields() as $key => $field ) {
        $in_rest = ! isset( $field['rest'] ) || false !== $field['rest'];

        register_post_meta(
            'rezepte',
            $key,
            array(
                'type'              => 'number' === $field['type'] ? 'integer' : 'string',
                'single'            => true,
                'default'           => 'number' === $field['type'] ? 0 : '',
                'description'       => $field['label'],
                'show_in_rest'      => $in_rest,
                'sanitize_callback' => 'napurelon_sanitize_rezept_meta_' . $field['type'],
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }
}

add_action( 'init', 'napurelon_register_rezept_meta' );

/**
 * Sanitize-Callback für einzeilige Textfelder.
 *
 * @param mixed $value Rohwert.
 * @return string
 */
function napurelon_sanitize_rezept_meta_text( $value ) {
    return sanitize_text_field( (string) $value );
}

/**
 * Sanitize-Callback für mehrzeilige Textfelder.
 *
 * @param mixed $value Rohwert.
 * @return string
 */
function napurelon_sanitize_rezept_meta_textarea( $value ) {
    return sanitize_textarea_field( (string) $value );
}

/**
 * Sanitize-Callback für Zahlenfelder.
 *
 * @param mixed $value Rohwert.
 * @return int
 */
function napurelon_sanitize_rezept_meta_number( $value ) {
    return absint( $value );
}

/**
 * Sanitize-Callback für URL-Felder.
 *
 * @param mixed $value Rohwert.
 * @return string
 */
function napurelon_sanitize_rezept_meta_url( $value ) {
    return esc_url_raw( (string) $value );
}

/**
 * Fügt die Metabox "Rezeptdetails" zum Editor hinzu.
 */
function napurelon_add_rezept_meta_box() {
    add_meta_box(
        'napurelon-rezeptdetails',
        'Rezeptdetails',
        'napurelon_render_rezept_meta_box',
        'rezepte',
        'normal',
        'high'
    );
}

add_action( 'add_meta_boxes', 'napurelon_add_rezept_meta_box' );

/**
 * Gibt die Eingabefelder der Metabox aus.
 *
 * @param WP_Post $post Aktueller Beitrag.
 */
function napurelon_render_rezept_meta_box( $post ) {
    wp_nonce_field( 'napurelon_save_rezept_meta', 'napurelon_rezept_meta_nonce' );

    echo '<table class="form-table" role="presentation"><tbody>';

    foreach ( napurelon_get_rezept_fields() as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        $label = $field['label'];

        if ( ! empty( $field['optional'] ) ) {
            $label .= ' <span class="description">(optional)</span>';
        }

        printf(
            '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>',
            esc_attr( $key ),
            wp_kses_post( $label )
        );

        if ( 'textarea' === $field['type'] ) {
            printf(
                '<textarea id="%1$s" name="%1$s" rows="5" class="large-text">%2$s</textarea>',
                esc_attr( $key ),
                esc_textarea( $value )
            );
        } else {
            $input_type = 'number' === $field['type'] ? 'number' : ( 'url' === $field['type'] ? 'url' : 'text' );

            printf(
                '<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="%4$s"%5$s />',
                esc_attr( $input_type ),
                esc_attr( $key ),
                esc_attr( $value ),
                'number' === $field['type'] ? 'small-text' : 'large-text',
                'number' === $field['type'] ? ' min="0" step="1"' : ''
            );
        }

        if ( ! empty( $field['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

/**
 * Speichert die Werte der Metabox.
 *
 * @param int     $post_id Beitrags-ID.
 * @param WP_Post $post    Beitrag.
 */
function napurelon_save_rezept_meta( $post_id, $post ) {
    // Autosave, Revisionen und andere Beitragstypen überspringen.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( 'rezepte' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Nonce prüfen: ohne gültiges Feld stammt der Request nicht aus dem Editor.
    if ( ! isset( $_POST['napurelon_rezept_meta_nonce'] )
        || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['napurelon_rezept_meta_nonce'] ) ), 'napurelon_save_rezept_meta' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    foreach ( napurelon_get_rezept_fields() as $key => $field ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            continue;
        }

        $raw       = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitisierung erfolgt in der Callback-Funktion.
        $sanitizer = 'napurelon_sanitize_rezept_meta_' . $field['type'];
        $value     = call_user_func( $sanitizer, $raw );

        if ( '' === $value || 0 === $value ) {
            delete_post_meta( $post_id, $key );
            continue;
        }

        update_post_meta( $post_id, $key, $value );
    }
}

add_action( 'save_post', 'napurelon_save_rezept_meta', 10, 2 );
