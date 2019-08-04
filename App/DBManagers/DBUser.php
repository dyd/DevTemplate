<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/24/2017
 * Time: 4:08 PM
 */

namespace App\DBManagers;


use App\Managers\Translation;
use App\Managers\UserModule;

define('USER_RIGHT_ADMINISTRATOR', 4);
define('USER_RIGHT_CRUD', 3);
define('USER_RIGHT_EDIT', 2);
define('USER_RIGHT_VIEW', 1);
define('USER_RIGHT_GUEST', 0);

define('USER_PASS_EXPIRE', 1);
define('USER_PASS_INFINITY', 0);

define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_NOT_ACTIVE', 0);

class DBUser extends DBBase
{

    /** @var string $DB_NAME */
    public static $DB_NAME = 'user_table';

    /** @var array $db_row */
    private $db_row;

    /** @var string $query */
    private $query;

    /** @vas \dbaccess $database */
    private $database;

    /** @var  int $id */
    public $id;

    /** @var  string $login_name */
    public $login_name;

    /** @var  string $pass */
    public $password;

    /** @var  \DateTime $creation_date */
    public $creation_date;

    /** @var  string $first_name */
    public $first_name;

    /** @var  string $last_name */
    public $last_name;

    /** @var  int $rights */
    public $rights;

    /** @var  string $rights_html */
    public $rights_html;

    /** @var  int $is_del */
    public $is_del;

    /** @var  array $remote_addr */
    public $remote_addr;

    /** @var  int $msisdn */
    public $msisdn;

    /** @var  string $email */
    public $email;

    /** @var  int $is_pass_expire */
    public $is_pass_expire;

    /** @var  \DateTime $pass_date_expire */
    public $pass_date_expire;

    /** @var  array $modules */
    public $modules;

    /** @var  integer $status */
    public $status;

    /** @var  integer $default_language */
    public $default_language;

    /**
     * DBUser constructor
     *
     * @param \dbaccess $database
     */
    public function __construct($database)
    {
        $this->database = $database;
        $this->reset();

        parent::__construct(self::$DB_NAME);
    }

    private function reset()
    {
        $this->db_row = array();

        $this->id = 0;
        $this->login_name = '';
        $this->password = '';
        $this->creation_date = new \DateTime();
        $this->first_name = '';
        $this->last_name = '';
        $this->rights = USER_RIGHT_GUEST;
        $this->is_del = 0;
        $this->remote_addr = array(0 => "*");

        $this->msisdn = 0;
        $this->email = '';
        $this->is_pass_expire = USER_PASS_EXPIRE;
        $this->pass_date_expire = new \DateTime();
        $this->status = USER_STATUS_ACTIVE;
        $this->default_language = Translation::getInstance($this->database)->currentLang;
    }

    private function saveToModel(&$array)
    {
        if (array_key_exists('id', $array)) {
            $this->id = $array['id'];
        }

        if (array_key_exists('login_name', $array)) {
            $this->login_name = $array['login_name'];
        }

        if (array_key_exists('pass', $array)) {
            $this->password = $array['pass'];
        }

        if (array_key_exists('creation_date', $array)) {
            $this->creation_date = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['creation_date']);
        }

        if (array_key_exists('first_name', $array)) {
            $this->first_name = $array['first_name'];
        }

        if (array_key_exists('last_name', $array)) {
            $this->last_name = $array['last_name'];
        }

        if (array_key_exists('rights', $array)) {
            $this->rights = $array['rights'];
        }

        $this->rights_html = Translation::getInstance($this->database)->saveUserRightsHtml($this->rights);

        if (array_key_exists('is_del', $array)) {
            $this->is_del = $array['is_del'];
        }

        if (array_key_exists('remote_addr', $array)) {
            $this->remote_addr = $this->pg_array_parse($array['remote_addr']);
        }

        if (array_key_exists('msisdn', $array)) {
            $this->msisdn = $array['msisdn'];
        }

        if (array_key_exists('email', $array)) {
            $this->email = strtolower($array['email']);
        }

        if (array_key_exists('is_pass_expire', $array)) {
            $this->is_pass_expire = $array['is_pass_expire'];
        }

        if (array_key_exists('pass_date_expire', $array)) {
            $this->pass_date_expire = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['pass_date_expire']);
        }

        if (array_key_exists('status', $array)) {
            $this->status = $array['status'];
        }

        if (array_key_exists('default_language', $array)) {
            $this->default_language = $array['default_language'];
        }

        $this->modules = UserModule::getInstance($this->id, $this->database)->getModules($this->id);
    }

    private function loadFromModel()
    {
        $this->db_row['id'] = $this->database->escape($this->id);
        $this->db_row['login_name'] = $this->database->escape($this->login_name);
        $this->db_row['pass'] = $this->database->escape($this->password);
        $this->db_row['creation_date'] = $this->creation_date->format(INTERNAL_DATE_FORMAT);
        $this->db_row['first_name'] = $this->database->escape($this->first_name);
        $this->db_row['last_name'] = $this->database->escape($this->last_name);
        $this->db_row['rights'] = $this->database->escape($this->rights);

        if ($this->is_del) {
            $this->db_row['is_del'] = 1;
        } else {
            $this->db_row['is_del'] = 0;
        }

        $this->db_row['remote_addr'] = $this->generatePostgreArray($this->remote_addr, true);
        $this->db_row['msisdn'] = $this->database->escape($this->msisdn);
        $this->db_row['email'] = $this->database->escape(strtolower($this->email));
        $this->db_row['is_pass_expire'] = $this->database->escape($this->is_pass_expire);
        $this->db_row['pass_date_expire'] = $this->pass_date_expire->format(INTERNAL_DATE_FORMAT);
        $this->db_row['status'] = $this->database->escape($this->status);
        $this->db_row['default_language'] = $this->database->escape($this->default_language);
    }

    public function saveAsNew()
    {
        $this->loadFromModel();
        $this->query = $this->generateQuery('insert', $this->db_row);

        $row = $this->database->execute($this->query);

        if ($this->database->error()) {
            return $this->database->error();
        }

        return $row[0]['id'];
    }

    public function save()
    {
        $this->loadFromModel();
        $this->query = $this->generateQuery('update', $this->db_row);

        $this->database->execute($this->query);

        if ($this->database->error()) {
            return false;
        }

        return true;
    }

    /**
     * LOAD USER OBJECT WITH ROW
     *
     * @param $user_id
     * @param \dbaccess $database
     * @return bool|DBUser
     */
    public static function loadFromId($user_id, $database)
    {
        $user_id = $database->escape($user_id);
        $query = "SELECT * FROM " . DBUser::$DB_NAME . " WHERE id = '" . $user_id . "';";
        $res = $database->execute($query);
        if ($database->error()) {
            //TODO MAIL ME ERROR
        }

        if ($database->rows() == 1) {
            $obj = new DBUser($database);
            $obj->saveToModel($res[0]);

            return $obj;
        }

        return false;
    }

    /**
     * @param $username
     * @param \dbaccess $database
     * @return bool|DBUser
     */
    public static function loadFromUsername($username, $database)
    {
        $username = $database->escape(mb_strtolower($username, 'UTF-8'));
        $query = "SELECT * FROM " . self::$DB_NAME . " WHERE lower(login_name) = '" . $username . "';";
        $res = $database->execute($query);

        if ($database->error()) {
            return false;
        }

        if ($database->rows() > 0) {
            $obj = new DBUser($database);
            $obj->saveToModel($res[0]);
            return $obj;
        }

        return false;
    }

    /**
     * LOAD MODEL FROM VALIDATED ARRAY
     *
     * @param array $array
     * @param \dbaccess $database
     * @return DBUser
     */
    public static function loadFromValidatedArray(&$array, $database)
    {
        $obj = new DBUser($database);
        $obj->saveToModel($array);

        return $obj;
    }

}