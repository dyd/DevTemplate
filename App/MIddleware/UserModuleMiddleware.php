<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 19.7.2018 г.
 * Time: 15:43
 */
namespace App\Middleware;

use App\Managers\Translation;
use App\Managers\UserModule;
use App\User\User;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

/**
 * @property  Router router
 * @property  User user
 * @property  UserModule user_module
 * @property  Messages flash
 * @property  Translation translation
 */
class UserModuleMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke($request, $response, $next)
    {
        $uri = "/" . $request->getUri()->getPath();
        $uri = str_replace("//", "/", $uri);

        $uri_path = explode('/', $uri);

        if ($uri_path && is_array($uri_path)) {
            $path = $uri_path[1];
        } else {
            $path = '';
        }

        if (!UserModule::moduleRemoteRights($this->user->getModules(), $path)) {
            if ($request->isXhr()) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'missingAccess')]);
            } else {
                $this->flash->addMessage('warning', $this->translation->getTranslation('System', 'missingAccess'));
                return $response->withRedirect($this->router->pathFor('home'));
            }
        }

        $response = $next($request, $response);
        return $response;
    }
}