<?php
if (!defined('ABSPATH')) exit;

$post_id = get_the_ID();

$output = '<div class="institute-single-content institute-kafedra-single">';

// ── Шапка: фото + поля ──────────────────────────────────────────────────────
$zav_photo_id = get_post_meta($post_id, '_srednee_zav_photo_id', true);

$output .= '<div class="kafedra-header-block">';

if ($zav_photo_id) {
    $output .= '<div class="kafedra-header-photo">';
    $output .= wp_get_attachment_image($zav_photo_id, 'medium', false, ['class' => 'zav-photo']);
    $output .= '<div class="kafedra-photo-caption">Заведующий отделением</div>';
    $output .= '</div>';
}

$output .= '<div class="kafedra-header-fields">';

$fields = [
    'zaveduyushchiy' => 'Заведующий',
    'email'          => 'Email',
    'phone'          => 'Телефон',
    'auditoria'      => 'Аудитория',
    'site'           => 'Сайт',
];

foreach ($fields as $key => $label) {
    $value = get_post_meta($post_id, '_srednee_' . $key, true);
    if (!$value) continue;
    $output .= '<div class="institute-field">';
    $output .= '<strong>' . esc_html($label) . '</strong>';
    if ($key === 'email') {
        $output .= '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
    } elseif ($key === 'site') {
        $output .= '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
    } else {
        $output .= '<span>' . esc_html($value) . '</span>';
    }
    $output .= '</div>';
}

$output .= '</div>'; // /kafedra-header-fields
$output .= '</div>'; // /kafedra-header-block

// ── Данные для вкладок ──────────────────────────────────────────────────────
$opisanie = get_post_meta($post_id, '_srednee_opisanie', true);
$pubs     = get_post_meta($post_id, '_srednee_publications', true);

$specialnosti = get_posts([
    'post_type'      => 'specialnost',
    'meta_key'       => '_specialnost_pripisann_k',
    'meta_value'     => 'srednee_' . $post_id,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$prepodavateli = get_posts([
    'post_type'      => 'prepodavatel',
    'meta_key'       => '_prepodavatel_pripisann_k',
    'meta_value'     => 'srednee_' . $post_id,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$has_info   = !empty($opisanie);
$has_spec   = !empty($specialnosti);
$has_prepod = !empty($prepodavateli);
$has_pubs   = !empty($pubs) && is_array($pubs);

$unique_id = 'srednee-tabs-' . $post_id;

// ── Вкладки ─────────────────────────────────────────────────────────────────
$output .= '<div class="kafedra-tabs" id="' . $unique_id . '">';
$output .= '<div class="kafedra-tabs-nav" role="tablist">';

$tabs = [];
if ($has_info)   $tabs[] = ['id' => 'info',   'label' => 'Об отделении'];
if ($has_spec)   $tabs[] = ['id' => 'spec',   'label' => 'Специальности <span class="tab-count">' . count($specialnosti) . '</span>'];
if ($has_prepod) $tabs[] = ['id' => 'prepod', 'label' => 'Преподаватели <span class="tab-count">' . count($prepodavateli) . '</span>'];
if ($has_pubs)   $tabs[] = ['id' => 'pubs',   'label' => 'Публикации'];

if (empty($tabs)) {
    $output .= '</div></div></div>';
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

// ── Панели ──────────────────────────────────────────────────────────────────

// 1. Об отделении
if ($has_info) {
    $first = $tabs[0]['id'] === 'info';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="info">';
    $output .= '<div class="kafedra-panel-section">';
    $output .= apply_filters('the_content', $opisanie);
    $output .= '</div>';
    $output .= '</div>';
}

// 2. Специальности
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
    $output .= '</div>';
}

// 3. Преподаватели
if ($has_prepod) {
    $first = $tabs[0]['id'] === 'prepod';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="prepod">';
    $output .= '<div class="institute-prepod-grid">';
    foreach ($prepodavateli as $prepod) {
        $photo_id  = get_post_meta($prepod->ID, '_prepodavatel_photo_id', true);
        $dolzhnost = get_post_meta($prepod->ID, '_prepodavatel_dolzhnost', true);
        $stepen    = get_post_meta($prepod->ID, '_prepodavatel_stepen', true);
        $output .= '<a href="' . esc_url(get_permalink($prepod->ID)) . '" class="prepod-card">';
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
    $output .= '</div>';
}

// 4. Публикации
if ($has_pubs) {
    $first = $tabs[0]['id'] === 'pubs';
    $output .= '<div class="kafedra-tab-panel ' . ($first ? 'active' : '') . '" data-panel="pubs">';
    $output .= '<div class="institute-publications kafedra-pubs-list"><ul>';
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
    $output .= '</ul></div>';
    $output .= '</div>';
}

$output .= '</div>'; // /kafedra-tabs

// ── JS ───────────────────────────────────────────────────────────────────────
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
