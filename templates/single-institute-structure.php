<?php
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
                $template_file = INSTITUTE_PLUGIN_DIR . 'includes/templates/single-' . $post_type . '.php';
                
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
<?php get_footer(); ?>