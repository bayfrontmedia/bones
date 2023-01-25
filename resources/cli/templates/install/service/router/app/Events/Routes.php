<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\RouteIt\Router;

/**
 * Routes event subscriber.
 *
 * Created with Bones v_bones_version_
 */
class Routes extends EventSubscriber implements EventSubscriberInterface
{

    protected $router;
    protected $filter;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Router $router, FilterService $filter)
    {
        $this->router = $router;
        $this->filter = $filter;
    }

    /**
     * NOTE:
     * Technically, routes do not have to be added until the app.http event,
     * however, if they are to be available via CLI, such as with the
     * route:list command, they need to be added earlier.
     *
     * @inheritDoc
     */

    public function getSubscriptions(): array
    {
        return [
            'app.bootstrap' => [
                [
                    'method' => 'addRoutes',
                    'priority' => 5
                ]
            ]
        ];
    }

    /**
     * @return void
     */

    public function addRoutes()
    {

        $this->router->setHost(App::getConfig('router.host'))
            ->setRoutePrefix(App::getConfig('router.route_prefix')) // Unfiltered
            ->addNamedRoute('/storage', 'storage')
            ->setRoutePrefix($this->filter->doFilter('router.route_prefix', App::getConfig('router.route_prefix'))) // Filtered
            ->addFallback('ANY', function() {
                App::abort(404);
            })
            ->get('/', 'Home:index', [], 'home');

    }

}