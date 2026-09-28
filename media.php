<?php
require __DIR__.'/app/core.php'; require_login();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    if (!can_edit()) $error='Недостаточно прав.';
    elseif (empty($_FILES['image']) || $_FILES['image']['error']!==UPLOAD_ERR_OK) $error='Выберите файл.';
    elseif ($_FILES['image']['size']>5*1024*1024) $error='Размер файла не должен превышать 5 МБ.';
    else {
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if(!isset($types[$mime])) $error='Разрешены только JPG, PNG, GIF и WebP.';
        else {$dir=__DIR__.'/uploads';if(!is_dir($dir))mkdir($dir,0755,true);$name=date('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$types[$mime];if(move_uploaded_file($_FILES['image']['tmp_name'],$dir.'/'.$name))flash('Изображение загружено: '.url('uploads/'.$name));else$error='Сервер не смог сохранить файл. Проверьте права папки uploads.';}
    }
}
$files=is_dir(__DIR__.'/uploads')?(glob(__DIR__.'/uploads/*')?:[]):[];
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="<?=url('assets/style.css')?>"><title>Медиа</title></head><body><main class="container"><p><a href="<?=url('admin')?>">← Админ-панель</a></p><h1>Медиафайлы</h1><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><?php if($m=flash()):?><p class="notice"><?=e($m)?></p><?php endif;?><form method="post" enctype="multipart/form-data" class="card"><input type="hidden" name="csrf" value="<?=csrf()?>"><label>Выберите изображение (JPG, PNG, GIF или WebP, до 5 МБ)<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required></label><button>Загрузить</button></form><div class="grid media-grid"><?php foreach(array_reverse($files) as $file):$name=basename($file);$link=url('uploads/'.$name);?><article class="card"><img class="cover" src="<?=e($link)?>" alt=""><input value="<?=e($link)?>" readonly onclick="this.select()"><small>Скопируйте URL или вставьте картинку через редактор.</small></article><?php endforeach;?></div></main></body></html>
