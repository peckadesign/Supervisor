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

	const DEFAULTS = [
		'prefix' => NULL,
		'configuration' => [],
		'defaults' => [],
		'group' => NULL,
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = \Pd\Supervisor\Adapter\Nette\DI\CompilerExtensionAdapter::mergeConfigWithDefaults($this, self::DEFAULTS);

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
			(string) $config['prefix'],
			isset($config['group']) ? (string) $config['group'] : NULL
		);

		$builder->addDefinition($this->prefix('renderCommand'))
			->setFactory(RenderCommand::class, [strtr($this->prefix('render'), '.', ':')])
			->addTag(ConsoleExtension::TAG_COMMAND)
		;
		$builder->addDefinition($this->prefix('writeCommand'))
			->setFactory(WriteCommand::class, [strtr($this->prefix('write'), '.', ':')])
			->addTag(ConsoleExtension::TAG_COMMAND)
		;
	}


	private function loadSupervisorConfiguration(array $config, array $defaults = [], string $prefix, string $group = NULL)
	{
		$builder = $this->getContainerBuilder();

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setType(Configuration::class)
		;

		foreach ($config as $sectionName => $sectionConfig) {
			if ( ! $sectionClass = (new Configuration)->findSection($sectionName)) {
				$sectionClass = GenericSection::class;
			}
			if (is_subclass_of($sectionClass, Named::class)) {
				foreach ((array) $sectionConfig as $name => $properties) {
					$name = $this->prepareName($name, $prefix);
					$configuration->addSetup('addSection', [
						\Pd\Supervisor\Adapter\Nette\DI\DiStatementFactory::createDiStatement($sectionClass, [
							$name,
							isset($defaults[$sectionName]) ? $this->mergeProperties($properties, $defaults[$sectionName]) : $properties,
						]),
					]);
				}
			} else {
				$configuration->addSetup('addSection', [
					\Pd\Supervisor\Adapter\Nette\DI\DiStatementFactory::createDiStatement(
						$sectionClass, [
						isset($defaults[$sectionName]) ? $this->mergeProperties($sectionConfig, $defaults[$sectionName]) : $sectionConfig,
					]),
				]);
			}
		}

		$this->prepareGroup($config, $configuration, $prefix, $group);
	}


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
	 * @param \Nette\DI\ServiceDefinition|\Nette\DI\Definitions\ServiceDefinition $configuration
	 */
	private function prepareGroup(array $config, $configuration, string $prefix, string $group = NULL): void
	{
		if ( ! $group) {
			return;
		}

		$webPrograms = implode(',', array_map(function ($name) use ($prefix): string {
			return $this->prepareName($name, $prefix);
		}, array_keys($config['program'])));

		$sectionClass = (new Configuration)->findSection('group');
		$configuration->addSetup('addSection', [
			\Pd\Supervisor\Adapter\Nette\DI\DiStatementFactory::createDiStatement(
				$sectionClass,
				[
					$group,
					['programs' => $webPrograms],
				]
			)
		]);
	}
}
