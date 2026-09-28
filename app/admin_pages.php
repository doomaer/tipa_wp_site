<?php
/* Секции админ-панели. Подключается из admin.php, когда обработаны POST-действия. */
if($section==='dashboard'){
 $n=[];foreach(['page','post','product'] as $t){$q=db()->prepare('SELECT COUNT(*) FROM content WHERE type=?');$q->execute([$t]);$n[$t]=$q->fetchColumn();}
 $orders=db()->query('SELECT COUNT(*) FROM orders WHERE status="new"')->fetchColumn();
 ?><h1>Панель управления</h1><div class="stats"><div>Страниц<b><?=$n['page']?></b></div><div>Постов<b><?=$n['post']?></b></div><div>Услуг<b><?=$n['product']?></b></div><div>Новых заявок<b><?=$orders?></b></div></div><p><a class="button" href="?section=edit&type=page">Новая страница</a> <a class="button" href="?section=edit&type=product">Новая услуга</a> <a class="button" href="?section=buttons">Библиотека кнопок</a></p><p class="notice">Для прохождения модерации Google Ads: заполните «Настройки → Бизнес-информация», опубликуйте страницу «Контакты», добавьте услуги на главную через редактор (кнопка «Вставить услугу…»).</p><?php
} elseif($section==='content'){
 $type=in_array($_GET['type']??'', ['page','post','product'],true)?$_GET['type']:'page';$q=db()->prepare('SELECT id,title,slug,status,language,price FROM content WHERE type=? ORDER BY updated_at DESC');$q->execute([$type]);
 ?><h1><?=e(['page'=>'Страницы','post'=>'Посты','product'=>'Услуги'][$type])?></h1><p><a class="button" href="?section=edit&type=<?=$type?>">Создать</a> <?php if($type==='product'):?><a class="button" href="<?=url('services')?>" target="_blank">Открыть список услуг</a><?php endif;?></p><table><tr><th>Название</th><th>Адрес</th><th>Статус</th><th>Цена</th><th>Язык</th><th></th></tr><?php foreach($q as $x):?><tr><td><?=e($x['title'])?></td><td><?=e($x['slug'])?></td><td><?=e($x['status'])?></td><td><?=$x['price']!==null?e(number_format((float)$x['price'],0,'',' ')):''?></td><td><?=e($x['language'])?></td><td><a href="?section=edit&id=<?=$x['id']?>">Изменить</a></td></tr><?php endforeach;?></table><?php
} elseif($section==='edit' && can_edit()){
 $id=(int)($_GET['id']??0);$type=$_GET['type']??'page';$x=['id'=>0,'status'=>'draft','title'=>'','slug'=>'','body'=>'','excerpt'=>'','image'=>'','template'=>'default','language'=>setting('default_language','ru'),'translation_group'=>'','category_id'=>'','seo_title'=>'','seo_description'=>'','review_enabled'=>0,'review_text'=>'','comments_enabled'=>1,'price'=>'','price_currency'=>'RUB','cta_label'=>''];if($id){$q=db()->prepare('SELECT * FROM content WHERE id=?');$q->execute([$id]);$x=$q->fetch(PDO::FETCH_ASSOC);$type=$x['type'];}$cats=db()->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
 ?><h1><?=$id?'Редактирование':'Новый материал'?> · <?=e(['page'=>'Страница','post'=>'Пост','product'=>'Услуга'][$type])?></h1><form method="post" class="editor"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=$x['id']?>"><input type="hidden" name="type" value="<?=e($type)?>"><label>Название<input name="title" value="<?=e($x['title'])?>" required></label><label>Адрес (slug)<input name="slug" value="<?=e($x['slug'])?>"></label><label>Статус<select name="status"><option value="draft">Черновик</option><option value="published" <?=$x['status']==='published'?'selected':''?>>Опубликован</option></select></label><label>Язык<select name="language"><?php foreach(languages() as $code=>$name):?><option value="<?=$code?>" <?=$x['language']===$code?'selected':''?>><?=e($name)?></option><?php endforeach;?></select></label><label>Группа переводов<input name="translation_group" value="<?=e($x['translation_group'])?>"></label><?php if($type==='post'):?><label>Категория<select name="category_id"><option value="">Без категории</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=$x['category_id']==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></label><?php endif;?><label>URL изображения<input name="image" value="<?=e($x['image'])?>"></label><label>Краткое описание (анонс)<textarea name="excerpt"><?=e($x['excerpt'])?></textarea></label><?php if($type==='product'):?><div class="inline-form"><label>Цена<input type="number" step="0.01" min="0" name="price" value="<?=$x['price']!==null&&$x['price']!==''?e((string)$x['price']):''?>" placeholder="пусто — цена по запросу"></label><label>Валюта<select name="price_currency"><?php foreach(['RUB'=>'₽','USD'=>'$','EUR'=>'€','KZT'=>'₸'] as $k=>$v):?><option value="<?=$k?>" <?=($x['price_currency']??'RUB')===$k?'selected':''?>><?=$v?> (<?=$k?>)</option><?php endforeach;?></select></label><label>Текст кнопки заявки<input name="cta_label" value="<?=e($x['cta_label'])?>" placeholder="Записаться / Заказать"></label></div><?php endif;?><label>Макет<select name="template"><option value="default" <?=$x['template']==='default'?'selected':''?>>Обычный</option><option value="wide" <?=$x['template']==='wide'?'selected':''?>>Широкий</option><option value="landing" <?=$x['template']==='landing'?'selected':''?>>Лендинг</option></select></label><label>Содержание<textarea class="rich" name="body"><?=e($x['body'])?></textarea></label><fieldset><legend>SEO (для Google Ads и поиска)</legend><input name="seo_title" placeholder="SEO title" value="<?=e($x['seo_title'])?>"><textarea name="seo_description" placeholder="SEO description"><?=e($x['seo_description'])?></textarea></fieldset><label><input type="checkbox" name="comments_enabled" <?=$x['comments_enabled']?'checked':''?>> Комментарии</label><label><input type="checkbox" name="review_enabled" <?=$x['review_enabled']?'checked':''?>> Мнение автора</label><textarea name="review_text"><?=e($x['review_text'])?></textarea><button>Сохранить</button></form><?php
} elseif($section==='categories' && can_edit()){
 ?><h1>Категории</h1><form method="post" class="card inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="category"><input name="name" placeholder="Название" required><input name="slug" placeholder="Адрес"><input name="description" placeholder="Описание"><button>Добавить</button></form><?php foreach(db()->query('SELECT * FROM categories') as $c):?><p><?=e($c['name'])?> — <?=e($c['slug'])?></p><?php endforeach;?><?php
} elseif($section==='menus' && can_edit()){
 $menus=db()->query('SELECT * FROM menus ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 $currentMenu=(int)($_GET['menu']??($menus[0]['id']??0));
 $pages=db()->query("SELECT id,title,slug,type FROM content WHERE status='published' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
 $items=[];if($currentMenu){$q=db()->prepare('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order,id');$q->execute([$currentMenu]);$items=$q->fetchAll(PDO::FETCH_ASSOC);}
 ?><h1>Меню</h1><p class="hint">Как в WordPress: создайте меню и наполните его пунктами — размещать его где-либо не обязательно. Назначить место показа можно в разделе «Размещение».</p>
 <div class="columns"><div class="col">
  <h2>Создать меню</h2>
  <form method="post" class="card inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu"><input name="name" placeholder="Название нового меню" required><button>Создать меню</button></form>
  <h2>Выберите меню для редактирования</h2>
  <ul class="menu-list"><?php foreach($menus as $m): $locs=[]; $lq=db()->prepare('SELECT location FROM location_items WHERE ref_type="menu" AND ref_id=?'); $lq->execute([$m['id']]); foreach($lq as $r)$locs[]=$r['location']; if((string)$m['location']!=='')$locs[]=$m['location'];?>
   <li><a class="<?=$currentMenu===$m['id']?'active':''?>" href="?section=menus&menu=<?=$m['id']?>"><?=e($m['name'])?></a> <?php if($locs):?><small>(<?=e(implode(', ',$locs))?>)</small><?php endif;?></li>
  <?php endforeach;if(!$menus):?><li><i>Пока нет ни одного меню</i></li><?php endif;?></ul>
 </div><div class="col">
  <?php if($currentMenu && $menus): $cmName=''; foreach($menus as $mm) if((int)$mm['id']===$currentMenu) $cmName=$mm['name'];?>
  <h2>Структура меню «<?=e($cmName)?>»</h2>
  <table class="menu-structure"><tr><th>Пункт</th><th>Ссылка</th><th>Родитель</th><th></th></tr>
  <?php foreach($items as $it):?>
   <tr><td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update_menu_item"><input type="hidden" name="menu_id" value="<?=$currentMenu?>"><input type="hidden" name="item_id" value="<?=$it['id']?>"><input name="label" value="<?=e($it['label'])?>"></td>
   <td><input name="url" value="<?=e($it['url'])?>"></td>
   <td><select name="parent_id"><option value="">— верхний уровень —</option><?php foreach($items as $pi){if($pi['id']===$it['id'])continue; echo '<option value="'.$pi['id'].'" '.((int)$it['parent_id']===(int)$pi['id']?'selected':'').'>└ '.e($pi['label']).'</option>';}?></select></td>
   <td><button>Сохранить</button></form>
   <form method="post" class="inline-form" onsubmit="return confirm('Удалить пункт?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_menu_item"><input type="hidden" name="menu_id" value="<?=$currentMenu?>"><input type="hidden" name="item_id" value="<?=$it['id']?>"><button class="danger">×</button></form></td></tr>
  <?php endforeach;?></table>
  <h3>Добавить пункт вручную</h3>
  <form method="post" class="card inline-form" id="menu-item-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_item"><input type="hidden" name="parent_id" value="" id="mi-parent"><input name="label" id="mi-label" placeholder="Текст пункта" required><input name="url" id="mi-url" placeholder="/adres ili https://…" required><input name="sort_order" type="number" value="0"><button>Добавить пункт</button></form>
  <details class="card"><summary>Быстрое добавление: существующие страницы и услуги</summary>
   <select id="quick-page"><option value="">Выбрать опубликованную страницу…</option><?php foreach($pages as $p): $u=$p['type']==='page'?'/'.$p['slug']:'/'.$p['type'].'/'.$p['slug']; echo '<option value="'.e($u).'" data-title="'.e($p['title']).'">'.e($p['title']).' ('.e($u).')</option>'; endforeach;?></select>
   <select id="quick-parent"><option value="">— верхний уровень —</option><?php foreach($items as $pi)echo '<option value="'.$pi['id'].'">└ '.e($pi['label']).'</option>';?></select>
   <p class="hint">Выбор страницы подставит ссылку и текст в форму «Добавить пункт».</p>
  </details>
  <form method="post" class="inline-form card"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="rename_menu"><input type="hidden" name="menu_id" value="<?=$currentMenu?>"><input name="name" placeholder="Переименовать меню" required><button>Переименовать</button></form>
  <form method="post" class="inline-form card" onsubmit="return confirm('Удалить меню со всеми пунктами?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_menu"><input type="hidden" name="menu_id" value="<?=$currentMenu?>"><button class="danger">Удалить меню</button></form>
  <?php else:?><p class="hint">Слева создайте меню и выберите его.</p><?php endif;?>
 </div></div><?php
} elseif($section==='buttons' && can_edit()){
 $buttons=db()->query('SELECT * FROM site_buttons ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
 ?><h1>Кнопки (библиотека)</h1><p class="hint">Кнопки здесь — заготовки. Создание никуда их не добавляет. Размещаете вы их сами: в разделе «Размещение» (шапка/подвал) или в визуальном редакторе страницы («Вставить кнопку…»). Текст на месте размещения можно задать свой — тогда он не зависит от библиотеки.</p>
 <form method="post" class="card inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="button"><input name="name" placeholder="Внутреннее имя (для поиска)" required><input name="label" placeholder="Текст кнопки по умолчанию" required><input name="url" placeholder="Ссылка: /uslugi, https://…, tel:+7…" required><select name="style"><option value="primary">Основная</option><option value="outline">Контурная</option><option value="ghost">Прозрачная</option></select><button>Создать кнопку</button></form>
 <table><tr><th>Имя</th><th>Текст</th><th>Ссылка</th><th>Стиль</th><th>Где размещена</th><th>Действия</th></tr>
 <?php foreach($buttons as $b): $places=[]; $pq=db()->prepare('SELECT location,label_override FROM location_items WHERE ref_type="button" AND ref_id=?'); $pq->execute([$b['id']]); foreach($pq as $r)$places[]=$r['location'].($r['label_override']?' (свой текст)':''); ?>
 <tr><td><?=e($b['name'])?></td><td><?=e($b['label'])?></td><td><?=e($b['url'])?></td><td><?=e($b['style'])?></td><td><?=e(implode(', ',$places) ?: '— не размещена —')?></td>
 <td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update_button"><input type="hidden" name="button_id" value="<?=$b['id']?>"><input name="name" value="<?=e($b['name'])?>" size="10"><input name="label" value="<?=e($b['label'])?>" size="10"><input name="url" value="<?=e($b['url'])?>" size="14"><select name="style"><option value="primary" <?=$b['style']==='primary'?'selected':''?>>Основная</option><option value="outline" <?=$b['style']==='outline'?'selected':''?>>Контурная</option><option value="ghost" <?=$b['style']==='ghost'?'selected':''?>>Прозрачная</option></select><button>Сохранить</button></form>
 <form method="post" class="inline-form" onsubmit="return confirm('Удалить кнопку и все её размещения?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_button"><input type="hidden" name="button_id" value="<?=$b['id']?>"><button class="danger">Удалить</button></form></td></tr>
 <?php endforeach;?></table><?php
} elseif($section==='locations' && can_edit()){
 $buttons=db()->query('SELECT * FROM site_buttons ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
 $menus=db()->query('SELECT * FROM menus ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
 ?><h1>Размещение элементов</h1><p class="hint">Здесь вы решаете, куда добавить кнопку или меню. На месте размещения можно задать свой текст — в библиотеке кнопок он при этом не меняется.</p>
 <form method="post" class="card inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="add_location">
  <select name="location"><option value="header">Шапка (header)</option><option value="footer">Подвал (footer)</option></select>
  <select name="ref_type" id="loc-type"><option value="button">Кнопка из библиотеки</option><option value="menu">Меню</option></select>
  <select name="ref_id" id="loc-ref"><?php foreach($buttons as $b)echo '<option value="'.$b['id'].'" data-kind="button">'.e($b['name']).' — '.e($b['label']).'</option>'; foreach($menus as $m)echo '<option value="'.$m['id'].'" data-kind="menu">'.e($m['name']).'</option>';?></select>
  <input name="label_override" placeholder="Свой текст на этом месте (необязательно)">
  <button>Добавить сюда</button></form>
 <?php foreach(['header'=>'Шапка сайта','footer'=>'Подвал сайта'] as $loc=>$title): $rows=location_items_all($loc);?>
  <h2><?=$title?> (<?=$loc?>)</h2>
  <?php if(!$rows):?><p class="hint">Пусто. Добавьте элементы формой выше.</p><?php endif;?>
  <?php if($rows):?><table><tr><th>Элемент</th><th>Текст на этом месте</th><th>Порядок</th><th></th></tr>
  <?php foreach($rows as $r):?>
  <tr><td><?php if($r['ref_type']==='menu'):?>Меню: <?=e($r['menu_name'] ?? '#'.(int)$r['ref_id'])?><?php else:?>Кнопка: <?=e($r['button_name'] ?? '#'.(int)$r['ref_id'])?> <small>(в библиотеке: «<?=e($r['button_label'] ?? '')?>»)</small><?php endif;?></td>
   <td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update_location"><input type="hidden" name="loc_id" value="<?=$r['id']?>"><input name="label_override" value="<?=e($r['label_override'])?>" placeholder="(текст из библиотеки)"><button>Сохранить текст</button></form></td>
   <td><?=(int)$r['sort_order']?></td>
   <td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="remove_location"><input type="hidden" name="loc_id" value="<?=$r['id']?>"><button class="danger">Убрать отсюда</button></form></td></tr>
  <?php endforeach;?></table><?php endif;?>
 <?php endforeach;?><p class="hint">Также кнопки и услуги вставляются внутрь любых страниц через визуальный редактор — там текст тоже задаётся на месте.</p><?php
} elseif($section==='themes' && is_admin()){
 $themes=theme_catalog();
 ?><h1>Темы оформления</h1><p class="hint">Активная тема подключает themes/&lt;имя&gt;/style.css после основных стилей. Новую тему можно загрузить .zip-архивом (внутри — одна папка темы со style.css или theme.json).</p>
 <form method="post" class="card inline-form" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="upload_theme"><input type="file" name="zip" accept=".zip" required><button>Загрузить тему (.zip)</button></form>
 <div class="grid"><?php foreach($themes as $t):?><div class="card theme-card <?=$t['active']?'active':''?>"><h3><?=e($t['name'])?> <?php if($t['active']):?><span class="badge">активна</span><?php endif;?></h3><p><?=e($t['description'])?> <small>v<?=e($t['version'])?> · <?=e($t['author'])?></small></p><?php if(!$t['active']):?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="activate_theme"><input type="hidden" name="dir" value="<?=e($t['dir'])?>"><button>Активировать</button></form><?php endif;?></div><?php endforeach;?></div><?php
} elseif($section==='plugins' && is_admin()){
 $plugins=plugin_catalog();
 ?><h1>Плагины</h1><p class="hint">Плагины расширяют функционал через хуки: <code>sf_add_action('wp_head', …)</code>, <code>sf_add_filter('the_content', …)</code>, <code>sf_do_action('order_created')</code>. Каталог — папка <code>plugins/</code>. Можно загрузить .zip (внутри — одна папка плагина с plugin.php).</p>
 <form method="post" class="card inline-form" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="upload_plugin"><input type="file" name="zip" accept=".zip" required><button>Загрузить плагин (.zip)</button></form>
 <table><tr><th>Плагин</th><th>Описание</th><th>Версия</th><th>Статус</th><th></th></tr>
 <?php foreach($plugins as $p):?><tr><td><b><?=e($p['name'])?></b><br><small><?=e($p['author'])?></small></td><td><?=e($p['description'])?></td><td><?=e($p['version'])?></td><td><?=$p['active']?'активен':'выключен'?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="toggle_plugin"><input type="hidden" name="dir" value="<?=e($p['dir'])?>"><button><?=$p['active']?'Отключить':'Активировать'?></button></form></td></tr><?php endforeach;?></table><?php
} elseif($section==='settings' && is_admin()){
 $b=business_info(); $social=$b['social'];
 ?><h1>Настройки сайта</h1><form method="post" class="editor"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="settings">
 <fieldset><legend>Основное</legend>
 <label>Название сайта<input name="site_name" value="<?=e(setting('site_name'))?>"></label>
 <label>Логотип URL<input name="logo" value="<?=e(setting('logo'))?>"></label>
 <label>Текст в подвале<input name="footer_text" value="<?=e(setting('footer_text'))?>"></label>
 <label>Заголовок страницы услуг<input name="services_title" value="<?=e(setting('services_title','Услуги'))?>"></label>
 <label>Длина анонса<input name="blog_excerpt_length" value="<?=e(setting('blog_excerpt_length'))?>"></label>
 <label>Язык по умолчанию<input name="default_language" value="<?=e(setting('default_language'))?>"></label>
 <label>Список языков (JSON)<textarea name="languages"><?=e(setting('languages'))?></textarea></label>
 </fieldset>
 <fieldset><legend>Бизнес-информация (показывается в подвале; нужна для Google Ads)</legend>
 <label>Название компании<input name="business_name" value="<?=e(setting('business_name'))?>"></label>
 <label>Описание компании<textarea name="business_description"><?=e($b['description'])?></textarea></label>
 <label>Адрес<input name="business_address" value="<?=e($b['address'])?>"></label>
 <label>Телефон<input name="business_phone" value="<?=e($b['phone'])?>"></label>
 <label>Email<input name="business_email" value="<?=e($b['email'])?>"></label>
 <label>Часы работы<input name="business_hours" value="<?=e($b['hours'])?>" placeholder="Пн–Пт 9:00–18:00"></label>
 <div class="inline-form"><?php foreach(['telegram','vk','whatsapp','instagram','facebook','youtube'] as $net):?><label><?=ucfirst($net)?><input name="social_<?=$net?>" value="<?=e($social[$net] ?? '')?>" placeholder="https://…"></label><?php endforeach;?></div>
 </fieldset>
 <fieldset><legend>SEO и аналитика</legend>
 <label>Описание сайта по умолчанию (meta description)<textarea name="seo_description"><?=e(setting('seo_description'))?></textarea></label>
 <label>Google Analytics / Tag ID (например G-XXXXXXX)<input name="analytics_id" value="<?=e(setting('analytics_id'))?>"></label>
 </fieldset>
 <button>Сохранить настройки</button></form><?php
} elseif($section==='users' && is_admin()){
 ?><h1>Пользователи</h1><form method="post" class="card inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="user"><input name="name" placeholder="Имя" required><input name="email" type="email" placeholder="Email" required><input name="password" type="password" placeholder="Пароль" required><select name="role"><option value="user">Пользователь</option><option value="editor">Редактор</option><option value="admin">Администратор</option></select><button>Создать</button></form><table><tr><th>Имя</th><th>Email</th><th>Роль</th></tr><?php foreach(db()->query('SELECT name,email,role FROM users ORDER BY id') as $u):?><tr><td><?=e($u['name'])?></td><td><?=e($u['email'])?></td><td><?=e($u['role'])?></td></tr><?php endforeach;?></table><?php
} elseif($section==='comments' && can_edit()){
 ?><h1>Комментарии</h1><table><tr><th>Автор</th><th>Текст</th><th>Статус</th><th></th></tr><?php foreach(db()->query('SELECT c.*,ct.title FROM comments c LEFT JOIN content ct ON ct.id=c.content_id ORDER BY c.created_at DESC LIMIT 200') as $c):?><tr><td><?=e($c['name'])?></td><td><?=e(mb_strimwidth($c['body'],0,120,'…'))?> <small>к «<?=e($c['title'])?>»</small></td><td><?=e($c['status'])?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="moderate_comment"><input type="hidden" name="id" value="<?=$c['id']?>"><select name="status"><option value="approved" <?=$c['status']==='approved'?'selected':''?>>Одобрен</option><option value="pending" <?=$c['status']==='pending'?'selected':''?>>На проверке</option><option value="spam" <?=$c['status']==='spam'?'selected':''?>>Спам</option></select><button>OK</button></form></td></tr><?php endforeach;?></table><?php
} elseif($section==='orders' && can_edit()){
 ?><h1>Заявки и заказы</h1><table><tr><th>Дата</th><th>Тип</th><th>Услуга</th><th>Клиент</th><th>Контакты</th><th>Сообщение</th><th>Статус</th><th></th></tr><?php foreach(db()->query('SELECT o.*,c.title AS product FROM orders o LEFT JOIN content c ON c.id=o.product_id ORDER BY o.created_at DESC LIMIT 300') as $o):?><tr><td><?=e($o['created_at'])?></td><td><?=$o['kind']==='service'?'услуга':'заказ'?></td><td><?=e($o['product'])?></td><td><?=e($o['customer_name'])?></td><td><?=e($o['phone'])?> <?=e($o['email'])?></td><td><?=e(mb_strimwidth((string)$o['message'],0,80,'…'))?></td><td><?=e($o['status'])?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?=$o['id']?>"><select name="status"><?php foreach(['new','processing','done','cancelled'] as $st):?><option value="<?=$st?>" <?=$o['status']===$st?'selected':''?>><?=$st?></option><?php endforeach;?></select><button>OK</button></form></td></tr><?php endforeach;?></table><?php
} elseif($section==='media.php'){
 ?><iframe src="<?=url('media.php')?>" style="width:100%;height:80vh;border:0"></iframe><?php
} else { echo '<h1>Раздел недоступен</h1>'; }
