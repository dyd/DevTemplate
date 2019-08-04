<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 16.7.2018 г.
 * Time: 17:35
 */
namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

/**
 * @property  Router router
 */
class StaticPathMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke($request, $response, $next)
    {

        $this->router->setBasePath($request->getUri()->getBaseUrl());

        $response = $next($request, $response);
        return $response;
    }
}