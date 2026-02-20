<?php
if (!defined('ABSPATH')) {
    exit;
}

// Rekisteröi lyhytkoodit
add_shortcode('my_jobs_list', 'map_jobs_list_shortcode');
add_shortcode('my_jobs_by_country', 'map_jobs_by_country_shortcode');

/**
 * Lyhytkoodin logiikka
 *
 * @param array $atts Lyhytkoodin attribuutit
 * @return string Työpaikkalistaus HTML-muodossa
 */
function map_jobs_list_shortcode($atts) {
    // Hae asetukset
    $opts = my_agg_get_settings();

    // Polylang: tunnista kieli renderöintihetkellä
    $lang_code = 'fi';
    if (function_exists('pll_current_language')) {
        $current = pll_current_language();
        if ($current) {
            $lang_code = $current;
        }
    }

    // Kielikohtainen label hakuajan päättymiselle
    switch ($lang_code) {
        case 'fi':
            $end_label = 'Hakuaika päättyy';
            break;
        case 'en':
            $end_label = 'Application ends';
            break;
        case 'sv':
            $end_label = 'Ansökan slutar';
            break;
        case 'it':
            $end_label = "L'applicazione termina";
            break;
        default:
            $end_label = 'Application ends';
    }

    // Lyhytkoodin attribuuttien oletukset
    $args = shortcode_atts(array(
        'import' => 'no', // Oletus: ei pakotettua tuontia
    ), $atts);

    // Pakota RSS-syötteen synkronointi, jos `import="yes"`
    if (strtolower($args['import']) === 'yes') {
        map_sync_feed();
    }

    // Hae työpaikat Custom Post Type -tietokannasta
    $query_args = array(
        'post_type'      => 'avoimet_tyopaikat',
        'post_status'    => 'publish',
        'posts_per_page' => $opts['items_count'], // Asetuksista haettu määrä
        'orderby'        => $opts['order_by'],    // Asetuksista haettu järjestyskenttä
        'order'          => $opts['order'],       // Asetuksista haettu järjestyssuunta
    );

    $query = new WP_Query($query_args);

    // Jos ei löydy yhtään työpaikkaa
    if (!$query->have_posts()) {
        switch ($lang_code) {
            case 'fi':
                $no_jobs_text = 'Ei työpaikkoja saatavilla.';
                break;
            case 'sv':
                $no_jobs_text = 'Inga lediga jobb tillgängliga.';
                break;
            case 'it':
                $no_jobs_text = 'Nessun lavoro disponibile.';
                break;
            default:
                $no_jobs_text = 'No jobs available.';
        }
        return '<p>' . esc_html($no_jobs_text) . '</p>';
    }

    // Dynaaminen inline-CSS, jotta värit päivittyvät asetuksista
    $output = '<style>
        .my-job-list { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
        }
        .my-job-list li { 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid rgba(0, 0, 0, 0.25); 
        }
        .my-job-list a { 
            color: ' . esc_attr($opts['link_color']) . '; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 18px; 
        }
        .my-job-list a:hover { 
            color: ' . esc_attr($opts['link_hover_color']) . '; 
            text-decoration: none; 
        }
        .my-job-list .description { 
            color: ' . esc_attr($opts['description_text_color']) . '; 
            font-size: 0.8rem; 
            font-weight: 300; 
            margin-top: 5px; 
        }
    </style>';

    $output .= '<ul class="my-job-list">';
    while ($query->have_posts()) {
        $query->the_post();

        $title   = get_the_title();
        $link    = get_post_meta(get_the_ID(), 'original_rss_link', true);
        $excerpt = get_the_excerpt();

        $output .= '<li>';
        if ($link) {
            $output .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener">' . esc_html($title) . '</a>';
        } else {
            // Jos linkkiä ei ole, näytetään pelkkä otsikko
            $output .= esc_html($title);
        }

        if ($excerpt) {
            $output .= '<div class="description">' . esc_html($end_label . ': ' . $excerpt) . '</div>';
        }

        $output .= '</li>';
    }
    $output .= '</ul>';

    wp_reset_postdata(); // Palautetaan WP:n query-tila

    return $output;
}

/**
 * Lyhytkoodin logiikka: maakohtainen ryhmittely moderneilla korteilla
 *
 * @param array $atts Lyhytkoodin attribuutit
 * @return string Maakohtainen työpaikkalistaus HTML-muodossa
 */
function map_jobs_by_country_shortcode($atts) {
    // Hae asetukset
    $opts = my_agg_get_settings();

    // Polylang: tunnista kieli renderöintihetkellä
    $lang_code = 'fi';
    if (function_exists('pll_current_language')) {
        $current = pll_current_language();
        if ($current) {
            $lang_code = $current;
        }
    }

    // Maat kiinteässä järjestyksessä
    $countries = array(
        'fi' => array('flag' => '🇫🇮', 'fi' => 'Suomi',  'en' => 'Finland', 'sv' => 'Finland',  'it' => 'Finlandia'),
        'se' => array('flag' => '🇸🇪', 'fi' => 'Ruotsi', 'en' => 'Sweden',  'sv' => 'Sverige',  'it' => 'Svezia'),
        'gr' => array('flag' => '🇬🇷', 'fi' => 'Kreikka','en' => 'Greece',  'sv' => 'Grekland', 'it' => 'Grecia'),
        'it' => array('flag' => '🇮🇹', 'fi' => 'Italia', 'en' => 'Italy',   'sv' => 'Italien',  'it' => 'Italia'),
    );

    // Monikieliset UI-tekstit
    $texts = array(
        'fi' => array(
            'end_label'      => 'Hakuaika päättyy',
            'no_jobs'        => 'Ei avoimia työpaikkoja tällä hetkellä.',
            'open_positions' => 'avointa paikkaa',
            'apply'          => 'Hae paikkaa',
            'cta_title'      => 'Etkö löytänyt sopivaa?',
            'cta_text'       => 'Jätä avoin hakemus – otamme yhteyttä, kun sopivia tehtäviä avautuu!',
            'cta_button'     => 'Jätä avoin hakemus',
        ),
        'en' => array(
            'end_label'      => 'Application ends',
            'no_jobs'        => 'No open positions at the moment.',
            'open_positions' => 'open positions',
            'apply'          => 'Apply',
            'cta_title'      => "Didn't find a suitable position?",
            'cta_text'       => 'Submit an open application – we will contact you when matching opportunities arise!',
            'cta_button'     => 'Submit open application',
        ),
        'sv' => array(
            'end_label'      => 'Ansökan slutar',
            'no_jobs'        => 'Inga lediga jobb just nu.',
            'open_positions' => 'lediga jobb',
            'apply'          => 'Ansök',
            'cta_title'      => 'Hittade du inte rätt tjänst?',
            'cta_text'       => 'Skicka en öppen ansökan – vi kontaktar dig när passande tjänster dyker upp!',
            'cta_button'     => 'Skicka öppen ansökan',
        ),
        'it' => array(
            'end_label'      => 'Scadenza',
            'no_jobs'        => 'Nessuna posizione aperta al momento.',
            'open_positions' => 'posizioni aperte',
            'apply'          => 'Candidati',
            'cta_title'      => 'Non hai trovato la posizione giusta?',
            'cta_text'       => 'Invia una candidatura spontanea – ti contatteremo quando ci saranno opportunità adatte!',
            'cta_button'     => 'Invia candidatura spontanea',
        ),
    );
    $t = isset($texts[$lang_code]) ? $texts[$lang_code] : $texts['en'];

    $output = '<div class="map-jobs-by-country">';

    foreach ($countries as $code => $country_data) {
        // Maan nimi nykyisellä kielellä
        $country_name = isset($country_data[$lang_code]) ? $country_data[$lang_code] : $country_data['en'];
        $flag         = $country_data['flag'];

        $query = new WP_Query(array(
            'post_type'      => 'avoimet_tyopaikat',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => 'job_country',
            'meta_value'     => $code,
            'orderby'        => $opts['order_by'],
            'order'          => $opts['order'],
        ));

        $job_count = $query->found_posts;

        if ($job_count > 0) {
            $output .= '<section class="map-country-section" data-country="' . esc_attr($code) . '">';
        } else {
            $output .= '<section class="map-country-section map-country-section--empty" data-country="' . esc_attr($code) . '">';
        }

        $output .= '<div class="map-country-header">';
        $output .= '<h2 class="map-country-title">';
        $output .= '<span class="map-country-flag">' . esc_html($flag) . '</span>';
        $output .= '<span class="map-country-name">' . esc_html($country_name) . '</span>';
        $output .= '</h2>';
        $output .= '<span class="map-country-count">' . esc_html($job_count . ' ' . $t['open_positions']) . '</span>';
        $output .= '</div>';

        if ($job_count > 0) {
            $output .= '<div class="map-jobs-grid">';
            while ($query->have_posts()) {
                $query->the_post();
                $post_id  = get_the_ID();
                $title    = get_the_title();
                $excerpt  = get_the_excerpt();
                $form_url = get_post_meta($post_id, 'job_form_url', true);
                $jobtype  = get_post_meta($post_id, 'job_type', true);
                $worktime = get_post_meta($post_id, 'job_worktime', true);

                // Käytä form_url:ia ensisijaisesti, fallback original_rss_link
                $apply_url = !empty($form_url) ? $form_url : get_post_meta($post_id, 'original_rss_link', true);

                $output .= '<article class="map-job-card">';
                $output .= '<div class="map-job-card__content">';
                $output .= '<h3 class="map-job-card__title">' . esc_html($title) . '</h3>';

                if (!empty($excerpt)) {
                    $output .= '<div class="map-job-card__meta">';
                    $output .= '<span class="map-job-card__deadline">';
                    $output .= '<span class="map-job-card__meta-icon">🕐</span>';
                    $output .= esc_html($t['end_label'] . ': ' . $excerpt);
                    $output .= '</span>';
                    $output .= '</div>';
                }

                if (!empty($jobtype) || !empty($worktime)) {
                    $output .= '<div class="map-job-card__tags">';
                    if (!empty($jobtype)) {
                        $output .= '<span class="map-job-card__tag">' . esc_html($jobtype) . '</span>';
                    }
                    if (!empty($worktime)) {
                        $output .= '<span class="map-job-card__tag">' . esc_html($worktime) . '</span>';
                    }
                    $output .= '</div>';
                }

                $output .= '</div>'; // map-job-card__content

                if (!empty($apply_url)) {
                    $output .= '<div class="map-job-card__action">';
                    $output .= '<a href="' . esc_url($apply_url) . '" target="_blank" rel="noopener" class="map-job-card__apply-btn">';
                    $output .= esc_html($t['apply']) . ' <span class="map-job-card__arrow">→</span>';
                    $output .= '</a>';
                    $output .= '</div>';
                }

                $output .= '</article>';
            }
            $output .= '</div>'; // map-jobs-grid
        } else {
            $output .= '<p class="map-country-empty">' . esc_html($t['no_jobs']) . '</p>';
        }

        $output .= '</section>';
        wp_reset_postdata();
    }

    // CTA-banneri – näytetään vain kerran lopussa, jos avoin hakemus -URL löytyy
    $cta_url = get_option('my_agg_open_application_url', '');
    if (!empty($cta_url)) {
        $output .= '<div class="map-cta-banner">';
        $output .= '<div class="map-cta-banner__content">';
        $output .= '<div class="map-cta-banner__icon">💼</div>';
        $output .= '<h3 class="map-cta-banner__title">' . esc_html($t['cta_title']) . '</h3>';
        $output .= '<p class="map-cta-banner__text">' . esc_html($t['cta_text']) . '</p>';
        $output .= '<a href="' . esc_url($cta_url) . '" target="_blank" rel="noopener" class="map-cta-banner__button">';
        $output .= esc_html($t['cta_button']) . ' <span class="map-cta-banner__arrow">→</span>';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>'; // map-jobs-by-country

    return $output;
}
