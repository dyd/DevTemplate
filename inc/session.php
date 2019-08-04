<?php

class session
{

	private $db;

	public function __construct($arr)
	{
		$this->db = pg_connect("host=" . $arr['dbhost'] . " port=" . $arr['dbport'] . " user=" . $arr['dbuser'] . " dbname=" . $arr['dbname'] . " password=" . $arr['dbpass'] . "") or die();

	}

	function open($save_path, $session_name)
	{
		$this->gc();
		return TRUE;
	}

	public function close()
	{
		$this->gc();
		return TRUE;
	}

	public function read($session_id)
	{
		$session_id = pg_escape_string($session_id);

		$query = "SELECT * FROM session WHERE session_id='$session_id'";
		$result = pg_query($this->db, $query);
		$session_arr = pg_fetch_assoc($result);

		if (isset($session_arr['session_data'])) {
			$this->session_arr = $session_arr;
			$this->session_arr['session_data'] = '';
			return $session_arr['session_data'];
		} else {
			return '';
		}

	}

	public function write($session_id, $session_data)
	{

		$session_data = pg_escape_string($session_data);
        $person_id = array_key_exists('person_id', $_SESSION) ? pg_escape_string($_SESSION['person_id']) : 0;
        $session_id = pg_escape_string($session_id);
		$remote_addr = pg_escape_string($_SERVER['REMOTE_ADDR']);

		if (!is_array($this->session_arr)) {
			if ($person_id != 0) {
				$query = "SELECT session_id FROM session WHERE person_id='$person_id'";
				$res = pg_query($this->db, $query);
				if (pg_num_rows($res) != 0) {
					$row = pg_fetch_assoc($res);
					if ($row['session_id'] != $session_id) {
						$query = "DELETE FROM session WHERE person_id='$person_id'";
						pg_query($this->db, $query);

						$query = "INSERT INTO session_log (user_id,remote_addr,status) VALUES ('$person_id','$remote_addr','New login')";
						pg_query($this->db, $query);
					}
				}
			}
			$query = "INSERT INTO session (session_id,person_id,session_data,date_create,last_update,remote_addr) VALUES ('$session_id','$person_id','$session_data',CURRENT_TIMESTAMP(0),CURRENT_TIMESTAMP(0),'$remote_addr')";
			pg_query($this->db, $query);

		} else {
			$query = "UPDATE session SET session_data='$session_data', last_update=CURRENT_TIMESTAMP(0) WHERE session_id='$session_id' AND person_id='$person_id'";
			pg_query($this->db, $query);
		}

		return TRUE;

	}

	public function destroy($session_id)
	{
		$session_id = pg_escape_string($session_id);

		$query = "SELECT person_id FROM session WHERE session_id='$session_id'";
		$row = pg_fetch_assoc(pg_query($this->db, $query));

		if (is_array($row)) {
			$query = "DELETE FROM session WHERE session_id='$session_id';INSERT INTO session_log (user_id,remote_addr,status) VALUES ('" . $row['person_id'] . "','" . $_SERVER['REMOTE_ADDR'] . "','Clean Logout');";
			pg_query($this->db, $query);
		}

		return TRUE;
	}

	public function gc()
	{

		$query = "SELECT person_id, remote_addr FROM session WHERE last_update + interval '30 minutes' < CURRENT_TIMESTAMP";
		$res = pg_query($this->db, $query);

		$query = "DELETE FROM session WHERE last_update + interval '30 minutes' < CURRENT_TIMESTAMP";
		pg_query($this->db, $query);

		unset($query);

		while ($row = pg_fetch_assoc($res)) {
			if ($row['person_id'] != 0) {
				$query = "INSERT INTO session_log (user_id,remote_addr,status) VALUES ('" . $row['person_id'] . "','" . $row['remote_addr'] . "','Delete from garbage collector.');";
			}
		}
		if (isset($query)) {
			pg_query($this->db, $query);
		}
		return TRUE;
	}

	public function __destruct()
	{
		@session_write_close();
		pg_close($this->db);
	}
}