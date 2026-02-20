<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rekisteröi turvallisuusparannukset: AJAX-endpoint synkronoinnille ja nonce-lataus.
 */

/**
 * Lataa admin-skriptit: lisää synkronointinonce wp_localize_script:llä.
 * Ajetaan prioriteetilla 20, jotta pääkooditiedoston skriptin rekisteröinti (pri 10) tapahtuu ensin.
 */
function map_security_admin_enqueue( $hook ) {
    if ( 'toplevel_page_my-agg-settings' !== $hook ) {
        return;
    }

    // Varmistetaan, että skripti on rekisteröity ennen lokalisointia
    if ( ! wp_script_is( 'admin-minun-aggregator-plugin-js', 'registered' )
        && ! wp_script_is( 'admin-minun-aggregator-plugin-js', 'enqueued' ) ) {
        return;
    }

    wp_localize_script(
        'admin-minun-aggregator-plugin-js',
        'mapAjax',
        array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'map_sync_nonce' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'map_security_admin_enqueue', 20 );

/**
 * AJAX-endpoint pakotettua synkronointia varten (turvallinen versio).
 * Tarkistaa noncen ja käyttäjän oikeudet ennen ajoa.
 */
function map_force_sync() {
    // Tarkista nonce
    check_ajax_referer( 'map_sync_nonce', 'nonce' );

    // Tarkista käyttäjäoikeudet
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Ei riittäviä oikeuksia.', 'my-aggregator-plugin' ) ), 403 );
        return;
    }

    // Ajetaan synkronointi
    $result = map_sync_feed();

    wp_send_json_success( array(
        'added'   => count( $result['added'] ),
        'removed' => count( $result['removed'] ),
        'updated' => count( $result['updated'] ),
    ) );
}
add_action( 'wp_ajax_map_force_sync', 'map_force_sync' );
