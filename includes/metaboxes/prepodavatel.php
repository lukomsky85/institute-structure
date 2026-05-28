<?php
if (!defined('ABSPATH')) exit;

/**
 * Метабокс для преподавателей
 */
function institute_add_prepodavatel_metabox() {
    add_meta_box(
        'prepodavatel_details',
        __('Данные преподавателя', 'institute-structure'),
        'institute_render_prepodavatel_metabox',
        'prepodavatel',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'institute_add_prepodavatel_metabox');

function institute_render_prepodavatel_metabox($post) {
    wp_nonce_field('institute_save_prepodavatel_meta', 'prepodavatel_meta_nonce');
    
    $fakultety = get_posts(['post_type' => 'fakultet', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $kafedry = get_posts(['post_type' => 'kafedra', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $srednee = get_posts(['post_type' => 'srednee_obrazovanie', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    
    $pripisann_k = get_post_meta($post->ID, '_prepodavatel_pripisann_k', true);
    $dolzhnost = get_post_meta($post->ID, '_prepodavatel_dolzhnost', true);
    $stepen = get_post_meta($post->ID, '_prepodavatel_stepen', true);
    $email = get_post_meta($post->ID, '_prepodavatel_email', true);
    $phone = get_post_meta($post->ID, '_prepodavatel_phone', true);
    $photo_id = get_post_meta($post->ID, '_prepodavatel_photo_id', true);
    $teacher_id = get_post_meta($post->ID, '_prepodavatel_api_teacher_id', true);
    
    echo '<table class="form-table"><tbody>';
    
    // Привязка к структуре
    echo '<tr><th scope="row"><label>' . __('Привязка к структуре', 'institute-structure') . '</label></th><td>';
    echo '<select name="prepodavatel_pripisann_k" id="prepodavatel_pripisann_k" style="width:100%;">';
    echo '<option value="">' . __('— Не выбрано —', 'institute-structure') . '</option>';
    
    if (!empty($fakultety)) {
        echo '<optgroup label="' . __('Факультеты', 'institute-structure') . '">';
        foreach ($fakultety as $f) {
            $selected = ($pripisann_k === 'fakultet_' . $f->ID) ? ' selected' : '';
            echo '<option value="fakultet_' . $f->ID . '"' . $selected . '>' . esc_html($f->post_title) . '</option>';
        }
        echo '</optgroup>';
    }
    
    echo '<optgroup label="' . __('Кафедры', 'institute-structure') . '">';
    foreach ($kafedry as $k) {
        $selected = ($pripisann_k === 'kafedra_' . $k->ID) ? ' selected' : '';
        echo '<option value="kafedra_' . $k->ID . '"' . $selected . '>' . esc_html($k->post_title) . '</option>';
    }
    echo '</optgroup>';
    
    echo '<optgroup label="' . __('Отделения СПО', 'institute-structure') . '">';
    foreach ($srednee as $s) {
        $selected = ($pripisann_k === 'srednee_' . $s->ID) ? ' selected' : '';
        echo '<option value="srednee_' . $s->ID . '"' . $selected . '>' . esc_html($s->post_title) . '</option>';
    }
    echo '</optgroup>';
    
    echo '</select>';
    echo '<p class="description">' . __('Выберите факультет, кафедру или отделение СПО.', 'institute-structure') . '</p>';
    echo '</td></tr>';
    
    // Должность
    echo '<tr><th scope="row"><label>' . __('Должность', 'institute-structure') . '</label></th>';
    echo '<td><input type="text" name="prepodavatel_dolzhnost" value="' . esc_attr($dolzhnost) . '" class="widefat"></td></tr>';
    
    // Учёная степень
    echo '<tr><th scope="row"><label>' . __('Учёная степень / звание', 'institute-structure') . '</label></th>';
    echo '<td><input type="text" name="prepodavatel_stepen" value="' . esc_attr($stepen) . '" class="widefat"></td></tr>';
    
    // Email
    echo '<tr><th scope="row"><label>' . __('Email', 'institute-structure') . '</label></th>';
    echo '<td><input type="email" name="prepodavatel_email" value="' . esc_attr($email) . '" class="widefat"></td></tr>';
    
    // Телефон
    echo '<tr><th scope="row"><label>' . __('Телефон', 'institute-structure') . '</label></th>';
    echo '<td><input type="text" name="prepodavatel_phone" value="' . esc_attr($phone) . '" class="widefat"></td></tr>';
    
    // ID преподавателя в API (обязательное поле)
    echo '<tr><th scope="row"><label>' . __('ID преподавателя в системе расписания', 'institute-structure') . '</label></th>';
    echo '<td>';
    echo '<input type="number" name="prepodavatel_api_teacher_id" value="' . esc_attr($teacher_id) . '" class="small-text" min="1" required>';
    echo '<p class="description">' . __('Числовой идентификатор преподавателя (idTeacher) из системы расписания. Обязательно для отображения расписания.', 'institute-structure') . '</p>';
    echo '</td></tr>';
    
    // Научные публикации
    $pubs = get_post_meta($post->ID, '_prepodavatel_publications', true);
    if (!is_array($pubs)) $pubs = [];
    
    echo '<tr><th scope="row">' . __('Научные публикации', 'institute-structure') . '</th><td>';
    echo '<div id="pubs-container">';
    foreach ($pubs as $i => $pub) {
        echo '<div class="pub-item" style="margin-bottom:10px; padding:8px; background:#f9f9f9; border-radius:4px;">';
        echo '<input type="text" name="pub_title[]" placeholder="' . __('Название статьи', 'institute-structure') . '" value="' . esc_attr($pub['title'] ?? '') . '" style="width:100%; margin-bottom:4px;">';
        echo '<input type="text" name="pub_journal[]" placeholder="' . __('Журнал / сборник', 'institute-structure') . '" value="' . esc_attr($pub['journal'] ?? '') . '" style="width:100%; margin-bottom:4px;">';
        echo '<div style="display:flex; gap:8px;">';
        echo '<input type="text" name="pub_year[]" placeholder="' . __('Год', 'institute-structure') . '" value="' . esc_attr($pub['year'] ?? '') . '" style="width:80px;">';
        echo '<input type="url" name="pub_link[]" placeholder="' . __('DOI / ссылка', 'institute-structure') . '" value="' . esc_url($pub['link'] ?? '') . '" style="flex:1;">';
        echo '<button type="button" class="button remove-pub" style="align-self:end;">' . __('Удалить', 'institute-structure') . '</button>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" class="button" id="add-pub" style="margin-top:10px;">+ ' . __('Добавить публикацию', 'institute-structure') . '</button>';
    echo '</td></tr>';
    
    // Фотография
    echo '<tr><th scope="row">' . __('Фотография', 'institute-structure') . '</th><td>';
    echo '<div id="prepodavatel-photo-preview" style="margin-bottom:10px;">';
    if ($photo_id) {
        echo wp_get_attachment_image($photo_id, 'thumbnail');
    }
    echo '</div>';
    echo '<input type="hidden" id="prepodavatel_photo_id" name="prepodavatel_photo_id" value="' . esc_attr($photo_id) . '">';
    echo '<button type="button" class="button" id="prepodavatel_upload_btn">' . __('Выбрать изображение', 'institute-structure') . '</button> ';
    echo '<button type="button" class="button" id="prepodavatel_remove_btn"' . (!$photo_id ? ' style="display:none;"' : '') . '>' . __('Удалить', 'institute-structure') . '</button>';
    echo '</td></tr>';
    
    echo '</tbody></table>';
}