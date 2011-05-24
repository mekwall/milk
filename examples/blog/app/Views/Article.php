<?php
namespace Blog\Views;

use Milk\Views\BaseView;

use Blog\Models\Article as Model;

class Article extends BaseView {

	public function read() {
		$article = new Model;
		return var_export($article, true);
	}
	
}