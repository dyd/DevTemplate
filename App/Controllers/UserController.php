<?php

namespace App\Controllers;

use App\DBManagers\DBLanguage;
use App\DBManagers\DBSession;
use App\DBManagers\DBUser;
use App\DBManagers\DBUserModule;
use App\DBManagers\DBUserPasswordReset;
use App\Managers\Mail;
use App\Managers\Translation;
use App\Managers\User;
use App\Utils;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Monolog\Handler\Curl\Util;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;
use Slim\Views\Twig;

/**
 * Class UserController
 * @package App\Controllers
 *
 * @property Twig view
 * @property Translation translation
 * @property \dbaccess database
 * @property User manager_user
 * @property DBUser db_user
 * @property Router router
 * @property Messages flash
 * @property \App\User\User user
 * @property  Mail manager_mail
 */
class UserController extends Controller
{
    private $responseData;

    public function __construct($container)
    {
        $this->responseData['pageTitle'] = $container->translation->getTranslation('Users', 'usersTitle');
        parent::__construct($container);
    }

    /**
     * @param $request
     * @param $response
     * @return ResponseInterface
     */
    public function usersEvents($request, $response)
    {
        return $this->view->render($response, 'content/user/user_events.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function usersAjaxEvents($request, $response)
    {
        $responseData = ['st' => 1];

        $users = $this->manager_user->return_active();

        if ($users) {
            $responseData['users'] = $users;
        } else {
            $responseData['msg'] = $this->translation->getTranslation("Users", 'noActiveUsers');
        }
        return $response->withJson($responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function getUserAdd($request, $response)
    {
        $this->responseData['language'] = $this->translation->getLanguageList();

        return $this->view->render($response, 'content/user/user_add.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userAjaxAdd($request, $response)
    {
        $parameters = $request->getParsedBody();

        $output = User::loadFromPost($parameters, $this->database);

        if ($output['st'] == 1) {

            $check_user = DBUser::loadFromUsername($output['login_name'], $this->database);
            if ($check_user) {
                //User Exists
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorDuplicate')]);
            }

            if (array_key_exists('module', $output)) {
                foreach ($output['module'] as $value) {
                    if (Utils::in_array_recur($value, $this->user->getModules())) {

                    } else {
                        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorModuleAccess')]);
                    }
                }
            }

            $obj_user = DBUser::loadFromValidatedArray($output, $this->database);
            $user_id = $obj_user->saveAsNew();

            if (is_numeric($user_id)) {
                $obj_module = DBUserModule::loadFromValidatedArray($output, $user_id, $this->database);
                /** @var DBUserModule $value */
                foreach ($obj_module as $value) {
                    $value->saveAsNew();
                }
            } else {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }

            //Password Creation
            $obj_pass = new DBUserPasswordReset($this->database);
            $obj_pass->login_name = $obj_user->login_name;
            $obj_pass->key = $this->manager_user->generateToken();
            $reset_id = $obj_pass->saveAsNew();
            if (is_numeric($reset_id)) {

                Translation::initialize($obj_user->default_language, $this->database);

                $title = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeTitle');
                $subject = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeSubject');

                $body = '<h2>' . Translation::getInstance($this->database)->getTranslation('Users', 'username') . ': ' . $obj_user->login_name . '</h2><br><br>' .
                    Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeBody') . '<br /><br />' .
                    '<a href="' . $this->router->pathFor('user.page.setPass', ['id' => $obj_pass->key]) . '">' .
                    Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeURL') . '</a>';

                $mail_response = $this->manager_mail->sendWelcomeMail(
                    $obj_user->email,
                    $obj_user->first_name . ' ' . $obj_user->last_name,
                    $title,
                    $subject,
                    $body
                );

                if ($mail_response) {
                    return $response->withJson(['st' => 1]);
                } else {
                    return $response->withJson(['st' => 0, 'notsent' => 0]);
                }

            } else {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }

        } else {
            return $response->withJson($output);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function userResetPassPage($request, $response)
    {
        return $this->view->render($response, 'content/user/user_set_password.twig', ['id' => $request->getAttribute('id')]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function userAjaxResetPass($request, $response)
    {
        $params = $request->getParsedBody();

        if (!Utils::validateUsername($params['loginName'])) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorResetPassLoginName')]);
        }

        if (trim($params['password']) !== trim($params['pass2'])) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userResetPassRequirePass2')]);
        }

        DBUserPasswordReset::garbageCollector($this->database);

        $obj_token = DBUserPasswordReset::loadByToken($params['id'], $this->database);

        if ($obj_token) {

            if ($obj_token->login_name !== $params['loginName']) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorResetPassInvalidUserToken')]);
            }

        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorResetPassInvalidToken')]);
        }

        if (!Utils::validatePassword($params['password'])) {
            return $response->withJson(
                [
                    'st' => 3,
                    'msg' => '<h4>' . $this->translation->getTranslation('Users', 'userResetPassRequireTitle') . '</h4>' .
                        '<div class="row"><div class="col"><ol>' . $this->translation->getTranslation('Users', 'userResetPassRequireBody') . '</ol></div></div>'
                ]
            );
        }

        $obj_user = DBUser::loadFromUsername($obj_token->login_name, $this->database);

        if (!$obj_user) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorMissingUser')]);
        }

        $obj_user->password = password_hash($params['password'], PASSWORD_BCRYPT);
        $obj_user->pass_date_expire = new \DateTime();

        if ($obj_user->save()) {

            $obj_token->is_used = 1;
            $obj_token->usage_date = new \DateTime();
            if ($obj_token->save()) {
                //TODO ADD LOGGING
            }

            return $response->withJson(['st' => 1]);
        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function userExpirePassPage($request, $response)
    {
        if ($request->isGet()) {
            $this->responseData['pageTitle'] = $this->translation->getTranslation('Password', 'expirePassTitle');

            return $this->view->render($response, 'content/user/password_expire.twig', $this->responseData);
        }

        if ($request->isPost()) {

            $params = $request->getParsedBody();

            if ($params['password'] !== $params['pass2']) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Password', 'expiredPassword')]);
            }

            if (!Utils::validatePassword($params['password'])) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Password', 'invalidPassword')]);
            }

            $obj = DBUser::loadFromId($this->user->getId(), $this->database);
            if (!$obj) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }

            if (password_verify($params['password'], $obj->password)) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Password', 'duplicatePassword')]);
            }

            $obj->password = password_hash($params['password'], PASSWORD_BCRYPT);
            $obj->pass_date_expire = new \DateTime();

            if ($obj->save()) {
                return $response->withJson(['st' => 1]);
            } else {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }
        }

        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function userList($request, $response)
    {
        return $this->view->render($response, 'content/user/user_list.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function userAjaxList($request, $response)
    {
        $postData = $request->getParams();

        $order = array();
        $search = '';
        $pagination = array();

        if (array_key_exists('order', $postData)) {
            switch ($postData['order'][0]['column']) {
                case '0' :
                    $order['column'] = 'login_name';
                    break;
                case '1':
                    $order['column'] = 'names';
                    break;
                case '2':
                    $order['column'] = 'email';
                    break;
                case '3':
                    $order['column'] = 'msisdn';
                    break;
                case '4':
                    $order['column'] = 'valuta';
                    break;
                case '5':
                    $order['column'] = 'rights';
                    break;
                case '6':
                    $order['column'] = 'creation_date';
                    break;
                case '7':
                    $order['column'] = 'status';
                    break;
                default :
                    $order['column'] = 'login_name';
                    break;
            }
            if ($postData['order'][0]['dir'] === 'asc' || $postData['order'][0]['dir'] === 'desc') {
                $order['dir'] = strtoupper($postData['order'][0]['dir']);
            } else {
                $order['dir'] = 'ASC';
            }
        } else {
            $order['column'] = 'login_name';
            $order['dir'] = 'ASC';
        }

        if (array_key_exists('search', $postData)) {
            if ($postData['search']['value'] != '') {
                $search = $postData['search']['value'];
            }
        }

        if (array_key_exists('start', $postData)) {
            $pagination['start'] = (int)$postData['start'];
        }

        if (array_key_exists('length', $postData)) {
            $pagination['length'] = (int)$postData['length'];
        }

        $users = $this->manager_user->loadUserList($pagination, $search, $order, $this->database);
        $this->responseData = [];
        $this->responseData['draw'] = (int)$postData['draw'];

        $this->responseData['recordsTotal'] = $users['filtered_pagination'];
        $this->responseData['recordsFiltered'] = $users['filtered'];
        unset($users['filtered_pagination']);
        unset($users['filtered']);

        $this->responseData['data'] = $users;

        return $response->withJson($this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     */
    public function userPage($request, $response, $args)
    {
        $userId = $args['id'];
        $user = DBUser::loadFromId($userId, $this->database);

        if (!$user) {
            $this->flash->addMessage('warning', $this->translation->getTranslation('System', 'invalidID'));
            return $response->withRedirect($this->router->pathFor('user.list'));
        }
        $this->responseData['user'] = $user;

        return $this->view->render($response, 'content/user/user.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userAjaxResetPassByUser($request, $response)
    {
        $userId = $request->getParsedBody()['userId'];

        if (!Utils::validateNumber($userId)) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
        }

        $obj = DBUser::loadFromId($userId, $this->database);
        if (!$obj) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
        }

        $obj_pass = new DBUserPasswordReset($this->database);
        $obj_pass->login_name = $obj->login_name;
        $obj_pass->key = $this->manager_user->generateToken();
        $reset_id = $obj_pass->saveAsNew();

        if (is_numeric($reset_id)) {
            Translation::initialize($obj->default_language, $this->database);

            $title = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeTitle') . ' ' . $obj->first_name . ' ' . $obj->last_name;
            $subject = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailResetSubject');
            $body = '<h2>' . Translation::getInstance($this->database)->getTranslation('Users', 'username') . ': ' . $obj->login_name . '</h2><br><br>' .
                Translation::getInstance($this->database)->getTranslation('Users', 'userEmailResetBody') . '<br><br>' .
                '<a href="' . $this->router->pathFor('user.page.setPass', ['id' => $obj_pass->key]) . '">' . Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeURL') . '</a>';

            if ($this->manager_mail->sendDocumentNotification(
                $obj->email,
                $obj->first_name . ' ' . $obj->last_name,
                $title,
                $subject,
                $body
            )
            ) {

                $sessions = DBSession::loadFromPersonId($obj->id, $this->database);
                if ($sessions) {
                    /** @var DBSession $value */
                    foreach ($sessions as $value) {
                        $value->delete();
                    }
                }

                return $response->withJson(['st' => 1]);

            } else {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }

        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userAjaxDelete($request, $response)
    {
        $userId = $request->getParsedBody()['userId'];

        if (!Utils::validateNumber($userId)) {
            return $response->withJson(["st" => 3, "msg" => $this->translation->getTranslation('system', 'invalidID')]);
        }

        $obj = DBUser::loadFromId($userId, $this->database);
        if (!$obj) {
            return $response->withJson(["st" => 3, "msg" => $this->translation->getTranslation('system', 'invalidID')]);
        }

        $obj->is_del = 1;
        if ($obj->save()) {
            $sessions = DBSession::loadFromPersonId($obj->id, $this->database);
            if ($sessions) {
                /** @var DBSession $session */
                foreach ($sessions as $session) {
                    $session->delete();
                }
            }
        }
        return $response->withJson(['st' => 1]);
    }

    private function resetUserPass($userId)
    {
        if (!Utils::validateNumber($userId)) {
            return ["st" => 3, "msg" => $this->translation->getTranslation('system', 'invalidID')];
        }

        $obj = DBUser::loadFromId($userId, $this->database);
        if (!$obj) {
            return ["st" => 3, "msg" => $this->translation->getTranslation('system', 'invalidID')];
        }

        //SEND RESET PASS MAIL
        //Password Creation Mechanic

        $obj_pass = new DBUserPasswordReset($this->database);
        $obj_pass->login_name = $obj->login_name;
        $obj_pass->key = User::getInstance($this->database)->generateToken();
        $reset_id = $obj_pass->saveAsNew();

        if (is_numeric($reset_id)) {

            $title = $this->translation->getTranslation('Users', 'userEmailWelcomeTitle') . ' ' . $obj->first_name . ' ' . $obj->last_name;
            $subject = $this->translation->getTranslation('Users', 'userEmailResetSubject');
            $body = '<h2>' . $this->translation->getTranslation('Users', 'username') . ': ' . $obj->login_name . '</h2><br/><br/>' .
                $this->translation->getTranslation('Users', 'userEmailResetBody') . '<br /><br />' .
                '<a href="' . $this->router->pathFor('users') . '">' . $this->translation->getTranslation('Users', 'userEmailWelcomeURL') . '</a>';


            //SEND EMAIL
            if ($this->manager_mail->sendWelcomeMail(
                $obj->email,
                $obj->first_name . ' ' . $obj->last_name,
                $title,
                $subject,
                $body
            )
            ) {
                // DELETE ALL SESSIONS
                $sessions = DBSession::loadFromPersonId($obj->id, $this->database);
                if ($sessions) {
                    /** @var DBSession $session */
                    foreach ($sessions as $session) {
                        $session->delete();
                    }
                }

                return ["st" => 1];
            } else {
                return [
                    "st" => 3,
                    "msg" => $this->translation->getTranslation('System', 'systemError')
                ];
            }
        } else {
            return [
                "st" => 3,
                "msg" => $this->translation->getTranslation('System', 'systemError')
            ];
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface|static
     */
    public function getUserEdit($request, $response)
    {
        $id = (int)$request->getAttribute('id');

        $user = DBUser::loadFromId($id, $this->database);

        if (!$user) {
            $this->flash->addMessage('warning', $this->translation->getTranslation('System', 'invalidID'));
            return $response->withRedirect($this->router->pathFor('user.list'));
        }

        $this->responseData['user'] = $user;
        $this->responseData['action'] = 'edit';
        $this->responseData['language'] = $this->translation->getLanguageList();

        return $this->view->render($response, 'content/user/user_edit.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function  userAjaxEdit($request, $response)
    {
        $user_id = $request->getAttribute('id');
        $parameters = $request->getParsedBody();

        $output = User::loadFromPost($parameters, $this->database);

        if ($output['st'] == 1) {

            $exists_user = DBUser::loadFromId($user_id, $this->database);
            if ($exists_user) {

                $output['user_id'] = $user_id;

            } else {
                return $response->withJson(['st' => 3, $this->translation->getTranslation('System', 'invalidID')]);
            }

            $query = "SELECT * FROM " . DBUser::$DB_NAME . " WHERE lower(login_name) = '" . $exists_user->login_name . "' AND id != '" . $exists_user->id . "';";
            $this->database->execute($query);

            if ($this->database->error()) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
            }

            if ($this->database->rows() > 0) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorDuplicate')]);
            }

            if (array_key_exists('module', $output)) {
                foreach ($output['module'] as $value) {
                    if (!Utils::in_array_recur($value, $this->user->getModules())) {
                        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Users', 'userErrorModuleAccess')]);
                    }
                }
            }

            $obj_user = DBUser::loadFromValidatedArray($output, $this->database);
            $obj_user->login_name = $exists_user->login_name;
            $obj_user->id = $exists_user->id;
            $obj_user->password = $exists_user->password;
            $obj_user->creation_date = $exists_user->creation_date;

            if ($obj_user->is_pass_expire && $obj_user->is_pass_expire != $exists_user->is_pass_expire) {
                $obj_user->pass_date_expire = new \DateTime();
            } else {
                $obj_user->pass_date_expire = $exists_user->pass_date_expire;
            }

            $user_id = $obj_user->save();

            if ($user_id) {
                if (array_key_exists('module', $output)) {
                    //Load Existing modules and remove them
                    foreach ($exists_user->modules as $value) {
                        $obj = new DBUserModule($this->database);
                        $obj->id = $value['id'];
                        $obj->delete();
                    }

                    $obj_module = DBUserModule::loadFromValidatedArray($output, $exists_user->id, $this->database);

                    /** @var DBUserModule $value */
                    foreach ($obj_module as $value) {
                        $value->saveAsNew();
                    }
                }

                return $response->withJson(['st' => 1]);
            }
        } else {
            return $response->withJson($output);
        }

        return $response->withJson(['st' => 3, 'msg' => json_encode($parameters + ['id' => $user_id])]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userAjaxForgottenPass($request, $response)
    {
        $params = $request->getParsedBody();

        if (!Utils::validateUsername($params['username'])) {
            return $response->withJson(['st' => 0, 'username' => 0]);
        }

        if (!Utils::validateEmail($params['email'])) {
            return $response->withJson(['st' => 0, 'email' => 0]);
        }

        $obj = DBUser::loadFromUsername($params['username'], $this->database);
        if (!$obj) {
            return $response->withJson(['st' => 5, "msg" => $this->translation->getTranslation('System', 'invalidData')]);
        }

        if ($obj->email !== strtolower(trim($params['email']))) {
            $response->withJson(['st' => 5, 'msg' => $this->translation->getTranslation('System', 'invalidData')]);
        }

        DBUserPasswordReset::garbageCollectorByUsername($params['username'], $this->database);

        $obj_reset = new DBUserPasswordReset($this->database);
        $obj_reset->login_name = $obj->login_name;
        $obj_reset->key = $this->manager_user->generateToken();
        $reset_id = $obj_reset->saveAsNew();

        if (!is_numeric($reset_id)) {
            return $response->withJson(['st' => 5, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }

        Translation::initialize($obj->default_language, $this->database);
        $title = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeTitle') . ' ' . $obj->first_name . ' ' . $obj->last_name;
        $subject = Translation::getInstance($this->database)->getTranslation('Users', 'userEmailResetSubject');
        $body = '<h2>' . Translation::getInstance($this->database)->getTranslation('Users', 'username') . ': ' . $obj->login_name . '</h2>' .
            '<br><br>' . Translation::getInstance($this->database)->getTranslation('Users', 'userEmailResetBody') . '<br><br>' .
            '<a href="' . $this->router->pathFor('user.page.setPass', ['id' => $obj_reset->key]) . '">' . Translation::getInstance($this->database)->getTranslation('Users', 'userEmailWelcomeURL') . '</a>';

        if ($this->manager_mail->sendWelcomeMail(
            $obj->email,
            $obj->first_name . ' ' . $obj->last_name,
            $title,
            $subject,
            $body
        )
        ) {
            return $response->withJson(['st' => 1]);
        } else {
            return $response->withJson(['st' => 5, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function changeLanguage($request, $response)
    {
        $lang_id = $request->getAttribute('id');

        if (is_numeric($lang_id)) {
            $obj = DBLanguage::loadById($lang_id, $this->database);
            if ($obj) {
                $uri = $request->getUri();
                $host = $uri->getHost();

                $response = $this->user->setLanguage($response, $host,$obj->id);

                $referer = $request->getHeader("HTTP_REFERER");
                if ($referer && is_array($referer) && count($referer) >= 1) {
                    $uri = $referer[0];
                } else {
                    $uri = $this->router->pathFor('home');
                }

                return $response->withRedirect($uri);
            }
        }

        $this->flash->addMessage('error', $this->translation->getTranslation('System', 'systemError'));
        return $response->withRedirect($this->router->pathFor('home'));
    }
}