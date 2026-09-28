<?php
require __DIR__.'/app/core.php';
header('Content-Type: application/json; charset=utf-8');
if (!can_edit() || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); echo json_encode(['error'=>'Нет доступа']); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); echo json_encode(['error'=>'Неверный защитный токен']); exit; }
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK || $_FILES['image']['size'] > 5 * 1024 * 1024) { http_response_code(400); echo json_encode(['error'=>'Файл не загружен или превышает 5 МБ']); exit; }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
$types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
if (!isset($types[$mime])) { http_response_code(415); echo json_encode(['error'=>'Разрешены JPG, PNG, GIF и WebP']); exit; }
$folder=__DIR__.'/uploads'; if (!is_dir($folder) && !mkdir($folder,0755,true)) { http_response_code(500); echo json_encode(['error'=>'Не удалось создать папку uploads']); exit; }
$name=date('YmdHis').'-'.bin2hex(random_bytes(5)).'.'.$types[$mime];
if (!move_uploaded_file($_FILES['image']['tmp_name'],$folder.'/'.$name)) { http_response_code(500); echo json_encode(['error'=>'Не удалось сохранить файл']); exit; }
echo json_encode(['url'=>url('uploads/'.$name)]);
