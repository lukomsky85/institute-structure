<?php
/**
 * Страница информации о плагине "Структура института"
 */

if (!defined('ABSPATH')) exit;

/**
 * Добавить ссылку на страницу информации в списке плагинов
 */
function institute_add_plugin_info_link($links, $file) {
    // Если это наш плагин
    if (strpos($file, 'institute-structure') !== false) {
        $links[] = '<a href="' . admin_url('admin.php?page=institute-plugin-info') . '" style="font-weight: 600;">ℹ️ О плагине</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=institute-rasp-settings') . '">⚙️ Настройки</a>';
    }
    return $links;
}
add_filter('plugin_action_links', 'institute_add_plugin_info_link', 10, 2);

/**
 * Добавить страницу информации в меню админки
 */
function institute_add_plugin_info_page() {
    add_menu_page(
        'Структура института',
        'Структура института',
        'manage_options',
        'institute-plugin-info',
        'institute_plugin_info_page_callback',
        'dashicons-building',
        30
    );
}
add_action('admin_menu', 'institute_add_plugin_info_page', 9);

/**
 * Callback для страницы информации о плагине
 */
function institute_plugin_info_page_callback() {
    global $wpdb;
    $stats = institute_get_plugin_stats();

    // Последние изменения
    $history_table = $wpdb->prefix . 'institute_history';
    $has_history   = $wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table;
    $recent        = $has_history ? $wpdb->get_results(
        "SELECT * FROM $history_table ORDER BY created_at DESC LIMIT 5"
    ) : [];

    $nav_items = [
        ['url' => 'edit.php?post_type=fakultet',          'icon' => 'dashicons-welcome-learn-more', 'color' => '#2563eb', 'label' => 'Факультеты',   'desc' => 'Управление факультетами'],
        ['url' => 'edit.php?post_type=kafedra',            'icon' => 'dashicons-analytics',          'color' => '#059669', 'label' => 'Кафедры',       'desc' => 'Управление кафедрами'],
        ['url' => 'edit.php?post_type=srednee_obrazovanie','icon' => 'dashicons-welcome-learn-more', 'color' => '#7c3aed', 'label' => 'СПО',           'desc' => 'Среднее проф. образование'],
        ['url' => 'edit.php?post_type=specialnost',        'icon' => 'dashicons-book',               'color' => '#dc2626', 'label' => 'Специальности', 'desc' => 'Специальности и направления'],
        ['url' => 'edit.php?post_type=prepodavatel',       'icon' => 'dashicons-groups',             'color' => '#d97706', 'label' => 'Преподаватели', 'desc' => 'Преподавательский состав'],
        ['url' => 'admin.php?page=institute-teacher-sync', 'icon' => 'dashicons-update',             'color' => '#0891b2', 'label' => 'Синхронизация', 'desc' => 'Синхронизация из API'],
        ['url' => 'admin.php?page=institute-history',      'icon' => 'dashicons-backup',             'color' => '#059669', 'label' => 'История',        'desc' => 'История изменений'],
        ['url' => 'admin.php?page=institute-access',       'icon' => 'dashicons-lock',               'color' => '#7c3aed', 'label' => 'Доступ',         'desc' => 'Управление доступом'],
        ['url' => 'admin.php?page=institute-rasp-settings','icon' => 'dashicons-admin-settings',     'color' => '#374151', 'label' => 'Настройки API',  'desc' => 'Базовый URL и кеш'],
    ];

    $stat_items = [
        ['key' => 'fakultets',    'label' => 'Факультетов',    'color' => '#2563eb', 'icon' => 'dashicons-welcome-learn-more', 'url' => 'edit.php?post_type=fakultet'],
        ['key' => 'kafedras',     'label' => 'Кафедр',         'color' => '#059669', 'icon' => 'dashicons-analytics',          'url' => 'edit.php?post_type=kafedra'],
        ['key' => 'srednee',      'label' => 'Отделений СПО',  'color' => '#7c3aed', 'icon' => 'dashicons-welcome-learn-more', 'url' => 'edit.php?post_type=srednee_obrazovanie'],
        ['key' => 'specialnosti', 'label' => 'Специальностей', 'color' => '#dc2626', 'icon' => 'dashicons-book',               'url' => 'edit.php?post_type=specialnost'],
        ['key' => 'prepodavateli','label' => 'Преподавателей', 'color' => '#d97706', 'icon' => 'dashicons-groups',             'url' => 'edit.php?post_type=prepodavatel'],
    ];

    $action_labels = [
        'create' => ['label' => 'Создан',  'color' => '#059669', 'bg' => '#f0fdf4'],
        'update' => ['label' => 'Изменён', 'color' => '#2563eb', 'bg' => '#eff6ff'],
        'delete' => ['label' => 'Удалён',  'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];
    ?>
    <div class="wrap is-info-page">

        <!-- Hero -->
        <div class="is-hero">
            <div class="is-hero-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="is-hero-text">
                <h1>Структура института</h1>
                <div class="is-hero-meta">
                    <span class="is-badge">v<?php echo esc_html(INSTITUTE_PLUGIN_VERSION); ?></span>
                    <span class="is-sep">·</span>
                    <a href="https://github.com/lukomsky85/" target="_blank" class="is-author-link">
                        <svg height="14" width="14" viewBox="0 0 16 16" fill="currentColor" style="vertical-align:middle;margin-right:4px;"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                        M. Lukomskiy
                    </a>
                </div>
            </div>
            <div class="is-hero-actions">
                <a href="<?php echo admin_url('admin.php?page=institute-history'); ?>" class="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;margin-top:-1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> История
                </a>
                <a href="<?php echo admin_url('admin.php?page=institute-rasp-settings'); ?>" class="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;margin-top:-1px;"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M9.17 9.17a4 4 0 0 0 0 5.66M14.83 9.17a4 4 0 0 1 0 5.66"/></svg> Настройки
                </a>
                <a href="<?php echo admin_url('admin.php?page=institute-teacher-sync'); ?>" class="button button-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;margin-top:-1px;"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> Синхронизация
                </a>
            </div>
        </div>

        <!-- Статистика -->
        <div class="is-stats-grid">
            <?php foreach ($stat_items as $s): ?>
            <a href="<?php echo admin_url($s['url']); ?>" class="is-stat-card">
                <div class="is-stat-icon" style="background:<?php echo $s['color']; ?>15;">
                    <span class="dashicons <?php echo $s['icon']; ?>" style="color:<?php echo $s['color']; ?>;font-size:20px;width:20px;height:20px;"></span>
                </div>
                <div class="is-stat-number" style="color:<?php echo $s['color']; ?>"><?php echo $stats[$s['key']]; ?></div>
                <div class="is-stat-label"><?php echo esc_html($s['label']); ?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Основной контент: возможности + последние изменения -->
        <div class="is-main-grid">

            <!-- Левая колонка -->
            <div>
                <div class="is-card">
                    <h3><span class="dashicons dashicons-editor-code"></span> Шорткоды</h3>
                    <div class="is-shortcode-list">
                        <div class="is-shortcode-item"><code>[fakultety]</code><span>Список факультетов</span></div>
                        <div class="is-shortcode-item"><code>[kafedry]</code><span>Список кафедр</span></div>
                        <div class="is-shortcode-item"><code>[srednee_obrazovanie]</code><span>Отделения СПО</span></div>
                        <div class="is-shortcode-item"><code>[specialnosti]</code><span>Специальности</span></div>
                        <div class="is-shortcode-item"><code>[prepodavateli]</code><span>Преподаватели</span></div>
                        <div class="is-shortcode-item"><code>[prepod_rasp id="123"]</code><span>Расписание</span></div>
                    </div>
                </div>

                <div class="is-card">
                    <h3><span class="dashicons dashicons-yes-alt"></span> Возможности</h3>
                    <ul class="is-feature-list">
                        <li>Управление факультетами и кафедрами</li>
                        <li>Среднее профессиональное образование</li>
                        <li>Специальности и направления подготовки</li>
                        <li>Преподавательский состав с фото</li>
                        <li>Расписание занятий преподавателей</li>
                        <li>Синхронизация с внешним API</li>
                        <li>Управление доступом по ролям</li>
                        <li>История изменений разделов</li>
                    </ul>
                </div>
            </div>

            <!-- Правая колонка: последние изменения -->
            <div class="is-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="margin:0;"><span class="dashicons dashicons-backup"></span> Последние изменения</h3>
                    <a href="<?php echo admin_url('admin.php?page=institute-history'); ?>"
                       style="font-size:12px;color:#2563eb;text-decoration:none;font-weight:600;">Все →</a>
                </div>

                <?php if (!empty($recent)): ?>
                <div style="display:flex;flex-direction:column;gap:0;">
                    <?php foreach ($recent as $i => $rec):
                        $a    = $action_labels[$rec->action] ?? $action_labels['update'];
                        $dt   = wp_date('d.m H:i', strtotime($rec->created_at));
                        $link = get_edit_post_link($rec->post_id);
                    ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;<?php echo $i > 0 ? 'border-top:1px solid #f3f4f6;' : ''; ?>">
                        <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;">
                            <?php echo get_avatar($rec->user_id, 32); ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php if ($link): ?>
                                <a href="<?php echo $link; ?>" style="color:#111827;text-decoration:none;"><?php echo esc_html($rec->post_title); ?></a>
                                <?php else: echo esc_html($rec->post_title); endif; ?>
                            </div>
                            <div style="font-size:11px;color:#9ca3af;">
                                <?php echo esc_html($rec->user_name); ?>
                                <span style="background:<?php echo $a['bg']; ?>;color:<?php echo $a['color']; ?>;padding:0 5px;border-radius:3px;margin-left:4px;font-weight:600;"><?php echo $a['label']; ?></span>
                            </div>
                        </div>
                        <div style="font-size:11px;color:#9ca3af;flex-shrink:0;"><?php echo $dt; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:24px;color:#9ca3af;">
                    <span class="dashicons dashicons-clock" style="font-size:28px;width:28px;height:28px;display:block;margin:0 auto 8px;"></span>
                    <p style="margin:0;font-size:12px;">История пока пуста</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Быстрый доступ -->
        <div class="is-card">
            <h3><span class="dashicons dashicons-arrow-right-alt"></span> Быстрый доступ</h3>
            <div class="is-nav-grid">
                <?php foreach ($nav_items as $item): ?>
                <a href="<?php echo admin_url($item['url']); ?>" class="is-nav-item" style="--accent:<?php echo $item['color']; ?>">
                    <span class="dashicons <?php echo $item['icon']; ?>" style="color:<?php echo $item['color']; ?>"></span>
                    <div>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <span><?php echo esc_html($item['desc']); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="is-footer">
            <p>
                Разработано для Омского института водного транспорта
                &nbsp;·&nbsp;
                © <?php echo date('Y'); ?>
                &nbsp;·&nbsp;
                <a href="https://github.com/lukomsky85/" target="_blank" style="color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                    <svg height="13" width="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                    M. Lukomskiy
                </a>
            </p>
        </div>

    </div>

    <style>
    .is-info-page { max-width: 1100px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

    /* Hero */
    .is-hero { display: flex; align-items: center; gap: 16px; padding: 20px 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
    .is-hero-icon { width: 52px; height: 52px; background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .is-hero-icon .dashicons { font-size: 26px; color: #2563eb; width: 26px; height: 26px; }
    .is-hero-text { flex: 1; }
    .is-hero-text h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #111827; letter-spacing: -0.3px; }
    .is-hero-meta { display: flex; align-items: center; gap: 8px; }
    .is-badge { background: #f3f4f6; color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #e5e7eb; }
    .is-sep { color: #d1d5db; }
    .is-author-link { display: inline-flex; align-items: center; font-size: 12px; color: #6b7280; text-decoration: none; font-weight: 600; transition: color .15s; }
    .is-author-link:hover { color: #111827; }
    .is-hero-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .is-hero-actions .button { display: flex; align-items: center; gap: 5px; border-radius: 7px !important; font-weight: 600 !important; }

    /* Stats */
    .is-stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
    a.is-stat-card { text-decoration: none; color: inherit; }
    .is-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 16px; text-align: center; transition: box-shadow .15s, transform .15s; display: block; }
    .is-stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.09); transform: translateY(-2px); }
    .is-stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
    .is-stat-number { font-size: 30px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
    .is-stat-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

    /* Cards */
    .is-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .is-card h3 { margin: 0 0 16px; font-size: 14px; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px; }
    .is-card h3 .dashicons { font-size: 15px; color: #6b7280; width: 15px; height: 15px; }

    /* Main grid */
    .is-main-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px; margin-bottom: 0; }
    .is-main-grid > div { display: flex; flex-direction: column; gap: 20px; }
    .is-main-grid > div .is-card { margin-bottom: 0; flex: 1; }
    .is-main-grid > .is-card { margin-bottom: 20px; }

    /* Feature list */
    .is-feature-list { margin: 0; padding: 0; list-style: none; }
    .is-feature-list li { padding: 7px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; display: flex; align-items: center; gap: 8px; }
    .is-feature-list li::before { content: ''; width: 5px; height: 5px; background: #2563eb; border-radius: 50%; flex-shrink: 0; }
    .is-feature-list li:last-child { border-bottom: none; }

    /* Shortcode list */
    .is-shortcode-list { display: flex; flex-direction: column; }
    .is-shortcode-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
    .is-shortcode-item:last-child { border-bottom: none; }
    .is-shortcode-item code { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: 'SFMono-Regular', Consolas, monospace; white-space: nowrap; flex-shrink: 0; }
    .is-shortcode-item span { font-size: 12px; color: #6b7280; }

    /* Nav grid */
    .is-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
    .is-nav-item { display: flex; align-items: center; gap: 10px; padding: 11px 13px; background: #f9fafb; border: 1px solid #e5e7eb; border-left: 3px solid var(--accent); border-radius: 8px; text-decoration: none; color: inherit; transition: transform .15s, box-shadow .15s, background .15s; }
    .is-nav-item:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-decoration: none; color: inherit; }
    .is-nav-item .dashicons { font-size: 18px; width: 18px; height: 18px; flex-shrink: 0; }
    .is-nav-item strong { display: block; font-size: 12px; color: #111827; margin-bottom: 1px; font-weight: 700; }
    .is-nav-item span { font-size: 11px; color: #6b7280; }

    /* Footer */
    .is-footer { text-align: center; padding: 16px; color: #9ca3af; font-size: 12px; }
    .is-footer a:hover { color: #374151 !important; }

    @media (max-width: 960px) {
        .is-stats-grid { grid-template-columns: repeat(3, 1fr); }
        .is-main-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .is-hero { flex-wrap: wrap; }
        .is-stats-grid { grid-template-columns: repeat(2, 1fr); }
        .is-hero-actions { width: 100%; flex-wrap: wrap; }
    }
    </style>
    <?php
}
/**
 * Получить статистику по записям плагина
 */
function institute_get_plugin_stats() {
    $stats = [
        'fakultets' => 0,
        'kafedras' => 0,
        'srednee' => 0,
        'specialnosti' => 0,
        'prepodavateli' => 0
    ];
    
    $post_types = [
        'fakultet' => 'fakultets',
        'kafedra' => 'kafedras',
        'srednee_obrazovanie' => 'srednee',
        'specialnost' => 'specialnosti',
        'prepodavatel' => 'prepodavateli'
    ];
    
    foreach ($post_types as $post_type => $key) {
        $count = wp_count_posts($post_type);
        $stats[$key] = $count->publish + $count->draft + $count->pending;
    }
    
    return $stats;
}