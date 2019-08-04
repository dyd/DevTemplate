<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 8/3/2017
 * Time: 4:40 PM
 */

namespace App\DBManagers;


use App\DBManagers\DBBase;

class DBSession extends DBBase
{

	/** @var string $DB_NAME */
	public static $DB_NAME = 'session';

	/** @var array $db_row */
	private $db_row;

	/** @var string $query */
	private $query;

	/** @vas \dbaccess $database */
	private $database;

	/** @var  int $id */
	public $id;

	/** @var  string $session_id */
	public $session_id;

	/** @var  int $person_id */
	public $person_id;

	/** @var  string $session */
	public $session_data;

	/** @var  \DateTime $date_create */
	public $date_create;

	/** @var  \DateTime $last_update */
	public $last_update;

	/** @var string $remote_addr */
	public $remote_addr;


	/**
	 * DBSession constructor
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
		$this->session_id = '';
		$this->person_id = 0;
		$this->session_data = '';
		$this->date_create = new \DateTime();
		$this->last_update = new \DateTime();
		$this->remote_addr = $_SERVER['REMOTE_ADDR'];
	}

	private function saveToModel(&$array)
	{
		$this->id = $array['id'];
		$this->session_id = $array['session_id'];
		$this->person_id = $array['person_id'];
		$this->session_data = $array['session_data'];
		$this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
		$this->last_update = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['last_update']);
		$this->remote_addr = $array['remote_addr'];
	}

	private function loadFromModel()
	{
		$this->db_row['id'] = $this->database->escape($this->id);
		$this->db_row['session_id'] = $this->database->escape($this->session_id);
		$this->db_row['person_id'] = $this->database->escape($this->person_id);
		$this->db_row['session_data'] = $this->database->escape($this->session_data);
		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
		$this->db_row['last_update'] = $this->last_update->format(INTERNAL_DATE_FORMAT);
		$this->db_row['remote_addr'] = $this->database->escape($this->remote_addr);
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

	public function delete()
	{
		$this->loadFromModel();
		$this->query = $this->generateQuery('delete', $this->db_row);

		$this->database->execute($this->query);

		if ($this->database->error()) {
			return false;
		}

		return true;
	}

	/**
	 * Loads array of objects with specific user_id
	 *
	 * @param $person_id
	 * @param \dbaccess $database
	 * @return array|bool
	 */
	public static function loadFromPersonId($person_id, $database)
	{
		$data = array();
		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE person_id='" . $database->escape($person_id) . "';";

		$res = $database->execute($query);
		if ($database->error()) {
			return false;
		}

		if ($database->rows() != 0) {
			foreach ($res as $value) {
				$obj = new DBSession($database);
				$obj->saveToModel($value);

				array_push($data, $obj);
				unset($obj);
			}

			return $data;
		}

		return false;
	}

	/**
	 * Load from Session ID
	 *
	 * @param $session_id
	 * @param \dbaccess $database
	 * @return bool|DBSession
	 */
	public static function loadFromSessionId($session_id, $database)
	{
		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE session_id = '" . $database->escape($session_id) . "';";
		$res = $database->execute($query);

		if ($database->error()) {
			return false;
		}

		if ($database->rows() == 1) {
			$obj = new DBSession($database);
			$obj->saveToModel($res[0]);
			return $obj;
		}

		return false;
	}
}