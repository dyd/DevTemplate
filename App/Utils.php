<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/6/2017
 * Time: 3:28 PM
 */

namespace App;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;

class Utils
{
    /**
     * @var Utils $instance
     */
    private static $instance;

    /** @var  \utility $utility */
    protected $utility;

    /**
     * Returns Utils instance
     *
     * @return Utils - User instance
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * setup the Utils Class
     */
    public static function initialize()
    {

    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * Utils instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Utils constructor
     */
    protected function __construct()
    {
        $this->utility = new \utility();
    }

    /**
     * VALIDATE EIK
     *
     * @param integer $var - eik number
     * @return bool
     */
    public static function validateEIK($var)
    {
        if (\EIK_Validator::is_valid($var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE USERNAME
     *
     * @param $var
     * @return bool
     */
    public static function validateUsername(&$var)
    {
        $var = trim($var);
        if (preg_match("/^[.@\da-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_USERNAME_LENGTH . "," . MAX_USERNAME_LENGTH . "}$/ui", $var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE PASSWORD
     *
     * @param $var
     * @return bool
     */
    public static function validatePassword(&$var)
    {
        $var = trim($var);
        if (preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{" . MIN_PASSWORD_LENGTH . "," . MAX_PASSWORD_LENGTH . "}$/", $var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE ClientName
     *
     * @param string $var
     * @return bool
     */
    public static function validateClientFirmName(&$var)
    {
        $var = trim($var);
        if (preg_match("/^['\"\d\s-a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ-]{" . MIN_FIRMNAME_LENGTH . "," . MAX_FIRMNAME_LENGTH . "}$/ui", $var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE Person Names
     *
     * @param $var
     * @param int $length
     * @return bool
     */
    public static function validatePersonNames(&$var, $length = MAX_PERSON_NAMES)
    {
        $var = trim($var);
        if (preg_match("/^[\s-a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_PERSON_NAMES . "," . $length . "}$/ui", $var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE Document Name
     *
     * @param $var
     * @param int $length
     * @return bool
     */
    public static function validateDocumentName(&$var, $length = MAX_DOCUMENT_NAMES)
    {
        $var = trim($var);
        if (preg_match("/^['\"\d\s-a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_DOCUMENT_NAMES . "," . $length . "}$/ui", $var)) {
            return true;
        }

        return false;
    }

    public static function validateNameUniversal(&$var, $min = 3, $max = 64)
    {
        $var = trim($var);
        if (preg_match("/^['\"\d\s-a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . $min . "," . $max . "}$/ui", $var)) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE FIRM NICK NAME
     *
     * @param string $var
     * @return bool
     */
    public static function validateNickName($var)
    {
        if (preg_match("/^[a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_NICKNAME_LENGTH . "," . MAX_NICKNAME_LENGTH . "}$/ui", trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE NUMBER
     *
     * @param integer $var
     * @return bool
     */
    public static function validateNumber($var)
    {

        if (is_numeric($var) && preg_match("/^[\d]{1,100}$/", trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE ADDRESS
     *
     * @param integer $var
     * @return bool
     */
    public static function validateAddress($var)
    {
        if (preg_match("/^[-\s\d.,;#№a-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_ADDRESS_LENGTH . "," . MAX_ADDRESS_LENGHT . "}$/ui", trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE ADDRESS
     *
     * @param string $var
     * @return bool
     */
    public static function validateFileName($var)
    {
        if (preg_match("/^([-\_\da-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_FILENAME_LENGTH . "," . MAX_FILENAME_LENGTH . "}).([a-z]{2,4})$/ui", trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE CURRENCY (0.0003)
     *
     * @param $var
     * @return bool
     */
    public static function validateCurrency(&$var)
    {
        if (preg_match('/^[0-9]{1,9}(?:(\.|,)[0-9]{0,4})?$/', trim($var))) {
            $var = str_replace(',', '.', trim($var));
            return true;
        }

        return false;
    }

    /**
     * VALIDATE PERCENT (0% - 100%)
     *
     * @param $var
     * @return bool
     */
    public static function validatePercent($var)
    {
        if (preg_match('/^([0-9]{1,2}|100)$/', trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE ShortCode
     *
     * @param $var
     * @return bool
     */
    public static function validateShortCode($var)
    {
        if (preg_match("/^[\da-z]{" . MIN_SHORTCODE_LENGTH . "," . MAX_SHORTCODE_LENGHT . "}$/ui", trim($var))) {

            return true;
        }

        return false;
    }

    /**
     * VALIDATE PREFIX
     *
     * @param string $var
     * @return bool
     */
    public static function validatePrefix($var)
    {
        if (preg_match("/^[\da-zабвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ]{" . MIN_PREFIX_LENGTH . "," . MAX_PREFIX_LENGTH . "}$/ui", trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE IF VAR IS INSIDE RANGE
     *
     * @param integer $var
     * @param int $minRange
     * @param int $maxRange
     * @return bool
     */
    public function validateRange($var, $minRange = MIN_DEFAULT_RANGE_PRICES, $maxRange = MAX_DEFAULT_RANGE_PRICES)
    {
        if (preg_match("/^[\d]{" . $minRange . ", " . $maxRange . "}$/", trim($var))) {
            return true;
        }

        return false;
    }

    public static function validateMNC($var)
    {
        if (mb_strlen($var, 'UTF-8') > 0 && mb_strlen($var, 'UTF-8') <= 6) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE MSISDN
     *
     * @param $var
     * @return bool
     */
    public static function validateMSISDN($var)
    {
        if (preg_match('/^(([0]{1})|(359))(([8]{1})|([9]{1}))(([7]{1})|([8]{1})|([9]{1}))([0-9]{7})$/', trim($var))) {
            return true;
        }

        return false;
    }

    /**
     * @param $var
     * @return string
     */
    public static function normalizeMSISDN($var)
    {
        if (substr($var, 0, 3) === '359') {
            $msisdn = $var;
        } else {

            if (substr($var, 0, 1) === '0') {
                $msisdn = '359' . ltrim($var, '0');
            } else {
                $msisdn = '359' . $var;
            }
        }

        return $msisdn;
    }

    /**
     * VALIDATE E-MAIL
     *
     * @param string $var
     * @return bool
     */
    public static function validateEmail($var)
    {
        if (filter_var(trim($var), FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    public static function validateComment($var, $length = MAX_COMMENT_LENGHT)
    {
        if (!empty($var) && mb_strlen($var, 'UTF-8') <= $length) {
            return true;
        }

        return false;
    }

    /**
     * VALIDATE DATE
     *
     * @param $var
     * @return bool|\DateTime
     */
    public static function validateDate($var)
    {
        $date = \DateTime::createFromFormat(INPUT_DATE_FORMAT, $var);
        $errors = \DateTime::getLastErrors();

        if (!empty($errors['warning_count'])) {
            return false;
        }

        return $date;
    }

    public static function validateValuta($var)
    {
        return ExchangeManager::getInstance()->isCurrencyExists($var);
    }

    /**
     * Returns Human readable size
     *
     * @param float $var
     * @param int $precision
     * @return string
     */
    public static function convertBytesReadable($var, $precision = 2)
    {
        $base = log($var, 1024);
        $suffixes = array('b', 'Kb', 'Mb', 'Gb', 'Tb');

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    public static function returnAllowedFileTypes()
    {
        return array(
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.ms-excel",
            "text/plain",
            "text/csv"
        );
    }

    public static function returnTemplateFileTypes()
    {
        return array(
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        );
    }

    public static function returnImageFileTypes()
    {
        return array(
            "image/gif",
            "image/jpeg",
            "image/png",
            "image/svg+xml"
        );
    }

    /**
     * COMPARE VALUES INTO MULTIDIMENSIONAL ARRAY
     *
     * @param $needle
     * @param $haystack
     * @param bool|false $strict
     * @return bool
     */
    public static function in_array_recur($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && Utils::in_array_recur($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $json_arr
     * @return bool
     */
    public function checkRemoteAddrJSON($json_arr)
    {
        $check = false;

        foreach ($json_arr as $value) {
            if ($value == '*') {
                $check = true;
                break;
            }
            if ($value == $_SERVER['REMOTE_ADDR']) {
                $check = true;
                break;
            }

            if ($this->utility->ip_in_range($_SERVER['REMOTE_ADDR'], $value)) {
                $check = true;
                break;
            }
        }
        return $check;
    }

    /**
     * Readable format of billsec
     *
     * @param $seconds
     * @return string
     */
    public static function billsec_conv($seconds)
    {
        $trailSeconds = $seconds % 60;
        $minutes = floor($seconds / 60);
        if ($minutes >= 60) {
            $hour = floor($minutes / 60);
            $minutes = $minutes % 60;
            return $hour . 'h' . $minutes . 'm'
                . $trailSeconds . 's';
        } else {
            return $minutes . 'm' . $trailSeconds . 's';
        }
    }

    /**
     * @param PhpWord $file
     * @return string
     */
    public static function extractTextFromWord($file)
    {
        $string = '';
        $sections = $file->getSections();

        foreach ($sections as $key => $value) {

            if ($value instanceof \PhpOffice\PhpWord\Element\Section) {

                $section = $value->getElements();

                foreach ($section as $elementKey => $elementValue) {
                    if ($elementValue instanceof TextRun) {

                        $string .= self::getTextFromSections($elementValue);

                    } elseif ($elementValue instanceof Text) {

                        $string .= $elementValue->getText();

                    }
                }

            } elseif ($value instanceof \PhpOffice\PhpWord\Element\Text) {

                $string .= $value->getText();

            }
        }

        return $string;
    }

    /**
     * @param TextRun $section
     * @return string
     */
    private function getTextFromSections($section)
    {
        $string = '';
        $sectionElement = $section->getElements();

        foreach ($sectionElement as $elementKey => $elementValue) {

            if ($elementValue instanceof \PhpOffice\PhpWord\Element\TextRun) {

                self::getTextFromSections($elementValue);

            } elseif ($elementValue instanceof \PhpOffice\PhpWord\Element\Text) {

                $string .= $elementValue->getText();

            }

        }

        return $string;
    }

    public static function validateWatcherOperand(&$var)
    {
        $var = trim($var);

        switch ($var) {
            case WATCHER_OPERAND_LESS:
            case WATCHER_OPERAND_BIGGER:
            case WATCHER_OPERAND_BIGGER_EQUAL:
            case WATCHER_OPERAND_EQUAL:
            case WATCHER_OPERAND_LESS_EQUAL:
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    public static function validateLanguage(&$var, $min = MIN_LANGUAGE_LENGTH, $max = MAX_LANGUAGE_LENGTH)
    {
        $var = trim($var);
        if (preg_match("/^[a-z]{" . $min . "," . $max . "}$/i", $var)) {
            return true;
        }

        return false;
    }

    public static function generatePostgreArray($array, $single = false)
    {
        $string = '';

        $cnt = 1;
        foreach ($array as $key => $value) {
            $key = str_replace("'", '', $key);
            $value = str_replace("'", '', $value);

            if ($single) {
                $string .= $value;
            } else {
                $string .= '{"' . $key . '", "' . $value . '"}';
            }

            if ($cnt != count($array)) {
                $string .= ",";
            }
            $cnt++;
        }

        return "{" . $string . "}";
    }

    public static function pg_array_parse($s, $start = 0, &$end = null)
    {
        if (empty($s) || $s[0] != '{') return $s;
        $return = array();
        $string = false;
        $quote = '';
        $len = strlen($s);
        $v = '';
        for ($i = $start + 1; $i < $len; $i++) {
            $ch = $s[$i];

            if (!$string && $ch == '}') {
                if ($v !== '' || !empty($return)) {
                    $return[] = $v;
                }
                $end = $i;
                break;
            } elseif (!$string && $ch == '{') {
                $v = Utils::pg_array_parse($s, $i, $i);
            } elseif (!$string && $ch == ',') {
                $return[] = $v;
                $v = '';
            } elseif (!$string && ($ch == '"' || $ch == "'")) {
                $string = true;
                $quote = $ch;
            } elseif ($string && $ch == $quote && $s[$i - 1] == "\\") {
                $v = substr($v, 0, -1) . $ch;
            } elseif ($string && $ch == $quote && $s[$i - 1] != "\\") {
                $string = false;
            } else {
                $v .= $ch;
            }
        }

        return $return;
    }

    public static function buildClauseFromArray(&$array, $column, $clause = 'OR')
    {
        if (!$array) return " ";

        $string = "";

        $string .= "(";

        $idx = 1;
        foreach ($array as $key => $value) {

            $string .= "$column = '$value'";

            if (count($array) != $idx) {
                $string .= " OR ";
            }

            $idx++;
        }

        $string .= ")";

        return $string;
    }
}