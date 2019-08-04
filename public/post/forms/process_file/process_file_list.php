<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 11.5.2018 г.
 * Time: 13:10
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

if ($user->hasVIEW()) {

} else {
	echo json_encode(array("st" => 3, "msg" => $translate['system']['missingAccess']));
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = array();

	$search_condition = '';
	if (array_key_exists('search', $_POST)) {
		if ($_POST['search']['value'] != '') {
			$search = strtolower($database->escape($_POST['search']['value']));

			$search_condition = " AND (lower(filename)) LIKE '%$search%' ";
		}
	}

	if (array_key_exists('start', $_POST)) {
		$pagination['start'] = (int)$_POST['start'];
	} else {
		return false;
	}

	if (array_key_exists('length', $_POST)) {
		$pagination['length'] = (int)$_POST['length'];
	} else {
		return false;
	}

	$pagination_string = " LIMIT " . $database->escape($pagination['length'] . " OFFSET " . $database->escape($pagination['start']));

	//SORT
	$order = array();
	if (array_key_exists('order', $_POST)) {
		$order['column'] = $_POST['order'][0]['column'];

		if (array_key_exists('dir', $_POST['order'][0])) {
			$order['dir'] = strtoupper($database->escape($_POST['order'][0]['dir']));
		} else {
			$order['dir'] = 'ASC';
		}
	} else {
		$order['column'] = 0;
		$order['dir'] = 'ASC';
	}

	$order_condition = '';
	switch ($order['column']) {
		case 0:
			$order_condition = " ORDER BY filename " . $order['dir'];
			break;
		case 1:
			$order_condition = " ORDER BY date_create " . $order['dir'];
			break;
		default:
			$order_condition = " ORDER BY filename " . $order['dir'];
			break;
	}

	$query = "SELECT * FROM " . \App\DBManagers\DBUpload::$DB_NAME . " WHERE is_process = '" . UPLOAD_FILES_PROCESS_NO . "' AND is_delete = '" . UPLOAD_FILES_DELETE_NO . "' " . $search_condition;

	$res_full = $database->execute($query);

	if ($database->error()) {
		$res_full = array();
	}

	$query_pagination = $query . $search_condition . $order_condition . $pagination_string;
	$res_pagination = $database->execute($query_pagination);

	if ($database->error()) {
		$res_pagination = array();
	}

	if ($database->rows() > 0) {
		foreach ($res_pagination as &$value) {
			$value['date_create'] = DateTime::createFromFormat(INTERNAL_DATE_FORMAT, $value['date_create'])->format(INPUT_DATE_FORMAT);
			$value['description'] = html_entity_decode($value['description']);
		}
	}

	$data['data'] = $res_pagination;
	$data['recordsFiltered'] = count($res_full);
	$data['recordsTotal'] = count($res_pagination);

	echo json_encode($data);
	exit();

}