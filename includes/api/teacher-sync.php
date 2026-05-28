<?php
/**
 * Синхронизация преподавателей с API расписания
 * Сканирует преподавателей по диапазону ID и сравнивает с существующими в системе
 */

if (!defined('ABSPATH')) exit;

class Institute_Teacher_Sync {

    private const CACHE_KEY_PREFIX = 'institute_teacher_';
    
    /**
     * Получить базовый URL API из настроек
     */
    private function get_api_base_url() {
        if (function_exists('institute_get_api_base_url')) {
            return institute_get_api_base_url();
        }
        // Значение по умолчанию
        return 'https://stud.oivt-sguwt.ru/api/Rasp';
    }
    
    /**
     * Получить время кеширования из настроек
     */
    private function get_cache_ttl() {
        if (function_exists('institute_get_cache_duration')) {
            return institute_get_cache_duration();
        }
        // Значение по умолчанию: 1 час
        return HOUR_IN_SECONDS;
    }
    
    /**
     * Получить список преподавателей из API (сканирование диапазона ID)
     */
    public function scan_teachers_range($start_id = 1, $end_id = 500) {
        $found_teachers = [];
        $date = date('Y-m-d');
        $base_url = $this->get_api_base_url();
        
        for ($teacher_id = $start_id; $teacher_id <= $end_id; $teacher_id++) {
            $cache_key = self::CACHE_KEY_PREFIX . $teacher_id;
            $cached = get_transient($cache_key);
            
            if ($cached !== false) {
                if (!empty($cached['lessons'])) {
                    $found_teachers[] = [
                        'id' => $teacher_id,
                        'name' => $cached['name'],
                        'lessons' => $cached['lessons']
                    ];
                }
                continue;
            }
            
            $api_url = add_query_arg([
                'idTeacher' => $teacher_id,
                'sdate' => $date,
                'edate' => $date
            ], $base_url);
            
            $response = wp_remote_get($api_url, [
                'timeout' => 8,
                'sslverify' => false,
            ]);
            
            if (is_wp_error($response)) {
                error_log("Teacher sync error ID {$teacher_id}: " . $response->get_error_message());
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['data']['rasp']) && is_array($data['data']['rasp'])) {
                // Извлекаем имя преподавателя из первого занятия
                $first_lesson = $data['data']['rasp'][0];
                $teacher_name = $this->extract_teacher_name($first_lesson);
                
                if ($teacher_name) {
                    $found_teachers[] = [
                        'id' => $teacher_id,
                        'name' => $teacher_name,
                        'lessons' => $data['data']['rasp']
                    ];
                    
                    // Кешируем результат
                    $cache_ttl = $this->get_cache_ttl();
                    set_transient($cache_key, [
                        'name' => $teacher_name,
                        'lessons' => $data['data']['rasp']
                    ], $cache_ttl);
                }
            } else {
                // Кешируем отсутствие данных (чтобы не опрашивать часто)
                $cache_ttl = $this->get_cache_ttl();
                set_transient($cache_key, ['name' => '', 'lessons' => []], $cache_ttl * 4);
            }
            
            // Пауза во избежание блокировки API
            usleep(150000); // 150ms
        }
        
        return $found_teachers;
    }
    
    /**
     * Извлечь имя преподавателя из данных занятия
     */
    private function extract_teacher_name($lesson) {
        if (!empty($lesson['фиоПреподавателя'])) {
            return trim($lesson['фиоПреподавателя']);
        }
        if (!empty($lesson['преподаватель'])) {
            return trim($lesson['преподаватель']);
        }
        return '';
    }
    
    /**
     * Сравнить имена преподавателей (фамилия + инициалы)
     */
    public function compare_teacher_names($api_name, $wp_name) {
        $api_name = mb_strtolower(trim($api_name), 'UTF-8');
        $wp_name = mb_strtolower(trim($wp_name), 'UTF-8');
        
        // Полное совпадение
        if ($api_name === $wp_name) {
            return true;
        }
        
        // Сравнение по фамилии
        $api_parts = preg_split('/\s+/', $api_name);
        $wp_parts = preg_split('/\s+/', $wp_name);
        
        if (empty($api_parts[0]) || empty($wp_parts[0])) {
            return false;
        }
        
        // Сравниваем фамилии
        if (mb_stripos($api_parts[0], $wp_parts[0]) === 0 || 
            mb_stripos($wp_parts[0], $api_parts[0]) === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Найти несопоставленных преподавателей
     */
    public function find_unmatched_teachers($api_teachers) {
        $wp_teachers = get_posts([
            'post_type' => 'prepodavatel',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $matched_ids = [];
        $unmatched = [];
        
        // Сопоставление существующих преподавателей
        foreach ($wp_teachers as $wp_id) {
            $wp_name = get_the_title($wp_id);
            $teacher_id_meta = get_post_meta($wp_id, '_prepodavatel_api_teacher_id', true);
            
            foreach ($api_teachers as $api_teacher) {
                if ($this->compare_teacher_names($api_teacher['name'], $wp_name)) {
                    // Обновляем метаполе с ID преподавателя из API
                    update_post_meta($wp_id, '_prepodavatel_api_teacher_id', $api_teacher['id']);
                    
                    // Отмечаем как сопоставленного
                    $matched_ids[] = $api_teacher['id'];
                    break;
                }
            }
        }
        
        // Находим несопоставленных
        foreach ($api_teachers as $api_teacher) {
            if (!in_array($api_teacher['id'], $matched_ids)) {
                $unmatched[] = $api_teacher;
            }
        }
        
        return [
            'matched_count' => count($matched_ids),
            'unmatched' => $unmatched,
            'total_api' => count($api_teachers),
            'total_wp' => count($wp_teachers)
        ];
    }
    
    /**
     * Создать записи для новых преподавателей
     */
    public function create_missing_teachers($unmatched_teachers) {
        $created = [];
        
        foreach ($unmatched_teachers as $teacher) {
            $post_id = wp_insert_post([
                'post_title'    => $teacher['name'],
                'post_type'     => 'prepodavatel',
                'post_status'   => 'draft', // Создаём как черновик для ручной проверки
                'post_content'  => ''
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_prepodavatel_api_teacher_id', $teacher['id']);
                update_post_meta($post_id, '_prepodavatel_needs_review', 'yes');
                $created[] = [
                    'id' => $post_id,
                    'name' => $teacher['name'],
                    'api_id' => $teacher['id']
                ];
            }
        }
        
        return $created;
    }
    
    /**
     * Обновить существующих преподавателей (очистить кеш)
     */
    public function update_existing_teachers() {
        $wp_teachers = get_posts([
            'post_type' => 'prepodavatel',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_prepodavatel_api_teacher_id',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);
        
        $updated = 0;
        $base_url = $this->get_api_base_url();
        
        foreach ($wp_teachers as $teacher) {
            $teacher_id = get_post_meta($teacher->ID, '_prepodavatel_api_teacher_id', true);
            
            if (!empty($teacher_id)) {
                // Очищаем кеш расписания
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+14 days"));
                $api_url = add_query_arg([
                    'idTeacher' => $teacher_id,
                    'sdate' => $start_date,
                    'edate' => $end_date
                ], $base_url);
                
                $cache_key = 'institute_rasp_teacher_' . md5($api_url);
                delete_transient($cache_key);
                
                $updated++;
            }
        }
        
        return $updated;
    }
    
    /**
     * Синхронизировать преподавателей (полный процесс)
     */
    public function sync_all_teachers($start_id = 1, $end_id = 500) {
        // Шаг 1: Сканирование преподавателей из API
        $api_teachers = $this->scan_teachers_range($start_id, $end_id);
        
        if (empty($api_teachers)) {
            return [
                'success' => false,
                'message' => 'Не найдено преподавателей в API'
            ];
        }
        
        // Шаг 2: Найти несопоставленных
        $unmatched_result = $this->find_unmatched_teachers($api_teachers);
        
        // Шаг 3: Создать записи для новых преподавателей
        $created = $this->create_missing_teachers($unmatched_result['unmatched']);
        
        // Шаг 4: Обновить существующих (очистить кеш)
        $updated = $this->update_existing_teachers();
        
        return [
            'success' => true,
            'scanned' => count($api_teachers),
            'matched' => $unmatched_result['matched_count'],
            'unmatched' => count($unmatched_result['unmatched']),
            'created' => count($created),
            'updated' => $updated,
            'new_teachers' => $created
        ];
    }
}