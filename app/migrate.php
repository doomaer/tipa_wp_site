<?php
/** Идемпотентная миграция схемы для существующих установок. Вызывается из core(). */
function sf_migrate(): void {
    static $done = false;
    if ($done) return; $done = true;
    try {
        $pdo = db();
        $has = function(string $table) use ($pdo): bool {
            $s = $pdo->prepare("SHOW TABLES LIKE ?"); $s->execute([$table]); return (bool)$s->fetchColumn();
        };
        $col = function(string $table, string $column) use ($pdo): bool {
            $s = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?"); $s->execute([$column]); return (bool)$s->fetchColumn();
        };
        if (!$has('location_items')) $pdo->exec("CREATE TABLE location_items (id int unsigned AUTO_INCREMENT PRIMARY KEY, location varchar(50) NOT NULL, ref_type enum('menu','button','service') NOT NULL DEFAULT 'button', ref_id int unsigned NOT NULL, label_override varchar(100) NULL, cta_override varchar(100) NULL, sort_order int NOT NULL DEFAULT 0, KEY location(location)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($has('location_items') && !$col('location_items','cta_override')) $pdo->exec("ALTER TABLE location_items ADD cta_override varchar(100) NULL AFTER label_override");
        if ($has('location_items')) { try { $pdo->exec("ALTER TABLE location_items MODIFY ref_type enum('menu','button','service') NOT NULL DEFAULT 'button'"); } catch (Throwable $e) {} }
        if (!$col('site_buttons','style')) $pdo->exec("ALTER TABLE site_buttons ADD style varchar(50) NOT NULL DEFAULT 'primary' AFTER url");
        if (!$col('content','price')) $pdo->exec("ALTER TABLE content ADD price decimal(12,2) NULL AFTER comments_enabled");
        if (!$col('content','price_currency')) $pdo->exec("ALTER TABLE content ADD price_currency varchar(10) NOT NULL DEFAULT 'RUB' AFTER price");
        if (!$col('content','cta_label')) $pdo->exec("ALTER TABLE content ADD cta_label varchar(100) NULL AFTER price_currency");
    } catch (Throwable $e) { /* миграция не должна ронять сайт */ }
}
