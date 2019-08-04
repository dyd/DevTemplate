<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 8/2/2017
 * Time: 11:06 AM
 */

namespace App\DBManagers;

class DBUserPasswordReset extends DBBase
{

	/** @var string $DB_NAME */
	public static $DB_NAME = 'password_reset_key';

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

	/** @var  string $key */
	public $key;

	/** @var  int $is_used */
	public $is_used;

	/** @var  \DateTime $date_create */
	public $date_create;

	/** @var  \DateTime $usage_date */
	public $usage_date;

	/** @var  int $is_expire */
	public $is_expire;

	/**
	 * DBUserPasswordReset constructor
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
		$this->key = '';
		$this->is_used = 0;
		$this->date_create = new \DateTime();
		$this->usage_date = new \DateTime();
		$this->is_expire = 0;
	}

	private function saveToModel(&$array)
	{
		if (array_key_exists('id', $array)) {
			$this->id = $array['id'];
		}

		if (array_key_exists('key', $array)) {
			$this->key = $array['key'];
		}

		if (array_key_exists('login_name', $array)) {
			$this->login_name = $array['login_name'];
		}

		if (array_key_exists('is_used', $array)) {
			$this->is_used = $array['is_used'];
		}

		if (array_key_exists('date_create', $array)) {
			$this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
		}

		if (array_key_exists('usage_date', $array)) {
			$this->usage_date = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['usage_date']);
		}

		if (array_key_exists('is_expire', $array)) {
			$this->is_expire = boolval($array['is_expire']);
		}
	}

	private function loadFromModel()
	{
		$this->db_row['id'] = $this->database->escape($this->id);
		$this->db_row['login_name'] = $this->database->escape($this->login_name);
		$this->db_row['key'] = $this->database->escape($this->key);

		if ($this->is_used) {
			$this->db_row['is_used'] = 1;
		} else {
			$this->db_row['is_used'] = 0;
		}

		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
		$this->db_row['usage_date'] = $this->usage_date->format(INTERNAL_DATE_FORMAT);

		if ($this->is_expire) {
			$this->db_row['is_expire'] = 1;
		} else {
			$this->db_row['is_expire'] = 0;
		}
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
	 * @param string $token
	 * @param \dbaccess $database
	 * @return bool|DBUserPasswordReset
	 */
	public static function loadByToken($token, $database)
	{
		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE is_used = '0' AND is_expire = '0' AND KEY='" . $token . "' ORDER BY id DESC LIMIT 1;";
		$res = $database->execute($query);

		if ($database->error()) {
			return false;
		}

		if ($database->rows() == 1) {
			$obj = new DBUserPasswordReset($database);
			$obj->saveToModel($res[0]);

			return $obj;
		}

		return false;
	}

	/**
	 * @param \dbaccess $database
	 * @return bool
	 */
	public static function garbageCollector($database)
	{
		$query = "UPDATE " . self::$DB_NAME . " SET is_expire = '1' WHERE (date_create + interval '48 hours') < CURRENT_TIMESTAMP(0) AND is_used = '0' and is_expire = '0';";
		$database->execute($query);
		if ($database->error()) {
			//TODO MAIL ME ERROR
			return false;
		}

		return true;
	}

	/**
	 * @param $username
	 * @param \dbaccess $database
	 * @return bool
	 */
	public static function garbageCollectorByUsername($username, $database)
	{
		$query = "UPDATE password_reset_key SET is_expire = '1',usage_date=CURRENT_TIMESTAMP(0) WHERE login_name='".$database->escape($username)."' AND is_used = '0' AND is_expire = '0';";

		$database->execute($query);
		if ($database->error()){
			//TODO MAIL ERROR
			return false;
		}

		return true;
	}
}