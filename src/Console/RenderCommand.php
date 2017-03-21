<?php declare(strict_types = 1);

namespace Pd\Supervisor\Console;

use Nette\DI\Container;
use Supervisor\Configuration\Configuration;
use Supervisor\Configuration\Writer\HasIniRenderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class RenderCommand extends Command
{

	use HasIniRenderer;

	/**
	 * @var Container
	 */
	private $container;


	public function __construct(string $name, Container $container)
	{
		parent::__construct($name);
		$this->container = $container;
	}


	protected function configure()
	{
		parent::configure();
		$this->setDescription('Renders supervisor configuration');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/**
		 * @var Configuration $configuration
		 */
		$configuration = $this->container->getByType(Configuration::class);
		$output->write($this->getRenderer()->render($configuration->toArray()));
	}

}
