<?php

namespace App\Middleware;

use App\Managers\Translation;
use App\User\User;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ModuleRightsMiddleware
 * @property Messages flash
 * @property Translation translation
 * @property User user
 * @package App\MIddleware
 *
 */
class RightsEDITMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke($request, $response, $next)
    {
        if (!$this->user->hasEDIT()) {
            if ($request->isXhr()) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'missingAccess')]);
            } else {
                $this->flash->addMessage('warning', $this->translation->getTranslation('System', 'missingAccess'));
                return $response->withRedirect($this->container->router->pathFor('home'));
            }
        }

        $response = $next($request, $response);
        return $response;
    }
}