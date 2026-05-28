<?php
if (!defined('ABSPATH')) exit;

function institute_add_kafedra_metabox() {
    add_meta_box(
        'kafedra_details',
        __('Данные кафедры', 'institute-structure'),
        'institute_render_kafedra_metabox',
        'kafedra',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'institute_add_kafedra_metabox');

function institute_render_kafedra_metabox($post) {
    wp_nonce_field('institute_save_kafedra_meta', 'kafedra_meta_nonce');

    $zaveduyushchiy = get_post_meta($post->ID, '_kafedra_zaveduyushchiy', true);
    $email          = get_post_meta($post->ID, '_kafedra_email', true);
    $phone          = get_post_meta($post->ID, '_kafedra_phone', true);
    $auditoria      = get_post_meta($post->ID, '_kafedra_auditoria', true);
    $site           = get_post_meta($post->ID, '_kafedra_site', true);
    $fakultet_id    = get_post_meta($post->ID, '_kafedra_fakultet', true);
    $zav_photo_id   = get_post_meta($post->ID, '_kafedra_zav_photo_id', true);
    $opisanie       = get_post_meta($post->ID, '_kafedra_opisanie', true);
    $nmr            = get_post_meta($post->ID, '_kafedra_nmr', true);
    $nrs            = get_post_meta($post->ID, '_kafedra_nrs', true);
    $uvr            = get_post_meta($post->ID, '_kafedra_uvr', true);
    $pubs           = get_post_meta($post->ID, '_kafedra_publications', true);
    if (!is_array($pubs)) $pubs = [];

    $fakultety = get_posts(['post_type' => 'fakultet', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);

    $prepods = get_posts([
        'post_type'   => 'prepodavatel',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
        'meta_query'  => [['key' => '_prepodavatel_pripisann_k', 'value' => 'kafedra_' . $post->ID]],
    ]);
    ?>
    <div class="imb-tabs">
        <nav class="imb-tab-nav">
            <button type="button" class="imb-tab-btn active" data-tab="main">
                <span class="dashicons dashicons-admin-home"></span> Основное
            </button>
            <button type="button" class="imb-tab-btn" data-tab="desc">
                <span class="dashicons dashicons-text"></span> Описание
            </button>
            <button type="button" class="imb-tab-btn" data-tab="work">
                <span class="dashicons dashicons-portfolio"></span> НМР / НРС / УВР
            </button>
            <button type="button" class="imb-tab-btn" data-tab="pubs">
                <span class="dashicons dashicons-book-alt"></span> Публикации
                <?php if (!empty($pubs)): ?><span class="imb-tab-count"><?php echo count($pubs); ?></span><?php endif; ?>
            </button>
            <button type="button" class="imb-tab-btn" data-tab="prepods">
                <span class="dashicons dashicons-groups"></span> Преподаватели
                <span class="imb-tab-count"><?php echo count($prepods); ?></span>
            </button>
        </nav>

        <!-- Основное -->
        <div class="imb-tab-panel active" data-panel="main">
            <div class="imb-grid-2">
                <div class="imb-field-group">
                    <div class="imb-field">
                        <label>Факультет</label>
                        <select name="kafedra_fakultet" class="widefat">
                            <option value="">— Не выбран —</option>
                            <?php foreach ($fakultety as $f): ?>
                            <option value="<?php echo $f->ID; ?>" <?php selected($fakultet_id, $f->ID); ?>>
                                <?php echo esc_html($f->post_title); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="imb-field">
                        <label>Заведующий</label>
                        <input type="text" name="kafedra_zaveduyushchiy" value="<?php echo esc_attr($zaveduyushchiy); ?>" class="widefat" placeholder="ФИО заведующего">
                    </div>
                    <div class="imb-field">
                        <label>Email</label>
                        <input type="email" name="kafedra_email" value="<?php echo esc_attr($email); ?>" class="widefat" placeholder="kafedra@example.com">
                    </div>
                    <div class="imb-field">
                        <label>Телефон</label>
                        <input type="text" name="kafedra_phone" value="<?php echo esc_attr($phone); ?>" class="widefat" placeholder="+7 (000) 000-00-00">
                    </div>
                    <div class="imb-field">
                        <label>Аудитория</label>
                        <input type="text" name="kafedra_auditoria" value="<?php echo esc_attr($auditoria); ?>" class="widefat" placeholder="Корпус, аудитория">
                    </div>
                    <div class="imb-field">
                        <label>Сайт</label>
                        <input type="url" name="kafedra_site" value="<?php echo esc_attr($site); ?>" class="widefat" placeholder="https://...">
                    </div>
                </div>

                <div class="imb-photo-col">
                    <label class="imb-photo-label">Фото заведующего</label>
                    <div class="imb-photo-preview" id="kafedra-zav-photo-preview">
                        <?php if ($zav_photo_id): ?>
                            <?php echo wp_get_attachment_image($zav_photo_id, 'medium'); ?>
                        <?php else: ?>
                            <div class="imb-photo-empty">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span>Фото не выбрано</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="kafedra_zav_photo_id" name="kafedra_zav_photo_id" value="<?php echo esc_attr($zav_photo_id); ?>">
                    <div class="imb-photo-btns">
                        <button type="button" class="button" id="kafedra_zav_upload_btn">
                            <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:14px;"></span> Выбрать
                        </button>
                        <button type="button" class="button imb-btn-danger" id="kafedra_zav_remove_btn" <?php echo !$zav_photo_id ? 'style="display:none"' : ''; ?>>
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:14px;"></span> Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Описание -->
        <div class="imb-tab-panel" data-panel="desc">
            <div class="imb-field" style="margin-bottom:8px;">
                <label>Общее описание кафедры</label>
                <p class="description">Краткая история, направление деятельности. Отображается на вкладке «О кафедре».</p>
            </div>
            <?php wp_editor($opisanie, 'kafedra_opisanie', [
                'textarea_name' => 'kafedra_opisanie',
                'textarea_rows' => 12,
                'media_buttons' => true,
                'tinymce'       => true,
                'quicktags'     => true,
            ]); ?>
        </div>

        <!-- НМР / НРС / УВР -->
        <div class="imb-tab-panel" data-panel="work">
            <div class="imb-work-section">
                <h4><span class="dashicons dashicons-chart-bar"></span> Научно-методическая работа (НМР)</h4>
                <?php wp_editor($nmr, 'kafedra_nmr', [
                    'textarea_name' => 'kafedra_nmr',
                    'textarea_rows' => 8,
                    'media_buttons' => true,
                    'tinymce'       => true,
                    'quicktags'     => true,
                ]); ?>
            </div>
            <div class="imb-work-section">
                <h4><span class="dashicons dashicons-groups"></span> Научная работа студентов (НРС)</h4>
                <?php wp_editor($nrs, 'kafedra_nrs', [
                    'textarea_name' => 'kafedra_nrs',
                    'textarea_rows' => 8,
                    'media_buttons' => true,
                    'tinymce'       => true,
                    'quicktags'     => true,
                ]); ?>
            </div>
            <div class="imb-work-section">
                <h4><span class="dashicons dashicons-heart"></span> Учебно-воспитательная работа (УВР)</h4>
                <?php wp_editor($uvr, 'kafedra_uvr', [
                    'textarea_name' => 'kafedra_uvr',
                    'textarea_rows' => 8,
                    'media_buttons' => true,
                    'tinymce'       => true,
                    'quicktags'     => true,
                ]); ?>
            </div>
        </div>

        <!-- Публикации -->
        <div class="imb-tab-panel" data-panel="pubs">
            <p class="description" style="margin-bottom:16px;">Научные публикации заведующего и сотрудников кафедры.</p>
            <div id="kafedra-pubs-container">
                <?php foreach ($pubs as $pub): ?>
                <div class="pub-item">
                    <div class="pub-item-row">
                        <input type="text" name="kafedra_pub_title[]" placeholder="Название статьи / работы" value="<?php echo esc_attr($pub['title'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row">
                        <input type="text" name="kafedra_pub_journal[]" placeholder="Журнал / сборник / конференция" value="<?php echo esc_attr($pub['journal'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row pub-item-meta">
                        <input type="text" name="kafedra_pub_year[]" placeholder="Год" value="<?php echo esc_attr($pub['year'] ?? ''); ?>" style="width:90px;">
                        <input type="url" name="kafedra_pub_link[]" placeholder="DOI / ссылка" value="<?php echo esc_url($pub['link'] ?? ''); ?>" style="flex:1;">
                        <button type="button" class="button remove-pub">
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:13px;"></span> Удалить
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button imb-btn-add" id="add-pub-btn-kafedra">
                <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;font-size:14px;"></span> Добавить публикацию
            </button>
        </div>

        <!-- Преподаватели -->
        <div class="imb-tab-panel" data-panel="prepods">
            <?php if (!empty($prepods)): ?>
            <div class="imb-prepods-grid">
                <?php foreach ($prepods as $pr):
                    $photo_id  = get_post_meta($pr->ID, '_prepodavatel_photo_id', true);
                    $dolzhnost = get_post_meta($pr->ID, '_prepodavatel_dolzhnost', true);
                    $stepen    = get_post_meta($pr->ID, '_prepodavatel_stepen', true);
                    $photo_src = $photo_id ? wp_get_attachment_image_src($photo_id, 'thumbnail') : null;
                ?>
                <div class="imb-prepod-wrap">
                    <a href="<?php echo get_edit_post_link($pr->ID); ?>" class="imb-prepod-item" target="_blank">
                        <div class="imb-prepod-photo">
                            <?php if ($photo_src): ?>
                                <img src="<?php echo esc_url($photo_src[0]); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-admin-users"></span>
                            <?php endif; ?>
                        </div>
                        <div class="imb-prepod-info">
                            <div class="imb-prepod-name"><?php echo esc_html($pr->post_title); ?></div>
                            <?php if ($dolzhnost): ?><div class="imb-prepod-role"><?php echo esc_html($dolzhnost); ?></div><?php endif; ?>
                            <?php if ($stepen): ?><div class="imb-prepod-degree"><?php echo esc_html($stepen); ?></div><?php endif; ?>
                        </div>
                        <span class="dashicons dashicons-external imb-prepod-ext"></span>
                    </a>
                    <button type="button" class="imb-prepod-detach" data-prepod-id="<?php echo $pr->ID; ?>" title="Открепить от кафедры">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:14px;font-size:11px;color:#9ca3af;">
                <a href="<?php echo admin_url('post-new.php?post_type=prepodavatel'); ?>" style="color:#2563eb;">+ Добавить нового преподавателя</a>
            </p>
            <?php else: ?>
            <div style="text-align:center;padding:32px 16px;color:#9ca3af;">
                <span class="dashicons dashicons-groups" style="font-size:36px;width:36px;height:36px;margin-bottom:10px;display:block;margin-inline:auto;"></span>
                <p style="margin:0 0 10px;font-size:13px;">К этой кафедре не привязано ни одного преподавателя.</p>
                <a href="<?php echo admin_url('post-new.php?post_type=prepodavatel'); ?>" class="button">+ Добавить преподавателя</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
