<?php
/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
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