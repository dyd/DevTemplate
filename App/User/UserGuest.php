<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:42 PM
 */

namespace App\User;


use App\Managers\Translation;
use App\User;
use App\Utils;
use Dflydev\FigCookies\FigRequestCookies;

class UserGuest extends \App\User\User
{
    public function isLogged()
    {
        return false;
    }

    public function getModules()
    {
        return array();
    }

    public function getUsername()
    {
        return '';
    }

    public function getNames()
    {
        return '';
    }

    public function getEmail()
    {
        return '';
    }

    public function getClientId()
    {
        return '';
    }

    public function getClientNames()
    {
        return '';
    }

    public function chooseLanguage($req)
    {
        $language_list = Translation::getInstance($this->database)->getLanguageList();

        $cookie_obj = FigRequestCookies::get($req, 'language');
        $cookie = $cookie_obj->getValue();

        if (isset($cookie) && is_numeric($cookie) && Utils::in_array_recur($cookie, $language_list)) {
            $lang_id = 0;
            foreach ($language_list as $value) {
                if ($value['id'] == $cookie) {
                    $this->language_id = $value['id'];
                    $this->language_code = $value['code'];
                    $this->language_name = $value['name'];
                }
            }

            return $lang_id;
        } else {
            // set default language
            foreach ($language_list as $value) {
                if ($value['code'] == 'EN') {
                    $this->language_id = $value['id'];
                    $this->language_code = $value['code'];
                    $this->language_name = $value['name'];
                }
            }
        }

        return 0;
    }
}