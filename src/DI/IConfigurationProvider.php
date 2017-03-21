<?php declare(strict_types = 1);

namespace Pd\Supervisor\DI;

interface IConfigurationProvider
{

	public function getSupervisorConfiguration(): array;
}
