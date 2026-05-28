<?php
if (!defined('ABSPATH')) exit;

/**
 * Сохранение метаданных факультета
 */
add_action('save_post_fakultet', 'institute_save_fakultet_meta');
function institute_save_fakultet_meta($post_id) {
    if (!isset($_POST['fakultet_meta_nonce']) || !wp_verify_nonce($_POST['fakultet_meta_nonce'], 'institute_save_fakultet_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $keys = ['zaveduyushchiy', 'email', 'phone', 'auditoria', 'site'];
    foreach ($keys as $key) {
        $value = $_POST['fakultet_' . $key] ?? '';
        if ($key === 'email') {
            $value = sanitize_email($value);
        } elseif ($key === 'site') {
            $value = esc_url_raw($value);
        } else {
            $value = sanitize_text_field($value);
        }
        update_post_meta($post_id, '_fakultet_' . $key, $value);
    }
    
    // Фото декана
    $zav_photo_id = absint($_POST['fakultet_zav_photo_id'] ?? 0);
    update_post_meta($post_id, '_fakultet_zav_photo_id', $zav_photo_id);
    
    // Публикации
    $pubs = institute_save_publications('fakultet');
    update_post_meta($post_id, '_fakultet_publications', $pubs);
    
    // Описание
    $opisanie = isset($_POST['fakultet_opisanie']) ? wp_kses_post($_POST['fakultet_opisanie']) : '';
    update_post_meta($post_id, '_fakultet_opisanie', $opisanie);
}

/**
 * Сохранение метаданных специальности
 */
add_action('save_post_specialnost', 'institute_save_specialnost_meta');
function institute_save_specialnost_meta($post_id) {
    if (!isset($_POST['specialnost_meta_nonce']) || !wp_verify_nonce($_POST['specialnost_meta_nonce'], 'institute_save_specialnost_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $kod = sanitize_text_field($_POST['specialnost_kod'] ?? '');
    $uroven = sanitize_text_field($_POST['specialnost_uroven'] ?? '');
    $opisanie = isset($_POST['specialnost_opisanie']) ? wp_kses_post($_POST['specialnost_opisanie']) : '';
    $pripisann_k = $_POST['specialnost_pripisann_k'] ?? '';
    
    if (preg_match('/^(kafedra_\d+|srednee_\d+)$/', $pripisann_k)) {
        update_post_meta($post_id, '_specialnost_pripisann_k', $pripisann_k);
    } else {
        delete_post_meta($post_id, '_specialnost_pripisann_k');
    }
    
    update_post_meta($post_id, '_specialnost_kod', $kod);
    update_post_meta($post_id, '_specialnost_uroven', $uroven);
    update_post_meta($post_id, '_specialnost_opisanie', $opisanie);
}

/**
 * Сохранение метаданных кафедры
 */
add_action('save_post_kafedra', 'institute_save_kafedra_meta');
function institute_save_kafedra_meta($post_id) {
    if (!isset($_POST['kafedra_meta_nonce']) || !wp_verify_nonce($_POST['kafedra_meta_nonce'], 'institute_save_kafedra_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $keys = ['zaveduyushchiy', 'email', 'phone', 'auditoria', 'site'];
    foreach ($keys as $key) {
        $value = $_POST['kafedra_' . $key] ?? '';
        if ($key === 'email') {
            $value = sanitize_email($value);
        } elseif ($key === 'site') {
            $value = esc_url_raw($value);
        } else {
            $value = sanitize_text_field($value);
        }
        update_post_meta($post_id, '_kafedra_' . $key, $value);
    }
    
    // Привязка к факультету
    $fakultet_id = absint($_POST['kafedra_fakultet'] ?? 0);
    update_post_meta($post_id, '_kafedra_fakultet', $fakultet_id);
    
    // Публикации
    $pubs = institute_save_publications('kafedra');
    update_post_meta($post_id, '_kafedra_publications', $pubs);
    
    // Новые блоки
    $nmr = isset($_POST['kafedra_nmr']) ? wp_kses_post($_POST['kafedra_nmr']) : '';
    $nrs = isset($_POST['kafedra_nrs']) ? wp_kses_post($_POST['kafedra_nrs']) : '';
    $uvr = isset($_POST['kafedra_uvr']) ? wp_kses_post($_POST['kafedra_uvr']) : '';
    
    update_post_meta($post_id, '_kafedra_nmr', $nmr);
    update_post_meta($post_id, '_kafedra_nrs', $nrs);
    update_post_meta($post_id, '_kafedra_uvr', $uvr);
    
    // Фото заведующего
    $zav_photo_id = absint($_POST['kafedra_zav_photo_id'] ?? 0);
    update_post_meta($post_id, '_kafedra_zav_photo_id', $zav_photo_id);
    
    // Описание
    $opisanie = isset($_POST['kafedra_opisanie']) ? wp_kses_post($_POST['kafedra_opisanie']) : '';
    update_post_meta($post_id, '_kafedra_opisanie', $opisanie);
}

/**
 * Сохранение метаданных отделения СПО
 */
add_action('save_post_srednee_obrazovanie', 'institute_save_srednee_meta');
function institute_save_srednee_meta($post_id) {
    if (!isset($_POST['srednee_meta_nonce']) || !wp_verify_nonce($_POST['srednee_meta_nonce'], 'institute_save_srednee_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $keys = ['zaveduyushchiy', 'email', 'phone', 'auditoria', 'site'];
    foreach ($keys as $key) {
        $value = $_POST['srednee_' . $key] ?? '';
        if ($key === 'email') {
            $value = sanitize_email($value);
        } elseif ($key === 'site') {
            $value = esc_url_raw($value);
        } else {
            $value = sanitize_text_field($value);
        }
        update_post_meta($post_id, '_srednee_' . $key, $value);
    }
    
    // Публикации
    $pubs = institute_save_publications('srednee');
    update_post_meta($post_id, '_srednee_publications', $pubs);
    
    // Фото заведующего
    $zav_photo_id = absint($_POST['srednee_zav_photo_id'] ?? 0);
    update_post_meta($post_id, '_srednee_zav_photo_id', $zav_photo_id);
    
    // Описание
    $opisanie = isset($_POST['srednee_opisanie']) ? wp_kses_post($_POST['srednee_opisanie']) : '';
    update_post_meta($post_id, '_srednee_opisanie', $opisanie);
}

/**
 * Сохранение метаданных преподавателя
 */
add_action('save_post_prepodavatel', 'institute_save_prepodavatel_meta');
function institute_save_prepodavatel_meta($post_id) {
    if (!isset($_POST['prepodavatel_meta_nonce']) || !wp_verify_nonce($_POST['prepodavatel_meta_nonce'], 'institute_save_prepodavatel_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = ['dolzhnost', 'stepen', 'email', 'phone', 'pripisann_k', 'groups_for_rasp', 'api_teacher_id'];
    foreach ($fields as $field) {
        $value = $_POST['prepodavatel_' . $field] ?? '';
        
        if ($field === 'email') {
            $value = sanitize_email($value);
        } elseif ($field === 'groups_for_rasp') {
            // Очищаем и проверяем формат ID групп
            $value = sanitize_text_field($value);
            $group_ids = array_filter(array_map('trim', explode(',', $value)));
            $valid_groups = [];
            foreach ($group_ids as $group_id) {
                if (is_numeric($group_id) && intval($group_id) > 0) {
                    $valid_groups[] = intval($group_id);
                }
            }
            $value = implode(',', $valid_groups);
            update_post_meta($post_id, '_prepodavatel_groups_for_rasp', $value);
            continue;
        } elseif ($field === 'api_teacher_id') {
            // ID преподавателя в API
            if (!empty($value) && is_numeric($value)) {
                update_post_meta($post_id, '_prepodavatel_api_teacher_id', intval($value));
            } else {
                delete_post_meta($post_id, '_prepodavatel_api_teacher_id');
            }
            continue;
        } elseif ($field === 'pripisann_k') {
            if (preg_match('/^(fakultet_\d+|kafedra_\d+|srednee_\d+)$/', $value)) {
                update_post_meta($post_id, '_prepodavatel_pripisann_k', $value);
            } else {
                delete_post_meta($post_id, '_prepodavatel_pripisann_k');
            }
            continue;
        } else {
            $value = sanitize_text_field($value);
        }
        
        update_post_meta($post_id, '_prepodavatel_' . $field, $value);
    }
    
    // Публикации
    $pubs = institute_save_publications('prepodavatel');
    update_post_meta($post_id, '_prepodavatel_publications', $pubs);
    
    // Фото
    $photo_id = absint($_POST['prepodavatel_photo_id'] ?? 0);
    update_post_meta($post_id, '_prepodavatel_photo_id', $photo_id);
}

/**
 * Вспомогательная функция для сохранения публикаций
 */
function institute_save_publications($prefix) {
    $pubs = [];
    $title_key = ($prefix === 'prepodavatel') ? 'pub_title' : $prefix . '_pub_title';
    
    if (!empty($_POST[$title_key])) {
        foreach ($_POST[$title_key] as $i => $title) {
            if (!empty(trim($title))) {
                $pub_prefix = ($prefix === 'prepodavatel') ? 'pub_' : $prefix . '_pub_';
                
                $pubs[] = [
                    'title' => sanitize_text_field($title),
                    'journal' => sanitize_text_field($_POST[$pub_prefix . 'journal'][$i] ?? ''),
                    'year' => sanitize_text_field($_POST[$pub_prefix . 'year'][$i] ?? ''),
                    'link' => esc_url_raw($_POST[$pub_prefix . 'link'][$i] ?? '')
                ];
            }
        }
    }
    
    return $pubs;
}