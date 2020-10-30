<?php declare(strict_types = 1);

namespace Pd\Supervisor\Adapter\Nette\DI;

class DiStatementFactory
{
	public static function createDiStatement($entity, array $arguments = [])
	{
		if (\class_exists(\Nette\DI\Definitions\Statement::class)) {
			return self::createDiStatementNette30($entity, $arguments);
		} else {
			return self::createDiStatementNette24($entity, $arguments);
		}
	}


	private static function createDiStatementNette24($entity, array $arguments = [])
	{
		return new \Nette\DI\Statement($entity, $arguments);
	}


	private static function createDiStatementNette30($entity, array $arguments = [])
	{
		return new \Nette\DI\Definitions\Statement($entity, $arguments);
	}
}
