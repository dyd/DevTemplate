<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/6/2017
 * Time: 12:29 PM
 */

namespace App\Managers;

use App\DBManagers\DBLanguage;

class Translation
{
    /**
     * @var Translation $instance
     */
    private static $instance;

    /** @var array $dataEN */
    private $dataEN;

    /** @var array $dataBG */
    private $dataBG;

    /** @var string */
    public $currentLang;

    /** @var \dbaccess $database */
    private $database;

    /** @var array $data */
    private $data;

    /** @var array $language_list */
    private $language_list;

    /**
     * Returns Translation instance
     *
     * @var \dbaccess $database
     * @return Translation - User instance
     */
    public static function getInstance($database)
    {
        if (null === static::$instance) {
            static::$instance = new static($database);
        }

        return static::$instance;
    }

    /**
     * @param integer $language
     * @param \dbaccess $database
     */
    public static function initialize($language, $database)
    {
        $translation = Translation::getInstance($database);
        $translation->setLanguage($language);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return Translation
     */
    private function setData()
    {
        $obj_lang = DBLanguage::loadById($this->currentLang, $this->database);

        if ($obj_lang) {
            if ($obj_lang->is_active == LANGUAGE_ACTIVE_NO) {
                $obj_lang = DBLanguage::loadByLanguageCode('EN', $this->database);
            }

            $this->data = $obj_lang->data;
        }
        return $this;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * Translation instance.
     *
     * @return void
     */
    private function __clone()
    {

    }

    /**
     * Translation constructor
     */
    protected function __construct($database)
    {
        $this->reset();

        $this->database = $database;

        $this->setLanguageList();
    }

    private function reset()
    {
        $this->currentLang = 0;
        $this->data = array();
        $this->language_list = array();
    }

    public function setLanguage($language)
    {
        $this->currentLang = $language;

        $this->setData();
    }

    private function setLanguageList()
    {
        $query = "SELECT * FROM " . DBLanguage::$DB_NAME . " WHERE is_active = '" . LANGUAGE_ACTIVE_YES . "' ORDER BY languages ASC;";

        $res = $this->database->execute($query);

        if ($this->database->error()) {
            return array();
        }

        if ($this->database->rows() > 0) {
            foreach ($res as $value) {
                array_push($this->language_list, [
                    'id' => $value['id'],
                    'code' => $value['language_code'],
                    'name' => $value['languages'],
                    'flag' => $value['language_flag']
                ]);
            }

            return $this->language_list;
        }

        return array();
    }

    public function getSection($section)
    {
        if (array_key_exists($section, $this->data)) {
			foreach ($this->data[$section] as &$value) {
                $value = html_entity_decode($value);
            }
            return $this->data[$section];
        }

        return array();
    }

    public function getTranslation($section, $index)
    {
        return (array_key_exists($section, $this->data) && array_key_exists($index, $this->data[$section])) ? html_entity_decode($this->data[$section][$index]) : 'undefined translation';
        //return $this->data[$section][$index];
    }

    public function parseINIFileHTML($data)
    {
        $string = '';
        if (!is_array($data)) return '';
        foreach ($data as $key => $value) {

            $string .= '<div class="row">';
            $string .= '<div class="col-md-12">';
            $string .= '<div class="well well-sm">';

            if (is_array($value)) {
                $string .= '<h4>' . $key . '</h4>';
                $string .= $this->parseINISectionHTML($key, $value);
            } else {
                $string .= '<div class="form-group">';
                $string .= '<label>' . $key . '</label>';
                $string .= '<input name="data[' . $key . ']" value="' . $value . '" class="form-control" />';
                $string .= '</div>';
            }

            $string .= '</div>';
            $string .= '</div>';
            $string .= '</div>';
        }

        return $string;
    }

    private function parseINISectionHTML($section, $data)
    {
        $string = '';
        foreach ($data as $key => $value) {
            $string .= '<div class="form-group">';
            $string .= '<label>' . $key . '</label>';
            $string .= '<input name="data[' . $section . '][' . $key . ']" value="' . $value . '" class="form-control" />';
            $string .= '</div>';
        }

        return $string;
    }

    public function getLanguageList()
    {
        return $this->language_list;
    }

    /**
     * @param $rights
     * @return string
     */
    public function saveUserRightsHtml($rights)
    {
        $translate = $this->getSection('Users');

        switch ($rights) {
            case USER_RIGHT_ADMINISTRATOR:
                return '<div class="badge badge-pill badge-primary">' . @$translate['userTypeAdmin'] . '</div>';
                break;
            case USER_RIGHT_CRUD:
                return '<div class="badge badge-pill badge-primary">' . @$translate['userTypeCRUD'] . '</div>';
                break;
            case USER_RIGHT_EDIT:
                return '<div class="badge badge-pill badge-primary">' . @$translate['userTypeEdit'] . '</div>';
                break;
            case USER_RIGHT_VIEW:
                return '<div class="badge badge-pill badge-primary">' . @$translate['userTypeView'] . '</div>';
                break;
            default:
                return '<div class="badge badge-pill badge-primary">' . @$translate['userTypeGuest'] . '</div>';
                break;
        }
    }
}