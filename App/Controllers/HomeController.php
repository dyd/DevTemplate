<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 25.6.2018 г.
 * Time: 15:31
 */

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;
use Slim\Views\Twig;

/**
 * @property  Router router
 * @property  Twig view
 * @property  translation
 */
class HomeController extends Controller
{

    public function home($request, $response)
    {
        //$user = DBUser::loadFromId('1', $this->database);
        //var_dump($user);

        //var_dump($this->user);
        //var_dump($this->translation);

        //die();
        return $this->view->render($response, 'home.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function landingPage($request, $response)
    {
        //return $response->withRedirect($this->router->pathFor('auth.signIn'));
        return $this->view->render($response, 'landing_page.twig');
    }
}
