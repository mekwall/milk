<?php
namespace Blog\Models;

use Milk\Core\Model,
	Milk\Core\Model\Field;
	
class ArticleManager extends Model\Manager {

}	

class Article extends Model {

	protected function setupFields() {
		$this->addFields(
			array(
				"author" =>
					new Field\OneToOne(),
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