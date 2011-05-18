<?php
namespace Milk\Utils;

abstract class Numbers {

	static $localeconv;
	
	static public function removeSeparator($numeric) {
		return str_replace(self::$localeconv['thousands_sep'], '', $numeric);
	}
	
	static public function parseCurrency($numeric) {
		$numeric = self::removeSeparator($numeric);
		$numeric = str_replace(self::$localeconv['mon_thousands_sep'], '', $numeric);
		$numeric = str_replace(self::$localeconv['int_curr_symbol'], '', $numeric);
		$numeric = str_replace(self::$localeconv['currency_symbol'], '', $numeric);
		$numeric = self::restoreDecimal($numeric);
		return $numeric;
	}
	
	static public function restoreDecimal($numeric) {
		if (self::$localeconv['decimal_point'] != '.')
			return str_replace(self::$localeconv['decimal_point'], '.', $numeric);
		else
			return $numeric;
	}

	static public function toFloat($numeric) {
		return floatval(self::restoreDecimal($numeric));
	}
	
	static public function toDouble($numeric) {
		return doubleval(self::restoreDecimal($numeric));
	}
	
	static public function toReal($numeric) {
		return (real)self::restoreDecimal($numeric);
	}
	
}
Numbers::$localeconv = localeconv();