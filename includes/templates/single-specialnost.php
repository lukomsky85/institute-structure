<?php
if (!defined('ABSPATH')) exit;

$post_id = get_the_ID();

$output = '<div class="institute-single-content">';

$fields = [
    'kod' => 'Код специальности',
    'uroven' => 'Уровень образования'
];

foreach ($fields as $key => $label) {
    $value = get_post_meta($post_id, '_specialnost_' . $key, true);
    if ($value) {
        $output .= '<div class="institute-field institute-' . esc_attr($key) . '">';
        $output .= '<strong>' . esc_html($label) . ':</strong> ' . esc_html($value);
        $output .= '</div>';
    }
}

// Описание
$opisanie = get_post_meta($post_id, '_specialnost_opisanie', true);
if ($opisanie) {
    $output .= '<div class="institute-description">';
    $output .= apply_filters('the_content', $opisanie);
    $output .= '</div>';
}

// Привязка к структуре
$pripisann_k = get_post_meta($post_id, '_specialnost_pripisann_k', true);
if ($pripisann_k) {
    if (strpos($pripisann_k, 'kafedra_') === 0) {
        $kafedra_id = intval(str_replace('kafedra_', '', $pripisann_k));
        $kafedra_title = get_the_title($kafedra_id);
        $output .= '<div class="institute-field institute-kafedra">';
        $output .= '<strong>Кафедра:</strong> ';
        $output .= '<a href="' . get_permalink($kafedra_id) . '">' . esc_html($kafedra_title) . '</a>';
        $output .= '</div>';
    } elseif (strpos($pripisann_k, 'srednee_') === 0) {
        $srednee_id = intval(str_replace('srednee_', '', $pripisann_k));
        $srednee_title = get_the_title($srednee_id);
        $output .= '<div class="institute-field institute-srednee">';
        $output .= '<strong>Отделение СПО:</strong> ';
        $output .= '<a href="' . get_permalink($srednee_id) . '">' . esc_html($srednee_title) . '</a>';
        $output .= '</div>';
    }
}

$output .= '</div>';

echo $output;