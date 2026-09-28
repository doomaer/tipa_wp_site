<?php
/** Хуки (actions/filters) — как в WordPress. Используются плагинами и темой. */
$GLOBALS['sf_hooks'] = $GLOBALS['sf_hooks'] ?? ['action'=>[], 'filter'=>[]];

function sf_add_action(string $hook, callable $cb, int $priority = 10): void {
    $GLOBALS['sf_hooks']['action'][$hook][$priority][] = $cb;
}
function sf_add_filter(string $hook, callable $cb, int $priority = 10): void {
    $GLOBALS['sf_hooks']['filter'][$hook][$priority][] = $cb;
}
function sf_do_action(string $hook, mixed ...$args): void {
    $list = $GLOBALS['sf_hooks']['action'][$hook] ?? [];
    ksort($list);
    foreach ($list as $cbs) foreach ($cbs as $cb) call_user_func_array($cb, $args);
}
function sf_apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
    $list = $GLOBALS['sf_hooks']['filter'][$hook] ?? [];
    ksort($list);
    foreach ($list as $cbs) foreach ($cbs as $cb) $value = call_user_func_array($cb, array_merge([$value], $args));
    return $value;
}

/** Загрузка активных плагинов из папки plugins/. */
function active_plugins(): array {
    return json_decode(setting('active_plugins','[]'), true) ?: [];
}
function load_plugins(): void {
    static $loaded = false;
    if ($loaded) return; $loaded = true;
    foreach (active_plugins() as $dir) {
        $dir = preg_replace('~[^a-zA-Z0-9_-]~','',$dir);
        $file = __DIR__ . '/../plugins/' . $dir . '/plugin.php';
        if ($dir !== '' && is_file($file)) require_once $file;
    }
}

/** Каталог доступных плагинов (по заголовку в первых строках plugin.php). */
function plugin_catalog(): array {
    $out = [];
    foreach (glob(__DIR__ . '/../plugins/*/plugin.php') ?: [] as $file) {
        $dir = basename(dirname($file));
        $head = implode("\n", array_slice(file($file), 0, 15));
        $meta = ['name'=>$dir,'description'=>'','version'=>'','author'=>''];
        if (preg_match('~Plugin Name:\s*(.+)~i', $head, $m)) $meta['name'] = trim($m[1]);
        if (preg_match('~Description:\s*(.+)~i', $head, $m)) $meta['description'] = trim($m[1]);
        if (preg_match('~Version:\s*(.+)~i', $head, $m)) $meta['version'] = trim($m[1]);
        if (preg_match('~Author:\s*(.+)~i', $head, $m)) $meta['author'] = trim($m[1]);
        $meta['dir'] = $dir; $meta['active'] = in_array($dir, active_plugins(), true);
        $out[] = $meta;
    }
    return $out;
}

/** Каталог тем: папки themes/* с theme.json или style.css. */
function theme_catalog(): array {
    $out = []; $active = setting('theme','default');
    foreach (glob(__DIR__ . '/../themes/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $name = basename($dir); $meta = ['dir'=>$name,'name'=>ucfirst($name),'description'=>'','version'=>'','author'=>'','active'=>$name===$active];
        $json = $dir . '/theme.json';
        if (is_file($json)) { $j = json_decode((string)file_get_contents($json), true); if (is_array($j)) $meta = array_merge($meta, $j, ['dir'=>$name,'active'=>$name===$active]); }
        elseif (is_file($dir . '/style.css')) { $head = implode("\n", array_slice(file($dir.'/style.css'), 0, 12));
            foreach (['Theme Name'=>'name','Description'=>'description','Version'=>'version','Author'=>'author'] as $tag=>$key)
                if (preg_match('~'.preg_quote($tag).'?:?\s*(.+?)\s*(?:\*/|$)~i', $head, $m)) $meta[$key] = trim($m[1],' */');
        }
        $out[] = $meta;
    }
    return $out;
}
