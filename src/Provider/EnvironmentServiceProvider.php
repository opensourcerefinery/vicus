<?php

/**
 * This file is part of EnvironmentServiceProvider for Silex
 *
 * @link https://github.com/simblo/environment-service-provider EnvironmentServiceProvider
 * @copyright (c) 2014 - 2015, Holger Braehne
 * @license http://raw.github.com/simblo/environment-service-provider/master/LICENSE MIT
 */

namespace Vicus\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Vicus\Api\BootableProviderInterface;
use Vicus\Exception\NoEnvironmentSetException;

/**
 * EnvironmentServiceProvider
 *
 * @author Holger Braehne <holger.braehne@simblo.org>
 * @since 1.0.0
 */
class EnvironmentServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

	//some default environments
	const ENV_DEVELOPMENT = 'development';
	const ENV_TESTING = 'testing';
	const ENV_STAGING = 'staging';
	const ENV_PRODUCTION = 'production';

	private $default;
	private $filepath;
	private $filename;
	private $variable;
	private $environments = array();

	/**
	 * Constructor.
	 */
	public function __construct(array $options = array())
	{

		$this->default = true === isset($options['environment.default']) ? $options['environment.default'] : 'development';
		$this->filepath = true === isset($options['environment.filepath']) ? $options['environment.filepath'] : false;
		$this->filename = true === isset($options['environment.filename']) ? $options['environment.filename'] : '.setenv';
		$this->variable = true === isset($options['environment.variable']) ? $options['environment.variable'] : 'APP_ENVIRONMENT';

		$this->environments = true === isset($options['environments']) ? $options['environments'] : array(
			self::ENV_DEVELOPMENT,
			self::ENV_TESTING,
			self::ENV_STAGING,
			self::ENV_PRODUCTION
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function register(Container $container)
	{
		$environment = $this->determine();

		if (false !== $environment && true === $this->isValid($environment)) {
			$container['environment'] = $environment;
		} elseif (false !== $this->default) {
			$container['environment'] = $this->default;
		} else {
			throw new NoEnvironmentSetException('No valid runtime environment was set.');
		}
	}

	public function boot(Container $container)
	{

	}

	/**
	 * Dertermines the runtime environment by checking for a file or an
	 * environment variable containing the applications runtime environment
	 * name. If both checks fail it will return false.
	 *
	 * @return string|bool
	 */
	private function determine()
	{

		if (false !== $this->filepath && true === is_readable($this->filepath . '/' . $this->filename)) {
			return strtolower(trim(file_get_contents($this->filepath . '/' . $this->filename)));
		} elseif (null !== getenv($this->variable)) {
			return getenv($this->variable);
		} else {
			return false;
		}
	}

	/**
	 * Validates the given environment against the internal environments array
	 * to ensure the application will run in a known environment.
	 *
	 * @param string $environment
	 * @return bool
	 */
	private function isValid($environment)
	{
		return in_array($environment, $this->environments);
	}

}
