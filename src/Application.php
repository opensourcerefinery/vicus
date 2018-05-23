<?php

namespace Vicus;

/**
 * Description of Application
 *
 * @author Michael Koert <mkoert at bluebikeproductions.com>
 */
// use Symfony\Component\Debug\ErrorHandler;
// use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Loader\YamlFileLoader as RoutingYamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;


use OpenSourceRefinery\HttpKernel\EventListener\RouterListener;
use OpenSourceRefinery\HttpKernel\EventListener\ExceptionListener;

use OpenSourceRefinery\Yaml2Pimple\ContainerBuilder;
use OpenSourceRefinery\Yaml2Pimple\YamlFileLoader as ContainerYamlFileLoader;
use Pimple\ServiceProviderInterface;
use Vicus\Api\BootableProviderInterface;
use Vicus\Api\EventListenerProviderInterface;

class Application
{
    public $container;

    const EARLY_EVENT = 512;
    const LATE_EVENT = -512;

    protected $providers = array();
    protected $booted = false;

    public function __construct($container, $config, array $values = array())
    {
        $this->container = $container;

        $defaultConfig = [
            'config.path' => null,
            'view.path' => null,
            'http.cache.path' => null,
            'view.cache.path' => null,
            'view.compile.path' => null,
            'routes.yml' => 'routes.yml',
            'services.yml' => 'services.yml',
        ];

        $realConfig = array_merge($defaultConfig, $config);

        $this->container['config'] = $realConfig;


        $this->container['callback_resolver'] = function ($c) {
            return new \Vicus\Component\CallbackResolver($c);
        };

        $this->container['controller_resolver'] = function ($c) {
            return new HttpKernel\Controller\ControllerResolver();
        };

        $this->container['resolver'] = function ($c) {
            return new \Vicus\Component\HttpKernel\Controller\ServiceControllerResolver($c['controller_resolver'], $c['callback_resolver']);
        };
        $this->container['routes'] = function ($c) {
            return new RouteCollection();
        };
        $this->container['routes'] = $this->container->extend('routes', function (RouteCollection $routes, $c) {
            $collection = $c['routing_yaml_file_loader']->load($c['config']['routes.yml']);
            $routes->addCollection($collection);

            return $routes;
        });

        $this->container['config_file_locator'] = function ($c) {
            return new FileLocator($c['config']['config.path']);
        };
        $this->container['routing_yaml_file_loader'] = function ($c) {
            $loader = new RoutingYamlFileLoader($c['config_file_locator']);
            return $loader;
        };

        $this->container['container_builder'] = function ($c) {
            return new ContainerBuilder($c);
        };

        $this->container['container_yaml_file_loader'] = function ($c) {
            return new ContainerYamlFileLoader($c['container_builder'], $c['config_file_locator']);
        };

        $this->container['services'] = function ($c) {
            return $c['container_yaml_file_loader']->load($c['config']['services.yml']);
        };

        $this->container['context'] = function ($c) {
            $context = new Routing\RequestContext();
            $context->setHttpPort($c['request.http_port']);
            $context->setHttpsPort($c['request.https_port']);
            return $context;
        };

        $this->container['matcher'] = function ($c) {
            return new Routing\Matcher\UrlMatcher($c['routes'], $c['context']);
        };

        $this->container['event_dispatcher'] = function ($c) {
            return new EventDispatcher();
        };

        $this->container['exception_handler'] = function ($c) {
            return new ExceptionHandler($c['debug']);
        };

        $this->container['event_dispatcher_add_listeners'] = function ($c) {
            $exceptionController = ($c['exception_controller']) ? $c['exception_controller'] : '\\\Vicus\\Controller\\ErrorController::exceptionAction';

            $logger = null;
            if ($this->container['logger']) {
                $logger = $this->container['logger'];
            }
            $c['event_dispatcher']->addSubscriber(new RouterListener($c['matcher'], null, $c['context'], $logger, null, $c['debug']));

            $c['event_dispatcher']->addSubscriber(new \Vicus\Listener\StringResponseListener());
            $c['event_dispatcher']->addSubscriber(new \Vicus\Listener\ContentLengthListener());

            $c['event_dispatcher']->addSubscriber(new HttpKernel\EventListener\StreamedResponseListener());
            // $c['event_dispatcher']->addSubscriber(new HttpKernel\EventListener\RouterListener($c['matcher']));
            $listener = new ExceptionListener($exceptionController, $logger);
            $c['event_dispatcher']->addSubscriber($listener);

            if (isset($c['exception_handler'])) {
                $c['event_dispatcher']->addSubscriber($c['exception_handler']);
            }
        };

        $this->container['kernel'] = function ($c) {
            return new \Vicus\Kernel($c['event_dispatcher'], $c['resolver']);
        };
        $this->container->extends['kernel'] = function ($c) {
            return new HttpCache($c['kernel'], new Store($c['config']['http.cache.path']));
        };

        $this->container['request_error'] = $this->container->protect(function () {
            throw new \RuntimeException('Accessed request service outside of request scope. Try moving that call to a before handler or controller.');
        });

        //request is already used in bootstrap. Any silex documentations that askes for request replace with request_state (for now)
        $this->container['request'] = $this->container['request_error'];

        $this->container['request.http_port'] = 80;
        $this->container['request.https_port'] = 443;
        $this->container['debug'] = false;
        $this->container['charset'] = 'UTF-8';
        $this->container['locale'] = 'en';

        //Build services list in container
        $this->container['services'];

        foreach ($values as $key => $value) {
            $this->container[$key] = $value;
        }
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return Application
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;
        $container = $this->container;
        $container->register($provider, $values);

        return $container;
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach ($this->providers as $provider) {
            if ($provider instanceof EventListenerProviderInterface) {
                $provider->subscribe($this->container, $this->container['event_dispatcher']);
            }

            if ($provider instanceof BootableProviderInterface) {
                $provider->boot($this->container);
            }
        }
    }

    /**
     * Handles the request and delivers the response.
     *
     * @param Request|null $request Request to process
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $this->container['context']->fromRequest($request);

        //Add event listeners - HAS TO BE CALLED BEFORE KERNAL IS USED - needs modifications
        $this->container['event_dispatcher_add_listeners'];

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     *
     * If you call this method directly instead of run(), you must call the
     * terminate() method yourself if you want the finish filters to be run.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->booted) {
            $this->boot();
        }

        $current = HttpKernelInterface::SUB_REQUEST === $type ? $this->container['request'] : $this->container['request_error'];

        $this->container['request'] = $request;
        $this->flush();

        $response = $this->container['kernel']->handle($request, $type, $catch);

        $this->container['request'] = $current;

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this->container['kernel']->terminate($request, $response);
    }

    // NOT FULLY DEVELOPED
    public function error($callback, $priority = -8)
    {
        $this->on(KernelEvents::EXCEPTION, new ExceptionListenerWrapper($this->container, $callback), $priority);
    }

    // NOT FULLY DEVELOPED
    public function on($eventName, $callback, $priority = 0)
    {
        if ($this->booted) {
            $this->container['event_dispatcher']->addListener($eventName, $this->container['callback_resolver']->resolveCallback($callback), $priority);

            return;
        }

        $this->container->extend('event_dispatcher', function ($dispatcher, $c) use ($callback, $priority, $eventName) {
            $dispatcher->addListener($eventName, $c['callback_resolver']->resolveCallback($callback), $priority);

            return $dispatcher;
        });
    }

    /**
     * Registers a before filter.
     *
     * Before filters are run before any route has been matched.
     *
     * @param mixed $callback Before filter callback
     * @param int   $priority The higher this value, the earlier an event
     *                        listener will be triggered in the chain (defaults to 0)
     */
    public function before($callback, $priority = 0)
    {
        //        $app = $this;
        $container = $this->container;

        $this->on(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($callback, $container) {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $ret = call_user_func($container['callback_resolver']->resolveCallback($callback), $event->getRequest(), $container);

            if ($ret instanceof Response) {
                $event->setResponse($ret);
            }
        }, $priority);
    }

    /**
     * Registers an after filter.
     *
     * After filters are run after the controller has been executed.
     *
     * @param mixed $callback After filter callback
     * @param int   $priority The higher this value, the earlier an event
     *                        listener will be triggered in the chain (defaults to 0)
     */
    public function after($callback, $priority = 0)
    {
        //        $app = $this;
        $container = $this->container;

        $this->on(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($callback, $container) {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $response = call_user_func($this->container['callback_resolver']->resolveCallback($callback), $event->getRequest(), $event->getResponse(), $container);
            if ($response instanceof Response) {
                $event->setResponse($response);
            } elseif (null !== $response) {
                throw new \RuntimeException('An after middleware returned an invalid response value. Must return null or an instance of Response.');
            }
        }, $priority);
    }

    /**
     * Registers a finish filter.
     *
     * Finish filters are run after the response has been sent.
     *
     * @param mixed $callback Finish filter callback
     * @param int   $priority The higher this value, the earlier an event
     *                        listener will be triggered in the chain (defaults to 0)
     */
    public function finish($callback, $priority = 0)
    {
        //        $app = $this;
        $container = $this->container;
        $this->on(KernelEvents::TERMINATE, function (PostResponseEvent $event) use ($callback, $container) {
            call_user_func($this->container['callback_resolver']->resolveCallback($callback), $event->getRequest(), $event->getResponse(), $container);
        }, $priority);
    }

    /**
     * Aborts the current request by sending a proper HTTP error.
     *
     * @param int    $statusCode The HTTP status code
     * @param string $message    The status message
     * @param array  $headers    An array of HTTP headers
     */
    public function abort($statusCode, $message = '', array $headers = array())
    {
        throw new HttpException($statusCode, $message, null, $headers);
    }

    /**
     * Flushes the controller collection.
     *
     * @param string $prefix The route prefix
     */
    public function flush($prefix = '')
    {
        //        $this->container['routes']->addCollection($this->container['controllers']->flush($prefix));
    }
}
