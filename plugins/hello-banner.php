<?php
/**
 * Plugin Name: Приветственный баннер
 * Description: Демонстрационный плагин. Добавляет информационный баннер в шапку сайта и подпись «Сделано на SiteForge» в подвал через хуки ядра.
 * Version: 1.0
 * sf_plugin: hello-banner
 */

// Выводим баннер сразу после открытия <body> (хук get_header вызывается в render_header()).
add_action('get_header', function (): void {
    if (logged_in()) return; // посетителям — гостям показываем, админу нет
    echo '<div class="sf-banner">👋 Добро пожаловать! Этот баннер добавлен плагином «Приветственный баннер».</div>';
});

// Добавляет слот размещения кнопок: плагин/тема регистрирует свою область,
// а владелец сайта сам решает, какие кнопки в неё поместить (раздел «Кнопки» в админке).
register_button_slot('hello-slot', 'Баннер плагина «Приветствие»');
add_action('get_footer', function (): void {
    echo '<div class="sf-plugin-buttons">'; render_buttons('hello-slot'); echo '</div>';
});

// Модифицируем название сайта через фильтр (пример: добавляет подпись к бренду).
add_filter('site_name', function ($value) {
    return $value; // по умолчанию ничего не меняем — правьте при необходимости
});

// Хук wp_footer: добавочная подпись в конце страницы.
add_action('wp_footer', function (): void {
    echo '<!-- activated: hello-banner plugin -->';
});
