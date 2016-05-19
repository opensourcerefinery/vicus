<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Vicus\Provider;



 use Vicus\Api\BootableProviderInterface;
 use Vicus\Exception\NoEnvironmentSetException;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Vicus\Listener\LogListener;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $container)
    {

        $container['logger'] = function () use ($container) {
            return $container['monolog'];
        };

        if ($bridge = class_exists('Symfony\Bridge\Monolog\Logger')) {
            $container['monolog.handler.debug'] = function () use ($container) {
                $level = MonologServiceProvider::translateLevel($container['monolog.level']);

                return new DebugHandler($level);
            };
        }

        $container['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $container['monolog'] = function ($container) {
            $log = new $container['monolog.logger.class']($container['monolog.name']);

            $log->pushHandler($container['monolog.handler']);

            if (isset($container['debug']) && $container['debug'] && isset($container['monolog.handler.debug'])) {
                $log->pushHandler($container['monolog.handler.debug']);
            }

            return $log;
        };

        $container['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $container['monolog.handler'] = function () use ($container) {
            $level = MonologServiceProvider::translateLevel($container['monolog.level']);

            $handler = new StreamHandler($container['monolog.logfile'], $level, $container['monolog.bubble'], $container['monolog.permission']);
            $handler->setFormatter($container['monolog.formatter']);

            return $handler;
        };

        $container['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $container['monolog.listener'] = function () use ($container) {
            return new LogListener($container['logger'], $container['monolog.exception.logger_filter']);
        };

        $container['monolog.name'] = 'myapp';
        $container['monolog.bubble'] = true;
        $container['monolog.permission'] = null;
        $container['monolog.exception.logger_filter'] = null;
    }

    public function boot(Container $container)
    {
        if (isset($container['monolog.listener'])) {
            $container['event_dispatcher']->addSubscriber($container['monolog.listener']);
        }
    }

    public static function translateLevel($name)
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }
}
