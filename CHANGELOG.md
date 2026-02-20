# Changelog

Kaikki merkittävät muutokset dokumentoidaan tähän tiedostoon.

## [3.0.0] - 2026-02-20

### Lisätty
- **REST API** (`includes/rest-api.php`): Uusi endpoint `my-aggregator/v1/jobs` (GET) työpaikkojen hakuun, suodatukseen ja sivutukseen. Parametrit: `page`, `per_page`, `search`, `lang`.
- **Gutenberg-blokki** (`includes/gutenberg-block.php`, `blocks/editor.js`): Rekisteröi lohkon `my-aggregator/jobs-list` (api_version 3) attribuuteilla `itemsCount`, `showSearch` ja `layout`.
- **Schema.org / JSON-LD** (`includes/schema-markup.php`): `JobPosting`-merkinnät `wp_head`-hookilla yksittäisen työpaikan sivuille.
- **Turvallisuusparannukset** (`includes/security-improvements.php`): AJAX-endpoint `map_force_sync` nonce- ja käyttöoikeustarkistuksilla; synkronointinonce ladataan `wp_localize_script`-funktiolla.
- **Uninstall-hook** (`uninstall.php`): Siisti poistotoiminto poistaa CPT-postaukset, optionit, cron-tapahtumat ja transientit.
- **WP-CLI-komento** (`includes/wp-cli.php`): `wp aggregator sync` ajaa `map_sync_feed()` ja tulostaa yhteenvedon.
- **Site Health -integraatio** (`includes/health-check.php`): Testi tarkistaa feed URL:n, viimeisimmän synkronoinnin ajankohdan ja mahdolliset virheet.
- **PHPUnit-testit** (`tests/test-sync-functions.php`): Testit tyhjälle URL:lle, kiellettyjen otsikoiden suodatukselle ja asetusten oletusarvoille.

### Muutettu
- Plugin-headerin `Version` päivitetty `3.0.0`:ksi.
- Plugin-headerin `Description` päivitetty kuvaamaan uusia ominaisuuksia.
- Lisätty `Requires at least: 5.8` ja `Requires PHP: 7.4` plugin-headeriin.
- `my-aggregator-plugin.php`: lisätty `require_once`-kutsut uusille tiedostoille.

## [2.0.0] - 2025

### Lisätty
- Builder-suoja (Elementor/Gutenberg)
- HTML-välimuisti (transientit)
- Polylang-monikielisyystuki (fi, en, sv, it)
- Tuontilokin sivutus ja "näytä vain muutokset" -suodatin
- Mukautettu cron-intervalli (3 tuntia)

## [1.3.1] - Aiempi versio

### Muutettu
- Perusominaisuudet: CPT `avoimet_tyopaikat`, WP-Cron, shortcode `[my_jobs_list]`
