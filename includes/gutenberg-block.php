<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rekisteröi Gutenberg-lohko my-aggregator/jobs-list.
 */
function map_register_gutenberg_block() {
    // Rekisteröi editor-skripti lohkolle
    $editor_js_path = plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/editor.js';
    if ( file_exists( $editor_js_path ) ) {
        wp_register_script(
            'my-aggregator-editor-script',
            plugins_url( 'blocks/editor.js', dirname( __FILE__ ) ),
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
            filemtime( $editor_js_path ),
            true
        );
    }

    // Rekisteröi lohko PHP:lla (api_version 3)
    register_block_type( 'my-aggregator/jobs-list', array(
        'api_version'     => 3,
        'editor_script'   => 'my-aggregator-editor-script',
        'render_callback' => 'map_jobs_list_shortcode',
        'attributes'      => array(
            'itemsCount' => array(
                'type'    => 'number',
                'default' => 10,
            ),
            'showSearch' => array(
                'type'    => 'boolean',
                'default' => false,
            ),
            'layout'     => array(
                'type'    => 'string',
                'default' => 'list',
                'enum'    => array( 'list', 'grid', 'card' ),
            ),
        ),
    ) );
}
add_action( 'init', 'map_register_gutenberg_block' );
