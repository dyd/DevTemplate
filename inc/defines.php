<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/6/2017
 * Time: 12:03 PM
 */

define('ROOT', getRootDir());

define('COMPANY_NAME', 'DN Template');

define('MAIL_TEMPLATES_FOLDER', ROOT . "/templates_email/");
define('MAIL_FROM', 'dnatzkin@voicecom.bg');

define('HEADER', ROOT . '/html/common/header.php');
define('FOOTER', ROOT . '/html/common/footer.php');

define('CLIENT_FILES_DIR', ROOT . '/upload/client_files');

//DEFAULT VALUES
define('MAX_COMMENT_LENGHT', 512);

//UTILS
define('MIN_PREFIX_LENGTH', 1);
define('MAX_PREFIX_LENGTH', 10);

define('MIN_DEFAULT_RANGE_PRICES', 1);
define('MAX_DEFAULT_RANGE_PRICES', 10000);

define('MIN_SHORTCODE_LENGTH', 3);
define('MAX_SHORTCODE_LENGHT', 64);

define('MIN_FILENAME_LENGTH', 2);
define('MAX_FILENAME_LENGTH', 120);

define('MIN_ADDRESS_LENGTH', 3);
define('MAX_ADDRESS_LENGHT', 512);

define('MIN_NICKNAME_LENGTH', 2);
define('MAX_NICKNAME_LENGTH', 100);

define('MIN_PERSON_NAMES', 2);
define('MAX_PERSON_NAMES', 128);

define('MIN_FIRMNAME_LENGTH', 2);
define('MAX_FIRMNAME_LENGTH', 255);

define('INPUT_DATE_FORMAT', 'd.m.Y');
define('INTERNAL_DATE_FORMAT', 'Y-m-d H:i:s');

define('INTERNAL_DATE_FORMAT_FROM', 'Y-m-d 00:00:00');
define('INTERNAL_DATE_FORMAT_TO', 'Y-m-d 23:59:59');

//OPERATORS
define('OPERATOR_MTEL', 1);
define('OPERATOR_TELENOR', 2);
define('OPERATOR_VIVACOM', 3);
define('OPERATOR_BTK', 4);
define('OPERATOR_OTHER', 5);

//FILES
define('MIN_FILE_SIZE', 10); //b
define('MAX_FILE_SIZE', 16777216); //b

//USERNAME
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 32);

//PASSWORD
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 70);//required by password_hash

define('MAX_DESCRIPTION', 5024);

//VAT FEEE
define('VAT_FEE', 20);

//REPORT EMAIL SENDER TMP FOLDER
define('TEMP_FOLDER', ROOT . '/upload/temp');

//upload FILES
define('UPLOAD_FILES_DIR', ROOT . '/upload/files');

define('MIN_LANGUAGE_LENGTH', 3);
define('MAX_LANGUAGE_LENGTH', 32);

define('TRANSLATIONS_FOLDER', getRootDir() . '/translations');
define('LANGUAGE_FLAGS_FOLDER', getRootDir() . '/public/img/language_flags');
