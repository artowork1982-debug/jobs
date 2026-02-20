<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ladataan vain kun WP-CLI on käytössä
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * WP-CLI-komennot My Aggregator Plugin -lisäosalle.
 */
class MAP_CLI_Command extends WP_CLI_Command {

    /**
     * Ajaa RSS-syötteen synkronoinnin manuaalisesti.
     *
     * ## EXAMPLES
     *
     *     wp aggregator sync
     *
     * @subcommand sync
     */
    public function sync( $args, $assoc_args ) {
        WP_CLI::log( 'Aloitetaan synkronointi...' );

        $result = map_sync_feed();

        $added   = count( $result['added'] );
        $removed = count( $result['removed'] );
        $updated = count( $result['updated'] );
        $error   = isset( $result['error'] ) ? $result['error'] : '';

        WP_CLI::log( sprintf( 'Lisätty: %d', $added ) );
        WP_CLI::log( sprintf( 'Poistettu: %d', $removed ) );
        WP_CLI::log( sprintf( 'Päivitetty: %d', $updated ) );

        if ( ! empty( $error ) ) {
            WP_CLI::warning( 'Virhe: ' . $error );
        } else {
            WP_CLI::success( 'Synkronointi valmis.' );
        }
    }
}

WP_CLI::add_command( 'aggregator', 'MAP_CLI_Command' );
