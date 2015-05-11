<?php
    return array(
        // Обработчик исключений
        'exception_handler' => array('Easy\\Core\\Easy', 'exceptionHandler'),
        
        // Обработчик ошибок 
        'error_handler' => array('Easy\\Core\\Easy', 'errorHandler'),
        
        // Базовый URL
        'base_url' => '/',
        
        // Базовый шаблон
        'template' => 'default',
        
        // Префикс для переменных шаблона
        'view_prefix' => '',
        
        // Кодировка ввода и вывода данных
        'charset' => 'utf-8',
        
        // Пользовательськие пути 
        'path' => array()
    );