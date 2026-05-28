<?php
if (!defined('ABSPATH')) exit;

/**
 * Управление доступом — привязка пользователей к разделам структуры
 *
 * Логика:
 * - Роль "institute_editor" — может редактировать ТОЛЬКО свой привязанный раздел
 * - Роль "institute_manager" — может редактировать все разделы плагина
 * - Администратор — полный доступ
 */

// ── Регистрация ролей ────────────────────────────────────────────────────────

register_activation_hook(INSTITUTE_PLUGIN_DIR . '../institute-structure.php', 'institute_create_roles');

/**
 * Назначаем capabilities при каждой загрузке — без transient кеша
 * чтобы гарантированно работало для существующих пользователей
 */
add_action('init', 'institute_ensure_capabilities', 1);
function institute_ensure_capabilities() {
    foreach (['administrator', 'editor', 'author'] as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('edit_institute_section')) {
            $role->add_cap('edit_institute_section');
        }
    }
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap('manage_institute_structure')) {
        $admin->add_cap('manage_institute_structure');
    }
}

function institute_create_roles() {
    // Редактор раздела (декан, зав. кафедрой)
    add_role('institute_editor', 'Редактор раздела', [
        'read'                    => true,
        'edit_posts'              => false,
        'edit_institute_section'  => true,
    ]);

    // Менеджер структуры
    add_role('institute_manager', 'Менеджер структуры', [
        'read'                     => true,
        'edit_posts'               => false,
        'edit_institute_section'   => true,
        'manage_institute_structure' => true,
    ]);

    // Добавим capability администратору, редактору и автору
    foreach (['administrator', 'editor', 'author'] as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->add_cap('edit_institute_section');
            $role->add_cap('manage_options_institute');
        }
    }
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_institute_structure');
    }
}

// Убираем роли при деактивации
register_deactivation_hook(INSTITUTE_PLUGIN_DIR . '../institute-structure.php', 'institute_remove_roles');
function institute_remove_roles() {
    remove_role('institute_editor');
    remove_role('institute_manager');
}

// ── Привязка пользователя к разделу ─────────────────────────────────────────

/**
 * Получить все разделы к которым привязан пользователь
 */
function institute_get_user_sections($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();

    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie'];
    $result    = [];

    // Получаем все посты нужных типов и проверяем вручную
    // (надёжнее чем LIKE по сериализованным данным)
    $all_posts = get_posts([
        'post_type'      => $cpt_types,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => 'publish',
    ]);

    foreach ($all_posts as $pid) {
        $users = institute_get_section_users($pid);
        if (in_array((int)$user_id, $users)) {
            $result[] = $pid;
        }
    }

    return $result;
}

/**
 * Получить ID первого раздела привязанного к пользователю (обратная совместимость)
 */
function institute_get_user_section($user_id = null) {
    $sections = institute_get_user_sections($user_id);
    return !empty($sections) ? $sections[0] : null;
}

/**
 * Получить всех пользователей привязанных к посту
 */
function institute_get_section_users($post_id) {
    $ids = get_post_meta($post_id, '_institute_section_users', true);
    return is_array($ids) ? array_map('intval', $ids) : [];
}

/**
 * Может ли текущий пользователь редактировать данный пост
 */
function institute_user_can_edit($post_id) {
    if (current_user_can('administrator') || current_user_can('manage_institute_structure')) {
        return true;
    }
    if (current_user_can('editor')) {
        return true;
    }
    if (current_user_can('edit_institute_section')) {
        $section_users = institute_get_section_users($post_id);
        return in_array((int)get_current_user_id(), array_map('intval', $section_users));
    }
    return false;
}

/**
 * Проверить — является ли пользователь ограниченным редактором раздела
 * (не администратор, не полный редактор, И имеет привязанный раздел)
 */
function institute_is_section_editor() {
    if (current_user_can('administrator')) return false;
    if (current_user_can('editor')) return false;
    if (current_user_can('manage_institute_structure')) return false;
    if (!current_user_can('edit_institute_section')) return false;

    // Только если есть хоть один привязанный раздел
    return !empty(institute_get_user_sections());
}

/**
 * Динамически выдаём право редактировать привязанный пост
 */
add_filter('user_has_cap', 'institute_grant_section_cap', 10, 4);
function institute_grant_section_cap($allcaps, $caps, $args, $user) {
    // Нас интересует только проверка edit_post / edit_published_post
    if (empty($args[0]) || !in_array($args[0], ['edit_post', 'edit_published_post', 'edit_others_posts'])) {
        return $allcaps;
    }

    // Администратор и полный редактор — не трогаем
    if (!empty($allcaps['administrator']) || !empty($allcaps['manage_institute_structure'])) {
        return $allcaps;
    }

    // Пользователь должен иметь edit_institute_section
    if (empty($allcaps['edit_institute_section'])) {
        return $allcaps;
    }

    $post_id = isset($args[2]) ? (int)$args[2] : 0;
    if (!$post_id) return $allcaps;

    // Проверяем что пост принадлежит нашим CPT
    $post_type = get_post_type($post_id);
    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];
    if (!in_array($post_type, $cpt_types)) return $allcaps;

    // Проверяем привязку — новая логика: список users в мета поста
    $section_users = institute_get_section_users($post_id);
    if (in_array((int)$user->ID, $section_users)) {
        foreach ($caps as $cap) {
            $allcaps[$cap] = true;
        }
    }

    return $allcaps;
}



add_action('current_screen', 'institute_restrict_edit_access');
function institute_restrict_edit_access() {
    if (!is_admin()) return;

    $screen = get_current_screen();
    if (!$screen) return;

    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];
    if (!in_array($screen->post_type, $cpt_types)) return;

    // Администратор, менеджер, полный редактор WordPress — полный доступ
    if (current_user_can('administrator') || current_user_can('manage_institute_structure') || current_user_can('editor')) return;

    // Ограниченный редактор раздела (author / institute_editor)
    if (institute_is_section_editor()) {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

        if ($screen->base === 'post' && $post_id) {
            if (!institute_user_can_edit($post_id)) {
                wp_die(
                    '<h1>Доступ запрещён</h1><p>У вас нет прав для редактирования этого раздела.</p>',
                    'Доступ запрещён',
                    ['response' => 403, 'back_link' => true]
                );
            }
        }

        // На странице списка — если один раздел — сразу туда, если несколько — на страницу выбора
        if ($screen->base === 'edit') {
            $sections = institute_get_user_sections();
            if (count($sections) === 1) {
                wp_redirect(admin_url('post.php?post=' . $sections[0] . '&action=edit'));
                exit;
            } elseif (count($sections) > 1) {
                wp_redirect(admin_url('admin.php?page=my-institute-sections'));
                exit;
            }
        }
        return;
    }

    // Все остальные без явного доступа — запрет
    wp_die(
        '<h1>Доступ запрещён</h1><p>У вас нет прав для управления структурой института.</p>',
        'Доступ запрещён',
        ['response' => 403, 'back_link' => true]
    );
}

// ── Скрываем лишние пункты меню для редакторов ──────────────────────────────

add_action('admin_menu', 'institute_filter_admin_menu', 999);
function institute_filter_admin_menu() {
    if (current_user_can('administrator') || current_user_can('manage_institute_structure')) return;

    $sections = institute_get_user_sections();
    if (empty($sections)) return;

    $is_full_editor = current_user_can('editor');

    // Для полного редактора — только добавляем ярлык, меню не скрываем
    if ($is_full_editor) {
        $cap  = 'edit_posts';
    } else {
        $cap  = 'edit_institute_section';
        remove_menu_page('institute-plugin-info');
        foreach (['index.php','edit.php','upload.php','edit-comments.php','themes.php','plugins.php','users.php','tools.php','options-general.php'] as $p) {
            remove_menu_page($p);
        }
    }

    $icon = 'dashicons-edit';

    if (count($sections) === 1) {
        $sid   = $sections[0];
        $title = get_the_title($sid);
        add_menu_page('Мой раздел', esc_html($title), $cap, 'my-institute-section',
            function() use ($sid) {
                wp_redirect(admin_url('post.php?post=' . $sid . '&action=edit')); exit;
            }, $icon, 1);
    } else {
        add_menu_page('Мои разделы', 'Мои разделы', $cap, 'my-institute-sections',
            function() use ($sections) {
                echo '<div class="wrap"><h1>Мои разделы</h1><ul style="margin-top:16px;">';
                foreach ($sections as $sid) {
                    echo '<li style="margin-bottom:8px;"><a href="' . admin_url('post.php?post=' . $sid . '&action=edit') . '" style="font-size:15px;">' . esc_html(get_the_title($sid)) . '</a></li>';
                }
                echo '</ul></div>';
            }, $icon, 1);
    }
}

// ── Метабокс привязки пользователя (только для администратора) ───────────────

add_action('add_meta_boxes', 'institute_add_user_access_metabox');
function institute_add_user_access_metabox() {
    if (!current_user_can('administrator') && !current_user_can('manage_institute_structure')) return;

    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie'];
    foreach ($cpt_types as $cpt) {
        add_meta_box(
            'institute_user_access',
            '🔑 Доступ к редактированию',
            'institute_render_user_access_metabox',
            $cpt,
            'side',
            'default'
        );
    }
}

function institute_render_user_access_metabox($post) {
    wp_nonce_field('institute_save_user_access', 'institute_user_access_nonce');

    $bound_user_ids = institute_get_section_users($post->ID);

    $all_users = get_users([
        'role__in' => ['institute_editor', 'institute_manager', 'editor', 'author', 'administrator'],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'exclude'  => [get_current_user_id()], // администратора не показываем
    ]);

    // Добавим всех авторов даже если не в этих ролях
    $all_users_extra = get_users([
        'role__not_in' => ['subscriber'],
        'orderby'      => 'display_name',
        'order'        => 'ASC',
    ]);

    $roles_map = [
        'administrator'     => 'Администратор',
        'editor'            => 'Редактор',
        'author'            => 'Автор',
        'institute_manager' => 'Менеджер структуры',
        'institute_editor'  => 'Редактор раздела',
        'contributor'       => 'Участник',
    ];
    ?>
    <div class="imb-access-box">
        <p class="description" style="margin-bottom:10px; font-size:11px;">
            Отмеченные пользователи смогут редактировать этот раздел.
        </p>

        <div style="max-height:200px; overflow-y:auto; border:1.5px solid #e5e7eb; border-radius:6px; padding:4px 0;">
            <?php foreach ($all_users_extra as $u):
                $u_obj     = new WP_User($u->ID);
                $role_key  = !empty($u_obj->roles) ? $u_obj->roles[0] : '';
                $role_name = $roles_map[$role_key] ?? $role_key;
                $checked   = in_array($u->ID, $bound_user_ids);
            ?>
            <label style="display:flex; align-items:center; gap:8px; padding:6px 10px; cursor:pointer; transition:background .1s; <?php echo $checked ? 'background:#f0fdf4;' : ''; ?>"
                   onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='<?php echo $checked ? '#f0fdf4' : 'transparent'; ?>'">
                <input type="checkbox" name="institute_section_users[]"
                       value="<?php echo $u->ID; ?>"
                       <?php checked($checked); ?>
                       style="margin:0; flex-shrink:0;">
                <span style="flex:1; min-width:0;">
                    <span style="font-size:12px; font-weight:600; color:#111827; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo esc_html($u->display_name); ?>
                    </span>
                    <span style="font-size:10px; color:#9ca3af;">
                        <?php echo esc_html($role_name); ?> · <?php echo esc_html($u->user_login); ?>
                    </span>
                </span>
                <?php if ($checked): ?>
                <span style="font-size:10px; color:#15803d; font-weight:700; flex-shrink:0;">✓</span>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($bound_user_ids)): ?>
        <div style="margin-top:8px; padding:8px 10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
            <div style="font-size:11px; color:#15803d; font-weight:600; margin-bottom:4px;">Имеют доступ:</div>
            <?php foreach ($bound_user_ids as $uid):
                $u = get_userdata($uid);
                if (!$u) continue;
            ?>
            <div style="font-size:11px; color:#374151;">· <?php echo esc_html($u->display_name); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ── Сохранение привязки ──────────────────────────────────────────────────────

add_action('save_post', 'institute_save_user_access');
function institute_save_user_access($post_id) {
    if (!isset($_POST['institute_user_access_nonce'])) return;
    if (!wp_verify_nonce($_POST['institute_user_access_nonce'], 'institute_save_user_access')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('administrator') && !current_user_can('manage_institute_structure')) return;

    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie'];
    if (!in_array(get_post_type($post_id), $cpt_types)) return;

    // Получаем массив выбранных пользователей
    $new_user_ids = [];
    if (!empty($_POST['institute_section_users']) && is_array($_POST['institute_section_users'])) {
        $new_user_ids = array_map('absint', $_POST['institute_section_users']);
        $new_user_ids = array_filter($new_user_ids); // убираем нули
        $new_user_ids = array_values($new_user_ids);
    }

    // Сохраняем список в мета поста
    update_post_meta($post_id, '_institute_section_users', $new_user_ids);
}

// ── Страница управления доступом ─────────────────────────────────────────────

add_action('admin_menu', 'institute_add_access_page', 20);
function institute_add_access_page() {
    add_submenu_page(
        'institute-plugin-info',
        'Управление доступом',
        '— Доступ',
        'manage_options',
        'institute-access',
        'institute_access_page_callback'
    );
}

function institute_access_page_callback() {
    if (!current_user_can('administrator') && !current_user_can('manage_institute_structure')) {
        wp_die('Доступ запрещён');
    }

    $cpt_types = [
        'kafedra'             => 'Кафедры',
        'srednee_obrazovanie' => 'Отделения СПО',
        'fakultet'            => 'Факультеты',
    ];
    ?>
    <div class="wrap">
        <h1>Управление доступом к разделам</h1>
        <p class="description" style="margin-bottom:20px;">
            Привязку пользователей можно изменить на странице редактирования раздела в боксе <strong>«Доступ к редактированию»</strong>.
        </p>

        <div style="display:flex; flex-direction:column; gap:24px;">
        <?php foreach ($cpt_types as $cpt => $label):
            $posts = get_posts(['post_type' => $cpt, 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
            if (empty($posts)) continue;
        ?>
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05);">
                <div style="padding:14px 18px; background:#f9fafb; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:14px; font-weight:700; color:#111827;"><?php echo esc_html($label); ?></span>
                    <span style="background:#e5e7eb; color:#374151; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px;"><?php echo count($posts); ?></span>
                </div>

                <?php foreach ($posts as $p):
                    $section_users = institute_get_section_users($p->ID);

                    // Преподаватели привязанные к этому разделу
                    $meta_key = ($cpt === 'kafedra') ? 'kafedra_' . $p->ID : 'srednee_' . $p->ID;
                    $prepods  = get_posts([
                        'post_type'      => 'prepodavatel',
                        'numberposts'    => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'meta_query'     => [[
                            'key'     => '_prepodavatel_pripisann_k',
                            'value'   => ($cpt === 'kafedra' ? 'kafedra_' : ($cpt === 'srednee_obrazovanie' ? 'srednee_' : 'fakultet_')) . $p->ID,
                            'compare' => '=',
                        ]],
                    ]);
                ?>
                <div style="border-bottom:1px solid #f3f4f6; padding:14px 18px;">
                    <div style="display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap;">

                        <!-- Название раздела -->
                        <div style="flex:1; min-width:200px;">
                            <a href="<?php echo get_edit_post_link($p->ID); ?>" style="font-size:14px; font-weight:600; color:#2563eb; text-decoration:none;">
                                <?php echo esc_html($p->post_title); ?>
                            </a>
                        </div>

                        <!-- Пользователи с доступом -->
                        <div style="min-width:180px;">
                            <div style="font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px;">Доступ к редактированию</div>
                            <?php if (!empty($section_users)): ?>
                                <?php foreach ($section_users as $uid):
                                    $su = get_userdata($uid);
                                    if (!$su) continue;
                                    $su_obj   = new WP_User($uid);
                                    $su_role  = !empty($su_obj->roles) ? $su_obj->roles[0] : '';
                                    $roles_map = ['administrator'=>'Администратор','editor'=>'Редактор','author'=>'Автор','institute_editor'=>'Ред. раздела','institute_manager'=>'Менеджер'];
                                    $su_role_name = $roles_map[$su_role] ?? $su_role;
                                ?>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:3px;">
                                    <span style="background:#f0fdf4; color:#15803d; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; border:1px solid #bbf7d0;">
                                        <?php echo esc_html($su->display_name); ?>
                                    </span>
                                    <span style="font-size:10px; color:#9ca3af;"><?php echo esc_html($su_role_name); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="font-size:12px; color:#9ca3af;">Нет доступа</span>
                            <?php endif; ?>
                        </div>

                        <!-- Преподаватели раздела -->
                        <?php if ($cpt !== 'fakultet'): ?>
                        <div style="min-width:200px; flex:2;">
                            <div style="font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px;">
                                Преподаватели (<?php echo count($prepods); ?>)
                            </div>
                            <?php if (!empty($prepods)): ?>
                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                <?php foreach ($prepods as $pr):
                                    $dolzhnost = get_post_meta($pr->ID, '_prepodavatel_dolzhnost', true);
                                    $photo_id  = get_post_meta($pr->ID, '_prepodavatel_photo_id', true);
                                ?>
                                <a href="<?php echo get_edit_post_link($pr->ID); ?>"
                                   title="<?php echo esc_attr($dolzhnost); ?>"
                                   style="display:inline-flex; align-items:center; gap:5px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:3px 8px; font-size:11px; color:#374151; text-decoration:none; transition:all .15s;"
                                   onmouseover="this.style.background='#eff6ff';this.style.borderColor='#bfdbfe'"
                                   onmouseout="this.style.background='#f9fafb';this.style.borderColor='#e5e7eb'">
                                    <?php if ($photo_id):
                                        $img = wp_get_attachment_image_src($photo_id, 'thumbnail');
                                        if ($img): ?>
                                        <img src="<?php echo esc_url($img[0]); ?>" style="width:18px; height:18px; border-radius:50%; object-fit:cover; flex-shrink:0;">
                                    <?php endif; endif; ?>
                                    <?php echo esc_html($pr->post_title); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                                <span style="font-size:12px; color:#9ca3af;">Преподаватели не добавлены</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>

        <div style="margin-top:24px; padding:16px 20px; background:#fffbeb; border:1px solid #fde68a; border-radius:10px;">
            <h3 style="margin:0 0 8px; font-size:13px; color:#92400e;">Как назначить доступ:</h3>
            <ol style="margin:0; padding-left:18px; color:#78350f; font-size:12px; line-height:1.8;">
                <li>Создайте пользователя WordPress (или используйте существующего)</li>
                <li>Установите роль <strong>«Редактор раздела»</strong> или <strong>«Автор»</strong></li>
                <li>Откройте нужный раздел в режиме редактирования</li>
                <li>В боксе <strong>«Доступ к редактированию»</strong> (справа) отметьте пользователя</li>
                <li>Сохраните</li>
            </ol>
        </div>
    </div>
    <?php
}
