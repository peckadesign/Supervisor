<?php declare(strict_types = 1);

namespace Pd\Supervisor\Console;

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

	private \Supervisor\Configuration\Configuration $configuration;


	public function __construct(string $name, \Supervisor\Configuration\Configuration $configuration)
	{
		parent::__construct($name);
		$this->configuration = $configuration;
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
		$file = $input->getArgument('file');
		$writer = new IniFileWriter($file);

		if ($input->getOption('merge')) {
			$loader = new IniFileLoader($file);
			try {
				$loader->load($this->configuration);
			} catch (LoaderException $exception) {
				$output->writeln($exception->getMessage());

				return 1;
			}
		}
		try {
			$writer->write($this->configuration);
			$output->writeln(sprintf('Supervisor configuration has been successfully written to file %s', $file));

			return 0;
		} catch (\Supervisor\Configuration\Exception\WriterException $e) {
			$output->writeln($e->getMessage());
		}

		return 1;
	}

}
