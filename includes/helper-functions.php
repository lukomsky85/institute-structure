<?php
if (!defined('ABSPATH')) exit;

/**
 * Получить краткое описание (обрезанное)
 */
function institute_get_short_description($content, $words = 30) {
    $content = wp_strip_all_tags($content);
    $words_array = explode(' ', $content);
    if (count($words_array) > $words) {
        return implode(' ', array_slice($words_array, 0, $words)) . '…';
    }
    return $content;
}

/**
 * Сравнить имена преподавателей
 */
function institute_compare_teacher_names($name1, $name2) {
    // Приводим к нижнему регистру и удаляем лишние пробелы
    $name1 = trim(mb_strtolower($name1, 'UTF-8'));
    $name2 = trim(mb_strtolower($name2, 'UTF-8'));
    
    // Если имена полностью совпадают
    if ($name1 === $name2) {
        return true;
    }
    
    // Проверяем частичное совпадение (фамилия + инициалы)
    $parts1 = explode(' ', $name1);
    $parts2 = explode(' ', $name2);
    
    if (count($parts1) >= 1 && count($parts2) >= 1) {
        // Сравниваем фамилии
        if ($parts1[0] === $parts2[0]) {
            return true;
        }
    }
    
    return false;
}

/**
 * Получить название структуры по привязке
 */
function institute_get_structure_name_by_ref($ref) {
    if (empty($ref)) return '';
    
    if (strpos($ref, 'fakultet_') === 0) {
        $id = intval(str_replace('fakultet_', '', $ref));
        return get_the_title($id);
    } elseif (strpos($ref, 'kafedra_') === 0) {
        $id = intval(str_replace('kafedra_', '', $ref));
        return get_the_title($id);
    } elseif (strpos($ref, 'srednee_') === 0) {
        $id = intval(str_replace('srednee_', '', $ref));
        return get_the_title($id);
    }
    
    return '';
}

/**
 * Получить ссылку на структуру по привязке
 */
function institute_get_structure_link_by_ref($ref) {
    if (empty($ref)) return '';
    
    if (strpos($ref, 'fakultet_') === 0) {
        $id = intval(str_replace('fakultet_', '', $ref));
        return get_permalink($id);
    } elseif (strpos($ref, 'kafedra_') === 0) {
        $id = intval(str_replace('kafedra_', '', $ref));
        return get_permalink($id);
    } elseif (strpos($ref, 'srednee_') === 0) {
        $id = intval(str_replace('srednee_', '', $ref));
        return get_permalink($id);
    }
    
    return '';
}

/**
 * Получить тип структуры по привязке
 */
function institute_get_structure_type_by_ref($ref) {
    if (strpos($ref, 'fakultet_') === 0) return 'fakultet';
    if (strpos($ref, 'kafedra_') === 0) return 'kafedra';
    if (strpos($ref, 'srednee_') === 0) return 'srednee_obrazovanie';
    return '';
}

/**
 * Получить преподавателей по привязке
 */
function institute_get_prepodavateli_by_ref($ref) {
    if (empty($ref)) return [];
    
    $query = new WP_Query([
        'post_type' => 'prepodavatel',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_prepodavatel_pripisann_k',
                'value' => $ref,
                'compare' => '='
            ]
        ],
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    return $query->posts;
}

/**
 * Получить специальности по привязке
 */
function institute_get_specialnosti_by_ref($ref) {
    if (empty($ref)) return [];
    
    $query = new WP_Query([
        'post_type' => 'specialnost',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_specialnost_pripisann_k',
                'value' => $ref,
                'compare' => '='
            ]
        ],
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    return $query->posts;
}

/**
 * Отформатировать дату расписания
 */
function institute_format_rasp_date($date_str, $format = 'd.m.Y') {
    $timestamp = strtotime($date_str);
    if ($timestamp === false) return $date_str;
    return date($format, $timestamp);
}

/**
 * Отформатировать день недели
 */
function institute_format_weekday($date_str, $short = true) {
    $timestamp = strtotime($date_str);
    if ($timestamp === false) return '';
    
    $days = $short ? 
        ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'] :
        ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    
    return $days[date('w', $timestamp)];
}

/**
 * Получить иконку для типа занятия
 */
function institute_get_lesson_type_icon($type) {
    $type = mb_strtolower($type, 'UTF-8');
    
    if (strpos($type, 'лекция') !== false) return '📖';
    if (strpos($type, 'практика') !== false) return '🔧';
    if (strpos($type, 'лаборатор') !== false) return '🔬';
    if (strpos($type, 'экзамен') !== false) return '📝';
    if (strpos($type, 'зачет') !== false) return '✅';
    
    return '📚';
}