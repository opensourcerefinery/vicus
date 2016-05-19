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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Vicus\Api\EventListenerProviderInterface;
use Vicus\Listener\Session\SessionListener;
use Vicus\Listener\Session\TestSessionListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Symfony HttpFoundation component Provider for sessions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    private $container;

    public function register(Container $container)
    {
        $this->container = $container;

        $container['session.test'] = false;

        $container['session'] = function ($container) {
            if (!isset($container['session.storage'])) {
                if ($container['session.test']) {
                    $container['session.storage'] = $container['session.storage.test'];
                } else {
                    $container['session.storage'] = $container['session.storage.native'];
                }
            }

            return new Session($container['session.storage']);
        };

        $container['session.storage.handler'] = function ($container) {
            return new NativeFileSessionHandler($container['session.storage.save_path']);
        };

        $container['session.storage.native'] = function ($container) {
            return new NativeSessionStorage(
                $container['session.storage.options'],
                $container['session.storage.handler']
            );
        };

        $container['session.listener'] = function ($container) {
            return new SessionListener($container);
        };

        $container['session.storage.test'] = function () {
            return new MockFileSessionStorage();
        };

        $container['session.listener.test'] = function ($container) {
            return new TestSessionListener($container);
        };

        $container['session.storage.options'] = array();
        $container['session.default_locale'] = 'en';
        $container['session.storage.save_path'] = null;
    }

    public function subscribe(Container $container, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($container['session.listener']);

        if ($container['session.test']) {
            $container['event_dispatcher']->addSubscriber($container['session.listener.test']);
        }
    }
}
