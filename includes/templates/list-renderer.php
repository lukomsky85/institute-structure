<?php
if (!defined('ABSPATH')) exit;

/**
 * Универсальный рендеринг списков структуры
 */
function institute_render_structure_list($post_type, $meta_prefix) {
    $query = new WP_Query([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $output = '<div class="institute-structure-list">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $zaveduyushchiy = get_post_meta($post_id, $meta_prefix . 'zaveduyushchiy', true);
            $email          = get_post_meta($post_id, $meta_prefix . 'email', true);
            $phone          = get_post_meta($post_id, $meta_prefix . 'phone', true);
            $auditoria      = get_post_meta($post_id, $meta_prefix . 'auditoria', true);
            $opisanie       = get_post_meta($post_id, $meta_prefix . 'opisanie', true);
            $zav_photo_id   = get_post_meta($post_id, $meta_prefix . 'zav_photo_id', true);
            $site           = get_post_meta($post_id, $meta_prefix . 'site', true);
            $fakultet_id    = ($post_type === 'kafedra') ? get_post_meta($post_id, '_kafedra_fakultet', true) : null;

            $zav_label = ($post_type === 'fakultet') ? 'Декан' : 'Заведующий';

            $output .= '<a href="' . esc_url(get_permalink()) . '" class="institute-card">';

            // Верхняя полоска с фото и именем заведующего
            $output .= '<div class="icard-top">';
            if ($zav_photo_id) {
                $output .= '<div class="icard-photo">';
                $output .= wp_get_attachment_image($zav_photo_id, 'thumbnail', false, ['class' => 'icard-photo-img']);
                $output .= '</div>';
            } else {
                $output .= '<div class="icard-photo icard-photo--empty">';
                $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>';
                $output .= '</div>';
            }
            $output .= '<div class="icard-zav-info">';
            if ($zaveduyushchiy) {
                $output .= '<div class="icard-zav-label">' . esc_html($zav_label) . '</div>';
                $output .= '<div class="icard-zav-name">' . esc_html($zaveduyushchiy) . '</div>';
            }
            $output .= '</div>';
            $output .= '</div>'; // /icard-top

            // Название кафедры
            $output .= '<div class="icard-title">' . esc_html(get_the_title()) . '</div>';

            // Мета-строки
            $output .= '<div class="icard-meta">';

            if ($post_type === 'kafedra' && $fakultet_id) {
                $fak_title = get_the_title($fakultet_id);
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
                $output .= '<span>' . esc_html($fak_title) . '</span>';
                $output .= '</div>';
            }
            if ($email) {
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
                $output .= '<span>' . esc_html($email) . '</span>';
                $output .= '</div>';
            }
            if ($phone) {
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.39 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
                $output .= '<span>' . esc_html($phone) . '</span>';
                $output .= '</div>';
            }
            if ($auditoria) {
                // Обрезаем длинный адрес до первой точки с запятой или 60 символов
                $auditoria_short = mb_strlen($auditoria) > 60
                    ? mb_substr($auditoria, 0, strpos($auditoria . ';', ';')) ?: mb_substr($auditoria, 0, 60) . '…'
                    : $auditoria;
                $output .= '<div class="icard-meta-row">';
                $output .= '<svg class="icard-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
                $output .= '<span>' . esc_html($auditoria_short) . '</span>';
                $output .= '</div>';
            }

            $output .= '</div>'; // /icard-meta

            // Краткое описание
            if ($opisanie) {
                $short = institute_get_short_description($opisanie, 25);
                $output .= '<div class="icard-desc">' . esc_html($short) . '</div>';
            }

            // Кнопка
            $output .= '<div class="icard-footer">';
            $output .= '<span class="icard-link">Подробнее';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
            $output .= '</span>';
            $output .= '</div>';

            $output .= '</a>'; // /institute-card
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>Записи не найдены.</p>';
    }

    $output .= '</div>';
    return $output;
}
