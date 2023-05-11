<?php

namespace Bayfront\Bones\Services\Api\Exceptions;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\ExceptionHandler;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\Response;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{

    /**
     * Modify exception response when request wants JSON.
     *
     * @param Response $response
     * @param Throwable $e
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function respond(Response $response, Throwable $e): void
    {

        if (App::getInterface() == App::INTERFACE_HTTP) {

            if (!Request::wantsJson()) {
                parent::respond($response, $e);
                return;
            }

        }

        /*
         * Build ErrorResponse schema
         */

        $data = $this->getDataArray($response, $e);

        $return = [
            'status' => Arr::get($data, 'error.status'),
            'title' => Arr::get($data, 'error.error'),
        ];

        if ($response->getStatusCode()['code'] !== 500) {
            $return['detail'] = Arr::get($data, 'error.message');
        }

        // Check for code and API documentation

        if (Arr::get($data, 'error.code', '0') !== '0') {

            $return['code'] = Arr::get($data, 'error.code', '0');

            if (App::getConfig('api-docs.' . $return['code'])) {
                $return['links']['about'] = App::getConfig('api-docs.' . $return['code']);
            }

        }

        if (App::getConfig('app.debug') === true) {
            $return['meta'] = Arr::get($data, 'meta', []);
        }

        $response->sendJson([
            'errors' => [
                $return
            ]
        ]);

    }

}