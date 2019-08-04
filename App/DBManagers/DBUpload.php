<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 5/9/2018
 * Time: 10:15 AM
 */

namespace App\DBManagers;


use App\DBManagers\DBBase;

define('UPLOAD_FILES_MAX_FILENAME_LENGTH', 512);
define('UPLOAD_FILES_PROCESS_NO', 0);
define('UPLOAD_FILES_PROCESS_YES', 1);

define('UPLOAD_FILES_DELETE_NO', 0);
define('UPLOAD_FILES_DELETE_YES', 1);

class DBUpload extends DBBase
{

	/** @var string $DB_NAME */
	public static $DB_NAME = 'uploaded_file';

	/** @var array $db_row */
	private $db_row;

	/** @var string $query */
	private $query;

	/** @vas \dbaccess $database */
	private $database;

	/** @var  integer $id */
	public $id;

	/** @var  string $filename */
	public $filename;

	/** @var  string $uid */
	public $uid;

	/** @var  string $mimetype */
	public $mimetype;

	/** @var  integer $filesize */
	public $filesize;

	/** @var  integer $uploader_id */
	public $uploader_id;

	/** @var  \DateTime $date_on_action */
	public $date_on_action;

	/** @var  \DateTime $date_create */
	public $date_create;

	/** @var  \DateTime $last_update */
	public $last_update;

	/** @var  string $description */
	public $description;

	/** @var  string $temp_file_link */
	public $temp_file_link;

	/** @var  string $extension */
	public $extension;

	/** @var  string $error */
	public $error;

	/** @var  integer $is_process */
	public $is_process;

	/** @var  integer $processor_id */
	public $processor_id;

	/** @var  integer $is_delete */
	public $is_delete;

	/** @var  integer $deleter_id */
	public $deleter_id;

	/** @var  string $physical_path */
	public $physical_path;

	/** @var  string $icon */
	public $icon;


	/**
	 * DBDocumentFiles constructor
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
		$this->filename = null;
		$this->uid = null;
		$this->mimetype = null;
		$this->filesize = 0;
		$this->uploader_id = 0;
		$this->date_on_action = new \DateTime();
		$this->extension = '';
		$this->description = '';
		$this->date_create = new \DateTime();
		$this->last_update = new \DateTime();
		$this->error = '';
		$this->temp_file_link = '';
		$this->is_process = UPLOAD_FILES_PROCESS_NO;
		$this->processor_id = 0;
		$this->is_delete = UPLOAD_FILES_DELETE_NO;
		$this->deleter_id = 0;

		$this->physical_path = '';

		$this->icon = '<i class="fa fa-file" style="font-size: 2em"></i>';
	}

	private function saveToModel(&$array)
	{
		if (array_key_exists('id', $array)) {
			$this->id = $array['id'];
		}

		if (array_key_exists('filename', $array)) {
			$this->filename = $array['filename'];
		}

		if (array_key_exists('uid', $array)) {
			$this->uid = $array['uid'];
		}

		if (array_key_exists('filesize', $array)) {
			$this->filesize = $array['filesize'];
		}

		if (array_key_exists('uploader_id', $array)) {
			$this->uploader_id = $array['uploader_id'];
		}

		if (array_key_exists('date_on_action', $array)) {
			$this->date_on_action = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_on_action']);
		}

		if (array_key_exists('extension', $array)) {
			$this->extension = strtolower($array['extension']);
		}

		if (array_key_exists('description', $array)) {
			$this->description = html_entity_decode($array['description']);
		}

		if (array_key_exists('date_create', $array)) {
			$this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
		}

		if (array_key_exists('last_update', $array)) {
			$this->last_update = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['last_update']);
		}

		if (array_key_exists('error', $array)) {
			$this->error = $array['error'];
		}

		if (array_key_exists('is_process', $array)) {
			$this->is_process = $array['is_process'];
		}

		if (array_key_exists('processor_id', $array)) {
			$this->processor_id = $array['processor_id'];
		}

		if (array_key_exists('is_delete', $array)) {
			$this->is_delete = $array['is_delete'];
		}

		if (array_key_exists('deleter_id', $array)) {
			$this->deleter_id = $array['deleter_id'];
		}

		$this->physical_path = UPLOAD_FILES_DIR . '/' . $this->uid . '.' . $this->extension;

		$this->icon = ($this->extension == 'csv') ? '<i class="fa fa-file-text" style="font-size: 2em"></i>' : '<i class="fa fa-file-excel" style="font-size: 2em"></i>';

	}

	private function loadFromModel()
	{
		$this->db_row['id'] = $this->database->escape($this->id);
		$this->db_row['filename'] = $this->database->escape($this->filename);
		$this->db_row['uid'] = $this->database->escape($this->uid);
		$this->db_row['mimetype'] = $this->database->escape($this->mimetype);
		$this->db_row['filesize'] = $this->database->escape($this->filesize);
		$this->db_row['uploader_id'] = $this->database->escape($this->uploader_id);
		$this->db_row['date_on_action'] = $this->date_on_action->format(INTERNAL_DATE_FORMAT);
		$this->db_row['extension'] = $this->database->escape(strtolower($this->extension));
		$this->db_row['description'] = $this->database->escape(htmlentities($this->description));
		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
		$this->db_row['last_update'] = $this->last_update->format(INTERNAL_DATE_FORMAT);
		$this->db_row['error'] = $this->database->escape($this->error);
		$this->db_row['is_process'] = $this->database->escape($this->is_process);
		$this->db_row['processor_id'] = $this->database->escape($this->processor_id);
		$this->db_row['is_delete'] = $this->database->escape($this->is_delete);
		$this->db_row['deleter_id'] = $this->database->escape($this->deleter_id);
	}

	public function saveAsNew()
	{
		if (!file_exists($this->temp_file_link)) return false;

		if (!$this->saveFile()) return false;

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
		$filepath = UPLOAD_FILES_DIR . '/' . $this->uid . '.' . $this->extensions;

		if (file_exists($filepath)) {
			unlink($filepath);
		}

		$this->loadFromModel();
		$this->query = $this->generateQuery('delete', $this->db_row);

		$this->database->execute($this->query);

		if ($this->database->error()) {
			return false;
		}

		return true;
	}

	protected function generateUUID()
	{
		return uniqid("", true);
	}

	protected function saveFile()
	{
		if (!$this->checkMkDir()) return false;

		do {
			$this->uid = $this->generateUUID();
		} while (file_exists(UPLOAD_FILES_DIR . '/' . $this->uid . '.' . $this->extension));

		//move file
		if (rename($this->temp_file_link, UPLOAD_FILES_DIR . '/' . $this->uid . '.' . $this->extension)) {
			if (file_exists($this->temp_file_link)) {
				unlink($this->temp_file_link);
			}
			return true;
		}

		return false;
	}

	/**
	 * @param $id
	 * @param \dbaccess $database
	 * @return bool | DBUpload
	 */
	public static function loadFromId($id, $database)
	{
		if (!is_numeric($id)) return false;

		$id = $database->escape($id);

		$query = "SELECT * FROM " . self::$DB_NAME . " WHERE id = '$id'";

		$res = $database->execute($query);

		if ($database->error()) {
			return false;
		}

		if ($database->rows() == 1) {
			$obj = new DBUpload($database);
			$obj->saveToModel($res[0]);

			return $obj;
		}

		return false;
	}

	private function checkMkDir()
	{
		if (!is_dir(UPLOAD_FILES_DIR)) {
			if (!mkdir(UPLOAD_FILES_DIR)) {
				return false;
			}
		}

		return true;
	}
}