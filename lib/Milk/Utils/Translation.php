<?php
namespace Milk\Utils;

class Translation {

	public function translate($string) {
		return gettext($string);
	}

}

namespace Milk\Utils\Translation;

use Milk\Utils\Translation;

function _($string) {
	return Translation::translate($string);
}