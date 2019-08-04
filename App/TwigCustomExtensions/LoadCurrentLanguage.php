<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 26.6.2018 г.
 * Time: 15:51
 */

namespace App\TwigCustomExtensions;

use App\Managers\Translation;
use App\User\User;
use App\Managers\UserModule;

class LoadCurrentLanguage extends \Twig_Extension
{
    /** @var  Translation $language */
    protected $language;

    /** @var  User $user */
    protected $user;

    /** @var  array $language_list */
    protected $language_list;

    /** @var  \dbaccess $database */
    protected $database;

    public function __construct($container)
    {
        $this->language = $container['translation'];
        $this->user = $container['user'];
        $this->database = $container['database'];

        if ($this->language instanceof Translation) {
            $this->language_list = $this->language->getLanguageList();
        } else {
            $this->language_list = [];
        }
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_Translation', array($this, 'getTranslation')),
            new \Twig_SimpleFunction('get_LeftMenu', array($this, 'getLeftMenu')),
            new \Twig_SimpleFunction('get_LanguageList', array($this, 'getLanguageList')),
            new \Twig_SimpleFunction('get_CurrentLanguage', array($this, 'getCurrentLanguage')),
        ];
    }

    public function getTranslation($section, $key)
    {
        return $this->language->getTranslation($section, $key);
    }

    public function getLeftMenu()
    {
        return UserModule::getInstance($this->user->getId(), $this->database)->outputHTMLModules($this->user->getModules());
    }

    public function getLanguageList()
    {
        return $this->language_list;
    }

    public function getCurrentLanguage()
    {
        return $this->language_list[array_search($this->user->getLanguage(), array_column($this->language_list, 'id'))];
    }
}