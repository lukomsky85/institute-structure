<?php
if (!defined('ABSPATH')) exit;

/**
 * Метабокс для специальностей
 */
function institute_add_specialnost_metabox() {
    add_meta_box(
        'specialnost_details',
        __('Данные специальности', 'institute-structure'),
        'institute_render_specialnost_metabox',
        'specialnost',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'institute_add_specialnost_metabox');

function institute_render_specialnost_metabox($post) {
    wp_nonce_field('institute_save_specialnost_meta', 'specialnost_meta_nonce');
    
    $kod = get_post_meta($post->ID, '_specialnost_kod', true);
    $uroven = get_post_meta($post->ID, '_specialnost_uroven', true);
    $opisanie = get_post_meta($post->ID, '_specialnost_opisanie', true);
    $pripisann_k = get_post_meta($post->ID, '_specialnost_pripisann_k', true);
    
    // Код специальности
    echo '<p>';
    echo '<label for="specialnost_kod">' . __('Код специальности', 'institute-structure') . '</label><br>';
    echo '<input type="text" id="specialnost_kod" name="specialnost_kod" value="' . esc_attr($kod) . '" class="widefat">';
    echo '</p>';
    
    // Уровень образования
    echo '<p>';
    echo '<label for="specialnost_uroven">' . __('Уровень образования', 'institute-structure') . '</label><br>';
    $urovni = ['СПО', 'Бакалавриат', 'Магистратура', 'Аспирантура'];
    echo '<select name="specialnost_uroven" id="specialnost_uroven" class="widefat">';
    foreach ($urovni as $u) {
        $selected = selected($uroven, $u, false);
        echo '<option value="' . esc_attr($u) . '"' . $selected . '>' . esc_html($u) . '</option>';
    }
    echo '</select>';
    echo '</p>';
    
    // Привязка к структуре
    $kafedry = get_posts(['post_type' => 'kafedra', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $srednee = get_posts(['post_type' => 'srednee_obrazovanie', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    
    echo '<p>';
    echo '<label>' . __('Привязка к структуре', 'institute-structure') . '</label><br>';
    echo '<select name="specialnost_pripisann_k" id="specialnost_pripisann_k" style="width:100%;">';
    echo '<option value="">' . __('— Не выбрано —', 'institute-structure') . '</option>';
    
    if (!empty($kafedry)) {
        echo '<optgroup label="' . __('Кафедры', 'institute-structure') . '">';
        foreach ($kafedry as $k) {
            $selected = ($pripisann_k === 'kafedra_' . $k->ID) ? ' selected' : '';
            echo '<option value="kafedra_' . $k->ID . '"' . $selected . '>' . esc_html($k->post_title) . '</option>';
        }
        echo '</optgroup>';
    }
    
    if (!empty($srednee)) {
        echo '<optgroup label="' . __('Отделения СПО', 'institute-structure') . '">';
        foreach ($srednee as $s) {
            $selected = ($pripisann_k === 'srednee_' . $s->ID) ? ' selected' : '';
            echo '<option value="srednee_' . $s->ID . '"' . $selected . '>' . esc_html($s->post_title) . '</option>';
        }
        echo '</optgroup>';
    }
    
    echo '</select>';
    echo '<p class="description">' . __('Выберите кафедру или отделение СПО.', 'institute-structure') . '</p>';
    echo '</p>';
    
    // Описание
    echo '<p><label for="specialnost_opisanie">' . __('Описание', 'institute-structure') . '</label></p>';
    wp_editor($opisanie, 'specialnost_opisanie', [
        'textarea_name' => 'specialnost_opisanie',
        'textarea_rows' => 8,
        'media_buttons' => false
    ]);
}