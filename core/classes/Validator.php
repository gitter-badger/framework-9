<?php

/**
 *                                     ВАЛИДАТОР
 * Сообщение по умолчанию, а так же разделители для ошибок можно изменить в файле config/validation.php
 * 
 *                                      ПРИМЕР
 * 
 *    $test = array('n' => 'roMan', 'email' => 'itoktor@gmail.com');
 * 
 *    $valid = Validator::make($test)
 *                 ->rules('n', array('trim', 'required', 'alpha(_)', 'length(,10)'), 'name')
 *                 ->rules('email', array('trim', 'email', 'length(5,15)'))
 *                 ->messages(array('email' => 'Парниша твой ":label" не катит!!!'));
 *      
 *    if($valid->check()){
 *          $test = $val->data();   // Если валидация прошла успешно, получаем обработаные данные
 *    }else{
 *          echo $valid->as_string();   // Если возникли ошибки, выводим их в виде строки
 *    }
 *  
 *                                   СПИСОК ФУНКЦИЙ
 *           - require           - email                    - matches
 *           - integer           - ip                       - date
 *           - float             - bool                     - length
 *           - url               - alpha                    - exact_length
 *
 * @package Helpers
 * @author iToktor
 * @since 1.0
 */
class Validator {
    
    /**
     * @var array массив данных на валидацию.
     */
    protected $_data = array();
        
    /**
     * @var array правила валидации.
     */
    protected $_rules = array();
    
    /**
     * @var array хранилище для ошибок.
     */
    protected $_errors = array();
    
    /**
     * @var array настройки для ошибок(сообщения).
     */
    protected $_error_configs = NULL;
    
    /**
     * Фабрика для валидации
     * 
     * @param array $values - данные на проверку.
     * @return Validator
     */
    public static function make(array $values) {
        return new Validator($values);
    }
    
    /**
     * Сохранение данных локально, загрузка настроек для ошибок.
     * 
     * @param array $values - данные на проверку.
     * @uses Config::read() 
     */
    public function __construct(array $values) {
        $this->data($values);
        if(empty($this->_error_configs)){
            $this->_error_configs = Config::read('validation');
        }
    }
    
    /**
     * Установка правил валидации.
     * 
     *                     Возможны 2 варианта:
     *      1) ->rules('name', array('required', 'max_length(30)'), 'user_name') 
     *      
     *      2) $rules = array(
     *              array(
     *                  'field' => 'password',
     *                  'rules' => array('required', 'alpha', integer(4,8))                       
     *              ),
     *              array(
     *                  'field' => 'confirm_password'
     *                  'rules' => array('matches(password)')
     *              )
     *         );
     * 
     *         ->rules($rules)
     *  
     * 
     * @param string|array $field - название поля или массив с правилами.
     * @param array $rules - правила валидации.
     * @param type $label - псевдоним для поля.
     * @return Validator
     * @throws Easy_Exception
     */
    public function rules($field, $rules = NULL, $label = NULL){
		
	if(is_array($field)){
            foreach ($field as $row){
                if ( ! isset($row['field']) OR ! isset($row['rules'])){
                    throw new Easy_Exception('Правила валидации заданы не верно. Проверьте входящие данные.');
		}
                
                $label = (isset($row['lable'])) ? $row['lable'] : NULL;
		$this->rules($row['field'], $row['rules'], $label);
            }
            return $this;
        }
		
	if ( ! is_string($field) OR  ! is_array($rules)){		
            throw new Easy_Exception('Правила валидации заданы не верно. Проверьте входящие данные.');
        }

	$label = (empty($label)) ? $field : $label;
	$this->_rules[] = array(
                        'field' => $field, 
                        'rules' => $rules,
                        'label' => $label
	);
        
        return $this;
    }
    
    /**
     * Проверка данных, используя заданные правила валидации.
     * 
     * @return boolean
     * @throws Easy_Exception
     */
    public function check(){
        if(empty($this->_rules)){
            throw new Easy_Exception('Правила валидации не заданы.');
        }
		
	foreach ($this->_rules as $row){
            $this->_check_rule($row);	
	}
		
	if(empty($this->_errors)){
            return TRUE;
        }else{
            return FALSE;                    
        }
    }
    
    /**
     * Проверка одного поля.
     * 
     * @param array $field - правила для текущего поля. 
     * @throws Easy_Exception
     */
    protected function _check_rule($field){
        if(!isset($this->_data[$field['field']])){
            throw new Easy_Exception('Данные '.$field['field'].' не найдены.');
        }
        
	foreach($field['rules'] as $rule){
            $param = NULL;
            $match = array();
            
            if (preg_match("/(.*?)\((.*?)\)/", $rule, $match)){
		$rule = $match[1]; //Правило валидации
		$param = $match[2]; //Параметры
            }
                        
            if(!method_exists($this, $rule)){
		if(function_exists($rule)){
                    $result = $rule($this->_data[$field['field']]);
			
                    if(!is_bool($result)){ 
                        $this->_data[$field['field']] = $result; 
                    }						
		}
            }else{
                $result = $this->$rule($this->_data[$field['field']],$param);
                if($result === FALSE){
                    if(empty($this->_error_configs['messages'][$rule])){
                        throw new Easy_Exception('Сообщение об ошибки для '.$rule.' не найдено');
                    }
                    $error = strtr($this->_error_configs['messages'][$rule],array(':label' => $field['label']));
                    $this->_errors[] = $error;
                }
			
            }
	}
    }
    
    /**
     * Если ничего не передавать работает как геттер(отправляет массив всех данных).
     * При передаче массива работает как сеттер(добавляет этот массив).
     * При передаче значение отправляет параметр с таким ключем.
     * 
     *      $validator->data('id'); // получим значение в ячейке 'id'
     * 
     * @param mixed $data - массив данных или ключ.
     * @return mixed
     */
    public function data($data = NULL) {
        if($data === NULL){
            return $this->_data;
        }else if(is_array($data)){
            Arr::merge($this->_data, $data);
        }else{
            return $this->_data[$data];
        }
        
        return $this;
    }
    
    /**
     * Установка пользовательських сообщений об ошибках.
     * 
     *      ->messages(array('required' => 'Ай-йа-йай, а поле ":label" то пустое!!!'))
     * 
     * @param array $messages - сообщения.
     * @return Validator
     * @throws Easy_Exception
     */
    public function messages(array $messages) {
        foreach ($messages as $key => $val) {
            if(!stripos($val, ':label')){
                throw new Easy_Exception('Сообщения заданы не верно!!!');
            }else{
                $this->_error_configs['messages'][$key] = $val;
            }
        }
        
        return $this;
    }
    
    /**
     * Возвращает ошибки в виде массива. 
     * 
     * @return array
     */
    public function as_array(){
        return $this->_errors;
    }
    
    /**
     * Возвращает ошибки в виде строки. 
     * Присутствует возможность задавать разделители(префикс и суфикс) 
     * 
     * @param string $prefix - разделитель справа.
     * @param string $suffix - разделитель слева.
     * @return string
     */
    public function as_string($prefix = NULL, $suffix = NULL) {
        if (count($this->_errors) === 0){
            return '';
	}
        
        if (empty($prefix)){
            $prefix = $this->_error_configs['delimiters']['prefix'];
    	}

	if (empty($suffix)){
            $suffix = $this->_error_configs['delimiters']['suffix'];
    	}
	
        $str = '';
	foreach ($this->_errors as $error){
            $str .= $prefix.$error.$suffix."\n";
			
	}
		
	return $str;
    }

    /**
     * Проверяет, что значение является не пустым.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function required($str){
        return ! in_array($str, array(NULL, FALSE, '', array()), TRUE);
    }
    
    /**
     * Проверяет, что значение является корректным целым числом,
     * и, при необходимости, входит в определенный диапазон.
     * 
     *      integer(1,5) - значение в диапазоне от 1 до 5 включая 
     *      integer(1) - значение не меньше 1
     *      integer(,5) - значение не больше 5
     * 
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки
     * @return boolean
     */
    protected function integer($str, $params = NULL){
        if(!empty($params)){
            $params = explode(',', $params);
            if(!empty($params['0'])){
                $params += array('min_range' => $params['0']);
            }
            
            if(!empty($params['1'])){
                $params += array('max_range' => $params['1']);
            }
        }

        return filter_var($str, FILTER_VALIDATE_INT, array('options' => $params));
    }
    
    /**
     * Проверяет, что значение является корректным числом с плавающей точкой.
     *
     *      float(.) - проверка является ли "." десятичным разделителем 
     * 
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки.
     * @return boolean
     */
    protected function float($str, $params = NULL){
        if(!empty($params)){
            $params = array('decimal' => trim($params));
        }
	return filter_var($str, FILTER_VALIDATE_FLOAT, array('options' => $params));
    }
    
    
    
    /**
     * Проверяет значение на корректность URL.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function url($str){
	return filter_var($str, FILTER_VALIDATE_URL);
    }
	
	
    /**
     * Проверяет, что значение является корректным e-mail.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function email($str){
	return filter_var($str, FILTER_VALIDATE_EMAIL);
    }
	
	
    /**
     * Проверяет, что значение является корректным IP-адресом.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function ip($str){
	return filter_var($str, FILTER_VALIDATE_IP);
    }
	
    /**
     * Возвращает TRUE для значений "1", "true", "on" и "yes".
     * Иначе - FALSE.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function bool($str){
	return filter_var($str, FILTER_VALIDATE_BOOLEAN);
    }
		
    /**
     * Проверяет значение на наличие символов кроме букв латинского алфавита,
     * а так же символов, которые можно передать в виде параметров.
     * 
     *      alpha(/, _, #) - подходят строки с латинскими буквами и "/", "_", "#"
     *
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки.
     * @return boolean
     */		
    protected function alpha($str, $params = NULL){
        $str = trim($str);
        if(empty($params)){
            $params = '';
        }else{
            $params = explode(',', $params);
            foreach ($params as &$param) {
                $param = preg_quote(trim($param), '/');
            }
            $params = implode($params);
        }
	return (!preg_match("/^([a-z".$params."])+$/i", $str)) ? FALSE : TRUE;
    }
    
    /**
     * Проверяет значение на соответствие переданым значениям.
     * 
     *      matches(password, confirm_password) - проверка на еквивалентность значениям
     *                                            полей password, confirm_pasword
     *
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки.
     * @return boolean
     */		
    protected function matches($str, $params = NULL){
        $params = explode(',', $params);
        $str = trim($str);
        foreach($params as $param){
            $param = trim($param);
            if(trim($this->_data[$param]) !== $str){
                return FALSE;
            }
        }
	return TRUE;
    }
	
    /**
     * Проверяет дату на корректность.
     *
     * @param string $str - значение на проверку.
     * @return boolean
     */
    protected function date($str){
	return (strtotime($str) !== FALSE);
    }
        
    /**
     * Проверяет длину значения.
     * 
     *      length(4,5) - строка в диапазоне от 4 до 5 символов включая,
     *      length(4) - строка не меньше 4 символов,
     *      length(,5) - строка не больше 5 символов.
     *
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки.
     * @return boolean
     */	  
    protected function length($str, $params = NULL){
        if(!empty($params)){
            $params = explode(',', $params);
            foreach ($params as &$value) {
                $value = trim($value);
            }
            $str = trim($str);
            if(!empty($params[0]) and (strlen($str) < $params[0])){
                return FALSE; 
            }

            if(!empty($params[1]) and (strlen($str) > $params[1])){
                return FALSE; 
            }
        }
        return TRUE;
    }

    /**
     * Проверяет длину значения.
     * 
     *      exact_length(4) - строка должна быть равна 4 символам,
     *      exact_length(2,5,56) - строка должна быть равно 2 или 5 или 56 символам.
     *
     * @param string $str - значение на проверку.
     * @param string $params - параметры в виде строки.
     * @return boolean
     */
    protected function exact_length($str, $params = NULL){
        if (!empty($params)){
            $params = explode(',', $params);
            $str = trim($str);
            foreach ($params as $param){
                if (strlen($str) === (int)(trim($param))){
                    return TRUE;
		}
            }
            return FALSE;
        }
        
	return TRUE;
    }
}