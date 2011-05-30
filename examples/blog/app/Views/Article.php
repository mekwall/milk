<?php
namespace Blog\Views;

use Milk\Views\BaseView;

use Blog\Models\Article as Model;

class Article extends BaseView {

	public static function read() {
		$article = new Model;
		return "<pre>".var_export($article, true)."</pre>";
	}
	
}