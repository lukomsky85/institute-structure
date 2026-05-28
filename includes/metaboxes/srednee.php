<?php
if (!defined('ABSPATH')) exit;

function institute_add_srednee_metabox() {
    add_meta_box(
        'srednee_details',
        __('Данные отделения', 'institute-structure'),
        'institute_render_srednee_metabox',
        'srednee_obrazovanie',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'institute_add_srednee_metabox');

function institute_render_srednee_metabox($post) {
    wp_nonce_field('institute_save_srednee_meta', 'srednee_meta_nonce');

    $zaveduyushchiy = get_post_meta($post->ID, '_srednee_zaveduyushchiy', true);
    $email          = get_post_meta($post->ID, '_srednee_email', true);
    $phone          = get_post_meta($post->ID, '_srednee_phone', true);
    $auditoria      = get_post_meta($post->ID, '_srednee_auditoria', true);
    $site           = get_post_meta($post->ID, '_srednee_site', true);
    $zav_photo_id   = get_post_meta($post->ID, '_srednee_zav_photo_id', true);
    $opisanie       = get_post_meta($post->ID, '_srednee_opisanie', true);
    $pubs           = get_post_meta($post->ID, '_srednee_publications', true);
    if (!is_array($pubs)) $pubs = [];

    $prepods = get_posts([
        'post_type'   => 'prepodavatel',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
        'meta_query'  => [['key' => '_prepodavatel_pripisann_k', 'value' => 'srednee_' . $post->ID]],
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
                        <label>Заведующий отделением</label>
                        <input type="text" name="srednee_zaveduyushchiy" value="<?php echo esc_attr($zaveduyushchiy); ?>" class="widefat" placeholder="ФИО заведующего">
                    </div>
                    <div class="imb-field">
                        <label>Email</label>
                        <input type="email" name="srednee_email" value="<?php echo esc_attr($email); ?>" class="widefat" placeholder="otdelenie@example.com">
                    </div>
                    <div class="imb-field">
                        <label>Телефон</label>
                        <input type="text" name="srednee_phone" value="<?php echo esc_attr($phone); ?>" class="widefat" placeholder="+7 (000) 000-00-00">
                    </div>
                    <div class="imb-field">
                        <label>Аудитория</label>
                        <input type="text" name="srednee_auditoria" value="<?php echo esc_attr($auditoria); ?>" class="widefat" placeholder="Корпус, аудитория">
                    </div>
                    <div class="imb-field">
                        <label>Сайт</label>
                        <input type="url" name="srednee_site" value="<?php echo esc_attr($site); ?>" class="widefat" placeholder="https://...">
                    </div>
                </div>

                <div class="imb-photo-col">
                    <label class="imb-photo-label">Фото заведующего</label>
                    <div class="imb-photo-preview" id="srednee-zav-photo-preview">
                        <?php if ($zav_photo_id): ?>
                            <?php echo wp_get_attachment_image($zav_photo_id, 'medium'); ?>
                        <?php else: ?>
                            <div class="imb-photo-empty">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span>Фото не выбрано</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="srednee_zav_photo_id" name="srednee_zav_photo_id" value="<?php echo esc_attr($zav_photo_id); ?>">
                    <div class="imb-photo-btns">
                        <button type="button" class="button" id="srednee_zav_upload_btn">
                            <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:14px;"></span> Выбрать
                        </button>
                        <button type="button" class="button imb-btn-danger" id="srednee_zav_remove_btn" <?php echo !$zav_photo_id ? 'style="display:none"' : ''; ?>>
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:14px;"></span> Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Описание -->
        <div class="imb-tab-panel" data-panel="desc">
            <div class="imb-field" style="margin-bottom:8px;">
                <label>Описание отделения</label>
                <p class="description">История отделения, направления подготовки. Отображается на вкладке «Об отделении».</p>
            </div>
            <?php wp_editor($opisanie, 'srednee_opisanie', [
                'textarea_name' => 'srednee_opisanie',
                'textarea_rows' => 14,
                'media_buttons' => true,
                'tinymce'       => true,
                'quicktags'     => true,
            ]); ?>
        </div>

        <!-- Публикации -->
        <div class="imb-tab-panel" data-panel="pubs">
            <p class="description" style="margin-bottom:16px;">Научные и методические публикации заведующего и сотрудников отделения.</p>
            <div id="srednee-pubs-container">
                <?php foreach ($pubs as $pub): ?>
                <div class="pub-item">
                    <div class="pub-item-row">
                        <input type="text" name="srednee_pub_title[]" placeholder="Название статьи / работы" value="<?php echo esc_attr($pub['title'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row">
                        <input type="text" name="srednee_pub_journal[]" placeholder="Журнал / сборник / конференция" value="<?php echo esc_attr($pub['journal'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row pub-item-meta">
                        <input type="text" name="srednee_pub_year[]" placeholder="Год" value="<?php echo esc_attr($pub['year'] ?? ''); ?>" style="width:90px;">
                        <input type="url" name="srednee_pub_link[]" placeholder="DOI / ссылка" value="<?php echo esc_url($pub['link'] ?? ''); ?>" style="flex:1;">
                        <button type="button" class="button remove-pub">
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:13px;"></span> Удалить
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button imb-btn-add" id="add-pub-btn-srednee">
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
                    <button type="button" class="imb-prepod-detach" data-prepod-id="<?php echo $pr->ID; ?>" title="Открепить от отделения">
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
                <p style="margin:0 0 10px;font-size:13px;">К этому отделению не привязано ни одного преподавателя.</p>
                <a href="<?php echo admin_url('post-new.php?post_type=prepodavatel'); ?>" class="button">+ Добавить преподавателя</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
