<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 11.5.2018 г.
 * Time: 16:35
 */
require_once('../../../start.php');
require_once(getRootDir() . '/inc/system.php');

if ($user->isLogged()) {
	//CHECK IF USER HAVE RIGHTS TO ACCESS CURRENT PAGE/MODULE
	//CHECK IF MODULE remote_addr IS ALLOWED FOR USER's REMOTE ARRD
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	$script = 'process_file.php';
	if (!\App\UserModuleManager::moduleRemoteRights($user->getModules(), $script)) {
		echo json_encode(array("st" => 3, "msg" => $translate['system']['missingAccess']));
		exit();
	}
} else {
	echo json_encode(array("st" => 3, "msg" => $translate['system']['notLogged']));
	exit();
}

if ($user->hasCRUD()) {

} else {
	echo json_encode(array("st" => 3, "msg" => $translate['system']['missingAccess']));
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (array_key_exists('id', $_POST) && (int)$_POST['id'] > 0) {
		$obj_file = \App\DBManagers\DBUpload::loadFromId((int)$_POST['id'], $database);

		if ($obj_file) {
			if ($obj_file->is_process == UPLOAD_FILES_PROCESS_YES) {
				echo json_encode(array("st" => 3, "msg" => $language->getTranslation('Upload', 'errorProcess')));
				exit();
			}
		} else {
			echo json_encode(array("st" => 3, "msg" => $translate['system']['invalidID']));
			exit();
		}
	} else {
		echo json_encode(array("st" => 3, "msg" => $translate['system']['invalidID']));
		exit();
	}

	//check if script is running
	$query = "SELECT * FROM " . \App\DBManagers\DBProcessFile::$DB_NAME . " WHERE process_status = '" . PROCESS_FILES_STATUS_STARTED_YES . "' OR process_status = '" . PROCESS_FILES_STATUS_PAUSED . "'";
	$res = $database->execute($query);

	if ($database->error()) {
		echo json_encode(array("st" => 3, "msg" => $language->getTranslation('System', 'systemError')));
		exit();
	}

	if ($database->rows() > 0) {
		echo json_encode(array("st" => 3, "msg" => $language->getTranslation('ProcessFile', 'errorAlreadyStarted')));
		exit();
	}


	$error = '';
	try {
		$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($obj_file->extension));
		//Only first worksheet
		$worksheet = $objReader->listWorksheetInfo($obj_file->physical_path);
	} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
		$error = $e->getMessage();
	}

	if ($error != '') {
		echo json_encode(array("st" => 3, "msg" => "Грешка при прочитането на файла " . $error));
		exit();
	}

	$cmd = getenv('SCRIPT_COMMAND_SCRIPT_COMMAND_PROCESS_FILE');

	$obj = new \App\DBManagers\DBProcessFile($database);
	$obj->process_status = PROCESS_FILES_STATUS_STARTED_YES;
	$obj->file_id = $obj_file->id;

	$processFileId = $obj->saveAsNew();
	$obj->id = $processFileId;

	$obj->chunk_end = $worksheet[0]['totalRows'];

	$pid = shell_exec($cmd . ' "' . $processFileId . ' " > /dev/null 2>&1 & echo $!');
	$obj->pid = $pid;

	$obj->last_update = new DateTime();
	$obj->save();

	$data = array(
		"filename" => $obj_file->filename . '.' . $obj_file->extension,
		"chunk_start" => $obj->chunk_start,
		"chunk_end" => $obj->chunk_end
	);

	echo json_encode(array("st" => 1, "info" => $data));
	exit();

}