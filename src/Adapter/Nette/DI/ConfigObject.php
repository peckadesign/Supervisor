<?php declare(strict_types = 1);

namespace Pd\Supervisor\Adapter\Nette\DI;

class ConfigObject
{
	/**
	 * @var string
	 */
	public $prefix;

	/**
	 * @var array<mixed>
	 */
	public $configuration;

	/**
	 * @var array<mixed>
	 */
	public $defaults;

	/**
	 * @var ?string
	 */
	public $group;

}
