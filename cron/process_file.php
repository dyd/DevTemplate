<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 11.5.2018 г.
 * Time: 17:33
 */
if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
	require_once "c:/xampp/htdocs/energo-pro/public/start.php";
} else {
	require_once "/opt/dev/projects/energo-pro/Energo-Pro/public/start.php";
}

if (php_sapi_name() == 'cli') {
	$host = gethostname();

	if (!$host) {
		exit();
	}
	$_SERVER['REMOTE_ADDR'] = gethostbyname($host);

	$_SERVER['SERVER_NAME'] = basename($host);
}

require_once getRootDir() . '/inc/system.php';

$_SESSION['person_id'] = 0;

$obj = \App\DBManagers\DBProcessFile::loadFromId((int)$_SERVER['argv'][1], $database);
if ($obj) {
	//OK
	$obj_file = \App\DBManagers\DBUpload::loadFromId($obj->file_id, $database);

	if ($obj_file) {

	} else {
		exit();
	}
} else {
	exit();
}

//OK We have Started Process for File Process
//NOW We need to read file on chunks AND insert records into the database
//BOX/SPOUT

if ($obj_file->extension == 'xlsx') {
	//Load Excel
	$reader = \Box\Spout\Reader\ReaderFactory::create(\Box\Spout\Common\Type::XLSX);
} else {
	//Load CSV
	/** @var \Box\Spout\Reader\CSV\Reader $reader */
	$reader = \Box\Spout\Reader\ReaderFactory::create(\Box\Spout\Common\Type::CSV);
	$reader->setFieldDelimiter(';');
	$reader->setFieldEnclosure('"');
	$reader->setEndOfLineCharacter("\r");
}

$reader->open($obj_file->physical_path);

$row_count = 0;
/** @var \Box\Spout\Reader\SheetInterface $sheet */
foreach ($reader->getSheetIterator() as $sheet) {

	foreach ($sheet->getRowIterator() as $current_row => $row) {

		$row_count = $current_row;

		$obj->chunk_start = $row_count;
		$obj->percent = round(($obj->chunk_start / $obj->chunk_end) * 100, 0, PHP_ROUND_HALF_UP);
		$obj->last_update = new DateTime();
		$obj->save();

		//You Need Validator HERE!!!

		$query = "SELECT * FROM " . \App\DBManagers\DBTask::$DB_NAME . " WHERE phone = '" . $row[3] . "' AND process_id = " . $obj->id . ";";
		$res_task = $database->execute($query);

		if ($database->error()) {
			\App\MailManager::getInstance()->sendErrorMail('dnatzkin@voicecom.bg', 'EnergoPro', 'EnergoPro Duplicate Checker', 'EnergoPro Duplicate Checker', $database->error());
		}

		if ($database->rows() == 1) {
			if ($row[0]['phone_type'] == TASK_PHONE_TYPE_MOBILE && $res_task[0]['phone_type'] == TASK_PHONE_TYPE_STATIONARY) {
				$obj_existing_task = new \App\DBManagers\DBTask($database);
				$obj_existing_task->saveToModel($res_task[0]);
				$obj_existing_task->last_update = new DateTime();
				$obj_existing_task->save();
			} else {
				continue;
			}
		} elseif ($database->rows() == 0) {

			$obj_task = new \App\DBManagers\DBTask($database);
			$obj_task->process_id = $obj->id;
			$obj_task->date_on_action = $obj_file->date_on_action;
			$obj_task->client_identifier = $row[0];
			$obj_task->bill = str_replace(',', '.', $row[1]);
			$obj_task->city = $row[2];
			$obj_task->phone = $row[3];
			$obj_task->phone_type = ($row[4] == 'stationary') ? TASK_PHONE_TYPE_STATIONARY : TASK_PHONE_TYPE_MOBILE;
			$task_id = $obj_task->saveAsNew();

			if (!is_numeric($task_id))
				\App\MailManager::getInstance()->sendErrorMail(
					'dnatzkin@voicecom.bg',
					'EnergoPro',
					'EnergoPro Error Insert Task',
					'EnergoPro Error Insert Task for process_id: ' . $obj->id,
					'Error Insert Task <br />' . 'process_id: ' . $obj->id .
					'<br />CLient id' . $row[0] .
					'<br />bill' . $row[1] .
					'<br />city' . $row[2] .
					'<br />msisdn: ' . $row[3] .
					'<br />Phone Type: ' . $row[4] .

					'<br /> Error: <br />' . $task_id
				);

		} else {

			\App\MailManager::getInstance()->sendErrorMail(
				'dnatzkin@voicecom.bg',
				'EnergoPro',
				'EnergoPro Multiple records',
				'EnergoPro Multiple records for process_id: ' . $obj->id,
				'Multiple records <br />' . 'process_id: ' . $res_task[0]['process_id'] . '<br />msisdn: ' . $res_task[0]['phone']
			);
		}
	}

	//Only first sheet
	break;
}

if ($row_count == $obj->chunk_end) {
	$obj->process_status = PROCESS_FILES_STATUS_FINISH;
	$obj->pid = -1;
	$obj->last_update = new DateTime();
	$obj->save();
}

$reader->close();

exit();