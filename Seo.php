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
		'twitter-cards' => null, // ['site' => '@accountname', 'type' => 'summary|summary_large_image']
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

		if ($k === 'img' and $v) {
			if ($v{0} === '/') // If host is missing from img url, it has to be added
				$v = ((isset($_SERVER['HTTPS']) and $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $v;
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

					$customMeta = $element->getMeta();
					$meta = array_merge($meta, $customMeta);

					if (!isset($meta['title']) or !$meta['title']) {
						$titleWords = ['titolo', 'title', 'nome', 'name'];
						foreach ($titleWords as $w) {
							if ($element[$w]) {
								$meta['title'] = $element[$w];
								break;
							}
						}
					}

					if (!isset($meta['description']) or !$meta['description']) {
						$descriptionWords = ['descrizione', 'description', 'testo', 'text', 'content', 'contents'];
						foreach ($descriptionWords as $w) {
							if ($element[$w]) {
								$meta['description'] = textCutOff(html_entity_decode(str_replace("\n", ' ', strip_tags($element[$w])), ENT_QUOTES, 'UTF-8'), 200);
								break;
							}
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
		$img = $this->getMeta('img');
		$canonical = $this->getMeta('canonical');
		?>
		<title><?= entities($title) ?></title>
		<meta charset="utf-8"/>
		<meta name="robots" content="index,follow"/>
		<meta property="og:title" content="<?= str_replace('"', '', $og_title) ?>"/>
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
		if ($img) {
			?>
			<meta name="og:image" content="<?= str_replace('"', '', $img) ?>"/>
			<?php
		}
		if ($canonical) {
			?>
			<link rel="canonical" href="<?= str_replace('"', '', $canonical) ?>"/>
			<?php
		}

		if ($this->options['twitter-cards']) {
			$twitter = array_merge([
				'type' => 'summary',
				'site' => null,
			], $this->options['twitter-cards']);

			if ($twitter['site']{0} != '@')
				$twitter['site'] = '@' . $twitter['site'];
			?>
			<meta name="twitter:card" content="summary_large_image"/>
			<?php
			if ($twitter['site']) {
				?>
				<meta name="twitter:site" content="<?= entities($twitter['site']) ?>"/>
				<?php
			}
			?>
			<meta name="twitter:title" content="<?= str_replace('"', '', $og_title) ?>"/>
			<meta name="twitter:description" content="<?= str_replace('"', '', $description) ?>"/>
			<?php
			if ($img) {
				?>
				<meta name="twitter:image" content="<?= $img ?>" /><?php
			}
		}
	}
}
