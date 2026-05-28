<?php
/**
 * Plugin Name: Institute Structure
 * Plugin URI: https://github.com/lukomsky85/institute-structure/
 * Description: Управление структурой института (СПО, кафедры, преподаватели, факультеты, специальности) с публичными страницами, привязкой, фото заведующих и шорткодами. Включает расписание преподавателей и синхронизацию с API.
 * Version: 3.0
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Author: M. Lukomskiy
 * Author URI: https://github.com/lukomsky85/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: institute-structure
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Константы плагина
define('INSTITUTE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INSTITUTE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INSTITUTE_PLUGIN_VERSION', '3.0');

/**
 * Автозагрузка файлов плагина
 */
function institute_autoload() {
    // Вспомогательные функции (загружаем первыми — они нужны в других файлах)
    require_once INSTITUTE_PLUGIN_DIR . 'includes/helper-functions.php';

    // Управление доступом (подключаем только если файл существует)
    if (file_exists(INSTITUTE_PLUGIN_DIR . 'includes/access-control.php')) {
        require_once INSTITUTE_PLUGIN_DIR . 'includes/access-control.php';
    }

    // История изменений (подключаем только если файл существует)
    if (file_exists(INSTITUTE_PLUGIN_DIR . 'includes/change-history.php')) {
        require_once INSTITUTE_PLUGIN_DIR . 'includes/change-history.php';
    }
    
    // Регистрация CPT (регистрируется на хук init внутри файла)
    require_once INSTITUTE_PLUGIN_DIR . 'includes/cpt-registration.php';
    
    // Метабоксы
    require_once INSTITUTE_PLUGIN_DIR . 'includes/metaboxes/fakultet.php';
    require_once INSTITUTE_PLUGIN_DIR . 'includes/metaboxes/kafedra.php';
    require_once INSTITUTE_PLUGIN_DIR . 'includes/metaboxes/srednee.php';
    require_once INSTITUTE_PLUGIN_DIR . 'includes/metaboxes/specialnost.php';
    require_once INSTITUTE_PLUGIN_DIR . 'includes/metaboxes/prepodavatel.php';
    
    // Обработчики сохранения
    require_once INSTITUTE_PLUGIN_DIR . 'includes/save-handlers.php';
    
    // API
    require_once INSTITUTE_PLUGIN_DIR . 'includes/api/raspisanie-api.php';
    require_once INSTITUTE_PLUGIN_DIR . 'includes/api/teacher-sync.php';
    
    // Рендереры списков и расписания
    require_once INSTITUTE_PLUGIN_DIR . 'includes/templates/list-renderer.php';

    // Шорткоды
    require_once INSTITUTE_PLUGIN_DIR . 'includes/shortcodes.php';
    
    // Страница информации о плагине (регистрирует главный пункт меню)
    require_once INSTITUTE_PLUGIN_DIR . 'admin/plugin-info-page.php';

    // Админка — синхронизация (подменю, должно идти ПОСЛЕ plugin-info-page)
    require_once INSTITUTE_PLUGIN_DIR . 'admin/sync-page.php';
}
add_action('plugins_loaded', 'institute_autoload');

/**
 * Добавляем класс institute-meta-box к нашим метабоксам
 */
add_filter('postbox_classes_kafedra_kafedra_details',              'institute_metabox_class');
add_filter('postbox_classes_fakultet_fakultet_details',            'institute_metabox_class');
add_filter('postbox_classes_srednee_obrazovanie_srednee_details',  'institute_metabox_class');
add_filter('postbox_classes_prepodavatel_prepodavatel_details',    'institute_metabox_class');
add_filter('postbox_classes_specialnost_specialnost_details',      'institute_metabox_class');
function institute_metabox_class($classes) {
    $classes[] = 'institute-meta-box';
    return $classes;
}

/**
 * AJAX — открепить преподавателя от раздела
 */
add_action('wp_ajax_institute_detach_prepod', 'institute_ajax_detach_prepod');
function institute_ajax_detach_prepod() {
    check_ajax_referer('institute_detach_prepod', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Нет прав');

    $prepod_id = absint($_POST['prepod_id'] ?? 0);
    if (!$prepod_id) wp_send_json_error('Не указан ID преподавателя');

    if (get_post_type($prepod_id) !== 'prepodavatel') wp_send_json_error('Неверный тип записи');

    delete_post_meta($prepod_id, '_prepodavatel_pripisann_k');
    wp_send_json_success(['message' => 'Преподаватель откреплён']);
}

/**
 * Отключаем Gutenberg для всех CPT плагина — используем классический редактор
 */
add_filter('use_block_editor_for_post_type', 'institute_disable_gutenberg', 10, 2);
function institute_disable_gutenberg($use_block_editor, $post_type) {
    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];
    if (in_array($post_type, $cpt_types)) {
        return false;
    }
    return $use_block_editor;
}

/**
 * Активация плагина
 */
register_activation_hook(__FILE__, 'institute_plugin_activate');
function institute_plugin_activate() {
    // Обновление пермалинков (регистрация CPT произойдёт на хуке init при следующей загрузке)
    flush_rewrite_rules();
    
    // Создание шаблонного файла
    institute_create_template_file();
}

/**
 * Деактивация плагина
 */
register_deactivation_hook(__FILE__, 'institute_plugin_deactivate');
function institute_plugin_deactivate() {
    flush_rewrite_rules();
}

/**
 * Подключение стилей и скриптов
 */
add_action('wp_enqueue_scripts', 'institute_enqueue_frontend_assets');
function institute_enqueue_frontend_assets() {
    wp_enqueue_style(
        'institute-frontend',
        INSTITUTE_PLUGIN_URL . 'assets/frontend-styles.css',
        [],
        INSTITUTE_PLUGIN_VERSION
    );
}

add_action('admin_enqueue_scripts', 'institute_enqueue_admin_assets');
function institute_enqueue_admin_assets($hook) {
    global $post_type;
    $cpt_types = ['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'];

    // Стили — на всех страницах админки
    wp_enqueue_style(
        'institute-admin',
        INSTITUTE_PLUGIN_URL . 'admin/admin-styles.css',
        [],
        INSTITUTE_PLUGIN_VERSION
    );

    // Скрипты и медиа — на страницах редактирования наших CPT
    if (in_array($hook, ['post.php', 'post-new.php']) && in_array($post_type, $cpt_types)) {
        wp_enqueue_media();
        wp_enqueue_script(
            'institute-admin-scripts',
            INSTITUTE_PLUGIN_URL . 'admin/admin-scripts.js',
            ['jquery'],
            INSTITUTE_PLUGIN_VERSION,
            true
        );
        wp_localize_script('institute-admin-scripts', 'instituteAdmin', [
            'detachNonce' => wp_create_nonce('institute_detach_prepod'),
        ]);
    }
}

/**
 * Шаблоны страниц
 */
add_filter('template_include', 'institute_template_include');
function institute_template_include($template) {
    if (is_singular(['fakultet', 'kafedra', 'srednee_obrazovanie', 'specialnost', 'prepodavatel'])) {
        $custom_template = INSTITUTE_PLUGIN_DIR . 'templates/single-institute-structure.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}

/**
 * Создание шаблонного файла
 */
function institute_create_template_file() {
    $template_path = INSTITUTE_PLUGIN_DIR . 'templates/single-institute-structure.php';
    
    if (!file_exists($template_path)) {
        $template_content = '<?php
/**
 * Шаблон для отображения записей структуры института
 * Автоматически создан плагином "Структура института"
 */
get_header();
?>
<div class="container institute-single">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            
            <div class="entry-content">
                <?php
                $post_type = get_post_type();
                $post_id = get_the_ID();
                
                // Подключаем соответствующий шаблон
                $template_file = INSTITUTE_PLUGIN_DIR . \'includes/templates/single-\' . $post_type . \'.php\';
                
                if (file_exists($template_file)) {
                    include $template_file;
                }
                
                // Стандартный контент записи
                if (get_the_content()) {
                    the_content();
                }
                ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?>';
        
        file_put_contents($template_path, $template_content);
    }
}