<?php

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\Hooks\Hooks;

/**
 * Get PHP Hooks instance from container.
 *
 * See: https://github.com/bayfrontmedia/php-hooks
 *
 * @return Hooks
 *
 * @throws NotFoundException
 */

function get_hooks(): Hooks
{
    return App::getFromContainer('hooks');
}

/**
 * Adds a hook for a given event name.
 *
 * See: https://github.com/bayfrontmedia/php-hooks#addevent
 *
 * @param string $name (Name of event)
 * @param callable $function
 * @param int $priority (Hooks will be executed by order of priority in descending order)
 *
 * @return void
 *
 * @throws NotFoundException
 */

function add_event(string $name, callable $function, int $priority = 5): void
{
    get_hooks()->addEvent($name, $function, $priority);
}

/**
 * Execute queued hooks for a given event in order of priority.
 *
 * See: https://github.com/bayfrontmedia/php-hooks#doevent
 *
 * @param string $name (Name of event)
 * @param mixed $arg (Optional additional argument(s) to be passed to the functions hooked to the event)
 *
 * @return void
 *
 * @throws NotFoundException
 */

function do_event(string $name, ...$arg): void
{
    get_hooks()->doEvent($name, ...$arg);
}

/**
 * Adds a hook for a given filter name.
 *
 * See: https://github.com/bayfrontmedia/php-hooks#addfilter
 *
 * @param string $name (Name of filter)
 * @param callable $function
 * @param int $priority (Filters will be executed in order of priority in descending order)
 *
 * @return void
 *
 * @throws NotFoundException
 */

function add_filter(string $name, callable $function, int $priority = 5): void
{
    get_hooks()->addFilter($name, $function, $priority);
}

/**
 * Filters value through queued filters in order of priority.
 *
 * See: https://github.com/bayfrontmedia/php-hooks#dofilter
 *
 * @param string $name (Name of filter)
 * @param mixed $value (Original value to be filtered)
 *
 * @return mixed (Filtered value)
 *
 * @throws NotFoundException
 */

function do_filter(string $name, $value)
{
    return get_hooks()->doFilter($name, $value);
}