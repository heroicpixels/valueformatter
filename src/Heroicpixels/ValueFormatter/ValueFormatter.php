<?php 

/**
 *	Copyright 2015 - Dave Hodgins
 */

namespace Heroicpixels\ValueFormatter;

class ValueFormatter {
	
	protected $autoFormat;
	protected $currency = '$';
	protected $formatters = array();
	protected $numberTypes = array('currency', 'decimal', 'integer', 'percentage');
	
	public function __construct($autoFormat = true) {
		$this->autoFormat = $autoFormat;
	}
	
	/**
	 *	Specify if attempt should be made to autoformat if 
	 *	requested format isn't available.
	 */
	public function autoFormat($bool) {
		$this->autoFormat = (bool) $bool;	
	}
	
	/**
	 *	Currency string
	 */
	public function currency($v) {
		$this->currency = $v;
	}
	
	/**
	 *	Add formatter
	 */
	public function add($name, $mixed = false, $overwrite = false) {	
		if ( !$overwrite && isset($this->formatters[$name]) ) {
			return true;
		}
		if ( !$mixed && $parse = $this->parse($name) ) {
			$mixed = $parse;
		}
		if ( $mixed ) {
			$this->formatters[$name] = $mixed;
			return true;
		}
		return false;
	}
	
	/**
	 *	Get formatted value
	 */
	public function get($value, $format = false) {

		if ( !$format ) {
			return $value;	
		}
		if ( $this->autoFormat && !isset($this->formatters[$format]) ) {
			$this->add($format);
		}
		if ( isset($this->formatters[$format]) ) {
			$formatter = $this->formatters[$format];
			if ( is_callable($formatter) ) {
				// Bind callback so that internal class methods can be referenced
				$value = $formatter->bindTo($this, $this)->__invoke($value);
			}
		}
		return $value;
	}
	
	public function has($format) {
		return isset($this->formatters[$format]);	
	}
	
	/**
	 *	Number format
	 */
	public function number($value, $decimals = 0, $decPoint = '.', $separator = ',') {
		if ( !is_numeric($value) ) {
			return $value;	
		}
		return number_format($value, $decimals, $decPoint, $separator);
	}
	public function keyValue($value) {
		
	}
	/**
	 *	Extremely simplistic attempt to parse a string and 
	 *	create a number formatter from it. Examples:
	 *	-	decimal3 = number_format($val, 3)
	 *	-	currency2 = $this->currency.number_format($val, 2)
	 *	-	$integer = '$'.number_format($val)
	 */
	public function parse($string) {
		$lower = strtolower($string);
		$alpha = preg_replace('/[^a-z]/', '', $lower);
		if ( in_array($alpha, $this->numberTypes) ) {
			$numeric = (int) preg_replace('/[^0-9]/', '', $lower);
			$prepend = $alpha == 'currency' ? $this->currency : preg_replace('/[a-z0-9]/', '', substr($lower, 0, 1));
			$append = $alpha == 'percentage' ? '%' : preg_replace('/[a-z0-9]/', '', substr($lower, 1));
			return $mixed = function($v) use ($prepend, $numeric, $append) {
				return $prepend.$this->number($v, $numeric).$append;
			};
		} else if ( $alpha == 'string' ) {
			$substr = preg_replace('/[^0-9\-]/', '', $lower);
			if ( $substr != '' ) {
				return function($v) use ($substr) {
					$start = $substr >= 0 ? 0 : false;
					return substr($v, $start, $substr);
				};
			}
		} else if ( stristr($string, '&') !== false ) {
			parse_str($string, $definitions);
			return $mixed = function($v) use ($definitions) {
				if ( is_array($definitions) && isset($definitions[$v]) ) {
					return $definitions[$v];
				}
				return $v;
			};
		}
		return false;
	}
	
	/**
	 *	Remove formatter
	 */
	public function remove($name) {
		if ( isset($this->formatters[$format]) ) {
			unset($this->formatters[$format]);	
		}
	}		
}
