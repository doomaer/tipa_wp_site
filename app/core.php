<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/hooks.php';
require_once __DIR__.'/migrate.php';

function config(): array { static $c; if (!$c) $c = require __DIR__.'/../config.php'; return $c; }
function db(): PDO { static $p; if (!$p) { $c=config(); $p=new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",$c['db_user'],$c['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); } return $p; }
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function now(): string { return date('Y-m-d H:i:s'); }
function setting(string $key, ?string $default=''): string { static $cache=[]; if (!array_key_exists($key,$cache)) { try { $s=db()->prepare('SELECT value FROM settings WHERE `key`=?');$s->execute([$key]);$cache[$key]=$s->fetchColumn(); } catch(Throwable){$cache[$key]=false;} } return $cache[$key]===false?$default:(string)$cache[$key]; }
function save_setting(string $key,string $value): void { db()->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]); }
function url(string $path=''): string { $b=rtrim(config()['base_url'] ?? '','/'); return $b.'/'.ltrim($path,'/'); }
function current_url(): string { $s=$_SERVER['HTTPS']??'http'; $host=$_SERVER['HTTP_HOST']??'localhost'; return ($s==='on'||$s==='1'?'https':'http').$host.url(trim(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/','/')); }
function logged_in(): bool { return isset($_SESSION['user']); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (user()['role'] ?? '')==='admin'; }
function can_edit(): bool { return in_array(user()['role'] ?? '', ['admin','editor'],true); }
function require_login(bool $admin=false): void { if (!logged_in() || ($admin&&!is_admin())) { header('Location: '.url('admin')); exit; } }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) die('Неверный защитный токен.'); }
function slugify(string $s): string { $m=['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya']; $l=strtolower($s); $t=strtr($l,$m); $t=str_replace(array_values($m),array_keys($m),$l); $s=strtr($l,$m); $s=preg_replace('~[^a-z0-9]+~','-',$s); return trim((string)$s,'-') ?: 'item'; }
function lang(): string { return preg_match('~^[a-z]{2}(-[A-Z]{2})?$~',$_GET['lang']??'')?$_GET['lang']:($_SESSION['lang']??setting('default_language','ru')); }
function languages(): array { return json_decode(setting('languages','{\"ru\":\"Русский\",\"en\":\"English\"}'),true) ?: ['ru'=>'Русский']; }
function flash(string $m=''): ?string { if($m){$_SESSION['flash']=$m;return null;} $x=$_SESSION['flash']??null;unset($_SESSION['flash']);return $x; }

/* ---------- Пункты мест размещения (для раздела «Размещение») ---------- */
function location_items_all(string $location): array {
    $s=db()->prepare('SELECT li.*, b.name AS button_name, b.label AS button_label, b.url AS button_url, m.name AS menu_name
        FROM location_items li
        LEFT JOIN site_buttons b ON li.ref_type="button" AND b.id=li.ref_id
        LEFT JOIN menus m ON li.ref_type="menu" AND m.id=li.ref_id
        WHERE li.location=? ORDER BY li.sort_order, li.id');
    $s->execute([$location]); return $s->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- Меню в стиле WordPress ---------- */
function menu_locations(): array { return ['header'=>'Шапка сайта (header)','footer'=>'Подвал сайта (footer)']; }
/** Активное меню для расположения: сначала location_items (WP-style), затем старое menus.location. */
function menu_for_location(string $location): ?int {
    $q=db()->prepare('SELECT ref_id FROM location_items WHERE location=? AND ref_type="menu" ORDER BY sort_order,id LIMIT 1');
    $q->execute([$location]); $id=(int)$q->fetchColumn(); if($id) return $id;
    $s=db()->prepare('SELECT id FROM menus WHERE location=? LIMIT 1'); $s->execute([$location]); $id=(int)$s->fetchColumn();
    return $id ?: null;
}
function render_menu(string $location): void {
    $menuId=menu_for_location($location); if(!$menuId) return;
    $s=db()->prepare('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order,id'); $s->execute([$menuId]);
    $items=$s->fetchAll(PDO::FETCH_ASSOC); $tree=[]; foreach($items as $i) $tree[(int)($i['parent_id']??0)][]=$i;
    $out=function($parent=0)use(&$out,$tree){ if(empty($tree[$parent]))return; echo '<ul>'; foreach($tree[$parent] as $x){ echo '<li><a href="'.e($x['url']).'">'.e($x['label']).'</a>'; $out((int)$x['id']); echo '</li>';} echo '</ul>'; };
    echo '<nav class="main-nav" aria-label="'.e($location).'">'; $out(); echo '</nav>';
}

/* ---------- Кнопки: библиотека + размещение (как виджеты WP) ---------- */
function get_button(int $id): ?array { $s=db()->prepare('SELECT * FROM site_buttons WHERE id=?'); $s->execute([$id]); $b=$s->fetch(PDO::FETCH_ASSOC); return $b?:null; }
function render_button(array $b, ?string $labelOverride=null): void {
    $label=$labelOverride!==null&&$labelOverride!==''?$labelOverride:$b['label'];
    echo '<a class="button button-'.e($b['style']??'primary').'" href="'.e($b['url']).'">'.e($label).'</a>';
}
/** Всё, что пользователь разместил в расположении (кнопки, меню и услуги), с переопределением текста на месте. */
function render_location(string $location): void {
    $s=db()->prepare('SELECT * FROM location_items WHERE location=? ORDER BY sort_order,id'); $s->execute([$location]);
    foreach($s as $it){
        $override=(string)($it['label_override']??'');
        if($it['ref_type']==='menu') render_menu_from_id((int)$it['ref_id']);
        elseif($it['ref_type']==='service') echo render_service_card((int)$it['ref_id'],$override);
        else { $b=get_button((int)$it['ref_id']); if($b) render_button($b,$override); }
    }
}
function render_menu_from_id(int $menuId): void {
    $s=db()->prepare('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order,id'); $s->execute([$menuId]);
    $items=$s->fetchAll(PDO::FETCH_ASSOC); $tree=[]; foreach($items as $i) $tree[(int)($i['parent_id']??0)][]=$i;
    $out=function($parent=0)use(&$out,$tree){ if(empty($tree[$parent]))return; echo '<ul>'; foreach($tree[$parent] as $x){ echo '<li><a href="'.e($x['url']).'">'.e($x['label']).'</a>'; $out((int)$x['id']); echo '</li>';} echo '</ul>'; };
    echo '<nav class="main-nav">'; $out(); echo '</nav>';
}
/** Совместимость со старым вызовом. */
function render_buttons(string $placement): void { render_location($placement); }

/* ---------- Шорткоды: [button id=N label="..."], [service id=N label="..." cta="..."] и [location name="footer"] ---------- */
function sf_shortcode_atts(string $src): array {
    $out=[]; if(preg_match_all('~(\w+)\s*=\s*"([^"]*)"~',$src,$m,PREG_SET_ORDER)) foreach($m as $p)$out[$p[1]]=$p[2];
    return $out;
}
function sf_shortcodes(string $html): string {
    $html=preg_replace_callback('~\[button([^\]]*)\]~', function($m){
        $a=sf_shortcode_atts($m[1]); $b=get_button((int)($a['id']??0)); if(!$b) return '';
        ob_start(); render_button($b,(string)($a['label']??'')); return ob_get_clean();
    }, $html);
    $html=preg_replace_callback('~\[service([^\]]*)\]~', function($m){
        $a=sf_shortcode_atts($m[1]);
        return render_service_card((int)($a['id']??0),(string)($a['label']??''),(string)($a['cta']??''));
    }, $html);
    $html=preg_replace_callback('~\[location([^\]]*)\]~', function($m){
        $a=sf_shortcode_atts($m[1]); $loc=in_array($a['name']??'',['header','footer'],true)?$a['name']:'footer';
        ob_start(); render_location($loc); return ob_get_clean();
    }, $html);
    return (string)$html;
}
/** Рендер для вставки внутрь содержимого страниц: [button], [service] и [location] без услуг.
 *  Услуги из «Размещения» выводятся только в реальном подвале/шапке сайта — так нет дублей на каждой странице. */
function sf_shortcodes_content(string $html): string {
    return (string)preg_replace_callback('~\[location([^\]]*)\]~', function($m){
        $a=sf_shortcode_atts($m[1]); $loc=in_array($a['name']??'',['header','footer'],true)?$a['name']:'footer';
        ob_start(); foreach(location_items_all($loc) as $it){
            if($it['ref_type']==='menu') render_menu_from_id((int)$it['ref_id']);
            elseif($it['ref_type']==='service') { /* пропускаем: эти услуги уже показаны в подвале сайта */ }
            else { $b=get_button((int)$it['ref_id']); if($b) render_button($b,(string)($it['label_override']??'')); }
        }
        return ob_get_clean();
    }, sf_shortcodes($html));
}
/** Карточка услуги. $titleOverride — свой заголовок на месте вставки, $ctaOverride — свой текст кнопки заявки. */
function render_service_card(int $id, string $titleOverride='', string $ctaOverride=''): string {
    $s=db()->prepare('SELECT * FROM content WHERE id=? AND type="product"'); $s->execute([$id]); $p=$s->fetch(PDO::FETCH_ASSOC);
    if(!$p) return '<p class="error">Услуга не найдена.</p>';
    $url=url('product/'.$p['slug']); $title=$titleOverride!==''?$titleOverride:$p['title'];
    ob_start(); ?>
    <div class="service-card card" itemscope itemtype="https://schema.org/Service">
      <?php if($p['image']):?><img src="<?=e($p['image'])?>" alt="<?=e($p['title'])?>" itemprop="image"><?php endif;?>
      <h3 itemprop="name"><a href="<?=e($url)?>"><?=e($title)?></a></h3>
      <?php if($p['excerpt']):?><p itemprop="description"><?=e($p['excerpt'])?></p><?php endif;?>
      <?php if($p['price']!==null):?><p class="price" itemprop="offers" itemscope itemtype="https://schema.org/Offer"><span itemprop="priceCurrency" content="<?=e($p['price_currency'])?>"></span><b itemprop="price" content="<?=e((string)$p['price'])?>"><?=e(number_format((float)$p['price'],0,'',' '))?> <?=e($p['price_currency'])?></b></p><?php endif;?>
      <p><a class="button" href="<?=e($url)?>#order"><?=e($ctaOverride!==''?$ctaOverride:($p['cta_label'] ?: 'Записаться'))?></a></p>
      <form method="post" action="<?=url('order')?>" class="order-form">
        <input type="hidden" name="csrf" value="<?=csrf()?>">
        <input type="hidden" name="kind" value="service">
        <input type="hidden" name="product_id" value="<?=$p['id']?>">
        <input name="name" placeholder="Ваше имя" required aria-label="Имя">
        <input name="phone" placeholder="Телефон" required aria-label="Телефон">
        <?=consent_checkbox_html()?>
        <button type="submit"><?=e($ctaOverride!==''?$ctaOverride:($p['cta_label'] ?: 'Записаться'))?></button>
      </form>
    </div>
    <?php return ob_get_clean();
}

/* ---------- Бизнес-информация (Google Ads / Local SEO) ---------- */
function business_info(): array {
    return [
        'name'=>setting('business_name', setting('site_name','Мой сайт')),
        'description'=>setting('business_description',''),
        'address'=>setting('business_address',''),
        'phone'=>setting('business_phone',''),
        'email'=>setting('business_email',''),
        'hours'=>setting('business_hours',''),
        'social'=>json_decode(setting('business_social','[]'),true) ?: [],
    ];
}
/** Нужен ли чекбокс согласия: только когда опубликована страница privacy. */
function consent_required(): bool {
    $s=db()->prepare('SELECT id FROM content WHERE type="page" AND slug="privacy" AND status="published" LIMIT 1');
    if(!$s->fetchColumn()) return false;
    return true;
}
/** Кнопка «Согласие на обработку данных» — требует опубликованной страницы privacy. */
function consent_checkbox_html(string $extra=''): string {
    if(!consent_required()) return '';
    return '<label class="consent"><input type="checkbox" name="consent" value="1" required '.$extra.'> Нажимая кнопку, вы соглашаетесь с <a href="'.url('privacy').'">политикой конфиденциальности</a> и обработкой персональных данных.</label>';
}
function render_business_footer(): void {
    $b=business_info(); if(!$b['address']&&!$b['phone']&&!$b['email']&&!$b['hours']&&!$b['description']) return; ?>
    <section class="business-info" itemscope itemtype="https://schema.org/LocalBusiness">
      <b itemprop="name"><?=e($b['name'])?></b>
      <?php if($b['description']):?><p itemprop="description"><?=e($b['description'])?></p><?php endif;?>
      <address>
        <?php if($b['address']):?><span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><span itemprop="streetAddress"><?=e($b['address'])?></span></span><?php endif;?>
        <?php if($b['phone']):?><a href="tel:<?=e(preg_replace('~[^\d+]~','',$b['phone']))?>" itemprop="telephone"><?=e($b['phone'])?></a><?php endif;?>
        <?php if($b['email']):?><a href="mailto:<?=e($b['email'])?>" itemprop="email"><?=e($b['email'])?></a><?php endif;?>
        <?php if($b['hours']):?><span class="hours" itemprop="openingHours" content="<?=e($b['hours'])?>">Режим работы: <?=e($b['hours'])?></span><?php endif;?>
      </address>
      <?php if($b['social']):?><ul class="social"><?php foreach($b['social'] as $net=>$link):?><?php if(filter_var($link,FILTER_VALIDATE_URL)):?><li><a rel="noopener me" target="_blank" href="<?=e($link)?>"><?=e(ucfirst($net))?></a></li><?php endif; endforeach;?></ul><?php endif;?>
    </section>
    <?php
}

/* ---------- SEO / JSON-LD ---------- */
function seo_head(string $title='', string $description='', string $canonical='', string $image=''): void {
    $site=setting('site_name','Мой сайт');
    $title=$title!==''?$title.' — '.$site:$site;
    $description=mb_substr(trim($description?:setting('seo_description',$site)),0,300);
    $canonical=$canonical?:current_url(); ?>
<meta name="description" content="<?=e($description)?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?=e($canonical)?>">
<meta property="og:site_name" content="<?=e($site)?>">
<meta property="og:title" content="<?=e($title)?>">
<meta property="og:description" content="<?=e($description)?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:image" content="<?=e($image?:setting('logo'))?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#5c46e8">
<?php
}
function json_ld(string $title='', string $description='', string $image=''): void {
    $b=business_info(); $org=[ '@context'=>'https://schema.org','@type'=>($b['address']?'LocalBusiness':'Organization'),'name'=>$b['name'],'url'=>current_url(),'logo'=>setting('logo')?:null,'description'=>$b['description']?:$description,'address'=>$b['address']?:null,'telephone'=>$b['phone']?:null,'email'=>$b['email']?:null,'openingHours'=>$b['hours']?:null,'sameAs'=>array_values(array_filter((array)$b['social'])) ];
    $org=array_filter($org,fn($v)=>$v!==null&&$v!==''&&$v!==[]);
    $web=['@context'=>'https://schema.org','@type'=>'WebSite','name'=>setting('site_name'),'url'=>rtrim(url(),'/').'/'];
    $crumbs=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>url()]]];
    if($title!==''){ $crumbs['itemListElement'][]=['@type'=>'ListItem','position'=>2,'name'=>$title,'item'=>current_url()]; }
    echo '<script type="application/ld+json">'.json_encode([$org,$web,$crumbs],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
}

/* ---------- Каркас страниц ---------- */
function render_header(string $title=''): void {
    load_plugins(); sf_migrate();
    $site=setting('site_name','Мой сайт'); $logo=setting('logo','');
    $desc=setting('seo_description','');
    ?><!doctype html><html lang="<?=e(lang())?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e(($title!==''?$title.' — ':'').$site)?></title>
<?php seo_head($title,$desc); $fn=(string)setting('favicon',''); if($fn!=='') echo '<link rel="icon" href="'.e($fn).'">'; ?>
<link rel="stylesheet" href="<?=url('assets/style.css')?>">
<link rel="stylesheet" href="<?=e(theme_style_url())?>">
<?php sf_do_action('wp_head'); ?>
<?=sf_apply_filters('head_extra', json_ld($title,$desc))?>
</head><body><a class="skip-link" href="#main">К содержанию</a><header class="site-header"><a class="brand" href="<?=url()?>"><?php if($logo):?><img src="<?=e($logo)?>" alt="<?=e($site)?>"><?php endif;?><?=e($site)?></a><?php render_location('header'); render_menu('header');?><div class="lang"><?php foreach(languages() as $code=>$name):?><a href="?lang=<?=$code?>"><?=e(strtoupper($code))?></a><?php endforeach;?></div></header><main class="container" id="main"><?php if($f=flash()):?><p class="notice"><?=e($f)?></p><?php endif;?>
<?php
}
function render_footer(): void {
    ?></main><footer class="site-footer"><div class="footer-cols"><?php render_location('footer'); render_menu('footer'); render_business_footer(); render_legal_links();?></div><small><?=e(setting('footer_text','© '.date('Y').' '.setting('site_name')))?></small><?php $ga=setting('analytics_id',''); if($ga):?><script async src="https://www.googletagmanager.com/gtag/js?id=<?=e(rawurlencode($ga))?>"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?=e($ga)?>');</script><?php endif; sf_do_action('wp_footer');?></footer></body></html><?php
}
/** Тема: style.css подключается в <head> (иначе стили применялись бы после рендера). */
function theme_style_url(): string { return url('themes/'.basename(setting('theme','default')).'/style.css'); }
/** Быстрые ссылки на правовые страницы, если они опубликованы. */
function render_legal_links(string $class='legal-links'): void {
    $slugs=['privacy'=>'Политика конфиденциальности','terms'=>'Условия использования','contacts'=>'Контакты'];
    $found=[];
    foreach($slugs as $slug=>$label){ $s=db()->prepare('SELECT id FROM content WHERE type="page" AND slug=? AND status="published" LIMIT 1'); $s->execute([$slug]); if($s->fetchColumn()) $found[]='<a href="'.url($slug).'">'.$label.'</a>'; }
    if(!$found) return;
    echo '<nav class="'.e($class).'" aria-label="Правовая информация">'.implode(' ',$found).'</nav>';
}
