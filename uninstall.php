<?php
// Varmistetaan, että tämä tiedosto ajetaan vain poiston yhteydessä
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Poistetaan kaikki avoimet_tyopaikat -tyypin postaukset
$post_ids = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
        'avoimet_tyopaikat'
    )
);

foreach ( $post_ids as $post_id ) {
    wp_delete_post( (int) $post_id, true ); // Pakota pysyvä poisto
}

// 2. Poistetaan lisäosan optionit
$options_to_delete = array(
    'my_agg_settings',
    'my_agg_import_log',
    'my_agg_last_sync',
    'my_agg_last_sync_stats',
    'my_agg_cache_bump',
);

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// 3. Poistetaan cron-tapahtumat
wp_clear_scheduled_hook( 'map_cron_sync_event' );
wp_clear_scheduled_hook( 'map_aggregator_cron_hook' );

// 4. Poistetaan transientit (pattern: map_jobs_html_*)
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_map_jobs_html_%'
        OR option_name LIKE '_transient_timeout_map_jobs_html_%'"
);
