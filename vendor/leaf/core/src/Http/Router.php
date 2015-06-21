<?php
namespace Leaf\Core\Http;

/**
 * Маршрутизатор. Обрабатывает переданные правила. Отправляет сегменты uri.
 *
 *                      ПРИМЕР
 *      'admin' => array(
 *              'rout' => 'admin/(<controller>(/<action>(/<id>)))',
 *              'default' => array(
 *                      'directory' => 'admin',
 *                      'controller' => 'register',
 *                      'action' => 'logout'
 *              ),
 *              'regex' => array(
 *                      'action' = 'login|logout|register',
 *                      'id' => '\d+'
 *              )
 *      )
 *
 *      Примеры подходящих ссылок:
 *          - admin/register/login/2005
 *          - admin/other/register
 *          - admin/yoyoy
 *          - admin
 *
 * @package    Core
 * @subpackage Http
 * @version    2.0
 * @author     Roman Kritskiy <itoktor@gmail.com>
 * @license    GNU Lisence
 * @copyright  2014 - 2015 Roman Kritskiy
 */
class Router
{

    /**
     * Что может быть частью (сегмента).
     *
     * @var string
     */
    const REGEX_SEGMENT = '[^/.,;?\n]++';

    /**
     * Что должно быть экранировано.
     *
     * @var string
     */
    const REGEX_ESCAPE = '[.\\+*?[^\\]${}=!|]';

    /**
     * Хранилище правил маршрутизации.
     *
     * @var array
     */
    protected static $routes;

    /**
     * Отправляет объект Router.
     *
     * @param array $routes Массив правил маршрутизации.
     *
     * @return Router
     */
    public static function make(array $routes = array())
    {
        return new self($routes);
    }

    /**
     * Компилирует и сохраняет правила маршрутизации, если таких еще нет.
     *
     * @param array $routes Массив правил маршрутизации.
     *
     * @return void
     */
    protected function __construct(array $routes)
    {
        foreach ($routes as $name => $value) {
            if (is_array($value) and array_key_exists('rout', $value) and empty(self::$routes[$name])) {
                $value['regex'] = empty($value['regex'])?null:$value['regex'];
                $rout           = $this->compile($value['rout'], $value['regex']);

                $default             = array_key_exists('default', $value)?$value['default']:null;
                self::$routes[$name] = array(
                    'rout'    => $rout,
                    'default' => $default
                );
            }
        }
    }

    /**
     * Проверяет соответствует ли uri правилам маршрутизации.
     *
     * @param string $uri Адрес запроса.
     *
     * @return boolean|array Если uri соответствует правилу - сегменты uri, нет - false.
     */
    public function check($uri)
    {
        $matches = array();
        $params  = array();

        foreach (self::$routes as $rout) {
            if (preg_match($rout['rout'], $uri, $matches)) {
                $default = $rout['default'];
                break;
            }
        }

        foreach ($matches as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $params[$key] = $value;
        }
        $params += $default;

        return empty($params)?false:$params;
    }

    /**
     * Компилирует правило маршрутизации(превращает в регулярное выражения).
     *
     * @param string $rout  Правило маршрутизации.
     * @param array  $regex Регулярные выражения.
     *
     * @return string Скомпилированное правило.
     */
    public function compile($rout, $regex)
    {
        $expression = preg_replace('#'.self::REGEX_ESCAPE.'#', '\\\\$0', $rout);

        if (strpos($expression, '(') !== false) {
            $expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
        }

        $expression = str_replace(array('<', '>'), array('(?P<', '>'.self::REGEX_SEGMENT.')'), $expression);

        if (is_array($regex)) {
            $search = $replace = array();
            foreach ($regex as $key => $value) {
                $search[]  = "<$key>".self::REGEX_SEGMENT;
                $replace[] = "<$key>$value";
            }

            $expression = str_replace($search, $replace, $expression);
        }

        return '#^'.$expression.'$#uD';
    }
}
