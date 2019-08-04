<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 5/8/2018
 * Time: 5:45 PM
 */
require_once('../../../start.php');
require_once(getRootDir() . '/inc/system.php');
if ($user->isLogged()) {
	//Logged
	//CHECK IF USER HAVE RIGHTS TO ACCESS CURRENT PAGE/MODULE
	//CHECK IF MODULE remote_addr IS ALLOWED FOR USER's REMOTE ARRD
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	$script = "upload.php";
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
	if (isset($_FILES) && is_array($_FILES) && array_key_exists('file', $_FILES)) {
		if ($_FILES['file']['error'] != '0') {
			echo json_encode(array('st' => 3, 'msg' => $language->getTranslation('Upload', 'fileError') . ' - ' . $_FILES['file']['error']));
			exit();
		}

		if (!in_array($_FILES['file']['type'], $fileTypes)) {
			echo json_encode(array('st' => 3, 'msg' => $language->getTranslation('Upload', 'errorMimeType')));
			exit();
		}
	} else {
		echo json_encode(array('st' => 3, 'msg' => $language->getTranslation('Upload', 'missingFile')));
		exit();
	}

	$date_on_action = \App\Utils::validateDate($_POST['date_on_action']);
	if (array_key_exists('date_on_action', $_POST) && $date_on_action) {

	} else {
		echo json_encode(array('st' => 3, 'msg' => $language->getTranslation('Upload', 'errorDate')));
		exit();
	}

	$description = '';
	if (array_key_exists('description', $_POST)) {
		if (mb_strlen($_POST['description'], 'UTF-8') <= MAX_DESCRIPTION) {
			$description = $_POST['description'];
		} else {
			echo json_encode(array("st" => 3, "msg" => $language->getTranslation('Upload', 'errorDescription')));
			exit();
		}
	}

	$obj = new \App\DBManagers\DBUpload($database);

	$info = pathinfo($_FILES['file']['name']);

	$obj->filename = $info['filename'];
	$obj->mimetype = $_FILES['file']['type'];
	$obj->error = $_FILES['file']['error'];
	$obj->temp_file_link = $_FILES['file']['tmp_name'];
	$obj->filesize = $_FILES['file']['size'];
	$obj->extension = $info['extension'];
	$obj->date_on_action = $date_on_action;
	$obj->description = $description;
	$obj->uploader_id = $user->getId();

	$id = $obj->saveAsNew();

	if (is_numeric($id)) {
		echo json_encode(array("st" => 1));
		exit();
	}

	echo json_encode(array("st" => 3, "msg" => $language->getTranslation('System', 'systemError') . $id));
	exit();
}

echo json_encode(array('st' => 3, "msg" => 'No post!'));
exit();