<?php declare(strict_types = 1);

namespace PdTests\Supervisor\Console;

class DebugOutput extends \Symfony\Component\Console\Output\Output
{

	private string $output = '';

	protected function doWrite($message, $newline)
	{
		$this->output .= $message.($newline ? "\n" : '');
	}


	public function getOutput(): string
	{
		return $this->output;
	}

}
