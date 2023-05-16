<?php

namespace Bayfront\Bones\Services\Api\Controllers\Relationships;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\RelationshipInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantUsersController extends PrivateApiController implements RelationshipInterface
{

    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add users to tenant.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function add(array $args): void
    {

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantUsersModel->add($args['tenant_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant users collection.
     * Users who own or belong to tenant.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function getCollection(array $args): void
    {

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'users');

        try {

            $results = $this->tenantUsersModel->getCollection($args['tenant_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = UsersCollection::create($results, [
            'collection_prefix' => '/users',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove users from tenant. (Owner cannot be removed)
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function remove(array $args): void
    {

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantUsersModel->remove($args['tenant_id'], $ids);

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $this->response->setStatusCode(204)->send();

    }

}