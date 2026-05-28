<?php
if (!defined('ABSPATH')) exit;

/**
 * Добавление страницы синхронизации в меню
 */
add_action('admin_menu', 'institute_add_sync_menu', 20);
function institute_add_sync_menu() {
    add_submenu_page(
        'institute-plugin-info',
        'Синхронизация с API',
        '— Синхронизация',
        'manage_options',
        'institute-teacher-sync',
        'institute_sync_admin_page'
    );
}

/**
 * Рендеринг страницы синхронизации
 */
function institute_sync_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав');
    }
    
    $sync = new Institute_Teacher_Sync();
    $results = null;
    $created = [];
    $updated = 0;
    
    // Обработка формы сканирования
    if (isset($_POST['scan_teachers'])) {
        check_admin_referer('institute_sync_nonce');
        
        $start = intval($_POST['start_id'] ?? 1);
        $end = intval($_POST['end_id'] ?? 100);
        
        $api_teachers = $sync->scan_teachers_range($start, $end);
        $results = $sync->find_unmatched_teachers($api_teachers);
        
        if (!empty($_POST['create_missing']) && !empty($results['unmatched'])) {
            $created = $sync->create_missing_teachers($results['unmatched']);
        }
    }
    
    // Обработка формы обновления
    if (isset($_POST['update_existing'])) {
        check_admin_referer('institute_sync_nonce');
        $updated = $sync->update_existing_teachers();
    }
    ?>
    <div class="wrap">
        <h1>Синхронизация преподавателей с системой расписания</h1>
        
        <div class="notice notice-info">
            <p><strong>Важно:</strong> API использует числовой идентификатор преподавателя (<code>idTeacher</code>). 
            Сканирование помогает найти соответствие между преподавателями в системе расписания и вашими записями.</p>
        </div>
        
        <h2>1. Сканирование преподавателей</h2>
        <p>Сканирует диапазон преподавателей в системе расписания и сопоставляет их с существующими записями.</p>
        
        <form method="post" id="scan-form">
            <?php wp_nonce_field('institute_sync_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="start_id">Начальный ID преподавателя</label></th>
                    <td>
                        <input type="number" name="start_id" id="start_id" value="1" min="1" class="small-text">
                        <p class="description">Начальный идентификатор в диапазоне (например, 1)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="end_id">Конечный ID преподавателя</label></th>
                    <td>
                        <input type="number" name="end_id" id="end_id" value="100" min="1" class="small-text">
                        <p class="description">Конечный идентификатор в диапазоне (например, 100)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Создать недостающих</th>
                    <td>
                        <label>
                            <input type="checkbox" name="create_missing" value="1" checked> 
                            Автоматически создать записи для несопоставленных преподавателей (как черновики)
                        </label>
                        <p class="description">Новые преподаватели будут созданы в статусе "Черновик" для ручной проверки.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Сканировать и синхронизировать', 'primary', 'scan_teachers'); ?>
        </form>
        
        <?php if ($results): ?>
            <hr>
            <h3>Результаты сканирования</h3>
            <div class="institute-sync-results">
                <table class="widefat fixed">
                    <tbody>
                        <tr>
                            <th scope="row">Всего найдено в системе расписания:</th>
                            <td><strong><?php echo esc_html($results['total_api']); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Сопоставлено с существующими:</th>
                            <td><strong><?php echo esc_html($results['matched_count']); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Несопоставленных:</th>
                            <td><strong><?php echo esc_html(count($results['unmatched'])); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Всего преподавателей в системе:</th>
                            <td><strong><?php echo esc_html($results['total_wp']); ?></strong></td>
                        </tr>
                        <?php if (!empty($created)): ?>
                        <tr>
                            <th scope="row">Создано новых записей:</th>
                            <td><strong><?php echo count($created); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($results['unmatched'])): ?>
                <h4>Несопоставленные преподаватели из системы расписания:</h4>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>ID в системе</th>
                            <th>Имя преподавателя</th>
                            <th>Пример занятия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['unmatched'] as $teacher): ?>
                            <tr>
                                <td><code><?php echo esc_html($teacher['id']); ?></code></td>
                                <td><strong><?php echo esc_html($teacher['name']); ?></strong></td>
                                <td>
                                    <?php 
                                    $lesson = $teacher['lessons'][0] ?? [];
                                    if (!empty($lesson['дисциплина'])):
                                        echo '<span class="discipline">' . esc_html($lesson['дисциплина']) . '</span>';
                                        if (!empty($lesson['аудитория'])):
                                            echo ' <span class="room">(ауд. ' . esc_html($lesson['аудитория']) . ')</span>';
                                        endif;
                                    else:
                                        echo '—';
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if (!empty($created)): ?>
                <h4>Созданные записи:</h4>
                <ul>
                    <?php foreach ($created as $item): ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($item['id']); ?>">
                                <?php echo esc_html($item['name']); ?>
                            </a> 
                            (ID записи: <?php echo $item['id']; ?>, API ID: <?php echo $item['api_id']; ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="notice notice-success inline">
                    <strong>Важно:</strong> Проверьте созданные черновики и заполните недостающие данные (привязка к кафедре, должность, фото и т.д.)
                </p>
            <?php endif; ?>
        <?php endif; ?>
        
        <hr>
        
        <h2>2. Обновление существующих преподавателей</h2>
        <p>Очищает кеш расписания для всех преподавателей с указанным ID в системе расписания.</p>
        
        <form method="post" id="update-form">
            <?php wp_nonce_field('institute_sync_nonce'); ?>
            <?php submit_button('Обновить расписание существующих преподавателей', 'secondary', 'update_existing'); ?>
        </form>
        
        <?php if ($updated > 0): ?>
            <div class="notice notice-success">
                <p>Кеш расписания обновлён для <strong><?php echo $updated; ?></strong> преподавателей.</p>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <h2>3. Инструкция</h2>
        <ol>
            <li><strong>Сканирование:</strong> Запустите сканирование с разумным диапазоном (например, 1-100). Процесс может занять несколько минут.</li>
            <li><strong>Проверка результатов:</strong> Система автоматически сопоставит преподавателей по имени и обновит поле <code>ID преподавателя в API</code>.</li>
            <li><strong>Создание недостающих:</strong> Новые преподаватели создаются как черновики. Обязательно проверьте и дополните их данными.</li>
            <li><strong>Ручная настройка:</strong> Для преподавателей, которых нет в системе расписания, можно указать группы вручную через поле <code>ID групп для расписания</code>.</li>
        </ol>
        
        <div class="notice notice-warning inline">
            <p><strong>Внимание:</strong> Сканирование выполняет много запросов к API. Не используйте слишком большой диапазон за один раз.</p>
        </div>
    </div>
    
    <style>
    .institute-sync-results {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin: 20px 0;
    }
    .institute-sync-results th {
        width: 300px;
        font-weight: 600;
    }
    .discipline {
        font-weight: 600;
        color: #2c3e50;
    }
    .room {
        color: #666;
        font-size: 0.9em;
    }
    </style>
    <?php
}