<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 8/11/2017
 * Time: 2:41 PM
 */

namespace App\Managers;


use App\DBManagers\DBClient;
use DBManagers\DBService;
use DBManagers\DBServiceShortcodeOperator;
use DBManagers\DBShortcode;
use SebastianBergmann\CodeCoverage\Util;

class Statistics
{
	/** @var string $DB_IN - VAS INCOMMING TABLE */
	public static $DB_SMS_IN = 'vas_in_combined';

	/** @var string $DB_IN - VAS OUTGOING TABLE */
	public static $DB_SMS_OUT = 'vas_out_combined';

	/** @var string $DB_COUNTER_IN INCOMMING TABLE */
	public static $DB_COUNTER_IN = 'counters_in';

	/** @var string $DB_COUNTER_OUT OUTGOING TABLE */
	public static $DB_COUNTER_OUT = 'counters_out';

	/** @var string $DB_COUNTER_bSMS bSMS counters table */
	public static $DB_COUNTER_bSMS = 'counters_bsms';

	/** @var string $DB_COUNTERS_VOICE VOICE counters table */
	public static $DB_COUNTERS_VOICE = 'counters_voice';

	/**
	 * @var Statistics $instance
	 */
	private static $instance;

	/** @var  \dbaccess $database */
	private $database;

	/** @var  \dbaccess $db_stat */
	protected $db_stat;

	/**
	 * Returns Statistics instance
	 *
	 * @return Statistics - instance
	 */
	public static function getInstance($database)
	{
		if (null === static::$instance) {
			static::$instance = new static($database);
		}

		return static::$instance;
	}

	/**
	 * setup the Statistics Class
	 */
	public static function initialize()
	{

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * Statistics instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Statistics constructor
	 *
	 * @param \dbaccess $database
	 */
	protected function __construct($database)
	{
		$this->database = $database;

		//LOAD Statistics Database INSTANCE
		//DATABSE WITH STATISTICS
		$local_database_settings_pallene["dbhost"] = getenv('DB_STAT_HOST');
		$local_database_settings_pallene["dbport"] = getenv('DB_STAT_PORT');
		$local_database_settings_pallene["dbuser"] = getenv('DB_STAT_USER');
		$local_database_settings_pallene["dbpass"] = getenv('DB_STAT_PASS');
		$local_database_settings_pallene["dbname"] = getenv('DB_STAT_NAME');

		$this->db_stat = new \dbaccess($local_database_settings_pallene);

	}

	/**
	 * @param array $intime
	 * @param $allowed_services
	 * @param $service
	 * @param $shortcode
	 * @param $graph_type
	 * @return array|bool
	 */
	public function voiceCountersBySid($intime, $allowed_shortcodes, $service, $shortcode, $graph_type)
	{
		$data = array();
		$array = array();

		$list_shortcodes = array();

		if (is_array($allowed_shortcodes)) {
			foreach ($allowed_shortcodes as $value) {
				array_push($list_shortcodes, (int)$value['shortcode']);
			}
		}

		$shortcodes_condition = (count($list_shortcodes) > 0) ? "AND " . $this->buildAllowedServices($list_shortcodes, 'e.shortcode') : '';

		if ($service != '') {
			$obj_service = DBService::loadFromId($service, $this->database);
			$res_service = array();

			if ($obj_service) {
				/** @var DBServiceShortcodeOperator $value */
				foreach ($obj_service->shortcodes as $value) {
					array_push($res_service, $value->shortcode);
				}
			}

			if (count($res_service) == 0) {
				$service_condition = "AND " . $this->buildAllowedServices(array('0' => '0'), 'e.shortcode');
			} else {
				$service_condition = "AND " . $this->buildAllowedServices($res_service, 'e.shortcode');
			}
		} else {
			$service_condition = '';
		}

		$shortcode_condition = ($shortcode != '') ? "AND e.shortcode = '$shortcode'" : "";

		$date_condition = "(year_call || '-' || month_call || '-' || day_call)::date >= '" . $intime['start'] . "'::date AND (year_call || '-' || month_call || '-' || day_call)::date <= '" . $intime['end'] . "'::date";

		if ($graph_type == 'month') {
			//Moths view
			$columns = "sum(v.count_call) as sum, v.year_call, v.month_call, v.extension";
			$group = "GROUP BY v.year_call, v.month_call, v.extension ORDER BY v.year_call ASC, v.month_call ASC";

			//Filling Array with all Months

			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1M');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-01'));
			}

		} else {
			//Days view
			$columns = "sum(v.count_call) as sum, v.year_call, v.month_call, v.day_call, v.extension";
			$group = "GROUP BY v.year_call, v.month_call, v.day_call, v.extension ORDER BY v.year_call ASC, v.month_call ASC, v.day_call ASC";

			//filling array with all days
			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1D');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-d'));
			}
		}

		$query = "SELECT $columns " .
			" FROM (SELECT * FROM " . self::$DB_COUNTERS_VOICE . " WHERE $date_condition) as v
			LEFT JOIN extensions_shortcodes as e ON v.extension = e.extension
			WHERE TRUE $shortcodes_condition $service_condition $shortcode_condition
			$group";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {

			return array();
		}

		if ($this->db_stat->rows() > 0) {

			$extensions = array();
			//Build up array for every unique extension

			foreach ($res as $value) {

				if (!array_key_exists($value['extension'], $extensions)) {
					$extensions[$value['extension']] = array();
				}

				if ($graph_type == 'month') {
					$tmp = array(
						'sum' => $value['sum'],
						'year_call' => $value['year_call'],
						'month_call' => $value['month_call']
					);
				} else {
					$tmp = array(
						'sum' => $value['sum'],
						'year_call' => $value['year_call'],
						'month_call' => $value['month_call'],
						'day_call' => $value['day_call']
					);
				}


				array_push($extensions[$value['extension']], $tmp);
			}

			$data['total'] = array();

			foreach ($array as $builder) {

				$data['total'][$builder] = array();

				foreach ($res as $cell) {

					if ($graph_type == 'month') {
						$needle = date('Y-m-d', strtotime($cell['year_call'] . '-' . $cell['month_call'] . '-01'));
					} else {
						$needle = date('Y-m-d', strtotime($cell['year_call'] . '-' . $cell['month_call'] . '-' . $cell['day_call']));
					}

					if ($builder == $needle) {

						if (array_key_exists('sum', $data['total'][$builder])) {
							$data['total'][$builder]['sum'] += $cell['sum'];
						} else {

							if ($graph_type == 'month') {
								$cell['label'] = date('M', strtotime($builder));
							} else {
								$cell['label'] = date('d M', strtotime($builder));
							}

							$data['total'][$builder] = $cell;
						}

					} else {

						if (count($data['total'][$builder]) == 0) {

							if ($graph_type == 'month') {
								$data['total'][$builder] = array(
									"sum" => "0",
									"year_sms" => date('Y', strtotime($builder)),
									"month_sms" => date('m', strtotime($builder)),
									"label" => date('M', strtotime($builder))
								);
							} else {
								$data['total'][$builder] = array(
									"sum" => "0",
									"year_sms" => date('Y', strtotime($builder)),
									"month_sms" => date('m', strtotime($builder)),
									"day_sms" => date('d', strtotime($builder)),
									"label" => date('d M', strtotime($builder))
								);
							}
						}
					}

				}

			}

			foreach ($extensions as $key => $value) {

				$data[$key] = array();

				foreach ($array as $builder) {

					$data[$key][$builder] = array();

					foreach ($value as $cell) {

						if ($graph_type == 'month') {
							$needle = date('Y-m-d', strtotime($cell['year_call'] . '-' . $cell['month_call'] . '-01'));
						} else {
							$needle = date('Y-m-d', strtotime($cell['year_call'] . '-' . $cell['month_call'] . '-' . $cell['day_call']));
						}

						if ($builder == $needle) {
							if ($graph_type == 'month') {
								$cell['label'] = date('M', strtotime($builder));
							} else {
								$cell['label'] = date('d M', strtotime($builder));
							}

							$data[$key][$builder] = $cell;
						} else {

							if (count($data[$key][$builder]) == 0) {
								if ($graph_type == 'month') {
									$data[$key][$builder] = array(
										"sum" => "0",
										"year_sms" => date('Y', strtotime($builder)),
										"month_sms" => date('m', strtotime($builder)),
										"label" => date('M', strtotime($builder))
									);
								} else {
									$data[$key][$builder] = array(
										"sum" => "0",
										"year_sms" => date('Y', strtotime($builder)),
										"month_sms" => date('m', strtotime($builder)),
										"day_sms" => date('d', strtotime($builder)),
										"label" => date('d M', strtotime($builder))
									);
								}
							}

						}
					}
				}

			}

		}

		return $data;

	}

	/**
	 * @param $pagination
	 * @param $intime
	 * @param $order
	 * @param $allowed_services
	 * @param $service
	 * @param $shortcode
	 * @param $msisdn
	 * @return array
	 */
	public function voice($pagination, $intime, $order, $allowed_shortcodes, $service, $shortcode, $msisdn)
	{
		$data = array();

		$list_shortcodes = array();
		if (is_array($allowed_shortcodes)) {
			foreach ($allowed_shortcodes as $value) {
				array_push($list_shortcodes, (int)$value['shortcode']);
			}
		}

		$shortcodes_condition = (count($list_shortcodes) > 0) ? "AND " . $this->buildAllowedServices($list_shortcodes, 'es.shortcode') : '';

		if ($service != '') {
			$obj_service = DBService::loadFromId($service, $this->database);
			$res_service = array();

			if ($obj_service) {
				/** @var DBServiceShortcodeOperator $value */
				foreach ($obj_service->shortcodes as $value) {
					array_push($res_service, $value->shortcode);
				}
			}

			if (count($res_service) == 0) {
				$service_condition = "AND " . $this->buildAllowedServices(array('0' => '0'), 'es.shortcode');
			} else {
				$service_condition = "AND " . $this->buildAllowedServices($res_service, 'es.shortcode');
			}
		} else {
			$service_condition = '';
		}

		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '" . $this->database->escape($shortcode) . "'" : "";

		$msisdn_condition = ($msisdn != '') ? "AND (trim(both '\"' from c.asserted_identity) = '" . $this->database->escape($msisdn) . "' OR trim(both '\"' from c.asserted_identity) = '0" . substr($this->database->escape($msisdn), 3) . "')" : "";

		if (is_array($pagination)) {
			$extend_lenght = $pagination['length'] + 1;
			$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		} else {
			$pagination = array();
			$pagination['start'] = 0;
			$pagination['length'] = 0;

			$extend_lenght = 0;
			$pagination_string = '';
		}

		$date_condition = "answer >= '" . $intime['start'] . "' AND answer <= '" . $intime['end'] . "'";

		if (array_key_exists('column', $order) && array_key_exists('dir', $order)) {

		} else {
			$order['column'] = 0;
			$order['dir'] = 'ASC';
		}
		//ORDER
		switch ($order['column']) {
			case '0' :
				$order_dir = $order['dir'];
				$order_string = "ORDER BY c.start $order_dir";
				break;
			case '1':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY c.end $order_dir";
				break;
			case '2':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY es.operator_id $order_dir";
				break;
			case '3':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY es.shortcode $order_dir";
				break;
			case '4':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY asserted_identity $order_dir";
				break;
			case '5':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY c.billsec $order_dir";
				break;
			case '6':
				$order_dir = $order['dir'];
				$order_string = "ORDER BY c.disposition $order_dir";
				break;
			case '7':
				$order_dir = $order['dir'];
				$order_string = "";
				break;
			case '8':
				$order_dir = $order['dir'];
				$order_string = "";
				break;
			default :
				$order_dir = 'ASC';
				$order_string = "ORDER BY c.start $order_dir";
				break;
		}

		$query_full = "select c.peeraccount,c.uniqueid,c.start,c.cdr_id,c.answer,c.end,c.billsec,c.disposition,c.voicefile,trim(both '\"' from c.asserted_identity) as asserted_identity, es.operator_id,es.shortcode From
					(
					select * from cdr
					where
						$date_condition AND lastapp='Dial' AND disposition in ('ANSWERED', 'NO ANSWER', 'BUSY')
					) as c
					LEFT JOIN extensions_shortcodes as es ON (es.extension=c.extension) WHERE TRUE $shortcodes_condition $service_condition $shortcode_condition $msisdn_condition";

		$query_res = $query_full;


		$query_pagination = $query_res . " " . $order_string . " " . $pagination_string;

		//For Manual Ordering
		if ($order['column'] == 7 || $order['column'] == 8) {
			$query_pagination = $query_res;
		}

		$res = $this->db_stat->execute($query_pagination);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as &$value) {

				$shortcode_id = ShortcodeManager::getInstance($this->database)->getShortcodeIdByName($value['shortcode']);

				$arr_service_names = array();
				if (is_numeric($shortcode_id)) {
					$obj_services_shortcodes = ServiceManager::getInstance($this->database)->loadServicesByShortcode($shortcode_id);

					/** @var DBServiceShortcodeOperator $cell */
					foreach ($obj_services_shortcodes as $cell) {

						if (!array_key_exists($cell->service_id, $data)) {

							$sc = $this->getServiceClientNames($cell->service_id);

							$arr_service_names[$cell->service_id] = array(
								'service_name' => $sc['service_name'],
								'client_id' => $sc['client_id'],
								'client_name' => $sc['client_name']
							);
						}

					}
				}

				$value['service_name'] = '';
				$value['client_name'] = '';
				foreach ($arr_service_names as $cell) {
					$value['service_name'] .= $cell['service_name'] . ' ';
					$value['client_name'] .= $cell['client_name'] . ' ';
				}

				$value['start'] = date('d.m.Y H:i:s', strtotime($value['start']));
				$value['end'] = date('d.m.Y H:i:s', strtotime($value['end']));

				$name = OperatorManager::getInstance($this->database)->getOperatorNameById($value['operator_id']);
				$value['operator'] = $name;
				$value['billsec'] = Utils::billsec_conv($value['billsec']);
			}

			if ($order['column'] == 7) {
				$service = array();
				foreach ($res as $key => $value) {
					$service[$key] = $value['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}

			if ($order['column'] == 8) {
				$client = array();
				foreach ($res as $key => $value) {
					$client[$key] = $value['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}
		}

		$data['data'] = $res;

		return $data;
	}

	public function voiceCountersByShortcode($intime, $allowed_shortcodes, $service, $shortcode)
	{
		$data = array();

		$list_shortcodes = array();
		if (is_array($allowed_shortcodes)) {
			foreach ($allowed_shortcodes as $value) {
				array_push($list_shortcodes, (int)$value['shortcode']);
			}
		}

		$shortcodes_condition = (count($list_shortcodes) > 0) ? "AND " . $this->buildAllowedServices($list_shortcodes, 'e.shortcode') : '';

		if ($service != '') {
			$obj_service = DBService::loadFromId($service, $this->database);
			$res_service = array();

			if ($obj_service) {
				/** @var DBServiceShortcodeOperator $value */
				foreach ($obj_service->shortcodes as $value) {
					array_push($res_service, $value->shortcode);
				}
			}

			if (count($res_service) == 0) {
				$service_condition = "AND " . $this->buildAllowedServices(array('0' => '0'), 'e.shortcode');
			} else {
				$service_condition = "AND " . $this->buildAllowedServices($res_service, 'e.shortcode');
			}
		} else {
			$service_condition = '';
		}

		$shortcode_condition = ($shortcode != '') ? "AND e.shortcode = '$shortcode'" : "";

		$date_condition = "(year_call || '-' || month_call || '-' || day_call)::date >= '" . $intime['start'] . "'::date AND (year_call || '-' || month_call || '-' || day_call)::date <= '" . $intime['end'] . "'::date";

		$query_res = "SELECT sum(v.count_call) as sum, sum(v.sum_billsec) as sum_billsec, v.year_call, v.month_call, e.shortcode " .
			" FROM (SELECT * FROM " . self::$DB_COUNTERS_VOICE . " WHERE $date_condition) as v LEFT JOIN extensions_shortcodes as e ON v.extension = e.extension WHERE TRUE $shortcodes_condition $service_condition $shortcode_condition GROUP BY v.year_call, v.month_call, e.shortcode ORDER BY year_call ASC, month_call ASC, sum DESC;";

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$total_shity_sum = 0;
		$total_shity_sec = 0;

		if ($this->db_stat->rows() > 0) {

			foreach ($res as $value) {
				$date = $value['year_call'] . '-' . $value['month_call'];

				$total_shity_sec += $value['sum_billsec'];
				$total_shity_sum += $value['sum'];

				if (array_key_exists($date, $data)) {
					$data[$date]['sum'] += $value['sum'];
					$data[$date]['sum_billsec'] += $value['sum_billsec'];
				} else {
					$data[$date] = array();
					$data[$date]['sum'] = $value['sum'];
					$data[$date]['sum_billsec'] = $value['sum_billsec'];
				}

				$tmp = array();

				$tmp['year_sms'] = $value['year_call'];
				$tmp['month_sms'] = $value['month_call'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['shortcode_sum'] = $value['sum'];
				$tmp['billsec'] = $value['sum_billsec'];
				$tmp['sum_billsec'] = Utils::billsec_conv($value['sum_billsec']);

				array_push($data[$date], $tmp);

			}

			foreach ($data as &$value) {
				$value['sum_billsec'] = Utils::billsec_conv($value['sum_billsec']);
			}
		}

		return array(
			'sum' => $total_shity_sum,
			'billsec' => $total_shity_sec,
			'billsec_human' => Utils::billsec_conv($total_shity_sec),
			'data' => $data
		);

	}

	/**
	 * @param $intime
	 * @param $shortcode
	 * @param $sid
	 * @return array
	 */
	public function voiceCountersByOper($intime, $shortcode, $allowed_shortcodes, $operator_id = '')
	{
		$data = array();

		$list_shortcodes = array();
		if (is_array($allowed_shortcodes)) {
			foreach ($allowed_shortcodes as $value) {
				array_push($list_shortcodes, (int)$value['shortcode']);
			}
		}

		$operator_condition = ($operator_id == '') ? '' : "AND e.operator_id = '" . $this->database->escape($operator_id) . "'";
		$shortcode_condition = "AND e.shortcode = '" . $this->db_stat->escape((int)$shortcode) . "'";

		$shortcodes_condition = (count($list_shortcodes) > 0) ? "AND " . $this->buildAllowedServices($list_shortcodes, 'e.shortcode') : '';

		$query = "SELECT sum(v.count_call) as sum, sum(v.sum_billsec) as sum_billsec, e.operator_id " .
			" FROM (SELECT * FROM " . self::$DB_COUNTERS_VOICE . " WHERE year_call = '" . $intime['year'] . "' AND month_call = '" . $intime['month'] . "') as v LEFT JOIN extensions_shortcodes as e ON v.extension = e.extension WHERE TRUE $shortcodes_condition $shortcode_condition $operator_condition GROUP BY e.operator_id;";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {

			return $data;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$name = OperatorManager::getInstance($this->database)->getOperatorNameById($value['operator_id']);
				$value['operator_name'] = $name;
				$value['sum_billsec_raw'] = $value['sum_billsec'];
				$value['sum_billsec'] = Utils::billsec_conv($value['sum_billsec']);
			}

			$data = $res;
		}

		return $data;

	}

	public function voiceCounters($pagination, $intime, $order, $allowed_shortcodes, $service, $shortcode)
	{
		$data = array();
		$data['data'] = array();

		$list_shortcodes = array();
		if (is_array($allowed_shortcodes)) {
			foreach ($allowed_shortcodes as $value) {
				array_push($list_shortcodes, (int)$value['shortcode']);
			}
		}

		$shortcodes_condition = (count($list_shortcodes) > 0) ? "AND " . $this->buildAllowedServices($list_shortcodes, 'e.shortcode') : '';

		if ($service != '') {
			$obj_service = DBService::loadFromId($service, $this->database);
			$res_service = array();

			if ($obj_service) {
				/** @var DBServiceShortcodeOperator $value */
				foreach ($obj_service->shortcodes as $value) {
					array_push($res_service, $value->shortcode);
				}
			}

			if (count($res_service) == 0) {
				$service_condition = "AND " . $this->buildAllowedServices(array('0' => '0'), 'e.shortcode');
			} else {
				$service_condition = "AND " . $this->buildAllowedServices($res_service, 'e.shortcode');
			}
		} else {
			$service_condition = '';
		}

		$shortcode_condition = ($shortcode != '') ? "AND e.shortcode = '$shortcode'" : "";

		if (is_array($pagination)) {
			$extend_lenght = $pagination['length'] + 1;
			$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		} else {
			$pagination = array();
			$pagination['start'] = 0;
			$pagination['length'] = 0;
			$extend_lenght = 0;
			$pagination_string = '';
		}


		$date_condition = "(year_call || '-' || month_call || '-' || day_call)::date >= '" . $intime['start'] . "'::date AND (year_call || '-' || month_call || '-' || day_call)::date <= '" . $intime['end'] . "'::date";

		if (array_key_exists('column', $order) && array_key_exists('dir', $order)) {

		} else {
			$order['column'] = 0;
			$order['dir'] = 'ASC';
		}

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY v.year_call $order_dir, v.month_call $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY e.shortcode $order_dir";
				break;
			case '2':
				$order_string = "";
				break;
			case '3':
				$order_string = "";
				break;
			case '4':
				$order_string = "ORDER BY sum $order_dir";
				break;
			case '5':
				$order_string = "ORDER BY sum_billsec $order_dir";
				break;
			default :
				$order_string = "ORDER BY v.year_call $order_dir, v.month_call $order_dir";
				break;
		}

		$query_res = "SELECT sum(v.count_call) as sum, sum(v.sum_billsec) as sum_billsec, v.year_call, v.month_call, e.shortcode " .
			" FROM (SELECT * FROM " . self::$DB_COUNTERS_VOICE . " WHERE $date_condition) as v LEFT JOIN extensions_shortcodes as e ON v.extension = e.extension WHERE TRUE $shortcodes_condition $service_condition $shortcode_condition GROUP BY v.year_call, v.month_call, e.shortcode $order_string $pagination_string;";

		//For the Manual ordering
		if ($order['column'] == '2' || $order['column'] == '3') {
			$query_res = "SELECT sum(v.count_call) as sum, sum(v.sum_billsec) as sum_billsec, v.year_call, v.month_call, e.shortcode " .
				" FROM (SELECT * FROM " . self::$DB_COUNTERS_VOICE . " WHERE $date_condition) as v LEFT JOIN extensions_shortcodes as e ON v.extension = e.extension WHERE TRUE $shortcodes_condition $service_condition $shortcode_condition GROUP BY year_call, month_call, e.shortcode $order_string;";
		}

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_call'] . '-' . $value['month_call'];
				$tmp['shortcode'] = $value['shortcode'];

				$tmp['billsec'] = $value['sum_billsec'];

				$tmp['sum_billsec'] = Utils::billsec_conv($value['sum_billsec']);

				$tmp['shortcode_sum'] = $value['sum'];

				$tmp['sum'] = $value['sum'] .
					'<a class="pull-right" data-type="in" data-html="true" data-toggle="popoverin" data-placement="left" data-content="Content" ' .
					'data-shortcode="' . $value['shortcode'] . '" ' .
					'data-year="' . $value['year_call'] . '" ' .
					'data-month="' . $value['month_call'] . '" ' .
					'><span class="glyphicon glyphicon-eye-open"></span></a>';

				$shortcode_id = ShortcodeManager::getInstance($this->database)->getShortcodeIdByName($value['shortcode']);

				$arr_service_names = array();
				if (is_numeric($shortcode_id)) {
					$obj_services_shortcodes = ServiceManager::getInstance($this->database)->loadServicesByShortcode($shortcode_id);

					/** @var DBServiceShortcodeOperator $cell */
					foreach ($obj_services_shortcodes as $cell) {

						if (!array_key_exists($cell->service_id, $data)) {

							$sc = $this->getServiceClientNames($cell->service_id);

							$arr_service_names[$cell->service_id] = array(
								'service_name' => $sc['service_name'],
								'client_id' => $sc['client_id'],
								'client_name' => $sc['client_name']
							);
						}

					}
				}

				$tmp['service_name'] = '';
				$tmp['client_name'] = '';
				foreach ($arr_service_names as $cell) {
					$tmp['service_name'] .= $cell['service_name'] . ' ';
					$tmp['client_name'] .= $cell['client_name'] . ' ';
				}

				array_push($data['data'], $tmp);
			}

			if ($order['column'] == '2') {
				$client = array();
				foreach ($data['data'] as $key => $row) {
					$client[$key] = $row['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $extend_lenght);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

			if ($order['column'] == '3') {
				$service = array();
				foreach ($data['data'] as $key => $row) {
					$service[$key] = $row['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $pagination['length']);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

		}

		return $data;

	}

	public function bSMSExportCounters($intime, $allowed_services, $service, $shortcode)
	{
		$data = array();

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '$shortcode'" : "";

		$date_condition = "(year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] . "'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date";

		$order_string = "ORDER BY year_sms ASC, month_sms ASC";


		$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
			" FROM " . self::$DB_COUNTER_bSMS . " WHERE $date_condition $services_condition $service_condition $shortcode_condition GROUP BY year_sms, month_sms, sid, shortcode $order_string;";

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		if ($this->db_stat->rows() > 0) {

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_sms'] . '-' . $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['sum'] = $value['sum'];
				$tmp['sid'] = $value['sid'];

				$sc = $this->getServiceClientNames($value['sid']);
				$tmp['service_name'] = $sc['service_name'];
				$tmp['client_id'] = $sc['client_id'];
				$tmp['client_name'] = $sc['client_name'];

				array_push($data, $tmp);
			}
		}

		return $data;

	}

	/**
	 * @param array $intime
	 * @param $allowed_services
	 * @param $service
	 * @param $shortcode
	 * @param $graph_type
	 * @return array|bool
	 */
	public function bSMSCountersBySid($intime, $allowed_services, $service, $shortcode, $graph_type)
	{
		$data = array();
		$array = array();

		$services_condition = $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND a.sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND a.shortcode = '$shortcode'" : "";

		if ($graph_type == 'month') {
			//Moths view
			$columns = "sum(count_sms) as sum, year_sms, month_sms, sid";
			$group = "GROUP BY year_sms, month_sms, sid ORDER BY year_sms ASC, month_sms ASC";

			//Filling Array with all Months

			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1M');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-01'));
			}

		} else {
			//Days view
			$columns = "sum(count_sms) as sum, year_sms, month_sms, day_sms, sid";
			$group = "GROUP BY year_sms, month_sms, day_sms, sid ORDER BY year_sms ASC, month_sms ASC, day_sms ASC";

			//filling array with all days
			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1D');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-d'));
			}
		}

		$query = "SELECT $columns FROM (SELECT * FROM " . self::$DB_COUNTER_bSMS .
			" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
			"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
			"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition" .
			" $group";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {
			return array();
		}

		if ($this->db_stat->rows() > 0) {

			foreach ($res as $value) {

				if ($graph_type == 'month') {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-01'));
				} else {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-' . $value['day_sms']));
				}

				if (Utils::in_array_recur($needle, $array)) {
					if ($graph_type == 'month') {
						$value['label'] = date('M', strtotime($needle));
					} else {
						$value['label'] = date('d M', strtotime($needle));
					}

					array_push($data, $value);
				} else {

					if ($graph_type == 'month') {
						array_push($data, array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"label" => date('M', strtotime($needle))
						));
					} else {
						array_push($data, array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"day_sms" => date('d', strtotime($needle)),
							"label" => date('d M', strtotime($needle))
						));
					}
				}

			}

		}

		return $data;

	}

	/**
	 * @param $pagination
	 * @param $intime
	 * @param $order
	 * @param $allowed_services
	 * @param $service
	 * @param $shortcode
	 * @param $msisdn
	 * @return array
	 */
	public function bSMS($pagination, $intime, $order, $allowed_services, $service, $shortcode, $msisdn, $uid)
	{
		$data = array();

		$services_condition = " AND " . $this->buildAllowedServices($allowed_services, 'service_id');
		$service_condition = ($service != '') ? "AND service_id = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '" . $this->database->escape($shortcode) . "'" : "";

		$msisdn_condition = ($msisdn != '') ? "AND msisdn = '" . $this->database->escape($msisdn) . "'" : "";
		$uid_condition = ($uid != '') ? "AND client_sms_id = '" . $this->database->escape($uid) . "'" : "";


		if (is_array($pagination)) {
			$extend_lenght = $pagination['length'] + 1;
			$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		} else {
			$pagination = array();
			$pagination['start'] = 0;
			$pagination['length'] = 0;

			$extend_lenght = 0;
			$pagination_string = '';
		}

		$date_condition = "otime >= '" . $intime['start'] . "' AND otime <= '" . $intime['end'] . "' AND intime >= '" . $intime['start'] . "'::TIMESTAMP - INTERVAL '24 hours' * 7 AND intime <= '" . $intime['end'] . "'";

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY i.otime $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY i.senttime $order_dir";
				break;
			case '3':
				$order_string = "ORDER BY i.msisdn $order_dir";
				break;
			case '4':
				$order_string = "ORDER BY i.callednum $order_dir";
				break;
			case '5':
				$order_string = "ORDER BY i.serviceid $order_dir";
				break;
			case '6':
				$order_string = "";
				break;
			case '7':
				$order_string = "";
				break;
			default :
				$order_string = "ORDER BY i.otime $order_dir";
				break;
		}

		$query_full = "SELECT DISTINCT ON (last_value(client_sms_id) OVER wnd, last_value(service_id) OVER wnd, last_value(msisdn) OVER wnd)
                last_value(id) OVER wnd AS id,
                last_value(otime) OVER wnd AS otime,
                last_value(ptime) OVER wnd AS ptime,
                last_value(mesg) OVER wnd AS mesg,
                last_value(msisdn) OVER wnd AS msisdn,
                CASE
                    WHEN last_value(shortcode_long) OVER wnd IS NULL OR last_value(shortcode_long) OVER wnd <>''  THEN last_value(shortcode) OVER wnd
                    ELSE last_value(shortcode_long) OVER wnd
                END AS shortcode,
                last_value(operator_id) OVER wnd AS operator_id,
                last_value(client_sms_id) OVER wnd AS client_sms_id,
                last_value(service_id) OVER wnd AS service_id,
                last_value(message_parts) OVER wnd AS message_parts,
                last_value(message_encoding) OVER wnd AS message_encoding,
                last_value(ext_id) OVER wnd AS ext_id,
                last_value(validity) OVER wnd AS validity
               	FROM bsms_in
              	WHERE TRUE
				AND (
				request_ip NOT IN (
					'84.21.200.11/32', -- office
    			    '217.75.128.62/32', -- darkwater real
    			    '10.9.0.157/32', -- darkwater OpenVPN
    			    '91.209.8.36/32', -- aegir real
    			    '10.9.0.211/32', -- aegir OpenVPN
    			    '127.0.0.1/32' --localhost
    			    )
    			    OR service_id IN (1025) --services with direct DB link - 734 also is with DB link, but has appropriate request ip
    			)
    			AND validity>0
				--condition
				AND $date_condition $services_condition $service_condition $shortcode_condition $msisdn_condition $uid_condition
           		WINDOW wnd AS (PARTITION BY client_sms_id, service_id, msisdn ORDER BY intime, id ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)";

		$query_res = $query_full . " " . $pagination_string;

		/*
		$query_pagination = $query_res ." " . $order_string . " " . $pagination_string;

		//For Manual Ordering
		if ($order['column'] == 6 || $order['column'] == 7) {
			$query_pagination = $query_res;
		}
		*/

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as &$value) {
				$tmp = $this->getServiceClientNames($value['service_id']);
				$value['service_name'] = $tmp['service_name'];
				$value['client_id'] = $tmp['client_id'];
				$value['client_name'] = $tmp['client_name'];
				$value['otime'] = date('d.m.Y H:i:s', strtotime($value['otime']));
				$value['ptime'] = date('d.m.Y H:i:s', strtotime($value['ptime']));
				$value['dlr'] = $value['ext_id'];
				$value['mesg'] = base64_decode($value['mesg']);
			}

			/*
			if ($order['column'] == 6) {
				$service = array();
				foreach($res as $key=>$value) {
					$service[$key] = $value['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}

			if ($order['column'] == 7) {
				$client = array();
				foreach($res as $key=>$value) {
					$client[$key] = $value['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}
			*/
		}

		$data['data'] = $res;

		return $data;
	}

	/**
	 * @param array $intime
	 * @param string $type
	 * @return array|bool
	 */
	public function bSMSCountersByShortcode($intime, $allowed_services, $service, $shortcode)
	{
		$data = array();

		$services_condition = $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND a.sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND a.shortcode = '$shortcode'" : "";


		$query = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode FROM (SELECT * FROM " . self::$DB_COUNTER_bSMS .
			" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
			"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
			"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition" .
			" GROUP BY year_sms, month_sms, shortcode ORDER BY year_sms DESC, month_sms DESC, sum DESC";


		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {
			return false;
		}

		$total_sum = 0;

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {

				/*
				$arr = $this->getServiceClientNames($value['sid']);
				$value['service_name'] = $arr['service_name'];
				*/

				$total_sum += $value['sum'];

				$date = $value['year_sms'] . '-' . $value['month_sms'];

				if (array_key_exists($date, $data)) {
					$data[$date]['sum'] += $value['sum'];
				} else {
					$data[$date] = array();
					$data[$date]['sum'] = $value['sum'];
				}

				$tmp = array();

				$tmp['year_sms'] = $value['year_sms'];
				$tmp['month_sms'] = $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				//$tmp['service_name'] = $value['service_name'];
				$tmp['shortcode_sum'] = $value['sum'];

				array_push($data[$date], $tmp);

			}

			return array('total' => $total_sum, 'data' => $data);
		}

		return array();

	}

	/**
	 * @param $intime
	 * @param $shortcode
	 * @param $sid
	 * @param array $allowed_services
	 * @param string $operator_id
	 * @return array
	 */
	public function bSMSCountersByOper($intime, $shortcode, $sid, $allowed_services, $operator_id = '')
	{

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$operator_condition = ($operator_id == '') ? '' : "AND operator_id='" . $this->database->escape($operator_id) . "'";

		$data = array();
		$query = "SELECT sum(a.count_sms) AS sum, a.operator_id FROM (SELECT * FROM " . self::$DB_COUNTER_bSMS . " WHERE " .
			"year_sms='" . $this->database->escape($intime['year']) . "' AND month_sms = '" . $this->database->escape($intime['month']) . "' $services_condition $operator_condition) as a " .
			"WHERE a.sid = '" . $this->database->escape($sid) . "' AND a.shortcode = '" . $this->database->escape($shortcode) . "' GROUP BY a.operator_id ORDER BY a.operator_id ASC;";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {

			return $data;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$name = OperatorManager::getInstance($this->database)->getOperatorNameById($value['operator_id']);
				$value['operator_name'] = $name;
			}

			$data = $res;
		}

		return $data;

	}

	public function bSMSCounters($pagination, $intime, $order, $allowed_services, $service, $shortcode)
	{
		$data = array();
		$data['data'] = array();

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '$shortcode'" : "";

		$extend_lenght = $pagination['length'] + 1;
		$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";

		$date_condition = "(year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] . "'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date";

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY shortcode $order_dir";
				break;
			case '2':
				$order_string = "ORDER BY sid $order_dir";
				break;
			case '3':
				$order_string = "";
				break;
			case '4':
				$order_string = "";
				break;
			case '5':
				$order_string = "ORDER BY sum $order_dir";
				break;
			default :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
		}

		$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
			" FROM " . self::$DB_COUNTER_bSMS . " WHERE $date_condition $services_condition $service_condition $shortcode_condition GROUP BY year_sms, month_sms, sid, shortcode $order_string $pagination_string;";

		//For the Manual ordering
		if ($order['column'] == '3' || $order['column'] == '4') {
			$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
				" FROM " . self::$DB_COUNTER_bSMS . " WHERE $date_condition $services_condition $service_condition $shortcode_condition GROUP BY year_sms, month_sms, sid, shortcode $order_string;";
		}

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_sms'] . '-' . $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['sum'] = $value['sum'] .
					'<a class="pull-right" data-type="in" data-html="true" data-toggle="popoverin" data-placement="left" data-content="Content" ' .
					'data-shortcode="' . $value['shortcode'] . '" ' .
					'data-sid="' . $value['sid'] . '" ' .
					'data-year="' . $value['year_sms'] . '" ' .
					'data-month="' . $value['month_sms'] . '" ' .
					'><span class="glyphicon glyphicon-eye-open"></span></a>';
				$tmp['sid'] = $value['sid'];

				$sc = $this->getServiceClientNames($value['sid']);
				$tmp['service_name'] = $sc['service_name'];
				$tmp['client_id'] = $sc['client_id'];
				$tmp['client_name'] = $sc['client_name'];

				array_push($data['data'], $tmp);
			}

			if ($order['column'] == '3') {
				$client = array();
				foreach ($data['data'] as $key => $row) {
					$client[$key] = $row['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $extend_lenght);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

			if ($order['column'] == '4') {
				$service = array();
				foreach ($data['data'] as $key => $row) {
					$service[$key] = $row['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $pagination['length']);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

		}

		return $data;

	}

	/**
	 * @param array $intime
	 * @param string $type
	 * @return array|bool
	 */
	public function vasCountersByShortcode($intime, $type, $allowed_services, $service, $shortcode, $category)
	{
		$data = array();

		if ($category) {
			$shortcodes_from_category = ShortcodeManager::getShortcodesByCategory($category, $this->database);

			$shr_odes = array();
			/** @var DBShortcode $value */
			foreach ($shortcodes_from_category as $value) {
				$shr_odes[] = $value->shortcodes;
			}

			if ($shr_odes) {
				$imploded_shortcodes = implode("','", $shr_odes);
				$category_string = " AND shortcode::character varying IN ('$imploded_shortcodes')";
			} else {
				echo json_encode(array(
					'total' => 0,
					'data' => array()
				));
				exit();
			}
		} else {
			$category_string = '';
		}

		$services_condition = $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND a.sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND a.shortcode = '$shortcode'" : "";

		if ($type == 'out') {
			$query = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode FROM (SELECT * FROM " . self::$DB_COUNTER_OUT .
				" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
				"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
				"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition $category_string" .
				" GROUP BY year_sms, month_sms, shortcode ORDER BY year_sms DESC, month_sms DESC, sum DESC";
		} else {
			$query = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode FROM (SELECT * FROM " . self::$DB_COUNTER_IN .
				" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
				"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
				"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition $category_string" .
				" GROUP BY year_sms, month_sms, shortcode ORDER BY year_sms DESC, month_sms DESC, sum DESC";
		}

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {
			return false;
		}

		$total_sum = 0;

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {

				/*
				$arr = $this->getServiceClientNames($value['sid']);
				$value['service_name'] = $arr['service_name'];
				*/

				$date = $value['year_sms'] . '-' . $value['month_sms'];

				$total_sum += $value['sum'];

				if (array_key_exists($date, $data)) {
					$data[$date]['sum'] += $value['sum'];
				} else {
					$data[$date] = array();
					$data[$date]['sum'] = $value['sum'];
				}

				$tmp = array();

				$tmp['year_sms'] = $value['year_sms'];
				$tmp['month_sms'] = $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				//$tmp['service_name'] = $value['service_name'];
				$tmp['shortcode_sum'] = $value['sum'];

				array_push($data[$date], $tmp);

			}

			return array('total' => $total_sum, 'data' => $data);
		}

		return array();

	}

	/**
	 * @param array $intime
	 * @param $allowed_services
	 * @param $service
	 * @param $shortcode
	 * @param $graph_type
	 * @return array|bool
	 */
	public function vasCountersBySid($intime, $allowed_services, $service, $shortcode, $graph_type)
	{
		$data = array();
		$data['in'] = array();
		$data['out'] = array();
		$array = array();

		$services_condition = $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND a.sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND a.shortcode = '$shortcode'" : "";

		if ($graph_type == 'month') {
			//Moths view
			$columns = "sum(count_sms) as sum, year_sms, month_sms, sid";
			$group = "GROUP BY year_sms, month_sms, sid ORDER BY year_sms ASC, month_sms ASC";

			//Filling Array with all Months

			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1M');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-01'));
			}

		} else {
			//Days view
			$columns = "sum(count_sms) as sum, year_sms, month_sms, day_sms, sid";
			$group = "GROUP BY year_sms, month_sms, day_sms, sid ORDER BY year_sms ASC, month_sms ASC, day_sms ASC";

			//filling array with all days
			$start = new \DateTime($intime['start']);
			$end = new \DateTime($intime['end']);
			$interval = new \DateInterval('P1D');
			$period = new \DatePeriod($start, $interval, $end);

			/** @var \DateTime $dt */
			foreach ($period as $dt) {
				array_push($array, $dt->format('Y-m-d'));
			}
		}

		$query_in = "SELECT $columns FROM (SELECT * FROM " . self::$DB_COUNTER_OUT .
			" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
			"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
			"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition" .
			" $group";

		$query_out = "SELECT $columns FROM (SELECT * FROM " . self::$DB_COUNTER_IN .
			" WHERE (year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] .
			"'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date " .
			"AND $services_condition) as a WHERE TRUE $service_condition $shortcode_condition" .
			" $group";


		$res_in = $this->db_stat->execute($query_in);

		if ($this->db_stat->error()) {
			return array('in' => array(), 'out' => array());
		}

		if ($this->db_stat->rows() > 0) {

			foreach ($res_in as $value) {

				if ($graph_type == 'month') {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-01'));
				} else {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-' . $value['day_sms']));
				}

				if (Utils::in_array_recur($needle, $array)) {
					if ($graph_type == 'month') {
						$value['label'] = date('M', strtotime($needle));
					} else {
						$value['label'] = date('d M', strtotime($needle));
					}

					array_push($data['in'], $value);
				} else {

					if ($graph_type == 'month') {
						array_push($data['in'], array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"label" => date('M', strtotime($needle))
						));
					} else {
						array_push($data['in'], array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"day_sms" => date('d', strtotime($needle)),
							"label" => date('d M', strtotime($needle))
						));
					}
				}

			}

		}

		$res_out = $this->db_stat->execute($query_out);

		if ($this->db_stat->error()) {
			return array('in' => array(), 'out' => array());
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res_out as $value) {

				if ($graph_type == 'month') {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-01'));
				} else {
					$needle = date('Y-m-d', strtotime($value['year_sms'] . '-' . $value['month_sms'] . '-' . $value['day_sms']));
				}

				if (Utils::in_array_recur($needle, $array)) {
					if ($graph_type == 'month') {
						$value['label'] = date('M', strtotime($needle));
					} else {
						$value['label'] = date('d M', strtotime($needle));
					}

					array_push($data['out'], $value);
				} else {

					if ($graph_type == 'month') {
						array_push($data['out'], array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"label" => date('M', strtotime($needle))
						));
					} else {
						array_push($data['out'], array(
							"sum" => 0,
							"year_sms" => date('Y', strtotime($needle)),
							"month_sms" => date('m', strtotime($needle)),
							"day_sms" => date('d', strtotime($needle)),
							"label" => date('d M', strtotime($needle))
						));
					}
				}

			}
		}

		return $data;

	}

	/**
	 * @param $intime
	 * @param $shortcode
	 * @param $sid
	 * @return array
	 */
	public function smsIncommingCountersByOper($intime, $shortcode, $sid, $allowed_services, $operator_id = '')
	{

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');

		$operator_condition = ($operator_id == '') ? '' : "AND operator_id='" . $this->database->escape($operator_id) . "'";

		$data = array();
		$query = "SELECT sum(a.count_sms) AS sum, a.operator_id FROM (SELECT * FROM " . self::$DB_COUNTER_IN . " WHERE " .
			"year_sms='" . $this->database->escape($intime['year']) . "' AND month_sms = '" . $this->database->escape($intime['month']) . "' $services_condition $operator_condition) as a " .
			"WHERE a.sid = '" . $this->database->escape($sid) . "' AND a.shortcode = '" . $this->database->escape($shortcode) . "' GROUP BY a.operator_id ORDER BY a.operator_id ASC;";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {

			return $data;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$name = OperatorManager::getInstance($this->database)->getOperatorNameById($value['operator_id']);
				$value['operator_name'] = $name;
			}

			$data = $res;
		}

		return $data;

	}

	/**
	 * @param $intime
	 * @param $shortcode
	 * @param $sid
	 * @return array
	 */
	public function smsOutgoingCountersByOper($intime, $shortcode, $sid, $allowed_services, $operator_id = '')
	{
		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$operator_condition = ($operator_id == '') ? '' : "AND operator_id='" . $this->database->escape($operator_id) . "'";

		$data = array();
		$query = "SELECT sum(a.count_sms) AS sum, a.operator_id FROM (SELECT * FROM " . self::$DB_COUNTER_OUT . " WHERE " .
			"year_sms='" . $this->database->escape($intime['year']) . "' AND month_sms = '" . $this->database->escape($intime['month']) . "' $services_condition $operator_condition) as a " .
			"WHERE a.sid = '" . $sid . "' AND a.shortcode = '" . $shortcode . "' GROUP BY a.operator_id ORDER BY a.operator_id ASC;";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {
			return $data;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$name = OperatorManager::getInstance($this->database)->getOperatorNameById($value['operator_id']);
				$value['operator_name'] = $name;
			}

			$data = $res;
		}

		return $data;

	}

	public function smsIncommingCounters($pagination, $intime, $order, $allowed_services, $service, $shortcode, $category)
	{
		$data = array();
		$data['data'] = array();

		if ($category) {
			$shortcodes_from_category = ShortcodeManager::getShortcodesByCategory($category, $this->database);

			$shr_odes = array();
			/** @var DBShortcode $value */
			foreach ($shortcodes_from_category as $value) {
				if (is_numeric($value->shortcodes)) {
					$shr_odes[] = $value->shortcodes;
				}
			}

			if ($shr_odes) {
				$imploded_shortcodes = implode("','", $shr_odes);
				$category_string = " AND shortcode::character varying IN ('$imploded_shortcodes')";
			} else {
				echo json_encode(array(
					'recordsTotal' => 0,
					'recordsFiltered' => 0,
					'data' => 0,
					'draw' => 0
				));
				exit();
			}
		} else {
			$category_string = '';
		}


		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '$shortcode'" : "";

		$extend_lenght = $pagination['length'] + 1;
		$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";

		$date_condition = "(year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] . "'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date";

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY shortcode $order_dir";
				break;
			case '2':
				$order_string = "ORDER BY sid $order_dir";
				break;
			case '3':
				$order_string = "";
				break;
			case '4':
				$order_string = "";
				break;
			case '5':
				$order_string = "ORDER BY sum $order_dir";
				break;
			default :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
		}

		$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
			" FROM " . self::$DB_COUNTER_IN . " WHERE $date_condition $services_condition $service_condition $shortcode_condition $category_string GROUP BY year_sms, month_sms, sid, shortcode $order_string $pagination_string;";

		//For the Manual ordering
		if ($order['column'] == '3' || $order['column'] == '4') {
			$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
				" FROM " . self::$DB_COUNTER_IN . " WHERE $date_condition $services_condition $service_condition $shortcode_condition $category_string GROUP BY year_sms, month_sms, sid, shortcode $order_string;";
		}

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {
			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_sms'] . '-' . $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['sum'] = $value['sum'] .
					'<a class="pull-right" data-type="in" data-html="true" data-toggle="popoverin" data-placement="left" data-content="Content" ' .
					'data-shortcode="' . $value['shortcode'] . '" ' .
					'data-sid="' . $value['sid'] . '" ' .
					'data-year="' . $value['year_sms'] . '" ' .
					'data-month="' . $value['month_sms'] . '" ' .
					'><span class="glyphicon glyphicon-eye-open"></span></a>';
				$tmp['sid'] = $value['sid'];

				$sc = $this->getServiceClientNames($value['sid']);
				$tmp['service_name'] = $sc['service_name'];
				$tmp['client_id'] = $sc['client_id'];
				$tmp['client_name'] = $sc['client_name'];

				array_push($data['data'], $tmp);
			}

			if ($order['column'] == '3') {
				$client = array();
				foreach ($data['data'] as $key => $row) {
					$client[$key] = $row['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $extend_lenght);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

			if ($order['column'] == '4') {
				$service = array();
				foreach ($data['data'] as $key => $row) {
					$service[$key] = $row['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $pagination['length']);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

		}

		return $data;

	}

	public function smsExportCounters($type, $intime, $allowed_services, $service, $shortcode)
	{
		$data = array();
		$data['data'] = array();

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '$shortcode'" : "";

		$date_condition = "(year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] . "'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date";

		if ($type == 'out') {
			$db_table = self::$DB_COUNTER_OUT;
		} else {
			$db_table = self::$DB_COUNTER_IN;
		}

		$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
			" FROM $db_table WHERE $date_condition $services_condition $service_condition $shortcode_condition GROUP BY year_sms, month_sms, sid, shortcode;";

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_sms'] . '-' . $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['sum'] = $value['sum'];
				$tmp['sid'] = $value['sid'];

				$sc = $this->getServiceClientNames($value['sid']);
				$tmp['service_name'] = $sc['service_name'];
				$tmp['client_id'] = $sc['client_id'];
				$tmp['client_name'] = $sc['client_name'];

				array_push($data['data'], $tmp);
			}
		}

		return $data;

	}

	public function smsOutgoingCounters($pagination, $intime, $order, $allowed_services, $service, $shortcode, $category)
	{
		$data = array();
		$data['data'] = array();

		if ($category) {
			$shortcodes_from_category = ShortcodeManager::getShortcodesByCategory($category, $this->database);

			$shr_odes = array();
			/** @var DBShortcode $value */
			foreach ($shortcodes_from_category as $value) {
				if (is_numeric($value->shortcodes)) {
					$shr_odes[] = $value->shortcodes;
				}
			}

			if ($shr_odes) {
				$imploded_shortcodes = implode("','", $shr_odes);
				$category_string = " AND shortcode::character varying IN ('$imploded_shortcodes')";
			} else {
				echo json_encode(array(
					'recordsTotal' => 0,
					'recordsFiltered' => 0,
					'data' => 0,
					'draw' => 0
				));
				exit();
			}
		} else {
			$category_string = '';
		}

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'sid');
		$service_condition = ($service != '') ? "AND sid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND shortcode = '$shortcode'" : "";

		$extend_lenght = $pagination['length'] + 1;
		$pagination_string = "LIMIT " . $this->database->escape($extend_lenght) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		$date_condition = "(year_sms || '-' || month_sms || '-' || day_sms)::date >= '" . $intime['start'] . "'::date AND (year_sms || '-' || month_sms || '-' || day_sms)::date <= '" . $intime['end'] . "'::date";

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY shortcode $order_dir";
				break;
			case '2':
				$order_string = "ORDER BY sid $order_dir";
				break;
			case '3':
				$order_string = "";
				break;
			case '4':
				$order_string = "";
				break;
			case '5':
				$order_string = "ORDER BY sum $order_dir";
				break;
			default :
				$order_string = "ORDER BY year_sms $order_dir, month_sms $order_dir";
				break;
		}

		$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
			" FROM " . self::$DB_COUNTER_OUT . " WHERE $date_condition $services_condition $service_condition $shortcode_condition $category_string GROUP BY year_sms, month_sms, sid, shortcode $order_string $pagination_string;";

		//For the Manual ordering
		if ($order['column'] == '3' || $order['column'] == '4') {
			$query_res = "SELECT sum(count_sms) as sum, year_sms, month_sms, shortcode, sid " .
				" FROM " . self::$DB_COUNTER_OUT . " WHERE $date_condition $services_condition $service_condition $shortcode_condition $category_string GROUP BY year_sms, month_sms, sid, shortcode $order_string;";
		}

		$res = $this->db_stat->execute($query_res);

		if ($this->db_stat->error()) {

			return false;
		}

		$data['recordsTotal'] = 0;
		$data['recordsFiltered'] = 0;

		if ($this->db_stat->rows() > 0) {

			if ($this->db_stat->rows() == $extend_lenght) {
				//we have next page
				$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
				$data['recordsFiltered'] = $data['recordsTotal'];
			} else {
				$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
				$data['recordsFiltered'] = $data['recordsTotal'];
			}

			foreach ($res as $value) {
				$tmp = array();

				$tmp['date'] = $value['year_sms'] . '-' . $value['month_sms'];
				$tmp['shortcode'] = $value['shortcode'];
				$tmp['sum'] = $value['sum'] .
					'<a class="pull-right" data-type="out" data-html="true" data-toggle="popoverout" data-placement="left" data-content="Content" ' .
					'data-shortcode="' . $value['shortcode'] . '" ' .
					'data-sid="' . $value['sid'] . '" ' .
					'data-year="' . $value['year_sms'] . '" ' .
					'data-month="' . $value['month_sms'] . '" ' .
					'><span class="glyphicon glyphicon-eye-open"></span></a>';
				$tmp['sid'] = $value['sid'];

				$sc = $this->getServiceClientNames($value['sid']);
				$tmp['service_name'] = $sc['service_name'];
				$tmp['client_id'] = $sc['client_id'];
				$tmp['client_name'] = $sc['client_name'];

				array_push($data['data'], $tmp);
			}

			if ($order['column'] == '3') {
				$client = array();
				foreach ($data['data'] as $key => $row) {
					$client[$key] = $row['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $extend_lenght);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

			if ($order['column'] == '4') {
				$service = array();
				foreach ($data['data'] as $key => $row) {
					$service[$key] = $row['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $data['data']);

				$data['data'] = array_slice($data['data'], $pagination['start'], $pagination['length']);

				if (count($data['data']) == $extend_lenght) {
					$data['recordsTotal'] = $pagination['start'] + $extend_lenght;
					$data['recordsFiltered'] = $data['recordsTotal'];
				} else {
					$data['recordsTotal'] = $pagination['start'] + $pagination['length'];
					$data['recordsFiltered'] = $data['recordsTotal'];
				}
			}

		}

		return $data;
	}

	/**
	 * @param array $pagination
	 * @param array $intime
	 * @return array
	 */
	public function smsIncomming($pagination, $intime, $order, $allowed_services, $service, $shortcode, $msisdn)
	{
		$data = array();

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'serviceid');
		$service_condition = ($service != '') ? "AND i.serviceid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND i.callednum = '$shortcode'" : "";

		$msisdn_condition = ($msisdn != '') ? "AND i.msisdn = '$msisdn'" : "";

		if (is_array($pagination)) {
			$pagination_string = "LIMIT " . $this->database->escape($pagination['length']) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		} else {
			$pagination_string = '';
		}
		$date_condition = "intime <= '" . $intime['end'] . "' AND otime >= '" . $intime['start'] . "'";

		$query_full = "SELECT count(i.id) FROM (SELECT * FROM " . self::$DB_SMS_IN . " WHERE $date_condition $services_condition) as i WHERE TRUE $service_condition $shortcode_condition $msisdn_condition";
		$res_full = $this->db_stat->execute($query_full);

		if ($this->db_stat->error()) {

			return false;
		}

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY i.otime $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY i.senttime $order_dir";
				break;
			case '3':
				$order_string = "ORDER BY i.msisdn $order_dir";
				break;
			case '4':
				$order_string = "ORDER BY i.callednum $order_dir";
				break;
			case '5':
				$order_string = "ORDER BY i.serviceid $order_dir";
				break;
			case '6':
				$order_string = "";
				break;
			case '7':
				$order_string = "";
				break;
			default :
				$order_string = "ORDER BY i.otime $order_dir";
				break;
		}

		$query_res = "SELECT * FROM (SELECT * FROM " . self::$DB_SMS_IN . " WHERE $date_condition $services_condition) as i WHERE TRUE $service_condition $shortcode_condition $msisdn_condition";
		$query_pagination = $query_res . " " . $order_string . " " . $pagination_string;

		//For Manual Ordering
		if ($order['column'] == 6 || $order['column'] == 7) {
			$query_pagination = $query_res;
		}

		$res = $this->db_stat->execute($query_pagination);

		if ($this->db_stat->error()) {
			return false;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$tmp = $this->getServiceClientNames($value['serviceid']);
				$value['service_name'] = $tmp['service_name'];
				$value['client_id'] = $tmp['client_id'];
				$value['client_name'] = $tmp['client_name'];
			}

			if ($order['column'] == 6) {
				$service = array();
				foreach ($res as $key => $value) {
					$service[$key] = $value['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}

			if ($order['column'] == 7) {
				$client = array();
				foreach ($res as $key => $value) {
					$client[$key] = $value['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}
		}

		$data['recordsTotal'] = count($res);
		$data['recordsFiltered'] = $res_full[0]['count'];
		$data['data'] = $res;

		return $data;
	}

	/**
	 * @param array $pagination
	 * @param array $intime
	 * @return array|bool
	 */
	public function smsOutgoing($pagination, $intime, $order, $allowed_services, $service, $shortcode, $msisdn)
	{
		$data = array();

		$services_condition = "AND " . $this->buildAllowedServices($allowed_services, 'serviceid');
		$service_condition = ($service != '') ? "AND o.serviceid = '$service'" : "";
		$shortcode_condition = ($shortcode != '') ? "AND o.callednum = '$shortcode'" : "";

		$msisdn_condition = ($msisdn != '') ? "AND o.msisdn = '$msisdn'" : "";

		if (is_array($pagination)) {
			$pagination_string = "LIMIT " . $this->database->escape($pagination['length']) . " OFFSET " . $this->database->escape($pagination['start']) . "";
		} else {
			$pagination_string = '';
		}

		$query_full = "SELECT count(o.id) FROM (SELECT * FROM " . self::$DB_SMS_OUT . " WHERE intime >= '" . $intime['start'] . "' AND intime <= '" . $intime['end'] . "' $services_condition) as o WHERE TRUE $service_condition $shortcode_condition $msisdn_condition";

		$res_full = $this->db_stat->execute($query_full);

		if ($this->db_stat->error()) {

			return false;
		}

		//ORDER
		$order_dir = $order['dir'];
		switch ($order['column']) {
			case '0' :
				$order_string = "ORDER BY o.intime $order_dir";
				break;
			case '1':
				$order_string = "ORDER BY o.senttime $order_dir";
				break;
			case '3':
				$order_string = "ORDER BY o.msisdn $order_dir";
				break;
			case '4':
				$order_string = "ORDER BY o.callednum $order_dir";
				break;
			case '5':
				$order_string = "ORDER BY o.serviceid $order_dir";
				break;
			case '6':
				$order_string = "";
				break;
			case '7':
				$order_string = "";
				break;
			default :
				$order_string = "ORDER BY o.intime $order_dir";
				break;
		}

		$query_res = "SELECT * FROM (SELECT * FROM " . self::$DB_SMS_OUT . " WHERE intime >= '" . $intime['start'] . "' AND intime <= '" . $intime['end'] . "' $services_condition) as o WHERE TRUE $service_condition $shortcode_condition $msisdn_condition $order_string";
		$query_pagination = $query_res . " " . $pagination_string;

		if ($order['column'] == 6 || $order['column'] == 7) {
			$query_pagination = $query_res;
		}

		$res = $this->db_stat->execute($query_pagination);

		if ($this->db_stat->error()) {

			return false;
		}

		if ($this->db_stat->rows() > 0) {
			foreach ($res as &$value) {
				$tmp = $this->getServiceClientNames($value['serviceid']);
				$value['service_name'] = $tmp['service_name'];
				$value['client_id'] = $tmp['client_id'];
				$value['client_name'] = $tmp['client_name'];
			}

			if ($order['column'] == 6) {
				$service = array();
				foreach ($res as $key => $value) {
					$service[$key] = $value['service_name'];
				}

				array_multisort($service, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}

			if ($order['column'] == 7) {
				$client = array();
				foreach ($res as $key => $value) {
					$client[$key] = $value['client_name'];
				}

				array_multisort($client, ($order_dir == 'ASC') ? SORT_ASC : SORT_DESC, $res);

				$res = array_slice($res, $pagination['start'], $pagination['length']);
			}
		}

		$data['data'] = $res;
		$data['recordsTotal'] = count($res);
		$data['recordsFiltered'] = $res_full[0]['count'];

		return $data;
	}

	/**
	 * @param int $sid
	 * @return array
	 */
	protected function getServiceClientNames($sid)
	{
		$value = array();

		$obj_service = DBService::loadFromId($sid, $this->database);

		$value['service_name'] = '<span class="text-muted">unknown</span>';
		$value['client_id'] = '0';
		$value['client_name'] = '<span class="text-muted">unknown</span>';

		if ($obj_service) {
			$value['service_name'] = $obj_service->service_name;
			$value['client_id'] = $obj_service->client_id;

			$obj_client = DBClient::loadFromId($obj_service->client_id, $this->database);
			if ($obj_client) {
				$value['client_name'] = $obj_client->client_names;
			} else {
				$value['client_name'] = '<span class="text-muted">unknown</span>';
			}
		}

		return $value;
	}

	/**
	 * @param array $array
	 * @param string $column
	 * @return string
	 */
	protected function buildAllowedServices($array, $column)
	{

		if ($array == 'all') {
			return "TRUE";
		}

		$string = "";

		$string .= "(";

		$idx = 1;
		foreach ($array as $value) {

			$string .= "$column = '$value'";

			if (count($array) != $idx) {
				$string .= " OR ";
			}

			$idx++;
		}

		$string .= ")";

		return $string;
	}

	public function deliveryStatus($ext_id)
	{
		$ext_id = $this->db_stat->escape($ext_id);

		$query = "SELECT DISTINCT ON (last_value(sid) OVER wnd1, last_value(out_ext_sid) OVER wnd1, min(status) OVER wnd1)
		    last_value(sid) OVER wnd1 AS sid,
		    last_value(out_ext_sid) OVER wnd1 AS out_ext_sid,
		    last_value(bsms_in_otime) OVER wnd1 AS bsms_in_otime,
		    last_value(status_date) OVER wnd1 AS status_date,
		    last_value(status) OVER wnd1 AS laststatus,
		    min(status_date) OVER wnd1 AS ptime,
		    last_value(intime) OVER wnd1 AS intime,
		    last_value(answer) OVER wnd1 AS answer,
		    last_value(smsc_id) OVER wnd1 AS smsc_id
		   FROM delivery_status
		  WHERE true AND out_ext_sid = '$ext_id'
		WINDOW wnd1 AS (PARTITION BY sid, out_ext_sid
		--ORDER BY intime
		ORDER BY
           	CASE WHEN status = '8' then 1
           	WHEN status = '16' then 2
           	WHEN status = '4' then 3
           	WHEN status = '1' then 4
            	WHEN status = '2' then 5
            	ELSE 10
            	END,
            	intime
		 ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING);";

		$res = $this->db_stat->execute($query);

		if ($this->db_stat->error()) {
			return false;
		}

		return $res;
	}
}