<?php
require __DIR__.'/app/core.php';
header('Content-Type: application/json; charset=utf-8');
if (!can_edit()) { http_response_code(403); echo '[]'; exit; }
$files=is_dir(__DIR__.'/uploads')?(glob(__DIR__.'/uploads/*')?:[]):[];$out=[];
foreach(array_reverse($files) as $file){if(is_file($file))$out[]=['name'=>basename($file),'url'=>url('uploads/'.basename($file))];}
echo json_encode($out,JSON_UNESCAPED_UNICODE);
