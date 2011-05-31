<?php
namespace Blog\Views;

use Milk\Views\BaseView,
	Milk\IO\Http;

use Blog\Models\Article as Model;

class Article extends BaseView {

	public static function read(Http\Request $request) {
		$article = new Model;
		
		$response = new Http\Response;
		$response->setContent("<pre>".var_export($article, true)."</pre>");
		
		return $response;
	}
	
}