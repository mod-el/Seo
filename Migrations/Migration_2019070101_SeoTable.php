<?php namespace Model\Seo\Migrations;

use Model\Db\Migration;

class Migration_2019070101_SeoTable extends Migration
{
	public function exec()
	{
		$this->createTable('model_seo');
		$this->addColumn('model_seo', 'controller');
		$this->addColumn('model_seo', 'title');
		$this->addColumn('model_seo', 'description', ['type' => 'text']);
		$this->addColumn('model_seo', 'keywords', ['type' => 'text']);
		$this->addColumn('model_seo', 'canonical');
	}

	public function check(): bool
	{
		return $this->tableExists('model_seo');
	}
}
