<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 9/20/2017
 * Time: 4:11 PM
 */

namespace App\Managers;


use PhpOffice\PhpSpreadsheet\Calculation\DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Export
{
	/**
	 * @var Export $instance
	 */
	private static $instance;

	/**
	 * Returns Export instance
	 *
	 * @return Export - instance
	 */
	public static function getInstance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * setup the Export Class
	 */
	public static function initialize()
	{

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * Export instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Export constructor
	 */
	protected function __construct()
	{

	}

	private function buildHeaderFields($array)
	{
		$columns = array();

		$data = array();
		foreach ($array as $key => $value) {
			if (array_key_exists($key, $columns)) {
				array_push($data, $columns[$value]);
			} else {
				array_push($data, $key);
			}
		}

		return $data;
	}

	public function outputCSV($array, $filename)
	{

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);

		$output = fopen('php://output', 'w');

		fputcsv($output, $this->buildHeaderFields($array[0]));

		foreach ($array as $value) {

			fputcsv($output, $value);
		}
	}

	public function outputXLS($array, $filename)
	{
		$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
			'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];

		$spreadsheet = new Spreadsheet();
		$spreadsheet->getProperties()
			->setCreator(COMPANY_NAME)
			->setLastModifiedBy(COMPANY_NAME)
			->setTitle("Export " . $filename . " - " . (new \DateTime())->format(INTERNAL_DATE_FORMAT) . " " . COMPANY_NAME)
			->setSubject("Export " . $filename . " - " . (new \DateTime())->format(INTERNAL_DATE_FORMAT) . " " . COMPANY_NAME)
			->setDescription("Export " . $filename . " - " . (new \DateTime())->format(INTERNAL_DATE_FORMAT) . " " . COMPANY_NAME)
			->setKeywords("export " . $filename . " - " . (new \DateTime())->format(INTERNAL_DATE_FORMAT))
			->setCategory("Export");

		$i = 0;
		foreach ($array[0] as $key => $value) {
			$spreadsheet->getActiveSheet()->setCellValue($columns[$i] . '1', $key);
			$i++;
		}

		$spreadsheet->getActiveSheet()->fromArray($array, NULL, 'A2');

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Export ' . $filename . ' - ' . (new \DateTime())->format(INTERNAL_DATE_FORMAT) . '.xls"');
		header('Cache-Control: max-age=0');

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');

		$writer->save('php://output');
	}
}