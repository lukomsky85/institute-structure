<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();

$output = '<div class="institute-single-content institute-kafedra-single">';

// ── Шапка кафедры: фото + ключевые поля ────────────────────────────────────
$zav_photo_id  = get_post_meta($post_id, '_kafedra_zav_photo_id', true);
$zaveduyushchiy = get_post_meta($post_id, '_kafedra_zaveduyushchiy', true);
$email          = get_post_meta($post_id, '_kafedra_email', true);
$phone          = get_post_meta($post_id, '_kafedra_phone', true);
$auditoria      = get_post_meta($post_id, '_kafedra_auditoria', true);
$site           = get_post_meta($post_id, '_kafedra_site', true);
$fakultet_id    = get_post_meta($post_id, '_kafedra_fakultet', true);

$output .= '<div class="kafedra-header-block">';

if ($zav_photo_id) {
    $output .= '<div class="kafedra-header-photo">';
    $output .= wp_get_attachment_image($zav_photo_id, 'medium', false, ['class' => 'zav-photo']);
    $output .= '<div class="kafedra-photo-caption">Заведующий кафедрой</div>';
    $output .= '</div>';
}

$output .= '<div class="kafedra-header-fields">';

if ($fakultet_id) {
    $fak_title = get_the_title($fakultet_id);
    $output .= '<div class="institute-field">';
    $output .= '<strong>Факультет</strong>';
    $output .= '<a href="' . get_permalink($fakultet_id) . '">' . esc_html($fak_title) . '</a>';
    $output .= '</div>';
}
if ($zaveduyushchiy) {
    $output .= '<div class="institute-field">';
    $output .= '<strong>Заведующий</strong>';
    $output .= '<span>' . esc_html($zaveduyushchiy) . '</span>';
    $output .= '</div>';
}
if ($email) {
    $output .= '<div class="institute-field">';
    $output .= '<strong>Email</strong>';
    $output .= '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    $output .= '</div>';
}
if ($phone) {
    $output .= '<div class="institute-field">';
    $output .= '<strong>Телефон</strong>';
    $output .= '<span>' . esc_html($phone) . '</span>';
    $output .= '</div>';
}
if ($auditoria) {
    $output .= '<div class="institute-field">';
    $output .= '<strong>Аудитория</strong>';
    $output .= '<span>' . esc_html($auditoria) . '</span>';
    $output .= '</div>';
}
if ($site) {
    $output .= '<div class="institute-field">';
    $output .= '<strong>Сайт</strong>';
    $output .= '<a href="' . esc_url($site) . '" target="_blank">' . esc_html($site) . '</a>';
    $output .= '</div>';
}

$output .= '</div>'; // /kafedra-header-fields
$output .= '</div>'; // /kafedra-header-block

// ── Собираем данные для вкладок ─────────────────────────────────────────────
$opisanie = get_post_meta($post_id, '_kafedra_opisanie', true);
$nmr      = get_post_meta($post_id, '_kafedra_nmr', true);
$nrs      = get_post_meta($post_id, '_kafedra_nrs', true);
$uvr      = get_post_meta($post_id, '_kafedra_uvr', true);
$pubs     = get_post_meta($post_id, '_kafedra_publications', true);

$prepodavateli = get_posts([
    'post_type'      => 'prepodavatel',
    'meta_key'       => '_prepodavatel_pripisann_k',
    'meta_value'     => 'kafedra_' . $post_id,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$specialnosti = get_posts([
    'post_type'      => 'specialnost',
    'meta_key'       => '_specialnost_pripisann_k',
    'meta_value'     => 'kafedra_' . $post_id,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// Определяем какие вкладки показывать
$has_opisanie = !empty($opisanie);
$has_nmr      = !empty($nmr);
$has_nrs      = !empty($nrs);
$has_uvr      = !empty($uvr);
$has_info     = $has_opisanie;   // «О кафедре» = только общее описание
$has_prepod   = !empty($prepodavateli);
$has_spec     = !empty($specialnosti);
$has_pubs     = !empty($pubs) && is_array($pubs);

$unique_id = 'kafedra-tabs-' . $post_id;

// ── Вкладки ─────────────────────────────────────────────────────────────────
$output .= '<div class="kafedra-tabs" id="' . $unique_id . '">';
$output .= '<div class="kafedra-tabs-nav" role="tablist">';

$tabs = [];
if ($has_info)   $tabs[] = ['id' => 'info',   'label' => 'О кафедре'];
if ($has_nmr)    $tabs[] = ['id' => 'nmr',    'label' => 'НМР'];
if ($has_nrs)    $tabs[] = ['id' => 'nrs',    'label' => 'Работа студентов'];
if ($has_uvr)    $tabs[] = ['id' => 'uvr',    'label' => 'УВР'];
if ($has_prepod) $tabs[] = ['id' => 'prepod', 'label' => 'Преподаватели <span class="tab-count">' . count($prepodavateli) . '</span>'];
if ($has_spec)   $tabs[] = ['id' => 'spec',   'label' => 'Специальности <span class="tab-count">' . count($specialnosti) . '</span>'];
if ($has_pubs)   $tabs[] = ['id' => 'pubs',   'label' => 'Публикации'];

if (empty($tabs)) {
    $output .= '</div></div>';
    $output .= '</div>';
    echo $output;
    return;
}

foreach ($tabs as $i => $tab) {
    $active = $i === 0 ? 'active' : '';
    $output .= '<button type="button" class="kafedra-tab-btn ' . $active . '" '
             . 'role="tab" data-tab="' . esc_attr($tab['id']) . '">'
             . $tab['label'] . '</button>';
}

$output .= '</div>'; // /kafedra-tabs-nav

// ── Панели вкладок ───────────────────────────────────────────────────────────

// 1. О кафедре (только opisanie)
if ($has_info) {
    $first = $tabs[0]['id'] === 'info';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="info">';
    $output .= '<div class="kafedra-panel-section">';
    $output .= apply_filters('the_content', $opisanie);
    $output .= '</div>';
    $output .= '</div>';
}

// 2. НМР
if ($has_nmr) {
    $first = $tabs[0]['id'] === 'nmr';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="nmr">';
    $output .= '<div class="kafedra-panel-section">';
    $output .= apply_filters('the_content', $nmr);
    $output .= '</div>';
    $output .= '</div>';
}

// 3. НРС
if ($has_nrs) {
    $first = $tabs[0]['id'] === 'nrs';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="nrs">';
    $output .= '<div class="kafedra-panel-section">';
    $output .= apply_filters('the_content', $nrs);
    $output .= '</div>';
    $output .= '</div>';
}

// 4. УВР
if ($has_uvr) {
    $first = $tabs[0]['id'] === 'uvr';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="uvr">';
    $output .= '<div class="kafedra-panel-section">';
    $output .= apply_filters('the_content', $uvr);
    $output .= '</div>';
    $output .= '</div>';
}

// 5. Преподаватели
if ($has_prepod) {
    $first = $tabs[0]['id'] === 'prepod';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="prepod">';
    $output .= '<div class="institute-prepod-grid">';

    foreach ($prepodavateli as $prepod) {
        $photo_id  = get_post_meta($prepod->ID, '_prepodavatel_photo_id', true);
        $dolzhnost = get_post_meta($prepod->ID, '_prepodavatel_dolzhnost', true);
        $stepen    = get_post_meta($prepod->ID, '_prepodavatel_stepen', true);
        $url       = get_permalink($prepod->ID);

        $output .= '<a href="' . esc_url($url) . '" class="prepod-card">';
        $output .= '<div class="prepod-card-photo">';
        if ($photo_id) {
            $output .= wp_get_attachment_image($photo_id, 'medium', false, ['class' => 'prepod-card-img']);
        } else {
            $output .= '<div class="prepod-card-no-photo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>';
        }
        $output .= '</div>';
        $output .= '<div class="prepod-card-info">';
        $output .= '<div class="prepod-card-name">' . esc_html($prepod->post_title) . '</div>';
        if ($dolzhnost) $output .= '<div class="prepod-card-dolzhnost">' . esc_html($dolzhnost) . '</div>';
        if ($stepen)    $output .= '<div class="prepod-card-stepen">' . esc_html($stepen) . '</div>';
        $output .= '</div>';
        $output .= '</a>';
    }

    $output .= '</div>';
    $output .= '</div>'; // /panel prepod
}

// 6. Специальности
if ($has_spec) {
    $first = $tabs[0]['id'] === 'spec';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="spec">';
    $output .= '<div class="kafedra-spec-list">';
    foreach ($specialnosti as $spec) {
        $kod = get_post_meta($spec->ID, '_specialnost_kod', true);
        $output .= '<a href="' . get_permalink($spec->ID) . '" class="kafedra-spec-item">';
        if ($kod) {
            $output .= '<span class="spec-kod">' . esc_html($kod) . '</span>';
        }
        $output .= '<span class="spec-title">' . esc_html($spec->post_title) . '</span>';
        $output .= '<svg class="spec-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 18l6-6-6-6"/></svg>';
        $output .= '</a>';
    }
    $output .= '</div>';
    $output .= '</div>'; // /panel spec
}

// 7. Публикации
if ($has_pubs) {
    $first = $tabs[0]['id'] === 'pubs';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="pubs">';
    $output .= '<div class="institute-publications kafedra-pubs-list">';
    $output .= '<ul>';
    foreach ($pubs as $pub) {
        $output .= '<li>';
        $output .= '<strong>' . esc_html($pub['title']) . '</strong>';
        $output .= '<span class="pub-journal">' . esc_html($pub['journal']);
        if (!empty($pub['year'])) $output .= ', ' . esc_html($pub['year']);
        $output .= '</span>';
        if (!empty($pub['link'])) {
            $output .= '<a href="' . esc_url($pub['link']) . '" class="pub-link" target="_blank">Открыть →</a>';
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    $output .= '</div>';
    $output .= '</div>'; // /panel pubs
}

$output .= '</div>'; // /kafedra-tabs

// ── JS переключения вкладок ─────────────────────────────────────────────────
$output .= '
<script>
(function() {
    var root = document.getElementById(' . json_encode($unique_id) . ');
    if (!root) return;
    var btns   = root.querySelectorAll(".kafedra-tab-btn");
    var panels = root.querySelectorAll(".kafedra-tab-panel");
    btns.forEach(function(btn) {
        btn.addEventListener("click", function() {
            var tab = this.dataset.tab;
            btns.forEach(function(b) { b.classList.remove("active"); });
            panels.forEach(function(p) { p.classList.remove("active"); });
            this.classList.add("active");
            var panel = root.querySelector(".kafedra-tab-panel[data-panel=\"" + tab + "\"]");
            if (panel) panel.classList.add("active");
        });
    });
})();
</script>';

$output .= '</div>'; // /institute-single-content
echo $output;
?>
