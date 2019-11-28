<?php namespace Model\Seo\AdminPages;

use Model\Admin\AdminPage;

class ModElSeo extends AdminPage
{
	public function options(): array
	{
		return [
			'table' => 'model_seo',
			'privileges' => [
				'C' => DEBUG_MODE,
				'D' => DEBUG_MODE,
			],
		];
	}
}
