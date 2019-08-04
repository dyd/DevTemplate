<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 1/25/2018
 * Time: 1:18 PM
 */

namespace App\Managers;


class Exchange
{
	/**
	 * @var Exchange $instance
	 */
	private static $instance;

	/** @var  array $rates */
	private $rates;

	private $currency;

	/** @var string $content */
	private $content;

	/** @var  resource $parser */
	private $parser;

	/**
	 * Returns Exchange instance
	 *
	 * @return Exchange - User instance
	 */
	public static function getInstance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * setup the Exchange Class
	 */
	public static function initialize()
	{

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * Exchange instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	private function reset()
	{
		$this->content = '';
		$this->rates = array();
		$this->currency = array();
		$this->parser = null;
	}

	/**
	 * Exchange constructor
	 */
	protected function __construct()
	{
		$this->reset();

		$this->content = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";

		$this->parser = simplexml_load_file($this->content);

		$this->fillRates();
	}

	protected function fillRates()
	{
		/** @var \SimpleXMLElement $rate */
		foreach ($this->parser->Cube->Cube->Cube as $rate) {
			array_push($this->currency, (string)$rate['currency']);
			$this->rates[(string)$rate['currency']] = (float)$rate['rate'];
		}

		$this->rates = array('EUR' => 1) + $this->rates;
	}

	public function getRates()
	{
		return $this->rates;
	}

	public function getCurrency()
	{
		return $this->currency;
	}

	public function isCurrencyExists($currency)
	{
		foreach ($this->currency as $value) {
			if ($value == $currency) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $amount
	 * @param $currency
	 * @return bool|float
	 */
	public function turnToEUR($amount, $currency, $precision = 2)
	{
		if (!in_array($currency, $this->currency)) return false;

		return round($amount / $this->rates[$currency], $precision, PHP_ROUND_HALF_UP);
	}

	/**
	 * @param $amount
	 * @param $currency
	 * @return bool|float
	 */
	public function exchEUR($amount, $currency, $precision = 2)
	{
		if (!in_array($currency, $this->currency)) return false;

		return round($this->rates[$currency] * $amount, $precision, PHP_ROUND_HALF_UP);
	}
}