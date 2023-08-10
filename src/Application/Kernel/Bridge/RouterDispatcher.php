<?php

namespace Bayfront\Bones\Application\Kernel\Bridge;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
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

        // Define everything here for continuity

        $type = Arr::get($this->route, 'type', '');
        $destination = Arr::get($this->route, 'destination', '');
        $params = $this->filters->doFilter('router.parameters', Arr::get($this->route, 'params', []));
        $status = Arr::get($this->route, 'status', 302); // Only used for redirects

        if ($type == 'redirect') {

            $this->events->doEvent('app.dispatch', [
                'type' => $type,
                'destination' => $destination,
                'params' => $params,
                'status' => $status
            ]);

            $this->response->redirect($destination, $status);

            return true;

        }

        if ($type == 'route' || $type == 'automap' || $type == 'fallback') {

            // ------------------------- Dispatch -------------------------

            // Callable

            if (is_callable($destination)) {

                $this->events->doEvent('app.dispatch', [
                    'type' => $type,
                    'destination' => $destination,
                    'params' => $params,
                    'status' => $status
                ]);

                return call_user_func($destination, $params);

            }

            // File

            if (Str::startsWith($destination, '@')) {

                $file = App::getConfig('router.files_root_path') . '/' . ltrim($destination, '@');

                if (is_file($file)) {

                    $this->events->doEvent('app.dispatch', [
                        'type' => $type,
                        'destination' => $destination,
                        'params' => $params,
                        'status' => $status
                    ]);

                    $this->safeInclude($file);

                    return true;

                }

                throw new ServiceException('Router unable to dispatch: file does not exist (' . $file . ')');

            }

            // Class:method

            $loc = explode(':', $destination, 2);

            if (isset($loc[1])) { // Dispatch to Class:method

                if (App::getConfig('router.class_namespace') == ''
                    || Str::startsWith($loc[0], App::getConfig('router.class_namespace'))) {

                    $class_name = $loc[0];

                } else {

                    $class_name = App::getConfig('router.class_namespace') . '\\' . $loc[0];

                }

                $this->events->doEvent('app.dispatch', [
                    'type' => $type,
                    'destination' => $destination,
                    'params' => $params,
                    'status' => $status
                ]);

                $method = $loc[1];

                $controller = $this->container->make($class_name);

                return $controller->$method($params);

            }

            throw new ServiceException('Router unable to dispatch: invalid destination');

        }

        throw new ServiceException('Router unable to dispatch: unknown type');

    }

}