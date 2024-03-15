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

	overrides:
		- 	match:
				name: '/program-name/i'
				property: '/numprocs/i'
				value: '/([1-9]\d+|[2-9])/'
			value: 1
```

Overrides add new option, if you have multiple neons with a lot of commands, and you need on test/dev/docker environment use only one process instead of many, you can easily do so with set of rules, that will override setup by neon (mainly to not include definitions on multiple files).

It is based on RegEx matcher, that will check if name is correct, if property name is correct and if value is correct (all of these are optional, but at least one must be present). If RegEx pass all rules, it will change the value.

In example above, the match will get every app that name contains program-name, have property numprocs and that property is bigger then 1, then replace it to number 1

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
