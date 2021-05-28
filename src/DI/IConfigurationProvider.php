<?php declare(strict_types = 1);

namespace Pd\Supervisor\DI;

interface IConfigurationProvider
{

	/**
	 * @return array<string, mixed>
	 */
	public function getSupervisorConfiguration(): array;

}
