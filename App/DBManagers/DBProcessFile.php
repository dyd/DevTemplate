<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 11.5.2018 г.
 * Time: 15:10
 */

namespace App\DBManagers;


use App\DBManagers\DBBase;

define('PROCESS_FILE_STATUS_STARTED_NOT', 0);
define('PROCESS_FILES_STATUS_STARTED_YES', 1);
define('PROCESS_FILES_STATUS_PAUSED', 2);
define('PROCESS_FILES_STATUS_FINISH', 3);
define('PROCESS_FILES_STATUS_ERROR', 4);

class DBProcessFile extends DBBase
{

	/** @var string $DB_NAME */
	public static $DB_NAME = 'process_file';

	/** @var array $db_row */
	private $db_row;

	/** @var string $query */
	private $query;

	/** @vas \dbaccess $database */
	private $database;

	/** @var  integer $id */
	public $id;

	/** @var  integer $file_id */
	public $file_id;

	/** @var  integer $chunk_start */
	public $chunk_start;

	/** @var  integer $chunk_end */
	public $chunk_end;

	/** @var  \DateTime $date_create */
	public $date_create;

	/** @var  \DateTime $last_update */
	public $last_update;

	/** @var  integer $percent */
	public $percent;

	/** @var  integer $processor_id */
	public $processor_id;

	/** @var  integer $pid */
	public $pid;

	/** @var  integer $process_status */
	public $process_status;

	/** @var  string $error */
	public $error;

	/**
	 * DBProcessFile constructor
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
		$this->file_id = 0;
		$this->chunk_start = 0;
		$this->chunk_end = 0;
		$this->date_create = new \DateTime();
		$this->last_update = new \DateTime();
		$this->percent = 0;
		$this->processor_id = 0;
		$this->pid = -1;
		$this->process_status = PROCESS_FILE_STATUS_STARTED_NOT;
		$this->error = '';
	}

	public function saveToModel(&$array)
	{
		if (array_key_exists('id', $array)) {
			$this->id = $array['id'];
		}

		if (array_key_exists('file_id', $array)) {
			$this->file_id = $array['file_id'];
		}

		if (array_key_exists('chunk_start', $array)) {
			$this->chunk_start = $array['chunk_start'];
		}

		if (array_key_exists('chunk_end', $array)) {
			$this->chunk_end = $array['chunk_end'];
		}

		if (array_key_exists('date_create', $array)) {
			$this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
		}

		if (array_key_exists('last_update', $array)) {
			$this->last_update = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['last_update']);
		}

		if (array_key_exists('percent', $array)) {
			$this->percent = $array['percent'];
		}

		if (array_key_exists('processor_id', $array)) {
			$this->processor_id = $array['processor_id'];
		}

		if (array_key_exists('pid', $array)) {
			$this->pid = $array['pid'];
		}

		if (array_key_exists('process_status', $array)) {
			$this->process_status = $array['process_status'];
		}

		if (array_key_exists('error', $array)) {
			$this->error = nl2br($array['error']);
		}

	}

	private function loadFromModel()
	{
		$this->db_row['id'] = (int)$this->id;
		$this->db_row['file_id'] = (int)$this->file_id;
		$this->db_row['chunk_start'] = (int)$this->chunk_start;
		$this->db_row['chunk_end'] = (int)$this->chunk_end;
		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
		$this->db_row['last_update'] = $this->last_update->format(INTERNAL_DATE_FORMAT);
		$this->db_row['percent'] = $this->database->escape($this->percent);
		$this->db_row['processor_id'] = (int)$this->processor_id;
		$this->db_row['pid'] = (int)$this->pid;
		$this->db_row['process_status'] = (int)$this->process_status;

		$err = str_replace(array('<br>', '<br />'), PHP_EOL, $this->error);
		$this->db_row['error'] = $this->database->escape($err);

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
	 * @param integer $id
	 * @param \dbaccess $database
	 * @return bool | DBProcessFile
	 */
	public static function loadFromId($id, $database)
	{
		if (!is_integer($id)) return false;

		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE id = '$id';";
		$res = $database->execute($query);

		if ($database->error()) {

			return false;
		}

		if ($database->rows() == 1) {
			$obj = new DBProcessFile($database);
			$obj->saveToModel($res[0]);

			return $obj;
		}

		return false;
	}

	public static function loadFromFileId($id, $database)
	{
		if (!is_integer($id)) return false;

		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE file_id = '$id';";
		$res = $database->execute($query);

		if ($database->error()) {
			return false;
		}

		if ($database->rows() == 1) {
			$obj = new DBProcessFile($database);
			$obj->saveToModel($res[0]);

			return $obj;
		}

		return false;
	}
}