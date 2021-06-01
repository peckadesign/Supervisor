<?php declare(strict_types = 1);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/DebugOutput.php';

$configuration = new \Supervisor\Configuration\Configuration();
$configuration->addSection(new \Supervisor\Configuration\Section\Program('abc', [
	'command' => 'echo 1',
]));

$renderer = new \Indigo\Ini\Renderer(\Indigo\Ini\Renderer::ARRAY_MODE_CONCAT | \Indigo\Ini\Renderer::BOOLEAN_MODE_BOOL_STRING);

$command = new \Pd\Supervisor\Console\RenderCommand('supervisor:render', $configuration, $renderer);

$output = new \PdTests\Supervisor\Console\DebugOutput;

$command->run(new \Symfony\Component\Console\Input\ArrayInput([]), $output);

$expected = <<<OUTPUT
[program:abc]
command = echo 1

OUTPUT;

\Tester\Assert::same($expected, $output->getOutput());
