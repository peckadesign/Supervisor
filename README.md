# Supervisor

Supervisor configuration with console commands for nette applications.

## Installation

The best way to install PeckaDesign/Supervisor is using  [Composer](http://getcomposer.org/):

```sh
$ composer require pd/supervisor
```

## Configuration

Enable extension in your application configuration:

```neon
extensions:
	supervisor: Pd\Supervisor\DI\SupervisorExtension
```

Now you can configure your supervisor.

```yaml
supervisor:
	defaults:
		program:
			autorestart: on

	configuration:
		program:
			name:
				command: moo
```

## Commands

### RenderCommand

Renders supervisor configuration

```sh
$ php www/index.php supervisor:render
```

### WriteCommand

Writes supervisor configuration to file

```sh
$ php www/index.php supervisor:write supervisor.conf
```

Optionally you can merge configuration sections to existing configuration file

```sh
$ php www/index.php supervisor:write supervisor.conf -m
```
