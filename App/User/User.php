<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:30 PM
 */

namespace App\User;

use App\DBManagers\DBUser;
use App\Managers\Translation;
use App\Managers\UserModule;
use App\Utils;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Slim\Http\Request;
use Slim\Http\Response;

class User
{
    /** @var \dbaccess */
    protected $database;

    /** @var DBUser $DBUser */
    private $dbUser;

    public $language_id = 0;

    /** @var string $language */
    public $language_code;

    /** @var string $language_name */
    public $language_name;

    /**
     * @param DBUser $db_user
     * @param \dbaccess $database
     */
    public function __construct($db_user, $database)
    {
        $this->database = $database;
        $this->dbUser = $db_user;
    }

    public function getId()
    {
        return $this->dbUser->id;
    }

    public function hasAdmin()
    {
        return false;
    }

    public function hasCRUD()
    {
        return false;
    }

    public function hasEDIT()
    {
        return false;
    }

    public function hasVIEW()
    {
        return false;
    }

    public function isLogged()
    {
        return true;
    }

    public function isPasswordExpire()
    {
        $exp_date = $this->dbUser->pass_date_expire;

        return ($this->dbUser->is_pass_expire == 1 && $exp_date->add(new \DateInterval('P30D')) <= (new \DateTime()));
    }

    public function getUsername()
    {
        return $this->dbUser->login_name;
    }

    public function getNames()
    {
        return $this->dbUser->first_name . ' ' . $this->dbUser->last_name;
    }

    public function getEmail()
    {
        return $this->dbUser->login_name;
    }

    public function getModules()
    {
        return UserModule::getInstance($this->getId(), $this->database)->modules;
    }

    public function getLanguage()
    {
        return $this->language_id;
    }

    public function getCurrentLanguageCode()
    {
        return $this->language_code;
    }

    public function getLanguageName()
    {
        return $this->language_name;
    }

    /**
     * @param Response $response
     * @param string $host
     * @param $id
     * @return mixed
     */
    public function setLanguage($response, $host, $id)
    {
        $response = FigResponseCookies::set($response, SetCookie::create('language')->withValue($id)->rememberForever()->withPath('/')->withDomain($host));

        return $response;
    }

    public function getDefaultLanguage()
    {
        return $this->dbUser->default_language;
    }

    /**
     * @param Request $req
     */
    public function chooseLanguage($req)
    {
        $language_list = Translation::getInstance($this->database)->getLanguageList();

        $cookie_obj = FigRequestCookies::get($req, 'language');
        $cookie = $cookie_obj->getValue();

        if (isset($cookie) && is_numeric($cookie) && Utils::in_array_recur($cookie, $language_list)) {

            foreach ($language_list as $value) {
                if ($value['id'] == $cookie) {
                    $this->language_id = $value['id'];
                    $this->language_code = $value['code'];
                    $this->language_name = $value['name'];
                }
            }

        } else {
            $key = array_search($this->dbUser->default_language, array_column($language_list, 'id'));
            $this->language_id = $language_list[$key]['id'];
            $this->language_code = $language_list[$key]['code'];
            $this->language_name = $language_list[$key]['name'];
        }
    }
}