<?php
if (!defined('ABSPATH')) exit;

/**
 * История изменений разделов структуры института
 */

// ── Таблица БД ───────────────────────────────────────────────────────────────

register_activation_hook(INSTITUTE_PLUGIN_DIR . 'institute-structure.php', 'institute_create_history_table');

function institute_create_history_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'institute_history';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id     BIGINT UNSIGNED NOT NULL,
        post_type   VARCHAR(50)     NOT NULL,
        post_title  VARCHAR(255)    NOT NULL DEFAULT '',
        user_id     BIGINT UNSIGNED NOT NULL,
        user_name   VARCHAR(255)    NOT NULL DEFAULT '',
        action      VARCHAR(50)     NOT NULL DEFAULT 'update',
        changed_fields TEXT,
        snapshot    LONGTEXT,
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Создаём таблицу при загрузке если не существует
add_action('init', 'institute_maybe_create_history_table');
function institute_maybe_create_history_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'institute_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        institute_create_history_table();
    }
}

// ── Запись истории ────────────────────────────────────────────────────────────

/**
 * Сохранить запись в историю изменений
 */
function institute_log_change($post_id, $action = 'update', $changed_fields = [], $snapshot = []) {
    global $wpdb;

    $user    = wp_get_current_user();
    $post    = get_post($post_id);
    if (!$post) return;

    $wpdb->insert(
        $wpdb->prefix . 'institute_history',
        [
            'post_id'        => $post_id,
            'post_type'      => $post->post_type,
            'post_title'     => $post->post_title,
            'user_id'        => $user->ID,
            'user_name'      => $user->display_name ?: $user->user_login,
            'action'         => $action,
            'changed_fields' => !empty($changed_fields) ? json_encode($changed_fields, JSON_UNESCAPED_UNICODE) : null,
            'snapshot'       => !empty($snapshot) ? json_encode($snapshot, JSON_UNESCAPED_UNICODE) : null,
            'created_at'     => current_time('mysql'),
        ],
        ['%d','%s','%s','%d','%s','%s','%s','%s','%s']
    );
}

// ── Перехват сохранения постов ────────────────────────────────────────────────

add_action('save_post', 'institute_track_changes', 20, 2);
function institute_track_changes($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];
    if (!in_array($post->post_type, $cpt_types)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Определяем префикс метаполей
    $prefixes = [
        'fakultet'          => '_fakultet_',
        'kafedra'           => '_kafedra_',
        'srednee_obrazovanie' => '_srednee_',
        'specialnost'       => '_specialnost_',
        'prepodavatel'      => '_prepodavatel_',
    ];
    $prefix = $prefixes[$post->post_type] ?? '_';

    // Лейблы полей для красивого отображения
    $field_labels = [
        'zaveduyushchiy' => 'Заведующий / Декан',
        'email'          => 'Email',
        'phone'          => 'Телефон',
        'auditoria'      => 'Аудитория',
        'site'           => 'Сайт',
        'fakultet'       => 'Факультет',
        'opisanie'       => 'Описание',
        'nmr'            => 'НМР',
        'nrs'            => 'НРС',
        'uvr'            => 'УВР',
        'dolzhnost'      => 'Должность',
        'stepen'         => 'Учёная степень',
        'pripisann_k'    => 'Привязка к разделу',
        'zav_photo_id'   => 'Фото',
        'photo_id'       => 'Фото',
        'kod'            => 'Код специальности',
        'uroven'         => 'Уровень образования',
    ];

    // Сравниваем текущие мета с тем что пришло в $_POST
    $changed = [];
    $snapshot = ['title' => $post->post_title];

    $watched_fields = array_keys($field_labels);
    foreach ($watched_fields as $field) {
        $meta_key   = $prefix . $field;
        $old_value  = get_post_meta($post_id, $meta_key, true);
        $post_key   = str_replace(['-', '.'], '_', ltrim($post->post_type, '_')) . '_' . $field;

        // Пробуем получить новое значение из $_POST
        $new_value = $_POST[$post_key] ?? $_POST[ltrim($prefix,'_') . $field] ?? null;
        if ($new_value === null) continue;

        $old_str = is_array($old_value) ? json_encode($old_value) : (string)$old_value;
        $new_str = is_array($new_value) ? json_encode($new_value) : (string)$new_value;

        if ($old_str !== $new_str) {
            $label = $field_labels[$field] ?? $field;

            if ($field === 'zav_photo_id' || $field === 'photo_id') {
                $changed[$label] = $new_value ? 'Фото обновлено' : 'Фото удалено';
            } elseif ($field === 'opisanie' || $field === 'nmr' || $field === 'nrs' || $field === 'uvr') {
                $changed[$label] = 'Текст изменён';
            } elseif ($field === 'fakultet' && $new_value) {
                $changed[$label] = get_the_title((int)$new_value) ?: $new_value;
            } elseif ($field === 'pripisann_k') {
                $parts = explode('_', $new_value, 2);
                $sid   = (int)($parts[1] ?? 0);
                $changed[$label] = $sid ? get_the_title($sid) : $new_value;
            } else {
                $changed[$label] = mb_strlen($new_str) > 80
                    ? mb_substr($new_str, 0, 80) . '…'
                    : $new_str;
            }
        }
        $snapshot[$field] = $old_str;
    }

    // Определяем тип действия
    $action = 'update';
    if (isset($_POST['original_post_status']) && $_POST['original_post_status'] === 'auto-draft') {
        $action = 'create';
    }

    // Логируем только если есть реальные изменения или это создание
    if (!empty($changed) || $action === 'create') {
        institute_log_change($post_id, $action, $changed, $snapshot);
    }
}

// ── Метабокс истории на странице редактирования ───────────────────────────────

add_action('add_meta_boxes', 'institute_add_history_metabox');
function institute_add_history_metabox() {
    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];
    foreach ($cpt_types as $cpt) {
        add_meta_box(
            'institute_change_history',
            '📋 История изменений',
            'institute_render_history_metabox',
            $cpt,
            'normal',
            'low'
        );
    }
}

function institute_render_history_metabox($post) {
    global $wpdb;
    $table = $wpdb->prefix . 'institute_history';

    $records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE post_id = %d ORDER BY created_at DESC LIMIT 20",
        $post->ID
    ));

    $action_labels = [
        'create' => ['label' => 'Создан',   'color' => '#059669', 'bg' => '#f0fdf4'],
        'update' => ['label' => 'Изменён',  'color' => '#2563eb', 'bg' => '#eff6ff'],
        'delete' => ['label' => 'Удалён',   'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];

    if (empty($records)): ?>
    <div style="text-align:center;padding:24px;color:#9ca3af;">
        <span class="dashicons dashicons-clock" style="font-size:32px;width:32px;height:32px;display:block;margin:0 auto 8px;"></span>
        <p style="margin:0;font-size:13px;">История изменений пуста. Она начнёт заполняться при следующем сохранении.</p>
    </div>
    <?php else: ?>
    <div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
        <?php foreach ($records as $i => $rec):
            $a      = $action_labels[$rec->action] ?? $action_labels['update'];
            $fields = $rec->changed_fields ? json_decode($rec->changed_fields, true) : [];
            $dt     = wp_date('d.m.Y в H:i', strtotime($rec->created_at));
            $user   = get_userdata($rec->user_id);
            $avatar = $user ? get_avatar($rec->user_id, 28) : '';
        ?>
        <div style="display:flex;gap:12px;padding:12px 0;<?php echo $i > 0 ? 'border-top:1px solid #f3f4f6;' : ''; ?>">

            <!-- Аватар -->
            <div style="flex-shrink:0;width:28px;">
                <?php if ($avatar): ?>
                <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;"><?php echo $avatar; ?></div>
                <?php else: ?>
                <div style="width:28px;height:28px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;">
                    <span class="dashicons dashicons-admin-users" style="font-size:16px;width:16px;height:16px;color:#9ca3af;"></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Контент -->
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <strong style="font-size:13px;color:#111827;"><?php echo esc_html($rec->user_name); ?></strong>
                    <span style="background:<?php echo $a['bg']; ?>;color:<?php echo $a['color']; ?>;padding:1px 7px;border-radius:4px;font-size:11px;font-weight:700;">
                        <?php echo $a['label']; ?>
                    </span>
                    <span style="font-size:11px;color:#9ca3af;margin-left:auto;"><?php echo $dt; ?></span>
                </div>

                <?php if (!empty($fields)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
                    <?php foreach ($fields as $field_name => $field_val): ?>
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:5px;padding:3px 8px;font-size:11px;color:#374151;max-width:100%;">
                        <span style="color:#6b7280;font-weight:600;"><?php echo esc_html($field_name); ?>:</span>
                        <span style="color:#111827;"><?php echo esc_html($field_val ?: '—'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Без детальной информации об изменениях</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d", $post->ID
        ));
        if ($total > 20): ?>
        <div style="text-align:center;padding-top:10px;border-top:1px solid #f3f4f6;">
            <a href="<?php echo admin_url('admin.php?page=institute-history&post_id=' . $post->ID); ?>"
               style="font-size:12px;color:#2563eb;">
                Показать все <?php echo $total; ?> записей →
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif;
}

// ── Страница полной истории ───────────────────────────────────────────────────

add_action('admin_menu', 'institute_add_history_page', 20);
function institute_add_history_page() {
    add_submenu_page(
        'institute-plugin-info',
        'История изменений',
        '— История',
        'manage_options',
        'institute-history',
        'institute_history_page_callback'
    );
}

function institute_history_page_callback() {
    global $wpdb;
    if (!current_user_can('administrator') && !current_user_can('manage_institute_structure')) {
        wp_die('Доступ запрещён');
    }

    $table    = $wpdb->prefix . 'institute_history';
    $post_id  = absint($_GET['post_id'] ?? 0);
    $per_page = 30;
    $page     = max(1, absint($_GET['paged'] ?? 1));
    $offset   = ($page - 1) * $per_page;

    $where = $post_id ? $wpdb->prepare('WHERE post_id = %d', $post_id) : '';

    $total   = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table $where");
    $records = $wpdb->get_results(
        "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
    );

    $pages = ceil($total / $per_page);

    $action_labels = [
        'create' => ['label' => 'Создан',  'color' => '#059669', 'bg' => '#f0fdf4'],
        'update' => ['label' => 'Изменён', 'color' => '#2563eb', 'bg' => '#eff6ff'],
        'delete' => ['label' => 'Удалён',  'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];

    $cpt_labels = [
        'fakultet'            => 'Факультет',
        'kafedra'             => 'Кафедра',
        'srednee_obrazovanie' => 'Отделение СПО',
        'specialnost'         => 'Специальность',
        'prepodavatel'        => 'Преподаватель',
    ];
    ?>
    <div class="wrap" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
            <h1 style="margin:0;font-size:22px;font-weight:700;color:#111827;">История изменений</h1>
            <?php if ($post_id): ?>
            <a href="<?php echo admin_url('admin.php?page=institute-history'); ?>"
               style="font-size:12px;color:#6b7280;text-decoration:none;border:1px solid #e5e7eb;padding:4px 10px;border-radius:6px;">
                ← Все записи
            </a>
            <?php endif; ?>
            <span style="background:#e5e7eb;color:#374151;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;margin-left:auto;">
                Всего: <?php echo $total; ?>
            </span>
        </div>

        <?php if (empty($records)): ?>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:48px;text-align:center;color:#9ca3af;">
            <span class="dashicons dashicons-clock" style="font-size:48px;width:48px;height:48px;display:block;margin:0 auto 12px;"></span>
            <p style="font-size:15px;margin:0;">История изменений пуста</p>
        </div>
        <?php else: ?>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);">
            <?php foreach ($records as $i => $rec):
                $a      = $action_labels[$rec->action] ?? $action_labels['update'];
                $fields = $rec->changed_fields ? json_decode($rec->changed_fields, true) : [];
                $dt     = wp_date('d.m.Y в H:i', strtotime($rec->created_at));
                $user   = get_userdata($rec->user_id);
                $avatar = $user ? get_avatar($rec->user_id, 36) : '';
                $cpt_label = $cpt_labels[$rec->post_type] ?? $rec->post_type;
                $edit_link = get_edit_post_link($rec->post_id);
            ?>
            <div style="display:grid;grid-template-columns:36px 1fr auto;gap:14px;padding:16px 20px;align-items:start;<?php echo $i > 0 ? 'border-top:1px solid #f3f4f6;' : ''; ?>">

                <!-- Аватар -->
                <div>
                    <?php if ($avatar): ?>
                    <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;"><?php echo $avatar; ?></div>
                    <?php else: ?>
                    <div style="width:36px;height:36px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;">
                        <span class="dashicons dashicons-admin-users" style="font-size:20px;width:20px;height:20px;color:#9ca3af;"></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Основное -->
                <div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                        <strong style="font-size:14px;color:#111827;"><?php echo esc_html($rec->user_name); ?></strong>
                        <span style="font-size:12px;color:#6b7280;">•</span>
                        <span style="background:<?php echo $a['bg']; ?>;color:<?php echo $a['color']; ?>;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;"><?php echo $a['label']; ?></span>
                        <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:4px;font-size:11px;"><?php echo $cpt_label; ?></span>
                    </div>

                    <div style="margin-bottom:6px;">
                        <?php if ($edit_link): ?>
                        <a href="<?php echo $edit_link; ?>" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">
                            <?php echo esc_html($rec->post_title); ?>
                        </a>
                        <?php else: ?>
                        <span style="font-size:13px;font-weight:600;color:#374151;"><?php echo esc_html($rec->post_title); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($fields)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        <?php foreach ($fields as $fname => $fval): ?>
                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:5px;padding:2px 8px;font-size:11px;">
                            <span style="color:#6b7280;font-weight:600;"><?php echo esc_html($fname); ?>:</span>
                            <span style="color:#111827;"><?php echo esc_html($fval ?: '—'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Время -->
                <div style="font-size:12px;color:#9ca3af;white-space:nowrap;text-align:right;">
                    <?php echo $dt; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;margin-top:20px;">
            <?php for ($p = 1; $p <= $pages; $p++):
                $url = add_query_arg(['paged' => $p, 'post_id' => $post_id ?: null],
                    admin_url('admin.php?page=institute-history'));
                $active = $p === $page;
            ?>
            <a href="<?php echo $url; ?>"
               style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;
                      <?php echo $active
                          ? 'background:#111827;color:#fff;'
                          : 'background:#fff;color:#374151;border:1px solid #e5e7eb;'; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif;
        endif; ?>
    </div>
    <?php
}
