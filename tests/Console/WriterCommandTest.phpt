<?php declare(strict_types = 1);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/DebugOutput.php';

$configuration = new \Supervisor\Configuration\Configuration();
$configuration->addSection(new \Supervisor\Configuration\Section\Program('abc', [
	'command' => 'echo 1',
]));

$command = new \Pd\Supervisor\Console\WriteCommand('supervisor:write', $configuration);

$output = new \PdTests\Supervisor\Console\DebugOutput;

$outputFile = __DIR__ . '/output.ini';

$exitCode = $command->run(new \Symfony\Component\Console\Input\ArrayInput([
	'file' => $outputFile,
]), $output);

\Tester\Assert::same(0, $exitCode);

$expected = <<<OUTPUT
[program:abc]
command = echo 1

OUTPUT;

\Tester\Assert::matchFile($outputFile, $expected);


$configuration->addSection(new \Supervisor\Configuration\Section\Program('bcd', [
	'command' => 'ls',
]));

$command = new \Pd\Supervisor\Console\WriteCommand('supervisor:write', $configuration);

$exitCode = $command->run(new \Symfony\Component\Console\Input\StringInput("--merge $outputFile"), $output);

\Tester\Assert::same(0, $exitCode);

$expected = <<<OUTPUT
[program:abc]
command = echo 1

[program:bcd]
command = ls

OUTPUT;

\Tester\Assert::matchFile($outputFile, $expected);
