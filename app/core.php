<?php
declare(strict_types=1);
session_start();

function config(): array { static $c; if (!$c) $c = require __DIR__.'/../config.php'; return $c; }
function db(): PDO { static $p; if (!$p) { $c=config(); $p=new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",$c['db_user'],$c['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); } return $p; }
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function now(): string { return date('Y-m-d H:i:s'); }
function setting(string $key, ?string $default=''): string { static $cache=[]; if (!array_key_exists($key,$cache)) { $s=db()->prepare('SELECT value FROM settings WHERE `key`=?');$s->execute([$key]);$cache[$key]=$s->fetchColumn(); } return $cache[$key]===false?$default:(string)$cache[$key]; }
function save_setting(string $key,string $value): void { db()->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]); }
function url(string $path=''): string { $b=rtrim(config()['base_url'] ?? '','/'); return $b.'/'.ltrim($path,'/'); }
function logged_in(): bool { return isset($_SESSION['user']); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (user()['role'] ?? '')==='admin'; }
function can_edit(): bool { return in_array(user()['role'] ?? '', ['admin','editor'],true); }
function require_login(bool $admin=false): void { if (!logged_in() || ($admin&&!is_admin())) { header('Location: '.url('admin')); exit; } }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) die('Неверный защитный токен.'); }
function slugify(string $s): string { $s=trim($s); $s=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s)?:$s; $s=strtolower(preg_replace('~[^a-z0-9]+~i','-',$s)); return trim($s,'-') ?: 'item'; }
function lang(): string { return preg_match('~^[a-z]{2}(-[A-Z]{2})?$~',$_GET['lang']??'')?$_GET['lang']:($_SESSION['lang']??setting('default_language','ru')); }
function languages(): array { return json_decode(setting('languages','{"ru":"Русский","en":"English"}'),true) ?: ['ru'=>'Русский']; }
function flash(string $m=''): ?string { if($m){$_SESSION['flash']=$m;return null;} $x=$_SESSION['flash']??null;unset($_SESSION['flash']);return $x; }
function render_header(string $title=''): void { $site=setting('site_name','Мой сайт'); $logo=setting('logo',''); ?><!doctype html><html lang="<?=e(lang())?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title?:$site)?></title><link rel="stylesheet" href="<?=url('assets/style.css')?>"></head><body><header class="site-header"><a class="brand" href="<?=url()?>"><?php if($logo):?><img src="<?=e($logo)?>" alt=""><?php endif;?><?=e($site)?></a><?php render_menu('header');?><div class="lang"><?php foreach(languages() as $code=>$name):?><a href="?lang=<?=$code?>"><?=e(strtoupper($code))?></a><?php endforeach;?></div><?php render_buttons('header');?></header><main class="container"><?php }
function render_footer(): void { ?></main><footer class="site-footer"><?php render_menu('footer'); render_buttons('footer');?> <small><?=e(setting('footer_text','© '.date('Y').' '.setting('site_name')))?></small></footer><link rel="stylesheet" href="<?=url('themes/'.basename(setting('theme','default')).'/style.css')?>"></body></html><?php }
function render_menu(string $location): void { $q=db()->prepare('SELECT id FROM menus WHERE location=? LIMIT 1');$q->execute([$location]);$menu=(int)$q->fetchColumn();if(!$menu)return;$s=db()->prepare('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order,id');$s->execute([$menu]);$items=$s->fetchAll(PDO::FETCH_ASSOC);$tree=[];foreach($items as $i)$tree[$i['parent_id']??0][]=$i; $out=function($parent=0)use(&$out,$tree){if(empty($tree[$parent]))return;echo '<ul>';foreach($tree[$parent] as $x){echo '<li><a href="'.e($x['url']).'">'.e($x['label']).'</a>'; $out($x['id']);echo '</li>';}echo '</ul>';};echo '<nav>';$out();echo '</nav>'; }
function render_buttons(string $placement): void { $s=db()->prepare('SELECT * FROM site_buttons WHERE placement=?');$s->execute([$placement]);foreach($s as $b)echo '<a class="button" href="'.e($b['url']).'">'.e($b['label']).'</a>'; }
