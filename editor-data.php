<?php
require __DIR__.'/app/core.php';
header('Content-Type: application/json; charset=utf-8');
if (!can_edit()) { http_response_code(403); echo '[]'; exit; }
$rows=db()->query('SELECT id,name,label,url FROM site_buttons ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
