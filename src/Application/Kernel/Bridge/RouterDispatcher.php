<?php

namespace Bayfront\Bones\Application\Kernel\Bridge;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Container\Container;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\RouteIt\DispatchException;
use Bayfront\StringHelpers\Str;

/**
 * Used to dispatch a Route-It route using the service container.
 */
class RouterDispatcher
{

    protected Container $container;
    protected FilterService $filters;
    protected EventService $events;
    protected Response $response;
    protected array $route;

    public function __construct(Container $container, EventService $events, FilterService $filters, Response $response, array $route)
    {
        $this->container = $container;
        $this->events = $events;
        $this->filters = $filters;
        $this->response = $response;
        $this->route = $route;
    }

    /**
     * Include a file without exposing variables.
     *
     * @param string $file
     * @return void
     */

    protected function safeInclude(string $file): void
    {
        include($file);
    }

    /**
     * Dispatch route.
     *
     * @return mixed
     * @throws DispatchException
     * @throws InvalidStatusCodeException
     * @throws ContainerException
     * @throws ServiceException
     * @throws NotFoundException
     */

    public function dispatchRoute(): mixed
    {

        $route = Arr::only($this->route, [
            'type',
            'destination',
            'status',
            'params'
        ]);

        if (Arr::get($route, 'type') == 'redirect') {

            $this->events->doEvent('app.dispatch', $route);

            $this->response->redirect(Arr::get($route, 'destination', ''), Arr::get($route, 'status', 302));

            return true;

        }

        if (Arr::get($route, 'type') == 'route' || Arr::get($route, 'type') == 'automap' || Arr::get($route, 'type') == 'fallback') {

            // ------------------------- Dispatch -------------------------

            // Callable

            if (is_callable(Arr::get($route, 'destination'))) {

                $this->events->doEvent('app.dispatch', $route);

                return call_user_func(Arr::get($route, 'destination', ''), Arr::get($route, 'params', []));

            }

            // Array - is_callable returns false when class has a constructor

            if (is_array(Arr::get($route, 'destination'))) {

                $dest = Arr::get($route, 'destination');

                if (isset($dest[0]) && isset($dest[1]) && method_exists($dest[0], $dest[1])) {

                    $controller = $this->container->make($dest[0]);
                    $method = $dest[1];

                    $this->events->doEvent('app.dispatch', $route);

                    return $controller->$method(Arr::get($route, 'params', []));

                }

                throw new ServiceException('Router unable to dispatch: unknown array destination');

            }

            // File

            if (Str::startsWith(Arr::get($route, 'destination', ''), '@')) {

                $file = App::getConfig('router.files_root_path') . '/' . ltrim(Arr::get($route, 'destination', ''), '@');

                if (is_file($file)) {

                    $this->events->doEvent('app.dispatch', $route);

                    $this->safeInclude($file);

                    return true;

                }

                throw new ServiceException('Router unable to dispatch: file does not exist (' . $file . ')');

            }

            // Class:method

            $loc = explode(':', Arr::get($route, 'destination', ''), 2);

            if (isset($loc[1])) { // Dispatch to Class:method

                if (App::getConfig('router.class_namespace') == ''
                    || Str::startsWith($loc[0], App::getConfig('router.class_namespace'))) {

                    $class_name = $loc[0];

                } else {

                    $class_name = App::getConfig('router.class_namespace') . '\\' . $loc[0];

                }

                $method = $loc[1];

                $controller = $this->container->make($class_name);

                $this->events->doEvent('app.dispatch', $route);

                return $controller->$method(Arr::get($route, 'params', []));

            }

            throw new ServiceException('Router unable to dispatch: invalid destination');

        }

        throw new ServiceException('Router unable to dispatch: unknown type');

    }

}