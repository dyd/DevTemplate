<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 2:36 PM
 */

namespace App\DBManagers;


use App\Managers\UserModule;
use App\UserModuleManager;

class DBUserModule extends DBBase {
    
    /** @var string $DB_NAME */
    public static $DB_NAME = '"rel_module<->user"';
    
    /** @var array $db_row */
    private $db_row;
   
    /** @var string $query */
    private $query;
    
    /** @vas \dbaccess $database */
    private $database;

	/** @var  int $id */
	public $id;

	/** @var  int $module_id */
	public $module_id;

	/** @var  int $user_id */
	public $user_id;

	/** @var  \DateTime */
	public $date_create;
   
	 /**
	 * DBUserModule constructor
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
		$this->module_id = 0;
		$this->user_id = 0;
		$this->date_create = new \DateTime();
	
	}
	
	private function saveToModel(&$array)
	{
		if (array_key_exists('id', $array)) {
			$this->id = $array['id'];
		}

		if (array_key_exists('module_id', $array)) {
			$this->module_id = $array['module_id'];
		}

		if (array_key_exists('user_id', $array)) {
			$this->user_id = $array['user_id'];
		}

		if (array_key_exists('date_create', $array)) {
			$this->date_create = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['date_create']);
		}
	}
	
	private function loadFromModel()
	{
		$this->db_row['id'] = $this->database->escape($this->id);
		$this->db_row['module_id'] = $this->database->escape($this->module_id);
		$this->db_row['user_id'] = $this->database->escape($this->user_id);
		$this->db_row['date_create'] = $this->date_create->format(INTERNAL_DATE_FORMAT);
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

	public function delete()
	{
		$this->loadFromModel();
		$this->query = $this->generateQuery('delete', $this->db_row);

		$row = $this->database->execute($this->query);

		if ($this->database->error()) {
			return false;
		}

		return true;
	}

	/**
	 * LOAD ARRAY WITH OBJECTS of DBUserMoudles
	 *
	 * @param array $array
	 * @param int $user_id
	 * @param \dbaccess $database
	 * @return array
	 */
	public static function loadFromValidatedArray(&$array, $user_id, $database)
	{
		$data = array();

		//Add default module
		$home_id = UserModule::getModuleIdByName('home', $database);
		array_push($array['module'], $home_id);

		foreach($array['module'] as $value) {
			$arr = array();
			$arr['user_id'] = $user_id;
			$arr['module_id'] = $value;

			$obj = new DBUserModule($database);
			$obj->saveToModel($arr);

			array_push($data, $obj);
			unset($obj);
		}

		return $data;
	}
}