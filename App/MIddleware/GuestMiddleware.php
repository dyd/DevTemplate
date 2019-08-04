<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 27.6.2018 г.
 * Time: 14:57
 */

namespace App\Middleware;

use App\User\User;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class GuestMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return mixed
     */
    public function __invoke($request, $response, $next)
    {
        /** @var Container $container */
        $container = $this->container;

        /** @var User $user */
        $user = $this->container->user;

        if ($user->isLogged()) {
            return $response->withRedirect($container->router->pathFor('home'));
        }

        $response = $next($request, $response);
        return $response;
    }
}