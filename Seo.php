<?php namespace Model\Seo;

use Model\Core\Globals;
use Model\Core\Module;

class Seo extends Module
{
	private array $elementMetaCache;
	private array $generalMetaCache;

	private array $options = [
		'prefix' => APP_NAME,
		'title' => APP_NAME,
		'description' => null,
		'keywords' => null,
		'og:type' => 'website',
		'full_title_pattern' => '[prefix] | [title]',
		'og_title_pattern' => null,
		'title_pattern' => '[title]',
		'twitter-cards' => null, // ['site' => '@accountname', 'type' => 'summary|summary_large_image']
		'exclude-get-from-canonical' => [],
	];
	private array $meta = [];
	private array $tags = [];

	/**
	 * @param array $options
	 */
	public function init(array $options)
	{
		$this->options = array_merge($this->options, $options);

		if (!isset(Globals::$data['adminAdditionalPages']))
			Globals::$data['adminAdditionalPages'] = [];
		Globals::$data['adminAdditionalPages'][] = [
			'name' => 'SEO',
			'page' => 'ModElSeo',
			'rule' => 'model-seo',
		];
	}

	/**
	 * @param string $k
	 * @param null|string $v
	 */
	public function setMeta(string $k, ?string $v): void
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

		$v = $this->meta[$lookFor] ?? $this->getMetaFromElement($lookFor) ?? $this->getGeneralMeta($lookFor) ?? $this->options[$lookFor] ?? null;

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
						$pattern = $this->getMeta('og_title_pattern') ?: $this->getMeta('full_title_pattern');
						break;
				}
			}

			$v = str_replace('[prefix]', $prefix, str_replace('[title]', $v, $pattern));
		}

		if ($k === 'img' and $v) {
			if ($v[0] === '/') // If host is missing from img url, it has to be added
				$v = BASE_HOST . $v;
		}

		if ($k === 'canonical' and !$v) {
			$get = '';
			if (count($_GET) > 1) {
				$get = $_GET;

				$exclude = $this->options['exclude-get-from-canonical'];
				foreach ($exclude as $excluded) {
					if (isset($get[$excluded]))
						unset($get[$excluded]);
				}
				$get = '?' . http_build_query($get);
			}
			$v = BASE_HOST . $this->model->getUrl() . $get;
		}

		if ($k === 'keywords' and is_array($v))
			$v = implode(',', $v);

		return $v;
	}

	/**
	 * @param string $k
	 * @return string|array|null
	 */
	private function getMetaFromElement(string $k)
	{
		if (!isset($this->elementMetaCache)) {
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
	 * @param string $tag
	 */
	public function addTag(string $tag): void
	{
		if (!in_array($tag, $this->tags))
			$this->tags[] = $tag;
	}

	/**
	 * @param string $k
	 * @return string|null
	 */
	private function getGeneralMeta(string $k): ?string
	{
		if (!isset($this->generalMetaCache)) {
			$this->generalMetaCache = [];

			if ($this->model->moduleExists('Db')) {
				$rules = $this->model->_Db->select_all('model_seo', ['controller' => $this->model->controllerName]);
				foreach ($rules as $rule) {
					if (trim($rule['tags'] ?: '')) {
						$rule_tags = explode(',', trim($rule['tags']));
						if (count(array_intersect($rule_tags, $this->tags)) === 0)
							continue;
					}

					foreach ($rule as $ck => $v) {
						if (in_array($ck, ['id', 'controller', 'tags']) or $v === null)
							continue;
						$this->generalMetaCache[$ck] = $v;
					}
				}
			}
		}

		return $this->generalMetaCache[$k] ?? null;
	}

	/**
	 *
	 */
	public function headings(): void
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
			<meta property="og:type" content="<?= str_replace('"', '', $og_type) ?>"/>
			<?php
		}
		if ($img) {
			?>
			<meta property="og:image" content="<?= str_replace('"', '', $img) ?>"/>
			<?php
		}
		if ($canonical) {
			?>
			<meta property="og:url" content="<?= str_replace('"', '', $canonical) ?>"/>
			<link rel="canonical" href="<?= str_replace('"', '', $canonical) ?>"/>
			<?php
		}

		if ($this->options['twitter-cards']) {
			$twitter = array_merge([
				'type' => 'summary',
				'site' => null,
			], $this->options['twitter-cards']);

			if ($twitter['site'][0] != '@')
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
