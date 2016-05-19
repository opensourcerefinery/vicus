<?php

/*
 * This file is part of ConfigServiceProvider.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vicus\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Vicus\Api\BootableProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

	private $filename;
	private $replacements = array();
	private $driver;
	private $prefix = null;

	public function __construct($filename, array $replacements = array(), ConfigDriver $driver = null, $prefix = null)
	{
		$this->filename = $filename;
		$this->prefix = $prefix;

		if ($replacements) {
			foreach ($replacements as $key => $value) {
				$this->replacements['%' . $key . '%'] = $value;
			}
		}

		$this->driver = $driver ? : new \Vicus\Driver\ChainConfigDriver(array(
			new \Vicus\Driver\PhpConfigDriver(),
			new \Vicus\Driver\YamlConfigDriver(),
			new \Vicus\Driver\JsonConfigDriver(),
			new \Vicus\Driver\TomlConfigDriver(),
		));
	}

	public function register(Container $container)
	{
		$config = $this->readConfig();

		foreach ($config as $name => $value) {
			if ('%' === substr($name, 0, 1)) {
				$this->replacements[$name] = (string) $value;
			}
		}

		$this->merge($container, $config);
	}

	public function boot(Container $container)
	{

	}

	private function merge(Container $container, array $config)
	{
		if ($this->prefix) {
			$config = array($this->prefix => $config);
		}

		foreach ($config as $name => $value) {
			if (isset($container[$name]) && is_array($value)) {
				$container[$name] = $this->mergeRecursively($container[$name], $value);
			} else {
				$container[$name] = $this->doReplacements($value);
			}
		}
	}

	private function mergeRecursively(array $currentValue, array $newValue)
	{
		foreach ($newValue as $name => $value) {
			if (is_array($value) && isset($currentValue[$name])) {
				$currentValue[$name] = $this->mergeRecursively($currentValue[$name], $value);
			} else {
				$currentValue[$name] = $this->doReplacements($value);
			}
		}

		return $currentValue;
	}

	private function doReplacements($value)
	{
		if (!$this->replacements) {
			return $value;
		}

		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = $this->doReplacements($v);
			}

			return $value;
		}

		if (is_string($value)) {
			return strtr($value, $this->replacements);
		}

		return $value;
	}

	private function readConfig()
	{
		if (!$this->filename) {
			throw new \RuntimeException('A valid configuration file must be passed before reading the config.');
		}

		if (!file_exists($this->filename)) {
			throw new \InvalidArgumentException(
			sprintf("The config file '%s' does not exist.", $this->filename));
		}

		if ($this->driver->supports($this->filename)) {
			return $this->driver->load($this->filename);
		}

		throw new \InvalidArgumentException(
		sprintf("The config file '%s' appears to have an invalid format.", $this->filename));
	}

}
