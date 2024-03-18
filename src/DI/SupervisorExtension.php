<?php declare(strict_types = 1);

namespace Pd\Supervisor\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Pd\Supervisor\Console\RenderCommand;
use Pd\Supervisor\Console\WriteCommand;
use Supervisor\Configuration\Configuration;
use Supervisor\Configuration\Section\GenericSection;
use Supervisor\Configuration\Section\Named;


final class SupervisorExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$config = (array) $this->getConfig();

		if ( ! isset($config['prefix'])) {
			throw new \Pd\Supervisor\DI\MissingConfigurationValueException(
				'Parametr \'prefix\' pro extension \'supervisor\' je vyzadovany. Doplnte jej a jako hodnotu vyberte idealne nazev projektu.'
			);
		}

		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IConfigurationProvider) {
				$config['configuration'] = array_merge_recursive($extension->getSupervisorConfiguration(), $config['configuration']);
			}
		}

		$this->loadSupervisorConfiguration(
			(array) $config['configuration'],
			(array) $config['defaults'],
			(array) $config['overrides'],
			(string) $config['prefix'],
			isset($config['group']) ? (string) $config['group'] : NULL
		);

		$builder->addDefinition($this->prefix('renderer'))
			->setFactory(\Indigo\Ini\Renderer::class, [
				\Indigo\Ini\Renderer::ARRAY_MODE_CONCAT | \Indigo\Ini\Renderer::BOOLEAN_MODE_BOOL_STRING,
			])
		;

		$builder->addDefinition($this->prefix('renderCommand'))
			->setFactory(RenderCommand::class, [strtr($this->prefix('render'), '.', ':')])
		;
		$builder->addDefinition($this->prefix('writeCommand'))
			->setFactory(WriteCommand::class, [strtr($this->prefix('write'), '.', ':')])
		;
	}


	public function getConfigSchema(): \Nette\Schema\Schema
	{
		return \Nette\Schema\Expect::structure([
			'prefix' => \Nette\Schema\Expect::string()->nullable(),
			'configuration' => \Nette\Schema\Expect::array()->default([]),
			'overrides' => $this->getOverrideSchema()->default([]),
			'defaults' => \Nette\Schema\Expect::array()->default([]),
			'group' => \Nette\Schema\Expect::string()->nullable(),
		]);
	}

	public function getOverrideSchema(): \Nette\Schema\Elements\Type
	{
		$isRegExp = static function (string $value): bool {
			try {
				return @preg_match($value, '') !== FALSE; // @ - is intentional
			} catch (\Throwable $e) {
				return FALSE;
			}
		};

		return \Nette\Schema\Expect::arrayOf(
			\Nette\Schema\Expect::structure([
				'match' => \Nette\Schema\Expect::structure([
						'name' => \Nette\Schema\Expect::string()->required(false)->assert($isRegExp, 'Must be valid regular expression'),
						'property' => \Nette\Schema\Expect::string()->required(false)->assert($isRegExp, 'Must be valid regular expression'),
						'value' => \Nette\Schema\Expect::string()->required(false)->assert($isRegExp, 'Must be valid regular expression'),
					])->castTo('array'),
				'value' => \Nette\Schema\Expect::scalar(),
			])->castTo('array'),
		);
	}


	/**
	 * @param array<string, mixed> $config
	 * @param array<string, mixed> $defaults
	 */
	private function loadSupervisorConfiguration(array $config, array $defaults, array $overrides, string $prefix, ?string $group): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $builder->addDefinition($this->prefix('configuration'))->setType(Configuration::class);

		foreach ($config as $sectionName => $sectionConfig) {
			if ( ! $sectionClass = (new Configuration)->findSection($sectionName)) {
				$sectionClass = GenericSection::class;
			}
			if (is_subclass_of($sectionClass, Named::class)) {
				foreach ((array) $sectionConfig as $name => $properties) {
					$name = $this->prepareName($name, $prefix);
					$properties = isset($defaults[$sectionName]) ? $this->mergeProperties($properties, $defaults[$sectionName]) : $properties;
					$properties = $this->processOverrides($overrides, $name, $properties);

					$configuration->addSetup('addSection', [
						new \Nette\DI\Definitions\Statement($sectionClass, [
							$name,
							$properties,
						]),
					]);
				}
			} else {
				$configuration->addSetup('addSection', [
					new \Nette\DI\Definitions\Statement(
						$sectionClass, [
						isset($defaults[$sectionName]) ? $this->mergeProperties($sectionConfig, $defaults[$sectionName]) : $sectionConfig,
					]),
				]);
			}
		}

		$this->prepareGroup($config, $configuration, $prefix, $group);
	}

	private function processOverrides(array $overrides, string $name, array $properties): array
	{
		foreach ($overrides as $item) {
			if (!\is_null($item['match']['name']) && !preg_match($item['match']['name'], $name)) {
				continue;
			}

			foreach ($properties as $property => $value) {
				if (!\is_null($item['match']['property']) && !preg_match($item['match']['property'], $property)) {
					continue;
				}

				if (!\is_null($item['match']['value']) && !preg_match($item['match']['value'], (string) $value)) {
					continue;
				}

				$properties[$property] = $item['value'];
			}
		}

		return $properties;
	}


	/**
	 * @param array<string, mixed> $properties
	 * @param array<string, mixed> $defaults
	 * @return array<string, mixed>
	 */
	private function mergeProperties(array $properties, array $defaults = []): array
	{
		foreach ($defaults as $key => $value) {
			if (isset($properties[$key]) && strpos($value, $placeholder = sprintf('%%%s%%', $key)) !== FALSE) {
				$properties[$key] = str_replace($placeholder, $properties[$key], $value);
			} else {
				$properties[$key] = $value;
			}
		}

		return $properties;
	}


	private function prepareName(string $name, string $prefix): string
	{
		$builder = $this->getContainerBuilder();
		$name = Helpers::expand($name, $builder->parameters);

		$name = sprintf('%s-%s', $prefix, $name);

		return $name;
	}


	/**
	 * @param array<string, mixed> $config
	 * @param \Nette\DI\ServiceDefinition|\Nette\DI\Definitions\ServiceDefinition $configuration
	 */
	private function prepareGroup(array $config, $configuration, string $prefix, ?string $group): void
	{
		if ( ! $group) {
			return;
		}

		$webPrograms = implode(',', array_map(function ($name) use ($prefix): string {
			return $this->prepareName($name, $prefix);
		}, array_keys($config['program'])));

		$sectionClass = (new Configuration)->findSection('group');
		$configuration->addSetup('addSection', [
			new \Nette\DI\Definitions\Statement(
				$sectionClass,
				[
					$group,
					['programs' => $webPrograms],
				]
			)
		]);
	}
}
