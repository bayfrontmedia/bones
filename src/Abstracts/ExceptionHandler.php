<?php

namespace Bayfront\Bones\Abstracts;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\Response;
use Throwable;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * This class defines the default ways in which Bones
 * reports and responds to uncaught exceptions.
 *
 * These methods are executed by calling parent::method()
 * from within the Exceptions\Handler class.
 */
abstract class ExceptionHandler
{

    protected function getDataArray(Response $response, Throwable $e): array
    {

        $message = $e->getMessage();

        $data = [
            'success' => false,
            'error' => [
                'status' => (string)$response->getStatusCode()['code'],
                'error' => $response->getStatusCode()['phrase'],
                'message' => $message,
                'type' => get_class($e),
                'code' => (string)$e->getCode(),
                'path' => (App::getInterface() == App::INTERFACE_HTTP) ? Request::getRequest(Request::PART_PATH) : '',
                'timestamp' => date('c')
            ]
        ];

        if (App::getConfig('app.debug')) {

            $data['meta']['exception'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_as_string' => $e->getTraceAsString(),
                //'trace' => print_r($e->getTrace(), true)
            ];

            $previous = $e->getPrevious();

            if (null !== $previous) {

                $data['meta']['previous'] = [
                    'type' => get_class($previous),
                    'file' => $previous->getFile(),
                    'line' => $previous->getLine(),
                    'trace_as_string' => $previous->getTraceAsString(),
                    'trace' => print_r($previous->getTrace(), true)
                ];

            }

            $data['meta']['bones'] = [
                'version' => App::getBonesVersion(),
                'debug' => (App::isDebug()) ? 'true' : 'false',
                'environment' => App::environment(),
                'elapsed_secs' => App::getElapsedTime()
            ];

        }

        return $data;

    }

    /**
     * Create response body.
     *
     * @param $data
     *
     * @return string
     */

    protected function bodyAsText($data): string
    {

        $body = '<h1>&#x1F6A7; Error: ' . Arr::get($data, 'error.message') . '</h1><ul>';

        $body .= '<li><strong>Status:</strong> ' . Arr::get($data, 'error.status') . '</li>';

        $body .= '<li><strong>Phrase:</strong> ' . Arr::get($data, 'error.error') . '</li>';

        $body .= '<li><strong>Message:</strong> ' . Arr::get($data, 'error.message') . '</li>';

        $body .= '<li><strong>Code:</strong> ' . Arr::get($data, 'error.code') . '</li>';

        $body .= '<li><strong>Timestamp:</strong> ' . Arr::get($data, 'error.timestamp') . '</li>';

        $body .= '</ul>';

        return $body;

    }

    /**
     * Respond to exception.
     *
     * @param Response $response
     * @param Throwable $e
     *
     * @return void
     * @throws NotFoundException
     * @throws ContainerException
     */

    public function respond(Response $response, Throwable $e): void
    {

        if (App::getInterface() == App::INTERFACE_CLI) {

            // Whoops

            $run = new Run;
            $handler = new PlainTextHandler;

            $run->pushHandler($handler);
            $run->handleException($e);

            return;

        }

        // Interface = HTTP

        $data = $this->getDataArray($response, $e);

        // Send response

        if (Request::wantsJson()) { // Send response as JSON

            if (App::getConfig('app.debug') === true) {
                $response->sendJson($data);
            } else {
                $response->sendJson(Arr::except($data, 'meta'));
            }

        } else { // Send response as text

            if (App::getConfig('app.debug') === true) {

                $run = new Run;

                $handler = new PrettyPageHandler;

                $handler->addDataTable('Error', Arr::get($data, 'error', []));

                $handler->addDataTable('Bones', Arr::get($data, 'meta.bones', []));

                $handler->setPageTitle('Error: ' . $e->getMessage());

                $run->pushHandler($handler);

                $run->sendHttpCode($response->getStatusCode()['code']);

                $run->handleException($e);

            } else { // Not in debug mode

                // Attempt to route to controller

                $class_name = rtrim(App::getConfig('app.namespace'), '\\') . '\Controllers\Errors';

                if (App::getConfig('router.class_namespace')) { // Router is installed - use its namespace

                    $class_name = App::getConfig('router.class_namespace') . '\Errors';

                }

                $method = 'error' . Arr::get($data, 'error.status');

                if (method_exists($class_name, $method)) {

                    $container = App::getContainer();

                    $controller = $container->make($class_name);

                    $controller->$method($data);

                    return;

                }

                // Unable to route to controller

                $response->setBody($this->bodyAsText(Arr::except($data, 'meta')))->send();

            }

        }

    }

}