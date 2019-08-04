<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 2:31 PM
 */

namespace App\Managers;


use App\DBManagers\DBUser;
use App\DBManagers\DBUserModule;
use App\Utils;
use Slim\Router;

class UserModule
{
    /** @var  string $DB_NAME */
    public static $DB_NAME = 'module';

    /**
     * @var UserModule $instance
     */
    private static $instance;

    /** @var  \dbaccess */
    private $database;

    /** @var  array $modules */
    public $modules;

    /** @var  int $user_id */
    private $user_id;

    /** @var  Router $router */
    protected static $router;

    /** @var array $translation */
    protected static $translation;

    /**
     *
     *
     * @return UserModule - User instance
     */

    /**
     * Returns UserModule instance
     *
     * @param $user_id
     * @param \dbaccess $database
     * @return UserModule
     */
    public static function getInstance($user_id, $database)
    {
        if (null === static::$instance) {
            static::$instance = new static($user_id, $database);
        }

        return static::$instance;
    }

    /**
     * setup the UserModule Class
     * @param $container
     */
    public static function initialize($container)
    {
        self::$router = $container->router;

        /** @var Translation $tr */
        $tr = $container['translation'];

        self::$translation = $tr->getInstance($container['database'])->getSection('Menu');
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * UserModule instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * UserModule constructor
     *
     * @param $user_id
     * @param \dbaccess $database
     */
    protected function __construct($user_id, $database)
    {
        $this->database = $database;

        $this->user_id = $user_id;
        $this->modules = $this->getModules($this->user_id);

    }

    /**
     * @param $user_id
     * @return array
     */
    public function getModules($user_id)
    {
        $user_id = $this->database->escape($user_id);

        //TODO THAT QUERY IS NOT OK!!!
        $query = "SELECT r.id, m.names,m.uri_path, array_to_json(m.remote_addr) AS remote_addr, m.id AS module_id
					FROM " . DBUserModule::$DB_NAME . " AS r
					INNER JOIN " . self::$DB_NAME . " AS m ON (r.module_id=m.id)
					INNER JOIN " . DBUser::$DB_NAME . " AS u ON (u.id=r.user_id)
					WHERE r.user_id='" . $user_id . "'
					ORDER BY m.positions ASC;";
        $res = $this->database->execute($query);
        if ($this->database->error()) {
            //TODO ERROR MAILER
            //$this->mail_me_error($this->database->error().'\n\n'.$query,'Извличане на модулите');
            return array();
        }

        if ($this->database->rows() > 1) {
            return $res;
        }

        return array();
    }

    /**
     * @param array $modules
     * @param string $script
     * @return bool
     */
    public static function moduleRemoteRights($modules, $script)
    {
        if (is_array($modules)) {
            //find current module
            $id_m = array_search($script, array_column($modules, 'uri_path'));
            if ($id_m !== false) {
                if (Utils::getInstance()->checkRemoteAddrJSON(json_decode($modules[$id_m]['remote_addr'], TRUE))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search into Database From module name and returns its ID
     *
     * @param $module
     * @param \dbaccess $database
     * @return bool
     */
    public static function getModuleIdByName($module, $database)
    {
        $module = $database->escape($module);
        $query = "SELECT id FROM " . UserModule::$DB_NAME . " WHERE names='" . $module . "'";
        $res = $database->execute($query);
        if ($database->error()) {
            return false;
        }

        if ($database->rows() == 1) {
            return $res[0]['id'];
        }

        return false;
    }

    /**
     * @param $modules
     * @return string
     */
    public function outputHTMLModules($modules)
    {
        $allowed_modules = array();

        if (Utils::in_array_recur('process_file', $modules)) {
            $upload = $this->outputProcessFile();

            if ($upload != '') array_push($allowed_modules, $upload);
        }

        if (Utils::in_array_recur('upload', $modules)) {
            $upload = $this->outputUpload();

            if ($upload != '') array_push($allowed_modules, $upload);
        }

        if (Utils::in_array_recur('users', $modules)) {

            $users = $this->outputUsers();

            if ($users != '') array_push($allowed_modules, $users);

        }

        if (Utils::in_array_recur('translations', $modules)) {

            $translations = $this->outputTranslations();

            if ($translations != '') array_push($allowed_modules, $translations);

        }

        $output = '';
        foreach ($allowed_modules as $module) {
            $output .= $module;
        }
        return $output;
    }

    private function outputUsers()
    {
        $filename = $this->extractModuleUrlByName('users', $this->modules);

        if ($this->moduleRemoteRights($this->modules, $filename)) {
            $active = (basename($_SERVER['SCRIPT_FILENAME']) == 'users.php') ? 'active' : '';
            return '<li class="' . $active . '" >' .
            '<a href="#" class="has-arrow " aria-expanded="false"><i class="fa fa-user"></i><span class="hide-menu">' .
            self::$translation['user'] . '</span></a>' .
            '<ul aria-expanded="false" class="collapse">' .
            '<li><a href="' . self::$router->pathFor('users.events') . '">' . self::$translation['overview'] . '</a></li>' .
            '<li><a href="' . self::$router->pathFor('user.add') . '">' . self::$translation['add'] . '</a></li>' .
            '<li><a href="' . self::$router->pathFor('user.list') . '">' . self::$translation['list'] . '</a></li>' .
            '</ul></li>';
        }

        return '';
    }

    private function outputTranslations()
    {
        $filename = $this->extractModuleUrlByName('translations', $this->modules);

        if ($this->moduleRemoteRights($this->modules, $filename)) {
            $active = (basename($_SERVER['SCRIPT_FILENAME']) == 'translations.php') ? 'active' : '';
            return '<li class="' . $active . '">' .
            '<a class="has-arrow " href="#" aria-expanded="false"><i class="fa fa-language"></i><span class="hide-menu">' .
            self::$translation['language'] . '</span></a>' .
            '<ul aria-expanded="false" class="collapse">' .
            '<li><a href="' . self::$router->pathFor('language.add') . '">' . self::$translation['add'] . '</a></li>' .
            '<li><a href="' . self::$router->pathFor('languages') . '">' . self::$translation['list'] . '</a></li>' .
            '</ul></li>';
        }

        return '';
    }

    private function outputUpload()
    {
        $filename = $this->extractModuleUrlByName('upload', $this->modules);

        if ($this->moduleRemoteRights($this->modules, $filename)) {
            $active = (basename($_SERVER['SCRIPT_FILENAME']) == 'upload.php') ? 'active' : '';
            return '<li class="' . $active . '">' .
            '<a class="has-arrow " href="#" aria-expanded="false"><i class="fa fa-upload"></i><span class="hide-menu">' .
            self::$translation['upload'] . '</span></a>' .
            '<ul aria-expanded="false" class="collapse">' .
            '<li><a href="' . self::$router->pathFor('home') . '">' . self::$translation['list'] . '</a></li>' .
            '<li><a href="' . self::$router->pathFor('home') . '">' . self::$translation['add'] . '</a></li>' .
            '</ul></li>';
        }

        return '';
    }

    private function outputProcessFile()
    {
        $filename = $this->extractModuleUrlByName('process_file', $this->modules);

        if ($this->moduleRemoteRights($this->modules, $filename)) {
            $active = (basename($_SERVER['SCRIPT_FILENAME']) == 'process_file.php') ? 'active' : '';
            return '<li class="' . $active . '">' .
            '<a href="' . self::$router->pathFor('home') . '" aria-expanded="false"><i class="fa fa-tasks"></i><span class="hide-menu">' .
            self::$translation['processFile'] . '</span></a>' .
            //'<ul aria-expanded="false" class="collapse">' .
            //'<li><a href="' . BASE_URL . '/' . $filename . '?route=list">' . $translation['list'] . '</a></li>' .
            //'<li><a href="' . BASE_URL . '/' . $filename . '?route=add">' . $translation['add'] . '</a></li>' .
            //'</ul>'.
            '</li>';
        }

        return '';
    }

    private function extractModuleUrlByName($module_name, $array)
    {
        foreach ($array as $value) {
            if ($value['names'] === $module_name) {
                return $value['uri_path'];
            }
        }

        return '';
    }
}