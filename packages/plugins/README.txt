Сюда (packages/plugins/) можно положить плагин для установки через админку:
раздел «Расширения» -> «Доступные для установки» -> «Установить».

Формат пакета:
  1) ZIP-архив с ОДНОЙ папкой плагина внутри (hello/hello.php), либо
  2) уже распакованная папка hello/ с главным файлом hello.php.

Главный файл плагина должен содержать заголовок:
  <?php
  /**
   * Plugin Name: Название
   * Description: Что делает плагин
   * Version: 1.0
   * sf_plugin: hello
   */

API плагина (функции ядра):
  add_action('get_header'|'wp_head'|'get_footer'|'wp_footer'|'plugins_loaded', callable);
  add_filter('site_name'|'the_content', fn($value)=>$value);
  register_button_slot('my-slot','Название области'); // своя область кнопок
  render_buttons('my-slot'); // вывод кнопок этой области в нужном месте

После установки нажмите «Активировать» в списке плагинов.
