<?php
/* Экран «Настройка меню» (аналог wp-admin/nav-menus.php).
   Ожидает: $menus (массив меню), $current (редактируемое меню или null), $locations (код=>название области). */
$used=[]; foreach($menus as $m) if(isset($locations[$m['location']])) $used[$m['location']]=(int)$m['id'];
$items=[]; $cur_url=[];
if($current){ $iq=db()->prepare('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order,id'); $iq->execute([(int)$current['id']]); $items=$iq->fetchAll(PDO::FETCH_ASSOC); foreach($items as $it)$cur_url[(int)$it['id']]=$it['url']; }
?>
<h1>Настройка меню</h1>
<p class="hint">Работает как в WordPress: создайте меню, назначьте его области (шапка / подвал), затем выбирайте меню в списке и добавляйте пункты из блоков «Структура сайта» и «Добавить пункты». Порядок и вложенность меняются перетаскиванием — не забудьте нажать «Сохранить меню».</p>
<div class="menu-screen">
 <div class="menu-col menu-left">
  <fieldset class="card"><legend>Создать новое меню</legend>
   <form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu">
    <label>Название меню<input name="name" placeholder="Например: Основное меню" required></label>
    <button>Создать меню</button>
   </form>
  </fieldset>
  <?php if($current):?>
  <fieldset class="card add-block"><legend>Добавить пункты</legend>
   <form method="post" data-menu-add>
    <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_item"><input type="hidden" name="menu_id" value="<?=$current['id']?>">
    <label>Тип пункта<select name="item_type" data-role="type"><option value="entry">Запись сайта (страница, пост, товар)</option><option value="custom">Произвольная ссылка</option></select></label>
    <div data-show="entry">
     <label>Запись<select name="entry_id">
      <option value="">— выберите —</option>
      <optgroup label="Системные страницы">
       <option value="s:home">Главная</option>
       <option value="s:blog">Блог (все записи)</option>
       <option value="s:shop">Магазин (каталог)</option>
      </optgroup>
      <optgroup label="Страницы"><?php foreach(db()->query("SELECT id,title FROM content WHERE type='page' AND status='published' ORDER BY title") as $p):?><option value="<?=$p['id']?>"><?=e($p['title'])?></option><?php endforeach;?></optgroup>
      <optgroup label="Записи блога"><?php foreach(db()->query("SELECT id,title FROM content WHERE type='post' AND status='published' ORDER BY created_at DESC LIMIT 100") as $p):?><option value="<?=$p['id']?>"><?=e($p['title'])?></option><?php endforeach;?></optgroup>
      <optgroup label="Товары"><?php foreach(db()->query("SELECT id,title FROM content WHERE type='product' AND status='published' ORDER BY created_at DESC LIMIT 100") as $p):?><option value="<?=$p['id']?>"><?=e($p['title'])?></option><?php endforeach;?></optgroup>
      <optgroup label="Категории"><?php foreach(db()->query("SELECT id,name FROM categories ORDER BY name") as $c):?><option value="c:<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></optgroup>
     </select>
    </div>
    <label data-show="custom">URL ссылки<input name="url" placeholder="https://example.com или /kontakty"></label>
    <label>Текст пункта<input name="label" placeholder="Пусто — возьмётся название записи"></label>
    <label>Вложить в<select name="parent_id"><option value="0">Без родителя (верхний уровень)</option><?php foreach($items as $opt):?><option value="<?=$opt['id']?>"><?=e($opt['label'])?></option><?php endforeach;?></select></label>
    <label class="check"><input type="checkbox" name="target" value="1"> Открывать в новом окне</label>
    <button>Добавить в меню</button>
   </form>
   <?php if(!$items):?><form method="post" class="auto-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_auto"><input type="hidden" name="menu_id" value="<?=$current['id']?>"><button class="link-btn">⚡ Автосоздание: Главная, Блог, Магазин</button></form><?php endif;?>
  </fieldset>
  <?php else:?>
  <p class="hint">Меню ещё не выбрано. Создайте меню слева или откройте его через список «Выбранное меню».</p>
  <?php endif;?>
 </div>
 <div class="menu-col menu-mid">
  <?php if($current):?>
  <fieldset class="card structure-card"><legend>Структура сайта</legend>
   <p class="hint small">Отметьте элементы и нажмите «Добавить в меню →».</p>
   <form method="post" data-structure>
    <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_batch"><input type="hidden" name="menu_id" value="<?=$current['id']?>">
    <b>Основные страницы</b><div class="struct-list">
     <label class="check"><input type="checkbox" name="pick[]" value="s:home"> Главная</label>
     <label class="check"><input type="checkbox" name="pick[]" value="s:blog"> Блог (все записи)</label>
     <label class="check"><input type="checkbox" name="pick[]" value="s:shop"> Магазин (каталог)</label>
     <?php foreach(db()->query("SELECT id,title FROM content WHERE type='page' AND status='published' ORDER BY title") as $p):?><label class="check"><input type="checkbox" name="pick[]" value="<?=$p['id']?>" data-title="<?=e($p['title'])?>"> <?=e($p['title'])?></label><?php endforeach;?>
    </div>
    <b>Недавние записи</b><div class="struct-list"><?php $r=db()->query("SELECT id,title FROM content WHERE type='post' AND status='published' ORDER BY created_at DESC LIMIT 10");foreach($r as $p):?><label class="check"><input type="checkbox" name="pick[]" value="<?=$p['id']?>" data-title="<?=e($p['title'])?>"> <?=e($p['title'])?></label><?php endforeach;?></div>
    <b>Категории</b><div class="struct-list"><?php foreach(db()->query("SELECT id,name FROM categories ORDER BY name") as $c):?><label class="check"><input type="checkbox" name="pick[]" value="c:<?=$c['id']?>" data-title="<?=e($c['name'])?>"> <?=e($c['name'])?></label><?php endforeach;?></div>
    <button>Добавить в меню →</button>
   </form>
  </fieldset>
  <?php endif;?>
 </div>
 <div class="menu-col menu-right">
  <fieldset class="card locations-card"><legend>Управление местоположениями</legend>
   <form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_locations">
    <?php foreach($locations as $code=>$lname):?><label><?=$lname?> <select name="loc[<?=$code?>]"><option value="">— не назначено —</option><?php foreach($menus as $m):?><option value="<?=$m['id']?>" <?=$m['location']===$code?'selected':''?>><?=e($m['name'])?></option><?php endforeach;?></select></label><?php endforeach;?>
    <button>Сохранить области</button>
   </form>
  </fieldset>
  <fieldset class="card manage-card"><legend>Управление меню</legend>
   <table class="menu-list"><tr><th>Меню</th><th>Область</th><th></th></tr>
   <?php foreach($menus as $m):?><tr><td><?=e($m['name'])?> <a href="?section=menus&edit=<?=$m['id']?>" class="badge"><?=$current&&(int)$current['id']===(int)$m['id']?'редактируется':'изменить'?></a></td><td><?=e($locations[$m['location']]??'— не назначено —')?></td><td><form method="post" onsubmit="return confirm('Удалить меню вместе со всеми пунктами?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_delete"><input type="hidden" name="id" value="<?=$m['id']?>"><button class="link-btn">Удалить</button></form></td></tr><?php endforeach;?>
   </table>
   <?php if(!$menus):?><p class="hint small">Меню пока нет — создайте его в блоке слева.</p><?php endif;?>
  </fieldset>
 </div>
</div>
<?php if($current):?>
<form method="post" id="menu-structure-form">
 <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="menu_update"><input type="hidden" name="menu_id" value="<?=$current['id']?>">
 <h2>Структура меню «<?=e($current['name'])?>»</h2>
 <ol class="menu-tree" id="menu-tree"><?php foreach($items as $it):?>
  <li class="menu-item<?=($it['type']??'custom')==='entry'&&($it['ref_id']??null)?' mi-entry':''?>" draggable="true" data-id="<?=$it['id']?>" data-parent="<?=e((string)($it['parent_id']??''))?>">
   <span class="mi-handle" title="Перетащите, чтобы изменить порядок и вложенность">⋮⋮</span>
   <input type="hidden" name="order[]" value="<?=$it['id']?>">
   <input type="hidden" name="parents[<?=$it['id']?>]" value="<?=e((string)($it['parent_id']??''))?>" data-parent-field>
   <input type="hidden" name="cur_url[<?=$it['id']?>]" value="<?=e($it['url'])?>" data-cur-url>
   <input class="mi-label" name="labels[<?=$it['id']?>]" value="<?=e($it['label'])?>" placeholder="Название пункта">
   <input class="mi-url" name="urls[<?=$it['id']?>]" value="<?=e($it['url'])?>" placeholder="/ссылка" data-url-field>
   <label class="check mi-target"><input type="checkbox" name="targets[<?=$it['id']?>]" value="1" <?=!empty($it['target'])?'checked':''?>> в новом окне</label>
   <?php if(($it['type']??'custom')==='entry'&&($it['ref_id']??null)):?><span class="mi-type" title="Ссылка ведёт на запись сайта и обновляется автоматически">запись</span><?php else:?><span class="mi-type" title="Произвольная ссылка">ссылка</span><?php endif;?>
   <button type="button" class="link-btn mi-remove" title="Убрать пункт из структуры">×</button>
  </li>
 <?php endforeach;?></ol>
 <p class="hint small" id="menu-empty-hint" <?=count($items)?'hidden':''?>>В этом меню пока нет пунктов — добавьте их через блоки выше.</p>
 <noscript><p class="hint small">Для изменения порядка и вложенности перетаскиванием включите JavaScript; без него сохранится текущий порядок пунктов.</p></noscript>
 <p><button class="button primary" id="menu-save">Сохранить меню</button> <a class="button" href="<?=url()?>">Посмотреть на сайте</a></p>
</form>
<script>window.MENU_ITEMS=<?=json_encode(array_map(fn($i)=>['id'=>(int)$i['id'],'parent'=>(int)($i['parent_id']??0)],$items),JSON_UNESCAPED_UNICODE)?>;</script>
<script src="<?=url('assets/menu.js')?>?v=20260928-1"></script>
<?php endif;?>
