<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 12/13/2017
 * Time: 1:27 PM
 */

namespace App\DBManagers;


use App\DBManagers\DBBase;
use App\TranslationManager;

define('LANGUAGE_SYSTEM_YES', 1);
define('LANGUAGE_SYSTEM_NO', 0);

define('LANGUAGE_ACTIVE_YES', 1);
define('LANGUAGE_ACTIVE_NO', 0);

class DBLanguage extends DBBase
{

    /** @var string $DB_NAME */
    public static $DB_NAME = 'lang';

    /** @var array $db_row */
    private $db_row;

    /** @var string $query */
    private $query;

    /** @vas \dbaccess $database */
    private $database;

    /** @var  integer $id */
    public $id;

    /** @var  string $languages */
    public $languages;

    /** @var  string $language_code */
    public $language_code;

    /** @var  string $filename */
    public $filename;

    /** @var  \DateTime $date_create */
    public $date_create;

    /** @var  \DateTime $last_update */
    public $last_update;

    /** @var  integer $creator_id */
    public $creator_id;

    /** @var  integer $updater_id */
    public $updater_id;

    /** @var  integer $is_system */
    public $is_system;

    /** @var  integer $is_active */
    public $is_active;

    /** @var  array $data */
    public $data;

    /** @var  string $flag_temp_file */
    public $flag_temp_file;

    /** @var  string $language_flag */
    public $language_flag;

    /**
     * DBLanguage constructor
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
        $this->languages = '';
        $this->language_code = '';
        $this->filename = '';
        $this->date_create = new \DateTime();
        $this->last_update = new \DateTime();
        $this->creator_id = 0;
        $this->updater_id = 0;
        $this->is_system = LANGUAGE_SYSTEM_NO;
        $this->is_active = LANGUAGE_ACTIVE_NO;
        $this->data = array();
        $this->flag_temp_file = '';
        $this->language_flag = '';
    }

    private function saveToModel(&$array)
    {
        if (array_key_exists('id', $array)) {
            $this->id = $array['id'];
        }

        if (array_key_exists('languages', $array)) {
            $this->languages = $array['languages'];
        }

        if (array_key_exists('language_code', $array)) {
            $this->language_code = $array['language_code'];
        }

        if (array_key_exists('filename', $array)) {
            $this->filename = $array['filename'];
        }

        if (array_key_exists('date_create', $array)) {
            $this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
        }

        if (array_key_exists('last_update', $array)) {
            $this->last_update = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['last_update']);
        }

        if (array_key_exists('creator_id', $array)) {
            $this->creator_id = $array['creator_id'];
        }

        if (array_key_exists('updater_id', $array)) {
            $this->updater_id = $array['updater_id'];
        }

        if (array_key_exists('is_system', $array)) {
            $this->is_system = $array['is_system'];
        }

        if (array_key_exists('is_active', $array)) {
            $this->is_active = $array['is_active'];
        }

        if (array_key_exists('language_flag', $array)) {
            $this->language_flag = $array['language_flag'];
        }

        if ($this->id != 0) {
            $this->data = $this->loadLanguageData();
        }
    }

    private function loadFromModel()
    {
        $this->db_row['id'] = $this->database->escape($this->id);
        $this->db_row['languages'] = $this->database->escape($this->languages);
        $this->db_row['language_code'] = $this->database->escape(strtoupper($this->language_code));
        $this->db_row['filename'] = $this->database->escape($this->filename);
        $this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
        $this->db_row['last_update'] = $this->last_update->format(INTERNAL_DATE_FORMAT);
        $this->db_row['creator_id'] = $this->database->escape($this->creator_id);
        $this->db_row['updater_id'] = $this->database->escape($this->updater_id);
        $this->db_row['is_system'] = $this->database->escape($this->is_system);
        $this->db_row['is_active'] = $this->database->escape($this->is_active);
        $this->db_row['language_flag'] = $this->database->escape($this->language_flag);
    }

    public function saveAsNew()
    {
        if (!$this->createTranslationFile()) {
            return false;
        }

        if (!$this->saveLanguageFlagImage()) {
            return false;
        }

        $this->loadFromModel();
        $this->query = $this->generateQuery('insert', $this->db_row);

        $row = $this->database->execute($this->query);

        if ($this->database->error()) {
            return $this->database->error();
        }

        return $row[0]['id'];
    }

    private function createTranslationFile()
    {
        if (!is_dir(TRANSLATIONS_FOLDER)) {
            return false;
        }

        if (file_exists(TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . $this->filename)) {
            return false;
        }

        if (!copy(TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . 'base.ini', TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . $this->filename)) {
            return false;
        }

        return true;
    }

    private function saveLanguageFlagImage()
    {
        if (!is_dir(LANGUAGE_FLAGS_FOLDER)) {
            if (!mkdir(LANGUAGE_FLAGS_FOLDER)) {
                return false;
            }
        }

        if (rename($this->flag_temp_file, LANGUAGE_FLAGS_FOLDER . DIRECTORY_SEPARATOR . $this->language_flag)) {
            if (file_exists($this->flag_temp_file)) {
                unlink($this->flag_temp_file);
            }
            return true;
        }

        return false;
    }

    public function save()
    {
        if ($this->put_ini_file(TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . $this->filename, $this->data)) {

        } else {
            return false;
        }

        if ($this->flag_temp_file) {
            if (!$this->saveLanguageFlagImage()) {
                return false;
            }
        }

        $this->loadFromModel();
        $this->query = $this->generateQuery('update', $this->db_row);

        $this->database->execute($this->query);

        if ($this->database->error()) {
            return false;
        }

        return true;
    }

    public function delete()
    {
        if ($this->is_system == LANGUAGE_SYSTEM_YES) return false;

        if (file_exists(TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . $this->filename)) {
            unlink(TRANSLATIONS_FOLDER . DIRECTORY_SEPARATOR . $this->filename);
        }

        if (file_exists(LANGUAGE_FLAGS_FOLDER . DIRECTORY_SEPARATOR . $this->language_flag)) {
            unlink(LANGUAGE_FLAGS_FOLDER . DIRECTORY_SEPARATOR . $this->language_flag);
        }

        $this->loadFromModel();
        $this->query = $this->generateQuery('delete', $this->db_row);

        $this->database->execute($this->query);

        if ($this->database->error()) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $language_id
     * @param \dbaccess $database
     * @return bool | DBLanguage
     */
    public static function loadById($language_id, $database)
    {
        if (!is_numeric($language_id)) return false;

        $language_id = $database->escape($language_id);

        $query = "SELECT * FROM " . DBLanguage::$DB_NAME . " WHERE id = '" . $language_id . "'";

        $res = $database->execute($query);

        if ($database->error()) {
            return false;
        }

        if ($database->rows() == 1) {
            $obj = new DBLanguage($database);

            $obj->saveToModel($res[0]);

            return $obj;
        }

        return false;
    }

    private function loadLanguageData()
    {
        $data = parse_ini_file(TRANSLATIONS_FOLDER . '/' . $this->filename, true);

        $this->data = $this->entityDecode($data);

        return $this->data;
    }

    private function put_ini_file($file, $array, $i = 0)
    {
        $str = "";
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $str .= str_repeat("", $i * 2) . "[$k]" . PHP_EOL;
                $str .= $this->put_ini_file("", $v, $i + 1);
            } else
                $str .= str_repeat("", $i * 2) . "$k = \"" . htmlentities($v) . "\"" . PHP_EOL;
        }
        if ($file)
            return file_put_contents($file, $str);
        else
            return $str;
    }

    /**
     * @param $code
     * @param \dbaccess $database
     * @return DBLanguage|bool
     */
    public static function loadByLanguageCode($code, $database)
    {
        $code = $database->escape($code);
        $query = "SELECT * FROM " . DBLanguage::$DB_NAME . " WHERE language_code = '$code'";

        $res = $database->execute($query);

        if ($database->error()) {
            return false;
        }

        if ($database->rows() == 1) {
            $obj = new DBLanguage($database);
            $obj->saveToModel($res[0]);

            return $obj;
        }

        return false;
    }
}