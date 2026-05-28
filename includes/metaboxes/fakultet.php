<?php
if (!defined('ABSPATH')) exit;

function institute_add_fakultet_metabox() {
    add_meta_box(
        'fakultet_details',
        __('Данные факультета', 'institute-structure'),
        'institute_render_fakultet_metabox',
        'fakultet',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'institute_add_fakultet_metabox');

function institute_render_fakultet_metabox($post) {
    wp_nonce_field('institute_save_fakultet_meta', 'fakultet_meta_nonce');

    $zaveduyushchiy = get_post_meta($post->ID, '_fakultet_zaveduyushchiy', true);
    $email          = get_post_meta($post->ID, '_fakultet_email', true);
    $phone          = get_post_meta($post->ID, '_fakultet_phone', true);
    $auditoria      = get_post_meta($post->ID, '_fakultet_auditoria', true);
    $site           = get_post_meta($post->ID, '_fakultet_site', true);
    $zav_photo_id   = get_post_meta($post->ID, '_fakultet_zav_photo_id', true);
    $opisanie       = get_post_meta($post->ID, '_fakultet_opisanie', true);
    $pubs           = get_post_meta($post->ID, '_fakultet_publications', true);
    if (!is_array($pubs)) $pubs = [];
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
        </nav>

        <!-- Основное -->
        <div class="imb-tab-panel active" data-panel="main">
            <div class="imb-grid-2">
                <div class="imb-field-group">
                    <div class="imb-field">
                        <label>Декан</label>
                        <input type="text" name="fakultet_zaveduyushchiy" value="<?php echo esc_attr($zaveduyushchiy); ?>" class="widefat" placeholder="ФИО декана">
                    </div>
                    <div class="imb-field">
                        <label>Email</label>
                        <input type="email" name="fakultet_email" value="<?php echo esc_attr($email); ?>" class="widefat" placeholder="dekanat@example.com">
                    </div>
                    <div class="imb-field">
                        <label>Телефон</label>
                        <input type="text" name="fakultet_phone" value="<?php echo esc_attr($phone); ?>" class="widefat" placeholder="+7 (000) 000-00-00">
                    </div>
                    <div class="imb-field">
                        <label>Аудитория / Деканат</label>
                        <input type="text" name="fakultet_auditoria" value="<?php echo esc_attr($auditoria); ?>" class="widefat" placeholder="Корпус, аудитория">
                    </div>
                    <div class="imb-field">
                        <label>Сайт</label>
                        <input type="url" name="fakultet_site" value="<?php echo esc_attr($site); ?>" class="widefat" placeholder="https://...">
                    </div>
                </div>

                <div class="imb-photo-col">
                    <label class="imb-photo-label">Фото декана</label>
                    <div class="imb-photo-preview" id="fakultet-zav-photo-preview">
                        <?php if ($zav_photo_id): ?>
                            <?php echo wp_get_attachment_image($zav_photo_id, 'medium'); ?>
                        <?php else: ?>
                            <div class="imb-photo-empty">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span>Фото не выбрано</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="fakultet_zav_photo_id" name="fakultet_zav_photo_id" value="<?php echo esc_attr($zav_photo_id); ?>">
                    <div class="imb-photo-btns">
                        <button type="button" class="button" id="fakultet_zav_upload_btn">
                            <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:14px;"></span> Выбрать
                        </button>
                        <button type="button" class="button imb-btn-danger" id="fakultet_zav_remove_btn" <?php echo !$zav_photo_id ? 'style="display:none"' : ''; ?>>
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:14px;"></span> Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Описание -->
        <div class="imb-tab-panel" data-panel="desc">
            <div class="imb-field" style="margin-bottom:12px;">
                <p class="description">История факультета, направление подготовки, особенности. Отображается на вкладке «О факультете».</p>
            </div>
            <?php wp_editor($opisanie, 'fakultet_opisanie', ['textarea_name' => 'fakultet_opisanie', 'textarea_rows' => 14, 'media_buttons' => true]); ?>
        </div>

        <!-- Публикации -->
        <div class="imb-tab-panel" data-panel="pubs">
            <p class="description" style="margin-bottom:16px;">Научные публикации декана и сотрудников факультета.</p>
            <div id="fakultet-pubs-container">
                <?php foreach ($pubs as $pub): ?>
                <div class="pub-item">
                    <div class="pub-item-row">
                        <input type="text" name="fakultet_pub_title[]" placeholder="Название статьи / работы" value="<?php echo esc_attr($pub['title'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row">
                        <input type="text" name="fakultet_pub_journal[]" placeholder="Журнал / сборник / конференция" value="<?php echo esc_attr($pub['journal'] ?? ''); ?>" class="widefat">
                    </div>
                    <div class="pub-item-row pub-item-meta">
                        <input type="text" name="fakultet_pub_year[]" placeholder="Год" value="<?php echo esc_attr($pub['year'] ?? ''); ?>" style="width:90px;">
                        <input type="url" name="fakultet_pub_link[]" placeholder="DOI / ссылка" value="<?php echo esc_url($pub['link'] ?? ''); ?>" style="flex:1;">
                        <button type="button" class="button remove-pub">
                            <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:13px;"></span> Удалить
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button imb-btn-add" id="add-pub-btn-fakultet">
                <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;font-size:14px;"></span> Добавить публикацию
            </button>
        </div>
    </div>
    <?php
}
