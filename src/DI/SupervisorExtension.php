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

	public function getConfigSchema(): \Nette\Schema\Schema
	{
		return \Nette\Schema\Expect::structure(
			[
				'prefix' => \Nette\Schema\Expect::string(),
				'configuration' => \Nette\Schema\Expect::array([]),
				'defaults' => \Nette\Schema\Expect::array([]),
				'group' => \Nette\Schema\Expect::string()->nullable(),
			]
		)->castTo(\Pd\Supervisor\Adapter\Nette\DI\ConfigObject::class);
	}


	public function getConfig(): \Pd\Supervisor\Adapter\Nette\DI\ConfigObject
	{
		\assert($this->config instanceof \Pd\Supervisor\Adapter\Nette\DI\ConfigObject);

		return $this->config;
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$configObject = $this->getConfig();

		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IConfigurationProvider) {
				$configObject->configuration = array_merge_recursive($extension->getSupervisorConfiguration(), $configObject->configuration);
			}
		}

		$this->loadSupervisorConfiguration(
			(array) $configObject->configuration,
			(array) $configObject->defaults,
			$configObject->prefix,
			isset($configObject->group) ? (string) $configObject->group : NULL
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
						new \Nette\DI\Definitions\Statement($sectionClass, [
							$name,
							isset($defaults[$sectionName]) ? $this->mergeProperties($properties, $defaults[$sectionName]) : $properties,
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
	 * @param \Nette\DI\Definitions\ServiceDefinition $configuration
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
