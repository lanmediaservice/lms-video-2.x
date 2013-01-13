<?php ## Модуль PHP_Autoload.
# Библиотека поддержки множественной автозагрузки классов, призванная
# компенсировать неудобство работы с функцией __autoload() в PHP5.
# В отличие от невозможности переопределения __autoload(), вы можете 
# регистрировать сразу несколько обработчиков. Пример использования:
#   require_once "PHP/Autoload.php";
#   ...
#   PHP_Autoload::register("MyAutoloadFunction1");
#   ...
#   PHP_Autoload::register("MyAutoloadFunction2");
# Функция-обработчик должна возвращать true в случае, если класс 
# (по ее мнению) загружен и остальные обработчики в списке следует
# пропустить. Вернув false, она сигнализирует, что можно передать 
# управление следующему обработчику в списке.
class PHP_Autoload {
  # Список функций, вызывающихся при запросе на autoload.
  static $funcs = array();
  # Успешно ли установлен главный обработчик __autoload().
  static $ok = true;

  # static void register(FunctionName $func)
  # Регистрирует новую функцию в списке обработчиков.
  # При запросе на autoload вызываются все обработчики
  # по порядку, начиная с последнего, до тех пор, пока
  # класс не загрузится. Допустимо передавать в параметрах
  # массив в одном из следующих форматов: 
  # - array(className, staticMethodName)
  # - array($object, methodName)
  # Функция-обработчик должна возвращать true в случае,
  # если класс по ее мнению загружен, и false, если можно
  # передать управление следующему обработчику в списке.
  static function register($func) {
    self::$funcs[] =& $func;
  }

  # static void register(FunctionName $func)
  # Удаляет функцию из списка зарегистрированных обработчиков.
  static function unregister($func) {
    $f =& self::$funcs;
    for ($i=0; $i<count($f); $i++)
      if ($f[$i] === $func) {
         array_splice($f, $i, 1);
         break;
      }
  }

  # void autoload(string $classname)
  # Вызывается в момент запроса на autoload (см. ниже).
  static function autoload($classname) {
    static $loading = array();
    # В случае, если класс еще не загружен, а вызывается
    # class_exists(), происходит повторный запрос на autoload,
    # и программа зацикливается. Чтобы этого избежать,
    # проверяем, чтобы вход в autoload() с тем же именем
    # класса не происходил дважды.
    if (isset($loading[$classname])) {
        return;
    }
    # Идет загрузка. В случае, если autoload() будет вызвана
    # рекурсивно, сработает предыдущая строчка.
    $loading[$classname] = true;
    foreach (array_reverse(self::$funcs) as $f) {
      # Вот здесь происходит рекурсивный вызов autoload(),
      # когда клас еще не загружен.
      if (class_exists($classname)) break;
      # Вызываем обработчик. Если он вернет false, значит,
      # произошла какая-то ошибка и необходимо запустить 
      # следующий по списку обработчик.
      if (call_user_func($f, $classname)) break; 
    }
    # Загрузка окончена.
    $loading[$classname] = false;
  }
}

# Код, выполняемый при подключении библиотеки.
# Устанавливает собственный ГЛОБАЛЬНЫЙ обработчик 
# на __autoload, но только в случае, если такого
# обработчика еще не было установлено где-то еще.
if (!function_exists("__autoload")) {
  function __autoload($c) { PHP_Autoload::autoload($c); }
} else {
  PHP_Autoload::$ok = false;
}
?>