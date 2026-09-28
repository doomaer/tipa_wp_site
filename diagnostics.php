<?php
/** Delete this file after diagnostics. It never displays database credentials. */
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
echo '<h1>SiteForge diagnostics</h1>';
echo '<p>PHP: <b>'.htmlspecialchars(PHP_VERSION).'</b></p>';
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo '<p style="color:#b00">Нужен PHP 8.0 или новее. Выберите PHP 8.1/8.2 в панели хостинга.</p>';
}
try {
    if (!file_exists(__DIR__.'/config.php')) throw new RuntimeException('config.php отсутствует.');
    require __DIR__.'/app/core.php';
    db()->query('SELECT 1');
    $tables = db()->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    echo $tables ? '<p style="color:green">Соединение с БД и таблицы: OK.</p>' : '<p style="color:#b00">В БД нет таблиц SiteForge.</p>';
    echo '<p>Если здесь всё OK, откройте в панели хостинга файл <code>error_log</code>, снова перейдите на <code>/admin.php</code> и пришлите последнюю строку ошибки.</p>';
} catch (Throwable $e) {
    echo '<p style="color:#b00">Ошибка: '.htmlspecialchars($e->getMessage()).'</p>';
}
