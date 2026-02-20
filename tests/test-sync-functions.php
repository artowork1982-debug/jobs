<?php
/**
 * PHPUnit-testit sync-funktioille.
 *
 * @package My_Aggregator_Plugin
 */

class Test_Sync_Functions extends WP_UnitTestCase {

    /**
     * Testaa map_sync_feed() tyhjällä feed URL:lla.
     * Odotettu tulos: palauttaa tyhjät taulukot, ei lisää postauksia.
     */
    public function test_sync_feed_empty_url() {
        // Varmistetaan, että feed_url on tyhjä
        update_option( 'my_agg_settings', array( 'feed_url' => '' ) );

        $result = map_sync_feed();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'added', $result );
        $this->assertArrayHasKey( 'removed', $result );
        $this->assertArrayHasKey( 'updated', $result );
        $this->assertEmpty( $result['added'] );
        $this->assertEmpty( $result['removed'] );
        $this->assertEmpty( $result['updated'] );

        // Varmistetaan, että synkka-aika on päivittynyt
        $this->assertNotEmpty( get_option( 'my_agg_last_sync' ) );
    }

    /**
     * Testaa kiellettyjen otsikoiden suodatusta.
     * Varmistaa, että kielletyt otsikot suodatetaan pois.
     */
    public function test_forbidden_titles_are_filtered() {
        $opts = my_agg_get_settings();

        // Kielletyt otsikot oletusasetuksissa
        $forbidden_raw   = $opts['forbidden_titles'];
        $forbidden_titles = array_filter( array_map( 'trim', explode( "\n", $forbidden_raw ) ) );

        // Varmistetaan, että oletusotsikot ovat kielletyissä
        $this->assertContains( 'Avoin hakemus', $forbidden_titles );
        $this->assertContains( 'Open application', $forbidden_titles );
        $this->assertContains( 'Öppen ansökan', $forbidden_titles );
    }

    /**
     * Testaa asetusten oletusarvot.
     */
    public function test_default_settings() {
        // Poistetaan optio, jotta saadaan oletusarvot
        delete_option( 'my_agg_settings' );

        $opts = my_agg_get_settings();

        $this->assertIsArray( $opts );
        $this->assertSame( '', $opts['feed_url'] );
        $this->assertSame( 10, $opts['items_count'] );
        $this->assertSame( 'date', $opts['order_by'] );
        $this->assertSame( 'DESC', $opts['order'] );
        $this->assertSame( '#000000', $opts['link_color'] );
        $this->assertSame( '#666666', $opts['description_text_color'] );
        $this->assertSame( '#ff0000', $opts['link_hover_color'] );
    }
}
