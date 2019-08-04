<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 21.5.2018 г.
 * Time: 16:38
 */
/* An easy way to keep in track of external processes.
* Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
* @compability: Linux only. (Windows does not work).
* @author: Peec
*
*  http://php.net/manual/en/function.exec.php
*
*/

class Process
{
	private $pid;
	private $command;

	public function __construct($cl = false)
	{
		if ($cl != false) {
			$this->command = $cl;
			$this->runCom();
		}
	}

	private function runCom()
	{
		$command = 'nohup ' . $this->command . ' > /dev/null 2>&1 & echo $!';
		exec($command, $op);
		$this->pid = (int)$op[0];
		sleep(2);
	}

	public function setPid($pid)
	{
		$this->pid = $pid;
	}

	public function getPid()
	{
		return $this->pid;
	}

	public function status()
	{
		$command = 'ps -u -p ' . $this->pid;
		exec($command, $op);
		if (!isset($op[1])) {
			return false;
		} else {
			return $op;
		}
	}

	public function start()
	{
		if (!$this->status()) { //multiple execution

			if ($this->command != '') {
				$this->runCom();

				if ($this->status()) {
					return true;
				}
			}

		} else {
			return true;
		}

		return false;
	}

	public function stop()
	{
		$command = 'kill ' . $this->pid;
		exec($command);
		sleep(2);
		if ($this->status() == false) {
			return true;
		} else {
			return false;
		}
	}

	public static function loadPid($pid)
	{
		$pr = new Process();
		$pr->pid = $pid;

		return $pr;
	}
}

?>