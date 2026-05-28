jQuery(document).ready(function($) {
    'use strict';

    // ── Вкладки метабоксов ────────────────────────────────────────────────
    $(document).on('click', '.imb-tab-btn', function() {
        var $btn  = $(this);
        var tab   = $btn.data('tab');
        var $tabs = $btn.closest('.imb-tabs');

        $tabs.find('.imb-tab-btn').removeClass('active');
        $tabs.find('.imb-tab-panel').removeClass('active');
        $btn.addClass('active');

        var $panel = $tabs.find('[data-panel="' + tab + '"]');
        $panel.addClass('active');

        // Перезапускаем TinyMCE редакторы в активированной панели
        if (typeof tinymce !== 'undefined') {
            $panel.find('.wp-editor-area').each(function() {
                var editorId = $(this).attr('id');
                if (editorId) {
                    var editor = tinymce.get(editorId);
                    if (editor) {
                        editor.show();
                        // Принудительно обновляем размеры редактора
                        setTimeout(function() {
                            if (tinymce.get(editorId)) {
                                tinymce.get(editorId).execCommand('mceAutoResize');
                            }
                        }, 100);
                    } else {
                        // Редактор не инициализирован — инициализируем
                        tinymce.init(tinymce.settings);
                    }
                }
            });
        }
    });

    // ── Открепить преподавателя ───────────────────────────────────────────
    $(document).on('click', '.imb-prepod-detach', function() {
        var $btn    = $(this);
        var $wrap   = $btn.closest('.imb-prepod-wrap');
        var prepodId = $btn.data('prepod-id');
        var name    = $wrap.find('.imb-prepod-name').text();

        if (!confirm('Открепить «' + name + '» от этого раздела?')) return;

        $wrap.addClass('detaching');
        $btn.addClass('loading');

        $.post(ajaxurl, {
            action:    'institute_detach_prepod',
            prepod_id: prepodId,
            nonce:     instituteAdmin.detachNonce
        }, function(resp) {
            if (resp.success) {
                $wrap.slideUp(250, function() {
                    $wrap.remove();
                    // Обновляем счётчик в табе
                    var $grid  = $('.imb-tab-panel[data-panel="prepods"] .imb-prepods-grid');
                    var count  = $grid.find('.imb-prepod-wrap').length;
                    var $count = $('.imb-tab-btn[data-tab="prepods"] .imb-tab-count');
                    $count.text(count);
                    if (count === 0) {
                        $grid.closest('.imb-tab-panel').find('.imb-prepods-grid').replaceWith(
                            '<p style="color:#9ca3af;font-size:13px;">Нет привязанных преподавателей.</p>'
                        );
                    }
                });
            } else {
                alert('Ошибка: ' + (resp.data || 'не удалось открепить'));
                $wrap.removeClass('detaching');
                $btn.removeClass('loading');
            }
        });
    });

    // ── Загрузка фото (универсальная) ─────────────────────────────────────
    var mediaUploaders = {};

    $(document).on('click', '[id$="_upload_btn"]', function(e) {
        e.preventDefault();
        var btnId     = $(this).attr('id');
        var fieldId   = btnId.replace('_upload_btn', '_photo_id').replace('upload_btn', 'photo_id');
        var previewId = btnId.replace('_upload_btn', '-photo-preview').replace('upload_btn', '-photo-preview');
        var removeBtnId = btnId.replace('_upload_btn', '_remove_btn').replace('upload_btn', '_remove_btn');

        if (mediaUploaders[btnId]) {
            mediaUploaders[btnId].open();
            return;
        }

        mediaUploaders[btnId] = wp.media({
            title:    'Выбрать фото',
            button:   { text: 'Выбрать' },
            multiple: false
        });

        mediaUploaders[btnId].on('select', function() {
            var att = mediaUploaders[btnId].state().get('selection').first().toJSON();
            var src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
            $('#' + fieldId).val(att.id);
            $('#' + previewId).html('<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;display:block;">');
            $('#' + removeBtnId).show();
        });

        mediaUploaders[btnId].open();
    });

    $(document).on('click', '[id$="_remove_btn"]', function() {
        var btnId       = $(this).attr('id');
        var fieldId     = btnId.replace('_remove_btn', '_photo_id');
        var previewId   = btnId.replace('_remove_btn', '-photo-preview');

        $('#' + fieldId).val('');
        $('#' + previewId).html(
            '<div class="imb-photo-empty">' +
            '<span class="dashicons dashicons-admin-users"></span>' +
            '<span>Фото не выбрано</span>' +
            '</div>'
        );
        $(this).hide();
    });

    // ── Добавление публикации (универсальная) ─────────────────────────────
    $(document).on('click', '[id^="add-pub-btn-"]', function() {
        var prefix    = $(this).attr('id').replace('add-pub-btn-', '');
        var container = $('#' + prefix + '-pubs-container');
        if (!container.length) return;

        var tpl =
            '<div class="pub-item">' +
            '<div class="pub-item-row">' +
            '<input type="text" name="' + prefix + '_pub_title[]" placeholder="Название статьи / работы" class="widefat">' +
            '</div>' +
            '<div class="pub-item-row">' +
            '<input type="text" name="' + prefix + '_pub_journal[]" placeholder="Журнал / сборник / конференция" class="widefat">' +
            '</div>' +
            '<div class="pub-item-row pub-item-meta">' +
            '<input type="text" name="' + prefix + '_pub_year[]" placeholder="Год" style="width:90px;">' +
            '<input type="url" name="' + prefix + '_pub_link[]" placeholder="DOI / ссылка" style="flex:1;">' +
            '<button type="button" class="button remove-pub">' +
            '<span class="dashicons dashicons-trash" style="margin-top:3px;font-size:13px;"></span> Удалить' +
            '</button>' +
            '</div>' +
            '</div>';

        container.append(tpl);
    });

    // ── Удаление публикации ────────────────────────────────────────────────
    $(document).on('click', '.remove-pub', function() {
        $(this).closest('.pub-item').remove();
    });
});
