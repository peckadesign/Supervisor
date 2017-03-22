# Supervisor

Supervisor configuration with console commands for nette applications.

[![Downloads total](https://img.shields.io/packagist/dt/pd/supervisor.svg)](https://packagist.org/packages/pd/supervisor)
[![Build Status](https://travis-ci.org/peckadesign/Supervisor.svg?branch=master)](https://travis-ci.org/peckadesign/Supervisor)
[![Latest Stable Version](https://poser.pugx.org/pd/supervisor/v/stable)](https://github.com/peckadesign/Supervisor/releases)

## Installation

The best way to install PeckaDesign/Supervisor is using  [Composer](http://getcomposer.org/):

```sh
$ composer require pd/supervisor
```

## Configuration

Enable extension in your application configuration:

```yaml
extensions:
	supervisor: Pd\Supervisor\DI\SupervisorExtension
```

Now you can configure your supervisor.

```yaml
supervisor:
	prefix: my-project #prefixes every named section

	defaults:
		program:
			autorestart: on

	configuration:
		group:
			group-name:
				programs:
					- program-name
		program:
			program-name:
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
