<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/5/2017
 * Time: 11:09 AM
 */

namespace App\DBManagers;


use App\Utils;

class DBBase
{

    /** @var  string $db_base - name of the database */
    private $db_name;

    /** @var  string $query */
    private $query;

    /**
     * DBBase constructor
     *
     * @param string $db_name
     */
    public function __construct($db_name)
    {
        $this->db_name = $db_name;
    }

    private function prepareInsertQuery($vals_array)
    {
        //generate query
        $idx = "(";
        $vals = "(";

        $tmp_counter = 1;
        foreach ($vals_array as $key => $cell) {
            if ($key == 'id') continue;

            $idx .= "$key";
            $vals .= "'$cell'";
            if ((count($vals_array) - 1) != $tmp_counter++) {
                $idx .= ',';
                $vals .= ',';
            }
        }
        $idx .= ')';
        $vals .= ')';

        $this->query = "INSERT INTO " . $this->db_name . " " . $idx . " VALUES " . $vals . " RETURNING *";
    }

    private function prepareUpdateQuery($vals_array)
    {
        //generate query
        $vals = '';
        $tmp_counter = 1;
        foreach ($vals_array as $key => $cell) {
            if ($key == 'id') continue;

            $vals .= $key . " = '" . $cell . "'";
            if ((count($vals_array) - 1) != $tmp_counter++) {
                $vals .= ', ';
            }

        }

        $this->query = "UPDATE " . $this->db_name . " SET " . $vals . " WHERE id = '" . $vals_array['id'] . "'";
    }

    private function prepareDeleteQuery($vals_array)
    {
        $this->query = "DELETE FROM " . $this->db_name . " WHERE id='" . $vals_array['id'] . "';";
    }

    public function generateQuery($type, $vals_array)
    {
        if ($type == 'insert') {

            $this->prepareInsertQuery($vals_array);

        } else if ($type == 'update') {

            $this->prepareUpdateQuery($vals_array);

        } else if ($type == 'delete') {

            $this->prepareDeleteQuery($vals_array);

        }

        return $this->query;
    }

    protected function generatePostgreArray($array, $single = false)
    {
        return Utils::generatePostgreArray($array, $single);
    }

    protected function pg_array_parse($s, $start = 0, &$end = null)
    {
        return Utils::pg_array_parse($s, $start, $end);
    }

    protected function escapeVar($var)
    {
        $data = [];

        if (!is_array($var) && is_string($var)) {
            return pg_escape_string($var);
        }

        foreach ($var as $key => $value) {

            $data[$key] = $this->escapeVar($value);

        }

        return $data = [];
    }

    protected function entityDecode($array)
    {
        $data = [];

        if (!is_array($array) && is_string($array)) {
            return html_entity_decode($array);
        }

        foreach ($array as $key => $value) {
            $data[$key] = $this->entityDecode($value);
        }

        return $data;
    }
}