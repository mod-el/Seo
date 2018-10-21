<?php namespace Model\Seo;

use Model\Core\Autoloader;
use Model\Core\Module;

class Seo extends Module
{
	/** @var array */
	private $elementMetaCache = null;

	/** @var array */
	private $options = [
		'prefix' => APP_NAME,
		'title' => APP_NAME,
		'description' => null,
		'keywords' => null,
		'og:type' => 'website',
		'full_title_pattern' => '[prefix] | [title]',
		'og_title_pattern' => '[prefix] | [title]',
		'title_pattern' => '[title]',
	];
	/** @var array */
	private $meta = [];

	/**
	 * @param array $options
	 */
	public function init(array $options)
	{
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * @param string $k
	 * @param null|string $v
	 */
	public function setMeta(string $k, ?string $v)
	{
		$this->meta[$k] = $v;
	}

	/**
	 * @param string $k
	 * @return null|string
	 */
	public function getMeta(string $k): ?string
	{
		$lookFor = $k;
		if ($lookFor === 'og:title')
			$lookFor = 'title';

		$v = $this->meta[$lookFor] ?? $this->getMetaFromElement($lookFor) ?? $this->options[$lookFor] ?? null;

		if (in_array($k, ['title', 'og:title'])) {
			$prefix = $this->getMeta('prefix');

			if ($prefix === $v) {
				$pattern = $this->getMeta('title_pattern');
			} else {
				switch ($k) {
					case 'title':
						$pattern = $this->getMeta('full_title_pattern');
						break;
					case 'og:title':
						$pattern = $this->getMeta('og_title_pattern');
						break;
				}
			}

			$v = str_replace('[prefix]', $prefix, str_replace('[title]', $v, $pattern));
		}

		return $v;
	}

	/**
	 * @param string $k
	 * @return string
	 */
	private function getMetaFromElement(string $k): ?string
	{
		if ($this->elementMetaCache === null) {
			$meta = [];
			if ($this->model->isLoaded('ORM')) {
				$element = $this->model->element;
				if ($element) {
					$meta = [
						'img' => $element->getMainImg(),
					];

					$titleWords = ['titolo', 'title', 'nome', 'name'];
					foreach ($titleWords as $w) {
						if ($element[$w]) {
							$meta['title'] = $element[$w];
							break;
						}
					}

					$descriptionWords = ['descrizione', 'description', 'testo', 'text', 'content', 'contents'];
					foreach ($descriptionWords as $w) {
						if ($element[$w]) {
							$meta['description'] = textCutOff(html_entity_decode(str_replace("\n", ' ', strip_tags($element[$w])), ENT_QUOTES, 'UTF-8'), 200);
							break;
						}
					}
				}
			}
			$this->elementMetaCache = $meta;
		}
		return $this->elementMetaCache[$k] ?? null;
	}

	/**
	 *
	 */
	public function headings()
	{
		$title = $this->getMeta('title');
		$og_title = $this->getMeta('og:title');
		$description = $this->getMeta('description');
		$keywords = $this->getMeta('keywords');
		$og_type = $this->getMeta('og:type');
		?>
		<title><?= entities($title) ?></title>
		<meta charset="utf-8"/>
		<meta name="robots" content="index,follow"/>
		<meta property="og:title" content="<?= entities($og_title) ?>"/>
		<?php
		if ($description) {
			?>
			<meta name="description" content="<?= entities(str_replace('"', '', $description)) ?>"/>
			<meta property="og:description" content="<?= entities(str_replace('"', '', $description)) ?>"/>
			<?php
		}
		if ($keywords) {
			?>
			<meta name="keywords" content="<?= str_replace('"', '', $keywords) ?>"/>
			<?php
		}
		if ($og_type) {
			?>
			<meta name="og:type" content="<?= str_replace('"', '', $og_type) ?>"/>
			<?php
		}
	}
}
