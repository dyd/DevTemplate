<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 2/1/2018
 * Time: 3:54 PM
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

$query = "SELECT * FROM " . \App\DBManagers\DBDocument::$DB_NAME .
	" WHERE notification_warning_days > 0 AND is_notifiable = '1' AND notification_send = '0' AND NOW() >= (document_valid_date - INTERVAL '1 day' * notification_warning_days)";

$res = $database->execute($query);

if ($database->error()) {
	exit();
}

if ($database->rows() > 0) {
	foreach ($res as $value) {
		$obj = \App\DBManagers\DBDocument::loadFromArray($value, $database);

		$additional_body = '<a href="' . BASE_URL . '/documents.php?route=view&id=' . $obj->id . '">' . \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningBodyAdditional') . '</a>';

		$sender = \DBManagers\DBUser::loadFromId($obj->creator_id, $database);

		if ($sender) {

			\App\TranslationManager::initialize($user->getDefaultLanugage(), $database);

			$creator_names = $sender->first_name . ' ' . $sender->last_name;
			$creator_email = $sender->email;
			\App\TranslationManager::initialize($sender->default_language, $database);
			$creator_subject = \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningSubject');
			$creator_subject = $creator_subject . ' ' . $obj->document_names;

			$creator_body = '<br /><h1>' . \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningTitle') . '</h1><br /><h2>' .
				$obj->document_names . '</h2><br /><h4>' .
				\App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentsPeriod') . ': ' .
				$obj->document_start_date->format(INPUT_DATE_FORMAT) . ' - ' . $obj->document_valid_date->format(INPUT_DATE_FORMAT) . '</h4><br />' .
				\App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningBody') . '<br />' . $additional_body;

			$creator_title = \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningMainTitle');

			if (!\App\MailManager::getInstance()->sendDocumentNotification($creator_email, $creator_names, $creator_subject, $creator_title, $creator_body)) exit();
		}

		$obj_lang = \App\DBManagers\DBLanguage::loadByLanguageCode('EN', $database);
		\App\TranslationManager::initialize($obj_lang->id, $database);

		$subject = \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningSubject');
		$subject = $subject . ' ' . $obj->document_names;

		$body = '<br /><h1>' . \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningTitle') . '</h1><br /><h2>' .
			$obj->document_names . '</h2><br /><h4>' .
			\App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentsPeriod') . ': ' .
			$obj->document_start_date->format(INPUT_DATE_FORMAT) . ' - ' . $obj->document_valid_date->format(INPUT_DATE_FORMAT) . '</h4><br />' .
			\App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningBody') . '<br />' . $additional_body;

		$title = \App\TranslationManager::getInstance($database)->getTranslation('Documents', 'documentWarningMainTitle');

		if ($obj_lang) {
			\App\TranslationManager::initialize($obj_lang->id, $database);

			if ($obj->notification_receivers) {
				foreach ($obj->notification_receivers as $cell) {
					if (filter_var($cell, FILTER_VALIDATE_EMAIL)) {

						\App\MailManager::getInstance()->sendDocumentNotification($cell, $cell, $subject, $title, $body);
					}
				}
			}
		}

		$obj->last_update = new DateTime();
		$obj->notification_send = DOCUMENT_NOTIFICATION_SEND;
		$obj->save();
	}
}
