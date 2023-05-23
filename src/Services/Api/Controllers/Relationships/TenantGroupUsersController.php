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
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantGroupUsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantGroupUsersController extends PrivateApiController implements RelationshipInterface
{

    protected TenantGroupUsersModel $tenantGroupUsersModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantGroupUsersModel $tenantGroupUsersModel)
    {
        $this->tenantGroupUsersModel = $tenantGroupUsersModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add users to tenant group.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws UnexpectedApiException
     * @throws InvalidStatusCodeException
     */
    public function add(array $args): void
    {

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.group.users.add',
            'tenant.group.users.add'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantGroupUsersModel->add($args['tenant_id'], $args['group_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10200);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10201);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant group users collection.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.group.users.read',
            'tenant.group.users.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'users');

        try {

            $results = $this->tenantGroupUsersModel->getCollection($args['tenant_id'], $args['group_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10202);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10203);
        }

        $schema = UsersCollection::create($results, [
            'collection_prefix' => '/users',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove users from tenant group.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.group.users.remove',
            'tenant.group.users.remove'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantGroupUsersModel->remove($args['tenant_id'], $args['group_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10204);
        }

        $this->response->setStatusCode(204)->send();

    }

}