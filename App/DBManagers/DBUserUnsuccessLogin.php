<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 8/3/2017
 * Time: 3:44 PM
 */

namespace App\DBManagers;

class DBUserUnsuccessLogin extends DBBase {
    
    /** @var string $DB_NAME */
    public static $DB_NAME = 'unsuccess_login';
    
    /** @var array $db_row */
    private $db_row;
   
    /** @var string $query */
    private $query;
    
    /** @vas \dbaccess $database */
    private $database;

	/** @var  int $id */
	public $id;

	/** @var  string $user_name */
	public $user_name;

	/** @var  \DateTime $log_time */
	public $log_time;

	/** @var  string $remote_addr */
	public $remote_addr;

	/** @var  string $reason */
	public $reason;

	/** @var int $status */
	public $status;
   
	 /**
	 * DBUserUnsuccessLogin constructor
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
		$this->user_name = '';
		$this->log_time = new \DateTime();
		$this->remote_addr = $_SERVER['REMOTE_ADDR'];
		$this->reason = '';
		$this->status = 0;
	}
	
	private function saveToModel(&$array)
	{
		$this->id = $array['id'];
		$this->user_name = $array['user_name'];
		$this->log_time = \DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $array['log_time']);
		$this->remote_addr = $array['remote_addr'];
		$this->reason = $array['reason'];
		$this->status = $array['status'];
	}
	
	private function loadFromModel()
	{
		$this->db_row['id'] = $this->database->escape($this->id);
		$this->db_row['user_name'] = $this->database->escape($this->user_name);
		$this->db_row['log_time'] = $this->log_time->format(INTERNAL_DATE_FORMAT);
		$this->db_row['remote_addr'] = $this->database->escape($this->remote_addr);
		$this->db_row['reason'] = $this->database->escape($this->reason);
		$this->db_row['status'] = $this->database->escape($this->status);
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
}