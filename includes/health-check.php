<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Site Health -integraatio: rekisteröi testi lisäosan tilasta.
 */
function map_add_site_health_tests( $tests ) {
    $tests['direct']['map_aggregator_status'] = array(
        'label' => __( 'My Aggregator Plugin -tila', 'my-aggregator-plugin' ),
        'test'  => 'map_site_health_test_aggregator',
    );
    return $tests;
}
add_filter( 'site_status_tests', 'map_add_site_health_tests' );

/**
 * Suorittaa Site Health -testin aggregaattorin tilalle.
 *
 * @return array Testitulos.
 */
function map_site_health_test_aggregator() {
    $result = array(
        'label'       => __( 'My Aggregator Plugin toimii oikein', 'my-aggregator-plugin' ),
        'status'      => 'good',
        'badge'       => array(
            'label' => __( 'Aggregaattori', 'my-aggregator-plugin' ),
            'color' => 'blue',
        ),
        'description' => '',
        'actions'     => '',
        'test'        => 'map_aggregator_status',
    );

    $opts     = function_exists( 'my_agg_get_settings' ) ? my_agg_get_settings() : array();
    $feed_url = isset( $opts['feed_url'] ) ? $opts['feed_url'] : '';

    // Tarkistus 1: onko feed URL asetettu
    if ( empty( $feed_url ) ) {
        $result['status']      = 'recommended';
        $result['label']       = __( 'RSS-syötteen URL puuttuu', 'my-aggregator-plugin' );
        $result['description'] = '<p>' . __( 'My Aggregator Plugin -lisäosalle ei ole asetettu RSS-syötteen URL:ia. Aseta se lisäosan asetuksista.', 'my-aggregator-plugin' ) . '</p>';
        $result['actions']     = '<a href="' . esc_url( admin_url( 'admin.php?page=my-agg-settings' ) ) . '">' . __( 'Siirry asetuksiin', 'my-aggregator-plugin' ) . '</a>';
        return $result;
    }

    // Tarkistus 2: milloin viimeisin synkronointi
    $last_sync = get_option( 'my_agg_last_sync', 0 );
    if ( empty( $last_sync ) ) {
        $result['status']      = 'recommended';
        $result['label']       = __( 'Synkronointia ei ole vielä suoritettu', 'my-aggregator-plugin' );
        $result['description'] = '<p>' . __( 'RSS-syötteen synkronointia ei ole vielä suoritettu. Aja synkronointi manuaalisesti tai odota, että WP-Cron ajaa sen automaattisesti.', 'my-aggregator-plugin' ) . '</p>';
        return $result;
    }

    $hours_since = ( time() - (int) $last_sync ) / HOUR_IN_SECONDS;
    if ( $hours_since > 24 ) {
        $result['status']      = 'recommended';
        $result['label']       = __( 'Synkronointi on vanhentunut', 'my-aggregator-plugin' );
        $result['description'] = '<p>' . sprintf(
            /* translators: %d: hours since last sync */
            __( 'Viimeisin synkronointi on %d tuntia sitten. Tarkista WP-Cronin toiminta.', 'my-aggregator-plugin' ),
            (int) $hours_since
        ) . '</p>';
        return $result;
    }

    // Tarkistus 3: oliko virheitä viimeisimmässä synkronoinnissa
    $stats = get_option( 'my_agg_last_sync_stats', array() );
    $import_log = get_option( 'my_agg_import_log', array() );
    if ( is_array( $import_log ) && ! empty( $import_log ) ) {
        $latest = end( $import_log );
        if ( ! empty( $latest['error'] ) ) {
            $result['status']      = 'critical';
            $result['label']       = __( 'Synkronoinnissa on virhe', 'my-aggregator-plugin' );
            $result['description'] = '<p>' . sprintf(
                /* translators: %s: error message */
                __( 'Viimeisin synkronointi päättyi virheeseen: %s', 'my-aggregator-plugin' ),
                esc_html( $latest['error'] )
            ) . '</p>';
            return $result;
        }
    }

    $result['description'] = '<p>' . __( 'RSS-syötteen synkronointi toimii normaalisti.', 'my-aggregator-plugin' ) . '</p>';
    return $result;
}
