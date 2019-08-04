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
use Slim\Route;
use Slim\Router;

/**
 * @property  Router router
 */
class AuthMiddleware extends Middleware
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

            if ($user->isPasswordExpire() && (string)$request->getUri() != $this->router->pathFor('user.page.expirePass')) {
                return $response->withRedirect($this->router->pathFor('user.page.expirePass'));
            }

        } else {
            return $response->withRedirect($container->router->pathFor('auth.signIn'));
        }

        $response = $next($request, $response);
        return $response;
    }
}