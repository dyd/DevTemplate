<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 1/19/2018
 * Time: 1:16 PM
 */
require_once '/opt/dev/projects/new_stat/public/start.php';

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

$query = "SELECT * FROM " . \App\DBManagers\DBNotificationReceiver::$DB_NAME . " WHERE is_send = '" . NOTIFICATION_SEND_NO . "' AND retry <= 5 AND ".
	" notification_id IN (SELECT id FROM ".\App\DBManagers\DBNotification::$DB_NAME." WHERE date_send < CURRENT_TIMESTAMP(0))";
$res = $database->execute($query);

if ($database->error()) {
	exit();
}

if ($database->rows() > 0) {
	foreach ($res as $value) {
		$obj = \App\DBManagers\DBNotificationReceiver::loadFromArray($value, $database);

		$files = array();
		$obj_files = \App\DBManagers\DBNotificationFile::loadFromNotificationId($obj->notification_id, $database);

		/** @var \App\DBManagers\DBNotificationFile $val_file */
		foreach ($obj_files as $val_file) {
			array_push(
				$files,
				array(
					'filename' => $val_file->filename,
					'extension' => $val_file->extensions,
					'uid' => $val_file->uid,
					'mimetype' => $val_file->mimetype
				)
			);
		}

		$obj_content = \App\DBManagers\DBNotificationContent::loadFromNotificationId($obj->notification_id, $database);

		$subject = '';
		$content = '';

		$idx = 1;
		/** @var \App\DBManagers\DBNotificationContent $val_content */
		foreach ($obj_content as $val_content) {

			$separator_subject = (count($obj_content) > $idx++) ? ' / ' : '';

			$separator_text = (count($obj_content) > $idx++) ? '<br /><br />' : '';

			$subject .= $val_content->subject . $separator_subject;

			$content .= $val_content->content . $separator_text;
		}

		try {
			if (\App\MailManager::getInstance()->sendNotificationMail($obj->email, $obj->names, $subject, $content, $files)) {
				$obj->is_send = NOTIFICATION_SEND_YES;
				$obj->retry++;
                $obj->response = 'Send OK';
			} else {
				$obj->retry++;
			}
		} catch
		(\PHPMailer\PHPMailer\Exception $e) {
			$obj->response = $obj->response . PHP_EOL . $e->getMessage();
			$obj->retry++;
		}

		$obj->last_update = new DateTime();
		$obj->save();
	}

}