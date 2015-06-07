<?php
namespace Easy\Core;

use Easy\Core\Utils\Arr;
use Easy\Core\Config\ConfigException;

/**
 * Класс для работы с конфигурациями
 * Добавлять конфигурационные файлы нужно в папку /config/.
 * Для загрузки используется метод "read"
 *  
 * @package    Core\Config
 * @version    2.0
 * @author     Roman Kritskiy <itoktor@gmail.com>
 * @license    GNU Lisence
 * @copyright  2014 - 2015 Roman Kritskiy
 */
abstract class Config{
    /**
     * Хранилище конфигураций.
     *
     * @var array 
     */
    protected static $data = array();     
    
    /**
     * Загруженые конфиги.
     *
     * @var array
     */
    protected static $load_files = array();     
    
    /**
     * Метод для загрузки конфигурационных файлов.
     * 
     * @param string $file Имя файла с расширением(определяется какой драйвер покдключать).
     * @param bool $set Сохранять ли их в общий массив(по умолчанию - нет).
     * @return array Массив загруженых настроек.
     * @throws ConfigException
     */
    public static function read($file, $set = false)
    {
        if (isset(self::$data[$file])) {
            return self::$data[$file];
        }  
        
        $info = pathinfo($file);
        $type = 'php';
        $name = $file;
        
        if (isset($info['extension'])) {
            $type = $info['extension'];
            $name = substr($file, 0, -(strlen($type) + 1));
        }
        $class = __NAMESPACE__.'\\Config\\'.ucfirst($type);
        
        if (class_exists($class)) {
            $configs = $class::read($name);
            if ($set) {
                self::set($name ,$configs);
            }
            self::$load_files[$file] = TRUE;
            return $configs;
        } else {
            throw new ConfigException('"'.$type.'" - в данный момент такой тип не поддержуется.');
        }
    }
    
    /**
     * Устанавливает значение конфигурации.
     * Используйте точечную анотацию.
     * 
     * @param string $name Имя конфигурации или групы.
     * @param mixed $config Значение конфигурации(й).
     * @return void
     */
    public static function set($name, $config)
    {
        Arr::setAnnotation($name, $config, self::$data);
    }    
    
    /**
     * Получает значение конфигурации по ключу.
     * 
     *      Нужно использывать точечную анотацию для доступа к свойствам в групповых массивах.
     *      Config::get('database.config.driver'); // $_data[database][config][driver]
     * 
     * @param string $name Ключ.
     * @param mixed $default Значение, если по ключу не найдено.
     * @return string Найденые настройки.
     */
    public static function get($name, $default = false)
    {
        return Arr::getAnnotation($name, self::$data, $default);
    }  
}
