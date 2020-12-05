<?php
/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;
use Bayfront\MonologFactory\LoggerFactory;

/**
 * Get LoggerFactory instance from container.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory
 *
 * @return LoggerFactory
 *
 * @throws NotFoundException
 */

function get_logs(): LoggerFactory
{
    return App::getFromContainer('logs');
}

/**
 * Logs with an arbitrary level.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#log
 *
 * @param $level
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_event($level, string $message, array $context, string $channel = NULL)
{

    $context = do_filter('logs.context', $context); // Filter

    if (NULL === $channel) {

        get_logs()->log($level, $message, $context);

    } else {

        get_logs()->channel($channel)->log($level, $message, $context);

    }

}

/**
 * Detailed debug information.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#debug
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_debug(string $message, array $context = [], string $channel = NULL)
{
    log_event('DEBUG', $message, $context, $channel);
}

/**
 * Interesting events.
 *
 * Example: User logs in, SQL logs.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#info
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_info(string $message, array $context = [], string $channel = NULL)
{
    log_event('INFO', $message, $context, $channel);
}

/**
 * Normal but significant events.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#notice
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_notice(string $message, array $context = [], string $channel = NULL)
{
    log_event('NOTICE', $message, $context, $channel);
}

/**
 * Exceptional occurrences that are not errors.
 *
 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#warning
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_warning(string $message, array $context = [], string $channel = NULL)
{
    log_event('WARNING', $message, $context, $channel);
}

/**
 * Runtime errors that do not require immediate action but should typically be logged and monitored.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#error
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_error(string $message, array $context = [], string $channel = NULL)
{
    log_event('ERROR', $message, $context, $channel);
}

/**
 * Critical conditions.
 *
 * Example: Application component unavailable, unexpected exception.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#critical
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_critical(string $message, array $context = [], string $channel = NULL)
{
    log_event('CRITICAL', $message, $context, $channel);
}

/**
 * Action must be taken immediately.
 *
 * Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#alert
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_alert(string $message, array $context = [], string $channel = NULL)
{
    log_event('ALERT', $message, $context, $channel);
}

/**
 * System is unusable.
 *
 * See: https://github.com/bayfrontmedia/monolog-factory#emergency
 *
 * @param string $message
 * @param array $context
 * @param string|null $channel (Log channel to use. Defaults to current channel.)
 *
 * @throws NotFoundException
 * @throws ChannelNotFoundException
 */

function log_emergency(string $message, array $context = [], string $channel = NULL)
{
    log_event('EMERGENCY', $message, $context, $channel);
}