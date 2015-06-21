<?php
namespace Leaf\Core\Utils;

/**
 * Вспомогательный класс для работы с массивами.
 *
 * @package    Core
 * @subpackage Utils
 * @version    2.0
 * @author     Roman Kritskiy <itoktor@gmail.com>
 * @license    GNU Lisence
 * @copyright  2014 - 2015 Roman Kritskiy
 */
class Arr
{

    /**
     * Извлекает значение из массива по ключу.
     *
     * @param array  &$arr Массив с которого будет извлекаться значение.
     * @param string $key  Ключ по которому будет извлекаться значение.
     *
     * @return mixed Значение по ключу.
     */
    public static function extract(&$arr, $key)
    {
        $val = $arr[$key];
        unset($arr[$key]);
        return $val;
    }

    /**
     * Присоединяет первый массив к другому.
     *
     * @param array $arr1    Исходный массив.
     * @param array $arr2    Присоединяемый массив.
     * @param bool  $prepend Добавить в начало?
     *
     * @return array Объединенный массив.
     */
    public static function merge(&$arr1, $arr2, $prepend = false)
    {
        $arr1 = ($prepend)?array_merge($arr2, $arr1):array_merge($arr1, $arr2);
        return $arr1;
    }

    /**
     * Рекурсивно конвертирует массив в одиночный с заданным разделителем.
     *
     *      Arr::asAnnotation($test, 'test', '.');
     *
     *                  Входящий массив:
     *      array(
     *          "level11" => "value1",
     *          "level12" => array(
     *                          "level21" => "value2"
     *                         )
     *      )
     *
     *                 Исходящий массив:
     *      array(
     *          "test.level11" => "value1",
     *          "test.level12.level21" => "value2",
     *      )
     *
     * @param array  $arr   Конвертируемый массив.
     * @param string $name  Имя, которое добавится в начале.
     * @param string $delim Разделитель.
     *
     * @return array
     */
    public static function asAnnotation(array $arr, $name = false, $delim = '.')
    {
        $array = array();
        foreach ($arr as $key => $value) {
            $arr_name = ($name)?$name.$delim.$key:$key;
            is_array($value)?$array += self::asAnnotation($value, $arr_name, $delim):$array[$arr_name] = $value;
        }

        return $array;
    }

    /**
     * Отправляет значение массива по ключу или default, если искомое - не найдено.
     * Предоставляет доступ к массиву с помощью переданной аннотации.
     *
     *      Arr::getAnnotation('system.test.config', $config);
     *          (return $config['system']['test']['config'])
     *
     * @param string $name    Имя с аннотацией.
     * @param array  $arr     Массив в котором брать значение.
     * @param string $default Значение, которое вернется, если искомое - не найдено.
     * @param string $delim   Разделитель(аннотация).
     *
     * @return mixed Значение по ключу или default.
     */
    public static function getAnnotation($name, $arr, $default = false, $delim = '.')
    {
        $segments = explode($delim, $name);
        $group    = &$arr;
        foreach ($segments as $segment => $value) {
            if (isset($group[$value])) {
                if ($segment == count($segments) - 1) {
                    return $group[$value];
                } else {
                    $group = &$group[$value];
                }
            } else {
                return $default;
            }
        }
    }

    /**
     * Установливает значения массива с помощью переданной аннотации.
     *
     *      Arr::setAnnotation('system.test.config', 'test', $config);
     *          ($config['system']['test']['config'] = 'test')
     *
     * @param string $name  Имя с аннотацией.
     * @param mixed  $val   Значение.
     * @param type   $arr   Массив в котором устаналивать значение.
     * @param string $delim Разделитель(аннотация).
     *
     * @return void
     */
    public static function setAnnotation($name, $val, &$arr, $delim = '.')
    {
        $segments = explode($delim, $name);
        $group    = &$arr;
        foreach ($segments as $segment => $value) {
            if ($segment == count($segments) - 1) {
                $group[$value] = $val;
            } else {
                $group = &$group[$value];
            }
        }
    }
}
