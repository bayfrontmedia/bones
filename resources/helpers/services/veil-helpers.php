<?php

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\Veil\FileNotFoundException;
use Bayfront\Veil\Veil;

/**
 * Get Veil instance from container.
 *
 * See: https://github.com/bayfrontmedia/veil
 *
 * @return Veil
 *
 * @throws NotFoundException
 */

function get_veil(): Veil
{
    return App::getFromContainer('veil');
}

/**
 * Get compiled template file as a string.
 *
 * Returned value is filtered through the "veil.view" hook.
 *
 * See: https://github.com/bayfrontmedia/veil#getview
 *
 * @param string $file (Path to file from base path, excluding file extension)
 * @param array $data (Data to pass to view in dot notation)
 * @param bool $minify (Minify compiled HTML?)
 *
 * @return string
 *
 * @throws NotFoundException
 * @throws FileNotFoundException
 */

function get_view(string $file, array $data = [], bool $minify = false): string
{
    return do_filter('veil.view', get_veil()->getView($file, $data, $minify));
}

/**
 * Echo compiled template file.
 *
 * Returned value is filtered through the "veil.view" hook.
 *
 * See: https://github.com/bayfrontmedia/veil#view
 *
 * @param string $file (Path to file from base path, excluding file extension)
 * @param array $data (Data to pass to view in dot notation)
 * @param bool $minify (Minify compiled HTML?)
 *
 * @return void
 *
 * @throws NotFoundException
 * @throws FileNotFoundException
 */

function view(string $file, array $data = [], bool $minify = false): void
{
    echo get_view($file, $data, $minify);
}