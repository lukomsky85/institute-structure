<?php
if (!defined('ABSPATH')) exit;

/**
 * Шорткод: [fakultety]
 */
add_shortcode('fakultety', 'institute_shortcode_fakultety');
function institute_shortcode_fakultety() {
    return institute_render_structure_list('fakultet', '_fakultet_');
}

/**
 * Шорткод: [kafedry]
 */
add_shortcode('kafedry', 'institute_shortcode_kafedry');
function institute_shortcode_kafedry() {
    return institute_render_structure_list('kafedra', '_kafedra_');
}

/**
 * Шорткод: [srednee_obrazovanie]
 */
add_shortcode('srednee_obrazovanie', 'institute_shortcode_srednee_obrazovanie');
function institute_shortcode_srednee_obrazovanie() {
    return institute_render_structure_list('srednee_obrazovanie', '_srednee_');
}

/**
 * Шорткод: [specialnosti kafedra="123" srednee="456"]
 */
add_shortcode('specialnosti', 'institute_shortcode_specialnosti');
function institute_shortcode_specialnosti($atts) {
    $atts = shortcode_atts(['kafedra' => '', 'srednee' => ''], $atts);

    $meta_query = [];
    if (!empty($atts['kafedra'])) {
        $meta_query = [['key' => '_specialnost_pripisann_k', 'value' => 'kafedra_' . (int)$atts['kafedra'], 'compare' => '=']];
    } elseif (!empty($atts['srednee'])) {
        $meta_query = [['key' => '_specialnost_pripisann_k', 'value' => 'srednee_' . (int)$atts['srednee'], 'compare' => '=']];
    }

    $query = new WP_Query([
        'post_type'      => 'specialnost',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => $meta_query,
    ]);

    $output = '<div class="institute-structure-list">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id  = get_the_ID();
            $kod      = get_post_meta($post_id, '_specialnost_kod', true);
            $uroven   = get_post_meta($post_id, '_specialnost_uroven', true);
            $opisanie = get_post_meta($post_id, '_specialnost_opisanie', true);

            $output .= '<a href="' . esc_url(get_permalink()) . '" class="institute-card">';
            $output .= '<div class="icard-title">' . esc_html(get_the_title()) . '</div>';
            $output .= '<div class="icard-meta">';
            if ($kod) {
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>';
                $output .= '<span>' . esc_html($kod) . '</span>';
                $output .= '</div>';
            }
            if ($uroven) {
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>';
                $output .= '<span>' . esc_html($uroven) . '</span>';
                $output .= '</div>';
            }
            $output .= '</div>';
            if ($opisanie) {
                $short = institute_get_short_description($opisanie, 25);
                $output .= '<div class="icard-desc">' . esc_html($short) . '</div>';
            }
            $output .= '<div class="icard-footer"><span class="icard-link">Подробнее';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
            $output .= '</span></div>';
            $output .= '</a>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>Специальности не найдены.</p>';
    }

    $output .= '</div>';
    return $output;
}

/**
 * Шорткод: [prepodavateli fakultet="123" kafedra="456" srednee="789"]
 */
add_shortcode('prepodavateli', 'institute_shortcode_prepodavateli');
function institute_shortcode_prepodavateli($atts) {
    $atts = shortcode_atts(['fakultet' => '', 'kafedra' => '', 'srednee' => ''], $atts);

    $meta_query = [];
    if (!empty($atts['fakultet'])) {
        $meta_query = [['key' => '_prepodavatel_pripisann_k', 'value' => 'fakultet_' . (int)$atts['fakultet'], 'compare' => '=']];
    } elseif (!empty($atts['kafedra'])) {
        $meta_query = [['key' => '_prepodavatel_pripisann_k', 'value' => 'kafedra_' . (int)$atts['kafedra'], 'compare' => '=']];
    } elseif (!empty($atts['srednee'])) {
        $meta_query = [['key' => '_prepodavatel_pripisann_k', 'value' => 'srednee_' . (int)$atts['srednee'], 'compare' => '=']];
    }

    $query = new WP_Query([
        'post_type'      => 'prepodavatel',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => $meta_query,
    ]);

    $output = '<div class="institute-prepod-grid">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id   = get_the_ID();
            $dolzhnost = get_post_meta($post_id, '_prepodavatel_dolzhnost', true);
            $stepen    = get_post_meta($post_id, '_prepodavatel_stepen', true);
            $photo_id  = get_post_meta($post_id, '_prepodavatel_photo_id', true);

            $output .= '<a href="' . esc_url(get_permalink()) . '" class="prepod-card">';
            $output .= '<div class="prepod-card-photo">';
            if ($photo_id) {
                $output .= wp_get_attachment_image($photo_id, 'medium', false, ['class' => 'prepod-card-img']);
            } else {
                $output .= '<div class="prepod-card-no-photo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>';
            }
            $output .= '</div>';
            $output .= '<div class="prepod-card-info">';
            $output .= '<div class="prepod-card-name">' . esc_html(get_the_title()) . '</div>';
            if ($dolzhnost) $output .= '<div class="prepod-card-dolzhnost">' . esc_html($dolzhnost) . '</div>';
            if ($stepen)    $output .= '<div class="prepod-card-stepen">' . esc_html($stepen) . '</div>';
            $output .= '</div>';
            $output .= '</a>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>Преподаватели не найдены.</p>';
    }

    $output .= '</div>';
    return $output;
}

/**
 * Шорткод: [prepod_rasp]
 */
add_shortcode('prepod_rasp', 'institute_shortcode_prepod_rasp');
function institute_shortcode_prepod_rasp($atts) {
    $atts = shortcode_atts([
        'id'         => 0,
        'days'       => 14,
        'show_empty' => 'false',
        'compact'    => 'false',
    ], $atts);

    $prepod_id = absint($atts['id']);

    if (!$prepod_id) {
        global $post;
        if (isset($post) && $post->post_type === 'prepodavatel') {
            $prepod_id = $post->ID;
        } else {
            return '<p class="institute-error">Укажите ID преподавателя: [prepod_rasp id="123"]</p>';
        }
    }

    $rasp_data = institute_get_prepod_rasp($prepod_id, $atts['days']);

    if (!$rasp_data || empty($rasp_data)) {
        if ($atts['show_empty'] === 'true') {
            return '<div class="institute-no-rasp">Расписание отсутствует или не настроено.</div>';
        }
        return '';
    }

    $renderer_file = INSTITUTE_PLUGIN_DIR . 'includes/templates/raspisanie-renderer.php';
    if (file_exists($renderer_file)) {
        require_once $renderer_file;
        if ($atts['compact'] === 'true' && function_exists('institute_render_compact_rasp')) {
            return institute_render_compact_rasp($rasp_data, get_the_title($prepod_id));
        }
        if (function_exists('institute_render_detailed_rasp')) {
            return institute_render_detailed_rasp($rasp_data, get_the_title($prepod_id));
        }
    }

    return '<p class="institute-error">Ошибка загрузки рендерера расписания.</p>';
}
