<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PublicApiController;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersResource;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class PublicController extends PublicApiController
{

    protected UsersModel $usersModel;

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     * @param UsersModel $usersModel
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, FilterService $filters, Response $response, UsersModel $usersModel)
    {
        parent::__construct($events, $filters, $response);

        $this->usersModel = $usersModel;

    }

    /**
     * @return void
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     * @throws \Bayfront\Bones\Services\Api\Exceptions\NotFoundException
     */
    public function createUser(): void
    {

        $attrs = $this->getResourceAttributesOrAbort('users', [
            'email',
            'password'
        ], [
            'email',
            'password',
            'meta',
            //'enabled'
        ]);

        try {

            if (App::getConfig('api.registration.enabled')) {

                $attrs['enabled'] = true;
                $id = $this->usersModel->create($attrs);
                $created = $this->usersModel->get($id);

            } else {

                $attrs['enabled'] = false;
                $id = $this->usersModel->create($attrs, true);
                $created = $this->usersModel->get($id, ['*'], true);

            }

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        }

        $schema = UsersResource::create($created, [
            'user_id' => $id
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

}