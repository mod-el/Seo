<?php namespace Model\Seo\Migrations;

use Model\Db\Migration;

class Migration_20200623143300_SeoTags extends Migration
{
	public function exec()
	{
		$this->addColumn('model_seo', 'tags');
	}
}
