<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lisää Schema.org JobPosting JSON-LD -merkinnät yksittäisen työpaikan sivulle.
 */
function map_output_schema_markup() {
    if ( ! is_singular( 'avoimet_tyopaikat' ) ) {
        return;
    }

    $post_id         = get_the_ID();
    $title           = get_the_title( $post_id );
    $date_posted     = get_the_date( 'Y-m-d', $post_id );
    $valid_through   = get_post_field( 'post_excerpt', $post_id ); // excerpt = hakuajan päättymispäivä
    $original_link   = get_post_meta( $post_id, 'original_rss_link', true );
    $url             = $original_link ? $original_link : get_permalink( $post_id );

    // Työnantajatiedot: yrityksen nimi voidaan asettaa asetuksissa; käytetään bloginfo-fallback
    $opts = function_exists( 'my_agg_get_settings' ) ? my_agg_get_settings() : array();
    $org_name = ! empty( $opts['organization_name'] ) ? $opts['organization_name'] : get_bloginfo( 'name' );

    $schema = array(
        '@context'          => 'https://schema.org',
        '@type'             => 'JobPosting',
        'title'             => $title,
        'datePosted'        => $date_posted,
        'validThrough'      => $valid_through,
        'url'               => $url,
        'employmentType'    => 'FULL_TIME',
        'hiringOrganization' => array(
            '@type' => 'Organization',
            'name'  => $org_name,
        ),
    );

    echo '<script type="application/ld+json">'
        . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
        . '</script>' . "\n";
}
add_action( 'wp_head', 'map_output_schema_markup' );
