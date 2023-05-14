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
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantsResource;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class PublicController extends PublicApiController
{

    protected UsersModel $usersModel;
    protected TenantsModel $tenantsModel;

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     * @param UsersModel $usersModel
     * @param TenantsModel $tenantsModel
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, FilterService $filters, Response $response, UsersModel $usersModel, TenantsModel $tenantsModel)
    {
        parent::__construct($events, $filters, $response);

        $this->usersModel = $usersModel;
        $this->tenantsModel = $tenantsModel;

    }

    /**
     * Create user.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
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

            if (App::getConfig('api.registration.users.enabled')) {

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

    /**
     * Create tenant.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function createTenant(): void
    {

        $attrs = $this->getResourceAttributesOrAbort('tenants', $this->tenantsModel->getRequiredAttrs(), $this->tenantsModel->getAllowedAttrs());

        try {

            $id = $this->tenantsModel->create($attrs);
            $created= $this->tenantsModel->get($id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        }

        $schema = TenantsResource::create($created, [
            'tenant_id' => $id
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

}