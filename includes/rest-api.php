<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API -endpoint työpaikkojen hakuun.
 * Route: my-aggregator/v1/jobs (GET)
 */
function map_register_rest_routes() {
    register_rest_route( 'my-aggregator/v1', '/jobs', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'map_rest_get_jobs',
        'permission_callback' => '__return_true',
        'args'                => array(
            'page'     => array(
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'lang'     => array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'map_register_rest_routes' );

/**
 * REST API -callback: palauttaa työpaikat JSON-muodossa.
 *
 * @param WP_REST_Request $request Pyyntö.
 * @return WP_REST_Response JSON-vastaus.
 */
function map_rest_get_jobs( WP_REST_Request $request ) {
    $page     = $request->get_param( 'page' );
    $per_page = $request->get_param( 'per_page' );
    $search   = $request->get_param( 'search' );
    $lang     = $request->get_param( 'lang' );

    // Rajoitetaan per_page järkeväksi
    $per_page = min( max( 1, $per_page ), 100 );
    $page     = max( 1, $page );

    $query_args = array(
        'post_type'      => 'avoimet_tyopaikat',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    );

    // Haku otsikon perusteella
    if ( ! empty( $search ) ) {
        $query_args['s'] = $search;
    }

    // Kielisuodatus Polylangin kautta
    if ( ! empty( $lang ) && function_exists( 'pll_get_post_language' ) ) {
        $query_args['lang'] = $lang;
    }

    $query = new WP_Query( $query_args );
    $jobs  = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $jobs[]  = array(
                'id'               => $post_id,
                'title'            => get_the_title(),
                'excerpt'          => get_the_excerpt(),
                'original_rss_link' => get_post_meta( $post_id, 'original_rss_link', true ),
                'date'             => get_the_date( 'c' ),
            );
        }
        wp_reset_postdata();
    }

    return new WP_REST_Response( array(
        'jobs'        => $jobs,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
    ), 200 );
}
