<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Рендеринг расписания (календарный вид)
 */
function institute_render_detailed_rasp($rasp_data, $prepod_name) {
    if (empty($rasp_data)) {
        return '<div class="institute-empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3>Расписание временно недоступно</h3>
                    <p>Попробуйте обновить страницу позже</p>
                </div>';
    }

    // Группируем по датам
    $grouped = [];
    foreach ($rasp_data as $lesson) {
        $date_key = date('Y-m-d', strtotime($lesson['дата'] ?? ''));
        if (!isset($grouped[$date_key])) {
            $grouped[$date_key] = [];
        }
        $grouped[$date_key][] = $lesson;
    }

    ksort($grouped);

    $output = '<div class="institute-rasp-calendar">';
    
    // Заголовок
    $output .= '<div class="rasp-header">';
    $output .= '<div class="lessons-count">' . count($rasp_data) . ' занятий</div>';
    $output .= '</div>';

    // Календарь дней
    $output .= '<div class="rasp-calendar-nav">';
    $output .= '<div class="calendar-days">';
    
    $dates = array_keys($grouped);
    $first_date = reset($dates);
    $active_date = $first_date; // По умолчанию первый день активен
    
    foreach ($dates as $date) {
        $timestamp = strtotime($date);
        $day_num = date('j', $timestamp);
        $weekday_short = mb_substr(institute_get_weekday_rus(date('w', $timestamp)), 0, 2);
        $is_today = date('Y-m-d') === $date;
        $is_active = $date === $active_date;
        
        $classes = ['calendar-day'];
        if ($is_today) $classes[] = 'today';
        if ($is_active) $classes[] = 'active';
        
        $output .= '<button type="button" 
                    class="' . implode(' ', $classes) . '" 
                    data-date="' . esc_attr($date) . '"
                    data-target="day-' . esc_attr(str_replace('-', '', $date)) . '">';
        $output .= '<span class="day-number">' . $day_num . '</span>';
        $output .= '<span class="day-week">' . $weekday_short . '</span>';
        $output .= '</button>';
    }
    
    $output .= '</div></div>';

    // Содержимое расписания по дням
    $output .= '<div class="rasp-days-content">';
    
    foreach ($grouped as $date => $lessons) {
        $timestamp = strtotime($date);
        $day = date('j', $timestamp);
        $month = institute_get_month_rus(date('n', $timestamp));
        $year = date('Y', $timestamp);
        $weekday = institute_get_weekday_rus(date('w', $timestamp));
        $is_active = $date === $active_date;
        
        $content_classes = ['rasp-day-content'];
        if ($is_active) $content_classes[] = 'active';
        
        $output .= '<div class="' . implode(' ', $content_classes) . '" 
                    id="day-' . esc_attr(str_replace('-', '', $date)) . '" 
                    data-date="' . esc_attr($date) . '">';
        $output .= '<div class="day-header">';
        $output .= '<h3>' . esc_html($weekday) . ', ' . esc_html($day) . ' ' . esc_html($month) . ' ' . esc_html($year) . ' г.</h3>';
        $output .= '</div>';
        $output .= '<div class="day-lessons">';
        
        foreach ($lessons as $lesson) {
            $time_start = esc_html($lesson['начало'] ?? '');
            $time_end = esc_html($lesson['конец'] ?? '');
            $subject = esc_html($lesson['дисциплина'] ?? '—');
            $type = esc_html($lesson['видЗанятия'] ?? '');
            $room = esc_html($lesson['аудитория'] ?? '—');
            $group = esc_html($lesson['source_group'] ?? '');
            
            $type_data = institute_get_lesson_type_data($type);
            
            $output .= '<div class="lesson-card">';
            $output .= '<div class="lesson-time">';
            $output .= '<div class="time-start">' . $time_start . '</div>';
            $output .= '<div class="time-divider"></div>';
            $output .= '<div class="time-end">' . $time_end . '</div>';
            $output .= '</div>';
            
            $output .= '<div class="lesson-content">';
            $output .= '<div class="lesson-main">';
            $output .= '<div class="lesson-icon" style="background:' . $type_data['bg'] . ';color:' . $type_data['color'] . ';border-color:' . $type_data['bg'] . '">';
            $output .= $type_data['icon'];
            $output .= '</div>';
            $output .= '<div class="lesson-details">';
            $output .= '<h4 class="lesson-subject">' . $subject . '</h4>';
            $output .= '<div class="lesson-type-badge" style="background:' . $type_data['bg'] . ';color:' . $type_data['color'] . '">' . $type . '</div>';
            $output .= '</div>';
            $output .= '</div>';
            
            $output .= '<div class="lesson-meta">';
            if ($room !== '—') {
                $output .= '<div class="meta-item room">';
                $output .= '<span class="meta-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>';
                $output .= '<span class="meta-text">Ауд. ' . $room . '</span>';
                $output .= '</div>';
            }
            if ($group) {
                $output .= '<div class="meta-item group">';
                $output .= '<span class="meta-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>';
                $output .= '<span class="meta-text">Гр. ' . $group . '</span>';
                $output .= '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div></div>';
    }
    
    $output .= '</div></div>';
    
    return $output . institute_rasp_calendar_css() . institute_rasp_calendar_js();
}

/**
 * Компактный вид с русскими месяцами
 */
function institute_render_compact_rasp($rasp_data, $prepod_name) {
    if (empty($rasp_data)) {
        return '<div class="institute-compact-empty">
                    <div class="empty-icon">📚</div>
                    <p>Ближайших занятий нет</p>
                </div>';
    }

    usort($rasp_data, function($a, $b) {
        return strtotime($a['дата'] . ' ' . $a['начало']) <=> strtotime($b['дата'] . ' ' . $b['начало']);
    });

    $next_lessons = array_slice($rasp_data, 0, 5);

    $output = '<div class="institute-rasp-compact">';
    $output .= '<div class="compact-header">';
    $output .= '<h3>Ближайшие занятия</h3>';
    $output .= '<div class="prepod-compact">' . esc_html($prepod_name) . '</div>';
    $output .= '</div>';
    
    $output .= '<div class="compact-lessons">';
    
    foreach ($next_lessons as $lesson) {
        $timestamp = strtotime($lesson['дата']);
        $date = date('j', $timestamp);
        $month_short = institute_get_month_short_rus(date('n', $timestamp));
        $weekday = mb_substr(institute_get_weekday_rus(date('w', $timestamp)), 0, 3);
        $time = $lesson['начало'] ?? '';
        $subject = $lesson['дисциплина'] ?? '—';
        $room = $lesson['аудитория'] ?? '';
        $type = $lesson['видЗанятия'] ?? '';
        
        $is_today = date('Y-m-d') === date('Y-m-d', $timestamp);
        $type_data = institute_get_lesson_type_data($type);
        
        $output .= '<div class="compact-lesson ' . ($is_today ? 'today-lesson' : '') . '">';
        $output .= '<div class="lesson-date">';
        $output .= '<div class="date-day">' . $date . '</div>';
        $output .= '<div class="date-month">' . $month_short . '</div>';
        $output .= '<div class="date-weekday">' . $weekday . '</div>';
        $output .= '</div>';
        
        $output .= '<div class="lesson-main-compact">';
        $output .= '<div class="lesson-time-compact">' . $time . '</div>';
        $output .= '<h4 class="lesson-subject-compact">' . esc_html($subject) . '</h4>';
        if ($room && $room !== '—') {
            $output .= '<div class="lesson-room-compact">';
            $output .= '<span class="room-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>';
            $output .= '<span>ауд. ' . esc_html($room) . '</span>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        $output .= '<div class="lesson-type-compact" style="background:' . $type_data['bg'] . ';color:' . $type_data['color'] . '">';
        $output .= $type_data['icon'];
        $output .= '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '<div class="compact-footer">';
    $output .= '<a href="#" class="view-full-link">Посмотреть полное расписание →</a>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output . institute_compact_rasp_css();
}

// === ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ===

/**
 * Получить день недели по-русски
 */
function institute_get_weekday_rus($num) {
    $days = [
        0 => 'воскресенье',
        1 => 'понедельник',
        2 => 'вторник',
        3 => 'среда',
        4 => 'четверг',
        5 => 'пятница',
        6 => 'суббота'
    ];
    return $days[$num] ?? '';
}

/**
 * Получить название месяца по-русски (полное)
 */
function institute_get_month_rus($num) {
    $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря'
    ];
    return $months[$num] ?? '';
}

/**
 * Получить название месяца по-русски (сокращенное)
 */
function institute_get_month_short_rus($num) {
    $months = [
        1 => 'янв',
        2 => 'фев',
        3 => 'мар',
        4 => 'апр',
        5 => 'мая',
        6 => 'июн',
        7 => 'июл',
        8 => 'авг',
        9 => 'сен',
        10 => 'окт',
        11 => 'ноя',
        12 => 'дек'
    ];
    return $months[$num] ?? '';
}

/**
 * Получить данные типа занятия (SVG иконка + цвет + аббревиатура)
 */
function institute_get_lesson_type_data($type) {
    $type_lower = mb_strtolower(trim($type), 'UTF-8');

    // SVG иконки для каждого типа занятия
    $icon_lecture = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>';
    $icon_practice = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';
    $icon_lab = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M14 2v6l3 9H7l3-9V2"/><path d="M6 2h12"/><path d="M7.5 16h9"/></svg>';
    $icon_consult = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
    $icon_exam = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>';
    $icon_zachet = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
    $icon_default = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>';

    if (strpos($type_lower, 'лекция') !== false || strpos($type_lower, 'лек') !== false) {
        return ['icon' => $icon_lecture, 'color' => '#1a56db', 'bg' => '#eff6ff', 'abbr' => 'лк', 'name' => 'Лекция'];
    }
    if (strpos($type_lower, 'практика') !== false || strpos($type_lower, 'пр') !== false) {
        return ['icon' => $icon_practice, 'color' => '#0e9f6e', 'bg' => '#f0fdf4', 'abbr' => 'пр', 'name' => 'Практика'];
    }
    if (strpos($type_lower, 'лаборатор') !== false || strpos($type_lower, 'лаб') !== false) {
        return ['icon' => $icon_lab, 'color' => '#7e3af2', 'bg' => '#faf5ff', 'abbr' => 'лб', 'name' => 'Лабораторная'];
    }
    if (strpos($type_lower, 'консультация') !== false) {
        return ['icon' => $icon_consult, 'color' => '#d97706', 'bg' => '#fffbeb', 'abbr' => 'кн', 'name' => 'Консультация'];
    }
    if (strpos($type_lower, 'экзамен') !== false) {
        return ['icon' => $icon_exam, 'color' => '#e02424', 'bg' => '#fef2f2', 'abbr' => 'эк', 'name' => 'Экзамен'];
    }
    if (strpos($type_lower, 'зачет') !== false || strpos($type_lower, 'зачёт') !== false) {
        return ['icon' => $icon_zachet, 'color' => '#0e9f6e', 'bg' => '#f0fdf4', 'abbr' => 'зч', 'name' => 'Зачёт'];
    }

    return ['icon' => $icon_default, 'color' => '#374151', 'bg' => '#f9fafb', 'abbr' => 'зн', 'name' => 'Занятие'];
}

// === CSS СТИЛИ ===

/**
 * Стили для календарного расписания — улучшенная версия
 */
function institute_rasp_calendar_css() {
    ob_start();
    ?>
    <style>
    .institute-rasp-calendar {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: #ffffff;
        color: #111827;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.06);
        margin: 32px 0;
        border: 1px solid #e5e7eb;
        max-width: 100%;
    }

    /* Шапка */
    .rasp-header {
        padding: 18px 24px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rasp-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }

    .header-icon { font-size: 20px; }

    .prepod-name {
        font-size: 14px;
        color: #6b7280;
        font-weight: 400;
        margin-top: 2px;
    }

    .lessons-count {
        background: #111827;
        color: #ffffff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    /* Навигация по дням */
    .rasp-calendar-nav {
        background: #ffffff;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: #d1d5db transparent;
    }

    .rasp-calendar-nav::-webkit-scrollbar {
        height: 4px;
    }
    .rasp-calendar-nav::-webkit-scrollbar-track { background: transparent; }
    .rasp-calendar-nav::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

    .calendar-days {
        display: flex;
        gap: 6px;
        min-width: max-content;
    }

    .calendar-day {
        background: #f9fafb;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 14px;
        cursor: pointer;
        transition: all 0.18s ease;
        min-width: 56px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
    }

    .calendar-day:hover {
        border-color: #9ca3af;
        background: #f3f4f6;
        transform: translateY(-1px);
    }

    .calendar-day.active {
        background: #111827;
        border-color: #111827;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(17,24,39,0.25);
    }

    .calendar-day.today {
        border-color: #3b82f6;
    }

    .calendar-day.today .day-number,
    .calendar-day.today .day-week { color: #3b82f6; }

    .calendar-day.today.active .day-number,
    .calendar-day.today.active .day-week { color: #ffffff; }

    .day-number {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }

    .day-week {
        font-size: 10px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .calendar-day.active .day-number,
    .calendar-day.active .day-week { color: #ffffff; }

    /* Контент дня */
    .rasp-days-content {
        min-height: 200px;
    }

    .rasp-day-content {
        display: none;
        padding: 24px;
    }

    .rasp-day-content.active {
        display: block;
        animation: fadeSlideIn 0.25s ease;
    }

    .day-header {
        margin-bottom: 20px;
    }

    .day-header h3 {
        margin: 0;
        color: #111827;
        font-size: 17px;
        font-weight: 700;
        padding-bottom: 14px;
        border-bottom: 2px solid #f3f4f6;
        text-transform: capitalize;
    }

    .day-lessons {
        display: grid;
        gap: 10px;
    }

    /* Карточка занятия */
    .lesson-card {
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 0;
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .lesson-card:hover {
        border-color: #d1d5db;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .lesson-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #111827;
        border-radius: 0;
    }

    /* Колонка времени */
    .lesson-time {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px 12px;
        gap: 4px;
        background: #f9fafb;
        border-right: 1px solid #f0f0f0;
    }

    .time-start {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.3px;
    }

    .time-divider {
        width: 20px;
        height: 1px;
        background: #d1d5db;
        margin: 1px 0;
    }

    .time-end {
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        letter-spacing: -0.2px;
    }

    /* Основной контент */
    .lesson-content {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .lesson-main {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .lesson-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid transparent;
    }

    .lesson-icon svg {
        display: block;
    }

    .lesson-details {
        flex: 1;
        min-width: 0;
    }

    .lesson-subject {
        margin: 0 0 6px 0;
        font-size: 15px;
        font-weight: 600;
        color: #111827;
        line-height: 1.35;
    }

    .lesson-type-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Мета-информация */
    .lesson-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: #f9fafb;
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid #f0f0f0;
        color: #374151;
    }

    .meta-icon {
        display: flex;
        align-items: center;
        color: #6b7280;
    }

    .meta-text {
        font-weight: 500;
    }

    /* Анимации */
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Пустое состояние */
    .institute-empty-state {
        text-align: center;
        padding: 48px 24px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        margin: 32px 0;
    }

    .empty-state-icon {
        font-size: 44px;
        margin-bottom: 14px;
        opacity: 0.4;
    }

    .institute-empty-state h3 {
        color: #111827;
        margin-bottom: 6px;
        font-size: 17px;
        font-weight: 600;
    }

    .institute-empty-state p {
        color: #6b7280;
        margin: 0;
        font-size: 14px;
    }

    /* Адаптивность */
    @media (max-width: 600px) {
        .rasp-day-content { padding: 16px; }
        .rasp-header { padding: 14px 16px; flex-wrap: wrap; }
        .rasp-calendar-nav { padding: 10px 14px; }

        .lesson-card {
            grid-template-columns: 70px 1fr;
        }

        .time-start { font-size: 13px; }
        .time-end   { font-size: 12px; }

        .lesson-content { padding: 12px; }
        .lesson-subject { font-size: 14px; }
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * JavaScript для переключения вкладок
 */
function institute_rasp_calendar_js() {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarContainer = document.querySelector('.institute-rasp-calendar');
        if (!calendarContainer) return;
        
        const dayButtons = calendarContainer.querySelectorAll('.calendar-day');
        const dayContents = calendarContainer.querySelectorAll('.rasp-day-content');
        
        dayButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                
                // Убираем активный класс со всех кнопок
                dayButtons.forEach(btn => btn.classList.remove('active'));
                // Добавляем активный класс текущей кнопке
                this.classList.add('active');
                
                // Скрываем все содержимое
                dayContents.forEach(content => content.classList.remove('active'));
                // Показываем нужное содержимое
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// === СТИЛИ И СКРИПТЫ ДЛЯ КОМПАКТНОГО ВИДА (БЕЛО-ЧЕРНЫЙ МИНИМАЛИЗМ) ===

function institute_compact_rasp_css() {
    ob_start();
    ?>
    <style>
    .institute-rasp-compact {
        background: #ffffff;
        color: #111827;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.06);
        transition: box-shadow 0.25s ease;
        margin: 28px 0;
        border: 1px solid #e5e7eb;
    }

    .institute-rasp-compact:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 8px 24px rgba(0,0,0,0.08);
    }

    .compact-header {
        padding: 16px 20px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    .compact-header h3 {
        margin: 0 0 4px 0;
        color: #111827;
        font-size: 16px;
        font-weight: 700;
    }

    .prepod-compact {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
    }

    .compact-lessons {
        padding: 12px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .compact-lesson {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid #f0f0f0;
        transition: all 0.18s ease;
        position: relative;
        overflow: hidden;
    }

    .compact-lesson:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        transform: translateX(2px);
    }

    .compact-lesson.today-lesson {
        border: 1.5px solid #3b82f6;
        background: #eff6ff;
    }

    .lesson-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 52px;
        padding: 6px 8px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .date-day {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }

    .date-month {
        font-size: 10px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        margin: 2px 0;
        letter-spacing: 0.4px;
    }

    .date-weekday {
        font-size: 10px;
        color: #9ca3af;
        font-weight: 500;
    }

    .lesson-main-compact {
        flex: 1;
        min-width: 0;
    }

    .lesson-time-compact {
        font-size: 12px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 3px;
        letter-spacing: -0.2px;
    }

    .lesson-subject-compact {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lesson-room-compact {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: #6b7280;
    }

    .room-icon {
        display: flex;
        align-items: center;
        color: #9ca3af;
    }

    .lesson-type-compact {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lesson-type-compact svg {
        display: block;
    }

    .compact-footer {
        padding: 10px 16px 14px;
        text-align: center;
        border-top: 1px solid #f0f0f0;
    }

    .view-full-link {
        display: inline-block;
        color: #111827;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 6px 16px;
        border-radius: 8px;
        transition: all 0.18s ease;
        border: 1.5px solid #e5e7eb;
    }

    .view-full-link:hover {
        background: #f3f4f6;
        color: #111827;
        text-decoration: none;
        border-color: #9ca3af;
    }

    .institute-compact-empty {
        text-align: center;
        padding: 32px 20px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .institute-compact-empty .empty-icon {
        font-size: 36px;
        opacity: 0.4;
        margin-bottom: 10px;
    }

    .institute-compact-empty p {
        color: #6b7280;
        margin: 0;
        font-size: 14px;
    }

    @media (max-width: 600px) {
        .institute-rasp-compact { margin: 16px 0; }

        .compact-lesson {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .lesson-date {
            flex-direction: row;
            gap: 10px;
            min-width: auto;
            width: 100%;
        }

        .lesson-main-compact { width: 100%; }

        .lesson-subject-compact { white-space: normal; }

        .lesson-type-compact {
            position: absolute;
            right: 12px;
            top: 12px;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .compact-lesson {
        animation: fadeIn 0.3s ease backwards;
    }

    .compact-lesson:nth-child(1) { animation-delay: 0.05s; }
    .compact-lesson:nth-child(2) { animation-delay: 0.1s; }
    .compact-lesson:nth-child(3) { animation-delay: 0.15s; }
    .compact-lesson:nth-child(4) { animation-delay: 0.2s; }
    .compact-lesson:nth-child(5) { animation-delay: 0.25s; }
    </style>
    <?php
    return ob_get_clean();
}