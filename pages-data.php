<?php
require __DIR__.'/app/core.php';
header('Content-Type: application/json; charset=utf-8');
if (!can_edit()) { http_response_code(403); echo '[]'; exit; }
$q=db()->query("SELECT title,slug FROM content WHERE type='page' AND status='published' ORDER BY title");echo json_encode($q->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_UNICODE);
