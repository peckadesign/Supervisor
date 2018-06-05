<?php declare(strict_types = 1);

namespace Pd\Supervisor\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
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

		$config = $this->getConfig(self::DEFAULTS);
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IConfigurationProvider) {
				$config['configuration'] = array_merge_recursive($extension->getSupervisorConfiguration(), $config['configuration']);
			}
		}

		$this->loadSupervisorConfiguration(
			(array) $config['configuration'],
			(array) $config['defaults'],
			isset($config['prefix']) ? (string) $config['prefix'] : NULL,
			isset($config['group']) ? (string) $config['group'] : NULL
		);

		$builder->addDefinition($this->prefix('renderCommand'))
			->setClass(RenderCommand::class, [strtr($this->prefix('render'), '.', ':')])
			->addTag(ConsoleExtension::TAG_COMMAND)
		;
		$builder->addDefinition($this->prefix('writeCommand'))
			->setClass(WriteCommand::class, [strtr($this->prefix('write'), '.', ':')])
			->addTag(ConsoleExtension::TAG_COMMAND)
		;
	}


	private function loadSupervisorConfiguration(array $config, array $defaults = [], string $prefix = NULL, string $group = NULL)
	{
		$builder = $this->getContainerBuilder();

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass(Configuration::class)
		;

		foreach ($config as $sectionName => $sectionConfig) {
			if ( ! $sectionClass = (new Configuration)->findSection($sectionName)) {
				$sectionClass = GenericSection::class;
			}
			if (is_subclass_of($sectionClass, Named::class)) {
				foreach ((array) $sectionConfig as $name => $properties) {
					$name = $this->prepareName($name, $prefix);
					$configuration->addSetup('addSection', [
						new Statement($sectionClass, [
							$name,
							isset($defaults[$sectionName]) ? $this->mergeProperties($properties, $defaults[$sectionName]) : $properties,
						]),
					]);
				}
			} else {
				$configuration->addSetup('addSection', [
					new Statement(
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


	private function prepareName(string $name, string $prefix = NULL): string
	{
		$builder = $this->getContainerBuilder();
		$parameters = [
			'webId' => $builder->parameters['webId'],
		];
		$name = Helpers::expand($name, $builder->parameters);

		if ($prefix !== NULL) {
			$name = sprintf('%s-%s', $prefix, $name);
		}

		return $name;
	}


	private function prepareGroup(array $config, \Nette\DI\ServiceDefinition $configuration, string $prefix = NULL, string $group = NULL): void
	{
		if ( ! $group) {
			return;
		}

		$webPrograms = implode(',', array_map(function ($name) use ($prefix): string {
			return $this->prepareName($name, $prefix);
		}, array_keys($config['program'])));

		$sectionClass = (new Configuration)->findSection('group');
		$configuration->addSetup('addSection', [
			new Statement(
				$sectionClass,
				[
					$group,
					['programs' => $webPrograms],
				]
			)
		]);
	}
}
