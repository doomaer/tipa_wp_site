<?php
/**
 * Plugin Name: Приветственный баннер
 * Description: Показывает гостям сайта приветственную полосу под шапкой. Пример пакета: установите его из раздела «Расширения» без FTP.
 * Version: 1.0
 * sf_plugin: welcome-banner
 */

add_action('get_header', function (): void {
    if (logged_in()) return;
    echo '<div class="sf-banner">👋 Рады видеть вас! Этот баннер добавлен плагином «Приветственный баннер».</div>';
});

// Плагин регистрирует собственную область кнопок — владелец сайта сам решает,
// какие кнопки в неё поместить (админка -> Кнопки -> вкладка с этой областью).
register_button_slot('welcome-slot', 'Под приветственным баннером');
add_action('get_footer', function (): void {
    echo '<div class="sf-plugin-buttons">'; render_buttons('welcome-slot'); echo '</div>';
});
