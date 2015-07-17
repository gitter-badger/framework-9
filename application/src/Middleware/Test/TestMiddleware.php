<?php
namespace Lebran\App\Middleware\Test;

use Lebran\Core\Mvc\Middleware;

/**
 * Description of TestMiddlewares
 *
 * @author Roma
 */
class TestMiddleware extends Middleware
{
    public function call()
    {
        $response = $this->next->call();
        
        $response->setBody('Middleware работает йоу йоу йоу!!!');

        return $response;
    }
}