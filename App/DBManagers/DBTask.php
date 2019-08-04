<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 18.5.2018 г.
 * Time: 10:37
 */

namespace App\DBManagers;


use App\DBManagers\DBBase;


define('TASK_PHONE_TYPE_MOBILE', 1);
define('TASK_PHONE_TYPE_STATIONARY', 2);

class DBTask extends DBBase
{

	/** @var string $DB_NAME */
	public static $DB_NAME = 'task';

	/** @var array $db_row */
	private $db_row;

	/** @var string $query */
	private $query;

	/** @vas \dbaccess $database */
	private $database;

	/** @var integer $id */
	public $id;

	/** @var  integer $process_id */
	public $process_id;

	/** @var  integer $phone */
	public $phone;

	/** @var  string $client_identifier */
	public $client_identifier;

	/** @var  string $city */
	public $city;

	/** @var  float $bill */
	public $bill;

	/** @var  integer */
	public $phone_type;

	/** @var  \DateTime $date_create */
	public $date_create;

	/** @var  \DateTime $last_update */
	public $last_update;

	/** @var  integer $creator_id */
	public $creator_id;

	/** @var  \DateTime $date_on_action */
	public $date_on_action;

	/**
	 * DBTask constructor
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
		$this->process_id = 0;
		$this->phone = 0;
		$this->client_identifier = '';
		$this->city = '';
		$this->bill = '';
		$this->phone_type = TASK_PHONE_TYPE_MOBILE;
		$this->date_create = new \DateTime();
		$this->last_update = new \DateTime();
		$this->creator_id = 0;
		$this->date_on_action = new \DateTime();
	}

	public function saveToModel(&$array)
	{
		if (array_key_exists('id', $array)) {
			$this->id = $array['id'];
		}

		if (array_key_exists('process_id', $array)) {
			$this->process_id = $array['process_id'];
		}

		if (array_key_exists('phone', $array)) {
			$this->phone = $array['phone'];
		}

		if (array_key_exists('client_identifier', $array)) {
			$this->client_identifier = $array['client_identifier'];
		}

		if (array_key_Exists('city', $array)) {
			$this->city = $array['city'];
		}

		if (array_key_exists('bill', $array)) {
			$this->bill = $array['bill'];
		}

		if (array_key_exists('phone_type', $array)) {
			$this->phone_type = $array['phone_type'];
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

		if (array_key_exists('date_on_action', $array)) {
			$this->date_on_action = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT_FROM, $array['date_on_action']);
		}
	}

	private function loadFromModel()
	{
		$this->db_row['id'] = (int)$this->id;
		$this->db_row['process_id'] = (int)$this->process_id;
		$this->db_row['phone'] = $this->phone;
		$this->db_row['client_identifier'] = $this->database->escape($this->client_identifier);
		$this->db_row['city'] = $this->database->escape($this->city);
		$this->db_row['bill'] = $this->bill;
		$this->db_row['phone_type'] = (int)$this->phone_type;
		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
		$this->db_row['last_update'] = $this->last_update->format(INTERNAL_DATE_FORMAT);
		$this->db_row['creator_id'] = (int)$this->creator_id;
		$this->db_row['date_on_action'] = $this->date_on_action->format(INTERNAL_DATE_FORMAT);
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
}