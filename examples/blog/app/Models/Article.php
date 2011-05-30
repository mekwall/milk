<?php
namespace Blog\Models;

use Milk\DB\Model,
	Milk\DB\Model\Field,
	Milk\Utils\Strings\Inflector;
	
class ArticleManager extends Model\Manager {

}

class Article extends Model {
	protected function setup() {
		// Add fields
		$this->addFields(
			array(
				"author" =>
					new Field\OneToOne(array(
						"index" => true
					)),
				"title" =>
					new Field\Text(),
				"slug" => 
					new Field\Slug(),
				"ingress" => 
					new Field\Text(),
				"content" => 
					new Field\Text(),
				"created" =>
					new Field\DateTime()
			)
		);
	}
}

Inflector::plural("/(article)/i", "articles");
Inflector::singular("/(articles)/i", "article");