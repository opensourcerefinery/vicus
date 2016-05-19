<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vicus\Listener\Session;

use Pimple\Container;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Sets the session in the request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionListener extends BaseSessionListener
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getSession()
    {
        if (!isset($this->container['session'])) {
            return null;
        }

        return $this->container['session'];
    }
}
