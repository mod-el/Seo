<?php namespace Model\Seo\Providers;

use Model\Admin\AbstractAdminProvider;

class AdminProvider extends AbstractAdminProvider
{
	public static function getAdditionalPages(): array
	{
		return [
			[
				'name' => 'SEO',
				'page' => 'ModElSeo',
				'rule' => 'model-seo',
			],
		];
	}
}
