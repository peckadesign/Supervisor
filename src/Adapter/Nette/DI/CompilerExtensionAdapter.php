<?php declare(strict_types = 1);

namespace Pd\Supervisor\Adapter\Nette\DI;

class CompilerExtensionAdapter
{

	/**
	 * Nahrazuje volani metody $this->getConfig() s parametrem, ktery obsahuje vychozi hodnoty.
	 * Zde je pouzita predchozi funkcionalita, nez byla z getConfig() odebrana.
	 * V Nette3 se pro validaci configu pouziva Schema. To by jsme pak meli pouzit namisto teto funkce.
	 *
	 * @param array<mixed> $defaults
	 * @return array<mixed>|string
	 */
	public static function mergeConfigWithDefaults(\Nette\DI\CompilerExtension $extension, array $defaults)
	{
		$defaultConfig = \Nette\DI\Helpers::expand($defaults, $extension->getContainerBuilder()->parameters);

		return \Nette\DI\Config\Helpers::merge($extension->getConfig(), $defaultConfig);
	}

}
