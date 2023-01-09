<?php

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\RouteIt\DispatchException;
use Bayfront\RouteIt\Router;

/**
 * Get Router instance from container.
 *
 * See: https://github.com/bayfrontmedia/route-it
 *
 * @return Router
 *
 * @throws NotFoundException
 */

function get_router(): Router
{
    return App::getFromContainer('router');
}

/**
 * Returns array of named routes.
 *
 * @return array
 *
 * @throws NotFoundException
 */

function get_named_routes(): array
{
    return get_router()->getNamedRoutes();
}

/**
 * Returns URL of a single named route.
 *
 * See: https://github.com/bayfrontmedia/route-it#getnamedroute
 *
 * @param string $name
 * @param string $default (Default value to return if named route does not exist)
 *
 * @return string
 *
 * @throws NotFoundException
 */

function get_named_route(string $name, string $default = ''): string
{
    return get_router()->getNamedRoute($name, $default);
}

/**
 * Redirects to a given URL using a given status code.
 *
 * See: https://github.com/bayfrontmedia/route-it#redirect
 *
 * @param string $url (Fully qualified URL)
 * @param int $status (Status code to return)
 *
 * @throws NotFoundException
 * @throws DispatchException
 */

function redirect(string $url, int $status = 302): void
{
    get_router()->redirect($url, $status);
}