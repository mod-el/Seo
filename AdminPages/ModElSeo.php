<?php namespace Model\Seo\AdminPages;

use Model\Admin\AdminPage;
use Model\Core\Autoloader;

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

	public function customize()
	{
		$sel_options = [];
		$controllers = Autoloader::getFilesByType('Controller');
		foreach ($controllers as $module => $moduleControllers) {
			foreach ($moduleControllers as $controller => $controllerPath) {
				$controllerName = substr($controller, 0, -10);
				$sel_options[$controllerName] = $controllerName;
			}
		}

		ksort($sel_options);
		$sel_options = ['' => ''] + $sel_options;

		$this->model->_Admin->field('controller', [
			'type' => 'select',
			'options' => $sel_options,
		]);
	}
}
