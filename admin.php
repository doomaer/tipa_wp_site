<?php
require __DIR__.'/app/core.php';
if (!logged_in()) { header('Location: '.url('login')); exit; }
load_plugins(); sf_migrate();
function go(string $s): void { header('Location: ?section='.$s); exit; }
function head_admin(): void { ?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="<?=url('assets/style.css')?>"><title>Админ-панель</title></head><body><div class="admin"><aside><h2><?=e(setting('site_name'))?></h2><a href="<?=url()?>">Открыть сайт</a><a href="?section=dashboard">Обзор</a><a href="?section=content&type=page">Страницы</a><a href="?section=content&type=post">Посты</a><a href="?section=content&type=product">Услуги</a><a href="?section=categories">Категории</a><a href="?section=menus">Меню</a><a href="?section=buttons">Кнопки</a><a href="?section=locations">Размещение</a><a href="?section=media.php">Медиафайлы</a><a href="?section=comments">Комментарии</a><a href="?section=orders">Заявки</a><?php if(is_admin()):?><a href="?section=themes">Темы</a><a href="?section=plugins">Плагины</a><a href="?section=users">Пользователи</a><a href="?section=settings">Настройки</a><?php endif;?><a href="<?=url('logout')?>">Выйти</a></aside><main><?php if($f=flash()):?><p class="notice"><?=e($f)?></p><?php endif;?>
<?php }
function foot_admin(): void { ?></main></div><script src="<?=url('assets/editor.js')?>?v=20260928-5"></script></body></html><?php }

/* Помощники раздела «Размещение» */
function location_items_all(string $location): array {
    $s=db()->prepare('SELECT li.*, b.name AS button_name, b.label AS button_label, b.url AS button_url, m.name AS menu_name FROM location_items li LEFT JOIN site_buttons b ON li.ref_type="button" AND b.id=li.ref_id LEFT JOIN menus m ON li.ref_type="menu" AND m.id=li.ref_id WHERE li.location=? ORDER BY li.sort_order, li.id');
    $s->execute([$location]); return $s->fetchAll(PDO::FETCH_ASSOC);
}
/** Распаковка zip-архива темы/плагина в корневую папку сайта. */
function sf_extract_upload(?array $file,string $targetDir): string {
    if(!$file || $file['error']!==UPLOAD_ERR_OK) return 'Файл не загружен.';
    if(!preg_match('~\.zip$~i',$file['name'])) return 'Нужен .zip архив.';
    if(!class_exists('ZipArchive')) return 'На сервере нет расширения ZipArchive.';
    $base=__DIR__.'/'.$targetDir; if(!is_dir($base)) mkdir($base,0755,true);
    $zip=new ZipArchive();
    if($zip->open($file['tmp_name'])!==true) return 'Не удалось открыть архив.';
    for($i=0;$i<$zip->numFiles;$i++){ $name=$zip->getNameIndex($i);
        if(str_contains($name,'..')||str_starts_with($name,'/')) continue;
        if(!str_ends_with($name,'/')){ $depth=count(explode('/',$name)); if($depth>2){ $zip->close(); return 'Архив должен содержать одну папку с файлами (как в WordPress).'; } }
    }
    $zip->extractTo($base); $zip->close();
    return 'Установлено в '.$targetDir.'. Активируйте в списке ниже.';
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $a=$_POST['action']??'';
 if($a==='save' && can_edit()){
  $id=(int)$_POST['id'];$type=$_POST['type'];$slug=slugify($_POST['slug'] ?: $_POST['title']);
  $v=[$_POST['status'],$_POST['title'],$slug,$_POST['body'],$_POST['excerpt'],$_POST['image'],$_POST['template'],$_POST['language'],$_POST['translation_group'] ?: bin2hex(random_bytes(12)),((int)$_POST['category_id'] ?: null),$_POST['seo_title'],$_POST['seo_description'],isset($_POST['review_enabled'])?1:0,$_POST['review_text'],isset($_POST['comments_enabled'])?1:0,($_POST['price']===''?null:(float)$_POST['price']),$_POST['price_currency'] ?: 'RUB',$_POST['cta_label'],now()];
  if($id){$v[]=$id;db()->prepare('UPDATE content SET status=?,title=?,slug=?,body=?,excerpt=?,image=?,template=?,language=?,translation_group=?,category_id=?,seo_title=?,seo_description=?,review_enabled=?,review_text=?,comments_enabled=?,price=?,price_currency=?,cta_label=?,updated_at=? WHERE id=?')->execute($v);}
  else{array_splice($v,10,0,[user()['id']]);db()->prepare('INSERT INTO content(status,title,slug,body,excerpt,image,template,language,translation_group,category_id,author_id,seo_title,seo_description,review_enabled,review_text,comments_enabled,price,price_currency,cta_label,updated_at,type,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute(array_merge($v,[$type,now()]));}
  flash('Сохранено.');go('content&type='.$type);
 }
 if($a==='category' && can_edit()){db()->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)')->execute([$_POST['name'],slugify($_POST['slug'] ?: $_POST['name']),$_POST['description']]);flash('Категория создана.');go('categories');}
 /* Меню как в WP: создание без привязки к месту */
 if($a==='menu' && can_edit()){db()->prepare("INSERT INTO menus(name,location) VALUES(?,'')")->execute([$_POST['name']]);flash('Меню создано. Добавьте пункты; размещать его нигде не обязательно — назначение места в разделе «Размещение».');go('menus');}
 if($a==='rename_menu' && can_edit()){db()->prepare('UPDATE menus SET name=? WHERE id=?')->execute([$_POST['name'],(int)$_POST['menu_id']]);flash('Меню переименовано.');go('menus');}
 if($a==='delete_menu' && can_edit()){db()->prepare('DELETE FROM menu_items WHERE menu_id=?')->execute([(int)$_POST['menu_id']]);db()->prepare('DELETE FROM location_items WHERE ref_type="menu" AND ref_id=?')->execute([(int)$_POST['menu_id']]);db()->prepare('DELETE FROM menus WHERE id=?')->execute([(int)$_POST['menu_id']]);flash('Меню удалено.');go('menus');}
 if($a==='menu_item' && can_edit()){db()->prepare('INSERT INTO menu_items(menu_id,parent_id,label,url,sort_order) VALUES(?,?,?,?,?)')->execute([(int)$_POST['menu_id'],(int)$_POST['parent_id'] ?: null,$_POST['label'],$_POST['url'],(int)$_POST['sort_order']]);flash('Пункт добавлен.');go('menus&menu='.(int)$_POST['menu_id']);}
 if($a==='update_menu_item' && can_edit()){db()->prepare('UPDATE menu_items SET label=?,url=?,parent_id=? WHERE id=? AND menu_id=?')->execute([$_POST['label'],$_POST['url'],(int)$_POST['parent_id'] ?: null,(int)$_POST['item_id'],(int)$_POST['menu_id']]);flash('Пункт обновлён.');go('menus&menu='.(int)$_POST['menu_id']);}
 if($a==='delete_menu_item' && can_edit()){db()->prepare('DELETE FROM menu_items WHERE id=? AND menu_id=?')->execute([(int)$_POST['item_id'],(int)$_POST['menu_id']]);db()->prepare('UPDATE menu_items SET parent_id=NULL WHERE parent_id=?')->execute([(int)$_POST['item_id']]);flash('Пункт удалён.');go('menus&menu='.(int)$_POST['menu_id']);}
 /* Библиотека кнопок: без обязательного размещения */
 if($a==='button' && can_edit()){db()->prepare("INSERT INTO site_buttons(name,label,url,style,placement) VALUES(?,?,?,?,'')")->execute([$_POST['name'],$_POST['label'],$_POST['url'],$_POST['style'] ?? 'primary']);flash('Кнопка создана в библиотеке. Разместить её можно в разделе «Размещение» или в визуальном редакторе страницы.');go('buttons');}
 if($a==='update_button' && can_edit()){db()->prepare('UPDATE site_buttons SET name=?,label=?,url=?,style=? WHERE id=?')->execute([$_POST['name'],$_POST['label'],$_POST['url'],$_POST['style'] ?? 'primary',(int)$_POST['button_id']]);flash('Кнопка обновлена. На местах размещения с собственным текстом надпись не изменится.');go('buttons');}
 if($a==='delete_button' && can_edit()){db()->prepare('DELETE FROM site_buttons WHERE id=?')->execute([(int)$_POST['button_id']]);db()->prepare('DELETE FROM location_items WHERE ref_type="button" AND ref_id=?')->execute([(int)$_POST['button_id']]);flash('Кнопка удалена из библиотеки и всех размещений.');go('buttons');}
 /* Размещение: пользователь сам решает, куда добавлять, и может переопределить текст на месте */
 if($a==='add_location' && can_edit()){$loc=in_array($_POST['location'],['header','footer'],true)?$_POST['location']:'header';$refType=$_POST['ref_type']==='menu'?'menu':'button';$maxq=db()->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM location_items WHERE location=?');$maxq->execute([$loc]);$next=(int)$maxq->fetchColumn();db()->prepare('INSERT INTO location_items(location,ref_type,ref_id,label_override,sort_order) VALUES(?,?,?,?,?)')->execute([$loc,$refType,(int)$_POST['ref_id'],$_POST['label_override'] ?: null,$next]);flash('Добавлено в расположение «'.$loc.'».');go('locations');}
 if($a==='update_location' && can_edit()){db()->prepare('UPDATE location_items SET label_override=? WHERE id=?')->execute([$_POST['label_override'] ?: null,(int)$_POST['loc_id']]);flash('Текст на месте размещения обновлён.');go('locations');}
 if($a==='remove_location' && can_edit()){db()->prepare('DELETE FROM location_items WHERE id=?')->execute([(int)$_POST['loc_id']]);flash('Убрано из расположения (элемент остался в библиотеке).');go('locations');}
 if($a==='settings' && is_admin()){foreach(['site_name','logo','footer_text','default_language','languages','blog_excerpt_length','seo_description','analytics_id','services_title','business_name','business_description','business_address','business_phone','business_email','business_hours'] as $k)save_setting($k,$_POST[$k] ?? '');
   $social=[]; foreach(['telegram','vk','whatsapp','instagram','facebook','youtube'] as $net){ $u=trim($_POST['social_'.$net] ?? ''); if($u!=='')$social[$net]=$u; } save_setting('business_social',json_encode($social,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   flash('Настройки сохранены.');go('settings');}
 if($a==='activate_theme' && is_admin()){save_setting('theme',preg_replace('~[^a-zA-Z0-9_-]~','',$_POST['dir']));flash('Тема активирована.');go('themes');}
 if($a==='upload_theme' && is_admin()){ flash(sf_extract_upload($_FILES['zip'] ?? null,'themes')); go('themes'); }
 if($a==='toggle_plugin' && is_admin()){ $dir=preg_replace('~[^a-zA-Z0-9_-]~','',$_POST['dir']); $list=active_plugins(); if(in_array($dir,$list,true)){$list=array_values(array_diff($list,$dir));flash('Плагин отключён.');}else{$list[]=$dir;flash('Плагин активирован.');} save_setting('active_plugins',json_encode($list)); go("plugins"); }
 if($a==='upload_plugin' && is_admin()){ flash(sf_extract_upload($_FILES['zip'] ?? null,'plugins')); go('plugins'); }
 if($a==='user' && is_admin()){db()->prepare('INSERT INTO users(name,email,password,role,created_at) VALUES(?,?,?,?,?)')->execute([$_POST['name'],$_POST['email'],password_hash($_POST['password'],PASSWORD_DEFAULT),$_POST['role'],now()]);flash('Пользователь создан.');go('users');}
 if($a==='moderate_comment'){ $st=in_array($_POST['status'],['approved','spam','pending'],true)?$_POST['status']:'approved'; db()->prepare('UPDATE comments SET status=? WHERE id=?')->execute($st,(int)$_POST['id']); flash('Комментарий обновлён.'); go('comments'); }
 if($a==='order_status'){ $st=in_array($_POST['status'],['new','processing','done','cancelled'],true)?$_POST['status']:'new'; db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute($st,(int)$_POST['id']); flash('Статус заявки обновлён.'); go('orders'); }
}
$section=$_GET['section'] ?? 'dashboard';
head_admin();
require __DIR__.'/app/admin_pages.php';
foot_admin();
