<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

use Bayfront\Bones\App;
use Bayfront\Bones\Exceptions\FileNotFoundException;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Exceptions\ModelException;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Container\Container;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;

/**
 * Returns datetime in Y-m-d H:i:s format for current time
 * or of an optional timestamp.
 *
 * @param int $timestamp (Optional timestamp)
 *
 * @return string
 */

function get_datetime(int $timestamp = 0): string
{
    if (0 === $timestamp) {
        $timestamp = time();
    }

    return date('Y-m-d H:i:s', $timestamp);

}

/**
 * Returns the fully qualified path to the APP_ROOT_PATH directory,
 * ensuring a leading slash, no trailing slash and single forward slashes.
 *
 * @param string $path (Path relative to the APP_ROOT_PATH directory)
 *
 * @return string
 *
 * @noinspection PhpUndefinedConstantInspection
 */

function root_path(string $path = ''): string
{
    return str_replace('//', '/', '/' . trim(APP_ROOT_PATH . '/' . $path, '/'));
}

/**
 * Returns the fully qualified path to the APP_PUBLIC_PATH directory
 * ensuring a leading slash, no trailing slash and single forward slashes.
 *
 * @param string $path (Path relative to the APP_PUBLIC_PATH directory)
 *
 * @return string
 *
 * @noinspection PhpUndefinedConstantInspection
 */

function public_path(string $path = ''): string
{
    return str_replace('//', '/', '/' . trim(APP_PUBLIC_PATH . '/' . $path, '/'));
}

/**
 * Returns the fully qualified path to the APP_CONFIG_PATH directory
 * ensuring a leading slash, no trailing slash and single forward slashes.
 *
 * @param string $path (Path relative to the APP_CONFIG_PATH directory)
 *
 * @return string
 */

function config_path(string $path = ''): string
{
    return str_replace('//', '/', '/' . trim(APP_CONFIG_PATH . '/' . $path, '/'));
}

/**
 * Returns the fully qualified path to the APP_RESOURCES_PATH directory
 * ensuring a leading slash, no trailing slash and single forward slashes.
 *
 * @param string $path (Path relative to the APP_RESOURCES_PATH directory)
 *
 * @return string
 */

function resources_path(string $path = ''): string
{
    return str_replace('//', '/', '/' . trim(APP_RESOURCES_PATH . '/' . $path, '/'));
}

/**
 * Returns the fully qualified path to the APP_STORAGE_PATH directory
 * ensuring a leading slash, no trailing slash and single forward slashes.
 *
 * @param string $path (Path relative to the APP_STORAGE_PATH directory)
 *
 * @return string
 */

function storage_path(string $path = ''): string
{
    return str_replace('//', '/', '/' . trim(APP_STORAGE_PATH . '/' . $path, '/'));
}

/**
 * Returns value of .env variable, or default value if not existing.
 *
 * Converts strings from "true", "false" and "null".
 *
 * @param string $key
 * @param mixed $default (Default value to return if not existing)
 *
 * @return mixed
 */

function get_env(string $key, $default = NULL)
{

    if (isset($_ENV[$key])) {

        // String to boolean / NULL

        switch ($_ENV[$key]) {

            case 'true':

                return true;

            case 'false':

                return false;

            case 'null':

                return NULL;

            default:

                return $_ENV[$key];

        }

    }

    return $default;

}

/**
 * @param string $key
 * @param null $default
 *
 * @return mixed
 *
 * @see Bayfront\Bones\App::getConfig()
 *
 */

function get_config(string $key, $default = NULL)
{
    return App::getConfig($key, $default);
}

/**
 * @return Container
 *
 * @see Bayfront\Bones\App::getConfig()
 *
 */

function get_container(): Container
{
    return App::getContainer();
}

/**
 * @param string $id
 *
 * @return bool
 */

function in_container(string $id): bool
{
    return App::inContainer($id);
}

/**
 * @param string $id
 *
 * @return mixed
 *
 * @throws NotFoundException
 */

function get_from_container(string $id)
{
    return App::getFromContainer($id);
}

/**
 * @param string $id
 * @param object $object
 *
 * @return void
 */

function put_in_container(string $id, object $object): void
{
    App::putInContainer($id, $object);
}

/**
 * @param string $id
 * @param string $class
 * @param array $params
 *
 * @return mixed
 *
 * @throws ContainerException
 */

function set_in_container(string $id, string $class, array $params = [])
{
    return App::setInContainer($id, $class, $params);
}

/**
 *
 * @param string $model
 * @param array $params
 * @param bool $force_unique
 *
 * @return mixed
 *
 * @throws ModelException
 *
 * @see Bayfront\Bones\App::getModel()
 *
 */

function get_model(string $model, array $params = [], bool $force_unique = false)
{
    return App::getModel($model, $params, $force_unique);
}

/**
 * @param string $service
 * @param array $params
 * @param bool $force_unique
 *
 * @return mixed
 *
 * @throws ServiceException
 *
 * @see Bayfront\Bones\App::getService()
 *
 */

function get_service(string $service, array $params = [], bool $force_unique = false)
{
    return App::getService($service, $params, $force_unique);
}

/**
 *
 * @param $helpers
 *
 * @throws FileNotFoundException
 *
 * @see Bayfront\Bones\App::useHelper()
 *
 */

function use_helper($helpers): void
{
    App::useHelper($helpers);
}

/**
 * @param int $characters
 *
 * @return string
 *
 * @throws Exception
 *
 * @see Bayfront\Bones\App::createKey()
 *
 */

function create_key(int $characters = 32): string
{
    return App::createKey($characters);
}

/**
 * @param int $code
 * @param string $message
 * @param array $headers
 * @param bool $reset_response (Reset the HTTP response after fetching it from the services container)
 *
 * @return void
 *
 * @throws HttpException
 * @throws InvalidStatusCodeException
 * @throws NotFoundException
 *
 * @see Bayfront\Bones\App::abort()
 *
 */

function abort(int $code, string $message = '', array $headers = [], bool $reset_response = false): void
{
    App::abort($code, $message, $headers, $reset_response);
}

/**
 * @return bool
 *
 * @see Bayfront\Bones\App::isCLI()
 *
 */

function is_cli(): bool
{
    return App::isCLI();
}

/**
 * @return bool
 *
 * @see Bayfront\Bones\App::isCron()
 *
 */

function is_cron(): bool
{
    return App::isCron();
}