<?php

namespace Bayfront\Bones\Exceptions\Handler;

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\Response;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;
use Bayfront\Validator\Validate;
use Bayfront\Veil\FileNotFoundException;
use Throwable;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * This class defines the default ways in which Bones
 * reports and responds to uncaught exceptions.
 *
 * These methods are executed by calling: parent::{method}
 * from within the Exceptions\Handler class.
 */
abstract class ExceptionHandler
{

    /**
     * Report exception.
     *
     * @param Throwable $e
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws ChannelNotFoundException
     */

    public function report(Throwable $e): void
    {

        if (function_exists('log_critical')) {

            log_critical('Exception (' . get_class($e) . '): ' . $e->getMessage(), [
                'exception' => $e
            ]);

        }

    }

    /**
     * Respond to exception.
     *
     * @param Response $response
     * @param Throwable $e
     *
     * @return void
     */

    public function respond(Response $response, Throwable $e): void
    {

        if (App::getInterface() == App::INTERFACE_CLI) {

            $run = new Run;

            $handler = new PlainTextHandler;

            $run->pushHandler($handler);

            $run->handleException($e);

            return;

        }

        // Render message

        $message = $e->getMessage();

        $data = [
            'status' => (string)$response->getStatusCode()['code'],
            'title' => $response->getStatusCode()['phrase'],
            'detail' => $message,
            'code' => (string)$e->getCode(),
        ];

        if (function_exists('get_config') && true === get_config('app.debug_mode')) {

            $data['meta'] = [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_as_string' => $e->getTraceAsString(),
                'trace' => print_r($e->getTrace(), true)
            ];

            $previous = $e->getPrevious();

            if (NULL !== $previous) {

                $data['meta']['previous'] = [
                    'type' => get_class($previous),
                    'file' => $previous->getFile(),
                    'line' => $previous->getLine(),
                    'trace_as_string' => $previous->getTraceAsString(),
                    'trace' => print_r($previous->getTrace(), true)
                ];

            }

        }

        if (Request::wantsJson()) { // Send response as JSON

            $headers = $response->getHeaders();

            if (!isset($headers['Content-Type'])) { // A more specific Content-Type may have already been set

                $response->setHeaders([
                    'Content-Type' => 'application/vnd.api+json'
                ]);

            }

            if (!Validate::json($message)) { // Only modify the message if it is not yet JSON

                /*
                 * Format as JSON:API error
                 * See: https://jsonapi.org/format/#errors
                 */

                $message = json_encode([
                    'errors' => [
                        $data
                    ]
                ]);

            }

            $response->setBody($message)->send();

        } else { // Send response as text

            if (function_exists('get_config') && true === get_config('app.debug_mode')) { // Use Whoops library

                $run = new Run;

                $handler = new PrettyPageHandler;

                $handler->addDataTable('Bones Data', [
                    'version' => BONES_VERSION
                ]);

                $handler->setPageTitle('Error: ' . $e->getMessage());

                $run->pushHandler($handler);

                $run->sendHttpCode($response->getStatusCode()['code']);

                $run->handleException($e);

            } else { // Not in debug mode

                if (App::getContainer()->has('veil')) { // Veil is optional, so check if it exists

                    try { // Attempt to find a template for this HTTP status code

                        $response->setBody(App::getFromContainer('veil')->getView('/errors/' . $response->getStatusCode()['code'], $data));

                    } catch (NotFoundException | FileNotFoundException $e) { // Body as text (template does not exist)

                        $response->setBody($this->_bodyAsText($message, $data));

                    }

                } else { // Body as text

                    $response->setBody($this->_bodyAsText($message, $data));

                }

                // Send response

                $response->send();

            }

        }

    }

    /**
     * Create response body.
     *
     * @param $message
     * @param $data
     *
     * @return string
     */

    private function _bodyAsText($message, $data): string
    {

        $body = '<h1>Error: ' . $message . '</h1><ul>';

        $body .= '<li><strong>Status:</strong> ' . $data['status'] . '</li>';

        $body .= '<li><strong>Title:</strong> ' . $data['title'] . '</li>';

        $body .= '<li><strong>Detail:</strong> ' . $data['detail'] . '</li>';

        $body .= '<li><strong>Code:</strong> ' . $data['code'] . '</li>';

        $body .= '</ul>';

        return $body;

    }

}