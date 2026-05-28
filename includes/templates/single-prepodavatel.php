<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();

$output = '<div class="institute-single-content institute-prepodavatel-single">';

// ── Шапка: фото + поля в одном grid-блоке ──────────────────────────────────
$output .= '<div class="prepod-header-block">';

// Фото
$photo_id = get_post_meta($post_id, '_prepodavatel_photo_id', true);
if ($photo_id) {
    $output .= '<div class="prepod-header-photo">';
    $output .= wp_get_attachment_image($photo_id, 'medium', false, ['class' => 'prepod-photo']);
    $output .= '</div>';
}

// Поля
$output .= '<div class="prepod-header-fields">';

$fields = [
    'dolzhnost' => 'Должность',
    'stepen'    => 'Учёная степень / звание',
    'email'     => 'Email',
    'phone'     => 'Телефон',
];

foreach ($fields as $key => $label) {
    $value = get_post_meta($post_id, '_prepodavatel_' . $key, true);
    if ($value) {
        $output .= '<div class="institute-field institute-' . esc_attr($key) . '">';
        $output .= '<strong>' . esc_html($label) . '</strong>';
        if ($key === 'email') {
            $output .= '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
        } else {
            $output .= '<span>' . esc_html($value) . '</span>';
        }
        $output .= '</div>';
    }
}

// Привязка к структуре
$pripisann_k = get_post_meta($post_id, '_prepodavatel_pripisann_k', true);
if ($pripisann_k) {
    if (strpos($pripisann_k, 'fakultet_') === 0) {
        $struct_id    = intval(str_replace('fakultet_', '', $pripisann_k));
        $struct_title = get_the_title($struct_id);
        $output .= '<div class="institute-field institute-fakultet">';
        $output .= '<strong>Факультет</strong>';
        $output .= '<a href="' . get_permalink($struct_id) . '">' . esc_html($struct_title) . '</a>';
        $output .= '</div>';
    } elseif (strpos($pripisann_k, 'kafedra_') === 0) {
        $struct_id    = intval(str_replace('kafedra_', '', $pripisann_k));
        $struct_title = get_the_title($struct_id);
        $output .= '<div class="institute-field institute-kafedra">';
        $output .= '<strong>Кафедра</strong>';
        $output .= '<a href="' . get_permalink($struct_id) . '">' . esc_html($struct_title) . '</a>';
        $output .= '</div>';
    } elseif (strpos($pripisann_k, 'srednee_') === 0) {
        $struct_id    = intval(str_replace('srednee_', '', $pripisann_k));
        $struct_title = get_the_title($struct_id);
        $output .= '<div class="institute-field institute-srednee">';
        $output .= '<strong>Отделение СПО</strong>';
        $output .= '<a href="' . get_permalink($struct_id) . '">' . esc_html($struct_title) . '</a>';
        $output .= '</div>';
    }
}

$output .= '</div>'; // /prepod-header-fields
$output .= '</div>'; // /prepod-header-block

// ── Научные публикации ──────────────────────────────────────────────────────
$pubs = get_post_meta($post_id, '_prepodavatel_publications', true);
if (!empty($pubs) && is_array($pubs)) {
    $output .= '<div class="institute-publications">';
    $output .= '<h3>Научные публикации</h3>';
    $output .= '<ul>';
    foreach ($pubs as $pub) {
        $output .= '<li>';
        $output .= '<strong>' . esc_html($pub['title']) . '</strong><br>';
        $output .= esc_html($pub['journal']);
        if (!empty($pub['year'])) {
            $output .= ' (' . esc_html($pub['year']) . ')';
        }
        if (!empty($pub['link'])) {
            $output .= ' <a href="' . esc_url($pub['link']) . '" target="_blank">[ссылка]</a>';
        }
        $output .= '</li>';
    }
    $output .= '</ul></div>';
}

// ── Расписание — на всю ширину ──────────────────────────────────────────────
$teacher_id = get_post_meta($post_id, '_prepodavatel_api_teacher_id', true);

$output .= '<div class="institute-rasp-section">';
$output .= '<h3>Расписание занятий</h3>';

if (!empty($teacher_id)) {
    $output .= do_shortcode('[prepod_rasp id="' . $post_id . '" days="14" compact="false"]');
} else {
    $output .= '<p class="institute-no-rasp">Расписание недоступно. Администратор должен указать ID преподавателя в системе расписания.</p>';
}

$output .= '</div>'; // /institute-rasp-section

$output .= '</div>'; // /institute-single-content

echo $output;
?>