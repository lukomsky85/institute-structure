<?php
if (!defined('ABSPATH')) exit;

/**
 * Инициализация настроек плагина
 */
function institute_rasp_settings_init() {
    register_setting('institute_rasp_settings', 'institute_rasp_api_base_url');
    register_setting('institute_rasp_settings', 'institute_rasp_cache_duration');
    
    add_settings_section(
        'institute_rasp_api_section',
        'Настройки расписания',
        'institute_rasp_settings_section_callback',
        'institute-rasp-settings'
    );
    
    add_settings_field(
        'institute_rasp_api_base_url',
        'Базовый URL API',
        'institute_rasp_api_base_url_callback',
        'institute-rasp-settings',
        'institute_rasp_api_section'
    );
    
    add_settings_field(
        'institute_rasp_cache_duration',
        'Время кеширования (секунды)',
        'institute_rasp_cache_duration_callback',
        'institute-rasp-settings',
        'institute_rasp_api_section'
    );
}
add_action('admin_init', 'institute_rasp_settings_init');

/**
 * Callback для секции настроек
 */
function institute_rasp_settings_section_callback() {
    echo '<p>Настройте параметры подключения к системе расписания</p>';
}

/**
 * Callback для поля базового URL
 */
function institute_rasp_api_base_url_callback() {
    $url = get_option('institute_rasp_api_base_url', 'https://stud.oivt-sguwt.ru/api/Rasp');
    echo '<input 
            type="url" 
            name="institute_rasp_api_base_url" 
            value="' . esc_attr($url) . '" 
            class="regular-text"
            placeholder="https://stud.oivt-sguwt.ru/api/Rasp"
            required>';
    echo '<p class="description">Базовый адрес для получения расписания (без завершающего слеша)</p>';
}

/**
 * Callback для поля времени кеширования
 */
function institute_rasp_cache_duration_callback() {
    $duration = get_option('institute_rasp_cache_duration', HOUR_IN_SECONDS);
    echo '<input 
            type="number" 
            name="institute_rasp_cache_duration" 
            value="' . esc_attr($duration) . '" 
            class="small-text"
            min="60"
            step="60">';
    echo '<p class="description">Время хранения кеша в секундах (по умолчанию: 3600 = 1 час)</p>';
}

/**
 * Добавить страницу настроек в меню админки
 */
function institute_add_rasp_settings_page() {
    add_submenu_page(
        'institute-plugin-info',
        'Настройки расписания',
        '— Настройки API',
        'manage_options',
        'institute-rasp-settings',
        'institute_rasp_settings_page_callback'
    );
}
add_action('admin_menu', 'institute_add_rasp_settings_page', 20);

/**
 * Callback для страницы настроек
 */
function institute_rasp_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>Настройки расписания</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('institute_rasp_settings');
            do_settings_sections('institute-rasp-settings');
            submit_button('Сохранить настройки');
            ?>
        </form>
        
        <hr style="margin: 40px 0;">
        
        <h2>Тест подключения к API</h2>
        <div class="card" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <p>
                <button type="button" class="button button-primary" id="test-api-connection">
                    Проверить подключение к API
                </button>
                <span id="test-result" style="margin-left: 15px; font-weight: 500;"></span>
            </p>
            <div id="test-details" style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px; display: none;">
                <pre id="test-response" style="margin: 0; white-space: pre-wrap; font-family: monospace; font-size: 13px;"></pre>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testButton = document.getElementById('test-api-connection');
            const resultSpan = document.getElementById('test-result');
            const detailsDiv = document.getElementById('test-details');
            const responsePre = document.getElementById('test-response');
            
            if (!testButton) return;
            
            testButton.addEventListener('click', function() {
                testButton.disabled = true;
                testButton.textContent = 'Проверка...';
                resultSpan.innerHTML = '';
                detailsDiv.style.display = 'none';
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=institute_test_api_connection'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resultSpan.innerHTML = '<span style="color: #008000;">✓ Подключение работает</span>';
                        if (data.data && data.data.response) {
                            responsePre.textContent = JSON.stringify(data.data.response, null, 2);
                            detailsDiv.style.display = 'block';
                        }
                    } else {
                        resultSpan.innerHTML = `<span style="color: #dc3232;">✗ Ошибка: ${data.data?.message || 'Неизвестная ошибка'}</span>`;
                        if (data.data?.debug) {
                            responsePre.textContent = data.data.debug;
                            detailsDiv.style.display = 'block';
                        }
                    }
                })
                .catch(error => {
                    resultSpan.innerHTML = `<span style="color: #dc3232;">✗ Ошибка запроса: ${error.message}</span>`;
                    console.error('API test error:', error);
                })
                .finally(() => {
                    testButton.disabled = false;
                    testButton.textContent = 'Проверить подключение к API';
                });
            });
        });
        </script>
    </div>
    <?php
}

/**
 * AJAX обработчик для теста подключения (без строгой проверки nonce для простоты теста)
 */
function institute_test_api_connection() {
    // Для теста подключения не требуем строгой проверки безопасности
    // (т.к. это админ-действие с правами manage_options)
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Недостаточно прав',
            'debug' => 'User does not have manage_options capability'
        ]);
    }
    
    $base_url = institute_get_api_base_url();
    
    // Валидация URL
    if (!filter_var($base_url, FILTER_VALIDATE_URL)) {
        wp_send_json_error([
            'message' => 'Некорректный базовый URL API',
            'debug' => 'Invalid URL format: ' . $base_url
        ]);
    }
    
    $test_url = add_query_arg([
        'sdate' => date('Y-m-d'),
        'edate' => date('Y-m-d', strtotime('+1 day'))
    ], $base_url);
    
    // Логируем для отладки
    error_log('API Test URL: ' . $test_url);
    
    $response = wp_remote_get($test_url, [
        'timeout' => 15,
        'sslverify' => false,
    ]);
    
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        error_log('API Test Error: ' . $error_msg);
        
        wp_send_json_error([
            'message' => 'Ошибка подключения: ' . $error_msg,
            'debug' => 'WP_Error: ' . print_r($response->get_error_codes(), true)
        ]);
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Попытка декодировать JSON
    $json_data = json_decode($body, true);
    $json_error = json_last_error_msg();
    
    error_log('API Response Code: ' . $http_code);
    error_log('API Response Body (first 500 chars): ' . substr($body, 0, 500));
    
    // Проверяем успешный ответ
    if ($http_code >= 200 && $http_code < 300) {
        if ($json_data && isset($json_data['data'])) {
            wp_send_json_success([
                'message' => 'API отвечает корректно (код ' . $http_code . ')',
                'response' => [
                    'status' => 'success',
                    'lessons_count' => !empty($json_data['data']['rasp']) ? count($json_data['data']['rasp']) : 0,
                    'sample' => !empty($json_data['data']['rasp']) ? array_slice($json_data['data']['rasp'], 0, 2) : null
                ]
            ]);
        } else {
            wp_send_json_error([
                'message' => 'API вернул некорректный JSON',
                'debug' => 'HTTP ' . $http_code . ', JSON error: ' . $json_error . ', Body preview: ' . substr($body, 0, 300)
            ]);
        }
    } else {
        wp_send_json_error([
            'message' => 'API вернул ошибку HTTP ' . $http_code,
            'debug' => 'HTTP ' . $http_code . ', Body: ' . substr($body, 0, 500)
        ]);
    }
}
add_action('wp_ajax_institute_test_api_connection', 'institute_test_api_connection');

/**
 * Получить базовый URL API из настроек
 */
function institute_get_api_base_url() {
    $url = get_option('institute_rasp_api_base_url');
    
    // Значение по умолчанию, если не настроено
    if (empty($url)) {
        $url = 'https://stud.oivt-sguwt.ru/api/Rasp';
    }
    
    // Убираем пробелы и завершающий слеш
    $url = trim($url);
    $url = untrailingslashit($url);
    
    return $url;
}

/**
 * Получить время кеширования из настроек
 */
function institute_get_cache_duration() {
    $duration = get_option('institute_rasp_cache_duration');
    
    // Значение по умолчанию 1 час
    if (empty($duration) || !is_numeric($duration) || $duration < 60) {
        $duration = HOUR_IN_SECONDS;
    }
    
    return (int) $duration;
}

/**
 * Получить расписание преподавателя по ID групп
 */
function institute_get_prepod_rasp_by_groups($prepod_name, $group_ids, $days_ahead = 14) {
    if (empty($prepod_name) || empty($group_ids)) {
        return false;
    }
    
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
    $all_lessons = [];
    
    foreach ($group_ids as $group_id) {
        $base_url = institute_get_api_base_url();
        $api_url = add_query_arg([
            'idGroup' => $group_id,
            'sdate' => $start_date,
            'edate' => $end_date
        ], $base_url);
        
        // Проверяем кеш (транзиент)
        $cache_key = 'institute_rasp_group_' . md5($api_url);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            $group_lessons = $cached_data;
        } else {
            $response = wp_remote_get($api_url, [
                'timeout' => 10,
                'sslverify' => false,
            ]);
            
            if (is_wp_error($response)) {
                error_log('Institute Structure Plugin: API Error for group ' . $group_id . ' - ' . $response->get_error_message());
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (empty($data['data']['rasp'])) {
                $group_lessons = [];
            } else {
                $group_lessons = $data['data']['rasp'];
            }
            
            // Кешируем результат
            $cache_duration = institute_get_cache_duration();
            set_transient($cache_key, $group_lessons, $cache_duration);
        }
        
        // Фильтруем занятия только этого преподавателя
        foreach ($group_lessons as $lesson) {
            // Проверяем несколько возможных полей с именем преподавателя
            $lesson_teacher = '';
            if (!empty($lesson['фиоПреподавателя'])) {
                $lesson_teacher = trim($lesson['фиоПреподавателя']);
            } elseif (!empty($lesson['преподаватель'])) {
                $lesson_teacher = trim($lesson['преподаватель']);
            }
            
            // Сравниваем имена
            if (institute_compare_teacher_names($lesson_teacher, $prepod_name)) {
                // Добавляем информацию о группе
                $lesson['source_group'] = $group_id;
                $all_lessons[] = $lesson;
            }
        }
    }
    
    // Удаляем дубликаты
    $unique_lessons = [];
    $seen_keys = [];
    foreach ($all_lessons as $lesson) {
        $key = $lesson['дата'] . '_' . $lesson['начало'] . '_' . $lesson['дисциплина'];
        if (!in_array($key, $seen_keys)) {
            $seen_keys[] = $key;
            $unique_lessons[] = $lesson;
        }
    }
    
    return $unique_lessons;
}

/**
 * Получить расписание преподавателя (основная функция)
 */
function institute_get_prepod_rasp($prepod_id, $days_ahead = 14) {
    $teacher_id = get_post_meta($prepod_id, '_prepodavatel_api_teacher_id', true);
    $prepod_name = get_the_title($prepod_id);
    
    // Сначала пробуем через idTeacher (новый метод)
    if (!empty($teacher_id)) {
        $rasp = institute_get_prepod_rasp_by_teacher_id($teacher_id, $days_ahead);
        if (!empty($rasp)) {
            return $rasp;
        }
    }
    
    // Резервный вариант: через группы
    $groups_str = get_post_meta($prepod_id, '_prepodavatel_groups_for_rasp', true);
    if (empty($groups_str)) {
        return false;
    }
    
    $group_ids = array_filter(array_map('trim', explode(',', $groups_str)));
    if (empty($group_ids)) {
        return false;
    }
    
    return institute_get_prepod_rasp_by_groups($prepod_name, $group_ids, $days_ahead);
}

/**
 * Получить расписание по ID преподавателя в API
 */
function institute_get_prepod_rasp_by_teacher_id($teacher_id, $days_ahead = 14) {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
    
    $base_url = institute_get_api_base_url();
    $api_url = add_query_arg([
        'idTeacher' => $teacher_id,
        'sdate' => $start_date,
        'edate' => $end_date
    ], $base_url);
    
    $cache_key = 'institute_rasp_teacher_' . md5($api_url);
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $response = wp_remote_get($api_url, [
        'timeout' => 10,
        'sslverify' => false,
    ]);
    
    if (is_wp_error($response)) {
        error_log('API Error (teacher): ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    $lessons = !empty($data['data']['rasp']) ? $data['data']['rasp'] : [];
    
    $cache_duration = institute_get_cache_duration();
    set_transient($cache_key, $lessons, $cache_duration);
    
    return $lessons;
}

/**
 * Очистить кеш расписания
 */
function institute_clear_rasp_cache($prepod_id = null) {
    global $wpdb;
    
    if ($prepod_id) {
        // Очистить кеш конкретного преподавателя
        $teacher_id = get_post_meta($prepod_id, '_prepodavatel_api_teacher_id', true);
        if ($teacher_id) {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+14 days"));
            
            $base_url = institute_get_api_base_url();
            $api_url = add_query_arg([
                'idTeacher' => $teacher_id,
                'sdate' => $start_date,
                'edate' => $end_date
            ], $base_url);
            
            $cache_key = 'institute_rasp_teacher_' . md5($api_url);
            delete_transient($cache_key);
        }
        
        // Очистить кеш по группам
        $groups_str = get_post_meta($prepod_id, '_prepodavatel_groups_for_rasp', true);
        if ($groups_str) {
            $base_url = institute_get_api_base_url();
            $group_ids = array_filter(array_map('trim', explode(',', $groups_str)));
            
            foreach ($group_ids as $group_id) {
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+14 days"));
                
                $api_url = add_query_arg([
                    'idGroup' => $group_id,
                    'sdate' => $start_date,
                    'edate' => $end_date
                ], $base_url);
                
                $cache_key = 'institute_rasp_group_' . md5($api_url);
                delete_transient($cache_key);
            }
        }
    } else {
        // Очистить весь кеш расписания
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_institute_rasp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_institute_rasp_%'");
    }
}

/**
 * Добавить кнопку очистки кеша в админ-панель
 */
function institute_add_cache_clear_button() {
    global $pagenow;
    
    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'institute-rasp-settings') {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrapForm = document.querySelector('.wrap form');
            if (!wrapForm) return;
            
            const cacheSection = document.createElement('div');
            cacheSection.innerHTML = `
                <hr style="margin: 30px 0;">
                <h2>Управление кешем</h2>
                <div class="card" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <p>
                        <button type="button" class="button button-secondary" id="clear-rasp-cache">
                            Очистить весь кеш расписания
                        </button>
                        <span id="clear-cache-result" style="margin-left: 15px; font-weight: 500;"></span>
                    </p>
                    <p class="description" style="margin-top: 10px;">
                        Очистка кеша заставит систему заново запросить данные из API при следующем обращении
                    </p>
                </div>
            `;
            
            wrapForm.parentNode.insertBefore(cacheSection, wrapForm.nextSibling);
            
            document.getElementById('clear-rasp-cache').addEventListener('click', function() {
                const button = this;
                const result = document.getElementById('clear-cache-result');
                
                button.disabled = true;
                button.textContent = 'Очистка...';
                result.innerHTML = '';
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=institute_clear_all_cache'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = '<span style="color: #008000;">✓ Кеш очищен</span>';
                    } else {
                        result.innerHTML = `<span style="color: #dc3232;">✗ Ошибка: ${data.data?.message || 'Неизвестная ошибка'}</span>`;
                    }
                })
                .catch(error => {
                    result.innerHTML = `<span style="color: #dc3232;">✗ Ошибка запроса: ${error.message}</span>`;
                    console.error('Cache clear error:', error);
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Очистить весь кеш расписания';
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'institute_add_cache_clear_button');

/**
 * AJAX обработчик для очистки кеша
 */
function institute_clear_all_cache_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Недостаточно прав']);
    }
    
    institute_clear_rasp_cache();
    wp_send_json_success(['message' => 'Кеш успешно очищен']);
}
add_action('wp_ajax_institute_clear_all_cache', 'institute_clear_all_cache_ajax');

/**
 * Функция сравнения имен преподавателей (нужна для фильтрации)
 */
if (!function_exists('institute_compare_teacher_names')) {
    function institute_compare_teacher_names($teacher1, $teacher2) {
        // Приводим к нижнему регистру и убираем лишние пробелы
        $t1 = mb_strtolower(trim($teacher1), 'UTF-8');
        $t2 = mb_strtolower(trim($teacher2), 'UTF-8');
        
        // Проверяем точное совпадение
        if ($t1 === $t2) {
            return true;
        }
        
        // Проверяем частичное совпадение (на случай разных форматов ФИО)
        if (strpos($t1, $t2) !== false || strpos($t2, $t1) !== false) {
            return true;
        }
        
        return false;
    }
}