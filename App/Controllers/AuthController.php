<?php

namespace App\Controllers;

use App\DBManagers\DBSession;
use App\DBManagers\DBUser;
use App\DBManagers\DBUserSessionLog;
use App\DBManagers\DBUserUnsuccessLogin;
use App\Managers\Translation;
use App\Utils;
use DateTime;
use dbaccess;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;
use Slim\Views\Twig;

/**
 * @property Twig view
 * @property Messages flash
 * @property Translation translation
 * @property Router router
 * @property dbaccess database
 */
class AuthController extends Controller
{
    /**
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function getSignIn($request, $response)
    {
        return $this->view->render($response, 'signin.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function postSignIn($request, $response)
    {
        $username = $request->getParam('username');
        $password = $request->getParam('password');

        if (Utils::validateUsername($username) && Utils::validatePassword($password)) {

        } else {
            $this->flash->addMessage('error', $this->translation->getTranslation('LogIn', 'invalidData'));

            return $response->withRedirect($this->router->pathFor('auth.signIn'));
        }

        $obj_usr = DBUser::loadFromUsername($username, $this->database);

        if ($obj_usr) {

            if ($obj_usr->is_del == 1 || $obj_usr->status == USER_STATUS_NOT_ACTIVE) {

                $this->flash->addMessage('error', $this->translation->getTranslation('LogIn', 'invalidData'));

                return $response->withRedirect($this->router->pathFor('auth.signIn'));
            }

            $_SESSION['session_id'] = session_id();
            $_SESSION['sess_name'] = session_name();


            if (Utils::getInstance()->checkRemoteAddrJSON($obj_usr->remote_addr)) {

            } else {
                $obj_log = new DBUserUnsuccessLogin($this->database);
                $obj_log->user_name = $obj_usr->login_name;
                $obj_log->status = 0;
                $obj_log->reason = 'Unauthorized IP';
                $obj_log->saveAsNew();

                $this->flash->addMessage('error', $this->translation->getTranslation('LogIn', 'invalidRemoteAddr'));

                return $response->withRedirect($this->router->pathFor('auth.signIn'));
            }


            if (!password_verify($password, $obj_usr->password)) {

                $obj_log = new DBUserUnsuccessLogin($this->database);
                $obj_log->user_name = $obj_usr->login_name;
                $obj_log->status = 0;
                $obj_log->reason = 'Wrong password';
                $obj_log->saveAsNew();

                $this->flash->addMessage('error', $this->translation->getTranslation('LogIn', 'invalidData'));

                return $response->withRedirect($this->router->pathFor('auth.signIn'));
            }

            $arr_obj = DBSession::loadFromPersonId($obj_usr->id, $this->database);

            if ($arr_obj) {
                /** @var DBSession $value */

                /*TODO This is not working */
                foreach ($arr_obj as $value) {
                    $value->last_update = new DateTime();
                    $value->person_id = 0;

                    $this->flash->addMessage('warning', $this->translation->getTranslation('LogIn', 'sessionGarbageCollector'));

                    $value->session_data = session_encode();
                    $value->save();

                    $this->flash->clearMessages();
                }


                $ses_log = new DBUserSessionLog($this->database);
                $ses_log->user_id = $obj_usr->id;
                $ses_log->status = 'New Login';
                $ses_log->saveAsNew();

                unset($ses_log);
                unset($arr_obj);
            }

            $obj_sess = DBSession::loadFromSessionId(session_id(), $this->database);

            if (!$obj_sess) {
                // Garbage collector case
                $obj_sess = new DBSession($this->database);
                $obj_sess->session_id = session_id();
                $obj_sess->person_id = $obj_usr->id;
                $obj_sess->session_data = session_encode();
                $obj_sess->last_update = new DateTime();

                $res_id = $obj_sess->saveAsNew();
                if (is_numeric($res_id)) {
                    $res_id = true;
                } else {
                    $res_id = false;
                }
            } else {
                $obj_sess->person_id = $obj_usr->id;
                $obj_sess->last_update = new DateTime();
                $res_id = $obj_sess->save();
            }

            if ($res_id) {
                $obj_log = new DBUserUnsuccessLogin($this->database);
                $obj_log->user_name = $obj_usr->login_name;
                $obj_log->status = 1;
                $obj_log->reason = 'Success login';
                $obj_log->saveAsNew();

                $_SESSION['person_id'] = $obj_usr->id;

                return $response->withRedirect($this->router->pathFor('home'));

            } else {

                $this->flash->addMessage('error', $this->translation->getTranslation('system', 'notLogged'));

                return $response->withRedirect($this->router->pathFor('auth.signIn'));
            }

        } else {
            $obj_log = new DBUserUnsuccessLogin($this->database);
            $obj_log->user_name = $username;
            $obj_log->status = 0;
            $obj_log->reason = 'Username does not exists';
            $obj_log->saveAsNew();

            $this->flash->addMessage('error', $this->translation->getTranslation('LogIn', 'invalidData'));

            return $response->withRedirect($this->router->pathFor('auth.signIn'));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function signOut($request, $response)
    {
        session_unset();
        session_destroy();
        return $response->withRedirect($this->router->pathFor('landing.page'));
    }
}