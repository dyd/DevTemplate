<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:23 PM
 */

namespace App\Managers;


use App\DBManagers\DBLanguage;
use App\DBManagers\DBUser;
use App\DBManagers\DBUserPasswordReset;
use App\UserFactory;
use App\Utils;

class User
{
    /**
     * @var User $instance
     */
    private static $instance;

    /** @var  \dbaccess */
    private $database;

    /**
     * Returns User instance
     *
     * @return User - User instance
     */
    public static function getInstance($database)
    {
        if (null === static::$instance) {
            static::$instance = new static($database);
        }

        return static::$instance;
    }

    /**
     * setup the User Class
     */
    public static function initialize()
    {

    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * User instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * User constructor
     */
    protected function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * LOADS USER FROM SESSION ID
     *
     * @param $session_id
     * @return \App\User\User
     */
    public function loadUserFromSession($session_id)
    {
        $session_id = $this->database->escape($session_id);
        $remote_add = $this->database->escape($_SERVER['REMOTE_ADDR']);
        $query = "SELECT person_id FROM session WHERE session_id='" . $session_id . "' AND remote_addr='" . $remote_add . "'";
        $res = $this->database->execute($query);

        if ($this->database->error()) {
            //TODO MAIL ME ERROR
            return UserFactory::create((new DBUser($this->database)), $this->database);
        }

        if ($this->database->rows() == 1 && $res[0]['person_id'] != 0) {
            $usr_obj = DBUser::loadFromId($res[0]['person_id'], $this->database);
            if ($usr_obj) {
                return UserFactory::create($usr_obj, $this->database);
            }
        }

        return UserFactory::create((new DBUser($this->database)), $this->database);
    }

    public static function loadFromPost(&$data, $database)
    {
        $result = array(
            'st' => 1,
            'msg' => '',
            'module' => array(),
            'service' => array()
        );

        if (isset($data['loginName']) && Utils::validateUsername($data['loginName'])) {
            $result['login_name'] = $data['loginName'];
        } else {
            $result['st'] = 0;
            $result['loginName'] = 0;
        }

        if (isset($data['firstname']) && Utils::validateNickName($data['firstname'])) {
            $result['first_name'] = $data['firstname'];
        } else {
            $result['st'] = 0;
            $result['firstname'] = 0;
        }

        if (isset($data['lastname']) && Utils::validateNickName($data['lastname'])) {
            $result['last_name'] = $data['lastname'];
        } else {
            $result['st'] = 0;
            $result['lastname'] = 0;
        }

        if (isset($data['e-mail']) && Utils::validateEmail($data['e-mail'])) {
            $result['email'] = $data['e-mail'];
        } else {
            $result['st'] = 0;
            $result['e-mail'] = 0;
        }

        if (isset($data['phone']) && Utils::validateMSISDN($data['phone'])) {
            $result['msisdn'] = Utils::normalizeMSISDN($data['phone']);
        } else {
            $result['st'] = 0;
            $result['phone'] = 0;
        }

        if (isset($data['rights']) && Utils::validateNumber($data['rights'])) {
            switch ($data['rights']) {
                case USER_RIGHT_ADMINISTRATOR:
                case USER_RIGHT_CRUD:
                case USER_RIGHT_EDIT:
                case USER_RIGHT_VIEW:
                    $result['rights'] = $data['rights'];
                    break;
                default:
                    $data['rights'] = USER_RIGHT_GUEST;
                    $result['st'] = 0;
                    $result['right'] = 0;
                    break;
            }
        } else {
            $result['st'] = 0;
            $result['right'] = 0;
        }

        if (isset($data['modules']) && $data['modules'] != 'null') {
            $module_arr = explode(',', $data['modules']);
            if (is_array($module_arr) && count($module_arr) > 0) {
                $result['module'] = array();
                foreach ($module_arr as $value) {
                    $module_id = UserModule::getModuleIdByName($value, $database);
                    if ($module_id) {
                        array_push($result['module'], $module_id);
                    } else {
                        unset($result['module']);
                        $result['st'] = 0;
                        $result['modules'] = 0;
                    }
                }
            } else {
                $result['st'] = 0;
                $result['modules'] = 0;
            }
        }

        $result['remote_addr'] = array();
        if (isset($data['allowed_ip']) && $data['allowed_ip'] != '') {
            $addr_arr = explode(',', $data['allowed_ip']);
            if (is_array($addr_arr) && count($addr_arr) > 0) {
                foreach ($addr_arr as $value) {
                    $value = trim($value);
                    if (filter_var($value, FILTER_VALIDATE_IP) || $value === '*') {
                        array_push($result['remote_addr'], $value);
                    } else {
                        $result['st'] = 0;
                        $result['allowed_ip'] = 0;
                    }
                }

            }
        } else {
            $result['remote_addr'] = array('0' => '*');
        }

        if (isset($data['is_pass_expire'])) {
            $result['is_pass_expire'] = USER_PASS_EXPIRE;
        } else {
            $result['is_pass_expire'] = USER_PASS_INFINITY;
        }

        if (array_key_exists('status', $data) && ($data['status'] == 'true' || $data['status'] == 'false')) {
            if ($data['status'] == 'true') {
                $result['status'] = USER_STATUS_ACTIVE;
            } else {
                $result['status'] = USER_STATUS_NOT_ACTIVE;
            }
        }

        if (array_key_exists('default_language', $data) && is_numeric($data['default_language'])) {
            $obj_lang = DBLanguage::loadById($data['default_language'], $database);
            if ($obj_lang) {
                $result['default_language'] = $obj_lang->id;
            }
        } else {
            $result['st'] = 0;
            $result['language'] = 0;
        }

        return $result;
    }

    /**
     * Generate UUID v4
     *
     * @return string
     */
    public function generateToken()
    {
        do {
            $token = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        } while (DBUserPasswordReset::loadByToken($token, $this->database));

        return $token;
    }

    /**
     * @param $pagination
     * @param $search
     * @param $order
     * @param \dbaccess $database
     * @return bool|string|array
     */
    public function loadUserList($pagination, $search, $order, $database)
    {
        $order_string = "ORDER BY " . $database->escape($order['column']) . " " . $database->escape($order['dir']);
        if ($order['column'] == 'names') {
            $order_string = "ORDER BY first_name " . $database->escape($order['dir']) . ", last_name " . $database->escape($order['dir']);
        }

        $search_string = 'AND TRUE';
        if ($search != '') {
            $search = preg_replace('/[^\p{L}\p{M}\p{Z}\p{N}\p{P}]/u', '', $database->escape($search));
            $search_string = "AND to_tsvector(
			login_name || ' ' ||
			first_name || ' ' || last_name || ' ' ||
			msisdn || ' ' || email) @@ to_tsquery('$search:*')";
        }

        $pagination_string = "LIMIT " . $database->escape($pagination['length']) . " OFFSET " . $database->escape($pagination['start']) . "";

        $main_data = array();

        $query = "SELECT * FROM " . DBUser::$DB_NAME . " WHERE is_del = '0' $search_string $order_string;";

        $database->execute($query);
        $main_data['filtered'] = $database->rows();

        $query = "SELECT * FROM " . DBUser::$DB_NAME . " WHERE is_del = '0' $search_string $order_string $pagination_string;";
        $res = $database->execute($query);

        $main_data['filtered_pagination'] = $database->rows();

        if ($res && $database->rows() > 0) {
            foreach ($res as $value) {
                $data = array();
                $obj = DBUser::loadFromId($value['id'], $database);
                $data['login_name'] = $obj->login_name;
                $data['names'] = $obj->first_name . ' ' . $obj->last_name;
                $data['email'] = $obj->email;
                $data['msisdn'] = $obj->msisdn;
                $data['rights'] = $obj->rights_html;
                $data['creation_date'] = $obj->creation_date->format(INPUT_DATE_FORMAT);
                $data['path'] = $obj->id;

                switch ($value['status']) {
                    case USER_STATUS_ACTIVE:
                        $data['status'] = '<span class="badge badge-pill badge-success">' . Translation::getInstance($database)->getTranslation('Users', 'userStatusActive') . '</span>';
                        break;
                    case USER_STATUS_NOT_ACTIVE:
                        $data['status'] = '<span class="badge badge-pill badge-warning">' . Translation::getInstance($database)->getTranslation('Users', 'userStatusNotActive') . '</span>';
                        break;
                    default:
                        $data['status'] = '<span class="badge badge-pill badge-light">unknown</span>';
                        break;
                }
                array_push($main_data, $data);
            }

            return $main_data;
        }

        return false;
    }

    public function return_active()
    {
        $query = "SELECT s.person_id,(s.last_update-s.date_create) AS duration,s.remote_addr,u.first_name,u.last_name FROM session AS s
				INNER JOIN " . DBUser::$DB_NAME . " AS u ON (s.person_id=u.id);";
        $res = $this->database->execute($query);

        if ($this->database->error()) {
            return false;
        }
        return $res;
    }
}