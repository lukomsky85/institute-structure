<?php
if (!defined('ABSPATH')) exit;

/**
 * Регистрация Custom Post Types
 */
function institute_register_cpt() {

    $parent = 'institute-plugin-info'; // slug главного меню плагина

    $common = [
        'public'         => true,
        'show_ui'        => true,
        'show_in_menu'   => $parent,
        'has_archive'    => true,
        'query_var'      => true,
        'show_in_rest'   => true,
        'map_meta_cap'   => true,
        'capability_type'=> 'post',
    ];

    // Факультеты
    register_post_type('fakultet', array_merge($common, [
        'labels'  => [
            'name'               => __('Факультеты', 'institute-structure'),
            'singular_name'      => __('Факультет', 'institute-structure'),
            'add_new'            => __('Добавить факультет', 'institute-structure'),
            'add_new_item'       => __('Добавить новый факультет', 'institute-structure'),
            'edit_item'          => __('Редактировать факультет', 'institute-structure'),
            'new_item'           => __('Новый факультет', 'institute-structure'),
            'view_item'          => __('Просмотреть факультет', 'institute-structure'),
            'search_items'       => __('Поиск факультетов', 'institute-structure'),
            'not_found'          => __('Факультеты не найдены', 'institute-structure'),
            'not_found_in_trash' => __('Факультеты не найдены в корзине', 'institute-structure'),
        ],
        'supports' => ['title'],
        'rewrite'  => ['slug' => 'fakultety'],
    ]));

    // Кафедры
    register_post_type('kafedra', array_merge($common, [
        'labels'  => [
            'name'               => __('Кафедры', 'institute-structure'),
            'singular_name'      => __('Кафедра', 'institute-structure'),
            'add_new'            => __('Добавить кафедру', 'institute-structure'),
            'add_new_item'       => __('Добавить новую кафедру', 'institute-structure'),
            'edit_item'          => __('Редактировать кафедру', 'institute-structure'),
            'new_item'           => __('Новая кафедра', 'institute-structure'),
            'view_item'          => __('Просмотреть кафедру', 'institute-structure'),
            'search_items'       => __('Поиск кафедр', 'institute-structure'),
            'not_found'          => __('Кафедры не найдены', 'institute-structure'),
            'not_found_in_trash' => __('Кафедры не найдены в корзине', 'institute-structure'),
        ],
        'supports' => ['title'],
        'rewrite'  => ['slug' => 'kafedry'],
    ]));

    // Отделения СПО
    register_post_type('srednee_obrazovanie', array_merge($common, [
        'labels'  => [
            'name'               => __('Отделения СПО', 'institute-structure'),
            'singular_name'      => __('Отделение СПО', 'institute-structure'),
            'add_new'            => __('Добавить отделение', 'institute-structure'),
            'add_new_item'       => __('Добавить новое отделение', 'institute-structure'),
            'edit_item'          => __('Редактировать отделение', 'institute-structure'),
            'new_item'           => __('Новое отделение', 'institute-structure'),
            'view_item'          => __('Просмотреть отделение', 'institute-structure'),
            'search_items'       => __('Поиск отделений', 'institute-structure'),
            'not_found'          => __('Отделения не найдены', 'institute-structure'),
            'not_found_in_trash' => __('Отделения не найдены в корзине', 'institute-structure'),
        ],
        'supports' => ['title'],
        'rewrite'  => ['slug' => 'srednee-obrazovanie'],
    ]));

    // Специальности
    register_post_type('specialnost', array_merge($common, [
        'labels'  => [
            'name'               => __('Специальности', 'institute-structure'),
            'singular_name'      => __('Специальность', 'institute-structure'),
            'add_new'            => __('Добавить специальность', 'institute-structure'),
            'add_new_item'       => __('Добавить новую специальность', 'institute-structure'),
            'edit_item'          => __('Редактировать специальность', 'institute-structure'),
            'new_item'           => __('Новая специальность', 'institute-structure'),
            'view_item'          => __('Просмотреть специальность', 'institute-structure'),
            'search_items'       => __('Поиск специальностей', 'institute-structure'),
            'not_found'          => __('Специальности не найдены', 'institute-structure'),
            'not_found_in_trash' => __('Специальности не найдены в корзине', 'institute-structure'),
        ],
        'supports' => ['title'],
        'rewrite'  => ['slug' => 'specialnosti'],
    ]));

    // Преподаватели
    register_post_type('prepodavatel', array_merge($common, [
        'labels'  => [
            'name'               => __('Преподаватели', 'institute-structure'),
            'singular_name'      => __('Преподаватель', 'institute-structure'),
            'add_new'            => __('Добавить преподавателя', 'institute-structure'),
            'add_new_item'       => __('Добавить нового преподавателя', 'institute-structure'),
            'edit_item'          => __('Редактировать преподавателя', 'institute-structure'),
            'new_item'           => __('Новый преподаватель', 'institute-structure'),
            'view_item'          => __('Просмотреть преподавателя', 'institute-structure'),
            'search_items'       => __('Поиск преподавателей', 'institute-structure'),
            'not_found'          => __('Преподаватели не найдены', 'institute-structure'),
            'not_found_in_trash' => __('Преподаватели не найдены в корзине', 'institute-structure'),
        ],
        'supports' => ['title'],
        'rewrite'  => ['slug' => 'prepodavateli'],
    ]));
}
add_action('init', 'institute_register_cpt', 0);