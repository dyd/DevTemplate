<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 28.6.2018 г.
 * Time: 15:42
 */

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

class ErrorsMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke($request, $response, $next)
    {
        if (array_key_exists('errors', $_SESSION)) {
            $this->container->view->getEnvironment()->addGlobal('errors', $_SESSION['errors']);
            unset($_SESSION['errors']);
        }

        $response = $next($request, $response);
        return $response;
    }
}