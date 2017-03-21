<?php declare(strict_types = 1);

namespace Pd\Supervisor\Console;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Nette\DI\Container;
use Supervisor\Configuration\Configuration;
use Supervisor\Configuration\Exception\LoaderException;
use Supervisor\Configuration\Loader\IniFileLoader;
use Supervisor\Configuration\Writer\IniFileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


final class WriteCommand extends Command
{

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
		$this->setDescription('Writes supervisor configuration to file');
		$this->addArgument('file', InputArgument::REQUIRED, 'The path to write supervisor configuration.');
		$this->addOption('merge', 'm', InputOption::VALUE_NONE, 'Merge configurations if file exists.');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$filesystemAdapter = new Local(getcwd());
		$filesystem = new Filesystem($filesystemAdapter);
		$file = $input->getArgument('file');
		$writer = new IniFileWriter($filesystem, $file);
		/**
		 * @var Configuration $configuration
		 */
		$configuration = $this->container->getByType(Configuration::class);
		if ($input->getOption('merge')) {
			$loader = new IniFileLoader($filesystem, $file);
			try {
				$loader->load($configuration);
			} catch (LoaderException $exception) {
				$output->writeln($exception->getMessage());
			}
		}
		if ($writer->write($configuration)) {
			$output->writeln(sprintf('Supervisor configuration has been successfully written to file %s', $filesystemAdapter->applyPathPrefix($file)));
		}
	}

}
