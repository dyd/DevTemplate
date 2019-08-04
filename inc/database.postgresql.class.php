<?php

class dbaccess
{

	private $dblink;  //database link
	private $error; //last error
	private $rows;  //number of returned or affected rows
	private $debug;    //boolean
	private $exclude_duplicate_errors;    //boolean

	public function __construct($database_settings, $debug = false)
	{

		#######################################
		# CONFIG PART OF CONSTRUCTOR          #
		# CONFIGURE THE FOLLOWING PARAMETERS  #
		# IN ORDER TO MATCH YOUR DESIRED      #
		# BEHAVIOUR                           #
		#######################################

		// First of all, set debug level
		$this->debug = $debug;

		// Set this to true to exclude unique constraint errors (shown in debug mode) in case you expect them.
		$this->exclude_duplicate_errors = true;


		#######################################
		# CORE PART OF THE CONSTRUCTOR        #
		# ORIGINALLY YOU SHOULD NOT           #
		# CHANGE ANYTHING BELLOW              #
		#######################################

		// Connect to database

		if (!function_exists("pg_connect")) {
			$this->error = "Postgres SQL not installed for this PHP, function pg_connect() not found.";
			$this->dblink = false;
		} else {

			$this->dblink = @pg_connect("host=" . $database_settings['dbhost'] . " port=" . $database_settings['dbport'] . " dbname=" . $database_settings['dbname'] . " user=" . $database_settings['dbuser'] . " password=" . $database_settings['dbpass']);
		}

		if ($this->dblink === false) {  // connection unsuccessful

			if (function_exists("pg_connect")) {
				$this->error = "ERROR: Can`t connect to server " . $database_settings['dbhost'];
			}

			if ($this->debug === true) {
				print $this->error . "\n";
			}
		}
	}

	/**
	 * @param $sql
	 * @param string $result_type
	 * @return bool|array
	 */
	public function execute($sql, $result_type = "ASSOC")
	{

		$this->rows = 0;  //nullify rows variable

		$result = @pg_query($this->dblink, $sql);
		if (pg_last_error($this->dblink) != "") { //query failed
			$this->error = pg_last_error($this->dblink);
			if ($this->debug === true) {
				if ($this->exclude_duplicate_errors) {
					if (!preg_match("/duplicate key violates unique constraint/", $this->error)) {
						print $sql . "\n";
						print $this->error . "\n";
					}
				} else {
					print $sql . "\n";
					print $this->error . "\n";
				}
			}
			return false;
		}


		if (pg_num_rows($result) > 0) { //select query
			if ($result_type == 'NUM') {  //type of return array - ASSOC or NUM
				$fetch_function = "pg_fetch_row";
			} else {
				$fetch_function = "pg_fetch_assoc";
			}
            $i = 0;
			while ($arr = $fetch_function($result)) {
                $return_arr[@(int)$i++] = $arr;  //create two-dimensional array with the result set
			}
			$this->rows = pg_num_rows($result); //set rows to number of returned rows
		} else {  //update, insert or delete query
			$this->rows = pg_affected_rows($result);  //set rows to number of affected rows
			return true;  //no result, no error, so return true
		}
        return $return_arr;
	}

	public function error()
	{ //get error
		return $this->error;
	}

	public function rows()
	{  //get number of rows
		return $this->rows;
	}

	public function escape($string, $striphtml = false)
	{ //escape string using pg_escape_string

		$str = pg_escape_string($string);
		if ($striphtml) {
			$str = htmlspecialchars($str);
		}

		return trim($str);
	}

	public function escape_binary($string)
	{ //escape string using pg_escape_string

		$str = pg_escape_bytea($string);

		return $str;
	}

	public function __destruct()
	{
		if ($this->dblink !== false)
			pg_close($this->dblink);
	}

}